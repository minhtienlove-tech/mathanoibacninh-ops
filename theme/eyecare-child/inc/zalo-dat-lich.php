<?php
/** Zalo Bot notifications. Official API: docs.zaloplatforms.com/docs/BOT/apis/sendMessage */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ec_zalo_settings() {
	$raw = get_option( 'ec_booking_zalo', array() );
	return wp_parse_args( is_array( $raw ) ? $raw : array(), array( 'enabled' => false, 'cipher' => '', 'webhook_cipher' => '', 'chat_id' => '', 'bot_name' => '', 'candidates' => array(), 'version' => '', 'webhook_last_at' => 0, 'webhook_last_probe_at' => 0, 'webhook_last_event' => 'never', 'webhook_last_outcome' => 'never', 'webhook_last_command' => 'none', 'webhook_last_candidate_count' => 0 ) );
}

/** Token is encrypted in the database; never returned to a browser or written to Git. */
function ec_zalo_encrypt( $token ) {
	if ( ! function_exists( 'openssl_encrypt' ) ) { return new WP_Error( 'crypto', 'Máy chủ chưa hỗ trợ lưu token bảo mật.' ); }
	try {
		$iv = random_bytes( 12 );
		$tag = '';
		$cipher = openssl_encrypt( $token, 'aes-256-gcm', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, $iv, $tag );
		return false === $cipher ? new WP_Error( 'crypto', 'Không thể lưu token bảo mật.' ) : base64_encode( $iv . $tag . $cipher );
	} catch ( Throwable $error ) { return new WP_Error( 'crypto', 'Không thể lưu token bảo mật.' ); }
}

function ec_zalo_token( $settings = null ) {
	$settings = $settings ?? ec_zalo_settings();
	if ( ! function_exists( 'openssl_decrypt' ) || ! is_string( $settings['cipher'] ) ) { return ''; }
	$raw = base64_decode( $settings['cipher'], true );
	if ( false === $raw || strlen( $raw ) < 29 ) { return ''; }
	$token = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', hash( 'sha256', wp_salt( 'auth' ), true ), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ) );
	return is_string( $token ) ? $token : '';
}

function ec_zalo_valid_token( $token ) { return is_string( $token ) && preg_match( '/^[A-Za-z0-9:_.-]{10,256}$/D', $token ); }
function ec_zalo_valid_chat( $chat ) { return is_string( $chat ) && preg_match( '/^[A-Za-z0-9_.:-]{1,128}$/D', $chat ); }
function ec_zalo_valid_webhook_secret( $secret ) { return is_string( $secret ) && preg_match( '/^[A-Za-z0-9._~-]{8,256}$/D', $secret ); }

function ec_zalo_webhook_secret( $settings = null ) {
	$settings = $settings ?? ec_zalo_settings();
	return ec_zalo_token( array( 'cipher' => $settings['webhook_cipher'] ?? '' ) );
}

function ec_zalo_webhook_url() { return rest_url( 'ec-booking/v1/zalo-webhook' ); }

/** Fixed HTTPS endpoint and no redirects prevent accidental token disclosure. */
function ec_zalo_api( $method, $body = array(), $token = null ) {
	$token = $token ?? ec_zalo_token();
	if ( ! in_array( $method, array( 'getMe', 'getUpdates', 'setWebhook', 'testWebhook', 'getWebhookInfo', 'sendMessage' ), true ) || ! ec_zalo_valid_token( $token ) ) { return new WP_Error( 'config', 'Vui lòng lưu Bot Token hợp lệ trước.' ); }
	$response = wp_remote_post( 'https://bot-api.zaloplatforms.com/bot' . $token . '/' . $method, array( 'timeout' => 10, 'redirection' => 0, 'sslverify' => true, 'limit_response_size' => 65536, 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( (object) $body ) ) );
	// Never persist or display raw HTTP errors: they may contain the token-bearing URL.
	if ( is_wp_error( $response ) ) { return new WP_Error( 'uncertain', 'Chưa nhận được phản hồi từ Zalo. Kiểm tra tin nhắn trước khi gửi lại.' ); }
	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $code >= 200 && $code < 300 && is_array( $data ) && true === ( $data['ok'] ?? false ) && isset( $data['result'] ) && is_array( $data['result'] ) ) {
		if ( 'sendMessage' === $method && ( ! is_scalar( $data['result']['message_id'] ?? null ) || '' === (string) $data['result']['message_id'] ) ) { return new WP_Error( 'uncertain', 'Zalo chưa xác nhận mã tin nhắn. Kiểm tra trên Zalo trước khi gửi lại.' ); }
		return $data['result'];
	}
	// Long polling returns 408 when no new event arrives in the requested window.
	if ( 'getUpdates' === $method && is_array( $data ) && 408 === (int) ( $data['error_code'] ?? 0 ) ) { return array(); }
	if ( $code >= 500 || ( $code >= 200 && $code < 300 && ! is_array( $data ) ) ) { return new WP_Error( 'uncertain', 'Zalo chưa xác nhận kết quả. Kiểm tra tin nhắn trước khi gửi lại.' ); }
	return new WP_Error( 'api', 'Zalo chưa chấp nhận yêu cầu. Kiểm tra Bot Token, Chat ID và việc người nhận đã nhắn tin cho bot.' );
}

