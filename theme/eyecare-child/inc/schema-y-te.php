<?php
/**
 * Schema y tế — Bệnh viện Mắt Hà Nội – Bắc Ninh
 *
 * Sinh khối JSON-LD khai đúng thực thể tổ chức, thay cho phần schema
 * đã mất khi gỡ plugin SEO ngày 05/08/2026 (lỗi F21).
 *
 * VÌ SAO VIẾT TAY, KHÔNG DÙNG PLUGIN:
 * Plugin SEO không sinh được Hospital / Physician / MedicalCondition
 * đúng cách. Theo HANDOFF-08B, đây chính là chỗ vượt được đối thủ:
 * cả hai bên hiện đều không có schema y tế nào.
 *
 * NGUYÊN TẮC: KHÔNG BỊA DỮ LIỆU.
 * Trường nào chưa có thì bỏ hẳn khỏi khối JSON-LD, không điền giá trị
 * tạm. Một schema thiếu trường thì vô hại; một schema sai thì dạy máy
 * đọc thông tin sai.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dữ liệu thực thể — nguồn sự thật duy nhất.
 *
 * Căn cứ: Giấy chứng nhận ĐKDN (bản ghi ĐÓNG-01 trong DECISION-LOG.md)
 * và bản đề án gửi Sở Y tế – Sở Giáo dục.
 */
