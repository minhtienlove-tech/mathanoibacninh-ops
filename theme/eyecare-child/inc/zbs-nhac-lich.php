<?php
/** One reminder per newly consented appointment; retired reminder events stay inert. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ec_zbs_reminder_hours( $settings = null ) {
	$settings = $settings ?? ec_zbs_settings();
	$value = $settings['reminder_hours'] ?? 2;
	return is_scalar( $value ) && preg_match( '/^[0-9]{1,2}$/D', (string) $value ) && (int) $value >= 1 && (int) $value <= 72 ? (int) $value : 0;
}

function ec_zbs_reminder_enabled( $settings = null ) {
	$settings = $settings ?? ec_zbs_settings();
	return ! empty( $settings['enabled'] ) && ! empty( $settings['reminder_enabled'] ) && ec_zbs_reminder_hours( $settings )
		&& '659483' === ec_zbs_template_id( 'reminder', $settings )
		&& ( $settings['reminder_map'] ?? array() ) == array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' );
}

function ec_zbs_reminder_consent( $data ) {
	return ec_zbs_consent( $data ) && true === ( $data['patient_zalo_reminder_consent'] ?? false )
		&& '2026-10-30-confirm-reminder-v1' === ( $data['patient_zalo_consent_version'] ?? '' );
}

function ec_zbs_reminder_hook() { return 'ec_zbs_reminder_v2_deliver'; }

function ec_zbs_reminder_unschedule( $id ) {
	$args = array( (int) $id );
	$when = wp_next_scheduled( ec_zbs_reminder_hook(), $args );
	if ( $when ) { wp_unschedule_event( $when, ec_zbs_reminder_hook(), $args ); }
}

/** Only the creation hook may enroll. Settings changes and old confirmation consent never enroll. */
function ec_zbs_reminder_enroll( $id ) {
	$post = get_post( $id );
	if ( ! ec_zbs_active_appointment( $post ) || ! ec_zbs_reminder_consent( ec_booking_read( $post ) ) || get_post_meta( $id, '_ec_zbs_reminder_v2_enrolled', true ) ) { return; }
	$settings = ec_zbs_settings();
	if ( ! ec_zbs_reminder_enabled( $settings ) || '1' !== get_post_meta( $id, '_ec_zbs_enrolled', true ) || ! ec_zbs_credentials_configured( $settings ) ) {
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'disabled' );
		update_post_meta( $id, '_ec_zbs_reminder_v2_enrolled', 'disabled' );
		return;
	}
	update_post_meta( $id, '_ec_zbs_reminder_v2_enrolled', '1' );
	update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'pending' );
	ec_zbs_reminder_reconcile( $id );
}
add_action( 'ec_booking_created', 'ec_zbs_reminder_enroll', 30 );

/** Caller holds the reminder lock. Replan only pending messages, never accepted/ambiguous attempts. */
function ec_zbs_reminder_plan_locked( $id ) {
	if ( '1' !== get_post_meta( $id, '_ec_zbs_reminder_v2_enrolled', true ) || 'pending' !== get_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), true ) ) { return; }
	$post = get_post( $id );
	$data = $post ? ec_booking_read( $post ) : array();
	$settings = ec_zbs_settings();
	$state = '';
	if ( ! ec_zbs_active_appointment( $post ) || ! ec_zbs_reminder_consent( $data ) ) { $state = 'skipped'; }
	elseif ( ! ec_zbs_reminder_enabled( $settings ) ) { $state = 'disabled'; }
	if ( $state ) {
		ec_zbs_reminder_unschedule( $id );
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), $state );
		return;
	}
	$variables = ec_zbs_template_payload( 'reminder', $post, $data, $settings );
	if ( is_wp_error( $variables ) || ! ec_zbs_phone( $data['phone'] ?? '' ) ) {
		ec_zbs_reminder_unschedule( $id );
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'failed' );
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'error' ), is_wp_error( $variables ) ? $variables->get_error_message() : 'Số điện thoại nhận nhắc lịch không hợp lệ.' );
		return;
	}
	$slot = ec_zbs_slot( $data );
	$due = $slot - ec_zbs_reminder_hours( $settings ) * HOUR_IN_SECONDS;
	$now = ec_zbs_now();
	$previous_slot = (int) get_post_meta( $id, '_ec_zbs_reminder_slot', true );
	$previous_due = (int) get_post_meta( $id, '_ec_zbs_reminder_due_at', true );
	// Never send an immediate extra message for a new/changed appointment inside the lead window.
	if ( ! $slot || $slot <= $now || ( $due <= $now && ( $previous_slot !== $slot || $previous_due !== $due ) ) || $now > $due + 300 ) {
		ec_zbs_reminder_unschedule( $id );
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'late' );
		return;
	}
	$args = array( (int) $id );
	$event = wp_next_scheduled( ec_zbs_reminder_hook(), $args );
	$when = $due > $now ? $due : $now + 2;
	if ( $event && $event >= $due && $event <= max( $due, $now + 20 ) && $previous_slot === $slot && $previous_due === $due ) { return; }
	ec_zbs_reminder_unschedule( $id );
	update_post_meta( $id, '_ec_zbs_reminder_slot', $slot );
	update_post_meta( $id, '_ec_zbs_reminder_due_at', $due );
	update_post_meta( $id, '_ec_zbs_reminder_hours', ec_zbs_reminder_hours( $settings ) );
	if ( (int) get_post_meta( $id, '_ec_zbs_reminder_slot', true ) !== $slot || (int) get_post_meta( $id, '_ec_zbs_reminder_due_at', true ) !== $due ) {
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'failed' );
		return;
	}
	$queued = wp_schedule_single_event( $when, ec_zbs_reminder_hook(), $args, true );
	if ( ! $queued || is_wp_error( $queued ) ) {
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'failed' );
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'error' ), 'Chưa xếp được tác vụ nhắc lịch. Nhân viên cần kiểm tra và liên hệ trực tiếp.' );
	}
}

