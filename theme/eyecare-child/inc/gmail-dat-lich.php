<?php
/** Gmail SMTP for appointment notifications only; no global wp_mail hooks. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ec_gmail_settings() {
	$raw = get_option( 'ec_booking_gmail', array() );
	return wp_parse_args( is_array( $raw ) ? $raw : array(), array( 'enabled' => false, 'username' => '', 'recipient' => '', 'cipher' => '', 'version' => '' ) );
}

/** Shared authenticated encryption helper; credentials stay in private server options. */
function ec_gmail_password( $settings = null ) { return ec_zalo_token( $settings ?? ec_gmail_settings() ); }
function ec_gmail_ready( $s ) { return is_email( $s['username'] ) && is_email( $s['recipient'] ) && preg_match( '/^[a-zA-Z0-9]{16}$/D', ec_gmail_password( $s ) ); }

/** Validate before changing any saved option. A different account needs its own password. */
function ec_gmail_prepare_settings( $input, $old ) {
	foreach ( array( 'username', 'recipient', 'app_password' ) as $key ) {
		if ( ! isset( $input[ $key ] ) || ! is_string( $input[ $key ] ) ) { return new WP_Error( 'input', 'Thông tin cấu hình không hợp lệ.' ); }
	}
	$username = trim( wp_unslash( $input['username'] ) );
	$recipient = trim( wp_unslash( $input['recipient'] ) );
	$password = preg_replace( '/\s+/', '', wp_unslash( $input['app_password'] ) );
	if ( ! is_email( $username ) || ! is_email( $recipient ) ) { return new WP_Error( 'email', 'Nhập đầy đủ email gửi và một email nhận hợp lệ.' ); }
	if ( '' !== $password && ! preg_match( '/^[a-zA-Z0-9]{16}$/D', $password ) ) { return new WP_Error( 'password', 'Mật khẩu ứng dụng Google phải gồm 16 ký tự. Không dùng mật khẩu đăng nhập Gmail.' ); }
	if ( '' === $password && ( strcasecmp( $username, $old['username'] ) !== 0 || ! ec_gmail_password( $old ) ) ) { return new WP_Error( 'password', 'Vui lòng nhập mật khẩu ứng dụng của tài khoản gửi.' ); }
	$s = $old;
	if ( '' !== $password ) {
		$s['cipher'] = ec_zalo_encrypt( $password );
		if ( is_wp_error( $s['cipher'] ) ) { return new WP_Error( 'crypto', 'Không thể lưu mật khẩu bảo mật trên máy chủ.' ); }
	}
	$s['username'] = $username;
	$s['recipient'] = $recipient;
	$s['enabled'] = isset( $input['enabled'] ) && '1' === $input['enabled'];
	$s['version'] = hash_hmac( 'sha256', $username . '|' . $recipient . '|' . ec_gmail_password( $s ), wp_salt( 'auth' ) );
	return $s;
}

