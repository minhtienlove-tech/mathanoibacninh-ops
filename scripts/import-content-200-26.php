<?php
/** Import the 26 checked long-form articles as drafts via WP-CLI. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_IMPORT_200_26' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI import gate required.' );
}
$dir = realpath( (string) getenv( 'EYECARE_200_26_PACKAGE_DIR' ) );
$items = $dir ? json_decode( (string) file_get_contents( $dir . '/manifest.json' ), true ) : null;
$parent = get_term_by( 'slug', 'kien-thuc', 'category' );
if ( ! is_array( $items ) || 26 !== count( $items ) || ! $parent instanceof WP_Term ) {
	throw new RuntimeException( 'Expected exactly 26 entries and parent category.' );
}
$prepared = array();
$slugs = array();
foreach ( $items as $item ) {
	$number = (string) ( $item['number'] ?? '' );
	$slug = (string) ( $item['slug'] ?? '' );
	$file = $dir . '/' . $number . '.html';
	$category = get_term_by( 'slug', (string) ( $item['category'] ?? '' ), 'category' );
	$image_source = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'name' => (string) ( $item['image_from'] ?? '' ), 'posts_per_page' => 2 ) );
	$collision = get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private', 'trash' ), 'name' => $slug, 'posts_per_page' => 2, 'fields' => 'ids' ) );
	if ( ! preg_match( '/^[0-9]{2}$/', $number ) || ! preg_match( '/^[a-z0-9-]+$/', $slug ) || isset( $slugs[ $slug ] ) || ! is_file( $file ) ||
		( $item['url'] ?? '' ) !== home_url( '/kien-thuc/' . $slug . '/' ) || ! $category instanceof WP_Term || 1 !== count( $image_source ) ||
		! has_post_thumbnail( $image_source[0] ) || $collision || empty( $item['title'] ) || empty( $item['description'] ) ) {
		throw new RuntimeException( "Invalid package or collision for {$number} / {$slug}." );
	}
	$html = (string) file_get_contents( $file );
	$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $html ) ) );
	if ( preg_match( '/<h1\b|<script\b|<iframe\b/i', $html ) || ! str_contains( $html, 'eyecare-nguon-tham-khao' ) ||
		4 > preg_match_all( '/<h2\b/i', $html ) || count( $words ) < 1400 || count( $words ) > 1950 ) {
		throw new RuntimeException( "Content preflight failed for {$number}." );
	}
	$prepared[] = array( $item, $html, $category->term_id, get_post_thumbnail_id( $image_source[0] ) );
	$slugs[ $slug ] = true;
}
if ( '1' !== getenv( 'EYECARE_IMPORT_200_26_EXECUTE' ) ) {
	WP_CLI::success( 'Preflight passed for 26 posts; no writes (set EYECARE_IMPORT_200_26_EXECUTE=1).' );
	return;
}
foreach ( $prepared as $entry ) {
	list( $item, $html, $category_id, $image_id ) = $entry;
	$id = wp_insert_post( array(
		'post_type' => 'post', 'post_status' => 'draft', 'post_author' => 0,
		'post_name' => $item['slug'], 'post_title' => $item['title'],
		'post_excerpt' => $item['excerpt'], 'post_content' => wp_kses_post( $html ),
		'post_category' => array( $parent->term_id, $category_id ),
	), true );
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( "Import failed for {$item['number']}: " . $id->get_error_message() );
	}
	set_post_thumbnail( $id, $image_id );
	update_post_meta( $id, '_eyecare_content_review_status', 'pending-author-and-medical-review' );
	update_post_meta( $id, '_eyecare_content_plan_id', 'N' . $item['number'] );
	update_post_meta( $id, '_eyecare_seo_title_proposal', $item['title'] . ' | Bệnh viện Mắt Hà Nội – Bắc Ninh' );
	update_post_meta( $id, '_eyecare_meta_description_proposal', $item['description'] );
	WP_CLI::log( "DRAFT {$item['number']} ID={$id} {$item['slug']} image={$image_id}" );
}
WP_CLI::success( 'Imported 26 distinct drafts; no reviewer name or date asserted.' );