/** Only /nhanlich opt-in messages are offered as recipient candidates. */
function ec_zalo_candidates( $result ) {
	if ( isset( $result['result'] ) && is_array( $result['result'] ) ) { $result = $result['result']; }
	$events = isset( $result['message'] ) ? array( $result ) : $result;
	$found = array();
	foreach ( $events as $event ) {
		if ( ! is_array( $event ) ) { continue; }
		$msg = $event['message'] ?? array();
		if ( ! is_array( $msg ) ) { continue; }
		$id = $msg['chat']['id'] ?? '';
		if ( ! is_array( $msg ) || ! is_string( $msg['text'] ?? null ) || '/nhanlich' !== trim( $msg['text'] ) || ! ec_zalo_valid_chat( $id ) || ! empty( $msg['from']['is_bot'] ) ) { continue; }
		$name = $msg['from']['display_name'] ?? $id;
		$found[ $id ] = is_string( $name ) ? sanitize_text_field( $name ) : $id;
		if ( count( $found ) >= 20 ) { break; }
	}
	return $found;
}

function ec_zalo_candidate_list( $settings = null ) {
	$settings = $settings ?? ec_zalo_settings();
	$found = array();
	foreach ( is_array( $settings['candidates'] ?? null ) ? $settings['candidates'] : array() as $id => $name ) {
		$id = (string) $id;
		if ( ec_zalo_valid_chat( $id ) ) { $found[ $id ] = sanitize_text_field( is_string( $name ) ? $name : $id ); }
		if ( count( $found ) >= 20 ) { break; }
	}
	return $found;
}

function ec_zalo_store_candidates( $found ) {
	if ( ! is_array( $found ) || ! $found ) { return; }
	$settings = ec_zalo_settings();
	$candidates = ec_zalo_candidate_list( $settings );
	foreach ( $found as $id => $name ) {
		$id = (string) $id;
		if ( ec_zalo_valid_chat( $id ) ) { $candidates[ $id ] = sanitize_text_field( is_string( $name ) ? $name : $id ); }
	}
	$settings['candidates'] = array_slice( $candidates, -20, 20, true );
	update_option( 'ec_booking_zalo', $settings, false );
}

/** Safe-only diagnostic result. It never contains API descriptions, tokens, secrets or chat data. */
function ec_zalo_diagnose_webhook( $settings = null ) {
	$settings = $settings ?? ec_zalo_settings();
	$result = array( 'checked_at' => (int) current_time( 'timestamp', true ), 'bot' => 'missing', 'secret' => ec_zalo_valid_webhook_secret( ec_zalo_webhook_secret( $settings ) ) ? 'saved' : 'missing', 'webhook' => 'unknown', 'endpoint' => 'unknown' );
	$token = ec_zalo_token( $settings );
	if ( ! ec_zalo_valid_token( $token ) ) { return $result; }
	$bot = ec_zalo_api( 'getMe', array(), $token );
	$result['bot'] = is_wp_error( $bot ) ? ( 'uncertain' === $bot->get_error_code() ? 'uncertain' : 'rejected' ) : 'ok';
	if ( 'ok' !== $result['bot'] ) { return $result; }
	$webhook = ec_zalo_api( 'getWebhookInfo', array(), $token );
	if ( is_wp_error( $webhook ) ) {
		$result['webhook'] = 'uncertain' === $webhook->get_error_code() ? 'uncertain' : 'unknown';
	} else {
		$remote_url = is_string( $webhook['url'] ?? null ) ? $webhook['url'] : ( is_string( $webhook['webhook_url'] ?? null ) ? $webhook['webhook_url'] : ( is_string( $webhook['webhook']['url'] ?? null ) ? $webhook['webhook']['url'] : '' ) );
		$result['webhook'] = '' === $remote_url ? 'missing' : ( hash_equals( rtrim( ec_zalo_webhook_url(), '/' ), rtrim( $remote_url, '/' ) ) ? 'matched' : 'mismatch' );
	}
	$endpoint = ec_zalo_api( 'testWebhook', array(), $token );
	if ( is_wp_error( $endpoint ) ) {
		$result['endpoint'] = 'uncertain' === $endpoint->get_error_code() ? 'uncertain' : 'failed';
		return $result;
	}
	$webhook_result = is_array( $endpoint['webhook'] ?? null ) ? $endpoint['webhook'] : $endpoint;
	$outcome = is_string( $webhook_result['outcome'] ?? null ) ? strtolower( $webhook_result['outcome'] ) : ( is_string( $webhook_result['status'] ?? null ) ? strtolower( $webhook_result['status'] ) : ( is_string( $webhook_result['code'] ?? null ) ? strtolower( $webhook_result['code'] ) : '' ) );
	if ( true === ( $webhook_result['ok'] ?? null ) || 'webhook.ok' === $outcome ) {
		$result['endpoint'] = 'ok';
	} elseif ( str_contains( $outcome, 'tls' ) ) {
		$result['endpoint'] = 'tls';
	} elseif ( str_contains( $outcome, 'unreachable' ) ) {
		$result['endpoint'] = 'unreachable';
	} elseif ( '' !== $outcome ) {
		$result['endpoint'] = 'failed';
	} else {
		$result['endpoint'] = 'unknown';
	}
	return $result;
}

function ec_zalo_record_webhook_activity( $event, $outcome, $command = 'none' ) {
	$events = array( 'never', 'verification', 'message', 'unknown' );
	$outcomes = array( 'never', 'recipient_found', 'not_command', 'unsupported', 'duplicate' );
	$commands = array( 'none', 'nhanlich' );
	$event = in_array( $event, $events, true ) ? $event : 'unknown';
	$outcome = in_array( $outcome, $outcomes, true ) ? $outcome : 'unsupported';
	$command = in_array( $command, $commands, true ) ? $command : 'none';
	$settings = ec_zalo_settings();
	$now = (int) current_time( 'timestamp', true );
	if ( 'verification' === $event ) {
		$settings['webhook_last_probe_at'] = $now;
	} else {
		$settings['webhook_last_at'] = $now;
		$settings['webhook_last_event'] = $event;
		$settings['webhook_last_outcome'] = $outcome;
		$settings['webhook_last_command'] = $command;
		$settings['webhook_last_candidate_count'] = count( ec_zalo_candidate_list( $settings ) );
	}
	update_option( 'ec_booking_zalo', $settings, false );
}

