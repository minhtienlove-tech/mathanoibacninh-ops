<?php
/**
 * Chống dò mật khẩu cho đăng nhập WordPress, luôn bật:
 * - Một địa chỉ mạng (IPv6 gộp /64) sai 8 lần trong 15 phút bị khóa 15 phút; sai 30 lần trong 24 giờ bị khóa 24 giờ.
 *   Khi đang khóa, mật khẩu đúng cũng không vào được. Áp dụng cả mật khẩu ứng dụng (REST API).
 * - Một thông báo chung cho sai tên đăng nhập và sai mật khẩu, không lộ tài khoản nào tồn tại.
 * - Cloudflare Turnstile trên form đăng nhập khi bật ở Lịch hẹn → Chống bot (dùng khóa trong inc/chong-bot-dat-lich.php).
 * Mở khóa: nút trong trang Chống bot, hoặc trên máy chủ chạy `wp eval 'ec_login_guard_unlock_all();'`.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const EC_LOGIN_SHORT_LIMIT  = 8;
const EC_LOGIN_SHORT_WINDOW = 900;
const EC_LOGIN_LONG_LIMIT   = 30;
const EC_LOGIN_LONG_WINDOW  = 86400;

/** Khóa transient theo HMAC của địa chỉ mạng; không lưu IP thật. */
function ec_login_guard_key( $kind ) {
	$ip = function_exists( 'ec_booking_client_ip' ) ? ec_booking_client_ip() : 'unknown';
	return 'ec_login_' . $kind . '_' . substr( hash_hmac( 'sha256', 'login:' . $ip, wp_salt( 'nonce' ) ), 0, 32 );
}

/** Thời điểm hết khóa (giây Unix), hoặc 0 nếu không bị khóa. */
function ec_login_guard_locked_until() {
	$until = (int) get_transient( ec_login_guard_key( 'lock' ) );
	return $until > time() ? $until : 0;
}

function ec_login_guard_locked_error( $until ) {
	$at = ( new DateTimeImmutable( '@' . $until ) )->setTimezone( new DateTimeZone( 'Asia/Ho_Chi_Minh' ) )->format( 'H:i d/m' );
	return new WP_Error( 'ec_login_locked', '<strong>Lỗi:</strong> Đăng nhập sai quá nhiều lần từ địa chỉ mạng này. Vui lòng thử lại sau ' . $at . ' hoặc nhờ quản trị viên mở khóa.' );
}

/** Ghi một lần đăng nhập sai; đủ ngưỡng thì khóa. Lượt bị từ chối vì đang khóa không đếm thêm. */
function ec_login_guard_record_failure( $error = null ) {
	if ( is_wp_error( $error ) && 'ec_login_locked' === $error->get_error_code() ) {
		return;
	}
	$day = 'ec_login_failed_' . ec_booking_now()->format( 'Ymd' );
	set_transient( $day, (int) get_transient( $day ) + 1, 2 * DAY_IN_SECONDS );
	$short = (int) get_transient( ec_login_guard_key( 'fail15' ) ) + 1;
	$long  = (int) get_transient( ec_login_guard_key( 'fail24' ) ) + 1;
	set_transient( ec_login_guard_key( 'fail15' ), $short, EC_LOGIN_SHORT_WINDOW );
	set_transient( ec_login_guard_key( 'fail24' ), $long, EC_LOGIN_LONG_WINDOW );
	$duration = $long >= EC_LOGIN_LONG_LIMIT ? EC_LOGIN_LONG_WINDOW : ( $short >= EC_LOGIN_SHORT_LIMIT ? EC_LOGIN_SHORT_WINDOW : 0 );
	if ( $duration && time() + $duration > ec_login_guard_locked_until() ) {
		set_transient( ec_login_guard_key( 'lock' ), time() + $duration, $duration );
	}
}

function ec_login_guard_failed( $username, $error = null ) {
	ec_login_guard_record_failure( $error );
}
add_action( 'wp_login_failed', 'ec_login_guard_failed', 10, 2 );
add_action( 'application_password_failed_authentication', 'ec_login_guard_record_failure' );

function ec_login_guard_success() {
	delete_transient( ec_login_guard_key( 'fail15' ) );
}
add_action( 'wp_login', 'ec_login_guard_success' );

/** Chỉ form wp-login.php mới có ô Turnstile; REST/XML-RPC không kiểm tra token. */
function ec_login_guard_is_form_login() {
	return isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] && 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['log'] );
}

/**
 * Chạy sau bước kiểm tra mật khẩu của WordPress (ưu tiên 20) để kết quả không bị ghi đè.
 * Thứ tự: đang khóa → chưa qua Turnstile → sai tên/mật khẩu (thông báo chung).
 */
function ec_login_guard_authenticate( $user, $username, $password ) {
	if ( '' === (string) $username && '' === (string) $password ) {
		return $user;
	}
	$until = ec_login_guard_locked_until();
	if ( $until ) {
		return ec_login_guard_locked_error( $until );
	}
	if ( ec_login_guard_is_form_login() && function_exists( 'ec_turnstile_login_active' ) && ec_turnstile_login_active() ) {
		$token = isset( $_POST['cf-turnstile-response'] ) && is_string( $_POST['cf-turnstile-response'] ) ? wp_unslash( $_POST['cf-turnstile-response'] ) : null;
		$check = ec_turnstile_verify( $token, 'login' );
		if ( is_wp_error( $check ) ) {
			return $check;
		}
	}
	if ( is_wp_error( $user ) && in_array( $user->get_error_code(), array( 'invalid_username', 'invalid_email', 'incorrect_password' ), true ) ) {
		return new WP_Error( 'incorrect_password', '<strong>Lỗi:</strong> Tên đăng nhập hoặc mật khẩu không đúng.' );
	}
	return $user;
}
add_filter( 'authenticate', 'ec_login_guard_authenticate', 99, 3 );

