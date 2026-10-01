<?php
/** Publish the 20-item content plan from a pre-reviewed, sanitized package. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_PUBLISH_CONTENT_PLAN' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI publication gate required.' );
}

$directory = realpath( (string) getenv( 'EYECARE_CONTENT_PUBLICATION_DIR' ) );
if ( ! $directory || ! is_dir( $directory ) ) {
	throw new RuntimeException( 'Publication directory is missing.' );
}

$existing = array(
	'01' => array( 6, 'page' ),
	'02' => array( 58, 'page' ),
	'03' => array( 57, 'page' ),
	'04' => array( 42, 'page' ),
	'05' => array( 285, 'post' ),
	'06' => array( 11, 'page' ),
	'10' => array( 120, 'post' ),
	'14' => array( 140, 'post' ),
);
$new = array(
	'07' => 1441, '08' => 1442, '09' => 1443, '11' => 1444,
	'12' => 1445, '13' => 1446, '15' => 1447, '16' => 1448,
	'17' => 1449, '18' => 1450, '19' => 1451, '20' => 1452,
);

// Fail the entire preflight before any writes if the target or copy is wrong.
$fragments = array();
foreach ( array_keys( $existing + $new ) as $number ) {
	$file = $directory . '/' . $number . '.html';
	if ( ! is_file( $file ) ) {
		throw new RuntimeException( "Missing fragment {$number}." );
	}
	$html = trim( (string) file_get_contents( $file ) );
	if ( '' === $html || preg_match( '/CẦN XÁC MINH|Chặn xuất bản|<h1\b|<script\b/i', $html ) ) {
		throw new RuntimeException( "Unclean fragment {$number}." );
	}
	$fragments[ $number ] = $html;
}
foreach ( $existing as $number => $target ) {
	$post = get_post( $target[0] );
	if ( ! $post || $target[1] !== $post->post_type || 'publish' !== $post->post_status ||
		get_post_meta( $post->ID, '_eyecare_content_plan_published_id', true ) ) {
		throw new RuntimeException( "Existing target {$number} changed or was updated already." );
	}
}
foreach ( $new as $number => $id ) {
	$post = get_post( $id );
	if ( ! $post || 'post' !== $post->post_type || 'draft' !== $post->post_status ||
		(string) $number !== get_post_meta( $id, '_eyecare_content_plan_id', true ) ) {
		throw new RuntimeException( "Draft target {$number} changed." );
	}
}

foreach ( $existing as $number => $target ) {
	$post = get_post( $target[0] );
	$section = '<section class="eyecare-content-plan-addendum" aria-label="Thông tin bổ sung">' .
		'<h2>Thông tin bổ sung cho người bệnh</h2>' . $fragments[ $number ] . '</section>';
	$result = wp_update_post( array(
		'ID'           => $post->ID,
		'post_content' => rtrim( $post->post_content ) . "\n\n" . $section,
	), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( "Cannot update {$number}: " . $result->get_error_message() );
	}
	update_post_meta( $post->ID, '_eyecare_content_plan_published_id', $number );
	WP_CLI::log( "UPDATED {$number} post={$post->ID}" );
}
foreach ( $new as $number => $id ) {
	$result = wp_update_post( array(
		'ID'           => $id,
		'post_content' => $fragments[ $number ],
		'post_status'  => 'publish',
	), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( "Cannot publish {$number}: " . $result->get_error_message() );
	}
	WP_CLI::log( "PUBLISHED {$number} post={$id} url=" . get_permalink( $id ) );
}
WP_CLI::success( '20 content-plan items applied. Review attribution remains pending.' );
