<?php
/**
 * MENU CHÍNH — dữ liệu và hàm in, dùng bởi header.php.
 *
 * TỆP NÀY TỪNG LÀM GÌ KHÁC:
 * Trước đây theme mượn header của Flatsome, nên logo và menu phải chèn vào bộ
 * render của Flatsome bằng filter (theme_mod_site_logo, has_nav_menu,
 * pre_wp_nav_menu). Cách đó có lỗi không sửa được từ ngoài: menu 6 mục tràn
 * xuống dòng thứ hai, mục "Liên hệ" rớt xuống đè ô tìm kiếm.
 *
 * Nay theme có header.php riêng nên tự in trực tiếp. Toàn bộ filter đã được gỡ:
 * giữ lại chúng thì mỗi mục menu bị sinh hai lần ở hai bộ render khác nhau.
 * Logo cũng chuyển sang in thẳng bằng thẻ <img> trong header.php.
 *
 * VÌ SAO KHÔNG ĐỌC MENU TỪ CƠ SỞ DỮ LIỆU:
 * Bản localhost này đồng bộ định kỳ từ nguồn khác — bản ghi menu trong
 * wp_posts/wp_terms sẽ bị đè mất. Dựng từ code thì cấu hình sống sót, và
 * kiểm soát được đích đến: chỉ trỏ tới trang ĐÃ PUBLISH.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Danh sách submenu dịch vụ lấy trực tiếp từ các trang con đã công bố.
 *
 * Khi quản trị viên thêm, đổi tên, sắp xếp hoặc gỡ công khai một trang con
 * của /dich-vu/, menu tự cập nhật theo mà không phải sửa lại tệp PHP.
 *
 * @return array[] Mảng các mục con: duong_dan, nhan.
 */
function eyecare_menu_dich_vu_con() {
	$ra = array(
		array( 'duong_dan' => '/dich-vu/', 'nhan' => 'Tất cả dịch vụ' ),
	);

	if ( function_exists( 'eyecare_trang_con_hub' ) ) {
		foreach ( eyecare_trang_con_hub( 'dich-vu', 30 ) as $muc ) {
			if ( empty( $muc['trang'] ) || ! $muc['trang'] instanceof WP_Post ) {
				continue;
			}

			$duong_dan = get_permalink( $muc['trang']->ID );
			if ( '' === $duong_dan ) {
				continue;
			}

			$ra[] = array(
				'duong_dan' => $duong_dan,
				'nhan'      => get_the_title( $muc['trang']->ID ),
			);
		}
	}

	$bang_gia = get_page_by_path( 'bang-gia', OBJECT, 'page' );
	if ( $bang_gia instanceof WP_Post && 'publish' === $bang_gia->post_status ) {
		$ra[] = array(
			'duong_dan' => get_permalink( $bang_gia->ID ),
			'nhan'      => 'Bảng giá dịch vụ',
		);
	}

	return $ra;
}

/**
 * Chuẩn hóa đích menu thành URL tuyệt đối.
 *
 * Mục tĩnh dùng đường dẫn tương đối; mục dịch vụ lấy từ get_permalink() đã là
 * URL tuyệt đối. Không đưa permalink tuyệt đối qua home_url() vì site cài trong
 * thư mục con sẽ bị lặp tiền tố /benhvienmathanoibacninh/ hai lần.
 *
 * @param string $duong_dan Đường dẫn tương đối hoặc URL tuyệt đối.
 * @return string
 */
function eyecare_menu_url( $duong_dan ) {
	$may_chu = wp_parse_url( $duong_dan, PHP_URL_HOST );

	if ( is_string( $may_chu ) && '' !== $may_chu ) {
		return $duong_dan;
	}

	return home_url( '/' . ltrim( $duong_dan, '/' ) );
}

/**
 * Danh sách mục menu chính. Mỗi mục: đường dẫn tương đối + nhãn.
 *
 * Chỉ gồm 6 đích đã kiểm là publish và có nội dung/vai trò rõ ràng:
 * giới thiệu, chuyên khoa, kiến thức, đội ngũ, hỏi đáp, liên hệ.
 *
 * 🔴 KHÔNG có "Đặt lịch khám": bệnh viện chưa có Giấy phép hoạt động (B-03),
 * chưa được quảng cáo dịch vụ khám chữa bệnh. Cùng một cửa đã giữ nút đặt lịch
 * khỏi trang chủ. Trang /dat-lich-kham/ có tồn tại nhưng không đưa vào điều
 * hướng chính cho tới khi có giấy phép.
 *
 * @return array[] Mảng các mảng: duong_dan, nhan.
 */