function eyecare_du_lieu_thuc_the() {
	$du_lieu = array(

		/* ---- ĐÃ CHỐT, có căn cứ giấy tờ ---------------------------- */

		// Thứ tự tên là BẮT BUỘC — quyết định QĐ-01. Không viết đảo.
		'ten'            => 'Bệnh viện Mắt Hà Nội – Bắc Ninh',
		'phap_nhan'      => 'CÔNG TY CỔ PHẦN BỆNH VIỆN MẮT HÀ NỘI – BẮC NINH',
		'mst'            => '2401020783',

		'dia_chi'        => 'Lô 4, đường Hùng Vương',
		'phuong'         => 'Phường Bắc Giang',
		'tinh'           => 'Thành phố Bắc Ninh',
		'quoc_gia'       => 'VN',

		// SỐ TỔNG ĐÀI CHÍNH THỨC: 0868 899 396
		// Nguồn: người phụ trách dự án xác nhận trực tiếp 06/08/2026
		// ("sau này có thay đổi sửa sau"). Chốt câu hỏi B-16.
		//
		// Số này ĐÈ LÊN các số cũ đang mâu thuẫn:
		//   - website hiện tại:   098 842 88 68
		//   - đề án gửi Sở Y tế:  0988 428 868
		//   - giấy chứng nhận ĐKDN: 0988428868  ← ⚠️ vẫn khác, xem B-16
		//
		// Đổi số thì SỬA DUY NHẤT Ở ĐÂY. Nơi khác gọi qua [hotline],
		// eyecare_hotline_hien() hoặc eyecare_hotline_goi().
		'dien_thoai'     => '+84868899396',
		'dien_thoai_hien'=> '0868 899 396',   // dạng hiển thị cho người đọc

		// Mở cửa 7 ngày — lợi thế cạnh tranh đang bị chôn (lỗi F14).
		'gio_mo'         => '07:30',
		'gio_dong'       => '18:00',

		/* ---- CHƯA CÓ — để rỗng, KHÔNG bịa ------------------------- */

		// Toạ độ cửa vào. Chưa có — cần đo tại chỗ hoặc lấy từ
		// Google Business Profile khi hồ sơ được duyệt.
		// Điền vào đây khi có, khối geo sẽ tự xuất hiện.
		'vi_do'          => '',
		'kinh_do'        => '',

		/* ---- GIẤY PHÉP HOẠT ĐỘNG — đọc từ bản giấy ----------------- */

		// Số giấy phép do người phụ trách đối chiếu bản giấy và xác nhận
		// ngày 07/10/2026. Trước đó trường này để rỗng vì chưa có bản giấy.
		// Rỗng thì khối identifier và trang công bố tự ẩn, KHÔNG bịa số.
		'so_gphd'        => '444/BYT-GPHĐ',
		'gphd_ngay_cap'  => '2026-10-06',
		'gphd_co_quan'   => 'Bộ Y tế',
		'gphd_nguoi_ky'  => 'Thứ trưởng Thường trực Vũ Mạnh Hà',
		'gphd_hinh_thuc' => 'Bệnh viện chuyên khoa',

		// Địa chỉ ghi NGUYÊN VĂN trên bản giấy. Giấy cấp 06/10/2026 còn dùng
		// “tỉnh Bắc Ninh”, trong khi địa danh hành chính hiện hành là
		// “Thành phố Bắc Ninh” (Nghị quyết 202/2025/QH15 và 39/2026/QH16).
		// Giữ cả hai: schema/giao diện dùng địa danh hiện hành ở trên,
		// trường này chỉ để trích dẫn đúng bản giấy trên trang công bố.
		'gphd_dia_chi_nguyen_van' => 'Lô 04, đường Hùng Vương, phường Bắc Giang, tỉnh Bắc Ninh',

		// Giờ hoạt động ghi trên giấy phép là “24/24 giờ”. Giờ tiếp nhận
		// khám theo lịch vẫn là gio_mo–gio_dong ở trên. CHƯA công bố 24/24
		// cho người bệnh vì đang chờ bệnh viện xác nhận có trực đêm thật;
		// để rỗng thì trang giấy phép không in dòng giờ trên giấy.
		'gphd_gio_tren_giay' => '',

		// Ảnh chụp bản giấy, đặt trong assets/giay-phep/.
		'gphd_anh'       => 'giay-phep/gphd-444-byt-2026.webp',
		'gphd_anh_rong'  => 1496,
		'gphd_anh_cao'   => 2000,

		// Các hồ sơ chính thức khác của bệnh viện trên mạng.
		// Chỉ thêm địa chỉ đã xác minh là của bệnh viện.
		'same_as'        => array(
			'https://www.facebook.com/benhvienmathanoibacninh',
		),
		// Hồ sơ Maps công khai khớp website, số tổng đài và địa điểm nhúng.
		'map_url'        => 'https://www.google.com/maps/place/B%E1%BB%87nh+Vi%E1%BB%87n+M%E1%BA%AFt+H%C3%A0+N%E1%BB%99i+-+B%E1%BA%AFc+Ninh/data=!4m2!3m1!1s0x313509eacd610853:0x354f69b583ef6a07',

		// URL nhúng Google Maps do người quản trị cập nhật trong trang
		// “Thông tin liên hệ”. Trường này không được đưa vào schema.
		'map_embed'      => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1105.3745839551725!2d106.20423332026398!3d21.270592741388416!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x313509eacd610853%3A0x354f69b583ef6a07!2zQuG7h25oIFZp4buHViBN4bqvdCBIw6AgTuG7mWkgLSBC4bqvYyBOaW5o!5e0!3m2!1svi!2s!4v1786318023452!5m2!1svi!2s',
	);

	/* Cho phép trang quản trị ghi đè các trường liên hệ vào tệp JSON trong
	   theme. Chỉ những khóa đã được kiểm soát trong hàm đọc cấu hình mới được
	   ghép, nên dữ liệu lạ trong tệp không thể chui vào schema/giao diện. */
	if ( function_exists( 'eyecare_lien_he_doc_cau_hinh' ) ) {
		$ghi_de = eyecare_lien_he_doc_cau_hinh();
		if ( is_array( $ghi_de ) ) {
			$du_lieu = array_merge( $du_lieu, $ghi_de );
		}
	}

	return $du_lieu;
}

/**
 * Ghép địa chỉ đầy đủ thành một chuỗi.
 */
function eyecare_dia_chi_day_du() {
	$d = eyecare_du_lieu_thuc_the();
	return $d['dia_chi'] . ', ' . $d['phuong'] . ', ' . $d['tinh'];
}

/* ==========================================================================
 * GIẤY PHÉP HOẠT ĐỘNG
 * --------------------------------------------------------------------------
 * Mọi nơi hiển thị giấy phép (trang công bố, chân trang, schema) đọc qua
 * các hàm dưới đây. Chưa có số giấy phép thì eyecare_co_gphd() trả false và
 * tất cả các điểm hiển thị tự ẩn — không có chỗ nào in giá trị tạm.
 * ========================================================================== */

