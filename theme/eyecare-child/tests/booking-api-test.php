<?php
/**
 * API đặt lịch cho đối tác: chữ ký HMAC, chống dò khóa, request_id theo đối tác, quyền xem lịch, CSP form nhúng.
 * Chạy: php theme/eyecare-child/tests/booking-api-test.php
 */
require __DIR__ . '/booking-validation-test.php';

define( 'MINUTE_IN_SECONDS', 60 );
class WP_REST_Response {
	public $data;
	public $status;
	public function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; }
}
class WP_REST_Server { const READABLE = 'GET'; const CREATABLE = 'POST'; }
class EcApiTestRequest implements ArrayAccess {
	public $headers;
	public $method;
	public $route;
	public $body;
	public $params;
	public function __construct( $method, $route, $body = '', $headers = array(), $params = array() ) {
		$this->method = $method; $this->route = $route; $this->body = $body; $this->headers = $headers; $this->params = $params;
	}
	public function get_header( $name ) { return $this->headers[ $name ] ?? null; }
	public function get_method() { return $this->method; }
	public function get_route() { return $this->route; }
	public function get_body() { return $this->body; }
	public function offsetExists( $key ): bool { return isset( $this->params[ $key ] ); }
	#[\ReturnTypeWillChange]
	public function offsetGet( $key ) { return $this->params[ $key ] ?? null; }
	public function offsetSet( $key, $value ): void { $this->params[ $key ] = $value; }
	public function offsetUnset( $key ): void { unset( $this->params[ $key ] ); }
}
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function wp_parse_url( $url ) { return parse_url( $url ); }
function ec_zalo_token( $settings ) { return str_starts_with( $settings['cipher'] ?? '', 'enc:' ) ? substr( $settings['cipher'], 4 ) : ''; }

require dirname( __DIR__ ) . '/inc/api-dat-lich.php';

$secret = 'sk_' . str_repeat( 'c', 64 );
$GLOBALS['ec_test_options']['ec_booking_api_clients'] = array(
	'zalobot01' => array( 'name' => 'Zalo bot', 'channel' => 'Zalo', 'public_key' => 'pk_' . str_repeat( 'a', 32 ), 'secret' => 'enc:' . $secret, 'origins' => array(), 'limit' => 50 ),
	'partner02' => array( 'name' => 'Đối tác', 'public_key' => 'pk_' . str_repeat( 'b', 32 ), 'secret' => 'enc:sk_other', 'origins' => array( 'https://doitac.vn' ), 'allow_api' => false ),
	'paused003' => array( 'name' => 'Tạm dừng', 'public_key' => 'pk_' . str_repeat( 'd', 32 ), 'secret' => 'enc:' . $secret, 'active' => false ),
	'BAD ID'    => array( 'name' => 'Sai mã' ),
);
$route = '/ec-booking/v1/appointments';
function ec_api_request( $body, $client = 'zalobot01', $key = null, $time = null, $method = 'POST', $path = '/ec-booking/v1/appointments' ) {
	$time = null === $time ? time() : $time;
	$key  = null === $key ? 'sk_' . str_repeat( 'c', 64 ) : $key;
	$json = is_string( $body ) ? $body : json_encode( $body, JSON_UNESCAPED_UNICODE );
	return new EcApiTestRequest( $method, $path, 'GET' === $method ? '' : $json, array(
		'x_ec_key'       => $client,
		'x_ec_timestamp' => (string) $time,
		'x_ec_signature' => ec_booking_api_sign( $key, (string) $time, $method, $path, 'GET' === $method ? '' : $json ),
	) );
}

