<?php
/** Restore only reminder options if they still match this deployment; preserve any rotated tokens. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) { exit( 1 ); }
$backup = $args[0] ?? '';
if ( ! is_string( $backup ) || ! preg_match( '~^/home/jwhxtzru/backups/zbs-reminder-20261030-[0-9]{6}$~D', $backup ) ) { throw new RuntimeException( 'Invalid backup directory.' ); }
$path = $backup . '/reminder-settings-before.json';
if ( ! is_file( $path ) ) { return; }
$previous = json_decode( file_get_contents( $path ), true );
$lock = ec_booking_lock( 'zbs-token' );
if ( ! $lock ) { throw new RuntimeException( 'ZBS settings busy; restore reminder options manually from backup.' ); }
try {
	$current = get_option( 'ec_booking_zbs', array() );
	$expected = array( 'template_reminder_id' => '659483', 'reminder_map' => array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' ), 'reminder_enabled' => true, 'reminder_hours' => 2 );
	foreach ( $expected as $key => $value ) { if ( ( $current[ $key ] ?? null ) !== $value ) { throw new RuntimeException( 'Reminder settings changed; refusing to overwrite.' ); } }
	foreach ( $previous as $key => $field ) { if ( $field['exists'] ) { $current[ $key ] = $field['value']; } else { unset( $current[ $key ] ); } }
	update_option( 'ec_booking_zbs', $current, false );
} finally { ec_booking_unlock( $lock ); }
