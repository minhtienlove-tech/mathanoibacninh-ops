<?php
/** Isolated approved-reminder tests. All recipients, responses and tokens are synthetic. */
require __DIR__ . '/booking-zbs-test.php';
require dirname( __DIR__ ) . '/inc/zbs-nhac-lich.php';

$GLOBALS['zbs_options'] = array();
$GLOBALS['zbs_posts'] = array();
$GLOBALS['zbs_meta'] = array();
$GLOBALS['zbs_events'] = array();
$GLOBALS['zbs_locks'] = array();
$GLOBALS['zbs_http'] = array();
$GLOBALS['zbs_http_queue'] = array();
$GLOBALS['zbs_send_override'] = null;
$GLOBALS['zbs_schedule_fail'] = false;
$GLOBALS['zbs_option_fail'] = false;
$GLOBALS['reminder_checks'] = 0;

function reminder_expect( $condition, $label ) {
	if ( ! $condition ) { throw new RuntimeException( $label ); }
	++$GLOBALS['reminder_checks'];
}
function reminder_clock( $time ) {
	$GLOBALS['zbs_now'] = ( new DateTimeImmutable( '2026-10-30 ' . $time, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) )->getTimestamp();
	$GLOBALS['zbs_provider'] = array( 'access_token' => 'mock-reminder-access-123', 'expires_at' => $GLOBALS['zbs_now'] + 86400 );
}
function reminder_timestamp( $time ) {
	return ( new DateTimeImmutable( '2026-10-30 ' . $time, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) )->getTimestamp();
}
function reminder_settings( $extra = array() ) {
	return array_merge( array(
		'enabled' => true,
		'template_confirm_id' => '657033',
		'confirm_map' => array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' ),
		'reminder_enabled' => true,
		'reminder_hours' => 2,
		'template_reminder_id' => '659483',
		'reminder_map' => array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' ),
	), $extra );
}
function reminder_post( $id, $time, $extra = array() ) {
	zbs_post( $id, $time );
	$data = array_merge( ec_booking_read( get_post( $id ) ), array(
		'date' => '2026-10-30',
		'patient_zalo_reminder_consent' => true,
		'patient_zalo_consent_version' => '2026-10-30-confirm-reminder-v1',
	), $extra );
	$GLOBALS['zbs_posts'][ $id ]->post_content = json_encode( $data );
	// Existing confirmation enrollment precedes the reminder hook on a new booking.
	ec_zbs_enqueue( $id );
}
function reminder_change( $id, $extra ) {
	$data = array_merge( ec_booking_read( get_post( $id ) ), $extra );
	$GLOBALS['zbs_posts'][ $id ]->post_content = json_encode( $data );
}
function reminder_event( $id ) {
	return wp_next_scheduled( 'ec_zbs_reminder_v2_deliver', array( $id ) );
}
function reminder_run( $id ) {
	$when = reminder_event( $id );
	if ( $when ) { wp_unschedule_event( $when, 'ec_zbs_reminder_v2_deliver', array( $id ) ); }
	ec_zbs_reminder_deliver( $id );
}
function reminder_accept_next( $id ) {
	$GLOBALS['zbs_http_queue'][] = zbs_response( 200, array( 'error' => 0, 'data' => array( 'msg_id' => 'reminder-' . $id ) ) );
}
function get_posts( $query ) {
	$GLOBALS['reminder_last_query'] = $query;
	$ids = array();
	foreach ( $GLOBALS['zbs_posts'] as $id => $post ) {
		if ( $post->post_type !== ( $query['post_type'] ?? '' ) || $post->post_status !== ( $query['post_status'] ?? '' ) ) { continue; }
		$matches = true;
		foreach ( $query['meta_query'] ?? array() as $clause ) {
			if ( ! is_array( $clause ) || ! isset( $clause['key'], $clause['value'] ) ) { continue; }
			if ( get_post_meta( $id, $clause['key'] ) !== $clause['value'] ) { $matches = false; break; }
		}
		if ( $matches ) { $ids[] = $id; }
	}
	return $ids;
}

