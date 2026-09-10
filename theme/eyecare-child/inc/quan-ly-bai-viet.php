<?php
/**
 * Cấu trúc thư mục cho thư viện bài viết kiến thức nhãn khoa.
 *
 * Dùng chuyên mục WordPress thật làm “thư mục”, để cùng một cấu trúc phục vụ
 * cả màn hình quản trị, trang /kien-thuc/ và liên kết nội bộ.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tạo mô tả ngắn từ dữ liệu gốc của bài viết.
 *
 * Không dùng get_the_excerpt() ở trang danh sách vì WordPress có thể chạy
 * the_content để tạo excerpt tự động. Khi đó breadcrumb, thời gian đọc hoặc
 * các khối do plugin chèn sẽ bị kéo vào thẻ bài viết.
 *
 * @param int     $post_id ID bài viết.
 * @param int     $so_tu   Số từ tối đa.
 * @param string  $them     Chuỗi kết thúc khi nội dung bị rút gọn.
 * @return string
 */
function eyecare_tom_tat_bai_viet_sach( $post_id, $so_tu = 25, $them = '…' ) {
	$post_id = absint( $post_id );
	$so_tu   = max( 1, absint( $so_tu ) );

	if ( ! $post_id ) {
		return '';
	}

	$tom_tat = (string) get_post_field( 'post_excerpt', $post_id, 'raw' );
	if ( '' === trim( $tom_tat ) ) {
		$tom_tat = (string) get_post_field( 'post_content', $post_id, 'raw' );
	}

	$tom_tat = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $tom_tat ) ) ) );

	return '' === $tom_tat ? '' : wp_trim_words( $tom_tat, $so_tu, $them );
}

/**
 * Trả về đường dẫn gọn cho một chuyên mục.
 *
 * Ví dụ: /category/kien-thuc/can-thi-tre-em/ trở thành
 * /kien-thuc/can-thi-tre-em/. Giữ cây chuyên mục giúp URL vẫn mô tả đúng
 * ngữ cảnh và không đụng với các bài viết có cùng slug ở nhánh khác.
 *
 * @param WP_Term $term Chuyên mục cần lấy đường dẫn.
 * @return string
 */
function eyecare_duong_dan_chuyen_muc_gon( $term ) {
	if ( ! $term instanceof WP_Term || 'category' !== $term->taxonomy ) {
		return '';
	}

	$ancestors = array_reverse( get_ancestors( $term->term_id, 'category' ) );
	$slugs     = array();

	foreach ( $ancestors as $ancestor_id ) {
		$ancestor = get_term( $ancestor_id, 'category' );
		if ( $ancestor instanceof WP_Term && ! is_wp_error( $ancestor ) ) {
			$slugs[] = $ancestor->slug;
		}
	}

	$slugs[] = $term->slug;
	$path    = implode( '/', array_filter( $slugs ) );

	return '' === $path ? '' : home_url( trailingslashit( $path ) );
}

/** Đổi link chuyên mục trong menu, breadcrumb, sitemap và các template. */
function eyecare_loc_chuyen_muc_gon( $link, $term, $taxonomy ) {
	if ( 'category' !== $taxonomy ) {
		return $link;
	}

	$term_link = eyecare_duong_dan_chuyen_muc_gon( $term );
	return '' !== $term_link ? $term_link : $link;
}
add_filter( 'term_link', 'eyecare_loc_chuyen_muc_gon', 10, 3 );

/**
 * Đăng ký rewrite cho từng chuyên mục thay vì dùng một regex bắt mọi slug.
 *
 * Đăng ký theo term_id làm URL bài viết không bị bắt nhầm nếu sau này admin
 * tạo một bài có slug trùng với slug chuyên mục. Nếu đường dẫn đã là page
 * thật (ví dụ /kien-thuc/ hoặc /tin-tuc/) thì để page giữ quyền ưu tiên.
 */
function eyecare_dang_ky_rewrite_chuyen_muc_gon() {
	$terms = get_categories(
		array(
			'hide_empty' => false,
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		)
	);

	foreach ( $terms as $term ) {
		$term_path = eyecare_duong_dan_chuyen_muc_gon( $term );
		if ( '' === $term_path ) {
			continue;
		}

		$path      = trim( (string) wp_parse_url( $term_path, PHP_URL_PATH ), '/' );
		$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( '' !== $home_path && ( $path === $home_path || 0 === strpos( $path, $home_path . '/' ) ) ) {
			$path = ltrim( substr( $path, strlen( $home_path ) ), '/' );
		}

		if ( '' === $path || get_page_by_path( $path ) instanceof WP_Post ) {
			continue;
		}

		$regex = '^' . preg_quote( $path, '/' );
		add_rewrite_rule( $regex . '/page/([0-9]{1,})/?$', 'index.php?cat=' . absint( $term->term_id ) . '&paged=$matches[1]', 'top' );
		add_rewrite_rule( $regex . '/?$', 'index.php?cat=' . absint( $term->term_id ), 'top' );
	}
}
add_action( 'init', 'eyecare_dang_ky_rewrite_chuyen_muc_gon', 5 );

/** Chuyển URL /category/... cũ sang cấu trúc gọn bằng redirect 301. */
function eyecare_chuyen_huong_chuyen_muc_cu( $redirect_url, $requested_url ) {
	if ( ! is_category() ) {
		return $redirect_url;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return $redirect_url;
	}

	$term_link = eyecare_duong_dan_chuyen_muc_gon( $term );
	if ( '' === $term_link ) {
		return $redirect_url;
	}

	$paged = absint( get_query_var( 'paged' ) );
	if ( $paged > 1 ) {
		$term_link = trailingslashit( $term_link ) . 'page/' . $paged . '/';
	}

	return $term_link;
}
add_filter( 'redirect_canonical', 'eyecare_chuyen_huong_chuyen_muc_cu', 10, 2 );

