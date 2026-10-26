<?php
/** Patient appointment messages through approved ZBS templates, separate from the staff Bot. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ec_zbs_now() { return (int) apply_filters( 'ec_zbs_now', time() ); }

function ec_zbs_settings() {
	$raw = get_option( 'ec_booking_zbs', array() );
	return wp_parse_args( is_array( $raw ) ? $raw : array(), array(
		'enabled' => false, 'template_confirm_id' => '', 'template_reminder_id' => '',
		'confirm_map' => array(), 'reminder_map' => array(),
		'app_id' => '', 'secret_key_cipher' => '', 'refresh_token_cipher' => '',
		'access_token_cipher' => '', 'access_token_expires_at' => 0, 'token_state' => '',
	) );
}

/** A ZBS template may use only these appointment values; variable names come from approved templates. */
function ec_zbs_template_values( $post, $data ) {
	$date = (string) ( $data['date'] ?? '' );
	$formatted_date = preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $date ) ? substr( $date, 8, 2 ) . '/' . substr( $date, 5, 2 ) . '/' . substr( $date, 0, 4 ) : '';
	return array(
		'name' => (string) ( $data['name'] ?? '' ),
		'date' => $formatted_date,
		'time' => (string) ( $data['time'] ?? '' ),
		'appointment_id' => (string) $post->ID,
		'hospital_name' => 'Bệnh viện Mắt Hà Nội - Bắc Ninh',
		'address' => function_exists( 'eyecare_dia_chi_day_du' ) ? eyecare_dia_chi_day_du() : '',
		'hotline' => function_exists( 'eyecare_hotline_hien' ) ? eyecare_hotline_hien() : '',
	);
}

function ec_zbs_template_payload( $kind, $post, $data, $settings ) {
	$map = $settings[ $kind . '_map' ] ?? null;
	if ( ! is_array( $map ) || ! $map || count( $map ) > 20 ) { return new WP_Error( 'config', 'Chưa cấu hình biến mẫu ZBS.' ); }
	$allowed = ec_zbs_template_values( $post, $data );
	$output = array();
	foreach ( $map as $variable => $token ) {
		if ( ! is_string( $variable ) || ! preg_match( '/^[A-Za-z][A-Za-z0-9_]{0,63}$/D', $variable ) || ! is_string( $token ) || ! array_key_exists( $token, $allowed ) || '' === $allowed[ $token ] ) {
			return new WP_Error( 'config', 'Biến mẫu ZBS không hợp lệ hoặc thiếu dữ liệu.' );
		}
		$output[ $variable ] = $allowed[ $token ];
	}
	return $output;
}

function ec_zbs_template_id( $kind, $settings ) {
	$id = $settings[ 'template_' . $kind . '_id' ] ?? '';
	return is_string( $id ) && preg_match( '/^[A-Za-z0-9]{1,64}$/D', $id ) ? $id : '';
}

function ec_zbs_phone( $phone ) {
	$phone = preg_replace( '/[\s().-]/', '', (string) $phone );
	if ( preg_match( '/^\+?84([35789][0-9]{8})$/D', $phone, $match ) ) { return '84' . $match[1]; }
	if ( preg_match( '/^0([35789][0-9]{8})$/D', $phone, $match ) ) { return '84' . $match[1]; }
	return '';
}

function ec_zbs_slot( $data ) {
	$date = $data['date'] ?? '';
	$time = $data['time'] ?? '';
	if ( ! is_string( $date ) || ! is_string( $time ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $date ) || ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time ) ) { return false; }
	$slot = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $time, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
	return $slot && $slot->format( 'Y-m-d H:i' ) === $date . ' ' . $time ? $slot->getTimestamp() : false;
}

function ec_zbs_active_appointment( $post ) {
	if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status ) { return false; }
	return in_array( get_post_meta( $post->ID, '_ec_booking_status', true ), array( 'confirmed', 'contacted' ), true );
}

function ec_zbs_consent( $data ) { return true === ( $data['patient_zalo_consent'] ?? false ); }
function ec_zbs_meta( $kind, $suffix ) { return '_ec_zbs_' . $kind . '_' . $suffix; }
function ec_zbs_hook( $kind ) { return 'ec_zbs_' . $kind . '_deliver'; }
function ec_zbs_safe_secret( $value ) { return is_string( $value ) && strlen( $value ) >= 10 && strlen( $value ) <= 4096 && ! preg_match( '/[\x00-\x1f\x7f]/', $value ); }

