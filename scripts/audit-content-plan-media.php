<?php
/** Read-only WP-CLI media inventory for the 12 new content-plan articles. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}
$needles = array( 'kham', 'can', 'khuc xa', 'kinh', 'bao hiem', 'bhyt', 'phau thuat', 'lao thi', 'laser', 'tre em' );
$images = get_posts( array(
	'post_type' => 'attachment', 'post_status' => 'inherit',
	'posts_per_page' => -1, 'post_mime_type' => 'image',
) );
foreach ( $images as $image ) {
	$title = strtolower( remove_accents( $image->post_title ) );
	foreach ( $needles as $needle ) {
		if ( false !== strpos( $title, $needle ) ) {
			WP_CLI::log( implode( "\t", array( $image->ID, $image->post_title, wp_get_attachment_url( $image->ID ) ) ) );
			break;
		}
	}
}
