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
	false !== strpos( $html, 'Cố vấn chuyên môn cao cấp' ),
	'Thiếu vai trò cố vấn chuyên môn cao cấp.'
);

foreach ( array( 'Chủ tịch HĐQT', '100.000 ca', '100,000 ca' ) as $unsupported_claim ) {
	eyecare_test_assert(
		false === strpos( $html, $unsupported_claim ),
		'Không được hiển thị chức danh hoặc thành tích chưa đối chiếu: ' . $unsupported_claim
	);
}

eyecare_test_assert(
	5 === substr_count( $html, 'eyecare-home-team__card"' ),
	'Lưới bác sĩ phải có đúng năm thẻ.'
);

// Các chân dung gốc được duyệt hiện nằm trong assets/, không dùng poster cũ.
$portrait_files = array(
	'doctor-le-nhu-tung.png',
	'doctor-dang-cong-hai.png',
	'doctor-bui-van-canh.png',
	'doctor-tran-khanh-thang.jpg',
	'doctor-tran-duc-thinh.jpg',
	'doctor-nguyen-dang-dat.jpg',
);

foreach ( $portrait_files as $filename ) {
	$path = get_stylesheet_directory() . '/assets/' . $filename;
	eyecare_test_assert( is_file( $path ), 'Thiếu chân dung gốc: ' . $filename );
	eyecare_test_assert( false !== strpos( $html, $filename ), 'HTML chưa dùng chân dung gốc: ' . $filename );
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
