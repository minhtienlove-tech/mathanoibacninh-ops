<?php
/** Google Sheets outbox. Credentials stay in private options; no browser-side API keys. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ec_sheets_document_id() { return '1kgKcmxpD1twMcFR_mdiGlmTmoAlFhHV3bESOicDkXmk'; }
function ec_sheets_settings() {
	$s = get_option( 'ec_booking_sheets', array() );
	return wp_parse_args( is_array( $s ) ? $s : array(), array( 'enabled' => false, 'endpoint' => '', 'cipher' => '', 'changes_enabled' => false ) );
}
function ec_sheets_secret( $s = null ) { return ec_zalo_token( $s ?? ec_sheets_settings() ); }
function ec_sheets_valid_endpoint( $url ) {
	return is_string( $url ) && preg_match( '~^https://script\.google\.com/macros/s/[A-Za-z0-9_-]{20,256}/exec$~D', $url );
}
function ec_sheets_ready( $s ) { return ec_sheets_valid_endpoint( $s['endpoint'] ) && preg_match( '/^[a-f0-9]{64}$/D', ec_sheets_secret( $s ) ); }
function ec_sheets_enabled() { $s = ec_sheets_settings(); return ! empty( $s['enabled'] ) && ec_sheets_ready( $s ); }

/** Legacy appointments keep their original notification path. New marked records fail closed. */
function ec_booking_notifications_ready( $id ) {
	$post = get_post( $id );
	if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status ) { return false; }
	$data = ec_booking_read( $post );
	return empty( $data['sheet_required'] ) || ( 'saved' === get_post_meta( $id, '_ec_sheet_state', true ) && ec_sheets_document_id() === get_post_meta( $id, '_ec_sheet_document', true ) );
}

/** The content-service redirect is fetched without the signed request body or credentials. */
function ec_sheets_request( $payload ) {
	$s = ec_sheets_settings();
	if ( ! ec_sheets_ready( $s ) ) { return new WP_Error( 'config', 'Chưa kết nối Google Sheet.' ); }
	$payload['spreadsheet_id'] = ec_sheets_document_id();
	$payload['sheet_id'] = 0;
	$payload['issued_at'] = time();
	$json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$body = wp_json_encode( array( 'payload' => $json, 'signature' => hash_hmac( 'sha256', $json, ec_sheets_secret( $s ) ) ) );
	$response = wp_remote_post( $s['endpoint'], array( 'timeout' => 25, 'redirection' => 0, 'sslverify' => true, 'limit_response_size' => 8192, 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => $body ) );
	if ( is_wp_error( $response ) ) { return new WP_Error( 'transport', 'Chưa xác nhận được kết quả ghi Sheet; hệ thống sẽ thử lại cùng mã yêu cầu.' ); }
	$code = wp_remote_retrieve_response_code( $response );
	if ( in_array( $code, array( 302, 303 ), true ) ) {
		$location = wp_remote_retrieve_header( $response, 'location' );
		$parts = is_string( $location ) ? wp_parse_url( $location ) : false;
		if ( ! $parts || 'https' !== ( $parts['scheme'] ?? '' ) || 'script.googleusercontent.com' !== ( $parts['host'] ?? '' ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) ) { return new WP_Error( 'redirect', 'Google trả về chuyển hướng không hợp lệ.' ); }
		$response = wp_remote_get( $location, array( 'timeout' => 15, 'redirection' => 0, 'sslverify' => true, 'limit_response_size' => 8192 ) );
		if ( is_wp_error( $response ) ) { return new WP_Error( 'transport', 'Chưa đọc được xác nhận ghi Sheet.' ); }
		$code = wp_remote_retrieve_response_code( $response );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $code < 200 || $code >= 300 || ! is_array( $data ) || true !== ( $data['ok'] ?? false ) || ec_sheets_document_id() !== ( $data['spreadsheet_id'] ?? '' ) || 0 !== ( $data['sheet_id'] ?? null ) ) { return new WP_Error( 'ack', 'Google Sheet chưa xác nhận thành công đúng bảng và trang tính.' ); }
	return $data;
}

