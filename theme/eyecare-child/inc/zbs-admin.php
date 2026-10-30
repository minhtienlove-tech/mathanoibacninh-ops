<?php
/**
 * Cấu hình tin ZBS gửi cho người đặt lịch và trạng thái gửi trong quản trị.
 * Khóa bí mật chỉ lưu dạng mã hóa; trang này không gửi tin khi lưu cấu hình.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ec_zbs_admin_tokens() {
	return array(
		'name'           => 'Họ tên người đặt lịch',
		'date'           => 'Ngày khám (dd/mm/yyyy)',
		'time'           => 'Giờ khám (HH:mm)',
		'schedule_time'  => 'Giờ và ngày khám (HH:mm:ss dd/mm/yyyy)',
		'appointment_id' => 'Mã lịch hẹn',
		'hospital_name'  => 'Tên bệnh viện',
		'address'        => 'Địa chỉ bệnh viện',
		'hotline'        => 'Số hotline',
	);
}

/** Mỗi dòng là tên tham số trong mẫu tin ZBS = mã dữ liệu được phép. */
function ec_zbs_admin_parse_map( $source ) {
	if ( ! is_string( $source ) || strlen( $source ) > 6000 ) {
		return new WP_Error( 'map', 'Danh sách tham số quá dài hoặc không hợp lệ.' );
	}
	$lines = preg_split( '/\r\n|\r|\n/', trim( $source ) );
	$map   = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( count( $map ) >= 20 || 1 !== substr_count( $line, '=' ) ) {
			return new WP_Error( 'map', 'Mỗi mẫu tin chỉ được có tối đa 20 dòng dạng ten_tham_so=ma_du_lieu.' );
		}
		list( $parameter, $token ) = array_map( 'trim', explode( '=', $line, 2 ) );
		if ( ! preg_match( '/^[A-Za-z][A-Za-z0-9_]{0,63}$/D', $parameter ) || ! isset( ec_zbs_admin_tokens()[ $token ] ) || isset( $map[ $parameter ] ) ) {
			return new WP_Error( 'map', 'Tên tham số, mã dữ liệu hoặc dòng trùng lặp không hợp lệ.' );
		}
		$map[ $parameter ] = $token;
	}
	return $map;
}

function ec_zbs_admin_map_text( $map ) {
	$lines = array();
	foreach ( is_array( $map ) ? $map : array() as $parameter => $token ) {
		if ( is_string( $parameter ) && is_string( $token ) && preg_match( '/^[A-Za-z][A-Za-z0-9_]{0,63}$/D', $parameter ) && isset( ec_zbs_admin_tokens()[ $token ] ) ) {
			$lines[] = $parameter . '=' . $token;
		}
	}
	return implode( "\n", $lines );
}

function ec_zbs_admin_notice( $message, $error = false ) {
	set_transient( 'ec_zbs_notice_' . get_current_user_id(), array( 'message' => $message, 'error' => (bool) $error ), MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'edit.php?post_type=ec_appointment&page=ec-zbs-settings' ) );
	exit;
}

function ec_zbs_admin_menu() {
	add_submenu_page( 'edit.php?post_type=ec_appointment', 'Tin ZBS cho bệnh nhân', 'Tin ZBS bệnh nhân', 'manage_options', 'ec-zbs-settings', 'ec_zbs_admin_page' );
}
add_action( 'admin_menu', 'ec_zbs_admin_menu' );