reminder_clock( '08:00' );
reminder_expect( ! ec_zbs_reminder_enabled(), 'Reminder starts disabled by default' );
reminder_expect( 2 === ec_zbs_reminder_hours(), 'Default lead time is two hours' );
reminder_expect( 1 === ec_zbs_reminder_hours( reminder_settings( array( 'reminder_hours' => 1 ) ) ) && 72 === ec_zbs_reminder_hours( reminder_settings( array( 'reminder_hours' => 72 ) ) ), 'Admin lead-time range supports one to seventy-two hours' );
update_option( 'ec_booking_zbs', reminder_settings() );
reminder_expect( ec_zbs_reminder_enabled(), 'Approved template and enabled setting activate reminders' );

reminder_post( 201, '10:30', array( 'patient_zalo_reminder_consent' => false ) );
ec_zbs_reminder_enroll( 201 );
reminder_expect( ! reminder_event( 201 ) && '1' !== get_post_meta( 201, '_ec_zbs_reminder_v2_enrolled' ), 'Confirmation consent alone never enrolls a reminder' );
reminder_post( 202, '10:30', array( 'patient_zalo_consent_version' => '2026-10-30-in-required-consent-v4' ) );
ec_zbs_reminder_enroll( 202 );
reminder_expect( ! reminder_event( 202 ) && '1' !== get_post_meta( 202, '_ec_zbs_reminder_v2_enrolled' ), 'Old confirmation-only evidence cannot authorize a new reminder' );
reminder_post( 203, '10:30', array( 'patient_zalo_consent' => false ) );
ec_zbs_reminder_enroll( 203 );
reminder_expect( ! reminder_event( 203 ), 'No Zalo consent blocks reminder enrollment even with a reminder flag' );

reminder_post( 204, '10:30' );
ec_zbs_reminder_reconcile( 204 );
reminder_expect( ! reminder_event( 204 ) && '1' !== get_post_meta( 204, '_ec_zbs_reminder_v2_enrolled' ), 'Reconciliation cannot backfill an unenrolled historical booking' );
update_post_meta( 204, '_ec_zbs_reminder_state', 'pending' );
$before = count( $GLOBALS['zbs_http'] );
ec_zbs_reminder_deliver( 204 );
reminder_expect( $before === count( $GLOBALS['zbs_http'] ), 'Legacy pending metadata cannot revive a reminder under the new hook' );

reminder_post( 205, '10:30' );
ec_zbs_reminder_enroll( 205 );
reminder_expect( '1' === get_post_meta( 205, '_ec_zbs_reminder_v2_enrolled' ) && 'pending' === get_post_meta( 205, '_ec_zbs_reminder_state' ), 'New consenting booking is enrolled exactly once' );
reminder_expect( reminder_timestamp( '08:30' ) === reminder_event( 205 ) && reminder_timestamp( '08:30' ) === (int) get_post_meta( 205, '_ec_zbs_reminder_due_at' ), 'Reminder is due exactly two hours before the Vietnam-time appointment' );
reminder_expect( reminder_timestamp( '10:30' ) === (int) get_post_meta( 205, '_ec_zbs_reminder_slot' ) && 2 === (int) get_post_meta( 205, '_ec_zbs_reminder_hours' ), 'Slot and lead time are saved for reconciliation' );
ec_zbs_reminder_enroll( 205 ); ec_zbs_reminder_reconcile( 205 );
reminder_expect( reminder_timestamp( '08:30' ) === reminder_event( 205 ), 'Repeated creation and recovery retain one reminder event at its original due time' );
$before = count( $GLOBALS['zbs_http'] );
reminder_run( 205 );
reminder_expect( $before === count( $GLOBALS['zbs_http'] ) && reminder_timestamp( '08:30' ) === reminder_event( 205 ), 'An early stale cron event is postponed without sending' );

