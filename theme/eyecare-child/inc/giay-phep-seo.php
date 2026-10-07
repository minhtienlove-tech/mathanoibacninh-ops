<?php
/**
 * Tiêu đề, mô tả và Open Graph cho trang công bố giấy phép hoạt động.
 *
 * Nội dung trang nằm trong template (page-giay-phep-hoat-dong.php), nên bản
 * ghi `post_content` trong database rỗng. Để OBS SEO Suite tự sinh thì mô tả
 * sẽ rỗng hoặc lấy từ đoạn không liên quan, vì vậy child theme tự xuất đúng
 * một bộ metadata và gỡ bộ Open Graph trùng của OBS trên riêng trang này —
 * cùng cách đã áp dụng cho nhóm trang khu vực.
 *
 * Mọi giá trị đọc từ eyecare_du_lieu_thuc_the(). Chưa có số giấy phép thì
 * toàn bộ phần này không chạy.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Đang xem trang công bố giấy phép hay không.
 *
 * So theo đường dẫn trang chứ không theo ID, để không phụ thuộc vào ID
 * được cấp khi tạo trang trên production.
 *
 * @return bool
 */
function eyecare_la_trang_gphd() {
	if ( ! is_page() || ! function_exists( 'eyecare_co_gphd' ) || ! eyecare_co_gphd() ) {
		return false;
	}

	return EYECARE_GPHD_SLUG === trim( (string) get_page_uri( get_queried_object() ), '/' );
}

/**
 * Mô tả trang — nêu đúng số giấy phép, cơ quan cấp và việc có bản chụp.
 *
 * @return string
 */
function eyecare_gphd_mo_ta() {
	$d    = eyecare_du_lieu_thuc_the();
	$ngay = eyecare_gphd_ngay_hien();

	$mo_ta = sprintf(
		'%1$s hoạt động theo Giấy phép hoạt động khám bệnh, chữa bệnh số %2$s do %3$s cấp',
		$d['ten'],
		$d['so_gphd'],
		$d['gphd_co_quan']
	);

	if ( '' !== $ngay ) {
		$mo_ta .= ' ngày ' . $ngay;
	}

	$mo_ta .= sprintf(
		'. Xem thông tin cơ sở, cơ quan cấp phép và bản chụp giấy phép để đối chiếu trước khi đến khám tại %s.',
		eyecare_dia_chi_day_du()
	);

	return $mo_ta;
}

/**
 * Tiêu đề trang gồm số giấy phép, để kết quả tìm kiếm trả lời ngay.
 *
 * @param string $title Tiêu đề do WordPress hoặc plugin dựng.
 * @return string
 */
function eyecare_gphd_tieu_de( $title ) {
	if ( ! eyecare_la_trang_gphd() ) {
		return $title;
	}

	$d = eyecare_du_lieu_thuc_the();

	return sprintf(
		'Giấy phép hoạt động số %1$s – %2$s',
		$d['so_gphd'],
		$d['ten']
	);
}
add_filter( 'pre_get_document_title', 'eyecare_gphd_tieu_de', 110 );

/**
 * Gỡ Open Graph và schema trùng của OBS trên riêng trang giấy phép.
 *
 * Giữ dữ liệu OBS nguyên vẹn, chỉ ngừng đầu ra ở trang này để trang không có
 * hai bộ og:description khác nhau.
 *
 * @return void
 */
function eyecare_gphd_go_metadata_obs_trung() {
	if ( ! eyecare_la_trang_gphd() || ! class_exists( 'OBS_Loader' ) ) {
		return;
	}

	$opengraph = OBS_Loader::get( 'opengraph' );
	if ( $opengraph ) {
		remove_action( 'wp_head', array( $opengraph, 'render' ), 5 );
	}
}
add_action( 'wp', 'eyecare_gphd_go_metadata_obs_trung', 20 );

/**
 * In một bộ meta description, Open Graph và Twitter cho trang giấy phép.
 *
 * @return void
 */
function eyecare_gphd_seo_meta() {
	if ( ! eyecare_la_trang_gphd() ) {
		return;
	}

	$d     = eyecare_du_lieu_thuc_the();
	$mo_ta = eyecare_gphd_mo_ta();
	$anh   = eyecare_gphd_anh_url();

	if ( '' === $anh ) {
		$anh = get_site_icon_url( 512 );
	}

	echo '<meta name="description" content="' . esc_attr( $mo_ta ) . '" />' . "\n";

	$og = array(
		'og:type'        => 'website',
		'og:site_name'   => $d['ten'],
		'og:locale'      => 'vi_VN',
		'og:title'       => wp_get_document_title(),
		'og:description' => $mo_ta,
		'og:url'         => get_permalink( get_queried_object_id() ),
	);

	if ( $anh ) {
		$og['og:image'] = $anh;
	}

	foreach ( $og as $thuoc_tinh => $gia_tri ) {
		echo '<meta property="' . esc_attr( $thuoc_tinh ) . '" content="' . esc_attr( $gia_tri ) . '" />' . "\n";
	}

	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $og['og:title'] ) . '" />' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $mo_ta ) . '" />' . "\n";

	if ( $anh ) {
		echo '<meta name="twitter:image" content="' . esc_url( $anh ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'eyecare_gphd_seo_meta', 3 );
