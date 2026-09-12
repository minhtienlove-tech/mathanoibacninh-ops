<?php
/**
 * Tiếp nhận yêu cầu lịch khám; dữ liệu chỉ hiển thị với quản trị viên.
 * Không gửi thông tin người đăng ký tới email hoặc dịch vụ bên ngoài.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Đồng hồ của bệnh viện, không phụ thuộc múi giờ PHP/WordPress. */
function ec_booking_now() {
	return new DateTimeImmutable( 'now', new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
}

/** Giờ bắt đầu lịch khám muộn nhất 17:00, luôn trước giờ đóng cửa. */
function ec_booking_schedule( $now = null, $hours = null ) {
	$now   = $now ?: ec_booking_now();
	$now   = $now->setTimezone( new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
	$hours = null === $hours ? eyecare_du_lieu_thuc_the() : $hours;
	$open  = isset( $hours['gio_mo'] ) ? $hours['gio_mo'] : '';
	$close = isset( $hours['gio_dong'] ) ? $hours['gio_dong'] : '';
	$valid = '/^(?:[01]\d|2[0-3]):[0-5]\d$/D';
	if ( ! is_string( $open ) || ! is_string( $close ) || ! preg_match( $valid, $open ) || ! preg_match( $valid, $close ) || $open >= $close ) {
		return new WP_Error( 'hours', 'Chưa thể tải giờ tiếp nhận. Vui lòng gọi bệnh viện để được hỗ trợ.' );
	}
	$start = (int) substr( $open, 0, 2 ) * 60 + (int) substr( $open, 3, 2 );
	$end   = (int) substr( $close, 0, 2 ) * 60 + (int) substr( $close, 3, 2 );
	$slots = array();
	for ( $minute = $start; $minute < $end && $minute <= 17 * 60; $minute += 30 ) {
		$slots[] = sprintf( '%02d:%02d', intdiv( $minute, 60 ), $minute % 60 );
	}
	return array(
		'today'   => $now->format( 'Y-m-d' ),
		'now'     => $now->format( DATE_ATOM ),
		'open'    => $open,
		'close'   => $close,
		'slots'   => $slots,
		'maxDate' => $now->modify( '+90 days' )->format( 'Y-m-d' ),
	);
}

/** Kiểm tra dữ liệu trước mọi thao tác ghi; có thể kiểm thử ngoài WordPress. */
function ec_booking_validate( $input, $now = null, $hours = null, $check_schedule = true ) {
	$now = $now ?: ec_booking_now();
	if ( ! is_array( $input ) ) {
		return new WP_Error( 'input', 'Thông tin gửi lên không hợp lệ.' );
	}
	$limits = array( 'name' => 400, 'phone' => 40, 'date' => 10, 'time' => 5, 'consent' => 1, 'website' => 200, 'request_id' => 36 );
	foreach ( $limits as $field => $limit ) {
		if ( ! isset( $input[ $field ] ) || ! is_string( $input[ $field ] ) || strlen( $input[ $field ] ) > $limit ) {
			return new WP_Error( 'input', 'Vui lòng kiểm tra và điền đầy đủ thông tin đặt lịch.' );
		}
	}
	if ( '' !== $input['website'] ) {
		return new WP_Error( 'input', 'Không thể tiếp nhận yêu cầu này. Vui lòng thử lại.' );
	}
	if ( '1' !== $input['consent'] ) {
		return new WP_Error( 'consent', 'Vui lòng đồng ý để bệnh viện liên hệ xác nhận lịch khám.' );
	}
	$name = trim( preg_replace( '/\s+/u', ' ', $input['name'] ) ?? '' );
	if ( ! preg_match( '/^[\p{L}\p{M}][\p{L}\p{M} .\x{2019}\x{0027}-]{1,99}$/uD', $name ) ) {
		return new WP_Error( 'name', 'Vui lòng nhập họ tên hợp lệ (từ 2 đến 100 ký tự).' );
	}
	$phone = preg_replace( '/[\s().-]/', '', $input['phone'] );
	if ( ! preg_match( '/^\+?[0-9]{9,15}$/D', $phone ) || preg_match( '/^\+?([0-9])\1+$/D', $phone ) ) {
		return new WP_Error( 'phone', 'Vui lòng nhập số điện thoại hợp lệ.' );
	}
	// Cùng một số Việt Nam không thể vượt giới hạn bằng cách đổi +84/0.
	if ( 0 === strpos( $phone, '+84' ) ) {
		$phone = '0' . substr( $phone, 3 );
	}
	if ( ! preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/iD', $input['request_id'] ) ) {
		return new WP_Error( 'request_id', 'Phiên đặt lịch không hợp lệ. Vui lòng mở lại biểu mẫu.' );
	}
	// Loại cả byte NUL trước createFromFormat (PHP 8 ném ValueError với NUL).
	if ( ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $input['date'] ) || ! preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $input['time'] ) ) {
		return new WP_Error( 'date', 'Ngày hoặc giờ đặt lịch không hợp lệ.' );
	}
	$moment = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $input['date'] . ' ' . $input['time'], new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
	if ( ! $moment || $moment->format( 'Y-m-d H:i' ) !== $input['date'] . ' ' . $input['time'] ) {
		return new WP_Error( 'date', 'Ngày hoặc giờ đặt lịch không hợp lệ.' );
	}
	if ( $check_schedule ) {
		$schedule = ec_booking_schedule( $now, $hours );
		if ( is_wp_error( $schedule ) ) {
			return $schedule;
		}
		if ( $input['date'] < $schedule['today'] || $input['date'] > $schedule['maxDate'] || $moment <= $now ) {
			return new WP_Error( 'date', 'Vui lòng chọn thời điểm sắp tới trong vòng 90 ngày.' );
		}
		if ( ! in_array( $input['time'], $schedule['slots'], true ) ) {
			return new WP_Error( 'time', 'Vui lòng chọn giờ khám trong khung giờ làm việc của bệnh viện.' );
		}
	}
	return array( 'name' => $name, 'phone' => $phone, 'date' => $input['date'], 'time' => $input['time'], 'request_id' => strtolower( $input['request_id'] ), 'consent' => true );
}