reminder_clock( '08:30' ); reminder_accept_next( 205 ); reminder_run( 205 );
$call = end( $GLOBALS['zbs_http'] ); $body = json_decode( $call[1]['body'], true );
reminder_expect( 'accepted' === get_post_meta( 205, '_ec_zbs_reminder_state' ) && 'reminder-205' === get_post_meta( 205, '_ec_zbs_reminder_msg_id' ), 'Provider acceptance records the reminder message ID' );
reminder_expect( 'https://business.openapi.zalo.me/message/template' === $call[0] && '659483' === $body['template_id'] && 'ec205r' === $body['tracking_id'] && '84987654321' === $body['phone'], 'Reminder uses approved ID, international phone and tracking distinct from confirmation' );
reminder_expect( array( 'schedule_time' => '10:30:00 30/10/2026', 'address' => 'Địa chỉ thử nghiệm', 'customer_name' => 'Người bệnh thử', 'booking_code' => '205' ) === $body['template_data'], 'All four approved template parameters contain their correct appointment values' );
$before = count( $GLOBALS['zbs_http'] );
ec_zbs_reminder_deliver( 205 ); ec_zbs_reminder_enroll( 205 ); ec_zbs_reminder_reconcile( 205 );
reminder_expect( $before === count( $GLOBALS['zbs_http'] ) && ! reminder_event( 205 ), 'Accepted reminder is never sent or queued twice' );
reminder_change( 205, array( 'time' => '14:00' ) ); ec_zbs_reminder_reconcile( 205 );
reminder_expect( ! reminder_event( 205 ) && 'accepted' === get_post_meta( 205, '_ec_zbs_reminder_state' ), 'Changing an already-reminded appointment does not send another reminder' );

reminder_clock( '08:00' );
reminder_post( 206, '09:00' ); ec_zbs_reminder_enroll( 206 );
reminder_expect( ! reminder_event( 206 ) && 'late' === get_post_meta( 206, '_ec_zbs_reminder_state' ), 'A booking made inside the two-hour window does not send an immediate reminder' );
reminder_expect( wp_next_scheduled( 'ec_zbs_confirm_deliver', array( 206 ) ), 'A near-term booking still retains its separate confirmation' );

reminder_post( 207, '12:00' ); ec_zbs_reminder_enroll( 207 );
reminder_change( 207, array( 'time' => '13:00' ) ); ec_zbs_reminder_reconcile( 207 );
reminder_expect( reminder_timestamp( '11:00' ) === reminder_event( 207 ) && reminder_timestamp( '13:00' ) === (int) get_post_meta( 207, '_ec_zbs_reminder_slot' ), 'A pending reminder follows a later appointment and removes its previous due time' );
reminder_clock( '10:00' ); $before = count( $GLOBALS['zbs_http'] ); reminder_run( 207 );
reminder_expect( $before === count( $GLOBALS['zbs_http'] ) && reminder_timestamp( '11:00' ) === reminder_event( 207 ), 'An old callback cannot send the reminder for a superseded appointment slot' );
reminder_clock( '08:00' ); reminder_change( 207, array( 'time' => '11:00' ) ); ec_zbs_reminder_reconcile( 207 );
reminder_expect( reminder_timestamp( '09:00' ) === reminder_event( 207 ), 'A pending reminder follows an earlier future appointment too' );

foreach ( array( 208 => 'cancelled', 209 => 'completed' ) as $id => $status ) {
	reminder_post( $id, '12:00' ); ec_zbs_reminder_enroll( $id );
	update_post_meta( $id, '_ec_booking_status', $status ); ec_zbs_reminder_reconcile( $id );
	$before = count( $GLOBALS['zbs_http'] ); ec_zbs_reminder_deliver( $id );
	reminder_expect( ! reminder_event( $id ) && 'skipped' === get_post_meta( $id, '_ec_zbs_reminder_state' ) && $before === count( $GLOBALS['zbs_http'] ), 'Cancelled/completed appointment removes the reminder and cannot send: ' . $status );
}
reminder_post( 210, '12:00' ); ec_zbs_reminder_enroll( 210 );
$GLOBALS['zbs_posts'][210]->post_status = 'trash'; ec_zbs_reminder_reconcile( 210 );
reminder_expect( ! reminder_event( 210 ) && 'skipped' === get_post_meta( 210, '_ec_zbs_reminder_state' ), 'Trashing an appointment removes its pending reminder' );

