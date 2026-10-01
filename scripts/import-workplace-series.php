<?php
/** Import the 15 workplace-eye articles as WordPress drafts after full preflight. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_IMPORT_WORKPLACE' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI import gate required.' );
}
$dir = realpath( (string) getenv( 'EYECARE_WORKPLACE_PACKAGE_DIR' ) );
if ( ! $dir || ! is_dir( $dir ) ) {
	throw new RuntimeException( 'Package directory not found.' );
}
$items = json_decode( (string) file_get_contents( $dir . '/manifest.json' ), true );
$parent = get_term_by( 'slug', 'kien-thuc', 'category' );
$category = get_term_by( 'slug', 'mat-va-moi-truong-lam-viec', 'category' );
if ( ! is_array( $items ) || 15 !== count( $items ) || ! $parent instanceof WP_Term || ! $category instanceof WP_Term ) {
	throw new RuntimeException( 'Expected 15 entries and both existing categories.' );
}

$validated = array();
$seen = array();
foreach ( $items as $item ) {
	$number = (string) ( $item['number'] ?? '' );
	$slug = (string) ( $item['slug'] ?? '' );
	$file = $dir . '/' . $number . '.html';
	if ( ! preg_match( '/^[0-9]{2}$/', $number ) || ! preg_match( '/^[a-z0-9-]+$/', $slug ) || isset( $seen[ $slug ] ) ||
		( $item['url'] ?? '' ) !== home_url( '/kien-thuc/' . $slug . '/' ) || ! is_file( $file ) ||
		( $item['html'] ?? '' ) !== $number . '.html' || empty( $item['title'] ) || empty( $item['description'] ) ) {
		throw new RuntimeException( "Invalid package entry {$number}." );
	}
	$html = (string) file_get_contents( $file );
	if ( preg_match( '/<h1\b|<script\b|<iframe\b|CẦN XÁC MINH/i', $html ) || 4 > preg_match_all( '/<h2\b/i', $html ) ) {
		throw new RuntimeException( "Unsafe or too little structure in {$number}." );
	}
	if ( ! isset( $item['word_count'] ) || (int) $item['word_count'] < 1450 || (int) $item['word_count'] > 1650 ) {
		throw new RuntimeException( "Incorrect word count in {$number}." );
	}
	$collision = get_posts( array(
		'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private', 'trash' ),
		'name' => $slug, 'posts_per_page' => 2, 'fields' => 'ids',
	) );
	if ( $collision ) {
		throw new RuntimeException( "Slug collision {$slug}." );
	}
	$validated[] = array( $item, $html );
	$seen[ $slug ] = true;
}

foreach ( $validated as $entry ) {
	list( $item, $html ) = $entry;
	$number = (string) $item['number'];
	$id = wp_insert_post( array(
		'post_type' => 'post',
		'post_status' => 'draft',
		'post_author' => 0,
		'post_name' => $item['slug'],
		'post_title' => $item['title'],
		'post_excerpt' => $item['excerpt'],
		'post_content' => $html,
		'post_category' => array( $parent->term_id, $category->term_id ),
	), true );
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( "Import {$number} failed: " . $id->get_error_message() );
	}
	update_post_meta( $id, '_eyecare_workplace_series_id', $number );
	update_post_meta( $id, '_eyecare_content_review_status', 'pending-author-and-medical-review' );
	update_post_meta( $id, '_eyecare_content_plan_id', 'W' . $number );
	update_post_meta( $id, '_eyecare_seo_title_proposal', $item['title'] . ' | Bệnh viện Mắt Hà Nội – Bắc Ninh' );
	update_post_meta( $id, '_eyecare_meta_description_proposal', $item['description'] );
	WP_CLI::log( "DRAFT {$number} ID={$id} slug={$item['slug']}" );
}
WP_CLI::success( '15 workplace-eye drafts imported; no medical reviewer attribution added.' );
