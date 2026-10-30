<?php
/**
 * Chống dò mật khẩu và Turnstile cho đăng nhập WordPress: ngưỡng khóa, thông báo chung,
 * mật khẩu ứng dụng, Turnstile chỉ trên form wp-login.php, chế độ hiện/ẩn ô xác minh.
 * Chạy: php theme/eyecare-child/tests/login-guard-test.php — không có HTTP thật.
 */
require __DIR__ . '/booking-turnstile-test.php';

function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function delete_transient( $key ) { unset( $GLOBALS['ec_test_transients'][ $key ] ); return true; }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
class WP_User { public $ID = 7; }
class EcLoginTestError extends WP_Error {
	public $added = array();
	public function add( $code, $message ) { $this->added[ $code ] = $message; }
}

require dirname( __DIR__ ) . '/inc/chong-bot-dang-nhap.php';

$GLOBALS['ec_test_transients'] = array();
$GLOBALS['ts_http'] = array();
$_SERVER['REMOTE_ADDR'] = '203.0.113.50';
$GLOBALS['pagenow'] = 'wp-login.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = array( 'log' => 'admin', 'pwd' => 'x' );
$user = new WP_User();

// Thông báo chung: không lộ tên đăng nhập có tồn tại hay không.
$unknown = ec_login_guard_authenticate( new WP_Error( 'invalid_username', 'Tên <strong>admin</strong> chưa đăng ký.' ), 'admin', 'x' );
$wrong   = ec_login_guard_authenticate( new WP_Error( 'incorrect_password', 'Mật khẩu cho <strong>admin</strong> không đúng.' ), 'admin', 'x' );
ec_expect( 'incorrect_password' === $unknown->get_error_code() && $unknown->get_error_message() === $wrong->get_error_message(), 'Unknown user and wrong password give the same message' );
ec_expect( false === strpos( $wrong->get_error_message(), 'admin' ), 'Message does not echo the username' );
ec_expect( $user === ec_login_guard_authenticate( $user, 'admin', 'right' ), 'Correct login passes through' );
ec_expect( 'keep' === ec_login_guard_authenticate( 'keep', '', '' ), 'Opening the login page (no credentials) is untouched' );

// Khóa 15 phút sau 8 lần sai trong 15 phút; mật khẩu đúng cũng bị chặn.
for ( $i = 0; $i < 7; $i++ ) {
	ec_login_guard_failed( 'admin', new WP_Error( 'incorrect_password', 'x' ) );
}
ec_expect( 0 === ec_login_guard_locked_until() && $user === ec_login_guard_authenticate( $user, 'admin', 'right' ), 'Seven failures do not lock' );
ec_login_guard_failed( 'admin', new WP_Error( 'incorrect_password', 'x' ) );
$until = ec_login_guard_locked_until();
ec_expect( $until > time() && abs( $until - time() - 900 ) <= 2, 'Eighth failure locks for 15 minutes' );
$blocked = ec_login_guard_authenticate( $user, 'admin', 'right' );
ec_expect( is_wp_error( $blocked ) && 'ec_login_locked' === $blocked->get_error_code(), 'Correct password refused while locked' );
ec_expect( false !== strpos( $blocked->get_error_message(), ( new DateTimeImmutable( '@' . $until ) )->setTimezone( new DateTimeZone( 'Asia/Ho_Chi_Minh' ) )->format( 'H:i' ) ), 'Lock message shows when it ends (Vietnam time)' );
$count = (int) get_transient( ec_login_guard_key( 'fail15' ) );
ec_login_guard_failed( 'admin', $blocked );
ec_expect( $count === (int) get_transient( ec_login_guard_key( 'fail15' ) ), 'Refusals caused by the lock are not counted again' );
ec_expect( 8 === (int) get_transient( 'ec_login_failed_' . ec_booking_now()->format( 'Ymd' ) ), 'Daily failed-login count kept for the admin page' );
$keys = implode( '|', array_keys( $GLOBALS['ec_test_transients'] ) );
ec_expect( false === strpos( $keys, '203.0.113.50' ), 'Lock keys contain no raw IP' );

// Địa chỉ mạng khác không bị ảnh hưởng; IPv6 cùng /64 dùng chung bộ đếm.
$_SERVER['REMOTE_ADDR'] = '198.51.100.99';
ec_expect( 0 === ec_login_guard_locked_until() && $user === ec_login_guard_authenticate( $user, 'admin', 'right' ), 'Other addresses can still log in' );
$_SERVER['REMOTE_ADDR'] = '2001:db8:aa:1::1';
$v6 = ec_login_guard_key( 'lock' );
$_SERVER['REMOTE_ADDR'] = '2001:db8:aa:1:ffff::9';
ec_expect( $v6 === ec_login_guard_key( 'lock' ), 'IPv6 addresses in one /64 share a lock' );

// Đăng nhập đúng xóa bộ đếm 15 phút; bộ đếm 24 giờ vẫn giữ.
$_SERVER['REMOTE_ADDR'] = '192.0.2.80';
for ( $i = 0; $i < 5; $i++ ) {
	ec_login_guard_failed( 'admin', new WP_Error( 'incorrect_password', 'x' ) );
}
ec_login_guard_success();
ec_expect( false === get_transient( ec_login_guard_key( 'fail15' ) ) && 5 === (int) get_transient( ec_login_guard_key( 'fail24' ) ), 'Successful login clears the 15-minute count only' );

// Khóa 24 giờ sau 30 lần sai trong 24 giờ.
set_transient( ec_login_guard_key( 'fail24' ), 29, 86400 );
ec_login_guard_failed( 'admin', new WP_Error( 'incorrect_password', 'x' ) );
ec_expect( abs( ec_login_guard_locked_until() - time() - 86400 ) <= 2, 'Thirtieth failure in a day locks for 24 hours' );

