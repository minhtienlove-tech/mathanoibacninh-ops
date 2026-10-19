<?php
/** WP-CLI only: update one public paragraph or run pure, non-persisting validation. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 1 ); }

$mode = getenv( 'EYECARE_BOOKING_CUTOFF_MODE' ) ?: 'verify';
$backup = getenv( 'EYECARE_BOOKING_CUTOFF_BACKUP' );
$post = get_post( 57 );
if ( ! $post || 'page' !== $post->post_type || 'dat-lich-kham' !== $post->post_name ) { WP_CLI::error( 'Booking page identity changed.' ); }

if ( 'apply' === $mode ) {
    if ( ! $backup || ! is_dir( $backup ) || substr_count( $post->post_content, '17:00' ) !== 1 || false === strpos( $post->post_content, 'mốc hẹn đến 17:00' ) ) { WP_CLI::error( 'Unexpected page content or missing backup directory.' ); }
    if ( false === file_put_contents( $backup . '/page57-before.html', $post->post_content ) ) { WP_CLI::error( 'Page backup failed.' ); }
    $next = str_replace( 'mốc hẹn đến 17:00', 'mốc hẹn đến 16:00', $post->post_content, $changed );
    if ( 1 !== $changed ) { WP_CLI::error( 'Expected exactly one booking paragraph replacement.' ); }
    $updated = wp_update_post( wp_slash( array( 'ID' => 57, 'post_content' => $next ) ), true );
    if ( is_wp_error( $updated ) ) { WP_CLI::error( 'Page update failed.' ); }
    WP_CLI::log( 'PAGE57_REPLACEMENTS=1' );
} elseif ( 'rollback' === $mode ) {
    if ( ! $backup || ! is_file( $backup . '/page57-before.html' ) ) { WP_CLI::error( 'Page backup missing.' ); }
    $updated = wp_update_post( wp_slash( array( 'ID' => 57, 'post_content' => file_get_contents( $backup . '/page57-before.html' ) ) ), true );
    if ( is_wp_error( $updated ) ) { WP_CLI::error( 'Page rollback failed.' ); }
    WP_CLI::log( 'PAGE57_ROLLBACK=ok' );
    return;
} elseif ( 'verify' !== $mode ) { WP_CLI::error( 'Unknown operation.' ); }

$now = ec_booking_now();
$schedule = ec_booking_schedule( $now );
if ( is_wp_error( $schedule ) || count( $schedule['slots'] ) !== 18 || end( $schedule['slots'] ) !== '16:00' || $schedule['open'] !== '07:30' || $schedule['close'] !== '18:00' ) { WP_CLI::error( 'Unexpected live booking schedule.' ); }
$input = array( 'name' => 'Kiểm Thử', 'phone' => '0912345678', 'date' => $now->modify( '+1 day' )->format( 'Y-m-d' ), 'time' => '16:00', 'consent' => '1', 'website' => '', 'request_id' => '123e4567-e89b-42d3-a456-426614174000' );
// Only the pure validation function is called: no submission, post, rate-limit,
// notification or patient-record operation is invoked by these checks.
if ( is_wp_error( ec_booking_validate( $input, $now ) ) ) { WP_CLI::error( '16:00 should be accepted by pure validation.' ); }
foreach ( array( '16:30', '17:00' ) as $time ) {
    $input['time'] = $time;
    $value = ec_booking_validate( $input, $now );
    if ( ! is_wp_error( $value ) || 'time' !== $value->get_error_code() ) { WP_CLI::error( 'After-cutoff time was not rejected.' ); }
}
$saved = get_post( 57 );
if ( false !== strpos( $saved->post_content, '17:00' ) || false === strpos( $saved->post_content, 'mốc hẹn đến 16:00' ) ) { WP_CLI::error( 'Public page paragraph did not match cutoff.' ); }
WP_CLI::success( '18 slots; 16:00 accepted; 16:30 and 17:00 rejected; opening unchanged; no appointments written.' );