/** Chỉ thay các khóa thuộc biểu mẫu; giữ nguyên token mới vừa được OAuth làm mới. */
function ec_zbs_admin_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Bạn không có quyền cấu hình ZBS.', '', array( 'response' => 403 ) );
	}
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( 'Phương thức không hợp lệ.', '', array( 'response' => 405 ) );
	}
	check_admin_referer( 'ec_zbs_settings', 'ec_zbs_nonce' );
	$fields = array( 'app_id', 'secret_key', 'refresh_token', 'access_token', 'access_token_expires', 'template_confirm_id', 'confirm_map', 'template_reminder_id', 'reminder_map', 'reminder_hours' );
	$input  = array();
	foreach ( $fields as $field ) {
		if ( ! isset( $_POST[ $field ] ) || ! is_string( $_POST[ $field ] ) ) {
			ec_zbs_admin_notice( 'Thiếu trường cấu hình hoặc dữ liệu không hợp lệ.', true );
		}
		$input[ $field ] = trim( wp_unslash( $_POST[ $field ] ) );
	}
	if ( '' !== $input['app_id'] && ! preg_match( '/^[0-9]{1,32}$/D', $input['app_id'] ) ) {
		ec_zbs_admin_notice( 'App ID phải là chuỗi số do Zalo cấp.', true );
	}
	foreach ( array( 'template_confirm_id', 'template_reminder_id' ) as $field ) {
		if ( '' !== $input[ $field ] && ! preg_match( '/^[A-Za-z0-9]{1,64}$/D', $input[ $field ] ) ) {
			ec_zbs_admin_notice( 'Mã mẫu tin chỉ gồm chữ và số, tối đa 64 ký tự.', true );
		}
	}
	foreach ( array( 'secret_key', 'refresh_token', 'access_token' ) as $field ) {
		if ( strlen( $input[ $field ] ) > 4096 || preg_match( '/[\x00-\x1f\x7f]/', $input[ $field ] ) ) {
			ec_zbs_admin_notice( 'Thông tin xác thực không hợp lệ.', true );
		}
	}
	$confirm_map  = ec_zbs_admin_parse_map( $input['confirm_map'] );
	if ( is_wp_error( $confirm_map ) ) {
		ec_zbs_admin_notice( $confirm_map->get_error_message(), true );
	}
	$reminder_map = ec_zbs_admin_parse_map( $input['reminder_map'] );
	if ( is_wp_error( $reminder_map ) ) { ec_zbs_admin_notice( $reminder_map->get_error_message(), true ); }
	if ( ! preg_match( '/^[0-9]{1,2}$/D', $input['reminder_hours'] ) || (int) $input['reminder_hours'] < 1 || (int) $input['reminder_hours'] > 72 ) {
		ec_zbs_admin_notice( 'Số giờ nhắc trước cần là số nguyên từ 1 đến 72.', true );
	}
	$requested_reminder = isset( $_POST['reminder_enabled'] ) && '1' === $_POST['reminder_enabled'];
	if ( $requested_reminder && ( '659483' !== $input['template_reminder_id'] || $reminder_map != array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' ) ) ) {
		ec_zbs_admin_notice( 'Mẫu nhắc lịch #659483 cần đủ bốn tham số đúng nội dung đã duyệt.', true );
	}
	$expires_at = null;
	if ( '' !== $input['access_token'] ) {
		if ( ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}$/D', $input['access_token_expires'] ) ) {
			ec_zbs_admin_notice( 'Nhập thời điểm hết hạn khi thay Access Token.', true );
		}
		$expiry = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $input['access_token_expires'], new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
		if ( ! $expiry || $expiry->format( 'Y-m-d\TH:i' ) !== $input['access_token_expires'] || $expiry->getTimestamp() <= time() + MINUTE_IN_SECONDS ) {
			ec_zbs_admin_notice( 'Access Token phải có thời điểm hết hạn hợp lệ trong tương lai.', true );
		}
		$expires_at = $expiry->getTimestamp();
	} elseif ( '' !== $input['access_token_expires'] ) {
		ec_zbs_admin_notice( 'Để trống thời điểm hết hạn khi không thay Access Token.', true );
	}

	$ciphers = array();
	foreach ( array( 'secret_key', 'refresh_token', 'access_token' ) as $field ) {
		if ( '' !== $input[ $field ] ) {
			$ciphers[ $field . '_cipher' ] = ec_zalo_encrypt( $input[ $field ] );
			if ( is_wp_error( $ciphers[ $field . '_cipher' ] ) ) {
				ec_zbs_admin_notice( 'Máy chủ chưa mã hóa được thông tin xác thực.', true );
			}
		}
	}

	$lock = ec_booking_lock( 'zbs-token' );
	if ( ! $lock ) {
		ec_zbs_admin_notice( 'Hệ thống đang làm mới token. Vui lòng thử lưu lại sau ít giây.', true );
	}
	$save_error = '';
	try {
		$raw = get_option( 'ec_booking_zbs', array() );
		$next = is_array( $raw ) ? $raw : array();
		$was_enabled = ! empty( $next['enabled'] );
		$was_reminder = ! empty( $next['reminder_enabled'] );
		$old_app_id = is_string( $next['app_id'] ?? null ) ? $next['app_id'] : '';
		$app_changed = $old_app_id !== $input['app_id'];
		if ( $app_changed && ( ! empty( $next['secret_key_cipher'] ) || ! empty( $next['refresh_token_cipher'] ) ) && ( '' === $input['secret_key'] || '' === $input['refresh_token'] ) ) {
			throw new RuntimeException( 'Khi đổi App ID, hãy nhập Secret Key và Refresh Token tương ứng.' );
		}
		if ( ( $app_changed || isset( $ciphers['refresh_token_cipher'] ) ) && '' === $input['access_token'] ) {
			unset( $next['access_token_cipher'] );
			$next['access_token_expires_at'] = 0;
		}
		$next['app_id']               = $input['app_id'];
		$next['template_confirm_id']  = $input['template_confirm_id'];
		$next['confirm_map']          = $confirm_map;
		$next['template_reminder_id'] = $input['template_reminder_id'];
		$next['reminder_map']         = $reminder_map;
		$next['reminder_enabled']     = $requested_reminder;
		$next['reminder_hours']       = (int) $input['reminder_hours'];
		foreach ( $ciphers as $key => $cipher ) {
			$next[ $key ] = $cipher;
		}
		if ( null !== $expires_at ) {
			$next['access_token_expires_at'] = $expires_at;
		}
		// A replaced refresh token restores a blocked OAuth chain; a new access token alone does not.
		if ( isset( $ciphers['refresh_token_cipher'] ) ) {
			$next['token_state'] = '';
		}
		$requested_enabled = isset( $_POST['enabled'] ) && '1' === $_POST['enabled'];
		$has_credentials   = ! empty( $next['app_id'] ) &&
			! empty( $next['secret_key_cipher'] ) && ec_zbs_safe_secret( ec_zalo_token( array( 'cipher' => $next['secret_key_cipher'] ) ) ) &&
			! empty( $next['refresh_token_cipher'] ) && ec_zbs_safe_secret( ec_zalo_token( array( 'cipher' => $next['refresh_token_cipher'] ) ) );
		$has_templates     = '' !== $next['template_confirm_id'] && $confirm_map;
		if ( $requested_enabled && ( ! $has_credentials || ! $has_templates ) ) {
			throw new RuntimeException( 'Chưa bật gửi tự động: cần App ID, Secret Key, Refresh Token, mã mẫu xác nhận và tham số mẫu.' );
		}
		if ( $requested_enabled && '657033' === $next['template_confirm_id'] && $confirm_map != array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' ) ) {
			throw new RuntimeException( 'Bốn tham số của mẫu xác nhận #657033 chưa khớp nội dung đã duyệt.' );
		}
		if ( $requested_enabled && in_array( $next['token_state'] ?? '', array( 'unknown', 'failed' ), true ) ) {
			throw new RuntimeException( 'Kết nối token ZBS đang lỗi hoặc chưa rõ kết quả. Hãy đối soát và nhập Refresh Token mới trước khi bật.' );
		}
		if ( $requested_enabled && get_option( 'ec_booking_zbs_token_unsafe', false ) && ! isset( $ciphers['refresh_token_cipher'] ) ) {
			throw new RuntimeException( 'Cặp token cũ chưa lưu an toàn. Hãy đối soát và nhập Refresh Token mới trước khi bật.' );
		}
		$next['enabled'] = $requested_enabled;
		update_option( 'ec_booking_zbs', $next, false );
		if ( get_option( 'ec_booking_zbs' ) !== $next ) {
			throw new RuntimeException( 'Chưa lưu được cấu hình. Vui lòng thử lại.' );
		}
		if ( $was_enabled !== $requested_enabled || $was_reminder !== $requested_reminder ) {
			// The public checkbox is visible only while ZBS is active.
			if ( function_exists( 'wp_cache_flush' ) ) { wp_cache_flush(); }
			do_action( 'litespeed_purge_all' );
		}
		if ( isset( $ciphers['refresh_token_cipher'] ) ) {
			update_option( 'ec_booking_zbs_token_unsafe', false, false );
			if ( get_option( 'ec_booking_zbs_token_unsafe', false ) ) {
				throw new RuntimeException( 'Chưa gỡ được khóa an toàn token. Vui lòng kiểm tra lưu trữ và thử lại.' );
			}
		}
	} catch ( RuntimeException $error ) {
		$save_error = $error->getMessage();
	} finally {
		ec_booking_unlock( $lock );
	}
	if ( '' !== $save_error ) {
		ec_zbs_admin_notice( $save_error, true );
	}
	do_action( 'ec_zbs_reminder_settings_changed' );
	ec_zbs_admin_notice( $requested_enabled ? 'Đã lưu và bật cấu hình ZBS cho lịch mới. Lưu cấu hình không gửi lại lịch cũ.' : 'Đã lưu cấu hình ZBS. Tự động gửi đang tắt.' );
}
add_action( 'admin_post_ec_zbs_settings', 'ec_zbs_admin_save' );

