<?php
/** Zalo Bot notifications. Official API: docs.zaloplatforms.com/docs/BOT/apis/sendMessage */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ec_zalo_settings() {
	$raw = get_option( 'ec_booking_zalo', array() );
	return wp_parse_args( is_array( $raw ) ? $raw : array(), array( 'enabled' => false, 'cipher' => '', 'chat_id' => '', 'bot_name' => '', 'version' => '' ) );
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

/** Fixed HTTPS endpoint and no redirects prevent accidental token disclosure. */
function ec_zalo_api( $method, $body = array(), $token = null ) {
	$token = $token ?? ec_zalo_token();
	if ( ! in_array( $method, array( 'getMe', 'getUpdates', 'sendMessage' ), true ) || ! ec_zalo_valid_token( $token ) ) { return new WP_Error( 'config', 'Vui lòng lưu Bot Token hợp lệ trước.' ); }
	$response = wp_remote_post( 'https://bot-api.zaloplatforms.com/bot' . $token . '/' . $method, array( 'timeout' => 10, 'redirection' => 0, 'sslverify' => true, 'limit_response_size' => 65536, 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( (object) $body ) ) );
	// Never persist or display raw HTTP errors: they may contain the token-bearing URL.
	if ( is_wp_error( $response ) ) { return new WP_Error( 'uncertain', 'Chưa nhận được phản hồi từ Zalo. Kiểm tra tin nhắn trước khi gửi lại.' ); }
	$code = wp_remote_retrieve_response_code( $response );
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $code >= 200 && $code < 300 && is_array( $data ) && true === ( $data['ok'] ?? false ) && isset( $data['result'] ) && is_array( $data['result'] ) ) {
		if ( 'sendMessage' === $method && ( ! is_scalar( $data['result']['message_id'] ?? null ) || '' === (string) $data['result']['message_id'] ) ) { return new WP_Error( 'uncertain', 'Zalo chưa xác nhận mã tin nhắn. Kiểm tra trên Zalo trước khi gửi lại.' ); }
		return $data['result'];
	}
	if ( $code >= 500 || ( $code >= 200 && $code < 300 && ! is_array( $data ) ) ) { return new WP_Error( 'uncertain', 'Zalo chưa xác nhận kết quả. Kiểm tra tin nhắn trước khi gửi lại.' ); }
	return new WP_Error( 'api', 'Zalo chưa chấp nhận yêu cầu. Kiểm tra Bot Token, Chat ID và việc người nhận đã nhắn tin cho bot.' );
}

