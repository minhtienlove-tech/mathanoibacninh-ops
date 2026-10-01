<?php
/**
 * Import the approved content-plan package as private WordPress drafts.
 * Run only through WP-CLI with EYECARE_CONTENT_IMPORT_DRAFTS=1 and
 * EYECARE_CONTENT_PACKAGE_DIR pointing to the private uploaded package.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_CONTENT_IMPORT_DRAFTS' ) ) {
	throw new RuntimeException( 'Draft import requires WP-CLI and the explicit import gate.' );
}

$package_dir = realpath( (string) getenv( 'EYECARE_CONTENT_PACKAGE_DIR' ) );
if ( ! $package_dir || ! is_dir( $package_dir ) ) {
	throw new RuntimeException( 'Package directory is missing.' );
}

$manifest = json_decode( (string) file_get_contents( $package_dir . '/manifest.json' ), true );
if ( ! is_array( $manifest ) ) {
	throw new RuntimeException( 'Invalid package manifest.' );
}

$items = array_values( array_filter( $manifest, static function ( $item ) {
	return is_array( $item ) && 'new-wordpress-draft' === ( $item['action'] ?? '' );
} ) );
if ( 12 !== count( $items ) ) {
	throw new RuntimeException( 'Expected exactly 12 new draft entries.' );
}

$parent = get_term_by( 'slug', 'kien-thuc', 'category' );
if ( ! $parent instanceof WP_Term ) {
	throw new RuntimeException( 'The knowledge category is missing.' );
}

$category_map = array(
	'Chuẩn bị đi khám và bảo hiểm'     => 'chuan-bi-kham-bao-hiem',
	'Tật khúc xạ ở người lớn'          => 'tat-khuc-xa-nguoi-lon',
	'Cận thị trẻ em'                   => 'can-thi-tre-em',
	'Kiến thức phẫu thuật khúc xạ (đề xuất)' => 'kien-thuc-phau-thuat-khuc-xa',
);

// Validate all package entries before changing the database.
$seen_slugs = array();
foreach ( $items as $item ) {
	$id   = (string) ( $item['id'] ?? '' );
	$url  = (string) ( $item['proposed_url'] ?? '' );
	$slug = basename( trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) );
	if ( ! preg_match( '/^[0-9]{2}$/', $id ) || ( $item['html'] ?? '' ) !== $id . '.html' ||
		'local-review-draft' !== ( $item['status'] ?? '' ) ||
		! isset( $category_map[ $item['category'] ?? '' ] ) ||
		! preg_match( '/^[a-z0-9-]+$/', $slug ) || isset( $seen_slugs[ $slug ] ) ||
		$url !== home_url( '/kien-thuc/' . $slug . '/' ) ||
		! is_file( $package_dir . '/' . $id . '.html' ) ||
		'' === trim( (string) ( $item['wp_title'] ?? '' ) ) ) {
		throw new RuntimeException( 'Invalid package entry ' . $id );
	}
	$html = (string) file_get_contents( $package_dir . '/' . $id . '.html' );
	if ( '' === trim( $html ) || preg_match( '/<h1\b/i', $html ) || stripos( $html, '<script' ) !== false ) {
		throw new RuntimeException( 'Unsafe or empty HTML in entry ' . $id );
	}
	$seen_slugs[ $slug ] = true;
}

foreach ( $items as $item ) {
	$id   = (string) $item['id'];
	$slug = basename( trim( (string) wp_parse_url( $item['proposed_url'], PHP_URL_PATH ), '/' ) );
	$matches = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => array( 'draft', 'pending', 'future', 'private', 'publish', 'trash' ),
		'posts_per_page' => 2,
		'meta_key'       => '_eyecare_content_plan_id',
		'meta_value'     => $id,
		'fields'         => 'ids',
	) );
	if ( $matches ) {
		$post_id = (int) $matches[0];
		if ( 1 !== count( $matches ) || 'draft' !== get_post_status( $post_id ) ) {
			throw new RuntimeException( 'Entry ' . $id . ' already exists outside one draft.' );
		}
		WP_CLI::log( 'SKIP existing draft ' . $id . ' post=' . $post_id );
		continue;
	}

	$collision = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => array( 'draft', 'pending', 'future', 'private', 'publish', 'trash' ),
		'name'           => $slug,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );
	if ( $collision ) {
		throw new RuntimeException( 'Slug collision for entry ' . $id . ': ' . $slug );
	}

	$category_slug = $category_map[ $item['category'] ];
	$category      = get_term_by( 'slug', $category_slug, 'category' );
	if ( ! $category instanceof WP_Term && 'kien-thuc-phau-thuat-khuc-xa' === $category_slug ) {
		$created = wp_insert_term( 'Kiến thức phẫu thuật khúc xạ', 'category', array(
			'slug'        => $category_slug,
			'parent'      => $parent->term_id,
			'description' => 'Thông tin tham khảo về đánh giá và phẫu thuật khúc xạ; chỉ định cần bác sĩ chuyên khoa xác nhận.',
		) );
		if ( is_wp_error( $created ) ) {
			throw new RuntimeException( 'Cannot create category: ' . $created->get_error_message() );
		}
		$category = get_term( (int) $created['term_id'], 'category' );
	}
	if ( ! $category instanceof WP_Term || (int) $category->parent !== (int) $parent->term_id ) {
		throw new RuntimeException( 'Missing or misplaced category for entry ' . $id );
	}

	$html = (string) file_get_contents( $package_dir . '/' . $id . '.html' );
	$post_id = wp_insert_post( array(
		'post_type'     => 'post',
		'post_status'   => 'draft',
		'post_author'   => 0,
		'post_title'    => (string) $item['wp_title'],
		'post_name'     => $slug,
		'post_content'  => $html,
		'post_excerpt'  => (string) ( $item['meta_description'] ?? '' ),
		'post_category' => array( (int) $parent->term_id, (int) $category->term_id ),
	), true );
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( 'Cannot import entry ' . $id . ': ' . $post_id->get_error_message() );
	}
	update_post_meta( $post_id, '_eyecare_content_plan_id', $id );
	update_post_meta( $post_id, '_eyecare_content_review_status', 'pending-author-and-medical-review' );
	update_post_meta( $post_id, '_eyecare_seo_title_proposal', (string) ( $item['seo_title'] ?? '' ) );
	update_post_meta( $post_id, '_eyecare_meta_description_proposal', (string) ( $item['meta_description'] ?? '' ) );
	update_post_meta( $post_id, '_eyecare_proposed_url', (string) $item['proposed_url'] );
	update_post_meta( $post_id, '_bvmat_tu_khoa_chinh', (string) ( $item['primary_keyword'] ?? '' ) );
	WP_CLI::log( 'CREATED draft ' . $id . ' post=' . $post_id . ' slug=' . $slug );
}

WP_CLI::success( 'Draft import finished; no post was published.' );
