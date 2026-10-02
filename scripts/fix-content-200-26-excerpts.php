<?php
/** Correct Markdown markers in the scoped set of new article excerpts and SEO proposals. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_FIX_200_26_EXCERPTS' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI gate required.' );
}
$dir = realpath( (string) getenv( 'EYECARE_200_26_PACKAGE_DIR' ) );
$items = $dir ? json_decode( (string) file_get_contents( $dir . '/manifest.json' ), true ) : null;
if ( ! is_array( $items ) || 26 !== count( $items ) ) {
	throw new RuntimeException( 'Expected exactly 26 package entries.' );
}
$ready = array();
foreach ( $items as $item ) {
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'name' => $item['slug'], 'posts_per_page' => 2 ) );
	if ( 1 !== count( $posts ) || 'N' . $item['number'] !== get_post_meta( $posts[0]->ID, '_eyecare_content_plan_id', true ) ) {
		throw new RuntimeException( 'Article identity mismatch: ' . $item['slug'] );
	}
	$ready[] = array( $posts[0], $item );
}
if ( '1' !== getenv( 'EYECARE_FIX_200_26_EXCERPTS_EXECUTE' ) ) {
	WP_CLI::success( 'Preflight passed for 26 articles; no writes.' );
	return;
}
$backup = array();
foreach ( $ready as list( $post, $item ) ) {
	$backup[] = array( 'id' => $post->ID, 'post_excerpt' => $post->post_excerpt,
		'meta_description_proposal' => get_post_meta( $post->ID, '_eyecare_meta_description_proposal', true ) );
}
$backup_file = $dir . '/../original-excerpts.json';
if ( false === file_put_contents( $backup_file, wp_json_encode( $backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) ) {
	throw new RuntimeException( 'Cannot back up original excerpts.' );
}
foreach ( $ready as list( $post, $item ) ) {
	$clean = preg_replace( '/\*\*|__/', '', (string) $item['description'] );
	$result = wp_update_post( array( 'ID' => $post->ID, 'post_excerpt' => $clean ), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( 'Excerpt update failed: ' . $post->ID );
	}
	update_post_meta( $post->ID, '_eyecare_meta_description_proposal', $clean );
}
WP_CLI::success( 'Corrected 26 excerpts and SEO proposals; backup at ' . $backup_file );