function ec_zbs_reminder_reconcile( $id ) {
	$lock = ec_booking_lock( 'zbs:reminder:' . (int) $id );
	if ( ! $lock ) { return; }
	try { ec_zbs_reminder_plan_locked( (int) $id ); }
	finally { ec_booking_unlock( $lock ); }
}

function ec_zbs_reminder_post_updated( $id, $after, $before ) {
	if ( 'ec_appointment' === $after->post_type && $after->post_content !== $before->post_content ) { ec_zbs_reminder_reconcile( (int) $id ); }
}
add_action( 'post_updated', 'ec_zbs_reminder_post_updated', 20, 3 );

function ec_zbs_reminder_meta_updated( $meta_id, $id, $key, $value ) {
	if ( '_ec_booking_status' === $key ) { ec_zbs_reminder_reconcile( (int) $id ); }
}
add_action( 'updated_post_meta', 'ec_zbs_reminder_meta_updated', 20, 4 );
add_action( 'added_post_meta', 'ec_zbs_reminder_meta_updated', 20, 4 );
function ec_zbs_reminder_post_status( $new, $old, $post ) {
	if ( 'ec_appointment' === $post->post_type && $new !== $old ) { ec_zbs_reminder_reconcile( (int) $post->ID ); }
}
add_action( 'transition_post_status', 'ec_zbs_reminder_post_status', 20, 3 );

/** Replan pending consented reminders after a dashboard change; never backfill historical rows. */
function ec_zbs_reminder_settings_changed() {
	$ids = get_posts( array( 'post_type' => 'ec_appointment', 'post_status' => 'private', 'numberposts' => -1, 'fields' => 'ids',
		'meta_query' => array( array( 'key' => '_ec_zbs_reminder_v2_enrolled', 'value' => '1' ), array( 'key' => '_ec_zbs_reminder_state', 'value' => 'pending' ) ),
		'no_found_rows' => true, 'suppress_filters' => true ) );
	foreach ( $ids as $id ) { ec_zbs_reminder_reconcile( (int) $id ); }
}
add_action( 'ec_zbs_reminder_settings_changed', 'ec_zbs_reminder_settings_changed' );

function ec_zbs_reminder_deliver( $id ) {
	$lock = ec_booking_lock( 'zbs:reminder:' . (int) $id );
	if ( ! $lock ) { return; }
	try {
		if ( '1' !== get_post_meta( $id, '_ec_zbs_reminder_v2_enrolled', true ) || 'pending' !== get_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), true ) ) { return; }
		ec_zbs_reminder_plan_locked( (int) $id );
		if ( 'pending' !== get_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), true ) ) { return; }
		$now = ec_zbs_now();
		$due = (int) get_post_meta( $id, '_ec_zbs_reminder_due_at', true );
		if ( $now < $due ) { return; }
		$post = get_post( $id );
		$data = ec_booking_read( $post );
		$settings = ec_zbs_settings();
		$variables = ec_zbs_template_payload( 'reminder', $post, $data, $settings );
		// Persist the send claim before calling ZBS; ambiguous outcomes never auto-retry.
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'sending' );
		if ( 'sending' !== get_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), true ) ) { return; }
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'started_at' ), $now );
		ec_zbs_reminder_unschedule( $id );
		$result = ec_zbs_send_template( array( 'phone' => ec_zbs_phone( $data['phone'] ), 'template_id' => '659483', 'template_data' => $variables, 'tracking_id' => 'ec' . (int) $id . 'r' ) );
		if ( is_wp_error( $result ) ) {
			if ( 'busy' === $result->get_error_code() ) {
				update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'pending' );
				$queued = wp_schedule_single_event( $now + 20, ec_zbs_reminder_hook(), array( (int) $id ), true );
				if ( ! $queued || is_wp_error( $queued ) ) { update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'failed' ); }
				return;
			}
			update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'unknown' === $result->get_error_code() ? 'unknown' : 'failed' );
			update_post_meta( $id, ec_zbs_meta( 'reminder', 'error' ), $result->get_error_message() );
			return;
		}
		if ( ! is_array( $result ) || ! is_scalar( $result['msg_id'] ?? null ) || '' === (string) $result['msg_id'] ) {
			update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'unknown' );
			return;
		}
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'msg_id' ), (string) $result['msg_id'] );
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'sent_at' ), $now );
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'accepted' );
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'error' ), '' );
	} finally { ec_booking_unlock( $lock ); }
}
add_action( 'ec_zbs_reminder_v2_deliver', 'ec_zbs_reminder_deliver' );