// Mật khẩu ứng dụng (REST API): sai thì đếm, đang khóa thì từ chối.
$app_error = new EcLoginTestError( 'none', '' );
ec_login_guard_app_password( $app_error );
ec_expect( isset( $app_error->added['ec_login_locked'] ), 'Locked address cannot use an application password' );
$_SERVER['REMOTE_ADDR'] = '192.0.2.81';
$fresh = new EcLoginTestError( 'none', '' );
ec_login_guard_app_password( $fresh );
ec_expect( array() === $fresh->added, 'Unlocked address keeps application passwords working' );
ec_login_guard_record_failure( new WP_Error( 'incorrect_password', 'x' ) );
ec_expect( 1 === (int) get_transient( ec_login_guard_key( 'fail15' ) ), 'Failed application password is counted' );

// Turnstile trên trang đăng nhập: nút bật riêng, dùng chung khóa với form đặt lịch.
$_SERVER['REMOTE_ADDR'] = '192.0.2.90';
$GLOBALS['ec_test_options']['ec_booking_turnstile'] = array( 'enabled' => true, 'site_key' => '0x4AAAAAAAtestSiteKey', 'secret_cipher' => 'enc:turnstile_secret_0123456789' );
ec_expect( ec_turnstile_active() && ! ec_turnstile_login_active(), 'Booking switch on does not turn on login checks' );
ec_expect( $user === ec_login_guard_authenticate( $user, 'admin', 'right' ) && array() === $GLOBALS['ts_http'], 'Login without its switch makes no Cloudflare call' );
ob_start();
ec_login_guard_turnstile_widget();
ec_expect( '' === ob_get_clean(), 'No widget on the login page while its switch is off' );
$GLOBALS['ec_test_options']['ec_booking_turnstile']['login'] = true;
ec_expect( ec_turnstile_login_active(), 'Login switch on with shared keys' );
unset( $_POST['cf-turnstile-response'] );
$no_token = ec_login_guard_authenticate( $user, 'admin', 'right' );
ec_expect( is_wp_error( $no_token ) && 'ec_login_captcha' === $no_token->get_error_code(), 'Login form without a token is refused even with the right password' );
ec_login_guard_failed( 'admin', $no_token );
ec_expect( 1 === (int) get_transient( ec_login_guard_key( 'fail15' ) ) && 1 === (int) get_transient( ec_turnstile_blocked_key( 'login' ) ), 'Captcha refusal counts toward the lock and the daily login block count' );
$_POST['cf-turnstile-response'] = 'login-token';
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => true, 'hostname' => 'mathanoibacninh.com', 'action' => 'login' ) );
ec_expect( $user === ec_login_guard_authenticate( $user, 'admin', 'right' ), 'Valid login token lets the right password in' );
$call = end( $GLOBALS['ts_http'] );
ec_expect( 'login-token' === $call['args']['body']['response'], 'Login token sent to Cloudflare' );
$wrong_pw = ec_login_guard_authenticate( new WP_Error( 'incorrect_password', 'x' ), 'admin', 'bad' );
ec_expect( 'incorrect_password' === $wrong_pw->get_error_code(), 'Valid token with a wrong password still gets the generic error' );
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => true, 'hostname' => 'mathanoibacninh.com', 'action' => 'booking' ) );
ec_expect( 'ec_login_captcha' === ec_login_guard_authenticate( $user, 'admin', 'right' )->get_error_code(), 'Booking-form token cannot be reused to log in' );
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => true, 'hostname' => 'mathanoibacninh.com', 'action' => 'booking' ) );
ec_expect( true === ec_turnstile_check( 'booking-token' ), 'Booking token still valid for the booking form' );
$GLOBALS['ts_reply'] = new WP_Error( 'http_request_failed', 'timeout' );
ec_expect( $user === ec_login_guard_authenticate( $user, 'admin', 'right' ), 'Cloudflare outage does not lock admins out' );
$GLOBALS['pagenow'] = 'xmlrpc.php';
unset( $_POST['cf-turnstile-response'] );
$calls = count( $GLOBALS['ts_http'] );
ec_expect( $user === ec_login_guard_authenticate( $user, 'admin', 'right' ) && $calls === count( $GLOBALS['ts_http'] ), 'Non-form logins (XML-RPC/REST) are not asked for a token' );
$GLOBALS['pagenow'] = 'wp-login.php';

// Hiện/ẩn ô xác minh: riêng cho từng nơi, mặc định ẩn.
ec_expect( 'interaction-only' === ec_turnstile_appearance( 'booking' ) && 'interaction-only' === ec_turnstile_appearance( 'login' ), 'Both default to hidden (shown only when suspicious)' );
$GLOBALS['ec_test_options']['ec_booking_turnstile']['login_appearance'] = 'always';
$GLOBALS['ec_test_options']['ec_booking_turnstile']['appearance'] = 'nonsense';
ec_expect( 'always' === ec_turnstile_appearance( 'login' ) && 'interaction-only' === ec_turnstile_appearance( 'booking' ), 'Login can always show the box; unknown values fall back to hidden' );
ob_start();
ec_login_guard_turnstile_widget();
$widget = ob_get_clean();
ec_expect( false !== strpos( $widget, 'data-sitekey="0x4AAAAAAAtestSiteKey"' ) && false !== strpos( $widget, 'data-action="login"' ) && false !== strpos( $widget, 'data-appearance="always"' ), 'Login widget carries site key, login action and chosen appearance' );
ec_expect( false === strpos( $widget, 'turnstile_secret' ), 'Secret key never printed on the page' );

echo 'PASS: ' . $assertions . " login guard and login Turnstile checks; no real network calls.\n";
