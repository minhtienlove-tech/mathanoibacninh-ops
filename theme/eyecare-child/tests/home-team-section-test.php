<?php
/**
 * Smoke test cho khối đội ngũ mới trên trang chủ.
 *
 * Chạy: C:\\xampp\\php\\php.exe tests/home-team-section-test.php
 */

define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 4 ) . '/wp-load.php';

function eyecare_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

eyecare_test_assert(
	function_exists( 'eyecare_doi_ngu_trang_chu_in' ),
	'Hàm eyecare_doi_ngu_trang_chu_in() chưa tồn tại.'
);

ob_start();
eyecare_doi_ngu_trang_chu_in();
$html = ob_get_clean();

$expected_names = array(
	'ThS.BS Lê Như Tùng',
	'BSCKI. Đặng Công Hải',
	'BSCKI. Bùi Văn Cảnh',
	'BSCK. Trần Khánh Thắng',
	'Cử nhân khúc xạ Trần Đức Thịnh',
	'Cử nhân Nguyễn Đăng Đạt',
);

foreach ( $expected_names as $name ) {
	eyecare_test_assert(
		false !== strpos( $html, $name ),
		'Thiếu bác sĩ: ' . $name
	);
}

eyecare_test_assert(
	1 === substr_count( $html, 'class="eyecare-home-team__featured"' ),
	'Khối cố vấn phải xuất hiện đúng một lần.'
);

eyecare_test_assert(
	false !== strpos( $html, 'Cố vấn chuyên môn cao cấp và Chủ tịch HĐQT' ),
	'Thiếu chức danh đầy đủ của cố vấn chuyên môn.'
);

eyecare_test_assert(
	false !== strpos( $html, 'Hơn 100.000 ca mổ Phaco trên toàn quốc và nước ngoài' ),
	'Thiếu thông tin số ca mổ Phaco của cố vấn chuyên môn.'
);

eyecare_test_assert(
	5 === substr_count( $html, 'eyecare-home-team__card"' ),
	'Lưới bác sĩ phải có đúng năm thẻ.'
);

foreach ( array( 'le-nhu-tung', 'dang-cong-hai', 'bui-van-canh', 'tran-khanh-thang', 'tran-duc-thinh', 'nguyen-dang-dat' ) as $slug ) {
	$path = get_stylesheet_directory() . '/assets/doctor-posters/' . $slug . '.webp';
	eyecare_test_assert( file_exists( $path ), 'Thiếu ảnh tối ưu: ' . $slug . '.webp' );
	eyecare_test_assert( false !== strpos( $html, $slug . '.webp' ), 'HTML chưa dùng ảnh: ' . $slug . '.webp' );
}

echo "PASS: homepage doctor team renders the approved 1 + 5 layout.\n";

ob_start();
eyecare_doi_ngu_trang_chu_in( 1 );
$page_html = ob_get_clean();

eyecare_test_assert(
	1 === substr_count( $page_html, '<h1 id="eyecare-home-team-title">' ),
	'Trang đội ngũ phải render tiêu đề chính bằng H1.'
);

echo "PASS: shared doctor team supports an H1 on the standalone page.\n";
