<?php
/** Run after booking-validation-test.php; no production database or network calls. */
require __DIR__ . '/booking-validation-test.php';

class EcAdminSaveStop extends Exception {}
function current_user_can( $cap ) { return ! empty( $GLOBALS['ec_test_admin'] ) && 'manage_options' === $cap; }
function get_post_type( $id ) { $post = get_post( $id ); return $post ? $post->post_type : ''; }
function wp_is_post_autosave( $id ) { return false; }
function wp_is_post_revision( $id ) { return false; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function sanitize_textarea_field( $value ) { return strip_tags( $value ); }
function get_current_user_id() { return 7; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function wp_die( $message, $title = '', $args = array() ) { throw new EcAdminSaveStop( $message ); }
function update_post_meta( $id, $key, $value ) {
	$post = get_post( $id );
	if ( $post ) { $post->meta_input[ $key ] = $value; }
	return true;
}
function ec_zbs_status_changed( $id ) { $GLOBALS['ec_test_zbs_status_calls'][] = $id; }
function wp_update_post( $data, $wp_error = false ) {
	++$GLOBALS['ec_test_update_count'];
	if ( ! empty( $GLOBALS['ec_test_fail_update'] ) ) { return new WP_Error( 'db', 'Test write failed' ); }
	$data = wp_unslash( $data );
	$post = get_post( $data['ID'] );
	$before = clone $post;
	foreach ( $data as $key => $value ) { $post->$key = $value; }
	$GLOBALS['ec_test_post_updated'][] = array( $before->post_content, $post->post_content );
	// WordPress fires save_post again for the nested update; the admin callback must guard it.
	ec_booking_admin_save( $post->ID );
	return $post->ID;
}

$GLOBALS['ec_test_admin'] = true;
$GLOBALS['ec_test_update_count'] = 0;
$GLOBALS['ec_test_post_updated'] = array();
$GLOBALS['ec_test_zbs_status_calls'] = array();
$now = new DateTimeImmutable( '2026-10-26 10:15:00', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
$hours = array( 'gio_mo' => '07:30', 'gio_dong' => '18:00' );
ec_expect( true === ec_booking_admin_validate_slot( '2026-10-27', '07:30', $now, $hours ), 'Future opening slot accepted in admin' );
ec_expect( true === ec_booking_admin_validate_slot( '2026-10-27', '16:00', $now, $hours ), 'Last daily slot accepted in admin' );
ec_expect_error( ec_booking_admin_validate_slot( '2026-10-27', '16:30', $now, $hours ), 'time', 'Admin cannot choose a slot past booking cutoff' );
ec_expect_error( ec_booking_admin_validate_slot( '2026-10-27', '08:15', $now, $hours ), 'time', 'Admin cannot choose off-grid slot' );
ec_expect_error( ec_booking_admin_validate_slot( '2026-10-26', '10:00', $now, $hours ), 'date', 'Admin cannot move to a past time' );
ec_expect_error( ec_booking_admin_validate_slot( '2026-10-27', '24:00', $now, $hours ), 'date', 'Malformed admin time rejected' );
ec_expect_error( ec_booking_admin_validate_slot( '2026-02-30', '09:00', $now, $hours ), 'date', 'Impossible admin date rejected' );
ec_expect_error( ec_booking_admin_validate_slot( '2027-02-01', '09:00', $now, $hours ), 'date', 'Admin cannot move beyond 90 days' );

$id = 2;
$post = get_post( $id );
$before = ec_booking_read( $post );
$fingerprint = $before['fingerprint'];
$consent = $before['patient_zalo_consent'];
$consent_text = $before['patient_zalo_consent_text'] ?? null;
$consent_at = $before['patient_zalo_consented_at'] ?? null;
$_POST = array( 'ec_booking_admin_nonce' => 'test-nonce', 'ec_booking_status' => 'contacted', 'ec_booking_note' => 'Đã xác nhận lại', 'ec_booking_date' => ec_booking_now()->modify( '+2 days' )->format( 'Y-m-d' ), 'ec_booking_time' => '09:00' );
ec_booking_admin_save( $id );
$after = ec_booking_read( get_post( $id ) );
ec_expect( $GLOBALS['ec_test_update_count'] === 1, 'Reschedule updates post once, without recursive save' );
ec_expect( $after['date'] === $_POST['ec_booking_date'] && $after['time'] === '09:00' && $post->post_status === 'private', 'Reschedule saves new private slot' );
ec_expect( $after['fingerprint'] === $fingerprint && $after['patient_zalo_consent'] === $consent && ( $after['patient_zalo_consent_text'] ?? null ) === $consent_text && ( $after['patient_zalo_consented_at'] ?? null ) === $consent_at, 'Reschedule preserves booking fingerprint and original consent evidence' );
ec_expect( get_post_meta( $id, '_ec_booking_status', true ) === 'contacted' && $GLOBALS['ec_test_zbs_status_calls'] === array( $id ), 'Status update runs ZBS status synchronization' );
ec_expect( get_post_meta( $id, '_ec_booking_previous_slot', true ) === $before['date'] . ' ' . $before['time'] && get_post_meta( $id, '_ec_booking_rescheduled_by', true ) === 7, 'Reschedule records previous slot and editor' );
ec_expect( count( $GLOBALS['ec_test_post_updated'] ) === 1 && $GLOBALS['ec_test_post_updated'][0][0] !== $GLOBALS['ec_test_post_updated'][0][1], 'WordPress post_updated can observe slot change for ZBS reminder' );

$_POST['ec_booking_date'] = '2026-02-30';
try { ec_booking_admin_save( $id ); ec_expect( false, 'Invalid reschedule must stop' ); }
catch ( EcAdminSaveStop $error ) { ec_expect( $GLOBALS['ec_test_update_count'] === 1 && ec_booking_read( get_post( $id ) )['date'] === $after['date'], 'Invalid date leaves appointment unchanged' ); }
$_POST['ec_booking_date'] = ec_booking_now()->modify( '+3 days' )->format( 'Y-m-d' );
$_POST['ec_booking_time'] = array( '09:00' );
try { ec_booking_admin_save( $id ); ec_expect( false, 'Array time must stop' ); }
catch ( EcAdminSaveStop $error ) { ec_expect( $GLOBALS['ec_test_update_count'] === 1, 'Array time rejected before database update' ); }
$_POST['ec_booking_time'] = '09:00';
$GLOBALS['ec_test_fail_update'] = true;
try { ec_booking_admin_save( $id ); ec_expect( false, 'Failed database update must stop' ); }
catch ( EcAdminSaveStop $error ) { ec_expect( ec_booking_read( get_post( $id ) )['date'] === $after['date'] && get_post_meta( $id, '_ec_booking_status', true ) === 'contacted', 'Failed reschedule does not partly change status or slot' ); }
$GLOBALS['ec_test_fail_update'] = false;
$_POST['ec_booking_admin_nonce'] = 'invalid';
ec_booking_admin_save( $id );
ec_expect( $GLOBALS['ec_test_update_count'] === 2, 'Invalid nonce prevents another update' );
$GLOBALS['ec_test_admin'] = false;
$_POST['ec_booking_admin_nonce'] = 'test-nonce';
ec_booking_admin_save( $id );
ec_expect( $GLOBALS['ec_test_update_count'] === 2, 'User without manage_options cannot reschedule' );

echo "PASS: focused WordPress-admin rescheduling checks.\n";