reminder_post( 211, '12:00' ); ec_zbs_reminder_enroll( 211 );
reminder_change( 211, array( 'patient_zalo_reminder_consent' => false ) );
reminder_clock( '10:00' ); $before = count( $GLOBALS['zbs_http'] ); reminder_run( 211 );
reminder_expect( $before === count( $GLOBALS['zbs_http'] ) && 'skipped' === get_post_meta( 211, '_ec_zbs_reminder_state' ), 'Revoked reminder consent is checked again immediately before sending' );

reminder_clock( '08:00' ); reminder_post( 212, '12:00' ); ec_zbs_reminder_enroll( 212 );
update_option( 'ec_booking_zbs', reminder_settings( array( 'reminder_enabled' => false ) ) );
reminder_clock( '10:00' ); $before = count( $GLOBALS['zbs_http'] ); reminder_run( 212 );
reminder_expect( $before === count( $GLOBALS['zbs_http'] ) && 'disabled' === get_post_meta( 212, '_ec_zbs_reminder_state' ), 'Turning off reminders blocks a queued reminder at send time' );
update_option( 'ec_booking_zbs', reminder_settings() ); ec_zbs_reminder_reconcile( 212 );
reminder_expect( ! reminder_event( 212 ), 'Re-enabling settings does not replay a disabled old reminder' );

reminder_clock( '08:00' ); reminder_post( 213, '12:00' ); ec_zbs_reminder_enroll( 213 );
reminder_clock( '10:06' ); $before = count( $GLOBALS['zbs_http'] ); reminder_run( 213 );
reminder_expect( 'late' === get_post_meta( 213, '_ec_zbs_reminder_state' ) && $before === count( $GLOBALS['zbs_http'] ), 'Cron delayed beyond five minutes skips the reminder' );
reminder_clock( '08:00' ); reminder_post( 214, '12:00' ); ec_zbs_reminder_enroll( 214 );
reminder_clock( '10:05' ); reminder_accept_next( 214 ); reminder_run( 214 );
reminder_expect( 'accepted' === get_post_meta( 214, '_ec_zbs_reminder_state' ), 'The five-minute grace boundary still sends once' );

reminder_clock( '08:00' );
$long = array( 'name' => str_repeat( 'Á', 31 ) );
reminder_post( 215, '12:00', $long );
reminder_expect( is_wp_error( ec_zbs_template_payload( 'reminder', get_post( 215 ), ec_booking_read( get_post( 215 ) ), reminder_settings() ) ), 'Approved reminder enforces the thirty-character Unicode name limit' );
$bad = reminder_settings(); $bad['reminder_map'] = array( 'customer_name' => 'name' );
reminder_expect( is_wp_error( ec_zbs_template_payload( 'reminder', get_post( 207 ), ec_booking_read( get_post( 207 ) ), $bad ) ), 'A reminder with missing approved parameters is rejected' );
ec_zbs_reminder_enroll( 215 );
reminder_expect( ! reminder_event( 215 ), 'Invalid template data is not queued for automatic reminder delivery' );
reminder_post( 216, '12:00', array( 'date' => '2026-02-31' ) ); ec_zbs_reminder_enroll( 216 );
reminder_expect( ! reminder_event( 216 ), 'An impossible appointment date cannot queue a reminder' );
reminder_post( 217, '12:00', array( 'phone' => '+12025550123' ) ); ec_zbs_reminder_enroll( 217 );
reminder_expect( ! reminder_event( 217 ), 'A phone outside the supported Vietnamese mobile format cannot receive a reminder' );