function ec_zalo_webhook_event_type( $event ) {
	return is_array( $event ) && is_array( $event['message'] ?? null ) ? 'message' : 'unknown';
}

function ec_zalo_webhook_authorized( $request ) {
	$secret = ec_zalo_webhook_secret();
	$provided = $request->get_header( 'x-bot-api-secret-token' );
	if ( ! ec_zalo_valid_webhook_secret( $secret ) || ! is_string( $provided ) || ! hash_equals( $secret, $provided ) ) {
		return new WP_Error( 'ec_zalo_unauthorized', 'Unauthorized', array( 'status' => 403 ) );
	}
	return true;
}

function ec_zalo_webhook_receive( $request ) {
	try {
		$payload = $request->get_json_params();
		// Some Zalo delivery requests omit the JSON content-type header.
		if ( ! is_array( $payload ) ) {
			$decoded = json_decode( $request->get_body(), true );
			$payload = is_array( $decoded ) ? $decoded : $payload;
		}
		// Zalo sends an authenticated empty POST while verifying a newly saved URL.
		if ( ! is_array( $payload ) || true !== ( $payload['ok'] ?? null ) || ! is_array( $payload['result'] ?? null ) ) {
			ec_zalo_record_webhook_activity( 'verification', 'never' );
			return rest_ensure_response( array( 'ok' => true ) );
		}
		$event_key = hash( 'sha256', wp_json_encode( $payload['result'] ) );
		$event_type = ec_zalo_webhook_event_type( $payload['result'] );
		if ( get_transient( 'ec_zalo_event_' . $event_key ) ) {
			ec_zalo_record_webhook_activity( $event_type, 'duplicate', ec_zalo_candidates( $payload['result'] ) ? 'nhanlich' : 'none' );
			return rest_ensure_response( array( 'ok' => true ) );
		}
		set_transient( 'ec_zalo_event_' . $event_key, 1, DAY_IN_SECONDS );
		$candidates = ec_zalo_candidates( $payload['result'] );
		ec_zalo_store_candidates( $candidates );
		ec_zalo_record_webhook_activity( $event_type, $candidates ? 'recipient_found' : ( 'message' === $event_type ? 'not_command' : 'unsupported' ), $candidates ? 'nhanlich' : 'none' );
		return rest_ensure_response( array( 'ok' => true ) );
	} catch ( Throwable $error ) {
		error_log( 'EC_ZALO_EXCEPTION [webhook]: ' . $error->getMessage() );
		return new WP_Error( 'ec_zalo_webhook_error', 'Unable to process event.', array( 'status' => 500 ) );
	}
}

function ec_zalo_register_webhook_route() {
	register_rest_route( 'ec-booking/v1', '/zalo-webhook', array(
		'methods' => WP_REST_Server::CREATABLE,
		'callback' => 'ec_zalo_webhook_receive',
		'permission_callback' => 'ec_zalo_webhook_authorized',
	) );
}
add_action( 'rest_api_init', 'ec_zalo_register_webhook_route' );

function ec_zalo_menu() { add_submenu_page( 'edit.php?post_type=ec_appointment', 'Cài đặt Zalo', 'Cài đặt Zalo', 'manage_options', 'ec-zalo-settings', 'ec_zalo_page' ); }
add_action( 'admin_menu', 'ec_zalo_menu' );

function ec_zalo_flash( $message, $error = false ) {
	set_transient( 'ec_zalo_notice_' . get_current_user_id(), array( 'message' => $message, 'error' => $error ), 60 );
	wp_safe_redirect( admin_url( 'edit.php?post_type=ec_appointment&page=ec-zalo-settings' ) );
	exit;
}

function ec_zalo_diagnostic_key() { return 'ec_zalo_diagnostic_' . get_current_user_id(); }

function ec_zalo_status_label( $group, $value ) {
	$labels = array(
		'bot' => array( 'missing' => 'Chưa lưu', 'ok' => 'Đã xác minh', 'rejected' => 'Zalo từ chối', 'uncertain' => 'Chưa xác định' ),
		'secret' => array( 'saved' => 'Đã lưu', 'missing' => 'Chưa lưu' ),
		'webhook' => array( 'matched' => 'Đúng URL website', 'missing' => 'Zalo chưa có URL', 'mismatch' => 'URL không khớp', 'unknown' => 'Chưa đọc được', 'uncertain' => 'Chưa xác định' ),
		'endpoint' => array( 'ok' => 'Zalo gọi website thành công', 'failed' => 'Zalo chưa gọi được website', 'tls' => 'Lỗi bảo mật HTTPS', 'unreachable' => 'Zalo chưa kết nối được website', 'unknown' => 'Chưa xác nhận', 'uncertain' => 'Chưa xác định' ),
		'event' => array( 'never' => 'Chưa có', 'verification' => 'Kiểm tra endpoint', 'message' => 'Tin nhắn', 'unknown' => 'Sự kiện khác' ),
		'outcome' => array( 'never' => 'Chưa có', 'recipient_found' => 'Đã lưu người nhận', 'not_command' => 'Không phải lệnh /nhanlich', 'unsupported' => 'Sự kiện chưa hỗ trợ', 'duplicate' => 'Sự kiện lặp, không ghi trùng' ),
		'command' => array( 'none' => 'Chưa nhận /nhanlich', 'nhanlich' => 'Đã nhận /nhanlich' ),
	);
	return $labels[ $group ][ $value ] ?? 'Chưa xác định';
}