/** Mật khẩu ứng dụng đúng nhưng địa chỉ mạng đang bị khóa thì vẫn từ chối. */
function ec_login_guard_app_password( $error ) {
	$until = ec_login_guard_locked_until();
	if ( $until && is_wp_error( $error ) ) {
		$error->add( 'ec_login_locked', wp_strip_all_tags( ec_login_guard_locked_error( $until )->get_error_message() ) );
	}
}
add_action( 'wp_authenticate_application_password_errors', 'ec_login_guard_app_password' );

function ec_login_guard_turnstile_script() {
	if ( function_exists( 'ec_turnstile_login_active' ) && ec_turnstile_login_active() ) {
		// Cloudflare yêu cầu nạp đúng URL gốc, không thêm tham số phiên bản.
		wp_enqueue_script( 'ec-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array( 'strategy' => 'async' ) );
	}
}
add_action( 'login_enqueue_scripts', 'ec_login_guard_turnstile_script' );

function ec_login_guard_turnstile_widget() {
	if ( function_exists( 'ec_turnstile_login_active' ) && ec_turnstile_login_active() ) {
		echo '<div class="cf-turnstile" style="margin:0 0 16px" data-sitekey="' . esc_attr( ec_turnstile_settings()['site_key'] ) . '" data-action="login" data-appearance="' . esc_attr( ec_turnstile_appearance( 'login' ) ) . '" data-theme="light" data-size="flexible"></div>';
	}
}
add_action( 'login_form', 'ec_login_guard_turnstile_widget' );

/** Tên transient của bộ đếm/khóa; null nếu site dùng object cache ngoài (transient không nằm trong bảng options). */
function ec_login_guard_transients( $kinds ) {
	global $wpdb;
	if ( wp_using_ext_object_cache() ) {
		return null;
	}
	$names = array();
	foreach ( $kinds as $kind ) {
		$like  = $wpdb->esc_like( '_transient_ec_login_' . $kind . '_' ) . '%';
		$names = array_merge( $names, $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 1000", $like ) ) );
	}
	return array_map( function ( $name ) { return substr( $name, strlen( '_transient_' ) ); }, $names );
}

function ec_login_guard_unlock_all() {
	$names = ec_login_guard_transients( array( 'lock', 'fail15', 'fail24' ) );
	foreach ( (array) $names as $name ) {
		delete_transient( $name );
	}
	return is_array( $names ) ? count( $names ) : 0;
}

function ec_login_guard_admin_unlock() {
	if ( ! current_user_can( 'manage_options' ) || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( 'Bạn không có quyền thực hiện thao tác này.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'ec_login_guard_unlock', 'ec_login_guard_nonce' );
	ec_login_guard_unlock_all();
	ec_turnstile_admin_notice( 'Đã mở khóa đăng nhập cho mọi địa chỉ mạng.' );
}
add_action( 'admin_post_ec_login_guard_unlock', 'ec_login_guard_admin_unlock' );

/** Thẻ thống kê trong trang Lịch hẹn → Chống bot. */
function ec_login_guard_admin_card() {
	$locks  = ec_login_guard_transients( array( 'lock' ) );
	$active = null;
	if ( is_array( $locks ) ) {
		$active = 0;
		foreach ( $locks as $name ) {
			if ( (int) get_transient( $name ) > time() ) {
				++$active;
			}
		}
	}
	$failed = (int) get_transient( 'ec_login_failed_' . ec_booking_now()->format( 'Ymd' ) );
	?>
	<div class="card" style="max-width:none;padding:12px 24px;margin-top:16px">
		<h2>Chống dò mật khẩu (luôn bật)</h2>
		<p>Một địa chỉ mạng đăng nhập sai <?php echo esc_html( (string) EC_LOGIN_SHORT_LIMIT ); ?> lần trong 15 phút bị khóa 15 phút; sai <?php echo esc_html( (string) EC_LOGIN_LONG_LIMIT ); ?> lần trong 24 giờ bị khóa 24 giờ. Khi đang khóa, mật khẩu đúng cũng không vào được. Thông báo lỗi không cho biết tên đăng nhập có tồn tại hay không.</p>
		<ul>
			<li>Lượt đăng nhập sai hôm nay: <strong><?php echo esc_html( (string) $failed ); ?></strong></li>
			<li>Địa chỉ mạng đang bị khóa: <strong><?php echo null === $active ? 'không đếm được (site dùng object cache)' : esc_html( (string) $active ); ?></strong></li>
		</ul>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Mở khóa đăng nhập cho mọi địa chỉ mạng?')">
			<input type="hidden" name="action" value="ec_login_guard_unlock">
			<?php wp_nonce_field( 'ec_login_guard_unlock', 'ec_login_guard_nonce' ); ?>
			<?php submit_button( 'Mở khóa tất cả', 'secondary', 'submit', false ); ?>
		</form>
		<p class="description">Nếu chính bạn bị khóa và không có quản trị viên khác: chờ hết thời gian khóa, hoặc trên máy chủ chạy <code>wp eval 'ec_login_guard_unlock_all();'</code>.</p>
	</div>
	<?php
}