/** Đường dẫn trang công bố giấy phép. */
const EYECARE_GPHD_SLUG = 'giay-phep-hoat-dong';

/**
 * Đã có số giấy phép để công bố hay chưa.
 *
 * @return bool
 */
function eyecare_co_gphd() {
	$d = eyecare_du_lieu_thuc_the();
	return ! empty( $d['so_gphd'] );
}

/**
 * URL trang công bố giấy phép, rỗng khi chưa có số giấy phép.
 *
 * @return string
 */
function eyecare_gphd_url() {
	if ( ! eyecare_co_gphd() ) {
		return '';
	}
	return home_url( '/' . EYECARE_GPHD_SLUG . '/' );
}

/**
 * Ngày cấp dạng người Việt đọc được, rỗng khi chưa có hoặc sai định dạng.
 *
 * @return string
 */
function eyecare_gphd_ngay_hien() {
	$d = eyecare_du_lieu_thuc_the();
	$ngay = isset( $d['gphd_ngay_cap'] ) ? (string) $d['gphd_ngay_cap'] : '';

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ngay ) ) {
		return '';
	}

	$moc = strtotime( $ngay . ' 00:00:00 +0000' );
	if ( false === $moc ) {
		return '';
	}

	return wp_date( 'd/m/Y', $moc, new DateTimeZone( 'UTC' ) );
}

/**
 * URL ảnh chụp bản giấy, rỗng khi tệp không tồn tại trong theme.
 *
 * Kiểm tra tệp thật để trang không hiển thị ô ảnh lỗi khi ảnh bị xóa.
 *
 * @return string
 */
function eyecare_gphd_anh_url() {
	$d = eyecare_du_lieu_thuc_the();
	$duong_dan = isset( $d['gphd_anh'] ) ? ltrim( (string) $d['gphd_anh'], '/' ) : '';

	if ( '' === $duong_dan || ! is_file( get_stylesheet_directory() . '/assets/' . $duong_dan ) ) {
		return '';
	}

	return get_stylesheet_directory_uri() . '/assets/' . $duong_dan;
}

/**
 * Số tổng đài dạng bấm gọi được — dùng cho thuộc tính href="tel:".
 */
function eyecare_hotline_goi() {
	$d = eyecare_du_lieu_thuc_the();
	return $d['dien_thoai'];
}

/**
 * Số tổng đài dạng người đọc — dùng để in ra màn hình.
 */
function eyecare_hotline_hien() {
	$d = eyecare_du_lieu_thuc_the();
	return $d['dien_thoai_hien'];
}

/**
 * Mã ngắn [hotline] — chèn số tổng đài vào bất kỳ trang nào.
 *
 * Dùng trong nội dung trang:  [hotline]
 * Muốn ra link bấm gọi:       [hotline link="1"]
 *
 * Mục đích: sau này đổi số thì sửa một chỗ duy nhất trong
 * eyecare_du_lieu_thuc_the(), không phải đi sửa từng trang.
 */
function eyecare_ma_ngan_hotline( $thuoc_tinh ) {
	$t = shortcode_atts( array( 'link' => '0' ), $thuoc_tinh, 'hotline' );

	$hien = esc_html( eyecare_hotline_hien() );

	if ( '1' === (string) $t['link'] ) {
		return '<a href="tel:' . esc_attr( eyecare_hotline_goi() ) . '">' . $hien . '</a>';
	}

	return $hien;
}
add_shortcode( 'hotline', 'eyecare_ma_ngan_hotline' );

/**
 * Khối thực thể tổ chức — dùng chung cho mọi trang.
 *
 * Dùng @type mảng để khai đồng thời Hospital và MedicalOrganization:
 * Hospital mô tả cơ sở vật lý, MedicalOrganization mô tả tổ chức y tế.
 */
