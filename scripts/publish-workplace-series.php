<?php
/** Publish only the 15 preflighted workplace-eye drafts from this package. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_PUBLISH_WORKPLACE' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI publish gate required.' );
}
$dir = realpath( (string) getenv( 'EYECARE_WORKPLACE_PACKAGE_DIR' ) );
$items = $dir ? json_decode( (string) file_get_contents( $dir . '/manifest.json' ), true ) : null;
if ( ! is_array( $items ) || 15 !== count( $items ) ) {
	throw new RuntimeException( 'Expected exactly 15 package entries.' );
}
$category = get_term_by( 'slug', 'mat-va-moi-truong-lam-viec', 'category' );
if ( ! $category instanceof WP_Term ) {
	throw new RuntimeException( 'Workplace-eye category is missing.' );
}
$ready = array();
foreach ( $items as $item ) {
	$slug = (string) $item['slug'];
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'draft', 'name' => $slug, 'posts_per_page' => 2 ) );
	if ( 1 !== count( $posts ) ) {
		throw new RuntimeException( "Expected one draft for {$slug}." );
	}
	$post = $posts[0];
	$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $post->post_content ) ) );
	if ( $post->post_title !== $item['title'] || (string) get_post_meta( $post->ID, '_eyecare_workplace_series_id', true ) !== (string) $item['number'] ||
		'pending-author-and-medical-review' !== get_post_meta( $post->ID, '_eyecare_content_review_status', true ) ||
		(int) $post->post_author !== 0 || ! has_category( $category->term_id, $post ) || count( $words ) < 1450 ||
		! str_contains( $post->post_content, 'eyecare-nguon-tham-khao' ) ) {
		throw new RuntimeException( "Draft failed preflight: {$slug}." );
	}
	$ready[] = $post;
}
foreach ( $ready as $post ) {
	$result = wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'publish' ), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( "Publish failed for {$post->ID}: " . $result->get_error_message() );
	}
	WP_CLI::log( "PUBLISHED {$post->ID} " . get_permalink( $post->ID ) );
}
WP_CLI::success( 'Published 15 workplace-eye posts; reviewer attribution remains pending.' );