function ec_zalo_activity( $settings = null ) {
	$settings = $settings ?? ec_zalo_settings();
	$event = in_array( $settings['webhook_last_event'] ?? '', array( 'never', 'verification', 'message', 'unknown' ), true ) ? $settings['webhook_last_event'] : 'never';
	$outcome = in_array( $settings['webhook_last_outcome'] ?? '', array( 'never', 'recipient_found', 'not_command', 'unsupported', 'duplicate' ), true ) ? $settings['webhook_last_outcome'] : 'never';
	$command = in_array( $settings['webhook_last_command'] ?? '', array( 'none', 'nhanlich' ), true ) ? $settings['webhook_last_command'] : 'none';
	return array( 'last_at' => absint( $settings['webhook_last_at'] ?? 0 ), 'last_probe_at' => absint( $settings['webhook_last_probe_at'] ?? 0 ), 'event' => $event, 'outcome' => $outcome, 'command' => $command, 'candidate_count' => count( ec_zalo_candidate_list( $settings ) ) );
}

function ec_zalo_timestamp_label( $timestamp ) {
	return $timestamp > 0 ? wp_date( 'd/m/Y H:i:s', $timestamp, wp_timezone() ) : 'Chưa có';
}

function ec_zalo_progress_label( $settings, $diagnostic, $activity ) {
	if ( ! ec_zalo_valid_token( ec_zalo_token( $settings ) ) ) { return 'Đang dừng ở bước lưu Bot Token.'; }
	if ( ! ec_zalo_valid_webhook_secret( ec_zalo_webhook_secret( $settings ) ) ) { return 'Đang dừng ở bước tạo Secret Webhook.'; }
	if ( ! is_array( $diagnostic ) ) { return 'Chưa chạy kiểm tra Webhook.'; }
	if ( 'ok' !== ( $diagnostic['bot'] ?? '' ) ) { return 'Bot chưa được Zalo xác minh.'; }
	if ( 'matched' !== ( $diagnostic['webhook'] ?? '' ) ) { return 'Webhook chưa được cấu hình đúng URL website.'; }
	if ( 'ok' !== ( $diagnostic['endpoint'] ?? '' ) ) { return 'Zalo chưa xác nhận gọi được endpoint của website.'; }
	if ( 'never' === $activity['event'] ) { return 'Kết nối đã sẵn sàng. Đang chờ Zalo gửi lệnh /nhanlich.'; }
	if ( 'nhanlich' !== $activity['command'] ) { return 'Website đã nhận sự kiện nhưng chưa phải lệnh /nhanlich.'; }
	if ( ! ec_zalo_valid_chat( $settings['chat_id'] ) ) { return 'Đã nhận /nhanlich. Chọn Chat ID, lưu cấu hình rồi gửi tin kiểm tra.'; }
	if ( ! $settings['enabled'] ) { return 'Đã có người nhận. Gửi tin kiểm tra, sau đó bật thông báo tự động.'; }
	return 'Sẵn sàng gửi thông báo lịch hẹn mới.';
}

