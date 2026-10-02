<?php
/** WP-CLI only; merge the three audited duplicate pairs, with exact URL replacements. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 1 ); }
$pairs = array(
	107 => array( 'keep' => 116, 'old' => '/kien-thuc/can-thi-la-gi-nguyen-nhan-va-dau-hieu-2/', 'new' => '/kien-thuc/can-thi-la-gi-nguyen-nhan-va-dau-hieu/' ),
	108 => array( 'keep' => 119, 'old' => '/kien-thuc/doi-kinh-lien-tuc-ma-van-mo-2/', 'new' => '/kien-thuc/doi-kinh-lien-tuc-ma-van-mo/' ),
	109 => array( 'keep' => 120, 'old' => '/kien-thuc/kham-khuc-xa-gom-nhung-gi-2/', 'new' => '/kien-thuc/kham-khuc-xa-gom-nhung-gi/' ),
);
$old = array(); $new = array(); $snapshot = array( 'duplicates' => array(), 'updated_posts' => array() );
foreach ( $pairs as $id => $pair ) {
	$duplicate = get_post( $id ); $keeper = get_post( $pair['keep'] );
	if ( ! $duplicate || ! $keeper || 'publish' !== $duplicate->post_status || 'publish' !== $keeper->post_status || $duplicate->post_title !== $keeper->post_title ) {
		WP_CLI::error( 'Pair preflight failed: ' . $id );
	}
	if ( wp_parse_url( get_permalink( $id ), PHP_URL_PATH ) !== $pair['old'] || wp_parse_url( get_permalink( $pair['keep'] ), PHP_URL_PATH ) !== $pair['new'] ) {
		WP_CLI::error( 'Permalink preflight failed: ' . $id );
	}
	$old[] = home_url( $pair['old'] ); $new[] = home_url( $pair['new'] );
	$snapshot['duplicates'][] = array( 'id' => $id, 'status' => $duplicate->post_status, 'content' => $duplicate->post_content );
}
$posts = get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => 'publish', 'posts_per_page' => -1 ) );
$updates = array();
foreach ( $posts as $post ) {
	if ( isset( $pairs[ $post->ID ] ) ) { continue; }
	$changed = str_replace( $old, $new, $post->post_content );
	if ( $changed !== $post->post_content ) {
		$updates[ $post->ID ] = $changed;
		$snapshot['updated_posts'][] = array( 'id' => $post->ID, 'content' => $post->post_content );
	}
}
WP_CLI::log( 'Preflight: 3 duplicates; ' . count( $updates ) . ' posts/pages with inbound links.' );
if ( '1' !== getenv( 'EYECARE_MERGE_THREE_APPLY' ) ) { WP_CLI::success( 'Dry run; no changes.' ); return; }
$backup = isset( $args[0] ) ? $args[0] : '';
if ( ! $backup || file_exists( $backup ) || false === file_put_contents( $backup, wp_json_encode( $snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) ) {
	WP_CLI::error( 'Could not write exclusive content snapshot.' );
}
foreach ( $updates as $id => $content ) {
	$result = wp_update_post( array( 'ID' => $id, 'post_content' => $content ), true );
	if ( is_wp_error( $result ) ) { WP_CLI::error( 'Could not update inbound links in ' . $id ); }
}
foreach ( array_keys( $pairs ) as $id ) {
	$result = wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ), true );
	if ( is_wp_error( $result ) ) { WP_CLI::error( 'Could not draft duplicate ' . $id ); }
}
WP_CLI::success( 'Merged 3 pairs; snapshot: ' . $backup );
