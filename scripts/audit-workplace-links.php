<?php
/** Read-only audit of links between the workplace series and its related posts. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	throw new RuntimeException( 'WP-CLI required.' );
}
$manifest_path = (string) getenv( 'EYECARE_WORKPLACE_MANIFEST' );
$items = json_decode( (string) file_get_contents( $manifest_path ), true );
if ( ! is_array( $items ) || 15 !== count( $items ) ) {
	throw new RuntimeException( 'Invalid manifest.' );
}
$new_urls = array_column( $items, 'url' );
$old_urls = array_unique( array_column( $items, 'internal' ) );
$urls = array_unique( array_merge( $new_urls, $old_urls ) );
foreach ( $urls as $url ) {
	$id = url_to_postid( $url );
	$post = $id ? get_post( $id ) : null;
	if ( ! $post ) {
		WP_CLI::log( wp_json_encode( array( 'url' => $url, 'error' => 'not found' ), JSON_UNESCAPED_UNICODE ) );
		continue;
	}
	$links = array();
	foreach ( $urls as $possible ) {
		if ( $url !== $possible && str_contains( $post->post_content, $possible ) ) {
			$links[] = $possible;
		}
	}
	$plain = trim( wp_strip_all_tags( $post->post_content ) );
	WP_CLI::log( wp_json_encode( array(
		'id' => $id,
		'url' => $url,
		'status' => $post->post_status,
		'title' => $post->post_title,
		'links' => $links,
		'end' => mb_substr( $plain, -420 ),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}