// Danh sách đối tác và domain nhúng.
$clients = ec_booking_api_clients();
ec_expect( isset( $clients['zalobot01'], $clients['partner02'] ) && ! isset( $clients['BAD ID'] ), 'Malformed client IDs are ignored' );
ec_expect( true === $clients['zalobot01']['active'] && true === $clients['zalobot01']['allow_embed'], 'Missing flags default to enabled' );
ec_expect( ec_booking_api_clean_origins( "https://doitac.vn/trang?x=1\nhttp://doitac.vn\nhttp://localhost:8080\njavascript:alert(1)\nhttps://*.doitac.vn, https://doitac.vn" ) === array( 'https://doitac.vn', 'http://localhost:8080', 'https://*.doitac.vn' ), 'Origins keep only https (or local http), strip paths and duplicates' );
ec_expect( ec_booking_api_clean_origins( "https://evil.vn'; script-src *" ) === array(), 'Origin with CSP injection characters rejected' );
ec_expect( ec_booking_api_client( 'pk_' . str_repeat( 'b', 32 ) )['id'] === 'partner02', 'Embed key resolves to its partner' );
ec_expect( null === ec_booking_api_client( 'pk_' . str_repeat( 'd', 32 ) ) && null === ec_booking_api_client( 'pk_x' ), 'Paused or malformed embed key rejected' );
ec_expect( 0 === strpos( ec_booking_api_frame_policy( $clients['partner02'] ), 'frame-ancestors https://doitac.vn;' ), 'CSP allows only the declared partner domain' );
ec_expect( 0 === strpos( ec_booking_api_frame_policy( null ), "frame-ancestors 'none';" ), 'Unknown key cannot be framed anywhere' );
$creds = ec_booking_api_new_credentials();
ec_expect( preg_match( '/^[a-z0-9]{8,16}$/D', $creds['id'] ) && preg_match( '/^pk_[a-f0-9]{32}$/D', $creds['public_key'] ) && preg_match( '/^sk_[a-f0-9]{64}$/D', $creds['secret'] ), 'New credentials match the formats the API accepts' );

// Xác thực chữ ký.
$GLOBALS['ec_test_transients'] = array();
$_SERVER['REMOTE_ADDR'] = '192.0.2.10';
$ok = ec_booking_api_authenticate( ec_api_request( array( 'a' => 1 ) ) );
ec_expect( is_array( $ok ) && 'zalobot01' === $ok['id'], 'Valid signature authenticates the partner' );
$tampered = ec_api_request( array( 'a' => 1 ) );
$tampered->body = '{"a":2}';
ec_expect_error( ec_booking_api_authenticate( $tampered ), 'auth', 'Changed body breaks the signature' );
ec_expect_error( ec_booking_api_authenticate( ec_api_request( array(), 'zalobot01', 'sk_wrong' ) ), 'auth', 'Wrong secret rejected' );
ec_expect_error( ec_booking_api_authenticate( ec_api_request( array(), 'zalobot01', null, time() - 400 ) ), 'auth_clock', 'Old timestamp rejected as replay' );
ec_expect_error( ec_booking_api_authenticate( ec_api_request( array(), 'paused003' ) ), 'auth', 'Paused partner rejected' );
ec_expect_error( ec_booking_api_authenticate( ec_api_request( array(), 'partner02', 'sk_other' ) ), 'auth', 'Embed-only partner cannot use the server API' );
$path_swap = ec_api_request( array() );
$path_swap->route = '/ec-booking/v1/appointments/1';
ec_expect_error( ec_booking_api_authenticate( $path_swap ), 'auth', 'Signature is bound to the route' );
$GLOBALS['ec_test_transients'] = array();
for ( $i = 0; $i < 30; $i++ ) {
	ec_booking_api_authenticate( ec_api_request( array(), 'zalobot01', 'sk_guess' . $i ) );
}
ec_expect_error( ec_booking_api_authenticate( ec_api_request( array( 'a' => 1 ) ) ), 'auth_throttled', 'Thirty failures lock out the IP, even with a valid key' );
$GLOBALS['ec_test_transients'] = array();

