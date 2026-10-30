<?php
/**
 * API đặt lịch cho hệ thống bên ngoài (Zalo bot, phần mềm khác) và form nhúng trên website đối tác.
 *
 * - Máy chủ đối tác ký từng yêu cầu bằng HMAC-SHA256 với khóa bí mật riêng (không gửi khóa qua mạng).
 * - Form nhúng dùng khóa công khai; chỉ các domain đã khai báo được nhúng (CSP frame-ancestors).
 * - Mọi lịch đi qua cùng ec_booking_validate()/ec_booking_store() với form trên website: cùng kiểm tra,
 *   chống trùng, giới hạn gửi và thông báo Sheet/Zalo/email.
 *
 * Tài liệu cho đối tác: docs/API-DAT-LICH.md.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const EC_BOOKING_API_SIGNATURE_WINDOW = 300;

/** Danh sách đối tác. Khóa bí mật lưu mã hóa AES-256-GCM, không bao giờ trả về trình duyệt sau lần tạo. */
function ec_booking_api_clients() {
	$raw = get_option( 'ec_booking_api_clients', array() );
	$clients = array();
	foreach ( is_array( $raw ) ? $raw : array() as $id => $client ) {
		if ( ! is_string( $id ) || ! preg_match( '/^[a-z0-9]{8,16}$/D', $id ) || ! is_array( $client ) ) {
			continue;
		}
		$clients[ $id ] = wp_parse_args( $client, array(
			'name'        => $id,
			'channel'     => 'Website',
			'public_key'  => '',
			'secret'      => '',
			'origins'     => array(),
			'limit'       => 120,
			'active'      => true,
			'allow_api'   => true,
			'allow_embed' => true,
			'created_at'  => 0,
		) );
		$clients[ $id ]['id'] = $id;
	}
	return $clients;
}

/** Đối tác đang hoạt động theo khóa công khai của form nhúng. */
function ec_booking_api_client( $public_key ) {
	if ( ! is_string( $public_key ) || ! preg_match( '/^pk_[a-f0-9]{32}$/D', $public_key ) ) {
		return null;
	}
	foreach ( ec_booking_api_clients() as $client ) {
		if ( $client['active'] && $client['allow_embed'] && hash_equals( (string) $client['public_key'], $public_key ) ) {
			return $client;
		}
	}
	return null;
}

function ec_booking_api_secret( $client ) {
	if ( empty( $client['secret'] ) || ! function_exists( 'ec_zalo_token' ) ) {
		return '';
	}
	return ec_zalo_token( array( 'cipher' => $client['secret'] ) );
}