function eyecare_schema_to_chuc() {
	$d    = eyecare_du_lieu_thuc_the();
	$goc  = home_url( '/' );

	$org = array(
		'@type'    => array( 'Hospital', 'MedicalOrganization' ),
		'@id'      => $goc . '#to-chuc',
		'name'     => $d['ten'],
		'legalName'=> $d['phap_nhan'],
		'url'      => $goc,
		'hasMap'   => $d['map_url'],
		'telephone'=> $d['dien_thoai'],

		// Chuyên khoa mắt — thuật ngữ chuẩn của schema.org
		'medicalSpecialty' => 'https://schema.org/Ophthalmology',

		'address'  => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $d['dia_chi'],
			'addressLocality' => $d['phuong'],
			'addressRegion'   => $d['tinh'],
			'addressCountry'  => $d['quoc_gia'],
		),

		// Mở cửa cả 7 ngày
		'openingHoursSpecification' => array(
			array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array(
					'Monday', 'Tuesday', 'Wednesday', 'Thursday',
					'Friday', 'Saturday', 'Sunday',
				),
				'opens'     => $d['gio_mo'],
				'closes'    => $d['gio_dong'],
			),
		),

		// Mã số thuế — tăng độ tin cậy, và phòng rủi ro nhầm pháp nhân
		// (có một pháp nhân tên gần trùng đã giải thể — xem source/01).
		'taxID'    => $d['mst'],
	);

	// Toạ độ: chỉ thêm khi CÓ ĐỦ cả hai. Toạ độ sai còn hại hơn không có.
	if ( $d['vi_do'] !== '' && $d['kinh_do'] !== '' ) {
		$org['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => $d['vi_do'],
			'longitude' => $d['kinh_do'],
		);
	}

	// Số giấy phép hoạt động: chỉ thêm khi đã được cấp.
	if ( $d['so_gphd'] !== '' ) {
		$org['identifier'] = array(
			'@type' => 'PropertyValue',
			'name'  => 'Giấy phép hoạt động khám bệnh, chữa bệnh',
			'value' => $d['so_gphd'],
		);

		/* Khai cả dạng hasCredential để máy đọc biết cơ quan cấp và ngày cấp,
		   không chỉ một con số trơ. Mỗi trường chỉ in khi thật sự có. */
		$chung_nhan = array(
			'@type' => array( 'EducationalOccupationalCredential' ),
			'name'  => 'Giấy phép hoạt động khám bệnh, chữa bệnh số ' . $d['so_gphd'],
			'identifier' => $d['so_gphd'],
		);

		if ( ! empty( $d['gphd_co_quan'] ) ) {
			$chung_nhan['recognizedBy'] = array(
				'@type' => 'GovernmentOrganization',
				'name'  => $d['gphd_co_quan'],
			);
		}

		if ( ! empty( $d['gphd_ngay_cap'] ) ) {
			$chung_nhan['validFrom'] = $d['gphd_ngay_cap'];
		}

		$trang_gphd = eyecare_gphd_url();
		if ( '' !== $trang_gphd ) {
			$chung_nhan['url'] = $trang_gphd;
		}

		$org['hasCredential'] = $chung_nhan;
	}

	// URL Facebook chính thức do người phụ trách website xác nhận 30/09/2026.
	if ( ! empty( $d['same_as'] ) ) {
		$org['sameAs'] = array_values( array_filter( $d['same_as'], 'esc_url_raw' ) );
	}

	// Logo: chỉ khai khi website thật sự có logo tuỳ chỉnh.
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
		if ( $logo_url ) {
			$org['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo_url,
			);
		}
	}

	return $org;
}

/**
 * Khối website.
 */
function eyecare_schema_website() {
	$goc = home_url( '/' );
	return array(
		'@type'      => 'WebSite',
		'@id'        => $goc . '#website',
		'url'        => $goc,
		'name'       => get_bloginfo( 'name' ),
		'inLanguage' => 'vi-VN',
		'publisher'  => array( '@id' => $goc . '#to-chuc' ),
	);
}

