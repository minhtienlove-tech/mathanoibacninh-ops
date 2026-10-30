<?php
/**
 * Chuẩn hóa số điện thoại/họ tên, chống trùng cùng ngày, giới hạn theo IP/thiết bị và mã thời gian form.
 * Chạy: php theme/eyecare-child/tests/booking-antispam-test.php
 */
require __DIR__ . '/booking-validation-test.php';

// Mọi cách viết cùng một số Việt Nam cho cùng một kết quả.
$vn_forms = array( '0912345678', '0912.345.678', '0912-345-678', '(091) 234 5678', '+84 912 345 678', '+840912345678', '84912345678', '0084912345678', '912345678', '０９１２３４５６７８' );
foreach ( $vn_forms as $form ) {
	ec_expect( ec_booking_normalize_phone( $form ) === '0912345678', 'Phone form normalizes to one value: ' . $form );
}
ec_expect( ec_booking_normalize_phone( '024 3825 1234' ) === '02438251234', 'Landline 02x with 11 digits accepted' );
ec_expect( ec_booking_normalize_phone( '+1 202 555 0123' ) === '+12025550123', 'Foreign number kept in E.164' );
foreach ( array( '0123456789', '091234567', '09123456789', '0612345678', '0999999999', '01234567890', 'abc', '', '+0000000000', '090<script>' ) as $bad ) {
	ec_expect( is_wp_error( ec_booking_normalize_phone( $bad ) ), 'Invalid phone rejected: ' . $bad );
}
ec_expect( false !== strpos( ec_booking_normalize_phone( '01234567890' )->get_error_message(), '10 số' ), 'Old 11-digit mobile prefix gets a specific hint' );
ec_expect( ec_booking_is_vn_mobile( '0912345678' ) && ! ec_booking_is_vn_mobile( '02438251234' ) && ! ec_booking_is_vn_mobile( '+12025550123' ), 'Only VN mobiles qualify for Zalo' );

// Họ tên: gộp khoảng trắng lạ, viết hoa đầu từ khi toàn hoa/toàn thường, từ chối số và ký hiệu.
ec_expect( ec_booking_clean_name( "  nguyễn\u{00A0}văn   an " ) === 'Nguyễn Văn An', 'Name spacing and casing cleaned' );
ec_expect( ec_booking_clean_name( 'Nguyễn Văn McKay' ) === 'Nguyễn Văn McKay', 'Mixed-case name left as typed' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'name' => 'Nguyen 123' ) ), $now ), 'name', 'Digits in name rejected' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'name' => '. -' ) ), $now ), 'name', 'Punctuation-only name rejected' );
$clean = ec_booking_validate( array_merge( $valid, array( 'name' => 'trần thị bình', 'phone' => '912 345 678' ) ), $now );
ec_expect( ! is_wp_error( $clean ) && $clean['name'] === 'Trần Thị Bình' && $clean['phone'] === '0912345678', 'Validated booking stores cleaned name and normalized phone' );

// Chống trùng: cùng số + cùng ngày trả lại lịch cũ, không tạo bản ghi mới.
$GLOBALS['ec_test_posts'] = array();
$GLOBALS['ec_test_transients'] = array();
$GLOBALS['ec_test_fail_insert'] = false;
$_SERVER['REMOTE_ADDR'] = '198.51.100.7';
$base = ec_booking_validate( array_merge( $valid, array( 'date' => '2026-09-14', 'request_id' => '223e4567-e89b-42d3-a456-426614174000' ) ), $now );
$first_id = ec_booking_store( $base );
ec_expect( is_int( $first_id ), 'First booking stored' );
$inserted = $GLOBALS['ec_test_insert_count'];
$again = ec_booking_store( array_merge( $base, array( 'time' => '09:00', 'request_id' => '223e4567-e89b-42d3-a456-426614174001' ) ) );
ec_expect( is_wp_error( $again ) && 'duplicate' === $again->get_error_code(), 'Same phone and day returns duplicate' );
ec_expect( $GLOBALS['ec_test_insert_count'] === $inserted, 'Duplicate creates no new record' );
ec_expect( (int) ( $again->get_error_data()['appointment_id'] ?? 0 ) === $first_id, 'Duplicate points to the existing appointment' );
ec_expect( false !== strpos( $again->get_error_message(), '07:30' ), 'Same person sees the time already booked' );
ec_expect( 0 === strpos( $again->get_error_message(), 'Không tạo lịch mới' ), 'Duplicate message says first that no new booking was made' );
$other_person = ec_booking_store( array_merge( $base, array( 'name' => 'Lê Văn Khác', 'request_id' => '223e4567-e89b-42d3-a456-426614174002' ) ) );
ec_expect( is_wp_error( $other_person ) && false === strpos( $other_person->get_error_message(), '07:30' ), 'Different name is not shown the existing time' );
$next_day = ec_booking_store( array_merge( $base, array( 'date' => '2026-09-15', 'request_id' => '223e4567-e89b-42d3-a456-426614174003' ) ) );
ec_expect( is_int( $next_day ), 'Same phone on another day is allowed' );
$GLOBALS['ec_test_posts'][ $first_id - 1 ]->meta_input['_ec_booking_status'] = 'cancelled';
$after_cancel = ec_booking_store( array_merge( $base, array( 'request_id' => '223e4567-e89b-42d3-a456-426614174004' ) ) );
ec_expect( is_int( $after_cancel ), 'Cancelled booking does not block rebooking the same day' );
ec_expect( ec_booking_store( $base ) === $first_id, 'Retry of the original request still replays' );

