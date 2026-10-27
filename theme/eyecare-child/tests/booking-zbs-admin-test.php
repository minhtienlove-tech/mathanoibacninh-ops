<?php
/** Isolated checks for the patient ZBS settings page. No WordPress, network, or real tokens. */
define( 'ABSPATH', __DIR__ );
define( 'MINUTE_IN_SECONDS', 60 );

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
class ZbsAdminRedirect extends RuntimeException {}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function add_action( $hook, $callback ) {}
function do_action( $hook ) { $GLOBALS['zbs_admin_purge_actions'][] = $hook; }
function wp_cache_flush() { ++$GLOBALS['zbs_admin_cache_flushes']; return true; }
function current_user_can( $capability ) { return $GLOBALS['zbs_admin_allowed']; }
function wp_die( $message ) { throw new RuntimeException( $message ); }
function check_admin_referer( $action, $name ) { return true; }
function wp_unslash( $value ) { return stripslashes( $value ); }
function get_current_user_id() { return 1; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['zbs_admin_transients'][ $key ] = $value; return true; }
function get_transient( $key ) { return $GLOBALS['zbs_admin_transients'][ $key ] ?? false; }
function delete_transient( $key ) { unset( $GLOBALS['zbs_admin_transients'][ $key ] ); }
function wp_safe_redirect( $url ) { throw new ZbsAdminRedirect( $url ); }
function admin_url( $path ) { return '/wp-admin/' . $path; }
function get_option( $key, $default = false ) { return $GLOBALS['zbs_admin_options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = false ) { $GLOBALS['zbs_admin_options'][ $key ] = $value; return true; }
function ec_booking_lock( $key ) { return $key; }
function ec_booking_unlock( $lock ) {}
function ec_zalo_encrypt( $value ) { return 'enc:' . $value; }
function ec_zalo_token( $settings ) { return str_starts_with( $settings['cipher'] ?? '', 'enc:' ) ? substr( $settings['cipher'], 4 ) : ''; }
function ec_zbs_safe_secret( $value ) { return is_string( $value ) && strlen( $value ) >= 10 && strlen( $value ) <= 4096 && ! preg_match( '/[\x00-\x1f\x7f]/', $value ); }
function ec_zbs_settings() { return array_merge( array( 'app_id' => '', 'secret_key_cipher' => '', 'refresh_token_cipher' => '', 'access_token_cipher' => '', 'access_token_expires_at' => 0, 'template_confirm_id' => '', 'template_reminder_id' => '', 'confirm_map' => array(), 'reminder_map' => array(), 'token_state' => '', 'enabled' => false ), get_option( 'ec_booking_zbs', array() ) ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_textarea( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_html( $value ); }
function wp_date( $format, $timestamp, $timezone ) { return date( $format, $timestamp ); }
function wp_nonce_field( $action, $name ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '">'; }
function checked( $value ) { if ( $value ) { echo 'checked="checked"'; } }
function submit_button( $label ) { echo '<button>' . esc_html( $label ) . '</button>'; }

require dirname( __DIR__ ) . '/inc/zbs-admin.php';
$GLOBALS['zbs_admin_allowed'] = true;
$GLOBALS['zbs_admin_options'] = array();
$GLOBALS['zbs_admin_transients'] = array();
$GLOBALS['zbs_admin_purge_actions'] = array();
$GLOBALS['zbs_admin_cache_flushes'] = 0;
$checks = 0;
function zbs_admin_expect( $condition, $label ) {
	global $checks;
	if ( ! $condition ) { throw new RuntimeException( $label ); }
	++$checks;
}
function zbs_admin_post( $values ) {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST = array_merge( array(
		'app_id' => '', 'secret_key' => '', 'refresh_token' => '', 'access_token' => '',
		'access_token_expires' => '', 'template_confirm_id' => '', 'confirm_map' => '',
	), $values );
	try { ec_zbs_admin_save(); }
	catch ( ZbsAdminRedirect $redirect ) {}
	return $GLOBALS['zbs_admin_transients']['ec_zbs_notice_1'] ?? array();
}

zbs_admin_expect( array() === ec_zbs_admin_parse_map( '' ), 'Empty mapping is allowed while disabled' );
zbs_admin_expect( array( 'patient_name' => 'name', 'visit_date' => 'date' ) === ec_zbs_admin_parse_map( "patient_name=name\nvisit_date=date" ), 'Approved tokens parse' );
zbs_admin_expect( array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' ) === ec_zbs_admin_parse_map( "schedule_time=schedule_time\naddress=address\ncustomer_name=name\nbooking_code=appointment_id" ), 'Approved template 657033 variables parse' );
zbs_admin_expect( is_wp_error( ec_zbs_admin_parse_map( 'patient_name=clinical_note' ) ), 'Unapproved value refused' );
zbs_admin_expect( is_wp_error( ec_zbs_admin_parse_map( "patient_name=name\npatient_name=date" ) ), 'Duplicate parameter refused' );
zbs_admin_expect( is_wp_error( ec_zbs_admin_parse_map( 'a=b=c' ) ), 'Ambiguous line refused' );

$notice = zbs_admin_post( array( 'confirm_map' => 'patient_name=clinical_note' ) );
zbs_admin_expect( ! empty( $notice['error'] ) && false === get_option( 'ec_booking_zbs' ), 'Invalid form cannot save' );
$notice = zbs_admin_post( array() );
zbs_admin_expect( empty( $notice['error'] ) && false === get_option( 'ec_booking_zbs' )['enabled'], 'Disabled blank configuration can save safely' );
$notice = zbs_admin_post( array( 'enabled' => '1' ) );
zbs_admin_expect( ! empty( $notice['error'] ) && false === get_option( 'ec_booking_zbs' )['enabled'], 'Cannot enable without confirmation template and credentials' );

$valid = array(
	'app_id' => '123456', 'secret_key' => 'mock-secret-key-123', 'refresh_token' => 'mock-refresh-token-123',
	'template_confirm_id' => '657033',
	'confirm_map' => "schedule_time=schedule_time\naddress=address\ncustomer_name=name\nbooking_code=appointment_id", 'enabled' => '1',
);
$notice = zbs_admin_post( $valid );
$saved = get_option( 'ec_booking_zbs' );
zbs_admin_expect( empty( $notice['error'] ) && true === $saved['enabled'] && '657033' === $saved['template_confirm_id'] && empty( $saved['template_reminder_id'] ), 'Confirmation-only settings can be enabled without a reminder template' );
$mismatched = $valid; $mismatched['confirm_map'] = 'customer_name=name';
$notice = zbs_admin_post( $mismatched );
zbs_admin_expect( ! empty( $notice['error'] ) && $saved === get_option( 'ec_booking_zbs' ), 'Approved template cannot be enabled with missing variables' );
zbs_admin_expect( 1 === $GLOBALS['zbs_admin_cache_flushes'] && in_array( 'litespeed_purge_all', $GLOBALS['zbs_admin_purge_actions'], true ), 'Enabling ZBS purges cached booking forms' );
zbs_admin_expect( 'mock-refresh-token-123' === ec_zalo_token( array( 'cipher' => $saved['refresh_token_cipher'] ) ) && ! isset( $saved['access_token_cipher'] ), 'Credentials are encrypted and access may be initially absent' );
$legacy = $saved; $legacy['template_reminder_id'] = '67890'; $legacy['reminder_map'] = array( 'customer_name' => 'name' );
update_option( 'ec_booking_zbs', $legacy );
$notice = zbs_admin_post( $valid ); $saved = get_option( 'ec_booking_zbs' );
zbs_admin_expect( empty( $notice['error'] ) && '' === $saved['template_reminder_id'] && array() === $saved['reminder_map'], 'Saving confirmation-only settings retires old reminder configuration' );

$saved['access_token_cipher'] = ec_zalo_encrypt( 'old-access-token-123' );
$saved['access_token_expires_at'] = time() + 7200;
$saved['token_state'] = 'unknown';
update_option( 'ec_booking_zbs', $saved );
update_option( 'ec_booking_zbs_token_unsafe', true );
$valid['secret_key'] = '';
$valid['refresh_token'] = '';
$notice = zbs_admin_post( $valid );
zbs_admin_expect( ! empty( $notice['error'] ) && true === get_option( 'ec_booking_zbs_token_unsafe' ), 'Unsafe token chain cannot be enabled by just saving again' );
$valid['refresh_token'] = 'new-refresh-token-123';
$notice = zbs_admin_post( $valid );
$saved = get_option( 'ec_booking_zbs' );
zbs_admin_expect( empty( $notice['error'] ) && false === get_option( 'ec_booking_zbs_token_unsafe', false ), 'Replacing refresh token clears unsafe marker' );
zbs_admin_expect( ! isset( $saved['access_token_cipher'] ) && 0 === $saved['access_token_expires_at'] && '' === $saved['token_state'], 'Replacing refresh token clears stale access pair and error state' );

$valid['refresh_token'] = '';
$valid['app_id'] = '999999';
$notice = zbs_admin_post( $valid );
zbs_admin_expect( ! empty( $notice['error'] ) && '123456' === get_option( 'ec_booking_zbs' )['app_id'], 'App change requires matching secret and refresh token' );

$valid['app_id'] = '123456';
$valid['access_token'] = 'new-access-token-123';
$valid['access_token_expires'] = '2000-01-01T12:00';
$notice = zbs_admin_post( $valid );
zbs_admin_expect( ! empty( $notice['error'] ) && ! isset( get_option( 'ec_booking_zbs' )['access_token_cipher'] ), 'Expired manual access token refused' );

ob_start();
ec_zbs_admin_page();
$html = ob_get_clean();
zbs_admin_expect( str_contains( $html, 'Tin ZBS cho người đặt lịch' ) && ! str_contains( $html, 'new-refresh-token-123' ) && ! str_contains( $html, 'mock-secret-key-123' ), 'Admin page renders status but never discloses credentials' );
zbs_admin_expect( ! str_contains( $html, 'name="template_reminder_id"' ) && ! str_contains( $html, 'name="reminder_map"' ), 'Admin page contains no reminder configuration' );

echo 'ZBS admin checks: ' . $checks . " passed\n";