function ec_zbs_credentials_configured( $settings ) {
	if ( get_option( 'ec_booking_zbs_token_unsafe', false ) ) { return false; }
	$provided = apply_filters( 'ec_zbs_access_token', null );
	if ( is_array( $provided ) && ec_zbs_safe_secret( $provided['access_token'] ?? null ) && (int) ( $provided['expires_at'] ?? 0 ) > ec_zbs_now() + 60 ) { return true; }
	return preg_match( '/^[0-9]{1,32}$/D', (string) ( $settings['app_id'] ?? '' ) )
		&& ec_zbs_safe_secret( ec_zbs_secret( $settings, 'secret_key_cipher' ) )
		&& ec_zbs_safe_secret( ec_zbs_secret( $settings, 'refresh_token_cipher' ) )
		&& ! in_array( $settings['token_state'] ?? '', array( 'unknown', 'failed' ), true );
}

/** Mark new bookings once. Historical rows without this marker can never be replayed into the outbox. */
function ec_zbs_enqueue( $id ) {
	$post = get_post( $id );
	if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status ) { return; }
	$data = ec_booking_read( $post );
	if ( ! ec_zbs_consent( $data ) || get_post_meta( $id, '_ec_zbs_enrolled', true ) ) { return; }
	$settings = ec_zbs_settings();
	$ready = ! empty( $settings['enabled'] ) && ec_zbs_template_id( 'confirm', $settings ) && ec_zbs_template_id( 'reminder', $settings );
	$ready = $ready && ! is_wp_error( ec_zbs_template_payload( 'confirm', $post, $data, $settings ) ) && ! is_wp_error( ec_zbs_template_payload( 'reminder', $post, $data, $settings ) );
	$ready = $ready && ec_zbs_phone( $data['phone'] ?? '' ) && ec_zbs_credentials_configured( $settings );
	// Configuration must already be complete when the booking is made; enabling later never backfills old rows.
	if ( ! $ready ) {
		update_post_meta( $id, ec_zbs_meta( 'confirm', 'state' ), 'disabled' );
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'disabled' );
		update_post_meta( $id, '_ec_zbs_enrolled', 'disabled' );
		return;
	}
	update_post_meta( $id, '_ec_zbs_enrolled', '1' );
	update_post_meta( $id, ec_zbs_meta( 'confirm', 'state' ), 'pending' );
	$queued = wp_schedule_single_event( ec_zbs_now() + 2, ec_zbs_hook( 'confirm' ), array( (int) $id ), true );
	if ( ! $queued || is_wp_error( $queued ) ) { update_post_meta( $id, ec_zbs_meta( 'confirm', 'state' ), 'failed' ); }
	$slot = ec_zbs_slot( $data );
	$due = $slot ? $slot - HOUR_IN_SECONDS : 0;
	if ( ! $due || $due <= ec_zbs_now() ) {
		update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'late' );
		return;
	}
	update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'pending' );
	update_post_meta( $id, ec_zbs_meta( 'reminder', 'due' ), $due );
	$queued = wp_schedule_single_event( $due, ec_zbs_hook( 'reminder' ), array( (int) $id ), true );
	if ( ! $queued || is_wp_error( $queued ) ) { update_post_meta( $id, ec_zbs_meta( 'reminder', 'state' ), 'failed' ); }
}
add_action( 'ec_booking_created', 'ec_zbs_enqueue', 20 );

/** Re-enqueue only previously enrolled pending messages, for idempotent browser retries. */
function ec_zbs_recover( $id ) {
	if ( '1' !== get_post_meta( $id, '_ec_zbs_enrolled', true ) ) { return; }
	$post = get_post( $id );
	if ( ! ec_zbs_active_appointment( $post ) || ! ec_zbs_consent( ec_booking_read( $post ) ) ) { return; }
	foreach ( array( 'confirm', 'reminder' ) as $kind ) {
		if ( 'reminder' === $kind && 'late' === get_post_meta( $id, ec_zbs_meta( $kind, 'state' ), true ) ) {
			// A new future slot may make a previously impossible reminder possible.
			$new_due = ( ec_zbs_slot( ec_booking_read( $post ) ) ?: 0 ) - HOUR_IN_SECONDS;
			if ( $new_due > ec_zbs_now() && ! get_post_meta( $id, ec_zbs_meta( $kind, 'msg_id' ), true ) ) {
				update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'pending' );
			}
		}
		if ( 'pending' !== get_post_meta( $id, ec_zbs_meta( $kind, 'state' ), true ) ) { continue; }
		$args = array( (int) $id );
		$scheduled = wp_next_scheduled( ec_zbs_hook( $kind ), $args );
		$when = 'confirm' === $kind ? ec_zbs_now() + 2 : ( ec_zbs_slot( ec_booking_read( $post ) ) ?: 0 ) - HOUR_IN_SECONDS;
		if ( 'reminder' === $kind ) {
			$when = max( 0, $when );
			update_post_meta( $id, ec_zbs_meta( 'reminder', 'due' ), $when );
			// Date/time may have changed in WordPress; the old one-shot event is no longer valid.
			if ( $scheduled && (int) $scheduled !== $when ) {
				wp_unschedule_event( $scheduled, ec_zbs_hook( $kind ), $args );
				$scheduled = false;
			}
		}
		if ( 'reminder' === $kind && $when + 300 < ec_zbs_now() ) {
			update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'late' );
			continue;
		}
		if ( $scheduled ) { continue; }
		$queued = wp_schedule_single_event( max( ec_zbs_now() + 1, $when ), ec_zbs_hook( $kind ), $args, true );
		if ( ! $queued || is_wp_error( $queued ) ) { update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'failed' ); }
	}
}

