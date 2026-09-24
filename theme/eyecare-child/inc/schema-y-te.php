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
		'tinh'           => 'Tỉnh Bắc Ninh',
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

		// Số Giấy phép hoạt động. Chưa có GPHĐ — câu hỏi B-03.
		'so_gphd'        => '',

		// Các hồ sơ chính thức khác của bệnh viện trên mạng.
		// Chỉ thêm địa chỉ đã xác minh là của bệnh viện.
		'same_as'        => array(
			'https://www.facebook.com/BenhVienMatHNBN',
		),

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
		'telephone'=> $d['dien_thoai'],

		// Chuyên khoa mắt — thuật ngữ chuẩn của schema.org
		'medicalSpecialty' => 'Ophthalmologic',

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
	}

	if ( ! empty( $d['same_as'] ) ) {
		$org['sameAs'] = array_values( $d['same_as'] );
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

			if ( function_exists( 'eyecare_schema_bac_si' ) ) {
				$do_thi[] = eyecare_schema_bac_si();
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

	// Bài viết: MedicalWebPage + Article, breadcrumb theo chuyên mục,
	// và khối bác sĩ đứng tên nội dung.
	if ( function_exists( 'eyecare_schema_bai_viet' ) ) {
		$bv = eyecare_schema_bai_viet();
		if ( $bv ) {
			$do_thi[] = $bv;
			$do_thi[] = eyecare_schema_bac_si();

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