function ec_zbs_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Bạn không có quyền truy cập.', '', array( 'response' => 403 ) );
	}
	$s      = ec_zbs_settings();
	$notice = get_transient( 'ec_zbs_notice_' . get_current_user_id() );
	delete_transient( 'ec_zbs_notice_' . get_current_user_id() );
	$expiry = (int) ( $s['access_token_expires_at'] ?? 0 );
	$token_state = is_string( $s['token_state'] ?? null ) ? $s['token_state'] : '';
	?>
	<div class="wrap" style="max-width:1000px">
		<h1>Tin ZBS cho người đặt lịch</h1>
		<p>Gửi một tin xác nhận sau khi lịch được lưu và một tin nhắc trước giờ khám nếu được bật. Đây là kênh gửi cho số điện thoại của người đặt lịch; thông báo Zalo Bot cho nhân viên được cấu hình ở trang riêng.</p>
		<?php if ( is_array( $notice ) ) : ?>
			<div class="notice <?php echo ! empty( $notice['error'] ) ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice['message'] ?? '' ); ?></p></div>
		<?php endif; ?>
		<div class="card" style="max-width:none;padding:20px 28px">
			<h2>Trạng thái cấu hình</h2>
			<ul>
				<li>App ID: <strong><?php echo empty( $s['app_id'] ) ? 'Chưa lưu' : 'Đã lưu'; ?></strong></li>
				<li>Secret Key: <strong><?php echo empty( $s['secret_key_cipher'] ) ? 'Chưa lưu' : 'Đã lưu mã hóa'; ?></strong></li>
				<li>Refresh Token: <strong><?php echo empty( $s['refresh_token_cipher'] ) ? 'Chưa lưu' : 'Đã lưu mã hóa'; ?></strong></li>
				<li>Access Token: <strong><?php echo empty( $s['access_token_cipher'] ) ? 'Chưa lưu' : ( $expiry > time() ? 'Đã lưu, còn hạn theo giờ máy chủ' : 'Đã lưu, cần làm mới' ); ?></strong></li>
				<li>Tình trạng xác thực: <strong><?php echo esc_html( array( 'ok' => 'Lần làm mới gần nhất thành công', 'failed' => 'Lần làm mới gần nhất thất bại', 'unknown' => 'Kết quả làm mới token chưa rõ; cần đối soát', '' => 'Chưa kiểm tra kết nối' )[ $token_state ] ?? 'Chưa kiểm tra kết nối' ); ?></strong></li>
				<?php if ( get_option( 'ec_booking_zbs_token_unsafe', false ) ) : ?><li><strong>Đã khóa gửi:</strong> Không xác nhận được việc lưu cặp token mới. Cấp quyền lại OA và nhập Refresh Token mới để khôi phục.</li><?php endif; ?>
			</ul>
			<?php if ( $expiry ) : ?><p class="description">Access Token hết hạn theo giờ Việt Nam: <?php echo esc_html( wp_date( 'H:i · d/m/Y', $expiry, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) ); ?>.</p><?php endif; ?>
			<p class="description">Secret Key và token không hiển thị lại. Chỉ nhập thông tin xác thực do OA/App của bệnh viện cấp; mẫu tin phải được ZBS phê duyệt. Lưu cấu hình không gửi thử, không gửi lại lịch cũ và không chứng minh tin đã tới điện thoại.</p>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ec_zbs_settings">
			<?php wp_nonce_field( 'ec_zbs_settings', 'ec_zbs_nonce' ); ?>
			<div class="card" style="max-width:none;padding:20px 28px">
				<h2>1. Kết nối OA và App</h2>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="ec-zbs-app-id">App ID</label></th><td><input id="ec-zbs-app-id" name="app_id" type="text" inputmode="numeric" pattern="[0-9]+" maxlength="32" class="regular-text" value="<?php echo esc_attr( $s['app_id'] ?? '' ); ?>" autocomplete="off"></td></tr>
					<tr><th scope="row"><label for="ec-zbs-secret">Secret Key</label></th><td><input id="ec-zbs-secret" name="secret_key" type="password" class="regular-text" maxlength="4096" value="" autocomplete="new-password" placeholder="<?php echo empty( $s['secret_key_cipher'] ) ? 'Nhập Secret Key' : 'Để trống để giữ khóa đã lưu'; ?>"></td></tr>
					<tr><th scope="row"><label for="ec-zbs-refresh">Refresh Token ban đầu</label></th><td><input id="ec-zbs-refresh" name="refresh_token" type="password" class="large-text" maxlength="4096" value="" autocomplete="new-password" placeholder="<?php echo empty( $s['refresh_token_cipher'] ) ? 'Nhập Refresh Token' : 'Để trống để giữ token đã lưu'; ?>"><p class="description">Hệ thống tự thay Refresh Token khi làm mới. Chỉ nhập lại khi cấp quyền lại cho OA/App.</p></td></tr>
					<tr><th scope="row"><label for="ec-zbs-access">Access Token ban đầu</label></th><td><input id="ec-zbs-access" name="access_token" type="password" class="large-text" maxlength="4096" value="" autocomplete="new-password" placeholder="<?php echo empty( $s['access_token_cipher'] ) ? 'Có thể để trống nếu hệ thống sẽ làm mới từ Refresh Token' : 'Để trống để giữ token đã lưu'; ?>"></td></tr>
					<tr><th scope="row"><label for="ec-zbs-expires">Access Token hết hạn lúc</label></th><td><input id="ec-zbs-expires" name="access_token_expires" type="datetime-local" value=""><p class="description">Chỉ điền khi thay Access Token; theo giờ Việt Nam. Nếu không có thời điểm hết hạn, để trống cả Access Token để hệ thống tự lấy token mới từ Refresh Token.</p></td></tr>
				</table>
			</div>
			<div class="card" style="max-width:none;padding:20px 28px">
				<h2>2. Mẫu tin đã được ZBS duyệt</h2>
				<p class="description">Mẫu xác nhận đã duyệt #657033 dùng đúng bốn dòng: <code>schedule_time=schedule_time</code>, <code>address=address</code>, <code>customer_name=name</code>, <code>booking_code=appointment_id</code>. Mã lịch hẹn là ID lịch trong WordPress. Tên người đặt lịch quá 30 ký tự sẽ không được gửi tự động; nhân viên cần liên hệ trực tiếp.</p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="ec-zbs-confirm-id">Mã mẫu xác nhận</label></th><td><input id="ec-zbs-confirm-id" name="template_confirm_id" type="text" pattern="[A-Za-z0-9]+" class="regular-text" maxlength="64" value="<?php echo esc_attr( $s['template_confirm_id'] ?? '' ); ?>"></td></tr>
					<tr><th scope="row"><label for="ec-zbs-confirm-map">Tham số mẫu xác nhận</label></th><td><textarea id="ec-zbs-confirm-map" name="confirm_map" class="large-text code" rows="7" maxlength="6000" spellcheck="false" placeholder="schedule_time=schedule_time&#10;address=address&#10;customer_name=name&#10;booking_code=appointment_id"><?php echo esc_textarea( ec_zbs_admin_map_text( $s['confirm_map'] ?? array() ) ); ?></textarea><p class="description">Mỗi dòng: <code>ten_tham_so_trong_mau=ma_du_lieu</code>. Tên tham số phải trùng mẫu tin đã duyệt.</p></td></tr>
				</table>
				<h3>Nhắc lịch hẹn qua Zalo OA</h3>
				<p><label><input type="checkbox" name="reminder_enabled" value="1" <?php checked( ! empty( $s['reminder_enabled'] ) ); ?>> Bật một tin nhắc trước giờ khám cho lịch mới đã đồng ý nhận tin nhắc.</label></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="ec-zbs-reminder-hours">Nhắc trước giờ khám</label></th><td><input id="ec-zbs-reminder-hours" name="reminder_hours" type="number" min="1" max="72" step="1" class="small-text" required value="<?php echo esc_attr( $s['reminder_hours'] ?? 2 ); ?>"> giờ<p class="description">Từ 1 đến 72 giờ, mặc định 2 giờ. Khi lưu, các tin nhắc đang chờ được tính lại theo mốc mới; không gửi bù mốc đã qua. Lịch đặt sát hơn mốc nhắc chỉ nhận tin xác nhận.</p></td></tr>
					<tr><th scope="row"><label for="ec-zbs-reminder-id">Mã mẫu nhắc lịch đã duyệt</label></th><td><input id="ec-zbs-reminder-id" name="template_reminder_id" type="text" class="regular-text" maxlength="64" value="<?php echo esc_attr( $s['template_reminder_id'] ?: '659483' ); ?>"><p class="description">Mẫu #659483: Thông báo nhắc đến lịch hẹn.</p></td></tr>
					<tr><th scope="row"><label for="ec-zbs-reminder-map">Tham số mẫu nhắc lịch</label></th><td><textarea id="ec-zbs-reminder-map" name="reminder_map" class="large-text code" rows="5" maxlength="6000" spellcheck="false"><?php echo esc_textarea( ec_zbs_admin_map_text( $s['reminder_map'] ?: array( 'schedule_time' => 'schedule_time', 'address' => 'address', 'customer_name' => 'name', 'booking_code' => 'appointment_id' ) ) ); ?></textarea></td></tr>
				</table>
				<p class="description">Nhắc lịch dùng giờ Việt Nam, kiểm tra lại lịch trước khi gửi và bỏ qua lịch hủy/đã khám. Mỗi lịch chỉ có một tin nhắc; không gửi thêm sau kết quả chưa rõ. Các lịch cũ chỉ đồng ý một tin xác nhận không được tự thêm nhắc lịch.</p>
				<p>Chỉ dùng các mã dữ liệu sau:</p>
				<ul><?php foreach ( ec_zbs_admin_tokens() as $token => $label ) : ?><li><code><?php echo esc_html( $token ); ?></code> — <?php echo esc_html( $label ); ?></li><?php endforeach; ?></ul>
			</div>
			<div class="card" style="max-width:none;padding:20px 28px">
				<h2>3. Bật gửi tự động</h2>
				<p><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?>> Gửi tin xác nhận và nhắc lịch (nếu bật ở trên) cho lịch mới theo cấu hình.</label></p>
				<p class="description">Chỉ bật sau khi mẫu tin và kết nối OA/App đã được kiểm tra. Lịch cũ không được gửi hàng loạt khi bấm lưu.</p>
				<?php submit_button( 'Lưu cấu hình ZBS' ); ?>
			</div>
		</form>
	</div>
	<?php
}