/** Called after the administrator changes booking status. Never reactivates a cancelled outbox. */
function ec_zbs_status_changed( $id ) {
	$post = get_post( $id );
	if ( ec_zbs_active_appointment( $post ) ) { ec_zbs_recover( $id ); return; }
	foreach ( array( 'confirm', 'reminder' ) as $kind ) {
		$args = array( (int) $id );
		$timestamp = wp_next_scheduled( ec_zbs_hook( $kind ), $args );
		if ( $timestamp ) { wp_unschedule_event( $timestamp, ec_zbs_hook( $kind ), $args ); }
		if ( 'pending' === get_post_meta( $id, ec_zbs_meta( $kind, 'state' ), true ) ) { update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'skipped' ); }
	}
}

/** Keep the reminder aligned if another authorized WordPress workflow edits the slot. */
function ec_zbs_post_updated( $id, $after, $before ) {
	if ( 'ec_appointment' === $after->post_type && $after->post_content !== $before->post_content ) {
		ec_zbs_recover( (int) $id );
	}
}
add_action( 'post_updated', 'ec_zbs_post_updated', 10, 3 );

function ec_zbs_secret( $settings, $key ) {
	return function_exists( 'ec_zalo_token' ) ? ec_zalo_token( array( 'cipher' => is_string( $settings[ $key ] ?? null ) ? $settings[ $key ] : '' ) ) : '';
}

/** Token provider filter may return a fresh {access_token, expires_at} pair from an external OAuth store. */
function ec_zbs_access_token() {
	if ( get_option( 'ec_booking_zbs_token_unsafe', false ) ) { return new WP_Error( 'token', 'Cặp token ZBS chưa được lưu an toàn; cần kết nối lại.' ); }
	$provided = apply_filters( 'ec_zbs_access_token', null );
	if ( is_array( $provided ) && is_string( $provided['access_token'] ?? null ) && (int) ( $provided['expires_at'] ?? 0 ) > ec_zbs_now() + 60 ) { return $provided['access_token']; }
	$settings = ec_zbs_settings();
	if ( 'unknown' === $settings['token_state'] || 'failed' === $settings['token_state'] ) { return new WP_Error( 'token', 'Kết nối ZBS cần được kiểm tra lại.' ); }
	$token = ec_zbs_secret( $settings, 'access_token_cipher' );
	if ( $token && (int) $settings['access_token_expires_at'] > ec_zbs_now() + 300 ) { return $token; }
	return ec_zbs_refresh_access_token();
}

/** After an ambiguous OAuth call, never reuse a possibly consumed refresh token. */
function ec_zbs_token_unknown( $settings, $message ) {
	$settings['token_state'] = 'unknown';
	update_option( 'ec_booking_zbs', $settings, false );
	update_option( 'ec_booking_zbs_token_unsafe', true, false );
	return new WP_Error( 'unknown', $message );
}

