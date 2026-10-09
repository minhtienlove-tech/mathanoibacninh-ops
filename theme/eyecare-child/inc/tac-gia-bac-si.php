<?php
/**
 * Khối tác giả bác sĩ và schema cho bài viết
 * — Bệnh viện Mắt Hà Nội – Bắc Ninh
 *
 * VÌ SAO CÓ TỆP NÀY:
 * Lỗi F25 — mọi nội dung trên site cũ khai tác giả là `admin`. Với E-E-A-T
 * và với AI trích dẫn, nội dung y khoa không có người chịu trách nhiệm
 * đứng tên thì không có giá trị tin cậy nào.
 *
 * 🔴 NGUYÊN TẮC KHÔNG BỊA — giống inc/schema-y-te.php:
 * Số chứng chỉ hành nghề của bác sĩ CHƯA CÓ trong hồ sơ dự án. Trường đó
 * để rỗng và tự động bị loại khỏi schema. Không điền số tạm, không suy ra
 * từ bất kỳ nguồn nào. Bài học QĐ-02: hai nguồn cùng chép từ một chỗ sai
 * thì vẫn khớp nhau — trùng khớp không phải bằng chứng.
 *
 * 🔴 CHỨC DANH: bản ghi F018 trong FACT-LEDGER.csv ghi rõ chức danh hiện
 * tại của Ths.BS Lê Như Tùng là "Cố vấn chuyên môn" — KHÔNG phải chức danh
 * pháp định "người chịu trách nhiệm chuyên môn kỹ thuật" (câu hỏi B-01).
 * Vì vậy khối này KHÔNG ghi "người chịu trách nhiệm chuyên môn kỹ thuật".
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dữ liệu hồ sơ kế thừa. Không dùng để suy luận tác giả cho bài/trang mới.
 *
 * Căn cứ: Giấy chứng nhận ĐKDN (ĐÓNG-01) cho họ tên và vai trò pháp nhân;
 * hồ sơ chuyên môn do chủ đầu tư cung cấp (F018) cho học vị và chức danh.
 */
function eyecare_du_lieu_bac_si() {
	return array(

		/* ---- ĐÃ CHỐT, có căn cứ giấy tờ ---------------------------- */

		'ho_ten'      => 'Lê Như Tùng',
		'hoc_vi'      => 'Ths.BS',

		// 🔴 CHỨC DANH DOANH NGHIỆP CỐ Ý ĐỂ RỖNG — quyết định QĐ-03, 06/08/2026.
		//
		// ĐKDN ghi chức danh người đại diện theo pháp luật là "Giám đốc".
		// Người phụ trách dự án xác nhận (bằng lời) chức danh thực tế là
		// "Chủ tịch HĐQT". Hai nguồn lệch nhau, xác nhận chưa có văn bản.
		//
		// Chọn bỏ hẳn thay vì chọn một trong hai: bài bệnh học cần chứng chỉ
		// CHUYÊN MÔN để người đọc tin, không cần chức danh quản trị. Bỏ đi thì
		// không tạo điểm lệch nào giữa website và hồ sơ pháp lý trên 100 trang
		// công khai — cùng bài học với vụ hotline (QĐ-02).
		//
		// Muốn hiện lại thì phải có văn bản Ban giám đốc, không phải xác nhận
		// miệng. Để rỗng thì khối tác giả tự bỏ dòng chức danh.
		'chuc_danh'   => '',

		'chuyen_khoa' => 'Nhãn khoa',

		/* ---- CHƯA CÓ — để rỗng, KHÔNG bịa ------------------------- */

		// Số chứng chỉ / giấy phép hành nghề. Chưa có trong hồ sơ dự án.
		// Điền vào đây khi có, schema sẽ tự khai thêm trường identifier.
		'so_gphn'     => '',

		// Trang hồ sơ riêng của bác sĩ. Chưa dựng — nhánh
		// /doi-ngu-bac-si/{ho-ten}/ trong kiến trúc 08B chưa có trang con.
		// Để rỗng thì khối tác giả trỏ về trang danh sách đội ngũ.
		'trang_ho_so' => '',
	);
}

/**
 * ĐỘI NGŨ BÁC SĨ — dữ liệu hiển thị trên trang chủ.
 *
 * Đây là dữ liệu mẫu kế thừa. Các mốc định lượng/chức vụ cũ chưa có nguồn
 * đối chiếu được lọc trước khi hiển thị hoặc tạo hồ sơ mới.
 *
 * @return array[]
 */
