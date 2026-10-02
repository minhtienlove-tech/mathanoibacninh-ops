<?php
/** Keep unfinished and utility pages publicly accessible without indexing them. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** IDs are the six verified placeholder/confirmation pages on this site. */
function eyecare_trang_giu_cho_ids() {
	return array( 7, 10, 53, 59, 63, 64 );
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