function ec_zalo_admin_action() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Bạn không có quyền cấu hình Zalo.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'ec_zalo_settings', 'ec_zalo_nonce' );
	$settings = ec_zalo_settings();
	$mode = isset( $_POST['ec_zalo_mode'] ) && is_string( $_POST['ec_zalo_mode'] ) ? sanitize_key( $_POST['ec_zalo_mode'] ) : '';
	if ( 'diagnose_webhook' === $mode ) {
		$key = ec_zalo_diagnostic_key();
		$diagnostic = get_transient( $key );
		if ( ! is_array( $diagnostic ) || absint( $diagnostic['checked_at'] ?? 0 ) < time() - 60 ) {
			$diagnostic = ec_zalo_diagnose_webhook( $settings );
			set_transient( $key, $diagnostic, 10 * MINUTE_IN_SECONDS );
			ec_zalo_flash( 'Đã kiểm tra Bot, URL Webhook và endpoint từ Zalo. Xem bảng trạng thái bên dưới.', 'ok' !== $diagnostic['endpoint'] );
		}
		ec_zalo_flash( 'Đang hiển thị kết quả kiểm tra gần nhất để tránh vượt giới hạn kiểm tra của Zalo.', false );
	}
	if ( 'save' === $mode ) {
		foreach ( array( 'bot_token', 'chat_id', 'webhook_secret' ) as $key ) { if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) { ec_zalo_flash( 'Thông tin cấu hình không hợp lệ.', true ); } }
		$token = trim( wp_unslash( $_POST['bot_token'] ) );
		$chat = trim( wp_unslash( $_POST['chat_id'] ) );
		$webhook_secret = trim( wp_unslash( $_POST['webhook_secret'] ) );
		if ( '' !== $chat && ! ec_zalo_valid_chat( $chat ) ) { ec_zalo_flash( 'Chat ID không hợp lệ.', true ); }
		if ( '' !== $webhook_secret && ! ec_zalo_valid_webhook_secret( $webhook_secret ) ) { ec_zalo_flash( 'Secret Webhook phải có 8–256 ký tự chữ, số hoặc . _ ~ -.', true ); }
		if ( '' !== $token ) {
			$result = ec_zalo_api( 'getMe', array(), $token );
			if ( is_wp_error( $result ) ) { ec_zalo_flash( $result->get_error_message(), true ); }
			$cipher = ec_zalo_encrypt( $token );
			if ( is_wp_error( $cipher ) ) { ec_zalo_flash( $cipher->get_error_message(), true ); }
			if ( $token !== ec_zalo_token( $settings ) ) { $chat = ''; }
			$settings['cipher'] = $cipher;
			$settings['bot_name'] = is_string( $result['account_name'] ?? null ) ? sanitize_text_field( $result['account_name'] ) : 'Zalo Bot';
		}
		if ( '' !== $webhook_secret ) {
			$cipher = ec_zalo_encrypt( $webhook_secret );
			if ( is_wp_error( $cipher ) ) { ec_zalo_flash( $cipher->get_error_message(), true ); }
			$settings['webhook_cipher'] = $cipher;
		}
		$enabled = isset( $_POST['enabled'] ) && '1' === $_POST['enabled'];
		if ( $enabled && ( ! $chat || ! ec_zalo_token( $settings ) ) ) { $enabled = false; }
		$settings['enabled'] = $enabled;
		$settings['chat_id'] = $chat;
		$settings['version'] = hash( 'sha256', ec_zalo_token( $settings ) . '|' . $chat );
		update_option( 'ec_booking_zalo', $settings, false );
		if ( ec_zalo_settings() !== $settings ) { ec_zalo_flash( 'Chưa lưu được cấu hình. Vui lòng thử lại.', true ); }
		ec_zalo_flash( $enabled ? 'Đã bật thông báo cho các lịch đăng ký mới.' : 'Đã lưu cấu hình. Thông báo tự động đang tắt; chọn người nhận, gửi thử rồi bật khi sẵn sàng.' );
	}
	if ( 'generate_webhook_secret' === $mode ) {
		$secret = wp_generate_password( 32, false, false );
		$cipher = ec_zalo_encrypt( $secret );
		if ( is_wp_error( $cipher ) ) { ec_zalo_flash( $cipher->get_error_message(), true ); }
		$settings['webhook_cipher'] = $cipher;
		update_option( 'ec_booking_zalo', $settings, false );
		ec_zalo_flash( 'Đã tạo Secret Webhook an toàn. Bấm Kích hoạt Webhook để website gửi cấu hình cho Zalo.', false );
	}
	if ( 'activate_webhook' === $mode ) {
		$secret = ec_zalo_webhook_secret( $settings );
		if ( ! ec_zalo_valid_webhook_secret( $secret ) ) { ec_zalo_flash( 'Hãy tạo hoặc lưu Secret Webhook trước khi kích hoạt.', true ); }
		$result = ec_zalo_api( 'setWebhook', array( 'url' => ec_zalo_webhook_url(), 'secret_token' => $secret ) );
		if ( is_wp_error( $result ) ) { ec_zalo_flash( $result->get_error_message(), true ); }
		if ( is_array( $result['verification'] ?? null ) && empty( $result['verification']['ok'] ) ) { ec_zalo_flash( 'Zalo đã lưu Webhook nhưng chưa kiểm tra được endpoint. Hãy thử lại sau ít phút.', true ); }
		ec_zalo_flash( 'Webhook đã được Zalo xác nhận. Hãy nhắn /nhanlich cho bot rồi tải lại trang này để chọn người nhận.' );
	}
	if ( 'discover' === $mode ) {
		if ( ec_zalo_valid_webhook_secret( ec_zalo_webhook_secret( $settings ) ) ) { ec_zalo_flash( 'Webhook đang được dùng. Zalo không cho lấy getUpdates song song; hãy nhắn /nhanlich rồi xem trạng thái Webhook ở đầu trang.', true ); }
		$result = ec_zalo_api( 'getUpdates', array( 'timeout' => 2 ) );
		if ( is_wp_error( $result ) ) { ec_zalo_flash( $result->get_error_message(), true ); }
		$found = ec_zalo_candidates( $result );
		ec_zalo_store_candidates( $found );
		set_transient( 'ec_zalo_candidates_' . get_current_user_id(), $found, 600 );
		ec_zalo_flash( $found ? 'Đã tìm được người nhận. Chọn người nhận ở phần Chat ID rồi lưu cấu hình.' : 'Chưa thấy tin nhắn /nhanlich. Nếu đã bật Webhook, hãy nhắn lại lệnh rồi tải lại trang này.', ! $found );
	}
	if ( 'test' === $mode ) {
		if ( ! ec_zalo_valid_chat( $settings['chat_id'] ) ) { ec_zalo_flash( 'Vui lòng chọn và lưu Chat ID trước khi gửi thử.', true ); }
		$result = ec_zalo_api( 'sendMessage', array( 'chat_id' => $settings['chat_id'], 'text' => "Kết nối Zalo thành công.\nBệnh viện Mắt Hà Nội – Bắc Ninh\nĐây là tin nhắn kiểm tra thông báo đặt lịch." ) );
		ec_zalo_flash( is_wp_error( $result ) ? $result->get_error_message() : 'Zalo đã nhận tin nhắn kiểm tra. Hãy kiểm tra đúng tài khoản nhận trước khi bật tự động.', is_wp_error( $result ) );
	}
	ec_zalo_flash( 'Thao tác không hợp lệ.', true );
}
add_action( 'admin_post_ec_zalo_settings', 'ec_zalo_admin_action' );