/** Schema bài tin bệnh viện: không gắn nhãn trang y khoa hay người duyệt giả định. */
function eyecare_schema_bai_tin() {
	if ( ! is_singular( 'post' ) || ! function_exists( 'eyecare_la_bai_tin_tuc' ) || ! eyecare_la_bai_tin_tuc( get_queried_object_id() ) ) {
		return null;
	}
	$id   = (int) get_queried_object_id();
	$goc  = home_url( '/' );
	$link = get_permalink( $id );
	$mo_ta = get_the_excerpt( $id );
	$bai  = array(
		'@type'            => 'Article',
		'@id'              => $link . '#bai-viet',
		'mainEntityOfPage'  => array( '@id' => $link ),
		'headline'         => get_the_title( $id ),
		'datePublished'     => get_post_time( DATE_W3C, false, $id ),
		'dateModified'      => get_post_modified_time( DATE_W3C, false, $id ),
		'inLanguage'        => 'vi-VN',
		'publisher'         => array( '@id' => $goc . '#to-chuc' ),
		'author'            => array( '@id' => $goc . '#to-chuc' ),
	);
	if ( $mo_ta ) {
		$bai['description'] = wp_strip_all_tags( $mo_ta );
	}
	$thumbnail = get_the_post_thumbnail_url( $id, 'full' );
	if ( $thumbnail ) {
		$bai['image'] = esc_url_raw( $thumbnail );
	}
	if ( function_exists( 'eyecare_bai_bac_si_id' ) ) {
		$doctor_id = (int) eyecare_bai_bac_si_id( $id );
		if ( $doctor_id && function_exists( 'eyecare_bac_si_ho_so_url' ) ) {
			$facebook = function_exists( 'eyecare_bac_si_facebook_url' ) ? eyecare_bac_si_facebook_url( $doctor_id ) : '';
			$bai['author'] = array(
				'@type' => 'Person',
				'name'  => get_the_title( $doctor_id ),
				'url'   => $facebook ?: eyecare_bac_si_ho_so_url( $doctor_id ),
			);
			if ( $facebook ) {
				$bai['author']['sameAs'] = array( $facebook );
			}
		}
	}
	return $bai;
}

/** Breadcrumb thống nhất với giao diện bài tin. */
function eyecare_schema_duong_dan_bai_tin() {
	if ( ! is_singular( 'post' ) || ! function_exists( 'eyecare_la_bai_tin_tuc' ) || ! eyecare_la_bai_tin_tuc( get_queried_object_id() ) ) {
		return null;
	}
	$id    = (int) get_queried_object_id();
	$root  = get_category_by_slug( 'tin-tuc' );
	$terms = get_the_category( $id );
	$child = null;
	foreach ( $terms as $term ) {
		if ( $root && (int) $term->parent === (int) $root->term_id ) {
			$child = $term;
			break;
		}
	}
	$items = array(
		array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => home_url( '/' ) ),
		array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Tin bệnh viện', 'item' => home_url( '/tin-tuc/' ) ),
	);
	if ( $child ) {
		$url = get_term_link( $child );
		if ( ! is_wp_error( $url ) ) {
			$items[] = array( '@type' => 'ListItem', 'position' => 3, 'name' => $child->name, 'item' => $url );
		}
	}
	$items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => get_the_title( $id ) );
	return array( '@type' => 'BreadcrumbList', '@id' => get_permalink( $id ) . '#duong-dan', 'itemListElement' => $items );
}

/**
 * Đường dẫn phân cấp (breadcrumb) — giúp máy hiểu cây thư mục 4 cấp.
 */
function eyecare_schema_duong_dan() {
	if ( ! is_page() ) {
		return null;
	}

	$goc   = home_url( '/' );
	$to_tien = array();
	$id      = get_the_ID();

	// Lần ngược lên trang cha
	$cha_ids = array();
	$p = wp_get_post_parent_id( $id );
	while ( $p ) {
		array_unshift( $cha_ids, $p );
		$p = wp_get_post_parent_id( $p );
	}

	$vi_tri = 1;
	$to_tien[] = array(
		'@type'    => 'ListItem',
		'position' => $vi_tri++,
		'name'     => 'Trang chủ',
		'item'     => $goc,
	);

	foreach ( $cha_ids as $cid ) {
		$to_tien[] = array(
			'@type'    => 'ListItem',
			'position' => $vi_tri++,
			'name'     => get_the_title( $cid ),
			'item'     => get_permalink( $cid ),
		);
	}

	$to_tien[] = array(
		'@type'    => 'ListItem',
		'position' => $vi_tri,
		'name'     => get_the_title( $id ),
	);

	// Chỉ có trang chủ thì không cần breadcrumb
	if ( count( $to_tien ) < 2 ) {
		return null;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => get_permalink( $id ) . '#duong-dan',
		'itemListElement' => $to_tien,
	);
}