/** Own PHPMailer instance keeps appointment SMTP separate from all other website mail. */
function ec_gmail_send( $subject, $body ) {
	$s = ec_gmail_settings();
	if ( ! ec_gmail_ready( $s ) ) { return new WP_Error( 'config', 'Vui lòng lưu Gmail, mật khẩu ứng dụng và email nhận trước.' ); }
	$mail = null;
	try {
		if ( ! class_exists( '\PHPMailer\PHPMailer\PHPMailer' ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
		}
		if ( ! class_exists( '\PHPMailer\PHPMailer\SMTP' ) ) { require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php'; }
		$mail = new \PHPMailer\PHPMailer\PHPMailer( true );
		$mail->isSMTP();
		$mail->Host = 'smtp.gmail.com';
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;
		$mail->SMTPDebug = 0;
		$mail->SMTPOptions = array( 'ssl' => array( 'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false ) );
		$mail->Timeout = 15;
		$mail->getSMTPInstance()->Timelimit = 30;
		$mail->Username = $s['username'];
		$mail->Password = ec_gmail_password( $s );
		$mail->CharSet = 'UTF-8';
		$mail->setFrom( $s['username'], 'Bệnh viện Mắt Hà Nội – Bắc Ninh' );
		$mail->addAddress( $s['recipient'] );
		$mail->isHTML( false );
		$mail->Subject = $subject;
		$mail->Body = $body;
		if ( $mail->send() ) { return true; }
	} catch ( Throwable $error ) {
		// Never expose SMTP dialogue, credentials or raw exception messages.
	} finally {
		if ( $mail ) { $mail->Password = ''; $mail->smtpClose(); }
	}
	return new WP_Error( 'uncertain', 'Chưa xác nhận gửi thành công. Kiểm tra thư đến/thư rác, email nhận, mật khẩu ứng dụng và kết nối SMTP; kiểm tra hộp thư trước khi gửi lại.' );
}

function ec_gmail_menu() { add_submenu_page( 'edit.php?post_type=ec_appointment', 'Cài đặt Gmail', 'Cài đặt Gmail', 'manage_options', 'ec-gmail-settings', 'ec_gmail_page' ); }
add_action( 'admin_menu', 'ec_gmail_menu' );
function ec_gmail_flash( $message, $error = false ) {
	set_transient( 'ec_gmail_notice_' . get_current_user_id(), array( 'message' => $message, 'error' => $error ), 60 );
	wp_safe_redirect( admin_url( 'edit.php?post_type=ec_appointment&page=ec-gmail-settings' ) );
	exit;
}
function ec_gmail_admin_action() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Bạn không có quyền cấu hình Gmail.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'ec_gmail_settings', 'ec_gmail_nonce' );
	$mode = isset( $_POST['ec_gmail_mode'] ) && is_string( $_POST['ec_gmail_mode'] ) ? $_POST['ec_gmail_mode'] : '';
	if ( 'save' === $mode ) {
		$s = ec_gmail_prepare_settings( $_POST, ec_gmail_settings() );
		if ( is_wp_error( $s ) ) { ec_gmail_flash( $s->get_error_message(), true ); }
		update_option( 'ec_booking_gmail', $s, false );
		if ( ec_gmail_settings() !== $s ) { ec_gmail_flash( 'Chưa lưu được cấu hình. Vui lòng thử lại.', true ); }
		ec_gmail_flash( $s['enabled'] ? 'Đã bật thông báo Gmail cho lịch đăng ký mới.' : 'Đã lưu cấu hình. Hãy gửi email kiểm tra, sau đó bật thông báo tự động.' );
	}
	if ( 'test' === $mode ) {
		$result = ec_gmail_send( '[HN–BN] Kiểm tra kết nối Gmail', "Đây là email kiểm tra thông báo đặt lịch của Bệnh viện Mắt Hà Nội – Bắc Ninh.\nNếu nhận được thư này, anh/chị có thể bật thông báo tự động trong Cài đặt Gmail." );
		ec_gmail_flash( is_wp_error( $result ) ? $result->get_error_message() : 'Gmail đã chấp nhận email kiểm tra. Hãy kiểm tra hộp thư đến và thư rác của người nhận.', is_wp_error( $result ) );
	}
	ec_gmail_flash( 'Thao tác không hợp lệ.', true );
}
add_action( 'admin_post_ec_gmail_settings', 'ec_gmail_admin_action' );

function ec_gmail_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Bạn không có quyền truy cập.' ); }
	$s = ec_gmail_settings();
	$notice = get_transient( 'ec_gmail_notice_' . get_current_user_id() );
	?>
	<div class="wrap" style="max-width:960px"><h1>Cài đặt thông báo Gmail</h1>
	<p>Nhận email khi có yêu cầu đặt lịch mới. Gmail và Zalo có thể bật độc lập hoặc dùng cùng lúc.</p>
	<?php if ( is_array( $notice ) ) : ?><div class="notice <?php echo $notice['error'] ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div><?php endif; ?>
	<div class="card" style="max-width:none;padding:20px 28px"><h2>1. Kết nối Gmail</h2>
	<ol><li>Bật <strong>Xác minh 2 bước</strong> cho tài khoản Google dùng gửi thư.</li><li><a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener noreferrer">Tạo mật khẩu ứng dụng</a>, đặt tên “Website đặt lịch bệnh viện”.</li><li>Nhập email gửi, mật khẩu ứng dụng 16 ký tự và email nhận bên dưới, rồi lưu.</li><li>Gửi email kiểm tra; nhận được thư rồi bật thông báo tự động.</li></ol>
	<p><a href="https://support.google.com/accounts/answer/185833?hl=vi" target="_blank" rel="noopener noreferrer">Hướng dẫn của Google khi không thấy mục mật khẩu ứng dụng</a></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ec_gmail_settings"><input type="hidden" name="ec_gmail_mode" value="save"><?php wp_nonce_field( 'ec_gmail_settings', 'ec_gmail_nonce' ); ?>
	<table class="form-table">
	<tr><th scope="row"><label for="ec-gmail-user">Email Gmail gửi</label></th><td><input id="ec-gmail-user" name="username" type="email" class="regular-text" maxlength="254" required value="<?php echo esc_attr( $s['username'] ); ?>" autocomplete="off"><p class="description">Địa chỉ đầy đủ của tài khoản Gmail hoặc Google Workspace.</p></td></tr>
	<tr><th scope="row"><label for="ec-gmail-password">Mật khẩu ứng dụng</label></th><td><input id="ec-gmail-password" name="app_password" type="password" class="regular-text" maxlength="64" autocomplete="new-password" value="" placeholder="<?php echo ec_gmail_password( $s ) ? 'Đã lưu — để trống để giữ mật khẩu' : 'Mật khẩu ứng dụng 16 ký tự'; ?>"><p class="description">Mật khẩu được mã hóa khi lưu và không hiển thị lại. Có thể dán cả khoảng trắng. Khi đổi email gửi, hãy nhập mật khẩu ứng dụng tương ứng.</p></td></tr>
	<tr><th scope="row"><label for="ec-gmail-recipient">Email nhận thông báo</label></th><td><input id="ec-gmail-recipient" name="recipient" type="email" class="regular-text" maxlength="254" required value="<?php echo esc_attr( $s['recipient'] ); ?>" autocomplete="off"><p class="description">Một địa chỉ email của nhân viên phụ trách lịch khám; có thể trùng email gửi.</p></td></tr>
	<tr><th scope="row">Thông báo tự động</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?>> Bật gửi email cho các lịch đăng ký mới</label><p class="description">Email gồm mã lịch, ngày giờ và liên kết xem chi tiết trong admin. Lịch cũ không được gửi hàng loạt.</p></td></tr>
	</table><?php submit_button( 'Lưu cấu hình Gmail' ); ?></form></div>
	<div class="card" style="max-width:none;padding:20px 28px"><h2>2. Kiểm tra gửi email</h2><p>Email kiểm tra được gửi đến địa chỉ nhận đã lưu ở trên.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ec_gmail_settings"><input type="hidden" name="ec_gmail_mode" value="test"><?php wp_nonce_field( 'ec_gmail_settings', 'ec_gmail_nonce' ); ?><button class="button" <?php disabled( ! ec_gmail_ready( $s ) ); ?>>Gửi email kiểm tra</button></form>
	<p class="description">Kết nối Gmail qua TLS. Thông báo lịch mới được xử lý nền, có thể chậm tùy lượt truy cập website. Xem kết quả gửi trong chi tiết từng lịch hẹn.</p></div></div>
	<?php
}

function ec_gmail_queue( $id ) {
	$s = ec_gmail_settings();
	$post = get_post( $id );
	if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status || ! $s['enabled'] || ! ec_gmail_ready( $s ) ) { return; }
	if ( in_array( get_post_meta( $id, '_ec_gmail_state', true ), array( 'pending', 'sending', 'sent' ), true ) ) { return; }
	update_post_meta( $id, '_ec_gmail_config', $s['version'] );
	update_post_meta( $id, '_ec_gmail_state', 'pending' );
	$scheduled = wp_next_scheduled( 'ec_gmail_deliver', array( (int) $id ) ) ?: wp_schedule_single_event( time() + 3, 'ec_gmail_deliver', array( (int) $id ), true );
	if ( is_wp_error( $scheduled ) || ! $scheduled ) { update_post_meta( $id, '_ec_gmail_state', 'failed' ); update_post_meta( $id, '_ec_gmail_error', 'Chưa đưa được thông báo vào hàng đợi.' ); }
}
add_action( 'ec_booking_created', 'ec_gmail_queue' );