/** Refresh token is single-use: one lock, one HTTP call, one option update for the new pair. */
function ec_zbs_refresh_access_token() {
	$lock = ec_booking_lock( 'zbs-token' );
	if ( ! $lock ) { return new WP_Error( 'busy', 'Đang làm mới kết nối ZBS.' ); }
	try {
		if ( get_option( 'ec_booking_zbs_token_unsafe', false ) ) { return new WP_Error( 'token', 'Cặp token ZBS chưa được lưu an toàn; cần kết nối lại.' ); }
		$settings = ec_zbs_settings();
		if ( 'unknown' === $settings['token_state'] || 'failed' === $settings['token_state'] ) { return new WP_Error( 'token', 'Kết nối ZBS cần được kiểm tra lại.' ); }
		$token = ec_zbs_secret( $settings, 'access_token_cipher' );
		if ( $token && (int) $settings['access_token_expires_at'] > ec_zbs_now() + 300 ) { return $token; }
		$app_id = (string) ( $settings['app_id'] ?? '' );
		$secret = ec_zbs_secret( $settings, 'secret_key_cipher' );
		$refresh = ec_zbs_secret( $settings, 'refresh_token_cipher' );
		if ( ! preg_match( '/^[0-9]{1,32}$/D', $app_id ) || ! ec_zbs_safe_secret( $secret ) || ! ec_zbs_safe_secret( $refresh ) ) { return new WP_Error( 'config', 'Chưa cấu hình đủ xác thực ứng dụng ZBS.' ); }
		$response = wp_remote_post( 'https://oauth.zaloapp.com/v4/oa/access_token', array(
			'timeout' => 12, 'redirection' => 0, 'sslverify' => true, 'limit_response_size' => 8192,
			'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded', 'secret_key' => $secret ),
			'body' => http_build_query( array( 'refresh_token' => $refresh, 'app_id' => $app_id, 'grant_type' => 'refresh_token' ), '', '&', PHP_QUERY_RFC3986 ),
		) );
		if ( is_wp_error( $response ) ) {
			return ec_zbs_token_unknown( $settings, 'Chưa xác định được kết quả làm mới token ZBS; cần kết nối lại.' );
		}
		$http = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $http >= 500 || ( $http >= 200 && $http < 300 && ! is_array( $body ) ) ) {
			return ec_zbs_token_unknown( $settings, 'Phản hồi làm mới token ZBS chưa rõ; cần kết nối lại.' );
		}
		if ( $http < 200 || $http >= 300 || ! is_array( $body ) || ( isset( $body['error'] ) && 0 !== (int) $body['error'] ) ) {
			$settings['token_state'] = 'failed'; update_option( 'ec_booking_zbs', $settings, false );
			return new WP_Error( 'token', 'ZBS chưa chấp nhận làm mới token; cần kiểm tra kết nối.' );
		}
		$new_access = $body['access_token'] ?? '';
		$new_refresh = $body['refresh_token'] ?? '';
		$ttl = (int) ( $body['expires_in'] ?? 0 );
		if ( ! ec_zbs_safe_secret( $new_access ) || ! ec_zbs_safe_secret( $new_refresh ) || $ttl < 3600 ) {
			return ec_zbs_token_unknown( $settings, 'ZBS trả về cặp token không xác nhận được; cần kết nối lại.' );
		}
		$access_cipher = ec_zalo_encrypt( $new_access );
		$refresh_cipher = ec_zalo_encrypt( $new_refresh );
		if ( is_wp_error( $access_cipher ) || is_wp_error( $refresh_cipher ) ) {
			return ec_zbs_token_unknown( $settings, 'Không lưu được cặp token ZBS mới; cần kết nối lại.' );
		}
		$settings['access_token_cipher'] = $access_cipher;
		$settings['refresh_token_cipher'] = $refresh_cipher;
		$settings['access_token_expires_at'] = ec_zbs_now() + $ttl;
		$settings['token_state'] = 'ok';
		if ( ! update_option( 'ec_booking_zbs', $settings, false ) || ec_zbs_secret( ec_zbs_settings(), 'refresh_token_cipher' ) !== $new_refresh ) {
			update_option( 'ec_booking_zbs_token_unsafe', true, false );
			return new WP_Error( 'unknown', 'Chưa xác nhận lưu cặp token ZBS mới; cần kiểm tra kết nối.' );
		}
		return $new_access;
	} finally { ec_booking_unlock( $lock ); }
}