function ec_zalo_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Bạn không có quyền truy cập.' ); }
	$s = ec_zalo_settings();
	$notice = get_transient( 'ec_zalo_notice_' . get_current_user_id() );
	$diagnostic = get_transient( ec_zalo_diagnostic_key() );
	$activity = ec_zalo_activity( $s );
	$candidates = array_merge( ec_zalo_candidate_list( $s ), is_array( get_transient( 'ec_zalo_candidates_' . get_current_user_id() ) ) ? get_transient( 'ec_zalo_candidates_' . get_current_user_id() ) : array() );
	?>
	<div class="wrap" style="max-width:960px"><h1>Cài đặt thông báo Zalo</h1>
	<p>Nhận thông báo khi website lưu thành công một yêu cầu đặt lịch mới. Tin nhắn gồm mã lịch, ngày giờ và liên kết xem chi tiết trong admin.</p>
	<?php if ( is_array( $notice ) ) : ?><div class="notice <?php echo $notice['error'] ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div><?php endif; ?>
	<div class="card" style="max-width:none;padding:20px 28px"><h2>Trạng thái kết nối</h2>
	<p><strong><?php echo esc_html( ec_zalo_progress_label( $s, $diagnostic, $activity ) ); ?></strong></p>
	<table class="widefat striped" style="max-width:760px"><tbody>
	<tr><th scope="row">Bot Token</th><td><?php echo esc_html( is_array( $diagnostic ) ? ec_zalo_status_label( 'bot', $diagnostic['bot'] ?? '' ) : ( ec_zalo_valid_token( ec_zalo_token( $s ) ) ? 'Đã lưu, chưa kiểm tra' : 'Chưa lưu' ) ); ?></td></tr>
	<tr><th scope="row">Secret Webhook</th><td><?php echo esc_html( ec_zalo_status_label( 'secret', ec_zalo_valid_webhook_secret( ec_zalo_webhook_secret( $s ) ) ? 'saved' : 'missing' ) ); ?></td></tr>
	<tr><th scope="row">URL Webhook</th><td><?php echo esc_html( is_array( $diagnostic ) ? ec_zalo_status_label( 'webhook', $diagnostic['webhook'] ?? '' ) : 'Chưa kiểm tra' ); ?></td></tr>
	<tr><th scope="row">Zalo gọi endpoint</th><td><?php echo esc_html( is_array( $diagnostic ) ? ec_zalo_status_label( 'endpoint', $diagnostic['endpoint'] ?? '' ) : 'Chưa kiểm tra' ); ?></td></tr>
	<tr><th scope="row">Sự kiện Webhook gần nhất</th><td><?php if ( 'never' === $activity['event'] ) : ?><?php echo esc_html( $activity['last_probe_at'] ? 'Zalo đã kiểm tra endpoint: ' . ec_zalo_timestamp_label( $activity['last_probe_at'] ) : 'Chưa nhận được sự kiện đã xác thực từ Zalo.' ); ?><?php else : ?><?php echo esc_html( ec_zalo_status_label( 'event', $activity['event'] ) . ' - ' . ec_zalo_status_label( 'outcome', $activity['outcome'] ) . ' - ' . ec_zalo_timestamp_label( $activity['last_at'] ) ); ?><?php endif; ?></td></tr>
	<tr><th scope="row">Lệnh /nhanlich</th><td><?php echo esc_html( ec_zalo_status_label( 'command', $activity['command'] ) ); ?></td></tr>
	<tr><th scope="row">Người nhận tìm được</th><td><?php echo esc_html( (string) $activity['candidate_count'] ); ?></td></tr>
	<tr><th scope="row">Chat ID đã chọn</th><td><?php echo esc_html( ec_zalo_valid_chat( $s['chat_id'] ) ? 'Đã chọn' : 'Chưa chọn' ); ?></td></tr>
	<tr><th scope="row">Thông báo tự động</th><td><?php echo esc_html( $s['enabled'] ? 'Đang bật' : 'Đang tắt' ); ?></td></tr>
	</tbody></table>
	<form style="margin-top:16px" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ec_zalo_settings"><input type="hidden" name="ec_zalo_mode" value="diagnose_webhook"><?php wp_nonce_field( 'ec_zalo_settings', 'ec_zalo_nonce' ); ?><button class="button button-primary">Kiểm tra Webhook ngay</button></form>
	<p class="description">Nút này không gửi tin nhắn, không cần Chat ID và chỉ hiển thị trạng thái an toàn. Kết quả được giới hạn một lần mỗi phút để tránh vượt hạn mức của Zalo.</p></div>
	<div class="card" style="max-width:none;padding:20px 28px"><h2>1. Tạo và kết nối bot</h2>
	<ol><li>Trong Zalo, tìm OA <strong>Zalo Bot Manager</strong>, chọn <strong>Tạo bot</strong>. Đặt tên bắt đầu bằng “Bot”, ví dụ “Bot Lịch khám HN–BN”.</li><li>Dán Bot Token Zalo gửi cho bạn vào ô bên dưới rồi lưu.</li><li>Tạo Secret Webhook, sau đó bấm <strong>Kích hoạt Webhook</strong>. Website sẽ nhận tin <code>/nhanlich</code> ổn định mà không cần long polling.</li><li>Dùng tài khoản nhận thông báo nhắn <code>/nhanlich</code> cho bot, tải lại trang, chọn Chat ID rồi gửi tin kiểm tra.</li></ol>
	<p><a href="https://docs.zaloplatforms.com/docs/BOT/create_bot" target="_blank" rel="noopener noreferrer">Hướng dẫn chính thức của Zalo</a></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ec_zalo_settings"><input type="hidden" name="ec_zalo_mode" value="save"><?php wp_nonce_field( 'ec_zalo_settings', 'ec_zalo_nonce' ); ?>
	<table class="form-table"><tr><th scope="row"><label for="ec-zalo-token">Bot Token</label></th><td><input id="ec-zalo-token" name="bot_token" type="password" class="regular-text" autocomplete="new-password" maxlength="256" value="" placeholder="<?php echo ec_zalo_token( $s ) ? 'Đã lưu — để trống để giữ token hiện tại' : 'Dán Bot Token'; ?>"><p class="description">Token được mã hóa khi lưu, không hiển thị lại. Khi đổi bot, cần chọn lại người nhận.</p><?php if ( $s['bot_name'] ) : ?><p>Bot: <strong><?php echo esc_html( $s['bot_name'] ); ?></strong></p><?php endif; ?></td></tr>
	<tr><th scope="row"><label for="ec-zalo-webhook-secret">Secret Webhook</label></th><td><input id="ec-zalo-webhook-secret" name="webhook_secret" type="password" class="regular-text" autocomplete="new-password" maxlength="256" value="" placeholder="<?php echo ec_zalo_webhook_secret( $s ) ? 'Đã lưu — để trống để giữ secret hiện tại' : 'Tạo secret bằng nút bên dưới'; ?>"><p class="description">Secret được mã hóa khi lưu. Bấm Tạo Secret Webhook rồi Kích hoạt Webhook để website tự gửi cấu hình an toàn cho Zalo.</p></td></tr>
	<tr><th scope="row"><label for="ec-zalo-chat">Chat ID người nhận</label></th><td><input id="ec-zalo-chat" name="chat_id" type="text" class="regular-text" list="ec-zalo-recipients" maxlength="128" value="<?php echo esc_attr( $s['chat_id'] ); ?>" autocomplete="off"><datalist id="ec-zalo-recipients"><?php if ( is_array( $candidates ) ) : foreach ( $candidates as $id => $name ) : ?><option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></option><?php endforeach; endif; ?></datalist><p class="description">Chat ID là mã cuộc trò chuyện của bot, không phải số điện thoại Zalo.</p><?php if ( is_array( $candidates ) && $candidates ) : ?><ul><?php foreach ( $candidates as $id => $name ) : ?><li><?php echo esc_html( $name ); ?>: <code><?php echo esc_html( $id ); ?></code></li><?php endforeach; ?></ul><?php endif; ?></td></tr>
	<tr><th scope="row">Thông báo tự động</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?>> Bật thông báo cho các lịch đăng ký mới</label><p class="description">Lịch đã có trước khi bật sẽ không được gửi hàng loạt. Lịch vẫn được lưu nếu Zalo tạm thời không gửi được.</p></td></tr></table>
	<?php submit_button( 'Lưu cấu hình Zalo' ); ?></form>
	<?php foreach ( array( 'generate_webhook_secret' => 'Tạo Secret Webhook', 'activate_webhook' => 'Kích hoạt Webhook' ) as $mode => $label ) : ?><form style="display:inline-block;margin-right:12px" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ec_zalo_settings"><input type="hidden" name="ec_zalo_mode" value="<?php echo esc_attr( $mode ); ?>"><?php wp_nonce_field( 'ec_zalo_settings', 'ec_zalo_nonce' ); ?><button class="button" <?php disabled( 'activate_webhook' === $mode && ( ! ec_zalo_token( $s ) || ! ec_zalo_webhook_secret( $s ) ) ); ?>><?php echo esc_html( $label ); ?></button></form><?php endforeach; ?>
	<p class="description">Webhook URL: <code><?php echo esc_html( ec_zalo_webhook_url() ); ?></code></p></div>
	<div class="card" style="max-width:none;padding:20px 28px"><h2>2. Kiểm tra người nhận</h2><p>Sau khi Webhook đã kích hoạt, nhắn <code>/nhanlich</code> cho bot rồi tải lại trang này. Tài khoản đó sẽ xuất hiện trong danh sách Chat ID. Xem dòng “Sự kiện Webhook gần nhất” ở đầu trang để biết lệnh đã tới website hay chưa.</p>
	<?php if ( ! ec_zalo_valid_webhook_secret( ec_zalo_webhook_secret( $s ) ) ) : ?><form style="display:inline-block;margin-right:12px" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ec_zalo_settings"><input type="hidden" name="ec_zalo_mode" value="discover"><?php wp_nonce_field( 'ec_zalo_settings', 'ec_zalo_nonce' ); ?><button class="button" <?php disabled( ! ec_zalo_token( $s ) ); ?>>Tìm người nhận khi chưa dùng Webhook</button></form><?php endif; ?>
	<form style="display:inline-block;margin-right:12px" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ec_zalo_settings"><input type="hidden" name="ec_zalo_mode" value="test"><?php wp_nonce_field( 'ec_zalo_settings', 'ec_zalo_nonce' ); ?><button class="button" <?php disabled( ! ec_zalo_token( $s ) ); ?>>Gửi tin kiểm tra đến Chat ID đã lưu</button></form>
	<p class="description">Thông báo được xử lý nền qua WordPress Cron, có thể chậm tùy lượt truy cập. Có thể xem kết quả gửi trong chi tiết từng lịch hẹn.</p></div></div>
	<?php
}

