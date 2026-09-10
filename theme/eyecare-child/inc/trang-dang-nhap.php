<?php
/**
 * Trang đăng nhập — Bệnh viện Mắt Hà Nội – Bắc Ninh
 *
 * Dùng logo bệnh viện qua assets/dang-nhap.css, thêm nhận diện thương hiệu
 * và Việt hóa các nhãn chính trên biểu mẫu WordPress.
 *
 * VÌ SAO KHÔNG DÙNG PLUGIN: trang đăng nhập là cửa vào duy nhất của site.
 * Thêm một plugin ở đúng chỗ đó là thêm một thứ phải theo dõi bản vá, mà
 * việc cần làm chỉ là CSS và ba bộ lọc có sẵn của WordPress.
 *
 * 🔴 Tệp này KHÔNG đổi địa chỉ đăng nhập và KHÔNG thêm lớp bảo vệ nào.
 * Giữ nguyên đường dẫn, biểu mẫu và cơ chế xác thực của WordPress.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nạp CSS riêng cho trang đăng nhập.
 *
 * style.css của theme con nạp ở hook wp_enqueue_scripts, hook đó không chạy
 * trên wp-login.php — nên phải nạp riêng ở login_enqueue_scripts.
 */
function eyecare_dn_nap_css() {
	$duong_dan = get_stylesheet_directory() . '/assets/dang-nhap.css';

	wp_enqueue_style(
		'eyecare-dang-nhap',
		get_stylesheet_directory_uri() . '/assets/dang-nhap.css',
		array( 'login' ),
		file_exists( $duong_dan ) ? (string) filemtime( $duong_dan ) : '1.0.0'
	);
}
add_action( 'login_enqueue_scripts', 'eyecare_dn_nap_css' );

/**
 * Chữ thay cho logo WordPress: tên bệnh viện.
 *
 * Lấy từ cài đặt chung chứ không viết cứng — đổi tên site thì trang đăng
 * nhập đổi theo, không phải sửa hai chỗ.
 *
 * @return string
 */
function eyecare_dn_ten_logo() {
	return get_bloginfo( 'name', 'display' );
}
add_filter( 'login_headertext', 'eyecare_dn_ten_logo' );

/**
 * Bấm vào tên thì về trang chủ site, không về wordpress.org.
 *
 * @return string
 */
function eyecare_dn_lien_ket_logo() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'eyecare_dn_lien_ket_logo' );

/**
 * Dòng phụ dưới tên bệnh viện.
 *
 * Chỉ hiện ở màn hình đăng nhập. Màn hình quên mật khẩu và đặt lại mật khẩu
 * đã có lời dẫn riêng của WordPress, thêm nữa thành hai câu chồng nhau.
 *
 * @param string $tin Nội dung WordPress định in ra.
 * @return string
 */
function eyecare_dn_dong_phu( $tin ) {
	$hanh_dong = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login';

	if ( 'login' !== $hanh_dong ) {
		return $tin;
	}

	return '<div class="eyecare-dn__intro"><h2>Đăng nhập quản trị</h2><p>Chào mừng bạn trở lại.<br>Đăng nhập để quản lý nội dung website.</p></div>' . $tin;
}
add_filter( 'login_message', 'eyecare_dn_dong_phu' );

/**
 * Chân trang: nhắc đây là trang nội bộ.
 *
 * Không ghi số điện thoại hay địa chỉ ở đây — trang đăng nhập không phải
 * điểm tiếp xúc với người bệnh, và mọi trang công khai đều đã có bộ NAP ở
 * chân trang.
 */
function eyecare_dn_chan_trang() {
	?>
	<aside class="eyecare-dn__brand" aria-label="Bệnh viện Mắt Hà Nội – Bắc Ninh">
		<span class="eyecare-dn__eyebrow">Hà Nội – Bắc Ninh</span>
		<h2>Nâng niu<br>đôi mắt Việt.</h2>
		<div class="eyecare-dn__line" aria-hidden="true"></div>
		<p>Hệ thống quản trị nội dung<br>Bệnh viện Mắt Hà Nội – Bắc Ninh</p>
	</aside>
	<div class="eyecare-dn__chan">
		<p><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></p>
		<p class="eyecare-dn__chan-noi-bo">
			<?php esc_html_e( 'Trang dành cho nhân sự bệnh viện. Không chia sẻ tài khoản.', 'eyecare-child' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'login_footer', 'eyecare_dn_chan_trang' );

/**
 * Việt hóa và rút gọn các nhãn chính cho nhân sự bệnh viện.
 *
 * 🔴 VÌ SAO GẮN TRONG login_init CHỨ KHÔNG GẮN THẲNG:
 * Bộ lọc `gettext` chạy trên MỌI chuỗi được dịch của toàn site — hàng nghìn
 * lần mỗi lần tải trang, cả ở giao diện ngoài và trang quản trị. Gắn thẳng ở
 * đầu tệp thì phải trả chi phí đó khắp nơi để đổi hai nhãn ở một trang.
 * login_init chỉ chạy trên wp-login.php.
 */
function eyecare_dn_gan_bo_loc_nhan() {
	add_filter( 'gettext', 'eyecare_dn_rut_ngan_nhan', 20, 2 );
}
add_action( 'login_init', 'eyecare_dn_gan_bo_loc_nhan' );

/**
 * Bảng rút ngắn nhãn. Chỉ chạy trên trang đăng nhập.
 *
 * @param string $ban_dich Chuỗi đã dịch.
 * @param string $goc      Chuỗi gốc tiếng Anh.
 * @return string
 */
function eyecare_dn_rut_ngan_nhan( $ban_dich, $goc ) {
	$bang = array(
		'Username or Email Address' => 'Tên đăng nhập hoặc email',
		'Lost your password?'       => 'Quên mật khẩu?',
		'Password'                 => 'Mật khẩu',
		'Remember Me'              => 'Ghi nhớ đăng nhập',
		'Log In'                   => 'Đăng nhập',
		'Language'                 => 'Ngôn ngữ',
		'Change'                   => 'Đổi',
		'&larr; Go to %s'           => '&larr; Về %s',
	);

	return $bang[ $goc ] ?? $ban_dich;
}
