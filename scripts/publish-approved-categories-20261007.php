<?php
/**
 * WP-CLI rollout for the 12 hospital-approved categories and introductory posts.
 * Stage this file beside category-intros/manifest.json in a server backup folder.
 * Dry run by default. Apply only with EYECARE_CATEGORY_APPLY=yes after DB backup.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$base = __DIR__;
$manifest_path = $base . '/category-intros/manifest.json';
$preflight_path = $base . '/category-preflight.json';
$progress_path = $base . '/category-created.json';
$apply = 'yes' === getenv( 'EYECARE_CATEGORY_APPLY' );
$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
if ( ! is_array( $manifest ) || empty( $manifest['entries'] ) || 12 !== count( $manifest['entries'] ) ) {
	WP_CLI::error( 'Manifest must contain exactly 12 entries.' );
}

$page_contents = array(
	53 => '<p>Tin bệnh viện tập hợp bài giới thiệu đội ngũ, đời sống cán bộ, hoạt động cộng đồng, câu chuyện người bệnh và các thông báo đã được xác nhận. Mỗi chuyên mục bên dưới có bài mở đầu giải thích phạm vi nội dung. Lịch hoạt động, chương trình hỗ trợ và trải nghiệm cá nhân chỉ được công bố khi đã kiểm tra thông tin và điều kiện chia sẻ.</p>',
	59 => '<p>Đây là nơi theo dõi cơ hội làm việc tại Bệnh viện Mắt Hà Nội – Bắc Ninh. Khi có vị trí đang tuyển, thông báo riêng sẽ nêu nhiệm vụ, yêu cầu, thời hạn, kênh nhận hồ sơ và người liên hệ. Bài giới thiệu chuyên mục giúp ứng viên biết cách đọc tin; bài này không phải thông báo tuyển dụng cho một vị trí cụ thể.</p>',
);
$page_hashes = array();
foreach ( $page_contents as $id => $content ) {
	$page = get_post( $id );
	$slug = 53 === $id ? 'tin-tuc' : 'tuyen-dung';
	if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status || $slug !== $page->post_name ) {
		WP_CLI::error( 'Unexpected page ID/status/slug: ' . $id );
	}
	$page_hashes[ $id ] = hash( 'sha256', $page->post_content );
}
$sitemap_before = get_option( 'obs_seo_sitemap', array() );
if ( ! is_array( $sitemap_before ) || ! isset( $sitemap_before['exclude_ids'] ) ) {
	WP_CLI::error( 'Unexpected OBS sitemap option; stop and inspect.' );
}

if ( ! $apply ) {
	file_put_contents( $preflight_path, wp_json_encode( array( 'page_hashes' => $page_hashes, 'sitemap_before' => $sitemap_before ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	WP_CLI::log( 'Dry run: 12 entries, 2 page IDs and sitemap option validated; no WordPress changes.' );
} else {
	$preflight = json_decode( (string) file_get_contents( $preflight_path ), true );
	if ( ! is_array( $preflight ) || $page_hashes !== ( $preflight['page_hashes'] ?? null )
		|| $sitemap_before !== ( $preflight['sitemap_before'] ?? null ) ) {
		WP_CLI::error( 'Page content or sitemap option changed after preflight. Stop and inspect.' );
	}
}

$created = file_exists( $progress_path ) ? json_decode( (string) file_get_contents( $progress_path ), true ) : array();
if ( ! is_array( $created ) ) {
	$created = array();
}
$created += array( 'terms' => array(), 'posts' => array(), 'pages_before' => array() );
$record = static function () use ( &$created, $progress_path ) {
	file_put_contents( $progress_path, wp_json_encode( $created, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
};

foreach ( $manifest['entries'] as $entry ) {
	$parent_slug = sanitize_title( $entry['parent_category_slug'] ?? '' );
	$category_slug = sanitize_title( $entry['category_slug'] ?? '' );
	$post_slug = sanitize_title( $entry['post_slug'] ?? '' );
	$filename = basename( (string) ( $entry['file'] ?? '' ) );
	$file = $base . '/category-intros/' . $filename;
	$parent = get_category_by_slug( $parent_slug );
	if ( ! $parent || ! $category_slug || ! $post_slug || ! is_file( $file ) || ! str_ends_with( $filename, '.html' ) ) {
		WP_CLI::error( 'Invalid entry or missing parent/file for ' . $category_slug );
	}
	$body = trim( (string) file_get_contents( $file ) );
	if ( strlen( wp_strip_all_tags( $body ) ) < 900 ) {
		WP_CLI::error( 'Intro body appears too short for ' . $category_slug );
	}
	$term = get_category_by_slug( $category_slug );
	if ( $term && (int) $term->parent !== (int) $parent->term_id ) {
		WP_CLI::error( 'Category slug already exists under a different parent: ' . $category_slug );
	}
	$existing = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'name' => $post_slug, 'posts_per_page' => 1 ) );
	if ( $existing ) {
		WP_CLI::error( 'Intro post slug already exists; refusing to overwrite: ' . $post_slug );
	}
	if ( ! $apply ) {
		WP_CLI::log( 'Would create: ' . $parent_slug . '/' . $category_slug . ' → ' . $post_slug );
		continue;
	}
	if ( ! $term ) {
		$result = wp_insert_term( (string) $entry['category'], 'category', array(
			'slug' => $category_slug,
			'parent' => (int) $parent->term_id,
			'description' => (string) ( $entry['category_description'] ?? '' ),
		) );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		$term = get_term( (int) $result['term_id'], 'category' );
		$created['terms'][ $category_slug ] = (int) $term->term_id;
		$record();
	}
	$post_id = wp_insert_post( array(
		'post_type' => 'post', 'post_status' => 'publish', 'post_author' => 0,
		'post_title' => (string) $entry['post_title'],
		'post_name' => $post_slug,
		'post_excerpt' => (string) $entry['excerpt'],
		'post_content' => wp_kses_post( $body ),
		'post_category' => array( (int) $term->term_id ),
		'meta_input' => array( '_eyecare_category_intro' => '1' ),
	), true );
	if ( is_wp_error( $post_id ) ) {
		WP_CLI::error( $post_id->get_error_message() );
	}
	$created['posts'][ $post_slug ] = (int) $post_id;
	$record();
	WP_CLI::log( 'Created ' . $category_slug . ' post #' . $post_id );
}

if ( $apply ) {
	foreach ( $page_contents as $id => $content ) {
		$page = get_post( $id );
		$created['pages_before'][ $id ] = array( 'post_content' => $page->post_content, 'post_modified' => $page->post_modified, 'post_modified_gmt' => $page->post_modified_gmt );
		$record();
		$result = wp_update_post( array( 'ID' => $id, 'post_content' => $content ), true );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
	}
	$created['sitemap_before'] = $sitemap_before;
	$record();
	$excluded = array_map( 'trim', explode( ',', (string) $sitemap_before['exclude_ids'] ) );
	$excluded = array_values( array_filter( $excluded, static function ( $id ) {
		return '' !== $id && ! in_array( $id, array( '53', '59' ), true );
	} ) );
	$sitemap_after = $sitemap_before;
	$sitemap_after['exclude_ids'] = implode( ',', $excluded );
	update_option( 'obs_seo_sitemap', $sitemap_after );
	flush_rewrite_rules( false );
	WP_CLI::success( 'Published 12 intro posts, updated two hub pages/sitemap and flushed rewrite rules.' );
}