// Tạo lịch qua API.
$tomorrow = ( new DateTimeImmutable( 'tomorrow', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) )->format( 'Y-m-d' );
$GLOBALS['ec_test_posts'] = array();
$body = array( 'name' => 'phạm văn bình', 'phone' => '+84 987 654 321', 'date' => $tomorrow, 'time' => '08:00', 'consent' => true, 'request_id' => 'zalo-msg-000001' );
$created = ec_booking_api_create( ec_api_request( $body ) );
ec_expect( 201 === $created->status && true === $created->data['ok'], 'API booking created with 201' );
$appointment = $created->data['appointment'];
ec_expect( $appointment['date'] === $tomorrow && '08:00' === $appointment['time'] && 'confirmed' === $appointment['status'], 'Response carries date, time and status' );
ec_expect( ! isset( $appointment['name'] ) && ! isset( $appointment['phone'] ) && false === strpos( json_encode( $created->data ), '987' ), 'Response never echoes patient name or phone' );
$stored = get_post( $appointment['appointment_id'] );
ec_expect( 'api:zalobot01' === $stored->meta_input['_ec_booking_source'] && 'Zalo' === ec_booking_read( $stored )['channel'], 'Stored with partner source and channel' );
ec_expect( 'Phạm Văn Bình' === ec_booking_read( $stored )['name'] && '0987654321' === ec_booking_read( $stored )['phone'], 'API input is cleaned like the website form' );
$count = count( $GLOBALS['ec_test_posts'] );
$retry = ec_booking_api_create( ec_api_request( $body ) );
ec_expect( 201 === $retry->status && $retry->data['appointment']['appointment_id'] === $appointment['appointment_id'] && count( $GLOBALS['ec_test_posts'] ) === $count, 'Retry with the same request_id returns the same appointment' );
$dup = ec_booking_api_create( ec_api_request( array_merge( $body, array( 'time' => '09:00', 'request_id' => 'zalo-msg-000002' ) ) ) );
ec_expect( 409 === $dup->status && 'duplicate' === $dup->data['code'] && $dup->data['appointment']['appointment_id'] === $appointment['appointment_id'], 'Same person and day returns the existing appointment' );
$other = ec_booking_api_create( ec_api_request( array_merge( $body, array( 'name' => 'Người Khác', 'request_id' => 'zalo-msg-000003' ) ) ) );
ec_expect( 409 === $other->status && ! isset( $other->data['appointment'] ) && false === strpos( $other->data['message'], '08:00' ), 'Different name on the same phone gets no appointment details' );
ec_expect( count( $GLOBALS['ec_test_posts'] ) === $count, 'Duplicates create no records' );
ec_expect( ec_booking_api_scoped_request_id( 'zalobot01', 'same-id-01' ) !== ec_booking_api_scoped_request_id( 'partner02', 'same-id-01' ), 'Two partners using the same request_id do not collide' );
ec_expect( preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-a[a-f0-9]{3}-[a-f0-9]{12}$/D', ec_booking_api_scoped_request_id( 'zalobot01', 'X' ) ), 'Scoped request_id is a valid UUID v4' );
$no_consent = ec_booking_api_create( ec_api_request( array_merge( $body, array( 'consent' => false, 'request_id' => 'zalo-msg-000004' ) ) ) );
ec_expect( 400 === $no_consent->status && 'consent' === $no_consent->data['code'], 'Patient consent is required' );
ec_expect( 'request_id' === ec_booking_api_create( ec_api_request( array_merge( $body, array( 'request_id' => 'x' ) ) ) )->data['code'], 'Short request_id rejected' );
ec_expect( 'input' === ec_booking_api_create( ec_api_request( 'not json' ) )->data['code'], 'Non-JSON body rejected' );
$past = ec_booking_api_create( ec_api_request( array_merge( $body, array( 'date' => '2020-01-01', 'request_id' => 'zalo-msg-000005' ) ) ) );
ec_expect( 400 === $past->status && 'date' === $past->data['code'], 'Past date rejected' );
$GLOBALS['ec_test_transients'] = array();
for ( $i = 0; $i < 50; $i++ ) {
	ec_booking_rate_limit( '09110000' . sprintf( '%02d', $i ), array( 'client' => 'zalobot01', 'client_limit' => 50 ) );
}
$limited = ec_booking_api_create( ec_api_request( array_merge( $body, array( 'phone' => '0911999999', 'request_id' => 'zalo-msg-000006' ) ) ) );
ec_expect( 429 === $limited->status && 'rate_limit' === $limited->data['code'], 'Partner hourly limit enforced per key' );
$GLOBALS['ec_test_transients'] = array();

// Xem lịch: chỉ lịch do chính đối tác tạo.
$path = '/ec-booking/v1/appointments/' . $appointment['appointment_id'];
$show = ec_api_request( array(), 'zalobot01', null, null, 'GET', $path );
$show->params['id'] = (string) $appointment['appointment_id'];
$shown = ec_booking_api_show( $show );
ec_expect( 200 === $shown->status && 'confirmed' === $shown->data['appointment']['status'], 'Partner reads its own appointment' );
$GLOBALS['ec_test_posts'][0]->meta_input['_ec_booking_status'] = 'cancelled';
ec_expect( 'cancelled' === ec_booking_api_show( $show )->data['appointment']['status'], 'Status changes by staff are visible to the partner' );
$GLOBALS['ec_test_posts'][0]->meta_input['_ec_booking_source'] = 'website';
ec_expect( 404 === ec_booking_api_show( $show )->status, 'Partner cannot read appointments from other sources' );

echo 'PASS: ' . $assertions . " booking API, signature, partner-scope and embed policy checks.\n";