function ec_sheets_queue( $id ) {
	$post = get_post( $id );
	if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status || empty( ec_booking_read( $post )['sheet_required'] ) ) { return; }
	if ( ec_booking_notifications_ready( $id ) ) { ec_sheets_release( $id ); return; }
	if ( 'failed' === get_post_meta( $id, '_ec_sheet_state', true ) ) { return; }
	update_post_meta( $id, '_ec_sheet_state', 'pending' );
	$scheduled = wp_next_scheduled( 'ec_sheets_deliver', array( (int) $id ) ) ?: wp_schedule_single_event( time() + 2, 'ec_sheets_deliver', array( (int) $id ), true );
	if ( ! $scheduled || is_wp_error( $scheduled ) ) { update_post_meta( $id, '_ec_sheet_state', 'failed' ); update_post_meta( $id, '_ec_sheet_error', 'Chưa đưa được yêu cầu đồng bộ vào hàng đợi.' ); }
}
add_action( 'ec_booking_created', 'ec_sheets_queue', 5 );

function ec_sheets_release( $id ) {
	if ( ! ec_booking_notifications_ready( $id ) ) { return; }
	foreach ( array( 'gmail', 'zalo' ) as $channel ) {
		// Existing channel state includes uncertain/failed outcomes: never auto-resend those.
		if ( ! get_post_meta( $id, '_ec_sheet_dispatched_' . $channel, true ) ) {
			if ( in_array( get_post_meta( $id, '_ec_' . $channel . '_state', true ), array( '', 'waiting_sheet' ), true ) ) { call_user_func( 'ec_' . $channel . '_queue', $id ); }
			update_post_meta( $id, '_ec_sheet_dispatched_' . $channel, true );
		}
	}
	if ( get_post_meta( $id, '_ec_sheet_dispatched_gmail', true ) && get_post_meta( $id, '_ec_sheet_dispatched_zalo', true ) ) { delete_post_meta( $id, '_ec_sheet_recovery' ); }
}
function ec_sheets_deliver( $id ) {
	$lock = ec_booking_lock( 'sheet:' . (int) $id );
	if ( ! $lock ) { return; }
	try {
		$post = get_post( $id );
		if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status ) { return; }
		$d = ec_booking_read( $post );
		if ( empty( $d['sheet_required'] ) ) { return; }
		if ( ec_booking_notifications_ready( $id ) ) { ec_sheets_release( $id ); return; }
		if ( ! ec_sheets_enabled() ) { update_post_meta( $id, '_ec_sheet_state', 'failed' ); update_post_meta( $id, '_ec_sheet_error', 'Kết nối Sheet đang tắt hoặc chưa hoàn tất; thông báo chưa được gửi.' ); return; }
		$attempt = 1 + (int) get_post_meta( $id, '_ec_sheet_attempts', true );
		update_post_meta( $id, '_ec_sheet_attempts', $attempt );
		$key = (string) $post->post_name;
		$contact = ec_zalo_booking_contact( $d );
		$values = array( $key, (string) ( $d['received_at'] ?? '' ), $contact['name'], $contact['phone'], (string) ( $d['date'] ?? '' ), (string) ( $d['time'] ?? '' ), 'Yêu cầu chờ bệnh viện xác nhận', 'Website', (string) ( $d['received_at'] ?? '' ) );
		$result = ec_sheets_request( array( 'action' => 'append', 'key' => $key, 'values' => $values ) );
		$fingerprint = hash( 'sha256', wp_json_encode( $values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS ) );
		if ( ! is_wp_error( $result ) && ( $key !== ( $result['key'] ?? '' ) || ! is_int( $result['row'] ?? null ) || $result['row'] < 2 || $result['row'] > 20000 || ! is_string( $result['fingerprint'] ?? null ) || ! hash_equals( $fingerprint, $result['fingerprint'] ) ) ) { $result = new WP_Error( 'ack', 'Xác nhận Sheet không khớp mã yêu cầu và nội dung.' ); }
		if ( is_wp_error( $result ) ) {
			update_post_meta( $id, '_ec_sheet_error', $result->get_error_message() );
			if ( $attempt >= 12 ) { update_post_meta( $id, '_ec_sheet_state', 'failed' ); return; }
			update_post_meta( $id, '_ec_sheet_state', 'pending' );
			$scheduled = wp_schedule_single_event( time() + min( 3600, 30 * ( 2 ** min( $attempt - 1, 7 ) ) ), 'ec_sheets_deliver', array( (int) $id ), true );
			if ( ! $scheduled || is_wp_error( $scheduled ) ) { update_post_meta( $id, '_ec_sheet_state', 'failed' ); }
			return;
		}
		update_post_meta( $id, '_ec_sheet_document', ec_sheets_document_id() );
		update_post_meta( $id, '_ec_sheet_row', $result['row'] );
		update_post_meta( $id, '_ec_sheet_saved_at', time() );
		update_post_meta( $id, '_ec_sheet_state', 'saved' );
		update_post_meta( $id, '_ec_sheet_error', '' );
		ec_sheets_release( $id );
	} finally { ec_booking_unlock( $lock ); }
}
add_action( 'ec_sheets_deliver', 'ec_sheets_deliver' );