function eyecare_menu_muc() {
	return array(
		array( 'duong_dan' => '/gioi-thieu/',     'nhan' => 'Giới thiệu' ),
		array( 'duong_dan' => '/chuyen-khoa/',    'nhan' => 'Chuyên khoa' ),
		array(
			'duong_dan' => '/dich-vu/',
			'nhan'      => 'Dịch vụ',
			'con'       => eyecare_menu_dich_vu_con(),
		),
		array( 'duong_dan' => '/kien-thuc/',      'nhan' => 'Kiến thức nhãn khoa' ),
		array( 'duong_dan' => '/doi-ngu-bac-si/', 'nhan' => 'Đội ngũ bác sĩ' ),
		array( 'duong_dan' => '/hoi-dap/',        'nhan' => 'Hỏi đáp' ),
		array( 'duong_dan' => '/lien-he/',        'nhan' => 'Liên hệ' ),
	);
}

/**
 * In menu chính ra màn hình. Gọi trong header.php.
 *
 * Mục đang xem được đánh dấu bằng aria-current="page" — vừa là thông tin cho
 * trình đọc màn hình, vừa là chỗ CSS bám vào để tô màu và kẻ vạch, nên không
 * cần thêm lớp "active" riêng.
 *
 * @return void
 */
function eyecare_menu_chinh_in() {

	/* Đường dẫn đang xem, để so với từng mục. wp_parse_url tách phần đường dẫn
	   khỏi tham số truy vấn; trailingslashit để '/hoi-dap' và '/hoi-dap/' được
	   coi là một. */
	$duong_dan_hien_tai = '';

	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$duong_dan_hien_tai = trailingslashit(
			(string) wp_parse_url(
				esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
				PHP_URL_PATH
			)
		);
	}

	echo '<nav class="eyecare-menu" id="menu-chinh" aria-label="Menu chính">';
	echo '<ul class="eyecare-menu__ds">';

	foreach ( eyecare_menu_muc() as $muc ) {

		/* So sánh phần đuôi: site chạy trong thư mục con
		   (/benhvienmathanoibacninh/) nên đường dẫn thật có tiền tố, không
		   khớp thẳng với '/hoi-dap/' được. */
		$duong_dan_muc = trailingslashit(
			(string) wp_parse_url( eyecare_menu_url( $muc['duong_dan'] ), PHP_URL_PATH )
		);
		$dang_xem = ( '' !== $duong_dan_hien_tai )
			&& str_starts_with( $duong_dan_hien_tai, $duong_dan_muc );
		$co_con = ! empty( $muc['con'] ) && is_array( $muc['con'] );

		$lop_muc = $co_con ? ' class="eyecare-menu__muc--co-con"' : '';
		$menu_con_id = $co_con ? wp_unique_id( 'eyecare-menu-con-' ) : '';
		echo '<li' . $lop_muc . '>';
		printf(
			'<a class="eyecare-menu__lk" href="%1$s"%3$s>%2$s</a>',
			esc_url( eyecare_menu_url( $muc['duong_dan'] ) ),
			esc_html( $muc['nhan'] ),
			$dang_xem ? ' aria-current="page"' : ''
		);

		if ( $co_con ) {
			printf(
				'<button class="eyecare-menu__toggle" type="button" aria-expanded="false" aria-controls="%1$s" aria-label="Mở submenu %2$s"><span class="eyecare-menu__mui-ten" aria-hidden="true">⌄</span></button>',
				esc_attr( $menu_con_id ),
				esc_attr( $muc['nhan'] )
			);
			echo '<ul id="' . esc_attr( $menu_con_id ) . '" class="eyecare-menu__con" aria-label="' . esc_attr( $muc['nhan'] ) . '" hidden>';
			foreach ( $muc['con'] as $muc_con ) {
				$duong_dan_con = trailingslashit(
					(string) wp_parse_url( eyecare_menu_url( $muc_con['duong_dan'] ), PHP_URL_PATH )
				);
				$con_dang_xem = ( '' !== $duong_dan_hien_tai )
					&& $duong_dan_hien_tai === $duong_dan_con;
				echo '<li>';
				printf(
					'<a href="%1$s"%3$s>%2$s</a>',
					esc_url( eyecare_menu_url( $muc_con['duong_dan'] ) ),
					esc_html( $muc_con['nhan'] ),
					$con_dang_xem ? ' aria-current="page"' : ''
				);
				echo '</li>';
			}
			echo '</ul>';
		}

		echo '</li>';
	}

	echo '</ul>';
	echo '</nav>';
}

