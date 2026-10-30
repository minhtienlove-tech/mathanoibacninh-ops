<?php
/**
 * Cloudflare Turnstile cho form đặt lịch: tắt thì không gọi mạng; token thiếu/sai bị chặn;
 * lỗi kết nối hoặc khóa bí mật sai thì vẫn nhận lịch; gửi lại đúng yêu cầu đã lưu không cần token mới.
 * Chạy: php theme/eyecare-child/tests/booking-turnstile-test.php — không có HTTP thật.
 */
require __DIR__ . '/booking-validation-test.php';

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
function update_option( $key, $value, $autoload = null ) { $GLOBALS['ec_test_options'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['ec_test_options'][ $key ] ); return true; }
function home_url( $path = '' ) { return 'https://mathanoibacninh.com' . $path; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function ec_zalo_token( $settings ) { return str_starts_with( $settings['cipher'] ?? '', 'enc:' ) ? substr( $settings['cipher'], 4 ) : ''; }
$GLOBALS['ts_http'] = array();
$GLOBALS['ts_reply'] = null;
function wp_remote_post( $url, $args ) {
	$GLOBALS['ts_http'][] = array( 'url' => $url, 'args' => $args );
	return $GLOBALS['ts_reply'];
}
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code'] ?? 0; }
function wp_remote_retrieve_body( $response ) { return $response['body'] ?? ''; }
function ts_reply( $data, $code = 200 ) { return array( 'response' => array( 'code' => $code ), 'body' => is_string( $data ) ? $data : json_encode( $data ) ); }

require dirname( __DIR__ ) . '/inc/chong-bot-dat-lich.php';

$secret = 'turnstile_secret_0123456789';
function ts_configure( $enabled, $site = '0x4AAAAAAAtestSiteKey', $cipher = 'enc:turnstile_secret_0123456789' ) {
	$GLOBALS['ec_test_options']['ec_booking_turnstile'] = array( 'enabled' => $enabled, 'site_key' => $site, 'secret_cipher' => $cipher );
}
$_SERVER['REMOTE_ADDR'] = '198.51.100.20';

// Mặc định tắt: không ô xác minh, không gọi Cloudflare.
unset( $GLOBALS['ec_test_options']['ec_booking_turnstile'] );
ec_expect( ! ec_turnstile_active() && '' === ec_turnstile_site_key(), 'Off by default with no keys' );
ec_expect( true === ec_turnstile_check( null ) && array() === $GLOBALS['ts_http'], 'Off: any submission passes without a network call' );
ts_configure( false );
ec_expect( ! ec_turnstile_active() && true === ec_turnstile_check( '' ) && array() === $GLOBALS['ts_http'], 'Keys saved but switch off: still inactive' );
ts_configure( true, '0x4AAAAAAAtestSiteKey', '' );
ec_expect( ! ec_turnstile_active(), 'Enabled without a secret stays inactive' );
ts_configure( true, 'bad key!' );
ec_expect( ! ec_turnstile_active(), 'Enabled with a malformed site key stays inactive' );

// Bật: token thiếu hoặc lạ bị chặn trước khi gọi mạng.
ts_configure( true );
ec_expect( ec_turnstile_active() && '0x4AAAAAAAtestSiteKey' === ec_turnstile_site_key(), 'Active with both keys' );
foreach ( array( null, '', array( 'x' ), "tok\nen", str_repeat( 'a', 2049 ) ) as $bad ) {
	ec_expect_error( ec_turnstile_check( $bad ), 'captcha', 'Missing or malformed token rejected' );
}
ec_expect( array() === $GLOBALS['ts_http'], 'Malformed tokens never reach Cloudflare' );
ec_expect( 5 === (int) get_transient( ec_turnstile_blocked_key() ), 'Blocked attempts counted for the admin page' );
ec_expect( 403 === ec_turnstile_check( '' )->get_error_data()['status'], 'Rejection carries HTTP 403' );

// Gọi siteverify đúng tham số.
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => true, 'hostname' => 'mathanoibacninh.com', 'action' => 'booking' ) );
ec_expect( true === ec_turnstile_check( 'valid-token' ), 'Valid token for this host and action passes' );
$call = end( $GLOBALS['ts_http'] );
ec_expect( EC_TURNSTILE_VERIFY_URL === $call['url'] && $secret === $call['args']['body']['secret'] && 'valid-token' === $call['args']['body']['response'] && '198.51.100.20' === $call['args']['body']['remoteip'], 'Siteverify receives secret, token and visitor IP' );
ec_expect( true === $call['args']['sslverify'] && 0 === $call['args']['redirection'] && $call['args']['timeout'] <= 10, 'Siteverify uses TLS verification, no redirects, short timeout' );
ec_expect( false === strpos( json_encode( $call['args'] ), '0912' ) && ! isset( $call['args']['body']['name'], $call['args']['body']['phone'] ), 'No patient name or phone sent to Cloudflare' );
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => true, 'hostname' => 'www.mathanoibacninh.com', 'action' => 'booking' ) );
ec_expect( true === ec_turnstile_check( 'valid-token' ), 'www host accepted' );
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => true, 'hostname' => 'evil.example', 'action' => 'booking' ) );
ec_expect_error( ec_turnstile_check( 'token-from-elsewhere' ), 'captcha', 'Token solved on another site rejected' );
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => true, 'hostname' => 'mathanoibacninh.com', 'action' => 'login' ) );
ec_expect_error( ec_turnstile_check( 'other-action' ), 'captcha', 'Token for another action rejected' );