function eyecare_du_lieu_doi_ngu_mac_dinh() {
	return array(
		array(
			'ho_ten'      => 'ĐẶNG CÔNG HẢI',
			'hoc_vi'      => 'BSCKI.',
			'chuc_danh'   => 'GIÁM ĐỐC BỆNH VIỆN',
			'chuyen_khoa' => 'Nhãn khoa',
			'badge'       => '20+ năm',
			'badges'      => array( '20+ năm', 'Hơn 10.000 ca', 'Chuyên sâu võng mạc' ),
			'anh_url'     => get_stylesheet_directory_uri() . '/assets/doctor-dang-cong-hai.png',
			'anh_can_chinh' => 'dang-cong-hai',
			'highlights'  => array(
				'Tốt nghiệp Bác sĩ chuyên khoa I, Đại học Y Hà Nội',
				'Hơn 20 năm kinh nghiệm nhãn khoa',
				'Thực hiện thành công hàng chục nghìn ca phẫu thuật',
				'Nguyên Trưởng khoa Phẫu thuật BV Mắt Bắc Ninh & BV Mắt Sông Cầu',
				'Có nhiều năm kinh nghiệm điều trị bệnh dịch kính - võng mạc, kiểm soát cận thị, giác mạc',
				'Hơn 10.000 ca Phaco, mộng, quặm',
			),
			'so_gphn'     => '',
			'anh'         => 0,
			'la_tac_gia'  => false,
		),
		array(
			'ho_ten'      => 'LÊ NHƯ TÙNG',
			'hoc_vi'      => 'THS. BS',
			'chuc_danh'   => 'CỐ VẤN CHUYÊN MÔN - CHỦ TỊCH HĐQT',
			'chuyen_khoa' => 'Nhãn khoa',
			'badge'       => '100.000+ ca',
			'badges'      => array( '100.000+ ca', '20+ năm', '10 năm Trưởng khoa' ),
			'anh_url'     => get_stylesheet_directory_uri() . '/assets/doctor-le-nhu-tung.png',
			'anh_can_chinh' => 'le-nhu-tung',
			'highlights'  => array(
				'Thạc sĩ Nhãn khoa, Đại học Y Hà Nội',
				'Hơn 20 năm kinh nghiệm',
				'100.000+ ca Phaco thành công',
				'Chuyên gia Phaco - thay thủy tinh thể nhân tạo',
				'10 năm Trưởng khoa Phẫu thuật BV Mắt Hitec HN',
				'Giải Nhất Hội thảo kỹ thuật Y tế HN 2010',
				'Đào tạo phẫu thuật tại Hà Nội, Viên Chăn (Lào), Bắc Ninh, Thái Nguyên, Thanh Hóa',
				'Phó giám đốc phụ trách chuyên môn tại BV Mắt Thanh An, BV Mắt Bắc Trung Nam, BV Mắt Sông Cầu và hệ thống BV Mắt Hà Nội',
			),
			'so_gphn'     => '',
			'anh'         => 0,
			'la_tac_gia'  => true,               // người đứng tên nội dung y khoa
		),
		array(
			'ho_ten'      => 'BÙI VĂN CẢNH',
			'hoc_vi'      => 'BSCKI.',
			'chuc_danh'   => 'PHÓ GIÁM ĐỐC CHUYÊN MÔN',
			'chuyen_khoa' => 'Nhãn khoa',
			'badge'       => '15+ năm',
			'badges'      => array( '15+ năm', 'Hàng chục nghìn ca', 'Hội Nhãn khoa Việt Nam' ),
			'anh_url'     => get_stylesheet_directory_uri() . '/assets/doctor-bui-van-canh.png',
			'anh_can_chinh' => 'bui-van-canh',
			'highlights'  => array(
				'Bác sĩ CKI, Đại học Y Hà Nội',
				'Nguyên PGĐ chuyên môn BV Mắt Sông Cầu',
				'Hơn 15 năm phẫu thuật Phaco',
				'Hàng chục nghìn ca thành công',
				'Chuyên mộng, quặm và thẩm mỹ',
				'Thành viên Hội Nhãn khoa VN',
			),
			'so_gphn'     => '',
			'anh'         => 0,
			'la_tac_gia'  => false,
		),
		array(
			'ho_ten'      => 'NGUYỄN ĐĂNG ĐẠT',
			'hoc_vi'      => 'CỬ NHÂN',
			'chuc_danh'   => 'TRƯỞNG KHOA CẬN LÂM SÀNG - PHỤ TRÁCH KHỐI ĐIỀU DƯỠNG',
			'chuyen_khoa' => 'Khúc xạ',
			'badge'       => '10+ năm',
			'badges'      => array( '10+ năm', 'Khúc xạ chuyên sâu', 'Kính đa tròng cao cấp' ),
			'anh_url'     => get_stylesheet_directory_uri() . '/assets/doctor-nguyen-dang-dat.jpg',
			'anh_can_chinh' => 'nguyen-dang-dat',
			'highlights'  => array(
				'Tốt nghiệp Cử nhân Trường Đại học Kỹ thuật Y tế Hải Dương',
				'Trưởng khoa Cận lâm sàng, phụ trách khối Điều dưỡng',
				'Chuyên sâu đo khám cận, viễn, loạn thị',
				'Tư vấn kính đa tròng và kính cao cấp',
				'Hơn 10 năm kinh nghiệm nhãn khoa',
			),
			'so_gphn'     => '',
			'anh'         => 0,
			'la_tac_gia'  => false,
		),
		array(
			'ho_ten'      => 'TRẦN KHÁNH THẮNG',
			'hoc_vi'      => 'BSCK.',
			'chuc_danh'   => 'TRƯỞNG KHOA KHÚC XẠ',
			'chuyen_khoa' => 'Khúc xạ - Lasik',
			'badge'       => '5+ năm',
			'badges'      => array( '5+ năm', 'Khúc xạ - Lasik', 'Kiểm soát cận thị trẻ em' ),
			'anh_url'     => get_stylesheet_directory_uri() . '/assets/doctor-tran-khanh-thang.jpg',
			'anh_can_chinh' => 'tran-khanh-thang',
			'highlights'  => array(
				'Tốt nghiệp chuyên khoa Mắt, Đại học Y Hà Nội và Viện Mắt Trung ương',
				'Được đào tạo chuyên sâu về khúc xạ và Lasik',
				'Hơn 5 năm kinh nghiệm khám và điều trị các bệnh về mắt',
				'Chuyên điều trị cận thị trẻ em bằng các phương pháp kiểm soát cận thị hiện đại',
				'Thành viên Hội Nhãn khoa Việt Nam',
			),
			'so_gphn'     => '',
			'anh'         => 0,
			'la_tac_gia'  => false,
		),
		array(
			'ho_ten'      => 'TRẦN ĐỨC THỊNH',
			'hoc_vi'      => 'CỬ NHÂN KHÚC XẠ',
			'chuc_danh'   => 'CHUYÊN GIA KHÚC XẠ',
			'chuyen_khoa' => 'Khúc xạ',
			'badge'       => 'Khúc xạ chuyên sâu',
			'badges'      => array( 'Khúc xạ chuyên sâu', 'Chẩn đoán và điều trị tật khúc xạ', 'Hội Nhãn khoa Việt Nam' ),
			'anh_url'     => get_stylesheet_directory_uri() . '/assets/doctor-tran-duc-thinh.jpg',
			'anh_can_chinh' => 'tran-duc-thinh',
			'highlights'  => array(
				'Tốt nghiệp Cử nhân Đại học Y Hà Nội, chuyên ngành Khúc xạ',
				'Chuyên gia chẩn đoán và điều trị tật khúc xạ',
				'Đã khám và chỉnh kính cho hàng nghìn bệnh nhân tại các bệnh viện lớn ở Hà Nội',
				'Tư vấn giải pháp kính phù hợp theo nhu cầu thị giác của từng người',
				'Thành viên Hội Nhãn khoa Việt Nam',
			),
			'so_gphn'     => '',
			'anh'         => 0,
			'la_tac_gia'  => false,
		),
	);
}

/** Suppress legacy numeric, award and previous-position claims pending evidence. */
function eyecare_du_lieu_doi_ngu_cong_khai( $doctor, $post_id = 0 ) {
	$source = $post_id ? trim( (string) get_post_meta( $post_id, '_eyecare_nguon_thanh_tich', true ) ) : '';
	if ( '' === $source ) {
		$needs_source = static function ( $line ) {
			return (bool) preg_match( '/\d|hàng\s+(?:nghìn|chục)|giải\s+(?:nhất|nhì|ba)|nguyên\s+|từng\s+|thành\s+viên\s+hội|thực\s+hiện\s+thành\s+công|phó\s+giám\s+đốc\s+phụ\s+trách|đào\s+tạo\s+phẫu\s+thuật\s+tại/iu', (string) $line );
		};
		foreach ( array( 'badges', 'highlights' ) as $key ) {
			$lines = isset( $doctor[ $key ] ) && is_array( $doctor[ $key ] ) ? $doctor[ $key ] : array();
			$doctor[ $key ] = array_values( array_filter( $lines, static function ( $line ) use ( $needs_source ) {
				return ! $needs_source( $line );
			} ) );
		}
		$doctor['badge'] = ! empty( $doctor['badges'] ) ? $doctor['badges'][0] : '';
	}
	if ( 'le-nhu-tung' === sanitize_title( isset( $doctor['ho_ten'] ) ? $doctor['ho_ten'] : '' )
		&& empty( $doctor['chuc_danh'] ) ) {
		$doctor['chuc_danh'] = 'Cố vấn chuyên môn cao cấp';
	}
	return $doctor;
}

/**
 * Đọc đội ngũ bác sĩ do người quản trị nhập trong WordPress.
 *
 * Ảnh đại diện được ưu tiên. Trường _eyecare_anh_url chỉ giữ ba ảnh mẫu của
 * theme để dữ liệu ban đầu hiển thị ngay trước khi người quản trị tải ảnh mới.
 *
 * @return array[]
 */