/** CPT hoàn toàn riêng tư: chỉ người có manage_options được đọc/quản lý. */
function ec_booking_register() {
	$caps = array();
	foreach ( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'read' ) as $cap ) {
		$caps[ $cap ] = 'manage_options';
	}
	$caps['create_posts'] = 'do_not_allow';
	register_post_type( 'ec_appointment', array(
		'labels'              => array( 'name' => 'Lịch hẹn khám', 'singular_name' => 'Yêu cầu lịch khám', 'edit_item' => 'Xử lý yêu cầu lịch khám', 'not_found' => 'Chưa có yêu cầu đặt lịch.' ),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'show_in_nav_menus'   => false,
		'query_var'           => false,
		'rewrite'             => false,
		'has_archive'         => false,
		'can_export'          => false,
		'supports'            => false,
		'menu_icon'           => 'dashicons-calendar-alt',
		'capabilities'        => $caps,
		'map_meta_cap'        => false,
	) );
}
add_action( 'init', 'ec_booking_register' );

/** Khóa nguyên tử; xóa khóa hết hạn chỉ khi giá trị chưa bị tiến trình khác thay. */
function ec_booking_lock( $key ) {
	global $wpdb;
	$name    = '_ec_booking_lock_' . hash( 'sha256', $key );
	$expires = (string) ( time() + 300 );
	if ( ! add_option( $name, $expires, '', false ) ) {
		$previous = get_option( $name );
		if ( ! is_scalar( $previous ) || ! ctype_digit( (string) $previous ) || (int) $previous >= time() ) {
			return false;
		}
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, (string) $previous ) );
		wp_cache_delete( $name, 'options' );
		if ( ! add_option( $name, $expires, '', false ) ) {
			return false;
		}
	}
	return array( 'name' => $name, 'value' => $expires );
}