// Cloudflare báo token sai/hết hạn/dùng lại thì chặn.
foreach ( array( 'invalid-input-response', 'timeout-or-duplicate', 'missing-input-response', 'bad-request' ) as $code ) {
	$GLOBALS['ts_reply'] = ts_reply( array( 'success' => false, 'error-codes' => array( $code ) ) );
	ec_expect_error( ec_turnstile_check( 'tok-' . $code ), 'captcha', 'Cloudflare error rejects: ' . $code );
}
ec_expect( false === get_option( 'ec_booking_turnstile_last_error' ), 'Bot rejections do not raise the admin config warning' );

// Sự cố phía Cloudflare hoặc cấu hình sai: vẫn nhận lịch, ghi lỗi cho quản trị viên.
$GLOBALS['ts_reply'] = new WP_Error( 'http_request_failed', 'timeout' );
ec_expect( true === ec_turnstile_check( 'any-token' ), 'Network failure keeps accepting bookings' );
ec_expect( 'network' === get_option( 'ec_booking_turnstile_last_error' )['code'], 'Network failure recorded for the admin page' );
$GLOBALS['ts_reply'] = ts_reply( 'gateway', 502 );
ec_expect( true === ec_turnstile_check( 'any-token' ) && 'http_502' === get_option( 'ec_booking_turnstile_last_error' )['code'], 'HTTP error accepted and recorded' );
$GLOBALS['ts_reply'] = ts_reply( '<html>' );
ec_expect( true === ec_turnstile_check( 'any-token' ) && 'bad_response' === get_option( 'ec_booking_turnstile_last_error' )['code'], 'Unreadable response accepted and recorded' );
foreach ( array( 'invalid-input-secret', 'internal-error' ) as $code ) {
	$GLOBALS['ts_reply'] = ts_reply( array( 'success' => false, 'error-codes' => array( $code ) ) );
	ec_expect( true === ec_turnstile_check( 'any-token' ) && $code === get_option( 'ec_booking_turnstile_last_error' )['code'], 'Configuration/Cloudflare fault accepted and recorded: ' . $code );
}
$stamp = time() - 60;
$GLOBALS['ec_test_options']['ec_booking_turnstile_last_error'] = array( 'code' => 'network', 'at' => $stamp );
$GLOBALS['ts_reply'] = new WP_Error( 'http_request_failed', 'timeout' );
ec_turnstile_check( 'any-token' );
ec_expect( $stamp === get_option( 'ec_booking_turnstile_last_error' )['at'], 'Same fault within 10 minutes is not rewritten' );

// Luồng AJAX của form: yêu cầu mới cần token; gửi lại đúng yêu cầu đã lưu thì không.
$GLOBALS['ec_test_posts'] = array();
$GLOBALS['ec_test_transients'] = array();
$GLOBALS['ts_http'] = array();
$tomorrow = ( new DateTimeImmutable( 'tomorrow', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) )->format( 'Y-m-d' );
$_SERVER['REQUEST_METHOD'] = 'POST';
$form = array( 'name' => 'Trần Văn Thử', 'phone' => '0977000111', 'date' => $tomorrow, 'time' => '08:00', 'consent' => '1', 'website' => '', 'request_id' => '423e4567-e89b-42d3-a456-426614174000', 'nonce' => 'test-nonce' );
function ts_submit( $post ) {
	$_POST = $post;
	try { ec_booking_ajax_submit(); } catch ( EcBookingTestResponse $response ) { return $response; }
	return null;
}
$no_token = ts_submit( $form );
ec_expect( $no_token && ! $no_token->response['success'] && 403 === $no_token->status && 'captcha' === $no_token->response['data']['code'], 'New booking without a token gets HTTP 403 captcha' );
ec_expect( array() === $GLOBALS['ec_test_posts'], 'Rejected request stores nothing' );
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => false, 'error-codes' => array( 'invalid-input-response' ) ) );
$bad = ts_submit( array_merge( $form, array( 'cf_turnstile' => 'forged' ) ) );
ec_expect( $bad && 'captcha' === $bad->response['data']['code'] && array() === $GLOBALS['ec_test_posts'], 'Forged token stores nothing' );
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => true, 'hostname' => 'mathanoibacninh.com', 'action' => 'booking' ) );
$ok = ts_submit( array_merge( $form, array( 'cf_turnstile' => 'good-token' ) ) );
ec_expect( $ok && $ok->response['success'] && 1 === count( $GLOBALS['ec_test_posts'] ), 'Valid token books the appointment' );
$calls = count( $GLOBALS['ts_http'] );
$GLOBALS['ts_reply'] = ts_reply( array( 'success' => false, 'error-codes' => array( 'timeout-or-duplicate' ) ) );
$replay = ts_submit( array_merge( $form, array( 'cf_turnstile' => 'good-token' ) ) );
ec_expect( $replay && $replay->response['success'] && 1 === count( $GLOBALS['ec_test_posts'] ) && $calls === count( $GLOBALS['ts_http'] ), 'Retry of a saved request succeeds without re-verifying the used token' );
ts_configure( false );
$off = ts_submit( array_merge( $form, array( 'phone' => '0977000222', 'request_id' => '423e4567-e89b-42d3-a456-426614174001' ) ) );
ec_expect( $off && $off->response['success'] && 2 === count( $GLOBALS['ec_test_posts'] ), 'Switched off: form books without a token as before' );

echo 'PASS: ' . $assertions . " booking Turnstile checks; no real network calls.\n";
