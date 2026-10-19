<?php
/** Integration harness: fake WordPress storage, Google HTTP, SMTP and Zalo only. No network. */
namespace PHPMailer\PHPMailer {
	#[\AllowDynamicProperties]
	class SMTP { public $Timelimit; }
	#[\AllowDynamicProperties]
	class PHPMailer {
		public static $sent = array();
		public function __construct( $exceptions = true ) {}
		public function isSMTP() {}
		public function getSMTPInstance() { return new SMTP(); }
		public function setFrom( $email, $name ) { $this->From = $email; }
		public function addAddress( $email ) { $this->To = $email; }
		public function isHTML( $html ) {}
		public function smtpClose() {}
		public function send() { self::$sent[] = clone $this; return true; }
	}
}
namespace {
define( 'ABSPATH', __DIR__ );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
class WP_Error {
	private $code; private $message; private $data;
	public function __construct( $code, $message, $data = null ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}
class EcSheetsTestStop extends \RuntimeException {}
class EcSheetsTestRequest {
	private $raw;
	public function __construct( $raw ) { $this->raw = $raw; }
	public function get_body() { return $this->raw; }
}
$GLOBALS['sheet_options'] = $GLOBALS['sheet_posts'] = $GLOBALS['sheet_meta'] = $GLOBALS['sheet_events'] = $GLOBALS['sheet_actions'] = $GLOBALS['sheet_transients'] = array();
$GLOBALS['sheet_http'] = $GLOBALS['sheet_zalo_sent'] = array();
$GLOBALS['sheet_reply_mode'] = 'valid';
$GLOBALS['sheet_schedule_fail'] = false;
$GLOBALS['sheet_insert_hook_snapshot'] = array();
$checks = 0;
function ec_sheet_expect( $ok, $label ) { global $checks; ++$checks; if ( ! $ok ) { fwrite( STDERR, 'FAIL: ' . $label . "\n" ); exit( 1 ); } }
function ec_sheet_error( $value, $code, $label ) { ec_sheet_expect( is_wp_error( $value ) && $code === $value->get_error_code(), $label ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['sheet_actions'][$hook][$priority][] = $callback; }
function do_action( $hook, ...$args ) {
	$priorities = $GLOBALS['sheet_actions'][$hook] ?? array(); ksort( $priorities );
	foreach ( $priorities as $callbacks ) { foreach ( $callbacks as $callback ) { call_user_func_array( $callback, $args ); } }
}
function add_filter( ...$args ) {}
function remove_filter( ...$args ) {}
function wp_salt( $scheme ) { return 'synthetic-sheets-test-salt'; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function wp_parse_url( $url ) { return parse_url( $url ); }
function wp_slash( $v ) { return is_array( $v ) ? array_map( 'wp_slash', $v ) : ( is_string( $v ) ? addslashes( $v ) : $v ); }
function wp_unslash( $v ) { return is_array( $v ) ? array_map( 'wp_unslash', $v ) : ( is_string( $v ) ? stripslashes( $v ) : $v ); }
function add_option( $key, $value, $deprecated = '', $autoload = false ) { if ( array_key_exists( $key, $GLOBALS['sheet_options'] ) ) { return false; } $GLOBALS['sheet_options'][$key] = $value; return true; }
function get_option( $key, $default = false ) { return $GLOBALS['sheet_options'][$key] ?? $default; }
function update_option( $key, $value, $autoload = false ) { if ( isset( $GLOBALS['sheet_options'][$key] ) && $GLOBALS['sheet_options'][$key] === $value ) { return false; } $GLOBALS['sheet_options'][$key] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['sheet_options'][$key] ); return true; }
function wp_cache_delete( ...$args ) {}
function set_transient( $key, $value, $ttl ) { $GLOBALS['sheet_transients'][$key] = $value; return true; }
function get_transient( $key ) { return $GLOBALS['sheet_transients'][$key] ?? false; }
function get_post( $id ) { return $GLOBALS['sheet_posts'][$id] ?? null; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['sheet_meta'][$id][$key] ?? ''; }
function update_post_meta( $id, $key, $value ) { if ( isset( $GLOBALS['sheet_meta'][$id][$key] ) && $GLOBALS['sheet_meta'][$id][$key] === $value ) { return false; } $GLOBALS['sheet_meta'][$id][$key] = $value; return true; }
function delete_post_meta( $id, $key ) { unset( $GLOBALS['sheet_meta'][$id][$key] ); return true; }
function get_posts( $query ) {
	$posts = array_values( array_filter( $GLOBALS['sheet_posts'], static function ( $p ) use ( $query ) {
		if ( $p->post_type !== $query['post_type'] ) { return false; }
		if ( isset( $query['post_name__in'] ) && ! in_array( $p->post_name, $query['post_name__in'], true ) ) { return false; }
		if ( isset( $query['meta_key'] ) && (string) $query['meta_value'] !== (string) get_post_meta( $p->ID, $query['meta_key'], true ) ) { return false; }
		return true;
	} ) );
	if ( isset( $query['posts_per_page'] ) ) { $posts = array_slice( $posts, ( max( 1, (int) ( $query['paged'] ?? 1 ) ) - 1 ) * $query['posts_per_page'], $query['posts_per_page'] ); }
	return 'ids' === ( $query['fields'] ?? '' ) ? array_map( static function ( $p ) { return $p->ID; }, $posts ) : $posts;
}
function wp_insert_post( $data, $error = false ) {
	$data = wp_unslash( $data ); $id = count( $GLOBALS['sheet_posts'] ) + 1;
	$data['ID'] = $id;
	$GLOBALS['sheet_posts'][$id] = (object) $data;
	foreach ( $data['meta_input'] ?? array() as $key => $value ) { update_post_meta( $id, $key, $value ); }
	$GLOBALS['sheet_insert_hook_snapshot'][$id] = json_decode( $data['post_content'], true );
	return $id;
}
class EcSheetsTestDb {
	public $options = 'fake_options';
	public function prepare( $sql, ...$args ) { return $args; }
	public function query( $args ) { if ( ( $GLOBALS['sheet_options'][$args[0]] ?? null ) === $args[1] ) { unset( $GLOBALS['sheet_options'][$args[0]] ); return 1; } return 0; }
}
$wpdb = new EcSheetsTestDb();
function wp_next_scheduled( $hook, $args = array() ) { return $GLOBALS['sheet_events'][$hook . ':' . json_encode( $args )] ?? false; }
function wp_schedule_single_event( $time, $hook, $args = array(), $error = false ) { if ( $GLOBALS['sheet_schedule_fail'] ) { return false; } $GLOBALS['sheet_events'][$hook . ':' . json_encode( $args )] = $time; return true; }
function wp_schedule_event( $time, $schedule, $hook, $args = array() ) { return wp_schedule_single_event( $time, $hook, $args ); }
function current_user_can( $cap ) { return true; }
function check_admin_referer( ...$args ) { return true; }
function wp_die( $message, $title = '', $args = array() ) { throw new EcSheetsTestStop( $message ); }
function absint( $v ) { return abs( (int) $v ); }
function is_email( $v ) { return is_string( $v ) && filter_var( $v, FILTER_VALIDATE_EMAIL ); }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function rest_url( $path ) { return 'https://example.test/wp-json/' . $path; }
function sanitize_text_field( $v ) { return strip_tags( $v ); }
function eyecare_du_lieu_thuc_the() { return array( 'gio_mo' => '07:30', 'gio_dong' => '18:00' ); }
function ec_sheet_response( $data, $code = 200, $headers = array() ) { return array( 'body' => is_string( $data ) ? $data : json_encode( $data ), 'response' => array( 'code' => $code ), 'headers' => $headers ); }
function ec_sheet_ack( $request ) {
	return array( 'ok' => true, 'spreadsheet_id' => ec_sheets_document_id(), 'sheet_id' => 0, 'key' => $request['key'] ?? '', 'row' => 2, 'fingerprint' => hash( 'sha256', json_encode( $request['values'] ?? array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS ) ) );
}
function wp_remote_post( $url, $args ) {
	$GLOBALS['sheet_http'][] = array( 'method' => 'POST', 'url' => $url, 'args' => $args );
	if ( str_starts_with( $url, 'https://bot-api.zaloplatforms.com/' ) ) {
		$GLOBALS['sheet_zalo_sent'][] = json_decode( $args['body'], true );
		return ec_sheet_response( array( 'ok' => true, 'result' => array( 'message_id' => 'synthetic-message-' . count( $GLOBALS['sheet_zalo_sent'] ) ) ) );
	}
	$envelope = json_decode( $args['body'], true );
	$request = json_decode( $envelope['payload'], true );
	ec_sheet_expect( hash_equals( hash_hmac( 'sha256', $envelope['payload'], ec_sheets_secret() ), $envelope['signature'] ), 'Every Google request has an HMAC in its JSON body' );
	ec_sheet_expect( 0 === $args['redirection'] && true === $args['sslverify'], 'Google POST verifies TLS and never blindly follows redirects' );
	$GLOBALS['sheet_last_request'] = $request;
	$ack = ec_sheet_ack( $request );
	switch ( $GLOBALS['sheet_reply_mode'] ) {
		case 'timeout': return new WP_Error( 'timeout', 'Synthetic timeout, no network' );
		case 'bad_json': return ec_sheet_response( '<html>Login</html>' );
		case 'wrong_document': $ack['spreadsheet_id'] = 'wrong'; break;
		case 'wrong_tab': $ack['sheet_id'] = 9; break;
		case 'wrong_key': $ack['key'] = 'wrong'; break;
		case 'wrong_fingerprint': $ack['fingerprint'] = str_repeat( '0', 64 ); break;
		case 'bad_row': $ack['row'] = 1; break;
		case 'refused': $ack['ok'] = false; break;
		case 'redirect': return ec_sheet_response( '', 302, array( 'location' => $GLOBALS['sheet_redirect'] ) );
	}
	return ec_sheet_response( $ack );
}
function wp_remote_get( $url, $args ) { $GLOBALS['sheet_http'][] = array( 'method' => 'GET', 'url' => $url, 'args' => $args ); return ec_sheet_response( ec_sheet_ack( $GLOBALS['sheet_last_request'] ) ); }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_header( $response, $name ) { return $response['headers'][$name] ?? ''; }

// Real crypto is required; PHP on the deployment host has OpenSSL enabled.
require dirname( __DIR__ ) . '/inc/dat-lich-kham.php';
require dirname( __DIR__ ) . '/inc/quan-ly-dat-lich.php';
require dirname( __DIR__ ) . '/inc/zalo-dat-lich.php';
require dirname( __DIR__ ) . '/inc/gmail-dat-lich.php';
require dirname( __DIR__ ) . '/inc/sheets-dat-lich.php';

$secret = str_repeat( 'a', 64 );
update_option( 'ec_booking_sheets', array( 'enabled' => true, 'changes_enabled' => true, 'endpoint' => 'https://script.google.com/macros/s/SyntheticDeploymentIdentifier_123456789/exec', 'cipher' => ec_zalo_encrypt( $secret ) ) );
update_option( 'ec_booking_zalo', array( 'enabled' => true, 'version' => 'zalo-synthetic-v1', 'cipher' => ec_zalo_encrypt( '123456789:synthetic-test-token' ), 'chat_ids' => array( 'staff.one', 'staff.two' ), 'candidates' => array( 'staff.one' => 'Synthetic staff one', 'staff.two' => 'Synthetic staff two' ), 'candidate_chat_types' => array( 'staff.one' => 'PRIVATE', 'staff.two' => 'PRIVATE' ) ) );
update_option( 'ec_booking_gmail', array( 'enabled' => true, 'version' => 'gmail-synthetic-v1', 'username' => 'hospital@example.test', 'recipient' => 'staff@example.test', 'cipher' => ec_zalo_encrypt( 'abcdefghijklmnop' ) ) );
$booking = array( 'name' => 'Nguyễn Văn Kiểm Thử', 'phone' => '0912345678', 'date' => ec_booking_now()->modify( '+1 day' )->format( 'Y-m-d' ), 'time' => '08:00', 'request_id' => '123e4567-e89b-42d3-a456-426614174000', 'consent' => true, 'public_share_consent' => false );
$_SERVER['REMOTE_ADDR'] = '192.0.2.20';
$id = ec_booking_store( $booking );
ec_sheet_expect( 1 === $id && true === $GLOBALS['sheet_insert_hook_snapshot'][$id]['sheet_required'], 'Sheet requirement is immutable in the inserted appointment before notification hooks' );
ec_sheet_expect( '1' === get_post_meta( $id, '_ec_sheet_recovery', true ), 'Durable recovery marker is part of the same insert' );
ec_sheet_expect( ec_booking_contact_consent_text() === ec_booking_read( get_post( $id ) )['consent_text'], 'Stored consent text matches the displayed checkbox' );
ec_sheet_expect( 'waiting_sheet' === get_post_meta( $id, '_ec_gmail_state', true ) && 'waiting_sheet' === get_post_meta( $id, '_ec_zalo_state', true ), 'Both channels wait for a successful Sheet acknowledgement' );
ec_sheet_expect( empty( $GLOBALS['sheet_http'] ) && empty( \PHPMailer\PHPMailer\PHPMailer::$sent ), 'Submitting form does not send to Google, Zalo or SMTP synchronously' );

foreach ( array( 'timeout', 'bad_json', 'wrong_document', 'wrong_tab', 'wrong_key', 'wrong_fingerprint', 'bad_row', 'refused' ) as $mode ) {
	$GLOBALS['sheet_reply_mode'] = $mode;
	ec_sheets_deliver( $id ); ec_gmail_queue( $id ); ec_zalo_queue( $id ); ec_gmail_deliver( $id ); ec_zalo_deliver( $id );
	ec_sheet_expect( ! ec_booking_notifications_ready( $id ), 'Bad or missing ACK cannot open notification gate: ' . $mode );
	ec_sheet_expect( empty( $GLOBALS['sheet_zalo_sent'] ) && empty( \PHPMailer\PHPMailer\PHPMailer::$sent ), 'Bad or missing ACK produces no staff notifications: ' . $mode );
}
// A stale scheduled channel job is also blocked at its final delivery boundary.
foreach ( array( 'gmail', 'zalo' ) as $channel ) {
	update_post_meta( $id, '_ec_' . $channel . '_state', 'pending' ); call_user_func( 'ec_' . $channel . '_deliver', $id );
	ec_sheet_expect( 'waiting_sheet' === get_post_meta( $id, '_ec_' . $channel . '_state', true ), 'Worker independently blocks unacknowledged Sheet: ' . $channel );
	$_GET['appointment'] = (string) $id;
	try { call_user_func( 'ec_' . $channel . '_retry' ); ec_sheet_expect( false, 'Manual retry must stop before sending' ); }
	catch ( EcSheetsTestStop $stop ) { ec_sheet_expect( str_contains( $stop->getMessage(), 'Google Sheet' ), 'Manual retry cannot bypass Sheet: ' . $channel ); }
}

$GLOBALS['sheet_events'] = array();
$count_before = count( $GLOBALS['sheet_posts'] );
ec_sheet_expect( $id === ec_booking_replay( $booking ) && count( $GLOBALS['sheet_posts'] ) === $count_before && false !== wp_next_scheduled( 'ec_sheets_deliver', array( $id ) ), 'Form replay recovers missing outbox scheduling without another appointment' );
$GLOBALS['sheet_reply_mode'] = 'valid';
ec_sheets_deliver( $id );
ec_sheet_expect( ec_booking_notifications_ready( $id ) && 'saved' === get_post_meta( $id, '_ec_sheet_state', true ), 'Matching document, tab, key, row and payload fingerprint release the gate' );
ec_sheet_expect( 'pending' === get_post_meta( $id, '_ec_gmail_state', true ) && 'pending' === get_post_meta( $id, '_ec_zalo_state', true ), 'ACK queues both previously waiting channels' );
ec_gmail_deliver( $id ); ec_zalo_deliver( $id );
$mail_count = count( \PHPMailer\PHPMailer\PHPMailer::$sent ); $zalo_count = count( $GLOBALS['sheet_zalo_sent'] ); $http_count = count( $GLOBALS['sheet_http'] );
ec_sheet_expect( 1 === $mail_count && 2 === $zalo_count, 'One email and one message per configured private Zalo recipient after ACK' );
ec_sheets_deliver( $id ); ec_sheets_release( $id ); ec_booking_replay( $booking ); ec_gmail_deliver( $id ); ec_zalo_deliver( $id );
ec_sheet_expect( $mail_count === count( \PHPMailer\PHPMailer\PHPMailer::$sent ) && $zalo_count === count( $GLOBALS['sheet_zalo_sent'] ) && $http_count === count( $GLOBALS['sheet_http'] ), 'Repeated ACK, workers and form replay never resend or rewrite the Sheet' );
ec_sheet_expect( '' === get_post_meta( $id, '_ec_sheet_recovery', true ), 'Completed channel dispatch removes recovery marker' );

// Requirements stay with the request even if settings are switched off after submission.
$second = $booking; $second['request_id'] = '123e4567-e89b-42d3-a456-426614174001'; $second['phone'] = '0987654321';
$second_id = ec_booking_store( $second );
$settings = ec_sheets_settings(); $settings['enabled'] = false; update_option( 'ec_booking_sheets', $settings );
ec_sheets_deliver( $second_id ); ec_gmail_queue( $second_id ); ec_zalo_queue( $second_id );
ec_sheet_expect( ! ec_booking_notifications_ready( $second_id ) && 'failed' === get_post_meta( $second_id, '_ec_sheet_state', true ), 'Disabling configuration cannot grandfather an already Sheet-required appointment' );
ec_sheet_expect( $mail_count === count( \PHPMailer\PHPMailer\PHPMailer::$sent ) && $zalo_count === count( $GLOBALS['sheet_zalo_sent'] ), 'Disabled Sheet configuration does not leak a booking to notifications' );
$settings['enabled'] = true; update_option( 'ec_booking_sheets', $settings );

// Empty/legacy channel states release; uncertain or failed outcomes never auto-resend.
update_post_meta( $second_id, '_ec_sheet_state', 'saved' ); update_post_meta( $second_id, '_ec_sheet_document', ec_sheets_document_id() );
update_post_meta( $second_id, '_ec_gmail_state', 'unknown' ); update_post_meta( $second_id, '_ec_zalo_state', 'failed' );
ec_sheets_release( $second_id );
ec_sheet_expect( 'unknown' === get_post_meta( $second_id, '_ec_gmail_state', true ) && 'failed' === get_post_meta( $second_id, '_ec_zalo_state', true ), 'Recovery preserves uncertain and failed prior sends for manual investigation' );
$legacy = clone get_post( $id ); $legacy->ID = 3; $legacy_data = ec_booking_read( $legacy ); unset( $legacy_data['sheet_required'] ); $legacy->post_content = json_encode( $legacy_data ); $legacy->post_name = 'legacy-record'; $GLOBALS['sheet_posts'][3] = $legacy;
ec_sheet_expect( ec_booking_notifications_ready( 3 ), 'Legacy appointments without a Sheet requirement keep their historical behavior' );

// Redirects carry no sensitive request body and cannot leave the fixed Google host.
$GLOBALS['sheet_reply_mode'] = 'redirect';
foreach ( array( 'https://evil.example/response', 'https://script.googleusercontent.com.evil.example/response', 'http://script.googleusercontent.com/response', 'https://user@script.googleusercontent.com/response', 'https://script.googleusercontent.com:443/response' ) as $bad_url ) {
	$GLOBALS['sheet_redirect'] = $bad_url; $before = count( $GLOBALS['sheet_http'] );
	ec_sheet_error( ec_sheets_request( array( 'action' => 'probe' ) ), 'redirect', 'Unsafe response redirect rejected: ' . $bad_url );
	ec_sheet_expect( count( $GLOBALS['sheet_http'] ) === $before + 1, 'Unsafe redirect never causes a second HTTP request' );
}
$GLOBALS['sheet_redirect'] = 'https://script.googleusercontent.com/macros/echo?user_content_key=synthetic';
ec_sheet_expect( ! is_wp_error( ec_sheets_request( array( 'action' => 'probe' ) ) ), 'Expected one-time ContentService redirect accepted' );
$get = end( $GLOBALS['sheet_http'] );
ec_sheet_expect( 'GET' === $get['method'] && ! isset( $get['args']['body'], $get['args']['headers'] ) && 0 === $get['args']['redirection'], 'Redirect GET strips request body and signature and forbids further redirects' );

function ec_sheet_event_request( $overrides = array(), $signature_ok = true ) {
	$d = array_merge( array( 'spreadsheet_id' => ec_sheets_document_id(), 'sheet_id' => 0, 'issued_at' => time(), 'event_id' => 'synthetic-event-0001', 'kind' => 'insert', 'changed_rows' => 1, 'range' => 'A2:I2' ), $overrides );
	$raw = json_encode( $d );
	return new EcSheetsTestRequest( json_encode( array( 'payload' => $raw, 'signature' => $signature_ok ? hash_hmac( 'sha256', $raw, ec_sheets_secret() ) : str_repeat( '0', 64 ) ) ) );
}
$before = count( $GLOBALS['sheet_events'] );
ec_sheet_error( ec_sheets_change( ec_sheet_event_request( array(), false ) ), 'auth', 'Forged Sheet callback rejected before queueing' );
foreach ( array( array( 'issued_at' => time() - 301 ), array( 'issued_at' => time() + 301 ), array( 'spreadsheet_id' => 'wrong' ), array( 'sheet_id' => 1 ), array( 'changed_rows' => 0 ), array( 'range' => "A1\nphone:0912345678" ), array( 'event_id' => 'short' ) ) as $bad_event ) {
	ec_sheet_error( ec_sheets_change( ec_sheet_event_request( $bad_event ) ), 'input', 'Malformed or stale authenticated Sheet callback rejected' );
}
ec_sheet_expect( $before === count( $GLOBALS['sheet_events'] ), 'Rejected callback events never enter a delivery queue' );
$event_result = ec_sheets_change( ec_sheet_event_request() );
ec_sheet_expect( ! is_wp_error( $event_result ) && true === $event_result['ok'], 'Valid signed metadata callback is accepted' );
$events_before_replay = count( $GLOBALS['sheet_events'] );
ec_sheets_change( ec_sheet_event_request() );
ec_sheet_expect( $events_before_replay === count( $GLOBALS['sheet_events'] ), 'Same callback event is durable and deduplicated' );
$event_key = '_ec_sheet_change_' . hash( 'sha256', 'synthetic-event-0001' );
$GLOBALS['sheet_reply_mode'] = 'valid';
ec_sheets_change_deliver( $event_key ); ec_sheets_change_deliver( $event_key );
ec_sheet_expect( count( $GLOBALS['sheet_zalo_sent'] ) === $zalo_count + 2 && 'sent' === get_option( $event_key )['state'], 'Change callback sends only once per configured Zalo recipient' );
$change_message = end( $GLOBALS['sheet_zalo_sent'] )['text'];
ec_sheet_expect( ! str_contains( $change_message, $booking['name'] ) && ! str_contains( $change_message, $booking['phone'] ) && str_contains( $change_message, ec_sheets_document_id() ), 'Change notification contains metadata and the fixed Sheet link without patient rows' );
ec_sheet_expect( $mail_count === count( \PHPMailer\PHPMailer\PHPMailer::$sent ), 'Ordinary Sheet changes do not send appointment emails' );

// A queued change must not silently switch to newly configured Zalo recipients.
$changed_event_id = 'synthetic-event-config-changed';
ec_sheet_expect( ! is_wp_error( ec_sheets_change( ec_sheet_event_request( array( 'event_id' => $changed_event_id, 'kind' => 'update' ) ) ) ), 'Queue a change event with the current notification configuration' );
$changed_event_key = '_ec_sheet_change_' . hash( 'sha256', $changed_event_id );
$change_count_before = count( $GLOBALS['sheet_zalo_sent'] );
$zalo_settings = ec_zalo_settings(); $zalo_settings['version'] = 'zalo-synthetic-v2'; update_option( 'ec_booking_zalo', $zalo_settings );
ec_sheets_change_deliver( $changed_event_key );
ec_sheet_expect( 'skipped' === get_option( $changed_event_key )['state'] && $change_count_before === count( $GLOBALS['sheet_zalo_sent'] ), 'Configuration changed after callback reception skips delivery instead of switching recipients' );
echo 'PASS: ' . $checks . " Sheet integration checks; no real HTTP, email, Zalo or customer data used.\n";
}