function ec_booking_unlock( $lock ) {
	global $wpdb;
	if ( $lock ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock['name'], $lock['value'] ) );
		wp_cache_delete( $lock['name'], 'options' );
	}
}

/** Giới hạn 5 yêu cầu/giờ cho từng IP và số điện thoại; chỉ lưu khóa HMAC. */
function ec_booking_rate_limit( $phone ) {
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	$hour = (string) intdiv( time(), 3600 );
	$keys = array(
		'ec_booking_rate_' . hash_hmac( 'sha256', 'ip:' . $ip . ':' . $hour, wp_salt( 'nonce' ) ),
		'ec_booking_rate_' . hash_hmac( 'sha256', 'phone:' . $phone . ':' . $hour, wp_salt( 'nonce' ) ),
	);
	sort( $keys );
	$locks = array();
	try {
		foreach ( $keys as $key ) {
			$lock = ec_booking_lock( $key );
			if ( ! $lock ) {
				return false;
			}
			$locks[] = $lock;
			if ( (int) get_transient( $key ) >= 5 ) {
				return false;
			}
		}
		foreach ( $keys as $key ) {
			if ( ! set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS ) ) {
				return false;
			}
		}
		return true;
	} finally {
		foreach ( $locks as $lock ) {
			ec_booking_unlock( $lock );
		}
	}
}

/** Dữ liệu chính cùng một hàng post: lưu thành công trước khi trả xác nhận. */
function ec_booking_read( $post ) {
	$data = json_decode( $post->post_content, true );
	return is_array( $data ) ? $data : array();
}

function ec_booking_find_request( $slug ) {
	$posts = get_posts( array( 'post_type' => 'ec_appointment', 'post_status' => array( 'private', 'trash' ), 'post_name__in' => array( $slug, $slug . '__trashed' ), 'numberposts' => 1, 'suppress_filters' => true ) );
	return $posts ? $posts[0] : null;
}

/** So sánh đầy đủ payload đã lưu; retry vẫn thành công khi giờ khám đã qua. */
function ec_booking_replay( $booking ) {
	$request_hash = hash_hmac( 'sha256', $booking['request_id'], wp_salt( 'nonce' ) );
	$existing     = ec_booking_find_request( 'ec-' . $request_hash );
	if ( ! $existing ) {
		return false;
	}
	$payload = $booking;
	unset( $payload['request_id'] );
	$fingerprint = hash_hmac( 'sha256', wp_json_encode( $payload ), wp_salt( 'nonce' ) );
	$data        = ec_booking_read( $existing );
	if ( isset( $data['fingerprint'] ) && is_string( $data['fingerprint'] ) && hash_equals( $data['fingerprint'], $fingerprint ) ) {
		return (int) $existing->ID;
	}
	return new WP_Error( 'request_conflict', 'Yêu cầu này đã được gửi với thông tin khác. Vui lòng mở lại biểu mẫu.', array( 'status' => 409 ) );
}

/** Lưu yêu cầu mới hoặc xác nhận lại đúng yêu cầu đã lưu (retry). */
function ec_booking_store( $booking ) {
	$request_hash = hash_hmac( 'sha256', $booking['request_id'], wp_salt( 'nonce' ) );
	$slug         = 'ec-' . $request_hash;
	$payload      = $booking;
	unset( $payload['request_id'] );
	$fingerprint = hash_hmac( 'sha256', wp_json_encode( $payload ), wp_salt( 'nonce' ) );
	$lock        = ec_booking_lock( 'request:' . $request_hash );
	if ( ! $lock ) {
		return new WP_Error( 'busy', 'Yêu cầu đang được xử lý. Vui lòng chờ ít giây rồi thử lại.', array( 'status' => 409 ) );
	}
	try {
		$replay = ec_booking_replay( $booking );
		if ( false !== $replay ) {
			return $replay;
		}
		if ( ! ec_booking_rate_limit( $booking['phone'] ) ) {
			return new WP_Error( 'rate_limit', 'Bạn đã gửi nhiều yêu cầu. Vui lòng thử lại sau hoặc gọi bệnh viện.', array( 'status' => 429 ) );
		}
		$payload['fingerprint'] = $fingerprint;
		$payload['received_at'] = ec_booking_now()->format( DATE_ATOM );
		$post_id = wp_insert_post( wp_slash( array(
			'post_type'    => 'ec_appointment',
			'post_status'  => 'private',
			'post_name'    => $slug,
			'post_title'   => 'Yêu cầu khám ' . $booking['date'] . ' ' . $booking['time'],
			'post_content' => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE ),
			'post_author'  => 0,
		) ), true );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return new WP_Error( 'storage', 'Chưa lưu được yêu cầu. Vui lòng thử lại hoặc gọi bệnh viện.', array( 'status' => 503 ) );
		}
		return (int) $post_id;
	} finally {
		ec_booking_unlock( $lock );
	}
}