function eyecare_du_lieu_doi_ngu() {
	$post_type = defined( 'EYECARE_POST_TYPE_BAC_SI' ) ? EYECARE_POST_TYPE_BAC_SI : 'eyecare_bac_si';

	if ( ! post_type_exists( $post_type ) ) {
		return array_map( 'eyecare_du_lieu_doi_ngu_cong_khai', eyecare_du_lieu_doi_ngu_mac_dinh() );
	}

	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'order'          => 'ASC',
		)
	);

	if ( ! $posts ) {
		// Trước lần tạo mẫu đầu tiên vẫn dùng dữ liệu trong theme. Sau đó, danh
		// sách rỗng có nghĩa là người quản trị đã chủ động xóa/ẩn toàn bộ bác sĩ.
		return get_option( 'eyecare_doi_ngu_da_tao_mau' )
			? array()
			: array_map( 'eyecare_du_lieu_doi_ngu_cong_khai', eyecare_du_lieu_doi_ngu_mac_dinh() );
	}

	$doi_ngu = array();
	$default_specialties = array();
	foreach ( eyecare_du_lieu_doi_ngu_mac_dinh() as $default_doctor ) {
		$default_specialties[ sanitize_title( $default_doctor['ho_ten'] ) ] = $default_doctor['chuyen_khoa'];
	}
	foreach ( $posts as $post ) {
		$ho_ten     = get_the_title( $post );
		$chuyen_mon = (string) get_post_meta( $post->ID, '_eyecare_chuyen_mon', true );
		if ( '' === $chuyen_mon ) {
			$chuyen_mon = isset( $default_specialties[ sanitize_title( $ho_ten ) ] ) ? $default_specialties[ sanitize_title( $ho_ten ) ] : '';
		}
		$highlights = get_post_meta( $post->ID, '_eyecare_highlights', true );
		$badges     = get_post_meta( $post->ID, '_eyecare_badges', true );
		$anh_id     = (int) get_post_thumbnail_id( $post->ID );
		if ( is_string( $highlights ) ) {
			$highlights = preg_split( '/\R/u', $highlights );
		}

		$highlights = array_values(
			array_filter(
				array_map( 'trim', is_array( $highlights ) ? $highlights : array() )
			)
		);

		if ( is_string( $badges ) ) {
			$badges = preg_split( '/\R/u', $badges );
		}
		$badges = array_values(
			array_filter(
				array_map( 'trim', is_array( $badges ) ? $badges : array() )
			)
		);
		if ( empty( $badges ) ) {
			$badge_fallback = (string) get_post_meta( $post->ID, '_eyecare_badge', true );
			$badges         = '' !== $badge_fallback ? array( $badge_fallback ) : array();
		}

		$doi_ngu[] = eyecare_du_lieu_doi_ngu_cong_khai( array(
			'post_id'     => (int) $post->ID,
			'ho_ten'      => $ho_ten,
			'hoc_vi'      => (string) get_post_meta( $post->ID, '_eyecare_hoc_vi', true ),
			'chuc_danh'   => (string) get_post_meta( $post->ID, '_eyecare_chuc_danh', true ),
			'chuyen_khoa' => $chuyen_mon,
			'badge'       => ! empty( $badges ) ? $badges[0] : '',
			'badges'      => $badges,
			'anh_url'     => (string) get_post_meta( $post->ID, '_eyecare_anh_url', true ),
			'anh_can_chinh' => $anh_id ? '' : (string) get_post_meta( $post->ID, '_eyecare_anh_can_chinh', true ),
			'highlights'  => $highlights,
			'gioi_thieu'  => $post->post_content,
			'facebook'    => function_exists( 'eyecare_bac_si_facebook_url' ) ? eyecare_bac_si_facebook_url( $post->ID ) : '',
			'profile'     => function_exists( 'eyecare_bac_si_ho_so_url' ) ? eyecare_bac_si_ho_so_url( $post->ID ) : home_url( '/doi-ngu-bac-si/' ),
			'so_gphn'     => '',
			'anh'         => $anh_id,
			'la_tac_gia'  => 'le-nhu-tung' === sanitize_title( $ho_ten ),
		), $post->ID );
	}

	return $doi_ngu;
}

/**
 * Tên đầy đủ dạng hiển thị của một bác sĩ trong đội ngũ: "BSCKI Đặng Công Hải".
 *
 * @param array $b Một phần tử của eyecare_du_lieu_doi_ngu().
 * @return string
 */
function eyecare_doi_ngu_ten_day_du( $b ) {
	return trim( ( isset( $b['hoc_vi'] ) ? $b['hoc_vi'] : '' ) . ' ' . ( isset( $b['ho_ten'] ) ? $b['ho_ten'] : '' ) );
}

/**
 * In khối "Đội ngũ bác sĩ" — dùng cho trang chủ (và trang /doi-ngu-bac-si/).
 *
 * Khối này giới thiệu cho người đọc; thực thể Physician/Person trong JSON-LD
 * chỉ xuất hiện khi bài/trang đã chỉ định đúng người viết hoặc người duyệt.
 *
 * @param bool $tren_trang_chu true: bản gọn cho trang chủ (có nhãn + đường dẫn).
 */