reminder_post( 218, '12:00' ); ec_zbs_reminder_enroll( 218 );
reminder_clock( '10:00' );
$GLOBALS['zbs_http_queue'][] = new WP_Error( 'timeout', 'simulated private transport detail' ); reminder_run( 218 );
$before = count( $GLOBALS['zbs_http'] ); ec_zbs_reminder_deliver( 218 ); ec_zbs_reminder_reconcile( 218 );
reminder_expect( 'unknown' === get_post_meta( 218, '_ec_zbs_reminder_state' ) && $before === count( $GLOBALS['zbs_http'] ) && ! reminder_event( 218 ), 'Ambiguous reminder outcome is never retried automatically' );
reminder_clock( '08:00' ); reminder_post( 219, '12:00' ); ec_zbs_reminder_enroll( 219 );
reminder_clock( '10:00' );
$GLOBALS['zbs_http_queue'][] = zbs_response( 200, array( 'error' => -131, 'message' => 'private provider detail' ) ); reminder_run( 219 );
$before = count( $GLOBALS['zbs_http'] ); ec_zbs_reminder_deliver( 219 ); ec_zbs_reminder_reconcile( 219 );
reminder_expect( 'failed' === get_post_meta( 219, '_ec_zbs_reminder_state' ) && $before === count( $GLOBALS['zbs_http'] ) && ! reminder_event( 219 ), 'A refused reminder remains terminal until an operator reviews it' );
reminder_expect( ! str_contains( (string) get_post_meta( 219, '_ec_zbs_reminder_error' ), 'private' ), 'Provider details are not copied to reminder errors' );

reminder_clock( '08:00' ); reminder_post( 220, '12:00' );
$GLOBALS['zbs_schedule_fail'] = true; ec_zbs_reminder_enroll( 220 ); $GLOBALS['zbs_schedule_fail'] = false;
reminder_expect( 'failed' === get_post_meta( 220, '_ec_zbs_reminder_state' ) && ! reminder_event( 220 ), 'A WP_Error scheduling result cannot leave a falsely pending reminder' );

reminder_post( 221, '12:00' ); ec_zbs_reminder_enroll( 221 );
update_option( 'ec_booking_zbs', reminder_settings( array( 'reminder_hours' => 3 ) ) ); ec_zbs_reminder_reconcile( 221 );
reminder_expect( reminder_timestamp( '09:00' ) === reminder_event( 221 ) && 3 === (int) get_post_meta( 221, '_ec_zbs_reminder_hours' ), 'Changing Dashboard hours moves only a still-pending reminder' );
update_option( 'ec_booking_zbs', reminder_settings( array( 'template_reminder_id' => 'unknown-template' ) ) );
reminder_expect( ! ec_zbs_reminder_enabled(), 'An unapproved reminder template does not activate automatic sends' );

update_option( 'ec_booking_zbs', reminder_settings() );
reminder_post( 222, '12:00' ); ec_zbs_reminder_enroll( 222 );
update_option( 'ec_booking_zbs', reminder_settings( array( 'reminder_hours' => 3 ) ) );
ec_zbs_reminder_settings_changed();
reminder_expect( reminder_timestamp( '09:00' ) === reminder_event( 222 ), 'Dashboard settings callback replans a pending reminder to the newly selected hours' );
$query = $GLOBALS['reminder_last_query'];
reminder_expect( 'ids' === $query['fields'] && in_array( array( 'key' => '_ec_zbs_reminder_v2_enrolled', 'value' => '1' ), $query['meta_query'], true ) && in_array( array( 'key' => '_ec_zbs_reminder_state', 'value' => 'pending' ), $query['meta_query'], true ), 'Settings scan explicitly selects only enrolled pending reminders' );
reminder_expect( ! reminder_event( 204 ) && ! reminder_event( 205 ) && ! reminder_event( 218 ) && ! reminder_event( 219 ), 'Dashboard save cannot backfill historical or replay accepted, uncertain and failed reminders' );

update_option( 'ec_booking_zbs', reminder_settings() );
reminder_post( 223, '14:00' ); ec_zbs_reminder_enroll( 223 );
wp_unschedule_event( reminder_event( 223 ), 'ec_zbs_reminder_v2_deliver', array( 223 ) );
ec_zbs_recover( 223 );
reminder_expect( reminder_timestamp( '12:00' ) === reminder_event( 223 ), 'Idempotent booking recovery restores a missing pending reminder event without creating a new enrollment' );

echo 'ZBS reminder checks: ' . $GLOBALS['reminder_checks'] . " passed\n";