function ec_booking_ajax_config() {
	nocache_headers();
	$schedule = ec_booking_schedule();
	if ( is_wp_error( $schedule ) ) {
		wp_send_json_error( array( 'code' => $schedule->get_error_code(), 'message' => $schedule->get_error_message() ), 503 );
	}
	$schedule['nonce'] = wp_create_nonce( 'ec_booking_submit' );
	wp_send_json_success( $schedule );
}
add_action( 'wp_ajax_ec_booking_config', 'ec_booking_ajax_config' );
add_action( 'wp_ajax_nopriv_ec_booking_config', 'ec_booking_ajax_config' );

function ec_booking_ajax_submit() {
	nocache_headers();
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		wp_send_json_error( array( 'code' => 'method', 'message' => 'Phương thức gửi không hợp lệ.' ), 405 );
	}
	if ( ! isset( $_POST['nonce'] ) || ! is_string( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ec_booking_submit' ) ) {
		wp_send_json_error( array( 'code' => 'nonce', 'message' => 'Phiên đặt lịch đã hết hạn. Vui lòng đóng và mở lại biểu mẫu.' ), 403 );
	}
	// Xác minh cấu trúc và consent trước khi tìm replay; chưa áp giới hạn ngày giờ.
	$input   = wp_unslash( $_POST );
	$booking = ec_booking_validate( $input, null, null, false );
	if ( is_wp_error( $booking ) ) {
		wp_send_json_error( array( 'code' => $booking->get_error_code(), 'message' => $booking->get_error_message() ), 400 );
	}
	$result = ec_booking_replay( $booking );
	if ( false === $result ) {
		$booking = ec_booking_validate( $input );
		if ( is_wp_error( $booking ) ) {
			wp_send_json_error( array( 'code' => $booking->get_error_code(), 'message' => $booking->get_error_message() ), 400 );
		}
		$result = ec_booking_store( $booking );
	}
	if ( is_wp_error( $result ) ) {
		$error_data = $result->get_error_data();
		wp_send_json_error( array( 'code' => $result->get_error_code(), 'message' => $result->get_error_message() ), isset( $error_data['status'] ) ? $error_data['status'] : 503 );
	}
	wp_send_json_success( array( 'message' => 'Đã nhận yêu cầu. Bệnh viện sẽ liên hệ xác nhận lịch khám.' ) );
}
add_action( 'wp_ajax_ec_booking_submit', 'ec_booking_ajax_submit' );
add_action( 'wp_ajax_nopriv_ec_booking_submit', 'ec_booking_ajax_submit' );

function ec_booking_admin_columns( $columns ) {
	return array( 'cb' => $columns['cb'], 'title' => 'Yêu cầu', 'ec_name' => 'Họ tên', 'ec_phone' => 'Điện thoại', 'ec_slot' => 'Lịch mong muốn', 'ec_status' => 'Trạng thái', 'date' => 'Ngày tiếp nhận' );
}
add_filter( 'manage_ec_appointment_posts_columns', 'ec_booking_admin_columns' );

