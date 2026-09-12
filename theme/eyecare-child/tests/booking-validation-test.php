<?php
/**
 * Tests độc lập, hoàn toàn trong bộ nhớ; không nạp WP, không kết nối production.
 * Chạy: php theme/eyecare-child/tests/booking-validation-test.php
 */
define( 'ABSPATH', __DIR__ );
define( 'HOUR_IN_SECONDS', 3600 );

class WP_Error {
	public $code;
	private $message;
	private $data;
	public function __construct( $code, $message, $data = null ) {
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}
	public function get_error_message() { return $this->message; }
	public function get_error_code() { return $this->code; }
	public function get_error_data() { return $this->data; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function wp_salt( $scheme ) { return 'standalone-test-salt'; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function wp_slash( $value ) { return is_array( $value ) ? array_map( 'wp_slash', $value ) : ( is_string( $value ) ? addslashes( $value ) : $value ); }
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : ( is_string( $value ) ? stripslashes( $value ) : $value ); }
function eyecare_du_lieu_thuc_the() { return array( 'gio_mo' => '07:30', 'gio_dong' => '18:00' ); }
function register_post_type( $name, $args ) { $GLOBALS['ec_test_cpt'] = $args; }
function nocache_headers() {}
function wp_create_nonce( $action ) { return 'test-nonce'; }
function wp_verify_nonce( $nonce, $action ) { return 'test-nonce' === $nonce; }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
class EcBookingTestResponse extends Exception {
	public $response;
	public $status;
	public function __construct( $success, $data, $status = 200 ) {
		$this->response = array( 'success' => $success, 'data' => $data );
		$this->status = $status;
	}
}
function wp_send_json_success( $data, $status = 200 ) { throw new EcBookingTestResponse( true, $data, $status ); }
function wp_send_json_error( $data, $status = 200 ) { throw new EcBookingTestResponse( false, $data, $status ); }

$GLOBALS['ec_test_options'] = array();
$GLOBALS['ec_test_transients'] = array();
$GLOBALS['ec_test_posts'] = array();
$GLOBALS['ec_test_insert_count'] = 0;
$GLOBALS['ec_test_fail_insert'] = false;
function add_option( $key, $value, $deprecated = '', $autoload = null ) {
	if ( array_key_exists( $key, $GLOBALS['ec_test_options'] ) ) { return false; }
	$GLOBALS['ec_test_options'][ $key ] = $value;
	return true;
}
function get_option( $key ) { return $GLOBALS['ec_test_options'][ $key ] ?? false; }
function wp_cache_delete( ...$args ) {}
function get_transient( $key ) { return $GLOBALS['ec_test_transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['ec_test_transients'][ $key ] = $value; return true; }
class EcBookingTestDb {
	public $options = 'test_options';
	public function prepare( $sql, ...$args ) { return $args; }
	public function query( $args ) {
		if ( ( $GLOBALS['ec_test_options'][ $args[0] ] ?? false ) === $args[1] ) {
			unset( $GLOBALS['ec_test_options'][ $args[0] ] );
			return 1;
		}
		return 0;
	}
}
$wpdb = new EcBookingTestDb();
function get_posts( $query ) {
	return array_values( array_filter( $GLOBALS['ec_test_posts'], function ( $post ) use ( $query ) {
		return in_array( $post->post_name, $query['post_name__in'], true ) && $post->post_type === $query['post_type'];
	} ) );
}
function wp_insert_post( $data, $wp_error = false ) {
	++$GLOBALS['ec_test_insert_count'];
	if ( $GLOBALS['ec_test_fail_insert'] ) { return new WP_Error( 'db_insert_error', 'Test failure' ); }
	$data = wp_unslash( $data );
	$data['ID'] = count( $GLOBALS['ec_test_posts'] ) + 1;
	$GLOBALS['ec_test_posts'][] = (object) $data;
	return $data['ID'];
}

require dirname( __DIR__ ) . '/inc/dat-lich-kham.php';

$assertions = 0;
function ec_expect( $condition, $message ) {
	global $assertions;
	++$assertions;
	if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $message . "\n" ); exit( 1 ); }
}
function ec_expect_error( $value, $code, $message ) { ec_expect( is_wp_error( $value ) && $value->code === $code, $message ); }

$now = new DateTimeImmutable( '2026-09-10 10:15:00', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
$valid = array( 'name' => 'Nguyễn Thị Ánh', 'phone' => '+84 912 345 678', 'date' => '2026-09-11', 'time' => '07:30', 'consent' => '1', 'website' => '', 'request_id' => '123e4567-e89b-42d3-a456-426614174000' );
$schedule = ec_booking_schedule( $now );
ec_expect( $schedule['slots'][0] === '07:30' && end( $schedule['slots'] ) === '17:30' && count( $schedule['slots'] ) === 21, 'Opening included; closing excluded; 30-minute slots' );
ec_expect( $schedule['now'] === '2026-09-10T10:15:00+07:00' && $schedule['maxDate'] === '2026-12-09', 'Explicit hospital timezone and inclusive 90-day horizon' );
$utc = ec_booking_schedule( new DateTimeImmutable( '2026-09-10 18:00:00', new DateTimeZone( 'UTC' ) ) );
ec_expect( $utc['today'] === '2026-09-11', 'UTC rollover uses Vietnamese calendar date' );
$custom = ec_booking_schedule( $now, array( 'gio_mo' => '08:15', 'gio_dong' => '17:15' ) );
ec_expect( $custom['slots'][0] === '08:15' && end( $custom['slots'] ) === '16:45', 'Slots follow configured opening, not hardcoded hours' );
ec_expect_error( ec_booking_schedule( $now, array( 'gio_mo' => '18:00', 'gio_dong' => '07:30' ) ), 'hours', 'Invalid hours fail closed' );
$booking = ec_booking_validate( $valid, $now );
ec_expect( ! is_wp_error( $booking ) && $booking['phone'] === '0912345678', 'Accept Vietnamese name and normalize +84 phone' );
ec_expect( ! is_wp_error( ec_booking_validate( array_merge( $valid, array( 'name' => "Anne O'Connor" ) ), $now ) ), 'Apostrophe in real names accepted' );
ec_expect( ! is_wp_error( ec_booking_validate( array_merge( $valid, array( 'date' => '2026-09-12' ) ), $now ) ), 'Hospital open weekends' );
ec_expect( ! is_wp_error( ec_booking_validate( array_merge( $valid, array( 'date' => '2026-12-09', 'time' => '17:30' ) ), $now ) ), 'Last allowed date and last slot accepted' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'date' => '2026-12-10' ) ), $now ), 'date', 'Horizon overflow rejected' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'date' => '2026-09-09' ) ), $now ), 'date', 'Past calendar day rejected' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'date' => '2026-09-10', 'time' => '10:00' ) ), $now ), 'date', 'Earlier same-day time rejected' );
ec_expect( ! is_wp_error( ec_booking_validate( array_merge( $valid, array( 'date' => '2026-09-10', 'time' => '10:30' ) ), $now ) ), 'Future same-day slot accepted' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'time' => '18:00' ) ), $now ), 'time', 'Closing time rejected at server' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'time' => '07:00' ) ), $now ), 'time', 'Before opening rejected' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'time' => '08:15' ) ), $now ), 'time', 'Unlisted time rejected' );
foreach ( array( '2026-02-30', '2026-09-31', '2026-9-11', 'abcd-ef-gh' ) as $date ) {
	ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'date' => $date ) ), $now ), 'date', 'Malformed or impossible date: ' . $date );
}
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'time' => '24:00' ) ), $now ), 'date', 'Overflow hour rejected rather than normalized' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'time' => "08:\0" ) ), $now ), 'date', 'NUL in time rejected without PHP ValueError' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'date' => "2026-09-\0" ) ), $now ), 'date', 'NUL in date rejected without PHP ValueError' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'name' => '<script>alert(1)</script>' ) ), $now ), 'name', 'HTML rejected before persistence' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'name' => 'A' ) ), $now ), 'name', 'Too short name rejected' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'phone' => '090<script>' ) ), $now ), 'phone', 'Invalid phone rejected' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'phone' => '0000000000' ) ), $now ), 'phone', 'Placeholder phone rejected' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'consent' => '0' ) ), $now ), 'consent', 'Consent enforced on server' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'website' => 'bot' ) ), $now ), 'input', 'Honeypot rejected' );
foreach ( array_keys( $valid ) as $field ) {
	$missing = $valid;
	unset( $missing[ $field ] );
	ec_expect_error( ec_booking_validate( $missing, $now ), 'input', 'Missing field rejected: ' . $field );
	ec_expect_error( ec_booking_validate( array_merge( $valid, array( $field => array( 'bad' ) ) ), $now ), 'input', 'Array rejected without PHP fatal: ' . $field );
}
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'request_id' => 'not-a-uuid' ) ), $now ), 'request_id', 'Idempotency identifier validated' );
ec_booking_register();
$cpt = $GLOBALS['ec_test_cpt'];
ec_expect( ! $cpt['public'] && ! $cpt['publicly_queryable'] && ! $cpt['show_in_rest'] && $cpt['exclude_from_search'] && ! $cpt['can_export'], 'Requests cannot be publicly queried, searched, exported or read via REST' );
ec_expect( $cpt['capabilities']['read_post'] === 'manage_options' && $cpt['capabilities']['edit_posts'] === 'manage_options' && $cpt['capabilities']['create_posts'] === 'do_not_allow', 'Administrative access only' );
ec_expect( ec_booking_keep_private( array( 'post_type' => 'ec_appointment', 'post_status' => 'publish' ) )['post_status'] === 'private', 'Accidental publish forced private' );