function eyecare_doi_ngu_bac_si_in( $tren_trang_chu = false ) {

	$ds = eyecare_du_lieu_doi_ngu();
	if ( empty( $ds ) ) {
		return;
	}

	$slider_id = wp_unique_id( 'eyecare-doctor-slider-' );
	$co_slider = count( $ds ) > 1;

	echo '<div class="eyecare-doi-ngu__slider" data-doctor-slider="true">';
	if ( $co_slider ) {
		echo '<div class="eyecare-doi-ngu__slider-nav" aria-label="Điều khiển danh sách bác sĩ">';
		echo '<button class="eyecare-doi-ngu__slider-button" type="button" aria-label="Xem bác sĩ trước" aria-controls="' . esc_attr( $slider_id ) . '" data-doctor-prev="true"><span aria-hidden="true">‹</span></button>';
		echo '<button class="eyecare-doi-ngu__slider-button eyecare-doi-ngu__slider-pause" type="button" aria-label="Tạm dừng tự động chuyển bác sĩ" aria-controls="' . esc_attr( $slider_id ) . '" aria-pressed="false" data-doctor-pause="true"><span aria-hidden="true" data-doctor-pause-icon="true">Ⅱ</span></button>';
		echo '<button class="eyecare-doi-ngu__slider-button" type="button" aria-label="Xem bác sĩ tiếp theo" aria-controls="' . esc_attr( $slider_id ) . '" data-doctor-next="true"><span aria-hidden="true">›</span></button>';
		echo '</div>';
	}

	echo '<div id="' . esc_attr( $slider_id ) . '" class="eyecare-doi-ngu__ds" data-doctor-team="true" data-doctor-track="true" tabindex="0" aria-label="Danh sách bác sĩ, dùng phím mũi tên trái và phải để di chuyển">';

	foreach ( $ds as $index => $b ) {

		$ten = eyecare_doi_ngu_ten_day_du( $b );
		$highlights = ! empty( $b['highlights'] ) && is_array( $b['highlights'] ) ? $b['highlights'] : array();
		$an_con_lai = max( count( $highlights ) - 3, 0 );
		$highlights_id = 'eyecare-doctor-highlights-' . (int) $index;

		$anh_can_chinh = ! empty( $b['anh_can_chinh'] ) ? sanitize_html_class( $b['anh_can_chinh'] ) : '';
		$badge_items   = ! empty( $b['badges'] ) && is_array( $b['badges'] ) ? array_values( array_filter( array_map( 'trim', $b['badges'] ) ) ) : array();
		if ( empty( $badge_items ) && ! empty( $b['badge'] ) ) {
			$badge_items = array( $b['badge'] );
		}
		$card_class    = 'eyecare-doi-ngu__the' . ( $anh_can_chinh ? ' eyecare-doi-ngu__the--anh-' . $anh_can_chinh : '' );
		echo '<article class="' . esc_attr( $card_class ) . '" data-doctor-card="true">';
		echo '<div class="eyecare-doi-ngu__anh-vung">';
		echo '<figure class="eyecare-doi-ngu__anh-khung">';
		if ( ! empty( $b['anh'] ) && wp_get_attachment_image_src( (int) $b['anh'] ) ) {
			echo wp_get_attachment_image( (int) $b['anh'], 'medium', false, array( 'class' => 'eyecare-doi-ngu__anh', 'alt' => $ten, 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() đã escape thuộc tính.
		} elseif ( ! empty( $b['anh_url'] ) ) {
			echo '<img class="eyecare-doi-ngu__anh" src="' . esc_url( $b['anh_url'] ) . '" alt="' . esc_attr( $ten ) . '" loading="lazy" decoding="async">';
		} else {
			$chu_cai = function_exists( 'mb_substr' ) ? mb_substr( $b['ho_ten'], 0, 1, 'UTF-8' ) : substr( $b['ho_ten'], 0, 1 );
			echo '<span class="eyecare-doi-ngu__anh eyecare-doi-ngu__anh--chu" aria-hidden="true">' . esc_html( $chu_cai ) . '</span>';
		}
		echo '</figure>';
		if ( ! empty( $badge_items ) ) {
			echo '<span class="eyecare-doi-ngu__badge" data-doctor-badge="true" data-doctor-badges="' . esc_attr( wp_json_encode( $badge_items, JSON_UNESCAPED_UNICODE ) ) . '" aria-live="off">' . esc_html( $badge_items[0] ) . '</span>';
		}
		echo '</div>';

		echo '<div class="eyecare-doi-ngu__loi">';
		$profile_url = function_exists( 'eyecare_bac_si_trang_ca_nhan_url' )
			? eyecare_bac_si_trang_ca_nhan_url( $b )
			: ( ! empty( $b['profile'] ) ? $b['profile'] : home_url( '/doi-ngu-bac-si/' ) );
		echo '<h3 class="eyecare-doi-ngu__ten"><a href="' . esc_url( $profile_url ) . '">' . esc_html( $ten ) . '</a></h3>';
		if ( ! empty( $b['chuc_danh'] ) ) {
			echo '<p class="eyecare-doi-ngu__chuc">' . esc_html( $b['chuc_danh'] ) . '</p>';
		}
		echo '<div class="eyecare-doi-ngu__divider" aria-hidden="true"></div>';
		if ( $highlights ) {
			echo '<ul id="' . esc_attr( $highlights_id ) . '" class="eyecare-doi-ngu__highlights" data-doctor-highlights="true">';
			foreach ( $highlights as $highlight_index => $highlight ) {
				echo '<li class="eyecare-doi-ngu__highlight' . ( $highlight_index > 2 ? ' eyecare-doi-ngu__highlight--more' : '' ) . '">';
				echo '<span class="eyecare-doi-ngu__check" aria-hidden="true">✓</span>';
				echo '<span>' . esc_html( $highlight ) . '</span>';
				echo '</li>';
			}
			echo '</ul>';
		}
		if ( $an_con_lai > 0 ) {
			echo '<button class="eyecare-doi-ngu__toggle" type="button" aria-expanded="false" aria-controls="' . esc_attr( $highlights_id ) . '" data-doctor-toggle="true" data-expand-label="Xem thêm +' . esc_attr( (string) $an_con_lai ) . '" data-collapse-label="Thu gọn">';
			echo '<span>Xem thêm +' . esc_html( (string) $an_con_lai ) . '</span><span aria-hidden="true">›</span>';
			echo '</button>';
		}
		$facebook_url = ! empty( $b['facebook'] ) ? $b['facebook'] : eyecare_bac_si_facebook_url( $b );
		if ( $facebook_url ) {
			echo '<p><a href="' . esc_url( $facebook_url ) . '" target="_blank" rel="noopener noreferrer">Facebook của ' . esc_html( $b['ho_ten'] ) . ' ↗</a></p>';
		}
		echo '</div>';
		echo '</article>';
	}

	echo '</div>';
	if ( $co_slider ) {
		echo '<div class="eyecare-doi-ngu__slider-progress" aria-hidden="true"><span data-doctor-progress="true"></span></div>';
		echo '<p class="eyecare-doi-ngu__slider-hint">Kéo ngang hoặc dùng nút mũi tên để xem thêm</p>';
	}
	echo '</div>';

	echo '<div class="eyecare-doi-ngu__stats" aria-label="Thống kê đội ngũ">';
	$stats = array(
		array( 'value' => 'Thăm khám', 'label' => 'Đánh giá tình trạng mắt', 'icon' => 'eye' ),
		array( 'value' => 'Tư vấn', 'label' => 'Giải thích phương án phù hợp', 'icon' => 'clock' ),
		array( 'value' => 'Theo dõi', 'label' => 'Đồng hành cùng người bệnh', 'icon' => 'shield' ),
	);
	foreach ( $stats as $stat ) {
		echo '<div class="eyecare-doi-ngu__stat" data-doctor-stat="true">';
		echo '<span class="eyecare-doi-ngu__stat-icon" aria-hidden="true">';
		if ( 'eye' === $stat['icon'] ) {
			echo '<svg viewBox="0 0 24 24" focusable="false"><path d="M2.4 12s3.4-5.5 9.6-5.5 9.6 5.5 9.6 5.5-3.4 5.5-9.6 5.5S2.4 12 2.4 12Z"/><circle cx="12" cy="12" r="2.8"/><path d="m16.4 17.6 1.5 1.5 3.1-3.5"/></svg>';
		} elseif ( 'clock' === $stat['icon'] ) {
			echo '<svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="8.8"/><path d="M12 7.5V12l3.2 2"/><path d="M8.4 2.9h7.2"/></svg>';
		} else {
			echo '<svg viewBox="0 0 24 24" focusable="false"><path d="M12 2.8 19 5.6v5.2c0 4.8-2.8 8.4-7 10.4-4.2-2-7-5.6-7-10.4V5.6L12 2.8Z"/><path d="m8.7 11.8 2.1 2.1 4.6-5"/></svg>';
		}
		echo '</span>';
		if ( isset( $stat['target'], $stat['suffix'] ) ) {
			echo '<strong data-doctor-count="' . esc_attr( (string) $stat['target'] ) . '" data-doctor-count-suffix="' . esc_attr( $stat['suffix'] ) . '">' . esc_html( $stat['value'] ) . '</strong>';
		} else {
			echo '<strong>' . esc_html( $stat['value'] ) . '</strong>';
		}
		echo '<span class="eyecare-doi-ngu__stat-label">' . esc_html( $stat['label'] ) . '</span></div>';
	}
	echo '</div>';
}

/**
 * Tên đầy đủ dạng hiển thị: "Ths.BS Lê Như Tùng".
 */
function eyecare_bac_si_ten_day_du() {
	$b = eyecare_du_lieu_bac_si();
	return trim( $b['hoc_vi'] . ' ' . $b['ho_ten'] );
}

/**
 * Hai chỉ số nổi bật của người đứng tên nội dung, lấy từ hồ sơ trong Admin.
 *
 * Ưu tiên badge chứa số năm kinh nghiệm và số ca. Nếu badge chưa có thì tìm
 * trong danh sách thành tích. Không ghi cứng số liệu vào template để khi quản
 * trị viên sửa hồ sơ, mọi thẻ E-E-A-T tự cập nhật theo.
 *
 * @return array<int,array{value:string,label:string,type:string}>
 */
function eyecare_bac_si_chi_so_noi_bat() {
	foreach ( eyecare_du_lieu_doi_ngu() as $bac_si ) {
		if ( empty( $bac_si['la_tac_gia'] ) ) {
			continue;
		}

		$nguon = array_merge(
			! empty( $bac_si['badges'] ) && is_array( $bac_si['badges'] ) ? $bac_si['badges'] : array(),
			! empty( $bac_si['highlights'] ) && is_array( $bac_si['highlights'] ) ? $bac_si['highlights'] : array()
		);
		$chi_so = array();

		foreach ( $nguon as $dong ) {
			$dong = trim( wp_strip_all_tags( (string) $dong ) );
			if ( '' === $dong ) {
				continue;
			}

			if ( ! isset( $chi_so['years'] ) && preg_match( '/\b\d+[+.]*\s*năm\b/iu', $dong, $khop ) ) {
				$chi_so['years'] = array(
					'value' => $khop[0],
					'label' => 'Kinh nghiệm nhãn khoa',
					'type'  => 'years',
				);
			}

			if ( ! isset( $chi_so['cases'] ) && preg_match( '/\b[\d.]+\+?\s*ca\b/iu', $dong, $khop ) ) {
				$chi_so['cases'] = array(
					'value' => $khop[0],
					'label' => 'Ca phẫu thuật',
					'type'  => 'cases',
				);
			}
		}

		return array_values( array_intersect_key( $chi_so, array_flip( array( 'years', 'cases' ) ) ) );
	}

	return array();
}

/**
 * In hai chỉ số nổi bật trong thẻ người đứng tên nội dung.
 */
function eyecare_bac_si_chi_so_in() {
	$chi_so = eyecare_bac_si_chi_so_noi_bat();
	if ( empty( $chi_so ) ) {
		return;
	}

	echo '<div class="eyecare-chuyen-khoa__eeat-proof" aria-label="Kinh nghiệm bác sĩ">';
	foreach ( $chi_so as $muc ) {
		echo '<div class="eyecare-chuyen-khoa__eeat-proof-item eyecare-chuyen-khoa__eeat-proof-item--' . esc_attr( $muc['type'] ) . '">';
		echo '<strong>' . esc_html( $muc['value'] ) . '</strong>';
		echo '<span>' . esc_html( $muc['label'] ) . '</span>';
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Ảnh người đứng tên nội dung y khoa.
 *
 * Ưu tiên ảnh đại diện được quản trị viên cập nhật trong mục Đội ngũ bác sĩ;
 * nếu chưa có thì dùng ảnh mẫu của bác sĩ Lê Như Tùng trong child theme.
 *
 * @return string URL ảnh.
 */
function eyecare_bac_si_anh_tac_gia() {
	foreach ( eyecare_du_lieu_doi_ngu() as $bac_si ) {
		if ( empty( $bac_si['la_tac_gia'] ) ) {
			continue;
		}

		if ( ! empty( $bac_si['anh'] ) ) {
			$anh = wp_get_attachment_image_url( (int) $bac_si['anh'], 'thumbnail' );
			if ( $anh ) {
				return $anh;
			}
		}

		if ( ! empty( $bac_si['anh_url'] ) ) {
			return (string) $bac_si['anh_url'];
		}
	}

	return get_stylesheet_directory_uri() . '/assets/doctor-le-nhu-tung.png';
}

/**
 * Trang cá nhân được bệnh viện cung cấp, hoặc hồ sơ nội bộ khi chưa có liên kết.
 */
function eyecare_bac_si_duong_dan( $doctor = 'Lê Như Tùng' ) {
	if ( function_exists( 'eyecare_bac_si_trang_ca_nhan_url' ) ) {
		return eyecare_bac_si_trang_ca_nhan_url( $doctor );
	}
	$b = eyecare_du_lieu_bac_si();
	if ( '' !== $b['trang_ho_so'] ) {
		return $b['trang_ho_so'];
	}
	return home_url( '/doi-ngu-bac-si/' );
}

/** Doctor/person entity only for an explicitly selected published team member. */
function eyecare_schema_bac_si( $doctor_id = 0 ) {
	// Bài công khai đang chờ xác minh không được ngầm gắn bác sĩ vào graph.
	if ( is_singular( 'post' ) && eyecare_bai_cho_duyet_cong_khai( get_queried_object_id() ) ) {
		return null;
	}

	$b = function_exists( 'eyecare_bac_si_du_lieu_theo_id' ) ? eyecare_bac_si_du_lieu_theo_id( $doctor_id ) : array();
	if ( ! $b ) {
		return null;
	}
	$la_bac_si = false !== strpos( strtoupper( $b['hoc_vi'] ), 'BS' );
	$bs = array(
		'@type' => $la_bac_si ? 'Physician' : 'Person',
		'@id'   => $b['profile'],
		'name'  => trim( $b['hoc_vi'] . ' ' . $b['ho_ten'] ),
		'url'   => $b['profile'],
	);
	if ( $la_bac_si ) {
		$bs['medicalSpecialty'] = 'https://schema.org/Ophthalmology';
	}
	if ( ! empty( $b['chuc_danh'] ) ) {
		$bs['jobTitle'] = $b['chuc_danh'];
	}
	if ( ! empty( $b['facebook'] ) ) {
		$bs['sameAs'] = array( $b['facebook'] );
	}
	return $bs;
}

/**
 * Mã ngắn [tac-gia] — khối thông tin bác sĩ đặt ở CUỐI mỗi bài.
 *
 * Dùng trong nội dung bài:  [tac-gia]
 *
 * Cố ý KHÔNG có nút đặt lịch trong khối này: khối tác giả nhằm cho biết ai
 * chịu trách nhiệm nội dung, không nhằm bán dịch vụ. Bài dấu hiệu cảnh báo
 * (cụm C8) tuyệt đối không được có nút đặt lịch — xem KIẾN TRÚC §6.3.
 */
function eyecare_ma_ngan_tac_gia( $thuoc_tinh ) {
	$bai_id    = get_the_ID() ?: get_queried_object_id();
	$doctor_id = function_exists( 'eyecare_bai_bac_si_id' ) ? eyecare_bai_bac_si_id( $bai_id ) : 0;
	$b         = $doctor_id && function_exists( 'eyecare_bac_si_du_lieu_theo_id' ) ? eyecare_bac_si_du_lieu_theo_id( $doctor_id ) : array();
	if ( ! $b ) {
		return '';
	}
	$ten = trim( $b['hoc_vi'] . ' ' . $b['ho_ten'] );

	$h  = '<aside class="eyecare-tac-gia" itemscope itemtype="https://schema.org/Physician">';
	$h .= '<p class="eyecare-tac-gia__nhan">Người đứng tên nội dung</p>';

	$h .= '<p class="eyecare-tac-gia__ten">';
	$personal_url = function_exists( 'eyecare_bac_si_trang_ca_nhan_url' ) ? eyecare_bac_si_trang_ca_nhan_url( $doctor_id ) : $b['profile'];
	$h .= '<a href="' . esc_url( $personal_url ) . '">';
	$h .= '<span itemprop="name">' . esc_html( $ten ) . '</span></a>';
	$h .= '</p>';

	// Chức danh đứng trước chuyên khoa, ngăn bằng dấu chấm giữa. Chức danh
	// đang để rỗng (QĐ-03) nên chỉ in chuyên khoa — không để lại dấu chấm mồ côi.
	$h .= '<p class="eyecare-tac-gia__chuc-danh">';
	if ( '' !== $b['chuc_danh'] ) {
		$h .= esc_html( $b['chuc_danh'] ) . ' · ';
	}
	if ( ! empty( $b['chuyen_mon'] ) ) {
		$h .= esc_html( $b['chuyen_mon'] );
	}
	$h .= '</p>';
	if ( ! empty( $b['facebook'] ) ) {
		$h .= '<p><a href="' . esc_url( $b['facebook'] ) . '" target="_blank" rel="noopener noreferrer">Facebook của bác sĩ ↗</a></p>';
	}
	$reviewer_id = function_exists( 'eyecare_bai_bac_si_duyet_id' ) ? eyecare_bai_bac_si_duyet_id( $bai_id ) : 0;
	$reviewed_at = (string) get_post_meta( $bai_id, '_bvmat_bac_si_duyet', true );
	if ( $reviewer_id && $reviewed_at ) {
		$reviewer = eyecare_bac_si_du_lieu_theo_id( $reviewer_id );
		if ( $reviewer ) {
			$reviewer_url = eyecare_bac_si_trang_ca_nhan_url( $reviewer_id );
			$h .= '<p class="eyecare-tac-gia__duyet">Duyệt chuyên môn: <a href="' . esc_url( $reviewer_url ) . '">'
				. esc_html( trim( $reviewer['hoc_vi'] . ' ' . $reviewer['ho_ten'] ) ) . '</a>';
			$h .= ' · ' . esc_html( mysql2date( 'd/m/Y', $reviewed_at, false ) ) . '</p>';
		}
	}

	// Chỉ in số giấy phép hành nghề khi thật sự có.
	if ( ! empty( $b['so_gphn'] ) ) {
		$h .= '<p class="eyecare-tac-gia__gphn">Số giấy phép hành nghề: '
			. esc_html( $b['so_gphn'] ) . '</p>';
	}

	$h .= '<p class="eyecare-tac-gia__don-vi">'
		. esc_html( eyecare_du_lieu_thuc_the()['ten'] ) . '<br>'
		. esc_html( eyecare_dia_chi_day_du() ) . '</p>';

	// Ngày cập nhật lấy từ chính bài đang hiển thị để người đọc biết độ mới
	// của nội dung y khoa; không ghi cứng ngày trong từng bản thảo.
	$bai_id = get_the_ID();
	if ( ! $bai_id ) {
		$bai_id = get_queried_object_id();
	}
	if ( $bai_id ) {
		$h .= '<p class="eyecare-tac-gia__cap-nhat">Cập nhật nội dung: <time datetime="'
			. esc_attr( get_post_modified_time( 'c', false, $bai_id ) ) . '">'
			. esc_html( get_the_modified_date( 'd/m/Y', $bai_id ) ) . '</time></p>';
	}

	// Miễn trừ trách nhiệm y khoa — bắt buộc với mọi bài nội dung y khoa.
	$h .= '<p class="eyecare-tac-gia__mien-tru">Bài viết cung cấp thông tin '
		. 'tham khảo về bệnh học, <strong>không thay thế việc khám và chỉ định '
		. 'của bác sĩ</strong>. Mỗi người bệnh có tình trạng khác nhau. '
		. 'Xem <a href="' . esc_url( home_url( '/chinh-sach/mien-tru-trach-nhiem-y-khoa/' ) )
		. '">miễn trừ trách nhiệm y khoa</a>.</p>';

	$h .= '</aside>';

	return $h;
}
add_shortcode( 'tac-gia', 'eyecare_ma_ngan_tac_gia' );

/**
 * Mã ngắn [faq] — khối câu hỏi thường gặp, đồng thời sinh FAQPage schema.
 *
 * Cách dùng trong nội dung bài:
 *
 *   [faq]
 *   H: Cận thị có tự khỏi được không?
 *   Đ: Trục nhãn cầu đã dài ra thì không ngắn lại...
 *
 *   H: Câu hỏi thứ hai?
 *   Đ: Trả lời thứ hai...
 *   [/faq]
 *
 * VÌ SAO LÀM THẾ NÀY: viết câu hỏi bằng chữ thường trong nội dung, máy tự
 * sinh schema. Người viết bài không phải chạm vào JSON — chạm vào JSON là
 * chỗ dễ sinh lỗi cú pháp nhất. Đối thủ có kho hỏi–đáp 16.158 từ mà KHÔNG
 * gắn FAQPage schema; đây chính là chỗ vượt được họ.
 */
function eyecare_ma_ngan_faq( $thuoc_tinh, $noi_dung = '' ) {
	$cap = eyecare_tach_faq( $noi_dung );

	if ( empty( $cap ) ) {
		return '';
	}

	// Gom lại để eyecare_in_schema() lấy khi in ra thẻ head.
	global $eyecare_faq_da_gom;
	if ( ! is_array( $eyecare_faq_da_gom ) ) {
		$eyecare_faq_da_gom = array();
	}
	$eyecare_faq_da_gom = array_merge( $eyecare_faq_da_gom, $cap );

	$h = '<div class="eyecare-faq">';
	foreach ( $cap as $c ) {
		$h .= '<div class="eyecare-faq__muc">';
		$h .= '<h3 class="eyecare-faq__hoi">' . esc_html( $c['hoi'] ) . '</h3>';
		$h .= '<div class="eyecare-faq__dap">' . wpautop( wp_kses_post( $c['dap'] ) ) . '</div>';
		$h .= '</div>';
	}
	$h .= '</div>';

	return $h;
}
add_shortcode( 'faq', 'eyecare_ma_ngan_faq' );

/**
 * Tách nội dung mã ngắn [faq] thành các cặp hỏi – đáp.
 *
 * Nhận cả "H:" / "Đ:" và "Hỏi:" / "Đáp:" để người viết bài không phải nhớ
 * đúng một dạng duy nhất.
 */
function eyecare_tach_faq( $noi_dung ) {
	$noi_dung = trim( wp_strip_all_tags( $noi_dung, false ) );
	if ( '' === $noi_dung ) {
		return array();
	}

	$dong = preg_split( '/\R/u', $noi_dung );
	$cap  = array();
	$hien = null;
	$che  = null; // 'hoi' hoặc 'dap'

	foreach ( $dong as $d ) {
		$d = trim( $d );
		if ( '' === $d ) {
			continue;
		}

		if ( preg_match( '/^(?:H|Hỏi)\s*[:.]\s*(.+)$/ui', $d, $m ) ) {
			if ( $hien && '' !== $hien['hoi'] && '' !== $hien['dap'] ) {
				$cap[] = $hien;
			}
			$hien = array( 'hoi' => trim( $m[1] ), 'dap' => '' );
			$che  = 'hoi';
			continue;
		}

		if ( preg_match( '/^(?:Đ|Đáp|D|Dap)\s*[:.]\s*(.+)$/ui', $d, $m ) ) {
			if ( $hien ) {
				$hien['dap'] = trim( $m[1] );
				$che = 'dap';
			}
			continue;
		}

		// Dòng tiếp nối của câu hỏi hoặc câu trả lời nhiều dòng.
		if ( $hien && 'dap' === $che ) {
			$hien['dap'] .= ' ' . $d;
		} elseif ( $hien && 'hoi' === $che ) {
			$hien['hoi'] .= ' ' . $d;
		}
	}

	if ( $hien && '' !== $hien['hoi'] && '' !== $hien['dap'] ) {
		$cap[] = $hien;
	}

	return $cap;
}

/**
 * Gom trước các cặp hỏi–đáp trong nội dung, để in được FAQPage schema.
 *
 * VÌ SAO CẦN HÀM NÀY:
 * wp_head() in ra trước khi nội dung bài được xử lý, nên tại thời điểm in
 * schema thì mã ngắn [faq] chưa chạy và biến gom còn rỗng. Hàm này đọc
 * thẳng nội dung thô của bài, tách riêng phần [faq] bằng biểu thức chính
 * quy và gom lại — không chạy do_shortcode để tránh sinh phụ phí và tránh
 * gọi lồng nhau.
 */
function eyecare_gom_faq_som() {
	global $eyecare_faq_da_gom;

	// Đã gom rồi thì thôi.
	if ( ! empty( $eyecare_faq_da_gom ) ) {
		return;
	}

	if ( ! is_singular() ) {
		return;
	}

	$bai = get_post( get_the_ID() );
	if ( ! $bai || '' === $bai->post_content ) {
		return;
	}

	if ( ! has_shortcode( $bai->post_content, 'faq' ) ) {
		return;
	}

	// Lấy phần bên trong mọi khối [faq]...[/faq].
	if ( ! preg_match_all( '#\[faq\](.*?)\[/faq\]#s', $bai->post_content, $khop ) ) {
		return;
	}

	if ( ! is_array( $eyecare_faq_da_gom ) ) {
		$eyecare_faq_da_gom = array();
	}

	foreach ( $khop[1] as $trong ) {
		$eyecare_faq_da_gom = array_merge(
			$eyecare_faq_da_gom,
			eyecare_tach_faq( $trong )
		);
	}
}

/**
 * Khối FAQPage schema, sinh từ các cặp đã gom được trong nội dung.
 *
 * Trả về null khi bài không có khối [faq] — schema FAQPage chỉ được khai
 * khi nội dung THẬT SỰ là hỏi đáp, theo đúng khuyến nghị của Google.
 */
function eyecare_schema_faq() {
	global $eyecare_faq_da_gom;

	if ( empty( $eyecare_faq_da_gom ) ) {
		return null;
	}

	$muc = array();
	foreach ( $eyecare_faq_da_gom as $c ) {
		$muc[] = array(
			'@type'          => 'Question',
			'name'           => $c['hoi'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $c['dap'],
			),
		);
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink() . '#hoi-dap',
		'mainEntity' => $muc,
	);
}

/**
 * Tránh in hai khối FAQ khi bài viết đã có [faq] hợp lệ của child theme.
 * OBS FAQ Discovery nối thêm một khối và FAQPage riêng sau nội dung; dữ liệu
 * _obs_faq_items vẫn được giữ để biên tập hoặc dùng ở bài không có shortcode.
 */
function eyecare_bo_faq_obs_khi_da_co_faq() {
	if ( is_admin() || ! is_singular( 'post' ) || ! class_exists( 'OBS_Loader' ) ) {
		return;
	}

	$bai = get_post( get_queried_object_id() );
	if ( ! $bai || ! has_shortcode( $bai->post_content, 'faq' ) ||
		! preg_match_all( '#\[faq\](.*?)\[/faq\]#s', $bai->post_content, $khop ) ) {
		return;
	}

	$co_faq_hop_le = false;
	foreach ( $khop[1] as $noi_dung ) {
		if ( eyecare_tach_faq( $noi_dung ) ) {
			$co_faq_hop_le = true;
			break;
		}
	}
	if ( ! $co_faq_hop_le ) {
		return;
	}

	$module = OBS_Loader::get( 'faq_discovery' );
	if ( $module ) {
		remove_filter( 'the_content', array( $module, 'maybe_append_faq' ), 20 );
	}
}
add_action( 'wp', 'eyecare_bo_faq_obs_khi_da_co_faq', 20 );

/**
 * Mã ngắn [doc-them] — khối liên kết nội bộ cuối bài.
 *
 * Cách dùng:
 *   [doc-them]
 *   /kien-thuc/can-thi-la-gi-nguyen-nhan-va-dau-hieu/ | Cận thị là gì
 *   /chuyen-khoa/glocom/ | Glôcôm — cườm nước
 *   [/doc-them]
 *
 * 🔴 Chặn liên kết tới nhánh khoá: mọi đường dẫn chứa /dich-vu/ hoặc
 * /bang-gia/ bị BỎ QUA và ghi cảnh báo. Lý do ở KIẾN TRÚC §6.2 — bài kiến
 * thức trỏ sang trang dịch vụ biến toàn bài thành quảng cáo dịch vụ chưa
 * được cấp phép, và 22 trang đó còn đang ở draft nên là liên kết chết.
 */
function eyecare_ma_ngan_doc_them( $thuoc_tinh, $noi_dung = '' ) {
	$dong = preg_split( '/\R/u', trim( wp_strip_all_tags( $noi_dung, false ) ) );
	$muc  = array();
	$bo   = array();

	foreach ( $dong as $d ) {
		$d = trim( $d );
		if ( '' === $d ) {
			continue;
		}

		$phan = array_map( 'trim', explode( '|', $d, 2 ) );
		$duong_dan = $phan[0];
		$nhan      = $phan[1] ?? '';

		if ( '' === $duong_dan || '/' !== $duong_dan[0] ) {
			continue;
		}

		if ( preg_match( '#/(dich-vu|bang-gia)/#', $duong_dan ) ) {
			$bo[] = $duong_dan;
			continue;
		}

		if ( '' === $nhan ) {
			$nhan = trim( str_replace( array( '/', '-' ), array( '', ' ' ), $duong_dan ) );
		}

		$muc[] = array( 'duong_dan' => $duong_dan, 'nhan' => $nhan );
	}

	if ( $bo && current_user_can( 'edit_posts' ) ) {
		$canh_bao = '<p class="eyecare-canh-bao"><strong>Cảnh báo cho người biên tập:</strong> '
			. count( $bo ) . ' liên kết bị bỏ vì trỏ vào nhánh khoá ('
			. esc_html( implode( ', ', $bo ) ) . '). Nhánh /dich-vu/ và /bang-gia/ '
			. 'chỉ mở sau khi có Giấy phép hoạt động.</p>';
	} else {
		$canh_bao = '';
	}

	if ( empty( $muc ) ) {
		return $canh_bao;
	}

	$h  = $canh_bao;
	$h .= '<nav class="eyecare-doc-them" aria-label="Đọc thêm">';
	$h .= '<h2 class="eyecare-doc-them__tieu-de">Đọc thêm</h2><ul>';
	foreach ( $muc as $m ) {
		$h .= '<li><a href="' . esc_url( home_url( $m['duong_dan'] ) ) . '">'
			. esc_html( $m['nhan'] ) . '</a></li>';
	}
	$h .= '</ul></nav>';

	return $h;
}
add_shortcode( 'doc-them', 'eyecare_ma_ngan_doc_them' );

/** Bài đã xuất bản nhưng chưa xác minh người viết và bác sĩ duyệt. */
function eyecare_bai_cho_duyet_cong_khai( $id ) {
	return $id && 'post' === get_post_type( $id ) && 'publish' === get_post_status( $id )
		&& 'pending-author-and-medical-review' === get_post_meta( $id, '_eyecare_content_review_status', true );
}

/**
 * Khối schema cho bài viết y khoa. Chỉ gắn người viết/người duyệt khi quản
 * trị viên đã chọn đúng hồ sơ bác sĩ cho từng bài.
 */
function eyecare_schema_bai_viet() {
	if ( ! is_singular( 'post' ) ) {
		return null;
	}

	$id = get_the_ID();
	$cho_duyet = 'pending-author-and-medical-review' === get_post_meta( $id, '_eyecare_content_review_status', true );
	// Bản xem trước chưa xuất bản chưa cần Article schema.
	if ( $cho_duyet && ! eyecare_bai_cho_duyet_cong_khai( $id ) ) {
		return null;
	}
	$goc = home_url( '/' );

	$bai = array(
		'@type'            => array( 'MedicalWebPage', 'Article' ),
		'@id'              => get_permalink( $id ) . '#bai-viet',
		'headline'         => get_the_title( $id ),
		'url'              => get_permalink( $id ),
		'inLanguage'       => 'vi-VN',
		'datePublished'    => $cho_duyet ? get_post_time( DATE_W3C, false, $id ) : get_the_date( 'c', $id ),
		'dateModified'     => $cho_duyet ? get_post_modified_time( DATE_W3C, false, $id ) : get_the_modified_date( 'c', $id ),
		'publisher'        => array( '@id' => $goc . '#to-chuc' ),
		'isPartOf'         => array( '@id' => $goc . '#website' ),
		'medicalAudience'  => array( '@type' => 'Patient' ),
	);
	$author_id = ! $cho_duyet && function_exists( 'eyecare_bai_bac_si_id' ) ? eyecare_bai_bac_si_id( $id ) : 0;
	if ( $author_id ) {
		$bai['author'] = array( '@id' => eyecare_bac_si_ho_so_url( $author_id ) );
	}

	$mo_ta = get_the_excerpt( $id );
	if ( $mo_ta ) {
		$bai['description'] = wp_strip_all_tags( $mo_ta );
	}

	$anh = get_the_post_thumbnail_url( $id, 'full' );
	if ( $anh ) {
		$bai['image'] = $anh;
	}

	// Ngày bác sĩ duyệt — chỉ khai khi thật sự đã duyệt.
	$ngay_duyet = get_post_meta( $id, '_bvmat_bac_si_duyet', true );
	$reviewer_id = ! $cho_duyet && function_exists( 'eyecare_bai_bac_si_duyet_id' ) ? eyecare_bai_bac_si_duyet_id( $id ) : 0;
	if ( $ngay_duyet && $reviewer_id ) {
		$bai['lastReviewed'] = $ngay_duyet;
		$bai['reviewedBy'] = array( '@id' => eyecare_bac_si_ho_so_url( $reviewer_id ) );
	}

	// Bệnh mà bài nói về — điền qua meta _bvmat_benh khi nhập bài.
	$benh = get_post_meta( $id, '_bvmat_benh', true );
	if ( $benh ) {
		$bai['about'] = array(
			'@type' => 'MedicalCondition',
			'name'  => $benh,
		);
	}

	return $bai;
}

/**
 * Schema cho page nội dung y khoa dài.
 *
 * Chỉ page có meta _bvmat_noi_dung_y_khoa=1 mới được khai MedicalWebPage.
 * Không khai reviewedBy/lastReviewed cho tới khi có meta _bvmat_bac_si_duyet.
 */
function eyecare_schema_trang_y_khoa() {
	if ( ! is_page() ) {
		return null;
	}

	$id = get_queried_object_id();
	if ( ! $id || '1' !== get_post_meta( $id, '_bvmat_noi_dung_y_khoa', true ) ) {
		return null;
	}

	$goc = home_url( '/' );
	$url = get_permalink( $id );

	$trang = array(
		'@type'            => 'MedicalWebPage',
		'@id'              => $url . '#noi-dung-y-khoa',
		'url'              => $url,
		'name'             => get_the_title( $id ),
		'headline'         => get_the_title( $id ),
		'inLanguage'       => 'vi-VN',
		'datePublished'    => get_the_date( 'c', $id ),
		'dateModified'     => get_the_modified_date( 'c', $id ),
		'publisher'        => array( '@id' => $goc . '#to-chuc' ),
		'isPartOf'         => array( '@id' => $goc . '#website' ),
		'mainEntityOfPage' => array( '@id' => $url ),
		'medicalAudience'  => array( '@type' => 'Patient' ),
	);
	$author_id = function_exists( 'eyecare_bai_bac_si_id' ) ? eyecare_bai_bac_si_id( $id ) : 0;
	if ( $author_id ) {
		$trang['author'] = array( '@id' => eyecare_bac_si_ho_so_url( $author_id ) );
	}

	$mo_ta = get_post_meta( $id, '_bvmat_seo_description', true );
	if ( ! $mo_ta ) {
		$mo_ta = get_the_excerpt( $id );
	}
	if ( $mo_ta ) {
		$trang['description'] = wp_strip_all_tags( $mo_ta );
	}

	$tu_khoa = array();
	foreach ( array( '_bvmat_tu_khoa_chinh', '_bvmat_tu_khoa_phu', '_bvmat_tu_khoa_semantic', '_bvmat_tu_khoa_dai' ) as $khoa ) {
		$gia_tri = get_post_meta( $id, $khoa, true );
		if ( $gia_tri ) {
			$tu_khoa[] = trim( wp_strip_all_tags( $gia_tri ) );
		}
	}
	if ( $tu_khoa ) {
		$trang['keywords'] = implode( ', ', $tu_khoa );
	}

	$chu_de = get_post_meta( $id, '_bvmat_chu_de_y_khoa', true );
	if ( $chu_de ) {
		$trang['about'] = array(
			'@type' => 'Thing',
			'name'  => wp_strip_all_tags( $chu_de ),
		);
	}

	$anh = get_the_post_thumbnail_url( $id, 'full' );
	if ( $anh ) {
		$trang['primaryImageOfPage'] = array(
			'@type' => 'ImageObject',
			'url'   => $anh,
		);
	}

	$ngay_duyet = get_post_meta( $id, '_bvmat_bac_si_duyet', true );
	$reviewer_id = function_exists( 'eyecare_bai_bac_si_duyet_id' ) ? eyecare_bai_bac_si_duyet_id( $id ) : 0;
	if ( $ngay_duyet && $reviewer_id ) {
		$trang['reviewedBy']   = array( '@id' => eyecare_bac_si_ho_so_url( $reviewer_id ) );
		$trang['lastReviewed'] = $ngay_duyet;
	}

	return $trang;
}

/**
 * Dùng SEO title đã được quản trị cho các page nội dung y khoa dài.
 *
 * @param string $tieu_de Tiêu đề WordPress mặc định.
 * @return string
 */
function eyecare_tieu_de_seo_trang_y_khoa( $tieu_de ) {
	if ( ! is_page() ) {
		return $tieu_de;
	}

	$id = get_queried_object_id();
	if ( ! $id || '1' !== get_post_meta( $id, '_bvmat_noi_dung_y_khoa', true ) ) {
		return $tieu_de;
	}

	$tieu_de_seo = trim( wp_strip_all_tags( get_post_meta( $id, '_bvmat_seo_title', true ) ) );

	return '' !== $tieu_de_seo ? $tieu_de_seo : $tieu_de;
}
add_filter( 'pre_get_document_title', 'eyecare_tieu_de_seo_trang_y_khoa', 20 );

/**
 * Meta description chuẩn cho page y khoa khi plugin hiện tại chưa xuất thẻ này.
 */
function eyecare_meta_description_trang_y_khoa() {
	if ( ! is_page() ) {
		return;
	}

	$id = get_queried_object_id();
	if ( ! $id || '1' !== get_post_meta( $id, '_bvmat_noi_dung_y_khoa', true ) ) {
		return;
	}

	$mo_ta = get_post_meta( $id, '_bvmat_seo_description', true );
	if ( ! $mo_ta ) {
		$mo_ta = get_the_excerpt( $id );
	}

	$mo_ta = trim( wp_strip_all_tags( $mo_ta ) );
	if ( '' !== $mo_ta ) {
		echo '<meta name="description" content="' . esc_attr( $mo_ta ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'eyecare_meta_description_trang_y_khoa', 4 );

/**
 * Đường dẫn phân cấp cho BÀI VIẾT.
 *
 * eyecare_schema_duong_dan() trong schema-y-te.php chỉ xử lý is_page().
 * Bài viết nằm ở /kien-thuc/{slug}/ nên cần breadcrumb riêng: Trang chủ →
 * Kiến thức nhãn khoa → tên bài.
 */
function eyecare_schema_duong_dan_bai_viet() {
	if ( ! is_singular( 'post' ) ) {
		return null;
	}

	$id  = get_the_ID();
	$goc = home_url( '/' );

	$muc = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'Trang chủ',
			'item'     => $goc,
		),
	);

	$vi_tri = 2;

	// Chuyên mục chính của bài — /kien-thuc/ hoặc /tin-tuc/.
	$dm = get_the_category( $id );
	if ( ! empty( $dm ) ) {
		$muc[] = array(
			'@type'    => 'ListItem',
			'position' => $vi_tri++,
			'name'     => $dm[0]->name,
			'item'     => get_category_link( $dm[0]->term_id ),
		);
	}

	$muc[] = array(
		'@type'    => 'ListItem',
		'position' => $vi_tri,
		'name'     => get_the_title( $id ),
	);

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => get_permalink( $id ) . '#duong-dan',
		'itemListElement' => $muc,
	);
}

/**
 * Không hiển thị khối plugin tự nhận bác sĩ là tác giả khi bài chưa duyệt.
 * Với bài đã xuất bản, thay cả Open Graph của plugin bằng dữ liệu địa phương.
 */
function eyecare_an_ghi_cong_ban_nhap_chua_duyet() {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return;
	}

	$id = (int) get_queried_object_id();
	if ( 'pending-author-and-medical-review' !== get_post_meta( $id, '_eyecare_content_review_status', true ) || ! class_exists( 'OBS_Loader' ) ) {
		return;
	}

	$aicb = OBS_Loader::get( 'ai_citation_bridge' );
	if ( $aicb ) {
		remove_filter( 'the_content', array( $aicb, 'maybe_inject' ), 8 );
		remove_filter( 'the_content', array( $aicb, 'maybe_inject_eeat' ), 11 );
	}

	$author_bio = OBS_Loader::get( 'author_bio' );
	if ( $author_bio ) {
		remove_filter( 'the_content', array( $author_bio, 'append_box' ), 99 );
		remove_filter( 'the_content', array( $author_bio, 'prepend_box' ), 99 );
		remove_filter( 'the_content', array( $author_bio, 'both_box' ), 99 );
	}

	// Plugin sẽ gán user quản trị làm author của Article dù post_author=0.
	// Bản nháp không cần Article; bài công khai dùng Article của child theme.
	$schema = OBS_Loader::get( 'schema' );
	if ( $schema ) {
		remove_action( 'wp_head', array( $schema, 'render' ), 10 );
	}
	if ( eyecare_bai_cho_duyet_cong_khai( $id ) ) {
		$opengraph = OBS_Loader::get( 'opengraph' );
		if ( $opengraph ) {
			remove_action( 'wp_head', array( $opengraph, 'render' ), 5 );
		}
	}

	// Template single.php đã có breadcrumb và thời gian đọc riêng.
	$breadcrumb = OBS_Loader::get( 'breadcrumb' );
	if ( $breadcrumb ) {
		remove_filter( 'the_content', array( $breadcrumb, 'auto_insert' ), 5 );
	}
	$reading_time = OBS_Loader::get( 'reading_time' );
	if ( $reading_time ) {
		remove_filter( 'the_content', array( $reading_time, 'prepend_to_content' ), 4 );
		remove_filter( 'the_content', array( $reading_time, 'append_to_content' ), 5 );
	}

	remove_shortcode( 'obs_eeat_box' );
	remove_shortcode( 'obs_author_bio' );
}
add_action( 'wp', 'eyecare_an_ghi_cong_ban_nhap_chua_duyet', 20 );

/** Open Graph và Twitter cho bài công khai còn chờ xác minh người viết. */
function eyecare_meta_bai_cho_duyet_cong_khai() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$id = (int) get_queried_object_id();
	if ( ! eyecare_bai_cho_duyet_cong_khai( $id ) ) {
		return;
	}

	$tieu_de = get_the_title( $id );
	$mo_ta = trim( wp_strip_all_tags( get_the_excerpt( $id ) ) );
	$anh_dai_dien = get_the_post_thumbnail_url( $id, 'full' );
	$anh = $anh_dai_dien ?: get_site_icon_url( 512 );
	$og = array(
		'og:type'                => 'article',
		'og:site_name'           => get_bloginfo( 'name' ),
		'og:locale'              => 'vi_VN',
		'og:title'               => $tieu_de,
		'og:url'                 => get_permalink( $id ),
		'article:published_time' => get_post_time( DATE_W3C, false, $id ),
		'article:modified_time'  => get_post_modified_time( DATE_W3C, false, $id ),
	);
	if ( $mo_ta ) {
		$og['og:description'] = $mo_ta;
	}
	if ( $anh ) {
		$og['og:image'] = $anh;
	}
	foreach ( $og as $thuoc_tinh => $gia_tri ) {
		echo '<meta property="' . esc_attr( $thuoc_tinh ) . '" content="' . esc_attr( $gia_tri ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ( $anh_dai_dien ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $tieu_de ) . '">' . "\n";
	if ( $mo_ta ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $mo_ta ) . '">' . "\n";
	}
	if ( $anh ) {
		echo '<meta name="twitter:image" content="' . esc_url( $anh ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'eyecare_meta_bai_cho_duyet_cong_khai', 4 );
