<?php
/**
 * Smoke test trang cá nhân bác sĩ /bac-si/<slug>/.
 *
 * Chạy trên server: php tests/doctor-page-test.php
 * Chỉ đọc dữ liệu; không ghi database.
 */

define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 4 ) . '/wp-load.php';

function eyecare_dp_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$pt = get_post_type_object( EYECARE_POST_TYPE_BAC_SI );
eyecare_dp_assert( $pt && $pt->public && $pt->publicly_queryable, 'Post type bác sĩ phải công khai.' );

$doctors = eyecare_du_lieu_doi_ngu();
eyecare_dp_assert( count( $doctors ) > 0, 'Không có bác sĩ đã đăng.' );

foreach ( $doctors as $doctor ) {
	$url = eyecare_bac_si_ho_so_url( $doctor );
	eyecare_dp_assert(
		0 === strpos( $url, home_url( '/bac-si/' ) ),
		'Liên kết hồ sơ phải là /bac-si/<slug>/: ' . $doctor['ho_ten'] . ' → ' . $url
	);
	eyecare_dp_assert( $url === get_permalink( $doctor['post_id'] ), 'Liên kết hồ sơ khác permalink: ' . $doctor['ho_ten'] );
	// Tên có học vị ở đầu vẫn tìm đúng bản ghi.
	eyecare_dp_assert(
		eyecare_bac_si_tim_id( eyecare_doi_ngu_ten_day_du( $doctor ) ) === (int) $doctor['post_id'],
		'Không tìm được bác sĩ theo tên đầy đủ: ' . eyecare_doi_ngu_ten_day_du( $doctor )
	);
}

// Phân tích ô “Liên kết và nguồn khác”.
$lk = eyecare_bac_si_phan_tich_lien_ket( "Kênh YouTube | https://www.youtube.com/@abc\n\nhttp://khong-an-toan.vn\njavascript:alert(1)\nBài báo|https://vnexpress.net/x\nhttps://example.com/y" );
eyecare_dp_assert( 3 === count( $lk ), 'Phải giữ đúng 3 liên kết HTTPS hợp lệ, có ' . count( $lk ) );
eyecare_dp_assert( 'Kênh YouTube' === $lk[0]['ten'] && 'Bài báo' === $lk[1]['ten'] && 'example.com' === $lk[2]['ten'], 'Tên liên kết sai.' );

// Khối đội ngũ trên trang chủ dẫn tới trang cá nhân, không sang Facebook.
ob_start();
eyecare_doi_ngu_trang_chu_in();
$html = ob_get_clean();
eyecare_dp_assert( false === strpos( $html, 'facebook.com' ), 'Khối đội ngũ không được dẫn sang Facebook.' );
foreach ( $doctors as $doctor ) {
	eyecare_dp_assert( false !== strpos( $html, 'href="' . esc_url( get_permalink( $doctor['post_id'] ) ) . '"' ), 'Thiếu liên kết trang cá nhân: ' . $doctor['ho_ten'] );
}

// Lọc bài theo bác sĩ không lỗi.
foreach ( $doctors as $doctor ) {
	eyecare_dp_assert( is_array( eyecare_bac_si_bai_viet_ids( $doctor['post_id'] ) ), 'Lọc bài lỗi.' );
}

echo "PASS: doctor profile pages, links and admin helpers.\n";
