<?php
/**
 * Kiểm tra các helper giấy phép hoạt động không cần WordPress.
 *
 * Stub đúng những hàm WordPress mà inc/schema-y-te.php gọi khi nạp, rồi chạy
 * eyecare_co_gphd(), eyecare_gphd_url(), eyecare_gphd_ngay_hien() và
 * eyecare_gphd_anh_url() để chắc chắn ngày cấp in đúng và ảnh thiếu thì trả
 * rỗng chứ không in đường dẫn chết.
 *
 * Chạy: php tests/gphd-helper-test.php
 *
 * @package eyecare-child
 */

define( 'ABSPATH', __DIR__ . '/../' );

$GLOBALS['eyecare_test_theme_dir'] = realpath( __DIR__ . '/..' );

function get_stylesheet_directory() {
	return $GLOBALS['eyecare_test_theme_dir'];
}

function get_stylesheet_directory_uri() {
	return 'https://mathanoibacninh.com/wp-content/themes/eyecare-child';
}

function home_url( $path = '/' ) {
	return 'https://mathanoibacninh.com' . $path;
}

function wp_date( $format, $timestamp = null, $timezone = null ) {
	$dt = new DateTime( '@' . (int) $timestamp );
	$dt->setTimezone( $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone( 'UTC' ) );
	return $dt->format( $format );
}

function add_shortcode() {}
function add_action() {}
function add_filter() {}
function shortcode_atts( $pairs, $atts ) { return $pairs; }
function esc_html( $t ) { return $t; }
function esc_attr( $t ) { return $t; }
function esc_url_raw( $t ) { return $t; }
function get_theme_mod() { return 0; }
function wp_get_attachment_image_url() { return ''; }
require_once __DIR__ . '/../inc/schema-y-te.php';

$loi = 0;
$kiem = static function ( $ten, $thuc, $mong ) use ( &$loi ) {
	if ( $thuc === $mong ) {
		echo "PASS  $ten\n";
		return;
	}
	$loi++;
	echo "FAIL  $ten\n      mong: " . var_export( $mong, true ) . "\n      thuc: " . var_export( $thuc, true ) . "\n";
};

$d = eyecare_du_lieu_thuc_the();

$kiem( 'so_gphd đúng bản giấy', $d['so_gphd'], '444/BYT-GPHĐ' );
$kiem( 'eyecare_co_gphd() true khi có số', eyecare_co_gphd(), true );
$kiem( 'URL trang công bố', eyecare_gphd_url(), 'https://mathanoibacninh.com/giay-phep-hoat-dong/' );
$kiem( 'ngày cấp in dạng d/m/Y', eyecare_gphd_ngay_hien(), '06/10/2026' );
$kiem(
	'ảnh tồn tại thì trả URL',
	eyecare_gphd_anh_url(),
	'https://mathanoibacninh.com/wp-content/themes/eyecare-child/assets/giay-phep/gphd-444-byt-2026.webp'
);
$kiem( 'địa chỉ hiển thị dùng địa danh hiện hành', eyecare_dia_chi_day_du(), 'Lô 4, đường Hùng Vương, Phường Bắc Giang, Thành phố Bắc Ninh' );
$kiem( 'giờ trên giấy để rỗng, chưa công bố 24/24', $d['gphd_gio_tren_giay'], '' );

echo $loi ? "\n$loi kiểm tra thất bại.\n" : "\nTất cả kiểm tra đạt.\n";
exit( $loi ? 1 : 0 );