function ec_booking_statuses() {
	return array( 'pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'contacted' => 'Đã liên hệ', 'cancelled' => 'Đã hủy' );
}

function ec_booking_admin_column( $column, $post_id ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$data = ec_booking_read( get_post( $post_id ) );
	if ( 'ec_name' === $column || 'ec_phone' === $column ) {
		$key = 'ec_name' === $column ? 'name' : 'phone';
		echo esc_html( isset( $data[ $key ] ) ? $data[ $key ] : '' );
	} elseif ( 'ec_slot' === $column ) {
		echo esc_html( isset( $data['date'], $data['time'] ) ? $data['date'] . ' · ' . $data['time'] : '' );
	} elseif ( 'ec_status' === $column ) {
		$statuses = ec_booking_statuses();
		$status   = get_post_meta( $post_id, '_ec_booking_status', true );
		echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['pending'] );
	}
}
add_action( 'manage_ec_appointment_posts_custom_column', 'ec_booking_admin_column', 10, 2 );

function ec_booking_admin_boxes() {
	remove_meta_box( 'submitdiv', 'ec_appointment', 'side' );
	add_meta_box( 'ec-booking-details', 'Thông tin đặt lịch', 'ec_booking_admin_details', 'ec_appointment', 'normal', 'high' );
}
add_action( 'add_meta_boxes_ec_appointment', 'ec_booking_admin_boxes' );

function ec_booking_admin_details( $post ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$data   = ec_booking_read( $post );
	$fields = array( 'name' => 'Họ tên', 'phone' => 'Số điện thoại', 'date' => 'Ngày mong muốn', 'time' => 'Giờ mong muốn', 'received_at' => 'Tiếp nhận lúc' );
	echo '<table class="form-table"><tbody>';
	foreach ( $fields as $key => $label ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( isset( $data[ $key ] ) ? $data[ $key ] : '' ) . '</td></tr>';
	}
	echo '<tr><th scope="row">Đồng ý liên hệ</th><td>' . ( ! empty( $data['consent'] ) ? 'Đã đồng ý' : 'Chưa ghi nhận' ) . '</td></tr></tbody></table>';
	wp_nonce_field( 'ec_booking_admin_status', 'ec_booking_admin_nonce' );
	$status = get_post_meta( $post->ID, '_ec_booking_status', true ) ?: 'pending';
	echo '<p><label for="ec-booking-status"><strong>Trạng thái xử lý</strong></label></p><select id="ec-booking-status" name="ec_booking_status">';
	foreach ( ec_booking_statuses() as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select><p>Lịch khám chỉ được xác nhận sau khi nhân viên bệnh viện liên hệ với người đăng ký.</p>';
	submit_button( 'Lưu trạng thái', 'primary', 'save', false );
}

function ec_booking_admin_save( $post_id ) {
	if ( ! current_user_can( 'manage_options' ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['ec_booking_admin_nonce'], $_POST['ec_booking_status'] ) || ! is_string( $_POST['ec_booking_admin_nonce'] ) || ! is_string( $_POST['ec_booking_status'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ec_booking_admin_nonce'] ) ), 'ec_booking_admin_status' ) ) {
		return;
	}
	$status = sanitize_key( wp_unslash( $_POST['ec_booking_status'] ) );
	if ( array_key_exists( $status, ec_booking_statuses() ) ) {
		update_post_meta( $post_id, '_ec_booking_status', $status );
	}
}
add_action( 'save_post_ec_appointment', 'ec_booking_admin_save' );

/** Tránh sửa nhanh trạng thái post thành public hoặc thay tên tham chiếu. */
function ec_booking_row_actions( $actions, $post ) {
	if ( 'ec_appointment' === $post->post_type ) {
		unset( $actions['inline hide-if-no-js'], $actions['view'] );
	}
	return $actions;
}
add_filter( 'post_row_actions', 'ec_booking_row_actions', 10, 2 );

function ec_booking_keep_private( $data ) {
	if ( isset( $data['post_type'] ) && 'ec_appointment' === $data['post_type'] && 'trash' !== $data['post_status'] ) {
		$data['post_status'] = 'private';
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'ec_booking_keep_private' );
