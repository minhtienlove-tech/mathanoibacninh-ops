<?php
/**
 * Tương thích sitemap giữa URL chuẩn WordPress và module sitemap của OBS SEO.
 *
 * OBS tắt sitemap lõi và dùng query var obs_sitemap. WordPress canonical lại
 * tự thêm dấu / vào đuôi tệp XML, làm rewrite không còn khớp. Lớp này giữ cả
 * /wp-sitemap.xml và /obs-sitemap.xml hoạt động mà không sửa plugin bên thứ ba.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Không canonical hóa các URL XML sitemap. */
function eyecare_sitemap_bo_canonical( $redirect_url, $requested_url ) {
	$path = (string) wp_parse_url( $requested_url, PHP_URL_PATH );
	if ( preg_match( '#/(?:wp-sitemap|obs-sitemap(?:-(?:posts|pages|products|cats))?)\.xml/?$#', $path ) ) {
		return false;
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'eyecare_sitemap_bo_canonical', 10, 2 );

/** Gán query var sớm để module OBS phục vụ cả URL chuẩn và URL có dấu slash. */
function eyecare_sitemap_dat_query_var() {
	$path = trim( (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
	$base = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $base && str_starts_with( $path, $base . '/' ) ) {
		$path = substr( $path, strlen( $base ) + 1 );
	}

	$map = array(
		'wp-sitemap.xml'           => 'index',
		'obs-sitemap.xml'          => 'index',
		'obs-sitemap-posts.xml'    => 'posts',
		'obs-sitemap-pages.xml'    => 'pages',
		'obs-sitemap-products.xml' => 'products',
		'obs-sitemap-cats.xml'     => 'cats',
	);
	$path = rtrim( $path, '/' );
	if ( isset( $map[ $path ] ) ) {
		set_query_var( 'obs_sitemap', $map[ $path ] );
		status_header( 200 );
	}
}
add_action( 'template_redirect', 'eyecare_sitemap_dat_query_var', 0 );