$_SERVER['REMOTE_ADDR'] = '192.0.2.1';
$first = ec_booking_store( $booking );
ec_expect( $first === 1 && count( $GLOBALS['ec_test_posts'] ) === 1, 'First valid request saved' );
$stored = ec_booking_read( $GLOBALS['ec_test_posts'][0] );
ec_expect( $stored['name'] === $booking['name'] && $stored['phone'] === $booking['phone'] && $stored['consent'] === true, 'Complete data persisted in same row' );
ec_expect( $GLOBALS['ec_test_posts'][0]->post_status === 'private' && strpos( $GLOBALS['ec_test_posts'][0]->post_title, $booking['name'] ) === false, 'Storage private and title excludes patient identity' );
$retry = ec_booking_store( $booking );
ec_expect( $retry === $first && $GLOBALS['ec_test_insert_count'] === 1, 'Same request retry never inserts a duplicate' );
ec_expect_error( ec_booking_store( array_merge( $booking, array( 'time' => '08:00' ) ) ), 'request_conflict', 'Reused request ID cannot mutate existing details' );
$busy_booking = array_merge( $booking, array( 'request_id' => '123e4567-e89b-42d3-a456-426614174001' ) );
$busy_hash = hash_hmac( 'sha256', $busy_booking['request_id'], wp_salt( 'nonce' ) );
$busy_lock = ec_booking_lock( 'request:' . $busy_hash );
ec_expect_error( ec_booking_store( $busy_booking ), 'busy', 'Concurrent same-request submit rejected before any success' );
ec_booking_unlock( $busy_lock );
$GLOBALS['ec_test_fail_insert'] = true;
ec_expect_error( ec_booking_store( $busy_booking ), 'storage', 'Insert failure must never report success' );
ec_expect( count( $GLOBALS['ec_test_options'] ) === 0, 'Failure releases locks so retry is possible' );
$GLOBALS['ec_test_fail_insert'] = false;
ec_expect( ec_booking_store( $busy_booking ) === 2, 'Retry after storage failure can save once' );
ec_expect( count( $GLOBALS['ec_test_posts'] ) === 2, 'No partial or duplicate rows on failure path' );

