<?php
/**
 * Dọn trang quản trị — Bệnh viện Mắt Hà Nội – Bắc Ninh
 *
 * Tắt các ô mặc định của WordPress trên Trang quản trị.
 * Lý do: người dùng thật của trang này là nhân sự bệnh viện, không
 * phải lập trình viên. Tin tức WordPress, bản nháp nhanh và tình
 * trạng website chỉ gây nhiễu.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gỡ các ô mặc định khỏi Trang quản trị.
 */
function eyecare_don_trang_quan_tri() {

	// Cột chính
	remove_meta_box( 'dashboard_right_now',      'dashboard', 'normal' );   // Tin nhanh
	remove_meta_box( 'dashboard_activity',       'dashboard', 'normal' );   // Hoạt động
	remove_meta_box( 'dashboard_site_health',    'dashboard', 'normal' );   // Tình trạng website

	// Cột phụ
	remove_meta_box( 'dashboard_quick_press',    'dashboard', 'side' );     // Bản nháp nhanh
	remove_meta_box( 'dashboard_primary',        'dashboard', 'side' );     // Tin tức & sự kiện WordPress
	remove_meta_box( 'dashboard_secondary',      'dashboard', 'side' );

	// Ô cũ, còn sót ở một số bản
	remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_plugins',        'dashboard', 'normal' );
	remove_meta_box( 'dashboard_recent_drafts',  'dashboard', 'side' );
	remove_meta_box( 'dashboard_recent_comments','dashboard', 'normal' );

	// Ô do plugin thêm vào
	remove_meta_box( 'dashboard_php_nag',        'dashboard', 'normal' );
	remove_meta_box( 'wpseo-dashboard-overview', 'dashboard', 'normal' );   // Yoast
	remove_meta_box( 'rank_math_dashboard_widget','dashboard','normal' );   // Rank Math
	remove_meta_box( 'woocommerce_dashboard_status', 'dashboard', 'normal' );
	remove_meta_box( 'e-dashboard-overview',     'dashboard', 'normal' );   // Elementor
}
add_action( 'wp_dashboard_setup', 'eyecare_don_trang_quan_tri', 999 );

/**
 * Gỡ ô "Chào mừng" của WordPress.
 */
remove_action( 'welcome_panel', 'wp_welcome_panel' );

/**
 * Ẩn ô Akismet trên Trang quản trị.
 */
function eyecare_go_o_akismet() {
	remove_meta_box( 'akismet_dashboard_widget', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'eyecare_go_o_akismet', 999 );
