<?php
/**
 * Theme con Eyecare Child — Bệnh viện Mắt Hà Nội – Bắc Ninh
 *
 * QUY TẮC:
 * - Mọi tuỳ chỉnh đặt trong file này, KHÔNG sửa theme mẹ Flatsome
 *   (sửa theme mẹ sẽ mất khi cập nhật).
 * - Đây là bản LOCALHOST để thử nghiệm. Không đưa lên máy chủ thật
 *   nếu chưa kiểm tra kỹ.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nạp CSS của theme.
 *
 * THEME ĐỘC LẬP: trước đây là theme con của Flatsome nên phải nạp style.css
 * của theme mẹ trước. Nay theme tự đứng — style.css đã có lớp nền riêng
 * (box-sizing, body, heading, link, nút) nên KHÔNG còn phụ thuộc Flatsome.
 */
function eyecare_child_enqueue_styles() {
	wp_enqueue_style(
		'eyecare-be-vietnam-pro',
		'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	/* Phiên bản = thời điểm sửa file (filemtime), KHÔNG phải số Version cố định
	   1.0.0. Version cố định thì mỗi lần sửa style.css trình duyệt vẫn thấy
	   ?ver=1.0.0 y như cũ nên dùng lại bản CSS trong cache — sửa xong không
	   thấy đổi. Lấy filemtime thì file đổi là ?ver đổi, trình duyệt tự tải
	   lại, không phải nhớ Ctrl+F5. filemtime chỉ đọc file cục bộ, rất nhẹ. */
	$css_child = get_stylesheet_directory() . '/style.css';
	$ver_child = file_exists( $css_child ) ? filemtime( $css_child ) : wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'eyecare-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array(),
		$ver_child
	);

	$css_pages = get_stylesheet_directory() . '/assets/page-layouts.css';
	if ( is_page() && file_exists( $css_pages ) ) {
		wp_enqueue_style(
			'eyecare-page-layouts',
			get_stylesheet_directory_uri() . '/assets/page-layouts.css',
			array( 'eyecare-child-style' ),
			filemtime( $css_pages )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'eyecare_child_enqueue_styles', 100 );

/** Nạp CSS cho khối đội ngũ dạng poster tại ba nơi đang dùng component. */
function eyecare_nap_css_doi_ngu_trang_chu() {
	if ( ! is_front_page() && ! is_page( array( 'gioi-thieu', 'doi-ngu-bac-si' ) ) ) {
		return;
	}

	$css = get_stylesheet_directory() . '/assets/home-team.css';
	if ( ! file_exists( $css ) ) {
		return;
	}

	wp_enqueue_style(
		'eyecare-home-team',
		get_stylesheet_directory_uri() . '/assets/home-team.css',
		array( 'eyecare-child-style' ),
		filemtime( $css )
	);
}
add_action( 'wp_enqueue_scripts', 'eyecare_nap_css_doi_ngu_trang_chu', 105 );


/**
 * Nạp JS slider — CHỈ trên trang chủ, và chỉ khi slider thực sự có ảnh.
 *
 * JS là LỚP NÂNG CẤP, không phải điều kiện để slider chạy: không có JS thì
 * slider vẫn trượt được bằng scroll-snap + chấm neo (đã có sẵn). Có JS thì
 * thêm hiệu ứng mờ/trượt, tự chạy và nút tạm dừng. Vì vậy chỉ nạp khi cần —
 * trang chủ có nhiều hơn một ảnh slider — để các trang khác không tải thừa.
 */
function eyecare_nap_js_slider() {

	if ( ! is_front_page() ) {
		return;
	}

	// Không có hàm đọc ảnh (chưa nạp inc/trang-chu.php) thì bỏ qua an toàn.
	if ( ! function_exists( 'eyecare_anh_slider' ) ) {
		return;
	}

	$anh = eyecare_anh_slider();
	if ( count( $anh ) < 2 ) {
		return; // Một ảnh hoặc hero chữ: không có gì để chuyển.
	}

	$tep_js = get_stylesheet_directory() . '/assets/slider.js';
	if ( ! file_exists( $tep_js ) ) {
		return;
	}

	wp_enqueue_script(
		'eyecare-slider',
		get_stylesheet_directory_uri() . '/assets/slider.js',
		array(),
		filemtime( $tep_js ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'eyecare_nap_js_slider', 110 );


/**
 * Nạp hiệu ứng bóc góc cho các thẻ lĩnh vực khám trên trang chủ.
 */
function eyecare_nap_js_linh_vuc() {
	if ( ! is_front_page() ) {
		return;
	}

	$tep_js = get_stylesheet_directory() . '/assets/linh-vuc.js';
	if ( ! file_exists( $tep_js ) ) {
		return;
	}

	wp_enqueue_script(
		'eyecare-linh-vuc',
		get_stylesheet_directory_uri() . '/assets/linh-vuc.js',
		array(),
		filemtime( $tep_js ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'eyecare_nap_js_linh_vuc', 116 );


/**
 * Nạp slider đánh giá bệnh nhân trên trang chủ.
 */
function eyecare_nap_js_danh_gia_benh_nhan() {
	if ( ! is_front_page() ) {
		return;
	}

	$tep_js = get_stylesheet_directory() . '/assets/danh-gia.js';
	if ( ! file_exists( $tep_js ) ) {
		return;
	}

	wp_enqueue_script(
		'eyecare-patient-reviews',
		get_stylesheet_directory_uri() . '/assets/danh-gia.js',
		array(),
		filemtime( $tep_js ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'eyecare_nap_js_danh_gia_benh_nhan', 117 );

/**
 * Nạp hiệu ứng kính mắt cho trang Liên hệ.
 */
function eyecare_nap_js_lien_he_eye() {
	if ( ! is_page( 'lien-he' ) ) {
		return;
	}

	$tep_js = get_stylesheet_directory() . '/assets/contact-eye.js';
	if ( ! file_exists( $tep_js ) ) {
		return;
	}

	wp_enqueue_script(
		'eyecare-contact-eye',
		get_stylesheet_directory_uri() . '/assets/contact-eye.js',
		array(),
		filemtime( $tep_js ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'eyecare_nap_js_lien_he_eye', 118 );


/* Nội dung hướng dẫn dài và FAQ của trang Liên hệ. Nạp trước schema để
 * JSON-LD dùng cùng nguồn câu hỏi với phần người đọc nhìn thấy. */
require_once get_stylesheet_directory() . '/inc/noi-dung-lien-he-seo.php';

/* ========================================================================== 
 * SCHEMA Y TẾ (F21)
 * --------------------------------------------------------------------------
 * Khối JSON-LD Hospital + MedicalOrganization nằm ở inc/schema-y-te.php
 *
 * Dữ liệu chưa có thì để rỗng trong hàm eyecare_du_lieu_thuc_the() —
 * trường rỗng tự động bị bỏ khỏi schema, KHÔNG bịa giá trị tạm.
 * Còn thiếu: toạ độ (B-16), số Giấy phép hoạt động (B-03).
 * ========================================================================== */
require_once get_stylesheet_directory() . '/inc/schema-y-te.php';

/* Quản trị số điện thoại, giờ mở cửa, địa chỉ và bản đồ từ một màn hình. */
require_once get_stylesheet_directory() . '/inc/quan-ly-lien-he.php';

/* Quản trị ảnh giao diện dùng chung: hero liên hệ, về chúng tôi, ảnh bác sĩ. */
require_once get_stylesheet_directory() . '/inc/quan-ly-anh-giao-dien.php';

/* Danh mục và mức giá dịch vụ, có công tắc riêng trước khi hiển thị công khai. */
require_once get_stylesheet_directory() . '/inc/quan-ly-bang-gia.php';

/* Mười thư mục chủ đề C1–C10 và nhãn quản trị bài viết kiến thức. */
require_once get_stylesheet_directory() . '/inc/quan-ly-bai-viet.php';

/* Giữ /wp-sitemap.xml hoạt động khi OBS SEO dùng sitemap riêng. */
require_once get_stylesheet_directory() . '/inc/sitemap-tuong-thich.php';


/* ==========================================================================
 * DỌN TRANG QUẢN TRỊ
 * --------------------------------------------------------------------------
 * Tắt các ô mặc định của WordPress (Tình trạng website, Bản nháp nhanh,
 * Tin nhanh, Hoạt động, Tin tức WordPress) cho MỌI tài khoản.
 *
 * Làm bằng code chứ không bấm "Tuỳ chọn hiển thị": nút đó chỉ tắt cho
 * riêng một tài khoản, tài khoản khác đăng nhập vẫn thấy nguyên.
 * ========================================================================== */
require_once get_stylesheet_directory() . '/inc/don-trang-quan-tri.php';


/* ==========================================================================
 * TRANG ĐĂNG NHẬP
 * --------------------------------------------------------------------------
 * Logo WordPress thay bằng tên bệnh viện, giao diện theo hệ màu của site.
 *
 * 🔴 Tệp đó KHÔNG đổi địa chỉ đăng nhập và KHÔNG thêm lớp bảo vệ nào — chỉ
 * là giao diện. Site thật dùng /mf-login thay cho /wp-admin; bản localhost
 * này vẫn là wp-login.php.
 * ========================================================================== */
require_once get_stylesheet_directory() . '/inc/trang-dang-nhap.php';


/* ==========================================================================
 * NỀN TẢNG THEME — thứ trước đây Flatsome mẹ cấp
 * --------------------------------------------------------------------------
 * Theme nay đứng độc lập, không còn theme mẹ Flatsome khai các hỗ trợ này hộ.
 * Thiếu thì: <title> mất (title-tag), ảnh đại diện bài không hiển thị được
 * (post-thumbnails), form tìm kiếm/chú thích dùng markup HTML4 cũ (html5),
 * và trang lưu trữ mất liên kết feed (automatic-feed-links).
 *
 * KHÔNG khai register_nav_menus: menu dựng bằng code (eyecare_menu_muc),
 * KHÔNG đọc từ DB — xem inc/dau-trang.php. Đăng ký menu ở đây sẽ mời người
 * dùng gán menu trong Giao diện, mà bản ghi đó bị đồng bộ DB đè mất.
 * ========================================================================== */
function eyecare_nen_tang_theme() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
}
add_action( 'after_setup_theme', 'eyecare_nen_tang_theme' );


/* ========================================================================== 
 * QUẢN LÝ ĐỘI NGŨ BÁC SĨ
 * --------------------------------------------------------------------------
 * Tạo mục quản trị riêng để thêm, sửa, xóa bác sĩ và cập nhật ảnh đại diện.
 * Nạp trước tệp tác giả để dữ liệu đội ngũ có thể đọc từ WordPress.
 * ========================================================================== */
require_once get_stylesheet_directory() . '/inc/quan-ly-bac-si.php';

/* Quản lý các thẻ “Dịch vụ của chúng tôi” trên trang chủ: ảnh, mô tả,
 * icon, liên kết, màu và thứ tự đều chỉnh được trong WordPress Admin. */
require_once get_stylesheet_directory() . '/inc/quan-ly-linh-vuc.php';


/* ========================================================================== 
 * QUẢN LÝ ĐÁNH GIÁ BỆNH NHÂN
 * --------------------------------------------------------------------------
 * Mỗi đánh giá là một bản ghi riêng, hỗ trợ ảnh đại diện/ảnh bìa hoặc video.
 * ========================================================================== */
require_once get_stylesheet_directory() . '/inc/quan-ly-danh-gia.php';


/* ========================================================================== 
 * TÁC GIẢ BÁC SĨ VÀ SCHEMA BÀI VIẾT (F25)
 * --------------------------------------------------------------------------
 * Khối thông tin người đứng tên nội dung ở cuối mỗi bài, cùng schema
 * Physician / MedicalWebPage / FAQPage cho 100 bài kiến thức nhãn khoa.
 *
 * Ba mã ngắn dùng trong nội dung bài:
 *   [tac-gia]                       khối bác sĩ cuối bài
 *   [faq]  H: ... Đ: ...  [/faq]    hỏi đáp, tự sinh FAQPage schema
 *   [doc-them] /duong-dan/ | nhãn [/doc-them]   liên kết nội bộ
 *
 * Số giấy phép hành nghề của bác sĩ CHƯA CÓ nên để rỗng và tự bị loại khỏi
 * schema. Không bịa — xem chú thích đầu tệp.
 * ========================================================================== */
require_once get_stylesheet_directory() . '/inc/tac-gia-bac-si.php';

/* Khối poster đội ngũ dùng chung cho trang chủ, Giới thiệu và Đội ngũ. */
require_once get_stylesheet_directory() . '/inc/doi-ngu-trang-chu.php';

/* Nội dung dài, trường SEO và FAQ schema cho category/tag. */
require_once get_stylesheet_directory() . '/inc/quan-ly-seo-danh-muc.php';

/**
 * Tắt riêng 2 khối cuối bài cho bài "Bắc Giang giờ thuộc tỉnh nào?".
 *
 * Lý do: bài này đang bị chèn tự động cả khối "Thông tin biên soạn" của
 * obs-seo-suite và khối author bio của cùng plugin. Ta chỉ gỡ trên 1 bài
 * cụ thể để không ảnh hưởng các bài khác và vẫn giữ phần "Bài viết liên quan".
 */
function eyecare_bo_khoi_bien_soan_va_tac_gia() {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return;
	}

	$post_id = (int) get_queried_object_id();
	if ( 590 !== $post_id ) {
		return;
	}

	if ( class_exists( 'OBS_Loader' ) ) {
		$aicb = OBS_Loader::get( 'ai_citation_bridge' );
		if ( $aicb ) {
			remove_filter( 'the_content', array( $aicb, 'maybe_inject_eeat' ), 11 );
		}

		$author_bio = OBS_Loader::get( 'author_bio' );
		if ( $author_bio ) {
			remove_filter( 'the_content', array( $author_bio, 'append_box' ), 99 );
			remove_filter( 'the_content', array( $author_bio, 'prepend_box' ), 99 );
			remove_filter( 'the_content', array( $author_bio, 'both_box' ), 99 );
		}
	}

	// Chặn luôn shortcode nếu sau này ai đó lỡ chèn lại vào bài này.
	remove_shortcode( 'obs_eeat_box' );
	remove_shortcode( 'obs_author_bio' );
}
add_action( 'wp', 'eyecare_bo_khoi_bien_soan_va_tac_gia', 20 );


/* ==========================================================================
 * TRANG CHỦ
 * --------------------------------------------------------------------------
 * Slider đầu trang và hàm lọc trang con có nội dung thật, dùng bởi
 * front-page.php.
 *
 * Dựng bằng front-page.php chứ không tạo một trang trong WordPress: quyết
 * định QĐ-08B (kiến trúc URL) đang chờ Ban giám đốc phê duyệt, chưa được
 * tạo trang mới.
 *
 * Slider chỉ hiện ảnh ĐÃ CÓ CHỮ ALT — alt là dấu cho biết đã có người xem
 * ảnh và kiểm ba thứ (số tổng đài cũ, cụm so sánh hơn nhất in trong ảnh,
 * logo đối thủ). Chưa có ảnh nào được kiểm thì tự rơi về hero chữ.
 * ========================================================================== */
require_once get_stylesheet_directory() . '/inc/trang-chu.php';

/* Bài hướng dẫn SEO dài ở cuối trang chủ, tách riêng để front-page.php dễ bảo trì. */
require_once get_stylesheet_directory() . '/inc/noi-dung-seo-trang-chu.php';


/* ==========================================================================
 * SLIDER TRANG CHỦ — trang cài đặt chọn ảnh
 * --------------------------------------------------------------------------
 * Giao diện → Slider trang chủ. Người dùng chọn ảnh từ Thư viện, sắp thứ tự
 * bằng kéo–thả, chọn hiệu ứng. Cấu hình ghi vào data/slider.json TRONG THEME,
 * KHÔNG vào cơ sở dữ liệu — vì bản localhost đồng bộ định kỳ sẽ đè mất mọi
 * theme_mod/option. Tệp theme nằm ngoài phạm vi đồng bộ. Xem chú thích đầu
 * inc/slider-cai-dat.php.
 * ========================================================================== */
require_once get_stylesheet_directory() . '/inc/slider-cai-dat.php';


/* ==========================================================================
 * MENU CHÍNH
 * --------------------------------------------------------------------------
 * Danh sách mục menu (eyecare_menu_muc) và hàm in menu (eyecare_menu_chinh_in),
 * dùng bởi header.php và footer.php.
 *
 * THEME TỰ DỰNG KHUNG TRANG: eyecare-child có header.php + footer.php +
 * index.php riêng, nên WordPress KHÔNG dùng khung của Flatsome nữa. Trước đây
 * phải chèn logo/menu vào bộ render của Flatsome bằng filter — cách đó làm menu
 * tràn xuống dòng thứ hai và không sửa được từ ngoài. Toàn bộ filter đó đã gỡ.
 *
 * KHÔNG ghi cơ sở dữ liệu và KHÔNG kích hoạt lại theme từ code trang chủ: bản
 * localhost đồng bộ định kỳ nên mọi theme_mod/menu ghi vào DB sẽ bị đè mất.
 * Theme nay ĐỘC LẬP (đã bỏ `Template: flatsome`); việc ghim theme đang dùng
 * sống sót qua đồng bộ do mu-plugin eyecare-ghim-theme.php lo, xem tệp đó.
 * ========================================================================== */
require_once get_stylesheet_directory() . '/inc/dau-trang.php';

/* Thumbnail SVG nhẹ theo chuyên mục cho các bài chưa có ảnh đại diện. */
require_once get_stylesheet_directory() . '/inc/anh-bai-du-phong.php';

/* Tạo featured image thật theo tiêu đề/chuyên mục và cho phép quản lý trong Admin. */
require_once get_stylesheet_directory() . '/inc/anh-dai-dien-bai-viet.php';

/* Tạo ảnh đại diện bằng HHTech API, mặc định dùng model Grok 1K tiết kiệm. */
require_once get_stylesheet_directory() . '/inc/quan-ly-anh-ai.php';