/** Domain gốc hợp lệ cho frame-ancestors: chỉ https (http cho localhost khi thử nghiệm). */
function ec_booking_api_clean_origins( $text ) {
	$origins = array();
	foreach ( preg_split( '/[\s,]+/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = wp_parse_url( $line );
		if ( empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
			continue;
		}
		$scheme = strtolower( $parts['scheme'] );
		$host   = strtolower( $parts['host'] );
		$local  = in_array( $host, array( 'localhost', '127.0.0.1' ), true );
		if ( ( 'https' !== $scheme && ! ( 'http' === $scheme && $local ) ) || ! preg_match( '/^(\*\.)?[a-z0-9.-]+$/D', $host ) ) {
			continue;
		}
		$origins[] = $scheme . '://' . $host . ( isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '' );
	}
	return array_values( array_unique( array_slice( $origins, 0, 20 ) ) );
}

/** CSP cho trang nhúng: chỉ domain đã khai báo; không có domain thì không cho nhúng ở đâu. */
function ec_booking_api_frame_policy( $client ) {
	$origins = $client && ! empty( $client['origins'] ) ? implode( ' ', $client['origins'] ) : "'none'";
	return "frame-ancestors " . $origins . "; base-uri 'self'; form-action 'self'; object-src 'none'";
}

/** Bộ khóa mới: mã đối tác, khóa công khai cho form nhúng, khóa bí mật cho máy chủ. */
function ec_booking_api_new_credentials() {
	return array(
		'id'         => bin2hex( random_bytes( 6 ) ),
		'public_key' => 'pk_' . bin2hex( random_bytes( 16 ) ),
		'secret'     => 'sk_' . bin2hex( random_bytes( 32 ) ),
	);
}

/** Chuỗi được ký: thời điểm, phương thức, route và thân yêu cầu nguyên văn. */
function ec_booking_api_canonical( $timestamp, $method, $route, $body ) {
	return $timestamp . "\n" . strtoupper( $method ) . "\n" . $route . "\n" . $body;
}

function ec_booking_api_sign( $secret, $timestamp, $method, $route, $body ) {
	return hash_hmac( 'sha256', ec_booking_api_canonical( $timestamp, $method, $route, $body ), $secret );
}

/** Giới hạn số lần ký sai theo IP để chặn dò khóa. */
function ec_booking_api_auth_throttled( $record_failure = false ) {
	$key   = 'ec_booking_api_fail_' . hash_hmac( 'sha256', ec_booking_client_ip(), wp_salt( 'nonce' ) );
	$count = (int) get_transient( $key );
	if ( $record_failure ) {
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}
	return $count >= 30;
}

/**
 * Xác thực yêu cầu máy chủ. Trả về đối tác hoặc WP_Error.
 * Header: X-EC-Key (mã đối tác), X-EC-Timestamp (giây Unix), X-EC-Signature (hex HMAC-SHA256).
 */
function ec_booking_api_authenticate( $request, $now = null ) {
	$now = null === $now ? time() : (int) $now;
	if ( ec_booking_api_auth_throttled() ) {
		return new WP_Error( 'auth_throttled', 'Quá nhiều lần xác thực sai. Vui lòng thử lại sau.', array( 'status' => 429 ) );
	}
	$id        = (string) $request->get_header( 'x_ec_key' );
	$timestamp = (string) $request->get_header( 'x_ec_timestamp' );
	$signature = strtolower( (string) $request->get_header( 'x_ec_signature' ) );
	$clients   = ec_booking_api_clients();
	$client    = $clients[ $id ] ?? null;
	$fail      = new WP_Error( 'auth', 'Chữ ký hoặc khóa API không hợp lệ.', array( 'status' => 401 ) );
	if ( ! $client || ! $client['active'] || ! $client['allow_api'] || ! preg_match( '/^[0-9]{9,11}$/D', $timestamp ) || ! preg_match( '/^[a-f0-9]{64}$/D', $signature ) ) {
		ec_booking_api_auth_throttled( true );
		return $fail;
	}
	if ( abs( $now - (int) $timestamp ) > EC_BOOKING_API_SIGNATURE_WINDOW ) {
		return new WP_Error( 'auth_clock', 'Đồng hồ máy chủ gửi lệch quá 5 phút. Vui lòng đồng bộ giờ (NTP).', array( 'status' => 401 ) );
	}
	$secret = ec_booking_api_secret( $client );
	$expect = '' !== $secret ? ec_booking_api_sign( $secret, $timestamp, $request->get_method(), $request->get_route(), (string) $request->get_body() ) : '';
	if ( '' === $expect || ! hash_equals( $expect, $signature ) ) {
		ec_booking_api_auth_throttled( true );
		return $fail;
	}
	return $client;
}

/** request_id riêng theo đối tác để hai đối tác dùng trùng mã không đụng lịch của nhau. */
function ec_booking_api_scoped_request_id( $client_id, $request_id ) {
	$hash = hash_hmac( 'sha256', 'api:' . $client_id . ':' . strtolower( $request_id ), wp_salt( 'nonce' ) );
	return substr( $hash, 0, 8 ) . '-' . substr( $hash, 8, 4 ) . '-4' . substr( $hash, 13, 3 ) . '-a' . substr( $hash, 17, 3 ) . '-' . substr( $hash, 20, 12 );
}

/** Lịch hẹn trả về cho đối tác: không kèm họ tên hay số điện thoại. */
function ec_booking_api_appointment( $post ) {
	$data   = ec_booking_read( $post );
	$status = get_post_meta( $post->ID, '_ec_booking_status', true );
	return array(
		'appointment_id' => (int) $post->ID,
		'status'         => array_key_exists( $status, ec_booking_statuses() ) ? $status : ( $data['initial_status'] ?? 'pending' ),
		'status_label'   => ec_booking_status_label( $post ),
		'date'           => (string) ( $data['date'] ?? '' ),
		'time'           => (string) ( $data['time'] ?? '' ),
	);
}

/** Chuyển WP_Error sang phản hồi JSON thống nhất cho đối tác. */
function ec_booking_api_error( $error ) {
	$data   = $error->get_error_data();
	$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;
	$body   = array( 'ok' => false, 'code' => $error->get_error_code(), 'message' => $error->get_error_message() );
	// Lịch trùng chỉ trả về khi cùng họ tên, giống form website, để không lộ giờ hẹn của người khác.
	if ( is_array( $data ) && ! empty( $data['appointment_id'] ) && ( 'duplicate' !== $error->get_error_code() || ! empty( $data['same_person'] ) ) ) {
		$existing = get_post( (int) $data['appointment_id'] );
		if ( $existing ) {
			$body['appointment'] = ec_booking_api_appointment( $existing );
		}
	}
	return new WP_REST_Response( $body, $status );
}

/** GET /slots: khung giờ còn nhận lịch. Công khai, không chứa dữ liệu cá nhân. */
function ec_booking_api_slots() {
	$schedule = ec_booking_schedule();
	if ( is_wp_error( $schedule ) ) {
		return ec_booking_api_error( $schedule );
	}
	$now  = ec_booking_now();
	$days = array();
	$day  = DateTimeImmutable::createFromFormat( '!Y-m-d', $schedule['today'], new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
	for ( $i = 0; $i < 14; $i++, $day = $day->modify( '+1 day' ) ) {
		$free = array();
		foreach ( $schedule['slots'] as $slot ) {
			$moment = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $day->format( 'Y-m-d' ) . ' ' . $slot, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
			if ( $moment > $now ) {
				$free[] = $slot;
			}
		}
		if ( $free ) {
			$days[] = array( 'date' => $day->format( 'Y-m-d' ), 'slots' => $free );
		}
	}
	return new WP_REST_Response( array( 'ok' => true, 'timezone' => 'Asia/Ho_Chi_Minh', 'open' => $schedule['open'], 'close' => $schedule['close'], 'max_date' => $schedule['maxDate'], 'days' => $days ), 200 );
}

/**
 * POST /appointments: đặt lịch từ máy chủ đối tác.
 * Thân JSON: name, phone, date, time, request_id (UUID), consent=true, patient_zalo_consent (tùy chọn).
 */
function ec_booking_api_create( $request ) {
	$client = ec_booking_api_authenticate( $request );
	if ( is_wp_error( $client ) ) {
		return ec_booking_api_error( $client );
	}
	$json = json_decode( (string) $request->get_body(), true );
	if ( ! is_array( $json ) ) {
		return ec_booking_api_error( new WP_Error( 'input', 'Thân yêu cầu phải là JSON hợp lệ.', array( 'status' => 400 ) ) );
	}
	$text  = function ( $key ) use ( $json ) {
		return isset( $json[ $key ] ) && is_scalar( $json[ $key ] ) ? (string) $json[ $key ] : '';
	};
	$flag  = function ( $key ) use ( $json ) {
		return isset( $json[ $key ] ) && ( true === $json[ $key ] || '1' === $json[ $key ] || 1 === $json[ $key ] ) ? '1' : '0';
	};
	$request_id = $text( 'request_id' );
	if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,64}$/D', $request_id ) ) {
		return ec_booking_api_error( new WP_Error( 'request_id', 'request_id bắt buộc, 8–64 ký tự (chữ, số, . _ : -). Gửi lại cùng request_id khi thử lại để không tạo lịch trùng.', array( 'status' => 400 ) ) );
	}
	$input = array(
		'name'                 => $text( 'name' ),
		'phone'                => $text( 'phone' ),
		'date'                 => $text( 'date' ),
		'time'                 => $text( 'time' ),
		'consent'              => $flag( 'consent' ),
		'patient_zalo_consent' => $flag( 'patient_zalo_consent' ),
		'public_share_consent' => '0',
		'website'              => '',
		'request_id'           => ec_booking_api_scoped_request_id( $client['id'], $request_id ),
	);
	$booking = ec_booking_validate( $input, null, null, false );
	if ( is_wp_error( $booking ) ) {
		return ec_booking_api_error( $booking );
	}
	$result = ec_booking_replay( $booking );
	if ( false === $result ) {
		$booking = ec_booking_validate( $input );
		if ( is_wp_error( $booking ) ) {
			return ec_booking_api_error( $booking );
		}
		$context = array( 'client' => $client['id'], 'client_limit' => (int) $client['limit'], 'source' => 'api:' . $client['id'], 'channel' => $client['channel'] );
		$result  = ec_booking_store( $booking, $context );
	}
	if ( is_wp_error( $result ) ) {
		return ec_booking_api_error( $result );
	}
	return new WP_REST_Response( array( 'ok' => true, 'appointment' => ec_booking_api_appointment( get_post( $result ) ) ), 201 );
}

