<?php
/** Standalone guard tests: php tests/video-bac-si-test.php. No WordPress DB needed. */

define( 'ABSPATH', __DIR__ );

function add_action() {}
function add_filter() {}
function wp_parse_url( $url ) { return parse_url( $url ); }
function attachment_url_to_postid( $url ) { return 'https://example.test/uploads/doctor.mp4' === $url ? 41 : 0; }
function wp_get_attachment_url( $id ) { return 41 === $id ? 'https://example.test/uploads/doctor.mp4' : false; }
function get_post_mime_type( $id ) { return 41 === $id ? 'video/mp4' : ''; }
function get_post_type( $id ) { return 7 === $id ? 'eyecare_bac_si' : ( 8 === $id ? 'post' : '' ); }
function get_post_status( $id ) { return in_array( $id, array( 7, 8 ), true ) ? 'publish' : 'draft'; }
function wp_attachment_is_image( $id ) { return 30 === $id; }
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function wp_verify_nonce( $nonce ) { return 'valid-test-nonce' === $nonce; }
function sanitize_text_field( $text ) { return trim( $text ); }
function wp_unslash( $text ) { return $text; }
function absint( $number ) { return abs( (int) $number ); }
function get_post_meta( $id, $key ) { return ''; }
function get_post_thumbnail_id() { return 0; }
function get_posts() { return array(); }

require dirname( __DIR__ ) . '/inc/video-bac-si.php';

function video_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$valid = array( 'url' => 'https://youtu.be/AbCdEfGhIjK', 'doctor' => 7, 'article' => 0, 'home' => 1, 'approved' => 1, 'poster' => 30 );
$source = eyecare_video_bs_source( $valid['url'] );
video_assert( 'https://www.youtube-nocookie.com/embed/AbCdEfGhIjK?rel=0' === $source['player_url'], 'YouTube must use the fixed privacy-enhanced player URL.' );
video_assert( eyecare_video_bs_publishable( 'Tư vấn về mắt', $valid ), 'A fully verified video should be publishable.' );

$bad_urls = array(
	'http://youtu.be/AbCdEfGhIjK',
	'https://youtube.com.evil.test/watch?v=AbCdEfGhIjK',
	'https://www.youtube.com/watch?v=invalid',
	'https://vimeo.com/not-a-video',
	'https://example.test/uploads/other.mp4',
	'https://example.test/uploads/doctor.mp4?autoplay=1',
	'javascript:alert(1)',
);
foreach ( $bad_urls as $url ) {
	video_assert( array() === eyecare_video_bs_source( $url ), 'Unsafe or unsupported URL accepted: ' . $url );
}

video_assert( 'https://player.vimeo.com/video/123456' === eyecare_video_bs_source( 'https://vimeo.com/123456' )['player_url'], 'Vimeo normalization failed.' );
video_assert( 'https://player.vimeo.com/video/123456?h=abcdef1234' === eyecare_video_bs_source( 'https://vimeo.com/123456/abcdef1234' )['player_url'], 'Unlisted Vimeo hash must survive normalization.' );
video_assert( 'mp4' === eyecare_video_bs_source( 'https://example.test/uploads/doctor.mp4' )['kind'], 'WordPress Media MP4 should be accepted.' );

foreach ( array( 'approved' => 0, 'doctor' => 99, 'article' => 99, 'poster' => 0, 'url' => 'https://evil.test/video' ) as $field => $value ) {
	$sample = $valid;
	$sample[ $field ] = $value;
	video_assert( ! eyecare_video_bs_publishable( 'Tư vấn về mắt', $sample ), 'Invalid ' . $field . ' must block publication.' );
}

video_assert( ! eyecare_video_bs_publishable( '', $valid ), 'Missing title must block publication.' );
video_assert( 'draft' === eyecare_video_bs_guard_publish( array( 'post_type' => EYECARE_VIDEO_BS_POST_TYPE, 'post_status' => 'publish', 'post_title' => 'Tư vấn' ), array( 'ID' => 1 ) )['post_status'], 'Programmatic publication without saved approval must become draft.' );

$_POST = array(
	'eyecare_video_bs_nonce' => 'valid-test-nonce',
	'eyecare_video_bs_url' => $valid['url'],
	'eyecare_video_bs_doctor' => '7',
	'eyecare_video_bs_article' => '0',
	'eyecare_video_bs_home' => '1',
	'eyecare_video_bs_approved' => '1',
	'_thumbnail_id' => '30',
);
$request = array( 'post_type' => EYECARE_VIDEO_BS_POST_TYPE, 'post_status' => 'publish', 'post_title' => 'Tư vấn' );
video_assert( 'publish' === eyecare_video_bs_guard_publish( $request, array( 'ID' => 1 ) )['post_status'], 'Valid editor form should be publishable.' );
unset( $_POST['eyecare_video_bs_approved'] );
video_assert( 'draft' === eyecare_video_bs_guard_publish( $request, array( 'ID' => 1 ) )['post_status'], 'Unchecking rights approval must revoke publication.' );
$_POST['eyecare_video_bs_approved'] = '1';
$_POST['_thumbnail_id'] = '-1';
video_assert( 'draft' === eyecare_video_bs_guard_publish( $request, array( 'ID' => 1 ) )['post_status'], 'Removing the poster must revoke publication.' );
video_assert( '' === eyecare_video_bs_home_html(), 'No approved videos must produce no placeholder section.' );

echo "PASS: source allowlist, consent and publication guard.\n";