/**
 * Xuất khối JSON-LD ra thẻ head.
 */
function eyecare_in_schema() {

	// Không xuất ở trang quản trị, trang tìm kiếm, trang 404.
	if ( is_admin() || is_search() || is_404() ) {
		return;
	}

	// Chạy trước nội dung để gom các cặp hỏi–đáp trong mã ngắn [faq].
	// Cần thiết vì wp_head() in ra TRƯỚC khi nội dung bài được xử lý, mà
	// FAQPage schema lại phải lấy dữ liệu từ chính nội dung đó.
	// do_shortcode chỉ chạy [faq] và bỏ qua phần còn lại nên không tốn kém.
	if ( function_exists( 'eyecare_gom_faq_som' ) ) {
		eyecare_gom_faq_som();
	}

	$do_thi = array( eyecare_schema_to_chuc(), eyecare_schema_website() );

	// Trang: breadcrumb theo cây phân cấp.
	$dd = eyecare_schema_duong_dan();
	if ( $dd ) {
		$do_thi[] = $dd;
	}

	// Trang địa bàn: nguồn công khai theo xã/phường, không gắn bác sĩ duyệt khi chưa duyệt.
	if ( function_exists( 'eyecare_schema_khu_vuc' ) ) {
		$trang_khu_vuc = eyecare_schema_khu_vuc();
		if ( $trang_khu_vuc ) {
			$do_thi[] = $trang_khu_vuc;
		}
	}

	// Page nội dung y khoa dài: MedicalWebPage và bác sĩ đứng tên nội dung.
	// Chỉ các page đã được đánh dấu _bvmat_noi_dung_y_khoa mới đi vào nhánh
	// này; page thông thường không bị gắn schema y khoa ngoài ý muốn.
	if ( function_exists( 'eyecare_schema_trang_y_khoa' ) ) {
		$trang_y_khoa = eyecare_schema_trang_y_khoa();
		if ( $trang_y_khoa ) {
			$do_thi[] = $trang_y_khoa;

			if ( function_exists( 'eyecare_schema_bac_si' ) && function_exists( 'eyecare_bai_bac_si_id' ) ) {
				$ids = array( eyecare_bai_bac_si_id( get_queried_object_id() ) );
				if ( function_exists( 'eyecare_bai_bac_si_duyet_id' ) && get_post_meta( get_queried_object_id(), '_bvmat_bac_si_duyet', true ) ) {
					$ids[] = eyecare_bai_bac_si_duyet_id( get_queried_object_id() );
				}
				foreach ( array_unique( array_filter( $ids ) ) as $doctor_id ) {
					$node = eyecare_schema_bac_si( $doctor_id );
					if ( $node ) {
						$do_thi[] = $node;
					}
				}
			}
		}
	}

	// Bài hướng dẫn dài được hiển thị ở cuối trang Liên hệ, dùng dữ liệu FAQ
	// từ cùng một nguồn với phần HTML để không có câu hỏi ẩn hoặc lệch nội dung.
	if ( function_exists( 'eyecare_schema_lien_he_noi_dung' ) ) {
		foreach ( eyecare_schema_lien_he_noi_dung() as $nut ) {
			$do_thi[] = $nut;
		}
	}

	// Tin bệnh viện là Article thông thường; chỉ bài kiến thức mới là MedicalWebPage.
	if ( function_exists( 'eyecare_la_bai_tin_tuc' ) && is_singular( 'post' ) && eyecare_la_bai_tin_tuc( get_queried_object_id() ) ) {
		$do_thi[] = eyecare_schema_bai_tin();
		$do_thi[] = eyecare_schema_duong_dan_bai_tin();
	} elseif ( function_exists( 'eyecare_schema_bai_viet' ) ) {
		$bv = eyecare_schema_bai_viet();
		if ( $bv ) {
			$do_thi[] = $bv;
			if ( function_exists( 'eyecare_schema_bac_si' ) && function_exists( 'eyecare_bai_bac_si_id' ) ) {
				$ids = array( eyecare_bai_bac_si_id( get_queried_object_id() ) );
				if ( function_exists( 'eyecare_bai_bac_si_duyet_id' ) && get_post_meta( get_queried_object_id(), '_bvmat_bac_si_duyet', true ) ) {
					$ids[] = eyecare_bai_bac_si_duyet_id( get_queried_object_id() );
				}
				foreach ( array_unique( array_filter( $ids ) ) as $doctor_id ) {
					$node = eyecare_schema_bac_si( $doctor_id );
					if ( $node ) {
						$do_thi[] = $node;
					}
				}
			}

			$dd_bv = eyecare_schema_duong_dan_bai_viet();
			if ( $dd_bv ) {
				$do_thi[] = $dd_bv;
			}
		}
	}

	// FAQPage — chỉ khi nội dung thật sự có khối hỏi đáp.
	if ( function_exists( 'eyecare_schema_faq' ) ) {
		$faq = eyecare_schema_faq();
		if ( $faq ) {
			$do_thi[] = $faq;
		}
	}

	$json = array(
		'@context' => 'https://schema.org',
		'@graph'   => $do_thi,
	);

	echo "\n<!-- Schema y tế — Bệnh viện Mắt Hà Nội – Bắc Ninh -->\n";
	echo '<script type="application/ld+json">' . "\n";
	echo wp_json_encode(
		$json,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
	);
	echo "\n</script>\n";
}
add_action( 'wp_head', 'eyecare_in_schema', 5 );

