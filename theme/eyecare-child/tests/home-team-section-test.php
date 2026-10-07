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

// Tên hiển thị theo cách viết trong Admin (thường là chữ hoa) nên so không phân biệt hoa thường.
foreach ( $expected_names as $name ) {
	eyecare_test_assert(
		false !== mb_stripos( str_replace( '. ', '.', $html ), str_replace( '. ', '.', $name ) ),
		'Thiếu bác sĩ: ' . $name
	);
}

eyecare_test_assert(
	1 === substr_count( $html, 'class="eyecare-home-team__featured"' ),
	'Khối cố vấn phải xuất hiện đúng một lần.'
);

// Chức danh và ảnh đại diện phải khớp đúng bản ghi bác sĩ trong Admin.
foreach ( eyecare_du_lieu_doi_ngu() as $doctor ) {
	if ( ! empty( $doctor['chuc_danh'] ) ) {
		eyecare_test_assert(
			false !== strpos( $html, esc_html( $doctor['chuc_danh'] ) ),
			'Chức danh chưa đồng bộ từ Admin: ' . $doctor['chuc_danh']
		);
	}
	if ( ! empty( $doctor['anh'] ) ) {
		$admin_url = wp_get_attachment_image_url( (int) $doctor['anh'], 'large' );
		eyecare_test_assert(
			$admin_url && false !== strpos( $html, esc_url( $admin_url ) ),
			'Ảnh đại diện chưa đồng bộ từ Admin: ' . $doctor['ho_ten']
		);
	}
}

eyecare_test_assert(
	5 === substr_count( $html, 'eyecare-home-team__card"' ),
	'Lưới bác sĩ phải có đúng năm thẻ.'
);

// Chân dung trong assets/ là ảnh dự phòng khi bác sĩ chưa có ảnh đại diện.
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
	eyecare_test_assert( is_file( $path ), 'Thiếu chân dung dự phòng: ' . $filename );
}

// Bấm vào bác sĩ nào cũng về trang cá nhân /bac-si/<slug>/, không mở Facebook.
eyecare_test_assert(
	false === strpos( $html, 'facebook.com' ) && false === strpos( $html, 'target="_blank"' ),
	'Khối đội ngũ không được dẫn thẳng sang Facebook.'
);
foreach ( eyecare_du_lieu_doi_ngu() as $doctor ) {
	$profile = esc_url( eyecare_bac_si_ho_so_url( $doctor ) );
	eyecare_test_assert(
		false !== strpos( $html, 'href="' . $profile . '"' ),
		'Thiếu liên kết hồ sơ nội bộ: ' . $doctor['ho_ten']
	);
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