function ec_gmail_message( $post ) {
	$data = ec_booking_read( $post );
	return "CÓ YÊU CẦU ĐẶT LỊCH MỚI\nBệnh viện Mắt Hà Nội – Bắc Ninh\nMã lịch: #" . $post->ID . "\nNgày khám: " . ec_booking_admin_date_label( $data['date'] ?? '' ) . "\nGiờ khám: " . ( $data['time'] ?? '' ) . "\nXem chi tiết: " . admin_url( 'post.php?post=' . (int) $post->ID . '&action=edit' );
}

function ec_gmail_deliver( $id ) {
	$lock = ec_booking_lock( 'gmail:' . (int) $id );
	if ( ! $lock ) { return; }
	try {
		$post = get_post( $id );
		if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status || 'pending' !== get_post_meta( $id, '_ec_gmail_state', true ) ) { return; }
		$s = ec_gmail_settings();
		if ( ! $s['enabled'] || $s['version'] !== get_post_meta( $id, '_ec_gmail_config', true ) ) { update_post_meta( $id, '_ec_gmail_state', 'skipped' ); return; }
		update_post_meta( $id, '_ec_gmail_started', time() );
		if ( ! update_post_meta( $id, '_ec_gmail_state', 'sending' ) ) { return; }
		$result = ec_gmail_send( '[HN–BN] Yêu cầu đặt lịch #' . (int) $id, ec_gmail_message( $post ) );
		if ( is_wp_error( $result ) ) {
			update_post_meta( $id, '_ec_gmail_state', 'uncertain' === $result->get_error_code() ? 'unknown' : 'failed' );
			update_post_meta( $id, '_ec_gmail_error', $result->get_error_message() );
		} else {
			update_post_meta( $id, '_ec_gmail_state', 'sent' );
			update_post_meta( $id, '_ec_gmail_sent_at', time() );
		}
	} finally { ec_booking_unlock( $lock ); }
}
add_action( 'ec_gmail_deliver', 'ec_gmail_deliver' );