/** Fixed endpoint. A transport error is ambiguous and must not be retried automatically. */
function ec_zbs_send_template( $body ) {
	$filtered = apply_filters( 'ec_zbs_send_template', null, $body );
	if ( null !== $filtered ) { return $filtered; }
	$token = ec_zbs_access_token();
	if ( is_wp_error( $token ) ) { return $token; }
	if ( ! ec_zbs_safe_secret( $token ) ) { return new WP_Error( 'config', 'Access Token ZBS không hợp lệ.' ); }
	$response = wp_remote_post( 'https://business.openapi.zalo.me/message/template', array(
		'timeout' => 12, 'redirection' => 0, 'sslverify' => true, 'limit_response_size' => 8192,
		'headers' => array( 'Content-Type' => 'application/json', 'access_token' => $token ),
		'body' => wp_json_encode( $body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
	) );
	if ( is_wp_error( $response ) ) { return new WP_Error( 'unknown', 'Chưa xác định ZBS đã nhận tin hay chưa; cần đối soát trước khi gửi lại.' ); }
	$http = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $http >= 200 && $http < 300 && is_array( $data ) && 0 === (int) ( $data['error'] ?? -1 ) && is_scalar( $data['data']['msg_id'] ?? null ) && '' !== (string) $data['data']['msg_id'] ) {
		return array( 'msg_id' => (string) $data['data']['msg_id'] );
	}
	if ( $http >= 500 || ( $http >= 200 && $http < 300 && ! is_array( $data ) ) || ( is_array( $data ) && 0 === (int) ( $data['error'] ?? -1 ) ) ) {
		return new WP_Error( 'unknown', 'ZBS chưa xác nhận mã tin; cần đối soát trước khi gửi lại.' );
	}
	return new WP_Error( 'rejected', 'ZBS từ chối tin theo mẫu; kiểm tra cấu hình và số điện thoại.' );
}

function ec_zbs_deliver( $id, $kind ) {
	if ( ! in_array( $kind, array( 'confirm', 'reminder' ), true ) ) { return; }
	$lock = ec_booking_lock( 'zbs:' . $kind . ':' . (int) $id );
	if ( ! $lock ) { return; }
	try {
		if ( 'pending' !== get_post_meta( $id, ec_zbs_meta( $kind, 'state' ), true ) || '1' !== get_post_meta( $id, '_ec_zbs_enrolled', true ) ) { return; }
		$post = get_post( $id );
		$data = $post ? ec_booking_read( $post ) : array();
		if ( ! ec_zbs_active_appointment( $post ) || ! ec_zbs_consent( $data ) ) { update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'skipped' ); return; }
		$slot = ec_zbs_slot( $data );
		$now = ec_zbs_now();
		if ( ! $slot || $slot <= $now ) { update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'late' ); return; }
		if ( 'reminder' === $kind ) {
			$due = $slot - HOUR_IN_SECONDS;
			if ( $now < $due ) { ec_zbs_recover( $id ); return; }
			if ( $now > $due + 300 ) { update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'late' ); return; }
		}
		$settings = ec_zbs_settings();
		$template_id = ec_zbs_template_id( $kind, $settings );
		$variables = ec_zbs_template_payload( $kind, $post, $data, $settings );
		$phone = ec_zbs_phone( $data['phone'] ?? '' );
		if ( empty( $settings['enabled'] ) || ! $template_id || is_wp_error( $variables ) || ! $phone ) { update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'disabled' ); return; }
		update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'sending' );
		update_post_meta( $id, ec_zbs_meta( $kind, 'started_at' ), $now );
		$tracking = 'ec' . (int) $id . ( 'confirm' === $kind ? 'c' : 'r' );
		$result = ec_zbs_send_template( array( 'phone' => $phone, 'template_id' => $template_id, 'template_data' => $variables, 'tracking_id' => $tracking ) );
		if ( is_wp_error( $result ) ) {
			if ( 'busy' === $result->get_error_code() ) {
				// No message request was made; retry safely after the other OAuth refresh finishes.
				update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'pending' );
				$retry = wp_schedule_single_event( ec_zbs_now() + 20, ec_zbs_hook( $kind ), array( (int) $id ), true );
				if ( ! $retry || is_wp_error( $retry ) ) { update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'failed' ); }
				return;
			}
			update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'unknown' === $result->get_error_code() ? 'unknown' : 'failed' );
			update_post_meta( $id, ec_zbs_meta( $kind, 'error' ), $result->get_error_message() );
			return;
		}
		if ( ! is_array( $result ) || ! is_scalar( $result['msg_id'] ?? null ) || '' === (string) $result['msg_id'] ) {
			update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'unknown' );
			return;
		}
		update_post_meta( $id, ec_zbs_meta( $kind, 'msg_id' ), (string) $result['msg_id'] );
		update_post_meta( $id, ec_zbs_meta( $kind, 'sent_at' ), ec_zbs_now() );
		update_post_meta( $id, ec_zbs_meta( $kind, 'state' ), 'accepted' );
		update_post_meta( $id, ec_zbs_meta( $kind, 'error' ), '' );
	} finally { ec_booking_unlock( $lock ); }
}
add_action( ec_zbs_hook( 'confirm' ), static function ( $id ) { ec_zbs_deliver( $id, 'confirm' ); } );
add_action( ec_zbs_hook( 'reminder' ), static function ( $id ) { ec_zbs_deliver( $id, 'reminder' ); } );
