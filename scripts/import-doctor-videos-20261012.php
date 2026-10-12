<?php
/**
 * WP-CLI importer for the four approved Dr Tran Khanh Thang Shorts.
 * Run through deploy-doctor-videos-20261012.sh so backup and the deploy lock
 * are in place. This file does not read or write patient review records.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "WP-CLI is required.\n" );
	exit( 1 );
}

$stage  = getenv( 'EYECARE_DOCTOR_VIDEO_POSTERS' );
$backup = getenv( 'EYECARE_DOCTOR_VIDEO_BACKUP' );
$apply  = '1' === getenv( 'EYECARE_DOCTOR_VIDEO_APPLY' );

if ( ! $stage || ! is_dir( $stage ) ) {
	WP_CLI::error( 'Poster staging directory is missing.' );
}
if ( $apply && ( ! $backup || ! preg_match( '~^/home/jwhxtzru/backups/doctor-videos-[0-9]{8}-[0-9]{6}$~D', $backup )
	|| ! is_file( $backup . '/database-before.sql' )
	|| ! is_file( $backup . '/child-theme-before.tar.gz' ) ) ) {
	WP_CLI::error( 'Apply requires the deployment backup with database and child theme archive.' );
}
if ( ! post_type_exists( 'eyecare_video_bs' ) || ! function_exists( 'eyecare_video_bs_source' ) || ! function_exists( 'eyecare_video_bs_publishable' ) ) {
	WP_CLI::error( 'The doctor video child-theme module is not active.' );
}

$doctor_id = 674;
if ( 'eyecare_bac_si' !== get_post_type( $doctor_id ) || 'publish' !== get_post_status( $doctor_id ) ) {
	WP_CLI::error( 'Doctor #674 is not a published eyecare_bac_si post.' );
}

// The association is editorial and intentionally explicit.
$items = array(
	array(
		'id'      => 'wyri86ODYrA',
		'article' => 144,
		'title'   => 'Những khuyến nghị của bác sĩ về cận thị học đường',
		'sha256'  => '38ab51322938f69b427cbcb8edd1bb8bf742bac64bd39c689736c81823ba1236',
	),
	array(
		'id'      => 'SlToWMv-_4E',
		'article' => 1445,
		'title'   => 'Cận thị không được phát hiện và kiểm soát kịp thời',
		'sha256'  => '8fa14a0ac80fba7e18ad99df364190f957538757d13d0316c2686945a134b50d',
	),
	array(
		'id'      => 'LqxNF7hxLhU',
		'article' => 140,
		'title'   => 'Những thói quen hằng ngày có thể ảnh hưởng đến mắt',
		'sha256'  => '19add1fdd6d86d44688a210fde3463321bba40ca7a70cd44a8344e2fc78d30d9',
	),
	array(
		'id'      => '3qVuG1DgNmk',
		'article' => 116,
		'title'   => 'Vì sao học sinh ngày càng dễ bị cận thị?',
		'sha256'  => '4a423b8522e8918c6ecb5f5d2cc8fe3f4a7c13eb9a42cc620f02d93d5154c7af',
	),
);

$planned = array();
foreach ( $items as $item ) {
	$article_id = $item['article'];
	if ( 'post' !== get_post_type( $article_id ) || 'publish' !== get_post_status( $article_id ) ) {
		WP_CLI::error( "Article #{$article_id} is not a published post." );
	}
	$url    = 'https://www.youtube.com/shorts/' . $item['id'];
	$source = eyecare_video_bs_source( $url );
	if ( 'embed' !== ( $source['kind'] ?? '' ) || empty( $source['portrait'] ) ) {
		WP_CLI::error( "Shorts source rejected: {$item['id']}." );
	}
	$poster = $stage . '/' . $item['id'] . '.jpg';
	$actual_hash = is_file( $poster ) && is_readable( $poster ) ? hash_file( 'sha256', $poster ) : false;
	if ( ! is_file( $poster ) || ! is_readable( $poster )
		|| ! is_string( $actual_hash ) || ! hash_equals( $item['sha256'], $actual_hash )
		|| false === getimagesize( $poster ) ) {
		WP_CLI::error( "Poster missing, changed, or not an image: {$poster}." );
	}
	$slug = sanitize_title( 'bac-si-thang-short-' . strtolower( $item['id'] ) );
	$ids  = get_posts( array(
		'post_type'      => 'eyecare_video_bs',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => '_eyecare_video_bs_youtube_id', 'value' => $item['id'] ),
			array( 'key' => EYECARE_VIDEO_BS_URL, 'value' => $url ),
		),
	) );
	$slug_post = get_page_by_path( $slug, OBJECT, 'eyecare_video_bs' );
	if ( $slug_post && ! in_array( $slug_post->ID, $ids, true ) ) {
		$ids[] = $slug_post->ID;
	}
	if ( count( $ids ) > 1 ) {
		WP_CLI::error( "Duplicate video records for {$item['id']}." );
	}
	$existing_id = $ids ? (int) $ids[0] : 0;
	if ( $existing_id ) {
		$fields = eyecare_video_bs_fields( $existing_id );
		if ( 'publish' !== get_post_status( $existing_id )
			|| $item['title'] !== get_the_title( $existing_id )
			|| $url !== $fields['url']
			|| $doctor_id !== (int) $fields['doctor']
			|| $article_id !== (int) $fields['article']
			|| 1 !== (int) $fields['home']
			|| 1 !== (int) $fields['approved']
			|| $item['id'] !== get_post_meta( $existing_id, '_eyecare_video_bs_youtube_id', true )
			|| ! eyecare_video_bs_publishable( $item['title'], $fields ) ) {
			WP_CLI::error( "Existing video #{$existing_id} differs from manifest; inspect it manually." );
		}
	}
	$planned[] = array_merge( $item, compact( 'url', 'poster', 'slug', 'existing_id' ) );
	WP_CLI::log( sprintf( '%s: article #%d; %s', $item['id'], $article_id, $existing_id ? "already published #{$existing_id}" : 'ready to create' ) );
}

if ( ! $apply ) {
	WP_CLI::success( 'Preflight only; no WordPress records changed.' );
	return;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

foreach ( $planned as $index => $item ) {
	if ( $item['existing_id'] ) {
		continue;
	}
	// The display query sorts by post_date DESC; distinct minutes keep this
	// editorial order stable even when all four records publish in one second.
	$post_date = current_datetime()->modify( '-' . ( $index + 1 ) . ' minutes' )->format( 'Y-m-d H:i:s' );
	$post_id = wp_insert_post( array(
		'post_type'   => 'eyecare_video_bs',
		'post_status' => 'draft',
		'post_title'  => $item['title'],
		'post_name'   => $item['slug'],
		'post_author' => 0,
		'post_date'   => $post_date,
	), true );
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		WP_CLI::error( "Could not create draft for {$item['id']}." );
	}
	$tmp = wp_tempnam( $item['id'] . '.jpg' );
	if ( ! $tmp || ! copy( $item['poster'], $tmp ) ) {
		WP_CLI::error( "Could not prepare poster for {$item['id']}; draft #{$post_id} remains." );
	}
	$attachment_id = media_handle_sideload( array(
		'name'     => $item['id'] . '.jpg',
		'tmp_name' => $tmp,
		'type'     => 'image/jpeg',
		'size'     => filesize( $tmp ),
		'error'    => 0,
	), $post_id, $item['title'], array( 'post_title' => 'Poster video: ' . $item['title'] ) );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		WP_CLI::error( "Poster import failed for {$item['id']}: " . $attachment_id->get_error_message() );
	}
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Poster video: ' . $item['title'] );
	set_post_thumbnail( $post_id, $attachment_id );
	update_post_meta( $post_id, '_eyecare_video_bs_youtube_id', $item['id'] );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_URL, $item['url'] );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_DOCTOR, $doctor_id );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_ARTICLE, $item['article'] );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_HOME, 1 );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_APPROVED, 1 );
	$fields = eyecare_video_bs_fields( $post_id );
	if ( ! eyecare_video_bs_publishable( $item['title'], $fields ) ) {
		WP_CLI::error( "Publish guard rejected draft #{$post_id}; inspect it manually." );
	}
	$result = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish', 'post_date' => $post_date ), true );
	if ( is_wp_error( $result ) || 'publish' !== get_post_status( $post_id ) ) {
		WP_CLI::error( "Publish failed for video #{$post_id}; inspect it manually." );
	}
	WP_CLI::log( "Published {$item['id']} as video #{$post_id}, poster #{$attachment_id}." );
}

WP_CLI::success( 'Four doctor Shorts are present and publishable.' );
