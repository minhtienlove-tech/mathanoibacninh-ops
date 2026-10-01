<?php
/** Add reversible, editorially selected links in both directions. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_LINK_WORKPLACE' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI link gate required.' );
}
$manifest_file = (string) getenv( 'EYECARE_WORKPLACE_MANIFEST' );
$backup_file = (string) getenv( 'EYECARE_WORKPLACE_LINK_BACKUP' );
$items = json_decode( (string) file_get_contents( $manifest_file ), true );
if ( ! is_array( $items ) || 15 !== count( $items ) || ! $backup_file || file_exists( $backup_file ) ) {
	throw new RuntimeException( 'Manifest invalid or backup path unavailable.' );
}
// Article numbers are from docs/workplace-15/package/manifest.json.
$companions = array(
	'01' => array( '02', '03' ), '02' => array( '01', '03' ),
	'03' => array( '02', '13' ), '04' => array( '05', '06' ),
	'05' => array( '04', '12' ), '06' => array( '05', '12' ),
	'07' => array( '09', '14' ), '08' => array( '06', '12' ),
	'09' => array( '07', '14' ), '10' => array( '15', '05' ),
	'11' => array( '12', '07' ), '12' => array( '05', '11' ),
	'13' => array( '03', '12' ), '14' => array( '07', '09' ),
	'15' => array( '10', '03' ),
);
$by_number = array();
$old_groups = array();
$originals = array();
$posts = array();
foreach ( $items as $item ) {
	$number = (string) ( $item['number'] ?? '' );
	$id = url_to_postid( (string) ( $item['url'] ?? '' ) );
	$post = $id ? get_post( $id ) : null;
	if ( ! isset( $companions[ $number ] ) || ! $post || 'publish' !== $post->post_status ||
		$post->post_name !== $item['slug'] || (string) get_post_meta( $id, '_eyecare_workplace_series_id', true ) !== $number ) {
		throw new RuntimeException( "Invalid new article {$number}." );
	}
	$by_number[ $number ] = array( 'item' => $item, 'post' => $post );
	$old_groups[ $item['internal'] ][] = $number;
	$posts[ $id ] = $post;
}
foreach ( $old_groups as $url => $numbers ) {
	$id = url_to_postid( $url );
	$post = $id ? get_post( $id ) : null;
	if ( ! $post || 'publish' !== $post->post_status || get_permalink( $id ) !== $url ) {
		throw new RuntimeException( "Invalid existing article {$url}." );
	}
	$posts[ $id ] = $post;
}
if ( 26 !== count( $posts ) ) {
	throw new RuntimeException( 'Expected 15 new and 11 existing posts.' );
}
foreach ( $posts as $id => $post ) {
	if ( str_contains( $post->post_content, 'eyecare-workplace-links:v1' ) ) {
		throw new RuntimeException( "Links already present in {$id}." );
	}
	$originals[ $id ] = array( 'slug' => $post->post_name, 'content' => $post->post_content );
}
$json = wp_json_encode( $originals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
if ( ! $json || strlen( $json ) !== file_put_contents( $backup_file, $json, LOCK_EX ) ) {
	throw new RuntimeException( 'Could not save original post content.' );
}
$updates = array();
foreach ( $by_number as $number => $record ) {
	$post = $record['post'];
	$links = array();
	foreach ( $companions[ $number ] as $peer ) {
		$target = $by_number[ $peer ]['item'];
		$links[] = '<li><a href="' . esc_url( $target['url'] ) . '">' . esc_html( $target['title'] ) . '</a></li>';
	}
	$block = "\n<!-- eyecare-workplace-links:v1 --><nav class=\"eyecare-workplace-links\" aria-label=\"Bài liên quan về mắt và công việc\"><h2>Đọc tiếp theo tình huống công việc</h2><ul>" . implode( '', $links ) . "</ul></nav>\n";
	$needle = '<section class="eyecare-nguon-tham-khao"';
	$pos = strpos( $post->post_content, $needle );
	if ( false === $pos ) {
		throw new RuntimeException( "Source section missing in {$post->ID}." );
	}
	$updates[ $post->ID ] = substr_replace( $post->post_content, $block, $pos, 0 );
}
foreach ( $old_groups as $url => $numbers ) {
	$post = $posts[ url_to_postid( $url ) ];
	$links = array();
	foreach ( $numbers as $number ) {
		$target = $by_number[ $number ]['item'];
		$links[] = '<li><a href="' . esc_url( $target['url'] ) . '">' . esc_html( $target['title'] ) . '</a></li>';
	}
	$block = "\n<!-- eyecare-workplace-links:v1 --><nav class=\"eyecare-workplace-links\" aria-label=\"Bài liên quan về mắt và công việc\"><h2>Đọc thêm về mắt trong môi trường làm việc</h2><ul>" . implode( '', $links ) . "</ul></nav>\n";
	$pos = strripos( $post->post_content, '[tac-gia]' );
	$updates[ $post->ID ] = false === $pos ? $post->post_content . $block : substr_replace( $post->post_content, $block, $pos, 0 );
}
foreach ( $updates as $id => $content ) {
	$result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $content ) ), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( "Failed on {$id}: " . $result->get_error_message() );
	}
	WP_CLI::log( "LINKED {$id} " . get_permalink( $id ) );
}
WP_CLI::success( 'Updated 15 new and 11 existing posts; originals saved separately.' );