// Giới hạn theo thiết bị: đổi IP không lách được khi cùng mã thiết bị hoặc cùng dấu vân tay phần cứng.
$GLOBALS['ec_test_transients'] = array();
$device = array( 'device_id' => '9a3e4567-e89b-42d3-a456-426614174000' );
for ( $i = 0; $i < 5; $i++ ) {
	$ctx = array_merge( $device, array( 'ip' => '203.0.113.' . $i ) );
	ec_expect( ec_booking_rate_limit( '09' . str_pad( (string) ( 10000000 + $i ), 8, '0', STR_PAD_LEFT ), $ctx ), 'Device request ' . ( $i + 1 ) . ' allowed' );
}
ec_expect( ! ec_booking_rate_limit( '0987000001', array_merge( $device, array( 'ip' => '203.0.113.99' ) ) ), 'Sixth request from same device blocked despite new IP and phone' );
$GLOBALS['ec_test_transients'] = array();
$hw = array( 'device_hash' => str_repeat( 'ab', 32 ) );
for ( $i = 0; $i < 8; $i++ ) {
	ec_booking_rate_limit( '09870000' . sprintf( '%02d', $i ), array_merge( $hw, array( 'ip' => '203.0.114.' . $i, 'device_id' => sprintf( '9a3e4567-e89b-42d3-a456-4266141740%02d', $i ) ) ) );
}
ec_expect( ! ec_booking_rate_limit( '0987000099', array_merge( $hw, array( 'ip' => '203.0.114.200', 'device_id' => '9a3e4567-e89b-42d3-a456-426614174099' ) ) ), 'Clearing browser storage does not reset the hardware fingerprint limit' );
$GLOBALS['ec_test_transients'] = array();
ec_expect( ec_booking_client_ip() === '198.51.100.7', 'IPv4 kept as is' );
$_SERVER['REMOTE_ADDR'] = '2001:db8:1:2:aaaa::1';
$v6a = ec_booking_client_ip();
$_SERVER['REMOTE_ADDR'] = '2001:db8:1:2:bbbb::9';
ec_expect( $v6a === ec_booking_client_ip(), 'IPv6 addresses in the same /64 share one limit' );
$keys = implode( '|', array_keys( $GLOBALS['ec_test_transients'] ) );
ec_booking_rate_limit( '0912345678', array( 'ip' => '198.51.100.7', 'device_id' => $device['device_id'] ) );
$keys = implode( '|', array_keys( $GLOBALS['ec_test_transients'] ) );
ec_expect( false === strpos( $keys, '198.51.100.7' ) && false === strpos( $keys, $device['device_id'] ) && false === strpos( $keys, '0912345678' ), 'Rate keys never contain raw IP, device ID or phone' );
$api = array( 'client' => 'zalo-bot', 'client_limit' => 2, 'ip' => '192.0.2.50' );
$GLOBALS['ec_test_transients'] = array();
ec_expect( ec_booking_rate_limit( '0911000001', $api ) && ec_booking_rate_limit( '0911000002', $api ) && ! ec_booking_rate_limit( '0911000003', $api ), 'API clients are limited per key, not per server IP' );

