<?php
/** Restore the 26 post bodies from the pre-link snapshot after exact preflight. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_ROLLBACK_WORKPLACE_LINKS' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI rollback gate required.' );
}
$file = (string) getenv( 'EYECARE_WORKPLACE_LINK_BACKUP' );
$originals = json_decode( (string) file_get_contents( $file ), true );
if ( ! is_array( $originals ) || 26 !== count( $originals ) ) {
	throw new RuntimeException( 'Expected 26 backed-up post bodies.' );
}
$ready = array();
foreach ( $originals as $id => $record ) {
	$post = get_post( (int) $id );
	if ( ! $post || $post->post_name !== $record['slug'] || ! str_contains( $post->post_content, 'eyecare-workplace-links:v1' ) ) {
		throw new RuntimeException( "Post {$id} is missing or changed identity." );
	}
	$without_block = preg_replace(
		'/\s*<!-- eyecare-workplace-links:v1 -->\s*<nav\b[^>]*class="eyecare-workplace-links"[^>]*>.*?<\/nav>\s*/su',
		"\n",
		$post->post_content,
		-1,
		$count
	);
	$normalize_space = static function ( $value ) {
		return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
	};
	if ( 1 !== $count || $normalize_space( $without_block ) !== $normalize_space( (string) $record['content'] ) ) {
		throw new RuntimeException( "Post {$id} has additional changes; review before rollback." );
	}
	$ready[ (int) $id ] = (string) $record['content'];
}
if ( '1' === getenv( 'EYECARE_ROLLBACK_WORKPLACE_LINKS_DRY_RUN' ) ) {
	WP_CLI::success( 'Dry run: 26 post bodies match the saved originals after removing link blocks.' );
	return;
}
foreach ( $ready as $id => $content ) {
	$result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $content ) ), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( "Could not restore {$id}: " . $result->get_error_message() );
	}
	WP_CLI::log( "RESTORED {$id}" );
}
WP_CLI::success( 'Restored 26 original post bodies.' );