/** GET /appointments/{id}: trạng thái lịch do chính đối tác đó tạo. */
function ec_booking_api_show( $request ) {
	$client = ec_booking_api_authenticate( $request );
	if ( is_wp_error( $client ) ) {
		return ec_booking_api_error( $client );
	}
	$post = get_post( (int) $request['id'] );
	if ( ! $post || 'ec_appointment' !== $post->post_type || 'api:' . $client['id'] !== get_post_meta( $post->ID, '_ec_booking_source', true ) ) {
		return ec_booking_api_error( new WP_Error( 'not_found', 'Không tìm thấy lịch hẹn do đối tác này tạo.', array( 'status' => 404 ) ) );
	}
	return new WP_REST_Response( array( 'ok' => true, 'appointment' => ec_booking_api_appointment( $post ) ), 200 );
}

function ec_booking_api_routes() {
	register_rest_route( 'ec-booking/v1', '/slots', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'ec_booking_api_slots',
		'permission_callback' => '__return_true',
	) );
	// Xác thực HMAC nằm trong callback để lỗi trả đúng định dạng JSON của API.
	register_rest_route( 'ec-booking/v1', '/appointments', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'ec_booking_api_create',
		'permission_callback' => '__return_true',
	) );
	register_rest_route( 'ec-booking/v1', '/appointments/(?P<id>[0-9]{1,10})', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'ec_booking_api_show',
		'permission_callback' => '__return_true',
	) );
}
add_action( 'rest_api_init', 'ec_booking_api_routes' );
