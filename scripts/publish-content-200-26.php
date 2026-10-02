<?php
/** Publish only the 26 prepared long-form drafts after final preflight. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_PUBLISH_200_26' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI publish gate required.' );
}
$dir = realpath( (string) getenv( 'EYECARE_200_26_PACKAGE_DIR' ) );
$items = $dir ? json_decode( (string) file_get_contents( $dir . '/manifest.json' ), true ) : null;
if ( ! is_array( $items ) || 26 !== count( $items ) ) {
	throw new RuntimeException( 'Expected exactly 26 package entries.' );
}
$published = (int) wp_count_posts( 'post' )->publish;
if ( 174 !== $published ) {
	throw new RuntimeException( "Expected 174 published posts; found {$published}." );
}
$ready = array();
foreach ( $items as $item ) {
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'draft', 'name' => $item['slug'], 'posts_per_page' => 2 ) );
	if ( 1 !== count( $posts ) ) {
		throw new RuntimeException( "Expected one draft for {$item['slug']}." );
	}
	$post = $posts[0];
	$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $post->post_content ) ) );
	$category = get_term_by( 'slug', $item['category'], 'category' );
	if ( $post->post_title !== $item['title'] || (string) get_post_meta( $post->ID, '_eyecare_content_plan_id', true ) !== 'N' . $item['number'] ||
		'pending-author-and-medical-review' !== get_post_meta( $post->ID, '_eyecare_content_review_status', true ) ||
		(int) $post->post_author !== 0 || ! $category instanceof WP_Term || ! has_category( $category->term_id, $post ) ||
		! has_post_thumbnail( $post ) || count( $words ) < 1400 || ! str_contains( $post->post_content, 'eyecare-nguon-tham-khao' ) ) {
		throw new RuntimeException( "Draft failed preflight: {$item['slug']}." );
	}
	$ready[] = $post;
}
if ( '1' !== getenv( 'EYECARE_PUBLISH_200_26_EXECUTE' ) ) {
	WP_CLI::success( 'Publication preflight passed for 26 drafts; no writes (set EYECARE_PUBLISH_200_26_EXECUTE=1).' );
	return;
}
foreach ( $ready as $post ) {
	$result = wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'publish' ), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( "Publish failed for {$post->ID}: " . $result->get_error_message() );
	}
	WP_CLI::log( "PUBLISHED {$post->ID} " . get_permalink( $post->ID ) );
}
WP_CLI::success( 'Published 26 articles; medical reviewer attribution remains pending.' );