$after_slot = new DateTimeImmutable( '2026-09-12 08:00:00', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
ec_expect_error( ec_booking_validate( $valid, $after_slot ), 'date', 'New bookings cannot use past slot' );
$structural = ec_booking_validate( $valid, $after_slot, null, false );
ec_expect( ! is_wp_error( $structural ) && ec_booking_replay( $structural ) === 1, 'Already saved exact replay succeeds after appointment time passes' );
ec_expect_error( ec_booking_replay( array_merge( $structural, array( 'name' => 'Nguyễn Văn Khác' ) ) ), 'request_conflict', 'Replay compares patient fields, not only ID' );
$GLOBALS['ec_test_posts'][0]->post_name .= '__trashed';
ec_expect( ec_booking_replay( $structural ) === 1, 'Trashed request does not create duplicate on retry' );
$GLOBALS['ec_test_posts'][0]->post_name = substr( $GLOBALS['ec_test_posts'][0]->post_name, 0, -9 );

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = array_merge( $valid, array( 'nonce' => 'test-nonce' ) );
try { ec_booking_ajax_submit(); ec_expect( false, 'AJAX must terminate response' ); }
catch ( EcBookingTestResponse $response ) {
	ec_expect( $response->response['success'] && array_keys( $response->response['data'] ) === array( 'message' ), 'AJAX replay success returns no PII or record ID' );
}
$_POST['nonce'] = 'invalid';
try { ec_booking_ajax_submit(); ec_expect( false, 'Bad nonce must terminate' ); }
catch ( EcBookingTestResponse $response ) {
	ec_expect( ! $response->response['success'] && $response->status === 403 && $response->response['data']['code'] === 'nonce', 'AJAX invalid nonce includes stable error code and HTTP403' );
}
$_POST['nonce'] = 'test-nonce';
$_POST['consent'] = '0';
try { ec_booking_ajax_submit(); ec_expect( false, 'Missing consent must terminate' ); }
catch ( EcBookingTestResponse $response ) {
	ec_expect( ! $response->response['success'] && $response->response['data']['code'] === 'consent', 'Replay still requires consent and valid scalar input' );
}
try { ec_booking_ajax_config(); ec_expect( false, 'Config must terminate' ); }
catch ( EcBookingTestResponse $response ) {
	ec_expect( $response->response['success'] && $response->response['data']['nonce'] === 'test-nonce' && isset( $response->response['data']['slots'], $response->response['data']['maxDate'] ), 'Config AJAX includes nonce and schedule contract' );
}

$GLOBALS['ec_test_transients'] = array();
for ( $i = 0; $i < 5; ++$i ) { ec_expect( ec_booking_rate_limit( '0912345678' ), 'First five hourly submissions allowed' ); }
ec_expect( ! ec_booking_rate_limit( '0912345678' ), 'Sixth request blocked' );
$_SERVER['REMOTE_ADDR'] = '192.0.2.2';
ec_expect( ! ec_booking_rate_limit( '0912345678' ), 'Phone limit survives changing IP' );
$_SERVER['REMOTE_ADDR'] = '192.0.2.1';
ec_expect( ! ec_booking_rate_limit( '0987654321' ), 'IP limit survives changing phone' );
ec_expect( strpos( implode( '|', array_keys( $GLOBALS['ec_test_transients'] ) ), '192.0.2.1' ) === false && strpos( implode( '|', array_keys( $GLOBALS['ec_test_transients'] ) ), '0912345678' ) === false, 'Rate records contain salted hashes, no raw IP or phone' );
ec_expect( count( $GLOBALS['ec_test_options'] ) === 0, 'All request/rate locks released after work' );

echo 'PASS: ' . $assertions . " booking validation, privacy, storage, retry and rate-limit checks.\n";
