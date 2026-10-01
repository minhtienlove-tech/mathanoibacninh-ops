<?php
/** WP-CLI: attach existing, topic-matched site media to the 12 new articles. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$mapping = array(
	1441 => 904, // BHYT.
	1442 => 604, // Hospital waiting area and optical shop.
	1443 => 933, // General examination workflow.
	1444 => 838, // Refraction examination.
	1445 => 934, // Child examination.
	1446 => 836, // Understanding myopia degree.
	1447 => 968, // Glasses and refraction.
	1448 => 928, // Myopia assessment before surgery.
	1449 => 982, // Refractive surgery service illustration.
	1450 => 739, // Refraction pathway illustration.
	1451 => 970, // Vision after age 40.
	1452 => 986, // Preoperative tests service illustration.
);

$apply = '1' === getenv( 'EYECARE_APPLY' );
foreach ( $mapping as $post_id => $attachment_id ) {
	$post = get_post( $post_id );
	$attachment = get_post( $attachment_id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || ! $attachment || 'attachment' !== $attachment->post_type || ! wp_attachment_is_image( $attachment_id ) ) {
		WP_CLI::error( "Invalid article or image: {$post_id} => {$attachment_id}" );
	}
	$current = (int) get_post_thumbnail_id( $post_id );
	if ( $current && $current !== $attachment_id ) {
		WP_CLI::error( "Article {$post_id} already has a different featured image ({$current})." );
	}
	if ( $apply && ! $current && ! set_post_thumbnail( $post_id, $attachment_id ) ) {
		WP_CLI::error( "Could not set featured image: {$post_id} => {$attachment_id}" );
	}
	WP_CLI::log( sprintf( '%d => %d %s | %s', $post_id, $attachment_id, $current ? 'already set' : ( $apply ? 'set' : 'preview' ), $post->post_title ) );
}