/**
 * Bảo đảm URL /category/... cũ luôn chuyển 301 kể cả khi plugin SEO tắt
 * redirect_canonical của WordPress.
 */
function eyecare_chuyen_huong_chuyen_muc_cu_truc_tiep() {
	if ( is_admin() || ! is_category() ) {
		return;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] )
		? (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH )
		: '';

	if ( ! preg_match( '#/category(?:/|$)#', $request_path ) ) {
		return;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$target = eyecare_duong_dan_chuyen_muc_gon( $term );
	$paged  = absint( get_query_var( 'paged' ) );
	if ( $paged > 1 ) {
		$target = trailingslashit( $target ) . 'page/' . $paged . '/';
	}

	if ( '' !== $target ) {
		wp_safe_redirect( $target, 301, 'Eyecare Clean Category URLs' );
		exit;
	}
}
add_action( 'template_redirect', 'eyecare_chuyen_huong_chuyen_muc_cu_truc_tiep', 1 );

/**
 * Flush rewrite một lần sau khi triển khai URL gọn.
 * Không xóa rewrite_rules; flush_rewrite_rules() là API WordPress an toàn.
 */
function eyecare_flush_rewrite_chuyen_muc_gon() {
	$version = '2026-08-30-clean-category-v2';
	if ( $version !== get_option( '_eyecare_clean_category_urls' ) ) {
		flush_rewrite_rules( false );
		update_option( '_eyecare_clean_category_urls', $version, false );
	}
}
add_action( 'init', 'eyecare_flush_rewrite_chuyen_muc_gon', 20 );

/**
 * Danh sách 10 cụm nội dung theo ma trận 100 bài của dự án.
 *
 * @return array<string,array<string,mixed>>
 */
function eyecare_thu_muc_bai_viet_cau_hinh() {
	return array(
		'C1'  => array( 'ten' => 'Tật khúc xạ ở người lớn', 'slug' => 'tat-khuc-xa-nguoi-lon', 'mo_ta' => 'Cận thị, viễn thị, loạn thị, lệch độ và cách hiểu kết quả khúc xạ.' ),
		'C2'  => array( 'ten' => 'Cận thị trẻ em', 'slug' => 'can-thi-tre-em', 'mo_ta' => 'Dấu hiệu, theo dõi và kiểm soát tiến triển cận thị ở trẻ.' ),
		'C3'  => array( 'ten' => 'Mắt người cao tuổi', 'slug' => 'mat-nguoi-cao-tuoi', 'mo_ta' => 'Đục thủy tinh thể, chăm sóc sau mổ và những vấn đề thường gặp ở người cao tuổi.' ),
		'C4'  => array( 'ten' => 'Glôcôm – cườm nước', 'slug' => 'glocom-cuom-nuoc', 'mo_ta' => 'Dấu hiệu, yếu tố nguy cơ, theo dõi nhãn áp và bảo vệ thị lực.' ),
		'C5'  => array( 'ten' => 'Dịch kính – võng mạc', 'slug' => 'dich-kinh-vong-mac', 'mo_ta' => 'Ruồi bay, chớp sáng, bệnh võng mạc và biến chứng liên quan bệnh toàn thân.' ),
		'C6'  => array( 'ten' => 'Giác mạc – kết mạc – khô mắt', 'slug' => 'giac-mac-ket-mac-kho-mat', 'mo_ta' => 'Đau mắt đỏ, khô mắt, viêm bờ mi và chăm sóc bề mặt nhãn cầu.' ),
		'C7'  => array( 'ten' => 'Nhược thị và lác ở trẻ', 'slug' => 'nhuoc-thi-lac-tre-em', 'mo_ta' => 'Phát hiện sớm, theo dõi thị giác và các vấn đề mắt ở trẻ nhỏ.' ),
		'C8'  => array( 'ten' => 'Dấu hiệu cần đi khám ngay', 'slug' => 'dau-hieu-can-kham-ngay', 'mo_ta' => 'Các triệu chứng cảnh báo cần được cơ sở y tế đánh giá sớm.' ),
		'C9'  => array( 'ten' => 'Mắt và môi trường làm việc', 'slug' => 'mat-va-moi-truong-lam-viec', 'mo_ta' => 'Màn hình, bụi, hóa chất, ánh sáng và bảo vệ mắt trong công việc.' ),
		'C10' => array( 'ten' => 'Chuẩn bị đi khám và bảo hiểm', 'slug' => 'chuan-bi-kham-bao-hiem', 'mo_ta' => 'Giấy tờ, quy trình khám, giãn đồng tử và thông tin bảo hiểm y tế.' ),
	);
}

/** Đổi nhãn menu quản trị để người dùng tìm bài và thư mục dễ hơn. */
function eyecare_doi_ten_menu_bai_viet() {
	global $menu, $submenu;

	if ( isset( $menu[5][0] ) ) {
		$menu[5][0] = 'Bài viết kiến thức';
	}

	if ( isset( $submenu['edit.php'][5][0] ) ) {
		$submenu['edit.php'][5][0] = 'Tất cả bài viết';
	}

	if ( isset( $submenu['edit.php'][15][0] ) ) {
		$submenu['edit.php'][15][0] = 'Thư mục bài viết';
	}
}
add_action( 'admin_menu', 'eyecare_doi_ten_menu_bai_viet', 999 );