function ec_gmail_delivery_box_register() { add_meta_box( 'ec-gmail-delivery', 'Thông báo Gmail', 'ec_gmail_delivery_box', 'ec_appointment', 'side' ); }
add_action( 'add_meta_boxes_ec_appointment', 'ec_gmail_delivery_box_register' );

function ec_gmail_delivery_box( $post ) {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$state = get_post_meta( $post->ID, '_ec_gmail_state', true );
	$labels = array( 'pending' => 'Đang chờ gửi', 'sending' => 'Đang gửi — kiểm tra Gmail trước khi gửi lại', 'sent' => 'Gmail đã chấp nhận thư', 'failed' => 'Chưa gửi được', 'unknown' => 'Chưa xác định kết quả — kiểm tra trên Gmail trước khi gửi lại', 'skipped' => 'Bỏ qua do đã tắt hoặc thay đổi cấu hình' );
	echo '<p>' . esc_html( $labels[ $state ] ?? 'Chưa có thông báo cho lịch này.' ) . '</p>';
	if ( in_array( $state, array( 'failed', 'unknown' ), true ) ) { echo '<p>' . esc_html( get_post_meta( $post->ID, '_ec_gmail_error', true ) ) . '</p>'; }
	if ( ! in_array( $state, array( 'pending', 'sent' ), true ) && ec_gmail_settings()['enabled'] && ( 'sending' !== $state || (int) get_post_meta( $post->ID, '_ec_gmail_started', true ) < time() - 120 ) ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=ec_gmail_retry&appointment=' . (int) $post->ID ), 'ec_gmail_retry_' . $post->ID );
		echo '<p><a class="button" href="' . esc_url( $url ) . '">Gửi thông báo Gmail</a></p>';
	}
	echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=ec_appointment&page=ec-gmail-settings' ) ) . '">Cài đặt Gmail</a>';
}

function ec_gmail_retry() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Không có quyền gửi thông báo.', '', array( 'response' => 403 ) ); }
	$id = isset( $_GET['appointment'] ) && is_string( $_GET['appointment'] ) ? absint( $_GET['appointment'] ) : 0;
	check_admin_referer( 'ec_gmail_retry_' . $id );
	$post = get_post( $id );
	if ( ! $post || 'ec_appointment' !== $post->post_type || 'private' !== $post->post_status ) { wp_die( 'Không tìm thấy lịch hẹn.' ); }
	$lock = ec_booking_lock( 'gmail:' . $id );
	if ( $lock ) {
		try {
			$state = get_post_meta( $id, '_ec_gmail_state', true );
			if ( ! in_array( $state, array( 'pending', 'sent' ), true ) && ( 'sending' !== $state || (int) get_post_meta( $id, '_ec_gmail_started', true ) < time() - 120 ) ) {
				update_post_meta( $id, '_ec_gmail_state', 'retry' );
				ec_gmail_queue( $id );
			}
		} finally { ec_booking_unlock( $lock ); }
	}
	wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
	exit;
}
add_action( 'admin_post_ec_gmail_retry', 'ec_gmail_retry' );