function ec_zalo_queue( $id ) {
	$s = ec_zalo_settings();
	$post = get_post( $id );
	if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status || ! $s['enabled'] || ! ec_zalo_token( $s ) || ! ec_zalo_valid_chat( $s['chat_id'] ) ) { return; }
	if ( in_array( get_post_meta( $id, '_ec_zalo_state', true ), array( 'pending', 'sending', 'sent' ), true ) ) { return; }
	update_post_meta( $id, '_ec_zalo_config', $s['version'] );
	update_post_meta( $id, '_ec_zalo_state', 'pending' );
	$scheduled = wp_next_scheduled( 'ec_zalo_deliver', array( (int) $id ) ) ?: wp_schedule_single_event( time() + 3, 'ec_zalo_deliver', array( (int) $id ), true );
	if ( is_wp_error( $scheduled ) || ! $scheduled ) { update_post_meta( $id, '_ec_zalo_state', 'failed' ); update_post_meta( $id, '_ec_zalo_error', 'Chưa đưa được thông báo vào hàng đợi.' ); }
}
add_action( 'ec_booking_created', 'ec_zalo_queue' );

function ec_zalo_message( $post ) {
	$data = ec_booking_read( $post );
	return "CÓ YÊU CẦU ĐẶT LỊCH MỚI\nBệnh viện Mắt Hà Nội – Bắc Ninh\nMã lịch: #" . $post->ID . "\nNgày khám: " . ec_booking_admin_date_label( $data['date'] ?? '' ) . "\nGiờ khám: " . ( $data['time'] ?? '' ) . "\nTrạng thái: Chờ xác nhận\nXem chi tiết: " . admin_url( 'post.php?post=' . (int) $post->ID . '&action=edit' );
}