/**
 * Trang Giới thiệu và Liên hệ đã có Hospital/BreadcrumbList từ child theme;
 * Liên hệ còn có schema bài hướng dẫn và FAQ. Giữ breadcrumb hiển thị của OBS,
 * chỉ bỏ JSON-LD thừa và OG trùng trên trang Liên hệ.
 */
function eyecare_go_metadata_obs_trang_thong_tin() {
	if ( ! is_page() || ! class_exists( 'OBS_Loader' ) ) {
		return;
	}

	$trang = get_queried_object();
	if ( ! $trang instanceof WP_Post ) {
		return;
	}

	$duong_dan = trim( get_page_uri( $trang ), '/' );
	if ( ! in_array( $duong_dan, array( 'lien-he', 'gioi-thieu' ), true ) ) {
		return;
	}

	$schema = OBS_Loader::get( 'schema' );
	if ( $schema ) {
		remove_action( 'wp_head', array( $schema, 'render' ), 10 );
	}

	$breadcrumb = OBS_Loader::get( 'breadcrumb' );
	if ( $breadcrumb ) {
		remove_action( 'wp_head', array( $breadcrumb, 'render_schema' ), 15 );
	}

	if ( 'lien-he' === $duong_dan ) {
		$opengraph = OBS_Loader::get( 'opengraph' );
		if ( $opengraph ) {
			remove_action( 'wp_head', array( $opengraph, 'render' ), 5 );
		}
	}
}
add_action( 'wp', 'eyecare_go_metadata_obs_trang_thong_tin', 20 );

/** Trang chủ tĩnh cần canonical tự tham chiếu; trang đơn dùng canonical của WordPress/SEO. */
function eyecare_canonical_trang_chu() {
	if ( is_front_page() && ! is_paged() ) {
		echo '<link rel="canonical" href="' . esc_url( home_url( '/' ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'eyecare_canonical_trang_chu', 4 );


/**
 * Đặt noindex cho trang kỹ thuật đã đánh dấu [X].
 *
 * Lỗi F26: ba trang kỹ thuật lọt vào sitemap.
 */
function eyecare_noindex_trang_ky_thuat() {
	if ( ! is_singular() ) {
		return;
	}
	if ( get_post_meta( get_the_ID(), '_bvmat_noindex', true ) === '1' ) {
		echo '<meta name="robots" content="noindex, follow" />' . "\n";
	}
}
add_action( 'wp_head', 'eyecare_noindex_trang_ky_thuat', 1 );


/**
 * Loại trang [X] khỏi sitemap của WordPress lõi.
 */
function eyecare_loai_khoi_sitemap( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}
	$args['meta_query'] = array(
		'relation' => 'OR',
		array( 'key' => '_bvmat_noindex', 'compare' => 'NOT EXISTS' ),
		array( 'key' => '_bvmat_noindex', 'value' => '1', 'compare' => '!=' ),
	);
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'eyecare_loai_khoi_sitemap', 10, 2 );
