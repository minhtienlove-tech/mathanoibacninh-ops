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
