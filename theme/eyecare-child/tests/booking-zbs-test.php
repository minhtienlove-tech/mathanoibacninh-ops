<?php
/** Isolated patient ZBS outbox tests. No network, credentials, patients or live messages. */
define( 'ABSPATH', __DIR__ );
define( 'HOUR_IN_SECONDS', 3600 );
class WP_Error {
	private $code; private $message;
	public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function zbs_expect( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } $GLOBALS['zbs_checks']++; }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function get_option( $key, $default = false ) { return $GLOBALS['zbs_options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = false ) { if ( 'ec_booking_zbs' === $key && ! empty( $GLOBALS['zbs_option_fail'] ) ) { return false; } $GLOBALS['zbs_options'][ $key ] = $value; return true; }
function get_post( $id ) { return $GLOBALS['zbs_posts'][ $id ] ?? null; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['zbs_meta'][ $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['zbs_meta'][ $id ][ $key ] = $value; return true; }
function add_action( $hook, $callback, $priority = 10 ) { $GLOBALS['zbs_hooks'][ $hook ][] = $callback; }
function apply_filters( $hook, $value ) {
	if ( 'ec_zbs_now' === $hook ) { return $GLOBALS['zbs_now']; }
	if ( 'ec_zbs_access_token' === $hook ) { return $GLOBALS['zbs_provider'] ?? null; }
	if ( 'ec_zbs_send_template' === $hook ) { return $GLOBALS['zbs_send_override'] ?? null; }
	return $value;
}
function ec_booking_read( $post ) { return json_decode( $post->post_content, true ); }
function ec_booking_lock( $key ) { if ( isset( $GLOBALS['zbs_locks'][ $key ] ) ) { return false; } $GLOBALS['zbs_locks'][ $key ] = true; return $key; }
function ec_booking_unlock( $key ) { unset( $GLOBALS['zbs_locks'][ $key ] ); }
function wp_next_scheduled( $hook, $args ) { return $GLOBALS['zbs_events'][ $hook . ':' . $args[0] ] ?? false; }
function wp_schedule_single_event( $when, $hook, $args, $wp_error = false ) {
	if ( ! empty( $GLOBALS['zbs_schedule_fail'] ) ) { return new WP_Error( 'schedule', 'simulated scheduling failure' ); }
	$GLOBALS['zbs_events'][ $hook . ':' . $args[0] ] = $when; return true;
}
function wp_unschedule_event( $when, $hook, $args ) { unset( $GLOBALS['zbs_events'][ $hook . ':' . $args[0] ] ); return true; }
function ec_zalo_encrypt( $secret ) { return 'enc:' . $secret; }
function ec_zalo_token( $settings ) { return str_starts_with( $settings['cipher'] ?? '', 'enc:' ) ? substr( $settings['cipher'], 4 ) : ''; }
function eyecare_dia_chi_day_du() { return 'Địa chỉ thử nghiệm'; }
function eyecare_hotline_hien() { return '0868899396'; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function wp_remote_post( $url, $args ) {
	$GLOBALS['zbs_http'][] = array( $url, $args );
	return array_shift( $GLOBALS['zbs_http_queue'] );
}
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function zbs_response( $code, $body ) { return array( 'code' => $code, 'body' => json_encode( $body ) ); }

require dirname( __DIR__ ) . '/inc/zbs-benh-nhan.php';
$GLOBALS['zbs_checks'] = 0;
$GLOBALS['zbs_now'] = ( new DateTimeImmutable( '2026-10-26 08:00', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) )->getTimestamp();
$GLOBALS['zbs_options'] = array(); $GLOBALS['zbs_posts'] = array(); $GLOBALS['zbs_meta'] = array();
$GLOBALS['zbs_events'] = array(); $GLOBALS['zbs_hooks'] = array(); $GLOBALS['zbs_locks'] = array();
$GLOBALS['zbs_http'] = array(); $GLOBALS['zbs_http_queue'] = array();
$GLOBALS['zbs_provider'] = array( 'access_token' => 'mock-access-token-123', 'expires_at' => $GLOBALS['zbs_now'] + 7200 );
function zbs_post( $id, $hour, $consent = true ) {
	$GLOBALS['zbs_posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => 'ec_appointment', 'post_status' => 'private', 'post_content' => json_encode( array( 'name' => 'Người bệnh thử', 'phone' => '0987654321', 'date' => '2026-10-26', 'time' => $hour, 'patient_zalo_consent' => $consent ) ) );
	$GLOBALS['zbs_meta'][ $id ]['_ec_booking_status'] = 'confirmed';
}
$settings = array( 'enabled' => true, 'template_confirm_id' => '657033',
	'confirm_map' => array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' ) );
zbs_post( 1, '10:00' );
ec_zbs_enqueue( 1 );
zbs_expect( 'disabled' === get_post_meta( 1, '_ec_zbs_enrolled' ) && 0 === count( $GLOBALS['zbs_events'] ), 'Default disabled never queues patient messages' );
update_option( 'ec_booking_zbs', $settings );
zbs_post( 2, '10:00', false );
ec_zbs_enqueue( 2 );
zbs_expect( '' === get_post_meta( 2, '_ec_zbs_enrolled' ) && 0 === count( $GLOBALS['zbs_events'] ), 'No patient consent never queues' );
zbs_post( 3, '10:00' );
ec_zbs_enqueue( 3 );
$due = ( new DateTimeImmutable( '2026-10-26 09:00', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) )->getTimestamp();
zbs_expect( '1' === get_post_meta( 3, '_ec_zbs_enrolled' ) && wp_next_scheduled( 'ec_zbs_confirm_deliver', array( 3 ) ) && ! wp_next_scheduled( 'ec_zbs_reminder_deliver', array( 3 ) ), 'New consented booking queues only confirmation' );
ec_zbs_enqueue( 3 ); ec_zbs_recover( 3 );
zbs_expect( 1 === count( $GLOBALS['zbs_events'] ), 'Repeated requests do not queue duplicates' );
$GLOBALS['zbs_http_queue'][] = zbs_response( 200, array( 'error' => 0, 'data' => array( 'msg_id' => 'confirm-3' ) ) );
ec_zbs_deliver( 3, 'confirm' );
$call = end( $GLOBALS['zbs_http'] );
$body = json_decode( $call[1]['body'], true );
zbs_expect( 'accepted' === get_post_meta( 3, '_ec_zbs_confirm_state' ) && 'confirm-3' === get_post_meta( 3, '_ec_zbs_confirm_msg_id' ), 'Provider acceptance saved with message ID' );
zbs_expect( 'https://business.openapi.zalo.me/message/template' === $call[0] && '84987654321' === $body['phone'] && 'ec3c' === $body['tracking_id'] && '657033' === $body['template_id'] && array( 'schedule_time' => '10:00:00 26/10/2026', 'address' => 'Địa chỉ thử nghiệm', 'customer_name' => 'Người bệnh thử', 'booking_code' => '3' ) === $body['template_data'], 'Official endpoint, international phone and exact approved template variables used' );
$sent = count( $GLOBALS['zbs_http'] ); ec_zbs_deliver( 3, 'confirm' );
zbs_expect( $sent === count( $GLOBALS['zbs_http'] ), 'Accepted confirmation is not sent twice' );
$sent = count( $GLOBALS['zbs_http'] );
update_post_meta( 3, '_ec_zbs_reminder_state', 'pending' );
ec_zbs_deliver( 3, 'reminder' );
zbs_expect( 'skipped' === get_post_meta( 3, '_ec_zbs_reminder_state' ) && $sent === count( $GLOBALS['zbs_http'] ), 'Retired reminder event cannot send' );
$GLOBALS['zbs_now'] = ( new DateTimeImmutable( '2026-10-26 08:00', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) )->getTimestamp();
zbs_post( 4, '11:00' ); ec_zbs_enqueue( 4 );
update_post_meta( 4, '_ec_booking_status', 'cancelled' ); ec_zbs_status_changed( 4 );
zbs_expect( 'skipped' === get_post_meta( 4, '_ec_zbs_confirm_state' ) && ! wp_next_scheduled( 'ec_zbs_confirm_deliver', array( 4 ) ), 'Cancellation removes queued confirmation' );
$sent = count( $GLOBALS['zbs_http'] ); ec_zbs_deliver( 4, 'confirm' );
zbs_expect( $sent === count( $GLOBALS['zbs_http'] ), 'Cancelled appointment cannot send confirmation' );
zbs_post( 5, '08:30' ); ec_zbs_enqueue( 5 );
zbs_expect( 'pending' === get_post_meta( 5, '_ec_zbs_confirm_state' ) && ! wp_next_scheduled( 'ec_zbs_reminder_deliver', array( 5 ) ), 'Near-term booking still queues confirmation only' );
zbs_post( 6, '11:00' ); ec_zbs_enqueue( 6 );
$GLOBALS['zbs_http_queue'][] = new WP_Error( 'timeout', 'simulated private detail' );
ec_zbs_deliver( 6, 'confirm' );
$sent = count( $GLOBALS['zbs_http'] ); ec_zbs_deliver( 6, 'confirm' );
zbs_expect( 'unknown' === get_post_meta( 6, '_ec_zbs_confirm_state' ) && $sent === count( $GLOBALS['zbs_http'] ), 'Ambiguous timeout is never retried automatically' );
zbs_post( 7, '11:00' ); ec_zbs_enqueue( 7 );
$GLOBALS['zbs_http_queue'][] = zbs_response( 200, array( 'error' => -131, 'message' => 'private provider detail' ) );
ec_zbs_deliver( 7, 'confirm' );
zbs_expect( 'failed' === get_post_meta( 7, '_ec_zbs_confirm_state' ) && ! str_contains( get_post_meta( 7, '_ec_zbs_confirm_error' ), 'private' ), 'Explicit refusal is safe to inspect and does not expose provider detail' );
$bad = $settings; $bad['confirm_map'] = array( 'patient_name' => 'unapproved_data' );
zbs_expect( is_wp_error( ec_zbs_template_payload( 'confirm', get_post( 7 ), ec_booking_read( get_post( 7 ) ), $bad ) ), 'Unapproved template value cannot escape whitelist' );
zbs_expect( '657033' === ec_zbs_template_id( 'confirm', $settings ) && '' === ec_zbs_template_id( 'confirm', array( 'template_confirm_id' => 'bad/id' ) ), 'Approved template ID accepted but unsafe IDs rejected' );
$long_name = ec_booking_read( get_post( 7 ) ); $long_name['name'] = str_repeat( 'A', 31 );
zbs_expect( is_wp_error( ec_zbs_template_payload( 'confirm', get_post( 7 ), $long_name, $settings ) ), 'Name exceeding approved 30-character limit is rejected before sending' );
zbs_post( 12, '15:00' );
$GLOBALS['zbs_posts'][12]->post_content = json_encode( $long_name );
ec_zbs_enqueue( 12 );
zbs_expect( 'failed' === get_post_meta( 12, '_ec_zbs_confirm_state' ) && ! wp_next_scheduled( 'ec_zbs_confirm_deliver', array( 12 ) ), 'Oversized patient name is flagged for staff and never queued' );
$invalid_slot = ec_booking_read( get_post( 7 ) ); $invalid_slot['date'] = '2026-02-31';
zbs_expect( is_wp_error( ec_zbs_template_payload( 'confirm', get_post( 7 ), $invalid_slot, $settings ) ), 'Invalid appointment date cannot enter approved schedule_time field' );

// OAuth refresh stores the new access and one-use refresh token together.
$GLOBALS['zbs_provider'] = null;
$oauth = $settings + array();
$oauth['app_id'] = '123456789';
$oauth['secret_key_cipher'] = ec_zalo_encrypt( 'mock-secret-key-123' );
$oauth['refresh_token_cipher'] = ec_zalo_encrypt( 'old-refresh-token-123' );
$oauth['access_token_cipher'] = ec_zalo_encrypt( 'old-access-token-123' );
$oauth['access_token_expires_at'] = $GLOBALS['zbs_now'] - 1;
update_option( 'ec_booking_zbs', $oauth );
$GLOBALS['zbs_http_queue'][] = zbs_response( 200, array( 'access_token' => 'new-access-token-123', 'refresh_token' => 'new-refresh-token-123', 'expires_in' => 90000 ) );
$token = ec_zbs_access_token();
$call = end( $GLOBALS['zbs_http'] );
zbs_expect( 'new-access-token-123' === $token && 'https://oauth.zaloapp.com/v4/oa/access_token' === $call[0] && str_contains( $call[1]['body'], 'grant_type=refresh_token' ), 'Official OAuth refresh request accepted' );
zbs_expect( 'new-refresh-token-123' === ec_zbs_secret( ec_zbs_settings(), 'refresh_token_cipher' ) && ec_zbs_settings()['access_token_expires_at'] > $GLOBALS['zbs_now'], 'Rotated token pair saved together' );
$oauth = ec_zbs_settings(); $oauth['access_token_expires_at'] = $GLOBALS['zbs_now'] - 1; update_option( 'ec_booking_zbs', $oauth );
$GLOBALS['zbs_http_queue'][] = new WP_Error( 'timeout', 'private token detail' );
$before = count( $GLOBALS['zbs_http'] );
zbs_expect( is_wp_error( ec_zbs_access_token() ) && 'unknown' === ec_zbs_settings()['token_state'], 'Unknown refresh result fails closed' );
zbs_expect( is_wp_error( ec_zbs_access_token() ) && count( $GLOBALS['zbs_http'] ) === $before + 1, 'One-use refresh token is not retried after unknown result' );
update_option( 'ec_booking_zbs_token_unsafe', false );
$oauth = ec_zbs_settings(); $oauth['token_state'] = ''; update_option( 'ec_booking_zbs', $oauth );
$GLOBALS['zbs_option_fail'] = true;
$GLOBALS['zbs_http_queue'][] = zbs_response( 200, array( 'access_token' => 'rotated-access-123', 'refresh_token' => 'rotated-refresh-123', 'expires_in' => 90000 ) );
$before = count( $GLOBALS['zbs_http'] );
zbs_expect( is_wp_error( ec_zbs_access_token() ) && get_option( 'ec_booking_zbs_token_unsafe', false ) === true, 'Failed persistence of consumed refresh token locks the OAuth chain' );
$GLOBALS['zbs_option_fail'] = false;
zbs_expect( is_wp_error( ec_zbs_access_token() ) && count( $GLOBALS['zbs_http'] ) === $before + 1, 'Unsafe OAuth chain never reuses the consumed refresh token' );
update_option( 'ec_booking_zbs_token_unsafe', false );
// A lost single event may be restored only while the appointment is still due.
$GLOBALS['zbs_provider'] = array( 'access_token' => 'mock-access-token-123', 'expires_at' => $GLOBALS['zbs_now'] + 7200 );
update_option( 'ec_booking_zbs', $settings );
zbs_post( 8, '12:00' ); ec_zbs_enqueue( 8 );
wp_unschedule_event( wp_next_scheduled( 'ec_zbs_confirm_deliver', array( 8 ) ), 'ec_zbs_confirm_deliver', array( 8 ) );
$GLOBALS['zbs_schedule_fail'] = true;
ec_zbs_recover( 8 );
zbs_expect( 'failed' === get_post_meta( 8, '_ec_zbs_confirm_state' ), 'Recovery records a WP_Error scheduling failure instead of leaving a false pending state' );
$GLOBALS['zbs_schedule_fail'] = false;
$GLOBALS['zbs_now'] = ( new DateTimeImmutable( '2026-10-26 11:02', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) )->getTimestamp();
$GLOBALS['zbs_provider']['expires_at'] = $GLOBALS['zbs_now'] + 7200;
zbs_post( 9, '13:00' ); ec_zbs_enqueue( 9 );
$before_post = clone get_post( 9 );
$changed = ec_booking_read( get_post( 9 ) ); $changed['time'] = '14:00';
$GLOBALS['zbs_posts'][9]->post_content = json_encode( $changed );
ec_zbs_post_updated( 9, get_post( 9 ), $before_post );
zbs_expect( ! wp_next_scheduled( 'ec_zbs_reminder_deliver', array( 9 ) ) && wp_next_scheduled( 'ec_zbs_confirm_deliver', array( 9 ) ), 'WordPress reschedule cannot create a reminder' );

// A busy OAuth refresh happens before the send request and can be retried safely.
$GLOBALS['zbs_provider'] = null;
$oauth = ec_zbs_settings();
$oauth['app_id'] = '123456789';
$oauth['secret_key_cipher'] = ec_zalo_encrypt( 'mock-secret-key-123' );
$oauth['refresh_token_cipher'] = ec_zalo_encrypt( 'fresh-refresh-token-123' );
$oauth['access_token_cipher'] = '';
$oauth['access_token_expires_at'] = 0;
$oauth['token_state'] = '';
update_option( 'ec_booking_zbs', $oauth );
zbs_post( 10, '14:00' ); ec_zbs_enqueue( 10 );
wp_unschedule_event( wp_next_scheduled( 'ec_zbs_confirm_deliver', array( 10 ) ), 'ec_zbs_confirm_deliver', array( 10 ) );
$token_lock = ec_booking_lock( 'zbs-token' );
$before = count( $GLOBALS['zbs_http'] );
ec_zbs_deliver( 10, 'confirm' );
zbs_expect( 'pending' === get_post_meta( 10, '_ec_zbs_confirm_state' ) && wp_next_scheduled( 'ec_zbs_confirm_deliver', array( 10 ) ) === $GLOBALS['zbs_now'] + 20 && count( $GLOBALS['zbs_http'] ) === $before, 'Busy OAuth lock requeues without contacting ZBS or losing confirmation' );
ec_booking_unlock( $token_lock );

zbs_post( 11, '11:30' ); ec_zbs_enqueue( 11 );
zbs_expect( 'pending' === get_post_meta( 11, '_ec_zbs_confirm_state' ) && ! wp_next_scheduled( 'ec_zbs_reminder_deliver', array( 11 ) ), 'Near-term booking has confirmation without reminder' );
$before_post = clone get_post( 11 );
$changed = ec_booking_read( get_post( 11 ) ); $changed['time'] = '14:30';
$GLOBALS['zbs_posts'][11]->post_content = json_encode( $changed );
ec_zbs_post_updated( 11, get_post( 11 ), $before_post );
zbs_expect( ! wp_next_scheduled( 'ec_zbs_reminder_deliver', array( 11 ) ), 'Future reschedule cannot queue a retired reminder' );

echo 'ZBS patient booking checks: ' . $GLOBALS['zbs_checks'] . " passed\n";