/** Indexed outbox plus a bounded rotating scan covers insert-before-meta crash gaps. */
function ec_sheets_recover() {
	$base = array( 'post_type' => 'ec_appointment', 'post_status' => 'private', 'posts_per_page' => 100, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' );
	$ids = get_posts( array_merge( $base, array( 'meta_query' => array( 'relation' => 'AND', array( 'key' => '_ec_sheet_recovery', 'value' => '1' ), array( 'relation' => 'OR', array( 'key' => '_ec_sheet_state', 'value' => 'failed', 'compare' => '!=' ), array( 'key' => '_ec_sheet_state', 'compare' => 'NOT EXISTS' ) ) ) ) ) );
	foreach ( $ids as $id ) { ec_sheets_queue( $id ); }
	// Move past failed records as well: a backlog must not starve newer pending rows.
	$page = max( 1, (int) get_option( 'ec_sheets_recovery_page', 1 ) );
	$scan = get_posts( array_merge( $base, array( 'paged' => $page ) ) );
	foreach ( $scan as $id ) { ec_sheets_queue( $id ); }
	update_option( 'ec_sheets_recovery_page', count( $scan ) < 100 ? 1 : $page + 1, false );
}
function ec_sheets_cron_intervals( $schedules ) { $schedules['ec_five_minutes'] = array( 'interval' => 300, 'display' => 'Mỗi 5 phút' ); return $schedules; }
add_filter( 'cron_schedules', 'ec_sheets_cron_intervals' );
add_action( 'ec_sheets_recover', 'ec_sheets_recover' );
function ec_sheets_bootstrap() {
	if ( ec_sheets_enabled() && ! wp_next_scheduled( 'ec_sheets_recover' ) ) { wp_schedule_event( time() + 300, 'ec_five_minutes', 'ec_sheets_recover' ); }
}
add_action( 'init', 'ec_sheets_bootstrap' );

/** Signed change events contain only metadata, never patient rows. */
function ec_sheets_change( $request ) {
	$s = ec_sheets_settings();
	if ( empty( $s['changes_enabled'] ) || ! ec_sheets_ready( $s ) ) { return new WP_Error( 'disabled', 'Chưa bật theo dõi Sheet.', array( 'status' => 503 ) ); }
	$raw = $request->get_body();
	if ( strlen( $raw ) > 4096 ) { return new WP_Error( 'input', 'Yêu cầu không hợp lệ.', array( 'status' => 400 ) ); }
	$envelope = json_decode( $raw, true );
	$payload = $envelope['payload'] ?? null;
	$signature = $envelope['signature'] ?? null;
	if ( ! is_string( $payload ) || ! is_string( $signature ) || ! preg_match( '/^[a-f0-9]{64}$/D', $signature ) || ! hash_equals( hash_hmac( 'sha256', $payload, ec_sheets_secret( $s ) ), $signature ) ) { return new WP_Error( 'auth', 'Không xác thực được yêu cầu.', array( 'status' => 403 ) ); }
	$d = json_decode( $payload, true );
	if ( ! is_array( $d ) || ec_sheets_document_id() !== ( $d['spreadsheet_id'] ?? '' ) || 0 !== ( $d['sheet_id'] ?? null ) || ! is_int( $d['issued_at'] ?? null ) || abs( time() - $d['issued_at'] ) > 300 || ! is_string( $d['event_id'] ?? null ) || ! preg_match( '/^[A-Za-z0-9_-]{16,100}$/D', $d['event_id'] ) || ! is_string( $d['kind'] ?? null ) || ! in_array( $d['kind'], array( 'edit', 'change', 'scan', 'insert', 'update', 'delete', 'mixed' ), true ) || ! is_int( $d['changed_rows'] ?? null ) || $d['changed_rows'] < 1 || $d['changed_rows'] > 100000 || ! is_string( $d['range'] ?? null ) || ! preg_match( '/^(?:[A-Z]{1,3}[1-9][0-9]{0,6}(?::[A-Z]{1,3}[1-9][0-9]{0,6})?|DATA)$/D', $d['range'] ) ) { return new WP_Error( 'input', 'Sự kiện Sheet không hợp lệ.', array( 'status' => 400 ) ); }
	$event_key = '_ec_sheet_change_' . hash( 'sha256', $d['event_id'] );
	$lock = ec_booking_lock( $event_key );
	if ( ! $lock ) { return new WP_Error( 'busy', 'Đang tiếp nhận sự kiện.', array( 'status' => 409 ) ); }
	try {
		$existing = get_option( $event_key, false );
		if ( false === $existing ) {
			$d['state'] = 'pending'; $d['sent'] = array(); $d['created_at'] = time(); $d['version'] = ec_zalo_settings()['version'];
			if ( ! add_option( $event_key, $d, '', false ) ) { return new WP_Error( 'storage', 'Chưa lưu được sự kiện.', array( 'status' => 503 ) ); }
			$recent = get_option( 'ec_sheets_recent_changes', array() );
			$recent = is_array( $recent ) ? $recent : array();
			update_option( 'ec_sheets_recent_changes', array_slice( array_values( array_unique( array_merge( array( $event_key ), $recent ) ) ), 0, 20 ), false );
		}
		if ( ! wp_next_scheduled( 'ec_sheets_change_deliver', array( $event_key ) ) && 'pending' === ( get_option( $event_key )['state'] ?? '' ) ) {
			$queued = wp_schedule_single_event( time() + 3, 'ec_sheets_change_deliver', array( $event_key ), true );
			if ( ! $queued || is_wp_error( $queued ) ) { return new WP_Error( 'queue', 'Chưa xếp được thông báo.', array( 'status' => 503 ) ); }
		}
		return array( 'ok' => true, 'event_id' => $d['event_id'] );
	} finally { ec_booking_unlock( $lock ); }
}
add_action( 'rest_api_init', static function () { register_rest_route( 'ec-booking/v1', '/sheet-change', array( 'methods' => 'POST', 'callback' => 'ec_sheets_change', 'permission_callback' => '__return_true' ) ); } );

function ec_sheets_change_deliver( $key ) {
	if ( ! is_string( $key ) || ! preg_match( '/^_ec_sheet_change_[a-f0-9]{64}$/D', $key ) ) { return; }
	$lock = ec_booking_lock( $key ); if ( ! $lock ) { return; }
	try {
		$d = get_option( $key ); $s = ec_zalo_settings();
		if ( ! is_array( $d ) || 'pending' !== ( $d['state'] ?? '' ) ) { return; }
		if ( empty( ec_sheets_settings()['changes_enabled'] ) || ! $s['enabled'] ) { $d['state'] = 'skipped'; update_option( $key, $d, false ); return; }
		if ( isset( $d['version'] ) && $d['version'] !== $s['version'] ) { $d['state'] = 'skipped'; update_option( $key, $d, false ); return; }
		$d['version'] = $s['version']; $d['state'] = 'sending'; $d['started_at'] = time();
		if ( ! update_option( $key, $d, false ) ) { return; }
		$text = "GOOGLE SHEET ĐẶT LỊCH ĐÃ THAY ĐỔI\nBảng: Đặt lịch khám BVMHNBN\nVùng dữ liệu: " . $d['range'] . "\nSố dòng thay đổi: " . $d['changed_rows'] . "\nXem bảng: https://docs.google.com/spreadsheets/d/" . ec_sheets_document_id() . '/edit#gid=0';
		$recipients = ec_zalo_selected_recipient_ids( $s );
		if ( ! $recipients ) { $d['state'] = 'failed'; update_option( $key, $d, false ); return; }
		foreach ( $recipients as $chat ) {
			$hash = ec_zalo_recipient_hash( $chat ); if ( in_array( $hash, $d['sent'], true ) ) { continue; }
			$r = ec_zalo_api( 'sendMessage', array( 'chat_id' => $chat, 'text' => $text ) );
			if ( is_wp_error( $r ) ) { $d['state'] = 'uncertain' === $r->get_error_code() ? 'unknown' : 'failed'; update_option( $key, $d, false ); return; }
			$d['sent'][] = $hash;
			if ( ! update_option( $key, $d, false ) ) { return; }
		}
		$d['state'] = 'sent'; update_option( $key, $d, false );
	} finally { ec_booking_unlock( $lock ); }
}
add_action( 'ec_sheets_change_deliver', 'ec_sheets_change_deliver' );

function ec_sheets_change_retry() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không có quyền gửi thông báo.' ); }
	$key = isset( $_GET['event'] ) && is_string( $_GET['event'] ) ? wp_unslash( $_GET['event'] ) : '';
	if ( ! preg_match( '/^_ec_sheet_change_[a-f0-9]{64}$/D', $key ) ) { wp_die( 'Sự kiện không hợp lệ.' ); }
	check_admin_referer( 'ec_sheets_change_retry_' . $key );
	$lock = ec_booking_lock( $key );
	if ( $lock ) {
		try {
			$d = get_option( $key ); $z = ec_zalo_settings();
			$state = is_array( $d ) ? ( $d['state'] ?? '' ) : '';
			$stale = 'sending' === $state && (int) ( $d['started_at'] ?? 0 ) < time() - 120;
			if ( ( $stale || in_array( $state, array( 'failed', 'unknown' ), true ) ) && ! empty( ec_sheets_settings()['changes_enabled'] ) && $z['enabled'] && ( $d['version'] ?? '' ) === $z['version'] ) {
				$d['state'] = 'pending'; update_option( $key, $d, false );
				wp_schedule_single_event( time() + 3, 'ec_sheets_change_deliver', array( $key ), true );
			}
		} finally { ec_booking_unlock( $lock ); }
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=ec_appointment&page=ec-sheets-settings' ) ); exit;
}
add_action( 'admin_post_ec_sheets_change_retry', 'ec_sheets_change_retry' );

function ec_sheets_menu() { add_submenu_page( 'edit.php?post_type=ec_appointment', 'Google Sheet đặt lịch', 'Google Sheet', 'manage_options', 'ec-sheets-settings', 'ec_sheets_page' ); }
add_action( 'admin_menu', 'ec_sheets_menu' );
function ec_sheets_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$s = ec_sheets_settings();
	echo '<div class="wrap"><h1>Google Sheet đặt lịch</h1><p>Form → ghi Google Sheet thành công → gửi Zalo và Gmail theo danh sách nhân viên đã cấu hình. Sửa dữ liệu trong Sheet → thông báo Zalo.</p>';
	echo '<p><a href="https://docs.google.com/spreadsheets/d/' . esc_attr( ec_sheets_document_id() ) . '/edit#gid=0" target="_blank" rel="noopener">Đặt lịch khám BVMHNBN · Trang tính1</a></p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ec_sheets_settings">';
	wp_nonce_field( 'ec_sheets_settings', 'ec_sheets_nonce' );
	echo '<p><label>URL Web App Apps Script<br><input type="url" name="endpoint" class="large-text" required value="' . esc_attr( $s['endpoint'] ) . '"></label></p>';
	echo '<p><label>Secret kết nối (64 ký tự hex)<br><input type="password" name="secret" value="" maxlength="64" autocomplete="new-password" placeholder="Để trống để giữ secret đã lưu"></label></p>';
	echo '<p><label><input type="checkbox" name="enabled" value="1" ' . checked( $s['enabled'], true, false ) . '> Bật ghi Sheet trước khi thông báo lịch mới</label></p>';
	echo '<p><label><input type="checkbox" name="changes_enabled" value="1" ' . checked( $s['changes_enabled'], true, false ) . '> Bật tiếp nhận thay đổi Sheet từ trigger để gửi Zalo</label></p>';
	echo '<p>Giữ bảng riêng tư. Cấu hình và cấp quyền Apps Script trước khi bật. Các lịch cũ không tự đồng bộ hoặc gửi lại.</p>';
	submit_button( 'Lưu và kiểm tra kết nối' ); echo '</form>';
	echo '<h2>Thông báo thay đổi Sheet gần đây</h2><p>Theo dõi dữ liệu trên toàn bộ các trang tính; thay đổi chỉ định dạng không gửi tin. Các chỉnh sửa qua API được kiểm tra mỗi 5 phút.</p>';
	$labels = array( 'pending' => 'Chờ gửi Zalo', 'sending' => 'Đang gửi; kiểm tra Zalo nếu kéo dài', 'sent' => 'Đã gửi Zalo', 'failed' => 'Zalo chưa chấp nhận thông báo', 'unknown' => 'Chưa rõ kết quả; kiểm tra tin trên Zalo trước khi gửi lại', 'skipped' => 'Bỏ qua do đã tắt hoặc thay đổi cấu hình' );
	$recent = get_option( 'ec_sheets_recent_changes', array() );
	foreach ( is_array( $recent ) ? $recent : array() as $key ) {
		if ( ! is_string( $key ) || ! preg_match( '/^_ec_sheet_change_[a-f0-9]{64}$/D', $key ) ) { continue; }
		$d = get_option( $key );
		if ( ! is_array( $d ) ) { continue; }
		echo '<p><strong>' . esc_html( wp_date( 'd/m/Y H:i', (int) $d['created_at'] ) ) . '</strong> · ' . esc_html( $d['range'] ) . ' · ' . (int) $d['changed_rows'] . ' dòng · ' . esc_html( $labels[ $d['state'] ] ?? 'Chưa xác định' );
		$stale = 'sending' === $d['state'] && (int) ( $d['started_at'] ?? 0 ) < time() - 120;
		if ( ( $stale || in_array( $d['state'], array( 'failed', 'unknown' ), true ) ) && ec_zalo_settings()['enabled'] && ( $d['version'] ?? '' ) === ec_zalo_settings()['version'] ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=ec_sheets_change_retry&event=' . rawurlencode( $key ) ), 'ec_sheets_change_retry_' . $key );
			echo ' <a class="button" href="' . esc_url( $url ) . '">Đã kiểm tra Zalo · gửi lại người chưa xác nhận</a>';
		}
		echo '</p>';
	}
	echo '</div>';
}
function ec_sheets_admin_save() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không có quyền cấu hình.' ); }
	check_admin_referer( 'ec_sheets_settings', 'ec_sheets_nonce' );
	$old = ec_sheets_settings(); $s = $old;
	if ( ! is_string( $_POST['endpoint'] ?? null ) || ! is_string( $_POST['secret'] ?? null ) ) { wp_die( 'Cấu hình không hợp lệ.' ); }
	$s['endpoint'] = trim( wp_unslash( $_POST['endpoint'] ) );
	$secret = trim( wp_unslash( $_POST['secret'] ) );
	if ( ! ec_sheets_valid_endpoint( $s['endpoint'] ) || ( '' !== $secret && ! preg_match( '/^[a-f0-9]{64}$/D', $secret ) ) ) { wp_die( 'URL hoặc secret không hợp lệ.' ); }
	if ( '' !== $secret ) { $s['cipher'] = ec_zalo_encrypt( $secret ); if ( is_wp_error( $s['cipher'] ) ) { wp_die( 'Không lưu được secret.' ); } }
	if ( ! ec_sheets_ready( $s ) ) { wp_die( 'Chưa có secret kết nối.' ); }
	$s['enabled'] = isset( $_POST['enabled'] ); $s['changes_enabled'] = isset( $_POST['changes_enabled'] );
	// Probe before changing the live gate; only a fixed, signed destination can be enabled.
	$GLOBALS['ec_sheets_probe_settings'] = $s;
	add_filter( 'pre_option_ec_booking_sheets', 'ec_sheets_probe_option' );
	$r = ec_sheets_request( array( 'action' => 'probe' ) );
	remove_filter( 'pre_option_ec_booking_sheets', 'ec_sheets_probe_option' ); unset( $GLOBALS['ec_sheets_probe_settings'] );
	if ( is_wp_error( $r ) ) { wp_die( esc_html( $r->get_error_message() ) ); }
	update_option( 'ec_booking_sheets', $s, false );
	wp_safe_redirect( admin_url( 'edit.php?post_type=ec_appointment&page=ec-sheets-settings' ) ); exit;
}
function ec_sheets_probe_option() { return $GLOBALS['ec_sheets_probe_settings']; }
add_action( 'admin_post_ec_sheets_settings', 'ec_sheets_admin_save' );