function ec_zbs_admin_status_box( $post ) {
	if ( ! current_user_can( 'manage_options' ) || 'ec_appointment' !== $post->post_type ) {
		return;
	}
	$labels = array(
		'pending'   => 'Đang chờ xử lý',
		'scheduled' => 'Đã lên lịch gửi',
		'sending'   => 'Đang gửi; chưa rõ kết quả',
		'accepted'  => 'ZBS đã nhận yêu cầu; chưa xác nhận điện thoại đã nhận tin',
		'failed'    => 'ZBS chưa chấp nhận yêu cầu',
		'unknown'   => 'Kết quả chưa rõ; cần đối chiếu trước khi gửi lại',
		'skipped'   => 'Đã bỏ qua',
		'late'      => 'Quá muộn để gửi nhắc lịch',
		'disabled'  => 'Đã tắt tại thời điểm xử lý',
	);
	foreach ( array( 'confirm' => 'Tin xác nhận', 'reminder' => 'Tin nhắc lịch' ) as $kind => $heading ) {
		$state = get_post_meta( $post->ID, '_ec_zbs_' . $kind . '_state', true );
		$state = is_string( $state ) ? $state : '';
		$sent  = (int) get_post_meta( $post->ID, '_ec_zbs_' . $kind . '_sent_at', true );
		echo '<p><strong>' . esc_html( $heading ) . ':</strong> ' . esc_html( $labels[ $state ] ?? 'Chưa có thông tin gửi' ) . '</p>';
		if ( 'reminder' === $kind && 'pending' === $state ) {
			$due = (int) get_post_meta( $post->ID, '_ec_zbs_reminder_due_at', true );
			if ( $due ) { echo '<p class="description">Dự kiến nhắc lúc ' . esc_html( wp_date( 'H:i · d/m/Y', $due, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) ) . '.</p>'; }
		}
		if ( $sent > 0 ) {
			echo '<p class="description">ZBS chấp nhận lúc ' . esc_html( wp_date( 'H:i · d/m/Y', $sent, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) ) . '.</p>';
		}
		if ( 'failed' === $state ) {
			$error = get_post_meta( $post->ID, '_ec_zbs_' . $kind . '_error', true );
			if ( is_string( $error ) && '' !== $error ) { echo '<p class="description">' . esc_html( $error ) . '</p>'; }
		}
	}
	echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=ec_appointment&page=ec-zbs-settings' ) ) . '">Cài đặt ZBS</a></p>';
}

function ec_zbs_admin_register_status_box() {
	add_meta_box( 'ec-zbs-status', 'Tin ZBS cho người đặt lịch', 'ec_zbs_admin_status_box', 'ec_appointment', 'side' );
}
add_action( 'add_meta_boxes_ec_appointment', 'ec_zbs_admin_register_status_box' );