function ec_zalo_deliver( $id ) {
	$lock = ec_booking_lock( 'zalo:' . (int) $id );
	if ( ! $lock ) { return; }
	try {
		$post = get_post( $id );
		if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status || 'pending' !== get_post_meta( $id, '_ec_zalo_state', true ) ) { return; }
		$s = ec_zalo_settings();
		if ( ! $s['enabled'] || $s['version'] !== get_post_meta( $id, '_ec_zalo_config', true ) ) { update_post_meta( $id, '_ec_zalo_state', 'skipped' ); return; }
		update_post_meta( $id, '_ec_zalo_started', time() );
		if ( ! update_post_meta( $id, '_ec_zalo_state', 'sending' ) ) { return; }
		$result = ec_zalo_api( 'sendMessage', array( 'chat_id' => $s['chat_id'], 'text' => ec_zalo_message( $post ) ) );
		if ( is_wp_error( $result ) ) {
			update_post_meta( $id, '_ec_zalo_state', 'uncertain' === $result->get_error_code() ? 'unknown' : 'failed' );
			update_post_meta( $id, '_ec_zalo_error', $result->get_error_message() );
		} else {
			update_post_meta( $id, '_ec_zalo_state', 'sent' );
			update_post_meta( $id, '_ec_zalo_sent_at', time() );
		}
	} finally { ec_booking_unlock( $lock ); }
}
add_action( 'ec_zalo_deliver', 'ec_zalo_deliver' );

function ec_zalo_delivery_box_register() { add_meta_box( 'ec-zalo-delivery', 'Thông báo Zalo', 'ec_zalo_delivery_box', 'ec_appointment', 'side' ); }
add_action( 'add_meta_boxes_ec_appointment', 'ec_zalo_delivery_box_register' );

function ec_zalo_delivery_box( $post ) {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$state = get_post_meta( $post->ID, '_ec_zalo_state', true );
	$labels = array( 'pending' => 'Đang chờ gửi', 'sending' => 'Đang gửi — kiểm tra Zalo trước khi gửi lại', 'sent' => 'Đã gửi tới Zalo', 'failed' => 'Chưa gửi được', 'unknown' => 'Chưa xác định kết quả — kiểm tra trên Zalo trước khi gửi lại', 'skipped' => 'Bỏ qua do đã tắt hoặc thay đổi cấu hình' );
	echo '<p>' . esc_html( $labels[ $state ] ?? 'Chưa có thông báo cho lịch này.' ) . '</p>';
	if ( in_array( $state, array( 'failed', 'unknown' ), true ) ) { echo '<p>' . esc_html( get_post_meta( $post->ID, '_ec_zalo_error', true ) ) . '</p>'; }
	if ( ! in_array( $state, array( 'pending', 'sent' ), true ) && ec_zalo_settings()['enabled'] && ( 'sending' !== $state || (int) get_post_meta( $post->ID, '_ec_zalo_started', true ) < time() - 120 ) ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=ec_zalo_retry&appointment=' . (int) $post->ID ), 'ec_zalo_retry_' . $post->ID );
		echo '<p><a class="button" href="' . esc_url( $url ) . '">Gửi thông báo Zalo</a></p>';
	}
	echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=ec_appointment&page=ec-zalo-settings' ) ) . '">Cài đặt Zalo</a>';
}

function ec_zalo_retry() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không có quyền gửi thông báo.', '', array( 'response' => 403 ) ); }
	$id = isset( $_GET['appointment'] ) && is_string( $_GET['appointment'] ) ? absint( $_GET['appointment'] ) : 0;
	check_admin_referer( 'ec_zalo_retry_' . $id );
	$post = get_post( $id );
	if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status ) { wp_die( 'Không tìm thấy lịch hẹn.' ); }
	$lock = ec_booking_lock( 'zalo:' . $id );
	if ( $lock ) {
		try {
			$state = get_post_meta( $id, '_ec_zalo_state', true );
			if ( ! in_array( $state, array( 'pending', 'sent' ), true ) && ( 'sending' !== $state || (int) get_post_meta( $id, '_ec_zalo_started', true ) < time() - 120 ) ) {
				update_post_meta( $id, '_ec_zalo_state', 'retry' );
				ec_zalo_queue( $id );
			}
		} finally { ec_booking_unlock( $lock ); }
	}
	wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
	exit;
}
add_action( 'admin_post_ec_zalo_retry', 'ec_zalo_retry' );