/**
 * ĐƯỜNG DẪN PHÂN CẤP (breadcrumb) — bản tự dựng, thay flatsome_breadcrumb().
 *
 * VÌ SAO TỰ DỰNG:
 * Theme đã cắt phụ thuộc Flatsome nên hàm flatsome_breadcrumb() không còn.
 * Bốn khuôn (single.php, page.php, page-lien-he.php, page-doi-ngu-bac-si.php)
 * gọi tới đường dẫn phân cấp; gom về một hàm ở đây để cả bốn luôn khớp nhau
 * và khớp với breadcrumb schema đã khai trong inc/tac-gia-bac-si.php và
 * inc/schema-y-te.php (cùng thứ tự: Trang chủ › trang cha › trang hiện tại).
 *
 * CHỈ IN LIÊN KẾT NỘI BỘ, KHÔNG đọc dữ liệu ngoài — an toàn với đồng bộ DB.
 * Trang chủ thì không in gì (không tự trỏ về mình).
 *
 * @return void
 */
function eyecare_duong_dan_in() {

	if ( is_front_page() || is_home() ) {
		return;
	}

	$muc = array();

	// Mắt xích đầu luôn là Trang chủ.
	$muc[] = array(
		'nhan'      => 'Trang chủ',
		'duong_dan' => home_url( '/' ),
	);

	if ( is_singular( 'post' ) ) {

		/* Bài viết: Trang chủ › chuyên mục chính (nếu có) › tên bài.
		   Dùng chuyên mục đầu tiên để khớp với eyecare_schema_duong_dan_bai_viet(). */
		$dm = get_the_category();
		if ( ! empty( $dm ) ) {
			$muc[] = array(
				'nhan'      => $dm[0]->name,
				'duong_dan' => get_category_link( $dm[0]->term_id ),
			);
		}

		$muc[] = array( 'nhan' => get_the_title(), 'duong_dan' => '' );

	} elseif ( is_page() ) {

		/* Trang tĩnh: dựng chuỗi trang cha › ... › trang hiện tại.
		   get_post_ancestors trả về từ cha gần nhất lên gốc, nên đảo lại. */
		$id_hien = get_the_ID();
		$to_tien = array_reverse( get_post_ancestors( $id_hien ) );

		foreach ( $to_tien as $id_cha ) {
			/* Không tạo liên kết chết tới trang cha còn ở trạng thái draft.
			   Vẫn giữ nhãn trong breadcrumb để người đọc biết vị trí hiện tại. */
			$link_cha = ( 'publish' === get_post_status( $id_cha ) ) ? get_permalink( $id_cha ) : '';
			$muc[] = array(
				'nhan'      => get_the_title( $id_cha ),
				'duong_dan' => $link_cha,
			);
		}

		$muc[] = array( 'nhan' => get_the_title( $id_hien ), 'duong_dan' => '' );

	} elseif ( is_category() || is_tax() || is_tag() ) {

		$muc[] = array( 'nhan' => single_term_title( '', false ), 'duong_dan' => '' );

	} elseif ( is_search() ) {

		$muc[] = array(
			/* translators: %s: từ khoá tìm kiếm */
			'nhan'      => sprintf( __( 'Kết quả tìm cho “%s”', 'eyecare-child' ), get_search_query() ),
			'duong_dan' => '',
		);

	} elseif ( is_404() ) {

		$muc[] = array( 'nhan' => __( 'Không tìm thấy trang', 'eyecare-child' ), 'duong_dan' => '' );

	} elseif ( is_archive() ) {

		$muc[] = array( 'nhan' => get_the_archive_title(), 'duong_dan' => '' );

	} else {

		// Loại trang khác chưa liệt kê: chỉ có Trang chủ thì không in gì thêm
		// (một mắt xích đơn độc không phải đường dẫn). Dừng an toàn.
		return;
	}

	echo '<nav class="eyecare-duong-dan" aria-label="Đường dẫn">';
	echo '<ol class="eyecare-duong-dan__ds">';

	$tong = count( $muc );
	foreach ( $muc as $i => $m ) {

		$la_cuoi = ( $i === $tong - 1 );

		echo '<li class="eyecare-duong-dan__muc">';

		if ( ! $la_cuoi && '' !== $m['duong_dan'] ) {
			printf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $m['duong_dan'] ),
				esc_html( $m['nhan'] )
			);
		} else {
			// Mắt xích cuối là trang đang xem — không phải liên kết.
			printf(
				'<span aria-current="page">%s</span>',
				esc_html( $m['nhan'] )
			);
		}

		echo '</li>';
	}

	echo '</ol>';
	echo '</nav>';
}