// Mã thời gian form: gửi trong 2 giây đầu là bot; mã thiếu hoặc lạ không chặn người thật.
$token_time = 1800000000;
ec_expect_error( ec_booking_check_form_token( ec_booking_form_token( $token_time ), $token_time + 1 ), 'too_fast', 'Submit within 2 seconds rejected' );
ec_expect( true === ec_booking_check_form_token( ec_booking_form_token( $token_time ), $token_time + 3 ), 'Normal human timing accepted' );
ec_expect( true === ec_booking_check_form_token( '', $token_time ) && true === ec_booking_check_form_token( '1800000000.' . str_repeat( '0', 32 ), $token_time ), 'Missing or forged token does not block cached pages' );

// Một ô đồng ý duy nhất gồm cả tin xác nhận Zalo: số di động nhận tin, số khác vẫn đặt được; lưu đúng câu đã hiển thị.
ec_expect( false !== strpos( ec_booking_contact_consent_text( true ), 'Zalo OA' ) && false === strpos( ec_booking_contact_consent_text(), 'Zalo OA' ), 'Combined wording mentions Zalo OA only when ZBS is shown' );
$GLOBALS['ec_test_posts'] = array();
$GLOBALS['ec_test_transients'] = array();
$combined = ec_booking_validate( array_merge( $valid, array( 'consent_zalo' => '1', 'request_id' => '323e4567-e89b-42d3-a456-426614174000' ) ), $now );
ec_expect( ! is_wp_error( $combined ) && true === $combined['patient_zalo_consent'], 'Required consent with Zalo clause enables ZBS for a VN mobile' );
$combined_id = ec_booking_store( $combined );
$combined_row = ec_booking_read( get_post( $combined_id ) );
ec_expect( $combined_row['consent_text'] === ec_booking_contact_consent_text( true ) && '2026-10-30-short-zalo-v3' === $combined_row['consent_version'], 'Stored consent is the combined sentence the patient ticked' );
ec_expect( $combined_row['patient_zalo_consent_text'] === $combined_row['consent_text'] && '2026-10-30-in-required-consent-v4' === $combined_row['patient_zalo_consent_version'] && ! empty( $combined_row['patient_zalo_consented_at'] ), 'Zalo consent evidence points to the same combined sentence' );
ec_expect( ec_booking_store( $combined ) === $combined_id, 'Retry of a combined-consent request replays' );
$landline = ec_booking_validate( array_merge( $valid, array( 'phone' => '024 3825 1234', 'consent_zalo' => '1', 'request_id' => '323e4567-e89b-42d3-a456-426614174001' ) ), $now );
ec_expect( ! is_wp_error( $landline ) && false === $landline['patient_zalo_consent'], 'Landline with combined consent books without a Zalo message' );
$plain = ec_booking_validate( array_merge( $valid, array( 'request_id' => '323e4567-e89b-42d3-a456-426614174002' ) ), $now );
ec_expect( ! is_wp_error( $plain ) && false === $plain['patient_zalo_consent'], 'Without the Zalo clause no Zalo consent is implied' );
$plain_row = ec_booking_read( get_post( ec_booking_store( array_merge( $plain, array( 'date' => '2026-09-12' ) ) ) ) );
ec_expect( $plain_row['consent_text'] === ec_booking_contact_consent_text() && '2026-10-29-short-v2' === $plain_row['consent_version'] && ! isset( $plain_row['patient_zalo_consent_text'] ), 'Form without ZBS stores the plain sentence and no Zalo evidence' );
ec_expect_error( ec_booking_validate( array_merge( $valid, array( 'consent_zalo' => 'yes' ) ), $now ), 'input', 'Malformed consent_zalo rejected' );
$legacy_box = ec_booking_validate( array_merge( $valid, array( 'patient_zalo_consent' => '1', 'request_id' => '323e4567-e89b-42d3-a456-426614174003' ) ), $now );
ec_expect( ! is_wp_error( $legacy_box ) && true === $legacy_box['patient_zalo_consent'] && false === $legacy_box['zalo_in_consent'], 'Cached old form with the separate Zalo box still works' );
ec_expect( ec_booking_fingerprint_payload( $combined ) === ec_booking_fingerprint_payload( array_merge( $combined, array( 'zalo_in_consent' => false ) ) ), 'Fingerprint binds the Zalo outcome, not which wording was shown' );

echo 'PASS: ' . $assertions . " anti-spam, duplicate and phone/name normalization checks.\n";