/** Only /nhanlich opt-in messages are offered as recipient candidates. */
function ec_zalo_candidates( $result ) {
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

function ec_zalo_menu() { add_submenu_page( 'edit.php?post_type=ec_appointment', 'Cài đặt Zalo', 'Cài đặt Zalo', 'manage_options', 'ec-zalo-settings', 'ec_zalo_page' ); }
add_action( 'admin_menu', 'ec_zalo_menu' );

function ec_zalo_flash( $message, $error = false ) {
	set_transient( 'ec_zalo_notice_' . get_current_user_id(), array( 'message' => $message, 'error' => $error ), 60 );
	wp_safe_redirect( admin_url( 'edit.php?post_type=ec_appointment&page=ec-zalo-settings' ) );
	exit;
}

function ec_zalo_admin_action() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Bạn không có quyền cấu hình Zalo.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'ec_zalo_settings', 'ec_zalo_nonce' );
	$settings = ec_zalo_settings();
	$mode = isset( $_POST['ec_zalo_mode'] ) && is_string( $_POST['ec_zalo_mode'] ) ? sanitize_key( $_POST['ec_zalo_mode'] ) : '';
	if ( 'save' === $mode ) {
		foreach ( array( 'bot_token', 'chat_id' ) as $key ) { if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) { ec_zalo_flash( 'Thông tin cấu hình không hợp lệ.', true ); } }
		$token = trim( wp_unslash( $_POST['bot_token'] ) );
		$chat = trim( wp_unslash( $_POST['chat_id'] ) );
		if ( '' !== $chat && ! ec_zalo_valid_chat( $chat ) ) { ec_zalo_flash( 'Chat ID không hợp lệ.', true ); }
		if ( '' !== $token ) {
			$result = ec_zalo_api( 'getMe', array(), $token );
			if ( is_wp_error( $result ) ) { ec_zalo_flash( $result->get_error_message(), true ); }
			$cipher = ec_zalo_encrypt( $token );
			if ( is_wp_error( $cipher ) ) { ec_zalo_flash( $cipher->get_error_message(), true ); }
			if ( $token !== ec_zalo_token( $settings ) ) { $chat = ''; }
			$settings['cipher'] = $cipher;
			$settings['bot_name'] = is_string( $result['account_name'] ?? null ) ? sanitize_text_field( $result['account_name'] ) : 'Zalo Bot';
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
	if ( 'discover' === $mode ) {
		$result = ec_zalo_api( 'getUpdates', array( 'timeout' => 2 ) );
		if ( is_wp_error( $result ) ) { ec_zalo_flash( $result->get_error_message(), true ); }
		$found = ec_zalo_candidates( $result );
		set_transient( 'ec_zalo_candidates_' . get_current_user_id(), $found, 600 );
		ec_zalo_flash( $found ? 'Đã tìm được người nhận. Chọn người nhận ở phần Chat ID rồi lưu cấu hình.' : 'Chưa thấy tin nhắn /nhanlich. Hãy nhắn /nhanlich cho bot, rồi bấm tìm lại.', ! $found );
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
	$candidates = get_transient( 'ec_zalo_candidates_' . get_current_user_id() );
	?>
	<div class="wrap" style="max-width:960px"><h1>Cài đặt thông báo Zalo</h1>
	<p>Nhận thông báo khi website lưu thành công một yêu cầu đặt lịch mới. Tin nhắn gồm mã lịch, ngày giờ và liên kết xem chi tiết trong admin.</p>
	<?php if ( is_array( $notice ) ) : ?><div class="notice <?php echo $notice['error'] ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div><?php endif; ?>
	<div class="card" style="max-width:none;padding:20px 28px"><h2>1. Tạo và kết nối bot</h2>
	<ol><li>Trong Zalo, tìm OA <strong>Zalo Bot Manager</strong>, chọn <strong>Tạo bot</strong>. Đặt tên bắt đầu bằng “Bot”, ví dụ “Bot Lịch khám HN–BN”.</li><li>Dán Bot Token Zalo gửi cho bạn vào ô bên dưới rồi lưu.</li><li>Dùng tài khoản sẽ nhận thông báo nhắn <code>/nhanlich</code> cho bot. Bấm <strong>Tìm người nhận</strong>, chọn Chat ID tìm được và lưu.</li><li>Gửi tin kiểm tra, sau đó bật thông báo tự động.</li></ol>
	<p><a href="https://docs.zaloplatforms.com/docs/BOT/create_bot" target="_blank" rel="noopener noreferrer">Hướng dẫn chính thức của Zalo</a></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ec_zalo_settings"><input type="hidden" name="ec_zalo_mode" value="save"><?php wp_nonce_field( 'ec_zalo_settings', 'ec_zalo_nonce' ); ?>
	<table class="form-table"><tr><th scope="row"><label for="ec-zalo-token">Bot Token</label></th><td><input id="ec-zalo-token" name="bot_token" type="password" class="regular-text" autocomplete="new-password" maxlength="256" value="" placeholder="<?php echo ec_zalo_token( $s ) ? 'Đã lưu — để trống để giữ token hiện tại' : 'Dán Bot Token'; ?>"><p class="description">Token được mã hóa khi lưu, không hiển thị lại. Khi đổi bot, cần chọn lại người nhận.</p><?php if ( $s['bot_name'] ) : ?><p>Bot: <strong><?php echo esc_html( $s['bot_name'] ); ?></strong></p><?php endif; ?></td></tr>
	<tr><th scope="row"><label for="ec-zalo-chat">Chat ID người nhận</label></th><td><input id="ec-zalo-chat" name="chat_id" type="text" class="regular-text" list="ec-zalo-recipients" maxlength="128" value="<?php echo esc_attr( $s['chat_id'] ); ?>" autocomplete="off"><datalist id="ec-zalo-recipients"><?php if ( is_array( $candidates ) ) : foreach ( $candidates as $id => $name ) : ?><option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $name ); ?></option><?php endforeach; endif; ?></datalist><p class="description">Chat ID là mã cuộc trò chuyện của bot, không phải số điện thoại Zalo.</p><?php if ( is_array( $candidates ) && $candidates ) : ?><ul><?php foreach ( $candidates as $id => $name ) : ?><li><?php echo esc_html( $name ); ?>: <code><?php echo esc_html( $id ); ?></code></li><?php endforeach; ?></ul><?php endif; ?></td></tr>
	<tr><th scope="row">Thông báo tự động</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?>> Bật thông báo cho các lịch đăng ký mới</label><p class="description">Lịch đã có trước khi bật sẽ không được gửi hàng loạt. Lịch vẫn được lưu nếu Zalo tạm thời không gửi được.</p></td></tr></table>
	<?php submit_button( 'Lưu cấu hình Zalo' ); ?></form></div>
	<div class="card" style="max-width:none;padding:20px 28px"><h2>2. Kiểm tra người nhận</h2><p>Nhắn <code>/nhanlich</code> cho bot trước khi tìm. Chức năng tìm chỉ dùng khi thiết lập ban đầu; không thay đổi webhook đã có của bot.</p>
	<?php foreach ( array( 'discover' => 'Tìm người nhận', 'test' => 'Gửi tin kiểm tra đến Chat ID đã lưu' ) as $mode => $label ) : ?><form style="display:inline-block;margin-right:12px" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ec_zalo_settings"><input type="hidden" name="ec_zalo_mode" value="<?php echo esc_attr( $mode ); ?>"><?php wp_nonce_field( 'ec_zalo_settings', 'ec_zalo_nonce' ); ?><button class="button" <?php disabled( ! ec_zalo_token( $s ) ); ?>><?php echo esc_html( $label ); ?></button></form><?php endforeach; ?>
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
