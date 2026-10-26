<?php
/** Keep unfinished and utility pages publicly accessible without indexing them. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Các trang còn đang giữ chỗ hoặc là trang xác nhận; Tin tức/Tuyển dụng đã có nội dung. */
function eyecare_trang_giu_cho_ids() {
	return array( 7, 10, 63, 64 );
}

function eyecare_trang_giu_cho_robots( $robots ) {
	if ( is_page( eyecare_trang_giu_cho_ids() ) ) {
		unset( $robots['index'] );
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'eyecare_trang_giu_cho_robots', 20 );

/** Serve the supplied Zalo ownership file at its exact root URL. */
function eyecare_zalo_site_verification() {
	$filename = 'zalo_verifierVywq9BdPEIXugxygYOrl3ac4n1cbyXz3DZOu.html';
	$path     = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
	$method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
	if ( '/' . $filename !== $path || ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
		return;
	}

	$file = get_stylesheet_directory() . '/verification/' . $filename;
	if ( ! is_readable( $file ) ) {
		return;
	}

	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	if ( 'HEAD' !== $method ) {
		readfile( $file );
	}
	exit;
}
add_action( 'template_redirect', 'eyecare_zalo_site_verification', -1 );