function ec_sheets_box( $post ) {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$d = ec_booking_read( $post );
	if ( empty( $d['sheet_required'] ) ) { echo '<p>Lịch thuộc luồng trước khi bật Google Sheet.</p>'; return; }
	$labels = array( 'pending' => 'Đang chờ ghi Google Sheet; chưa gửi thông báo.', 'saved' => 'Đã ghi Google Sheet.', 'failed' => 'Chưa ghi được Google Sheet; chưa gửi thông báo.' );
	$state = get_post_meta( $post->ID, '_ec_sheet_state', true );
	echo '<p>' . esc_html( $labels[ $state ] ?? 'Đang chờ đồng bộ.' ) . '</p>';
	if ( 'saved' === $state ) { echo '<p>Dòng: ' . (int) get_post_meta( $post->ID, '_ec_sheet_row', true ) . '</p>'; }
	if ( 'failed' === $state ) {
		echo '<p>' . esc_html( get_post_meta( $post->ID, '_ec_sheet_error', true ) ) . '</p>';
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=ec_sheets_retry&appointment=' . (int) $post->ID ), 'ec_sheets_retry_' . $post->ID );
		echo '<p><a class="button" href="' . esc_url( $url ) . '">Thử ghi Sheet lại</a></p>';
	}
}
add_action( 'add_meta_boxes_ec_appointment', static function () { add_meta_box( 'ec-sheet-delivery', 'Google Sheet', 'ec_sheets_box', 'ec_appointment', 'side' ); } );
function ec_sheets_retry() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không có quyền đồng bộ.' ); }
	$id = isset( $_GET['appointment'] ) && is_string( $_GET['appointment'] ) ? absint( $_GET['appointment'] ) : 0;
	check_admin_referer( 'ec_sheets_retry_' . $id );
	if ( ! ec_booking_notifications_ready( $id ) && 'failed' === get_post_meta( $id, '_ec_sheet_state', true ) ) { update_post_meta( $id, '_ec_sheet_attempts', 0 ); update_post_meta( $id, '_ec_sheet_state', 'retry' ); ec_sheets_queue( $id ); }
	wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) ); exit;
}
add_action( 'admin_post_ec_sheets_retry', 'ec_sheets_retry' );
