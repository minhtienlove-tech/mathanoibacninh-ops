<?php
/** Finish eight workplace-series SEO descriptions cut at a fixed character count. */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WP_CLI' ) ) {
	return;
}

$ids = array( 1474, 1475, 1476, 1478, 1482, 1485, 1486, 1488 );
$rows = array();
foreach ( $ids as $id ) {
	$post = get_post( $id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ||
		! get_post_meta( $id, '_eyecare_workplace_series_id', true ) ) {
		WP_CLI::error( 'Post precondition failed: ' . $id );
	}
	$excerpt = trim( (string) $post->post_excerpt );
	$old = (string) get_post_meta( $id, '_eyecare_meta_description_proposal', true );
	if ( mb_strlen( $excerpt, 'UTF-8' ) <= 155 ||
		mb_substr( $excerpt, 0, 155, 'UTF-8' ) . '.' !== $old ) {
		WP_CLI::error( 'Meta precondition failed: ' . $id );
	}
	$rows[] = array( 'id' => $id, 'slug' => $post->post_name, 'old' => $old, 'new' => $excerpt );
}

WP_CLI::log( 'Preflight passed for eight workplace meta descriptions.' );
if ( '1' !== getenv( 'WORKPLACE_META_APPLY' ) ) {
	WP_CLI::log( 'Dry run only.' );
	return;
}

$backup_dir = getenv( 'BG_SEO_BACKUP_DIR' );
if ( ! $backup_dir || ! preg_match( '#^/home/jwhxtzru/backups/[A-Za-z0-9/_-]+$#', $backup_dir ) ||
	! is_dir( $backup_dir ) || file_exists( $backup_dir . '/workplace-meta-before-after.json' ) ) {
	WP_CLI::error( 'Backup directory invalid or record already exists.' );
}
$json = wp_json_encode( $rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
if ( false === $json || strlen( $json ) !== file_put_contents( $backup_dir . '/workplace-meta-before-after.json', $json ) ) {
	WP_CLI::error( 'Backup write failed.' );
}
chmod( $backup_dir . '/workplace-meta-before-after.json', 0600 );

foreach ( $rows as $row ) {
	if ( ! update_post_meta( $row['id'], '_eyecare_meta_description_proposal', $row['new'], $row['old'] ) ||
		$row['new'] !== get_post_meta( $row['id'], '_eyecare_meta_description_proposal', true ) ) {
		WP_CLI::error( 'Meta update failed: ' . $row['id'] );
	}
	WP_CLI::log( 'Updated meta ' . $row['id'] );
}
WP_CLI::success( 'Eight descriptions restored from their full editorial excerpts.' );
