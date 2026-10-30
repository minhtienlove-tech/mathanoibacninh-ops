<?php
/** Run with wp eval-file; touches only the four reminder options, never credentials or bookings. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) { exit( 1 ); }
$backup = $args[0] ?? '';
if ( ! is_string( $backup ) || ! preg_match( '~^/home/jwhxtzru/backups/zbs-reminder-20261030-[0-9]{6}$~D', $backup ) || ! is_dir( $backup ) ) { throw new RuntimeException( 'Invalid backup directory.' ); }
$desired = array( 'template_reminder_id' => '659483', 'reminder_map' => array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' ), 'reminder_enabled' => true, 'reminder_hours' => 2 );
$lock = ec_booking_lock( 'zbs-token' );
if ( ! $lock ) { throw new RuntimeException( 'ZBS settings busy.' ); }
try {
	$current = get_option( 'ec_booking_zbs', array() );
	if ( ! is_array( $current ) || empty( $current['enabled'] ) || '657033' !== ( $current['template_confirm_id'] ?? '' ) || ! ec_zbs_credentials_configured( ec_zbs_settings() ) ) { throw new RuntimeException( 'Confirmation configuration changed; stop.' ); }
	$previous = array();
	foreach ( $desired as $key => $value ) { $previous[ $key ] = array( 'exists' => array_key_exists( $key, $current ), 'value' => $current[ $key ] ?? null ); }
	if ( false === file_put_contents( $backup . '/reminder-settings-before.json', wp_json_encode( $previous ), LOCK_EX ) ) { throw new RuntimeException( 'Cannot save reminder settings backup.' ); }
	chmod( $backup . '/reminder-settings-before.json', 0600 );
	$next = array_merge( $current, $desired );
	update_option( 'ec_booking_zbs', $next, false );
	$readback = get_option( 'ec_booking_zbs' );
	foreach ( $desired as $key => $value ) { if ( ( $readback[ $key ] ?? null ) !== $value ) { throw new RuntimeException( 'Reminder settings did not persist.' ); } }
	if ( ! ec_zbs_reminder_enabled() ) { throw new RuntimeException( 'Reminder not ready.' ); }
} finally { ec_booking_unlock( $lock ); }
// No historical rows are enrolled and no provider calls are made here.
echo "REMINDER_ENABLED=659483; HOURS=2; HISTORICAL_ENROLLMENT=none\n";
