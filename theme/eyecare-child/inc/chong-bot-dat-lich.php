<?php
/**
 * Chống bot bằng Cloudflare Turnstile cho form đặt lịch và trang đăng nhập WordPress. Mặc định tắt;
 * bật tại Đặt lịch khám → Chống bot sau khi nhập Site Key và Secret Key. Mỗi nơi có nút bật và chế độ
 * hiện ô xác minh riêng. Trang đăng nhập dùng inc/chong-bot-dang-nhap.php.
 *
 * - Chỉ kiểm tra yêu cầu mới từ form website và form nhúng; API đối tác đã ký HMAC riêng.
 * - Token thiếu, sai, hết hạn hoặc dùng lại thì từ chối. Lỗi kết nối Cloudflare hoặc khóa bí mật sai
 *   thì vẫn nhận lịch (các lớp chống spam khác vẫn áp dụng) và ghi lỗi cho quản trị viên.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const EC_TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

function ec_turnstile_settings() {
	$raw = get_option( 'ec_booking_turnstile', array() );
	$raw = is_array( $raw ) ? $raw : array();
	$appearance = function ( $value ) {
		return 'always' === $value ? 'always' : 'interaction-only';
	};
	return array(
		'enabled'          => ! empty( $raw['enabled'] ),
		'site_key'         => is_string( $raw['site_key'] ?? null ) ? $raw['site_key'] : '',
		'secret_cipher'    => is_string( $raw['secret_cipher'] ?? null ) ? $raw['secret_cipher'] : '',
		'appearance'       => $appearance( $raw['appearance'] ?? '' ),
		'login'            => ! empty( $raw['login'] ),
		'login_appearance' => $appearance( $raw['login_appearance'] ?? '' ),
	);
}

/**
 * Chế độ hiện ô xác minh: 'interaction-only' = chỉ hiện khi Cloudflare nghi ngờ,
 * 'always' = luôn hiện ô "Tôi không phải người máy".
 */
function ec_turnstile_appearance( $context = 'booking' ) {
	$settings = ec_turnstile_settings();
	return 'login' === $context ? $settings['login_appearance'] : $settings['appearance'];
}

function ec_turnstile_valid_key( $key ) {
	return is_string( $key ) && (bool) preg_match( '/^[A-Za-z0-9_-]{10,100}$/D', $key );
}

function ec_turnstile_secret( $settings = null ) {
	$settings = $settings ?? ec_turnstile_settings();
	if ( '' === $settings['secret_cipher'] || ! function_exists( 'ec_zalo_token' ) ) {
		return '';
	}
	$secret = ec_zalo_token( array( 'cipher' => $settings['secret_cipher'] ) );
	return ec_turnstile_valid_key( $secret ) ? $secret : '';
}

/** Đủ Site Key và Secret Key hợp lệ. */
function ec_turnstile_ready( $settings = null ) {
	$settings = $settings ?? ec_turnstile_settings();
	return ec_turnstile_valid_key( $settings['site_key'] ) && '' !== ec_turnstile_secret( $settings );
}

/** Form đặt lịch: đang bật và đủ khóa hợp lệ. */
function ec_turnstile_active() {
	$settings = ec_turnstile_settings();
	return $settings['enabled'] && ec_turnstile_ready( $settings );
}

/** Trang đăng nhập WordPress: nút bật riêng, dùng chung khóa. */
function ec_turnstile_login_active() {
	$settings = ec_turnstile_settings();
	return $settings['login'] && ec_turnstile_ready( $settings );
}

/** Site key đưa vào form; rỗng khi tắt nên form không nạp Turnstile. */
function ec_turnstile_site_key() {
	return ec_turnstile_active() ? ec_turnstile_settings()['site_key'] : '';
}

/** Ghi lần gần nhất phải bỏ qua kiểm tra; cùng lỗi trong 10 phút chỉ ghi một lần. */
function ec_turnstile_record_bypass( $code ) {
	$last = get_option( 'ec_booking_turnstile_last_error', array() );
	if ( is_array( $last ) && ( $last['code'] ?? '' ) === $code && (int) ( $last['at'] ?? 0 ) > time() - 600 ) {
		return;
	}
	update_option( 'ec_booking_turnstile_last_error', array( 'code' => $code, 'at' => time() ), false );
}

function ec_turnstile_blocked_key( $context = 'booking' ) {
	return ( 'login' === $context ? 'ec_turnstile_login_blocked_' : 'ec_turnstile_blocked_' ) . ec_booking_now()->format( 'Ymd' );
}

/** Từ chối và đếm số lượt bị chặn trong ngày để hiện ở trang quản trị. */
function ec_turnstile_reject( $context = 'booking' ) {
	$key = ec_turnstile_blocked_key( $context );
	set_transient( $key, (int) get_transient( $key ) + 1, 2 * DAY_IN_SECONDS );
	if ( 'login' === $context ) {
		return new WP_Error( 'ec_login_captcha', '<strong>Lỗi:</strong> Chưa xác minh được bạn không phải chương trình tự động. Vui lòng chờ ô xác minh hoàn tất (hoặc bấm vào ô đó) rồi đăng nhập lại.' );
	}
	$hotline = function_exists( 'eyecare_du_lieu_thuc_the' ) ? ( eyecare_du_lieu_thuc_the()['dien_thoai_hien'] ?? '' ) : '';
	return new WP_Error( 'captcha', 'Chưa xác minh được bạn không phải chương trình tự động. Vui lòng hoàn tất ô xác minh phía trên nút gửi (nếu có) rồi bấm gửi lại, hoặc gọi ' . ( '' !== $hotline ? $hotline : 'bệnh viện' ) . '.', array( 'status' => 403 ) );
}

/** Form đặt lịch: true nếu đạt hoặc đang tắt; WP_Error 'captcha' nếu token thiếu, sai, hết hạn hoặc đã dùng. */
function ec_turnstile_check( $token ) {
	return ec_turnstile_active() ? ec_turnstile_verify( $token, 'booking' ) : true;
}

/**
 * Kiểm tra token với Cloudflare cho một nơi dùng ('booking' hoặc 'login'); token phải được tạo
 * cho đúng action đó. Người gọi tự kiểm tra nơi đó có đang bật hay không.
 */
function ec_turnstile_verify( $token, $context ) {
	if ( ! is_string( $token ) || ! preg_match( '/^[\x21-\x7E]{1,2048}$/D', $token ) ) {
		return ec_turnstile_reject( $context );
	}
	$body = array( 'secret' => ec_turnstile_secret(), 'response' => $token );
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
	if ( false !== filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		$body['remoteip'] = $ip;
	}
	$response = wp_remote_post( EC_TURNSTILE_VERIFY_URL, array( 'timeout' => 8, 'redirection' => 0, 'sslverify' => true, 'limit_response_size' => 8192, 'body' => $body ) );
	if ( is_wp_error( $response ) ) {
		ec_turnstile_record_bypass( 'network' );
		return true;
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( 200 !== $status || ! is_array( $data ) || ! array_key_exists( 'success', $data ) ) {
		ec_turnstile_record_bypass( 200 !== $status ? 'http_' . $status : 'bad_response' );
		return true;
	}
	if ( true === $data['success'] ) {
		// Token phải được tạo trên chính website này (kể cả form nhúng, vì iframe chạy trên domain bệnh viện).
		$host     = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$hostname = strtolower( (string) ( $data['hostname'] ?? '' ) );
		if ( '' !== $hostname && $hostname !== $host && $hostname !== 'www.' . $host ) {
			return ec_turnstile_reject( $context );
		}
		// Token của form đặt lịch không dùng được để đăng nhập và ngược lại.
		if ( ! empty( $data['action'] ) && $context !== $data['action'] ) {
			return ec_turnstile_reject( $context );
		}
		return true;
	}
	$errors = is_array( $data['error-codes'] ?? null ) ? $data['error-codes'] : array();
	foreach ( array( 'invalid-input-secret', 'missing-input-secret', 'internal-error' ) as $bypass ) {
		if ( in_array( $bypass, $errors, true ) ) {
			ec_turnstile_record_bypass( $bypass );
			return true;
		}
	}
	return ec_turnstile_reject( $context );
}

function ec_turnstile_admin_notice( $message, $error = false ) {
	set_transient( 'ec_turnstile_notice_' . get_current_user_id(), array( 'message' => $message, 'error' => (bool) $error ), MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'edit.php?post_type=ec_appointment&page=ec-booking-turnstile' ) );
	exit;
}

function ec_turnstile_admin_menu() {
	add_submenu_page( 'edit.php?post_type=ec_appointment', 'Chống bot form đặt lịch', 'Chống bot', 'manage_options', 'ec-booking-turnstile', 'ec_turnstile_admin_page' );
}
add_action( 'admin_menu', 'ec_turnstile_admin_menu' );

/** Secret Key để trống thì giữ khóa đã lưu; chỉ bật được khi đủ hai khóa. */
function ec_turnstile_admin_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Bạn không có quyền cấu hình chống bot.', '', array( 'response' => 403 ) );
	}
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( 'Phương thức không hợp lệ.', '', array( 'response' => 405 ) );
	}
	check_admin_referer( 'ec_booking_turnstile', 'ec_turnstile_nonce' );
	$site_key = isset( $_POST['site_key'] ) && is_string( $_POST['site_key'] ) ? trim( wp_unslash( $_POST['site_key'] ) ) : '';
	$secret   = isset( $_POST['secret_key'] ) && is_string( $_POST['secret_key'] ) ? trim( wp_unslash( $_POST['secret_key'] ) ) : '';
	$enabled  = isset( $_POST['enabled'] ) && '1' === $_POST['enabled'];
	$login    = isset( $_POST['login'] ) && '1' === $_POST['login'];
	$look     = function ( $field ) {
		return isset( $_POST[ $field ] ) && 'always' === $_POST[ $field ] ? 'always' : 'interaction-only';
	};
	if ( '' !== $site_key && ! ec_turnstile_valid_key( $site_key ) ) {
		ec_turnstile_admin_notice( 'Site Key không đúng định dạng. Sao chép lại từ Cloudflare.', true );
	}
	if ( '' !== $secret && ! ec_turnstile_valid_key( $secret ) ) {
		ec_turnstile_admin_notice( 'Secret Key không đúng định dạng. Sao chép lại từ Cloudflare.', true );
	}
	$before = ec_turnstile_settings();
	$next   = array( 'enabled' => $enabled, 'site_key' => $site_key, 'secret_cipher' => $before['secret_cipher'], 'appearance' => $look( 'appearance' ), 'login' => $login, 'login_appearance' => $look( 'login_appearance' ) );
	if ( '' !== $secret ) {
		$cipher = ec_zalo_encrypt( $secret );
		if ( is_wp_error( $cipher ) ) {
			ec_turnstile_admin_notice( 'Máy chủ chưa mã hóa được Secret Key.', true );
		}
		$next['secret_cipher'] = $cipher;
	}
	if ( ( $enabled || $login ) && ( '' === $site_key || '' === ec_turnstile_secret( $next ) ) ) {
		ec_turnstile_admin_notice( 'Chưa bật: cần nhập cả Site Key và Secret Key.', true );
	}
	update_option( 'ec_booking_turnstile', $next, false );
	if ( get_option( 'ec_booking_turnstile' ) !== $next ) {
		ec_turnstile_admin_notice( 'Chưa lưu được cấu hình. Vui lòng thử lại.', true );
	}
	if ( '' !== $secret ) {
		delete_option( 'ec_booking_turnstile_last_error' );
	}
	// Form được lưu bộ đệm cùng trang; bật/tắt, đổi chế độ hiện hoặc đổi Site Key phải làm mới trang.
	if ( $before['enabled'] !== $next['enabled'] || $before['site_key'] !== $next['site_key'] || $before['appearance'] !== $next['appearance'] ) {
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		do_action( 'litespeed_purge_all' );
	}
	$parts = array( 'Form đặt lịch: ' . ( $enabled ? 'bật' : 'tắt' ), 'Trang đăng nhập: ' . ( $login ? 'bật' : 'tắt' ) );
	ec_turnstile_admin_notice( 'Đã lưu. ' . implode( '; ', $parts ) . '.' . ( $login && ! $before['login'] ? ' Hãy mở trang đăng nhập trong cửa sổ ẩn danh và thử đăng nhập trước khi đăng xuất khỏi tài khoản này.' : '' ) );
}
add_action( 'admin_post_ec_booking_turnstile', 'ec_turnstile_admin_save' );

/** Hai lựa chọn hiện ô xác minh cho một nơi dùng. */
function ec_turnstile_admin_appearance( $field, $current ) {
	$options = array(
		'interaction-only' => 'Ẩn: chỉ hiện ô xác minh khi Cloudflare nghi ngờ (khuyên dùng)',
		'always'           => 'Hiện: luôn hiện ô "Tôi không phải người máy"',
	);
	echo '<fieldset style="margin-top:8px">';
	foreach ( $options as $value => $label ) {
		echo '<label style="display:block;margin:4px 0"><input type="radio" name="' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '" ' . checked( $current, $value, false ) . '> ' . esc_html( $label ) . '</label>';
	}
	echo '</fieldset>';
}

function ec_turnstile_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Bạn không có quyền truy cập.', '', array( 'response' => 403 ) );
	}
	$s      = ec_turnstile_settings();
	$notice = get_transient( 'ec_turnstile_notice_' . get_current_user_id() );
	delete_transient( 'ec_turnstile_notice_' . get_current_user_id() );
	$last   = get_option( 'ec_booking_turnstile_last_error', array() );
	$labels = array( 'network' => 'không kết nối được Cloudflare', 'bad_response' => 'Cloudflare trả dữ liệu lạ', 'invalid-input-secret' => 'Secret Key sai hoặc đã bị thu hồi', 'missing-input-secret' => 'thiếu Secret Key', 'internal-error' => 'lỗi nội bộ của Cloudflare' );
	$host   = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	?>
	<div class="wrap" style="max-width:900px">
		<h1>Chống bot</h1>
		<p>Dùng Cloudflare Turnstile để phân biệt người thật với chương trình tự động, cho form đặt lịch (trên website và form nhúng) và trang đăng nhập WordPress. Mỗi nơi bật riêng và chọn riêng cách hiện ô xác minh. API đối tác không bị ảnh hưởng.</p>
		<?php if ( is_array( $notice ) ) : ?>
			<div class="notice <?php echo ! empty( $notice['error'] ) ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice['message'] ?? '' ); ?></p></div>
		<?php endif; ?>
		<div class="card" style="max-width:none;padding:12px 24px">
			<h2>Trạng thái</h2>
			<ul>
				<li>Form đặt lịch: <strong><?php echo ec_turnstile_active() ? 'Đang bật' : 'Đang tắt'; ?></strong> · <?php echo 'always' === $s['appearance'] ? 'luôn hiện ô xác minh' : 'chỉ hiện khi nghi ngờ'; ?></li>
				<li>Trang đăng nhập: <strong><?php echo ec_turnstile_login_active() ? 'Đang bật' : 'Đang tắt'; ?></strong> · <?php echo 'always' === $s['login_appearance'] ? 'luôn hiện ô xác minh' : 'chỉ hiện khi nghi ngờ'; ?></li>
				<li>Site Key: <strong><?php echo '' === $s['site_key'] ? 'Chưa nhập' : esc_html( $s['site_key'] ); ?></strong></li>
				<li>Secret Key: <strong><?php echo '' === $s['secret_cipher'] ? 'Chưa nhập' : 'Đã lưu mã hóa'; ?></strong></li>
				<li>Lượt gửi form bị chặn hôm nay: <strong><?php echo esc_html( (string) (int) get_transient( ec_turnstile_blocked_key() ) ); ?></strong></li>
				<li>Lượt đăng nhập bị Turnstile chặn hôm nay: <strong><?php echo esc_html( (string) (int) get_transient( ec_turnstile_blocked_key( 'login' ) ) ); ?></strong></li>
				<?php if ( is_array( $last ) && ! empty( $last['code'] ) ) : ?>
					<li style="color:#b32d2e">Lần gần nhất phải bỏ qua xác minh Turnstile (vẫn nhận lịch/cho đăng nhập) vì <?php echo esc_html( $labels[ $last['code'] ] ?? $last['code'] ); ?>: <?php echo esc_html( wp_date( 'H:i d/m/Y', (int) $last['at'], new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
		<div class="card" style="max-width:none;padding:12px 24px;margin-top:16px">
			<h2>Lấy khóa</h2>
			<ol>
				<li>Đăng nhập Cloudflare → Turnstile → Add widget.</li>
				<li>Hostname: <code><?php echo esc_html( $host ); ?></code>. Widget mode: <strong>Managed</strong>.</li>
				<li>Sao chép Site Key và Secret Key vào ô bên dưới, chọn Bật rồi lưu.</li>
			</ol>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="card" style="max-width:none;padding:12px 24px;margin-top:16px">
			<input type="hidden" name="action" value="ec_booking_turnstile">
			<?php wp_nonce_field( 'ec_booking_turnstile', 'ec_turnstile_nonce' ); ?>
			<table class="form-table" role="presentation"><tbody>
				<tr><th scope="row"><label for="ec-turnstile-site">Site Key</label></th><td><input class="regular-text code" id="ec-turnstile-site" name="site_key" value="<?php echo esc_attr( $s['site_key'] ); ?>" autocomplete="off"></td></tr>
				<tr><th scope="row"><label for="ec-turnstile-secret">Secret Key</label></th><td><input class="regular-text code" id="ec-turnstile-secret" name="secret_key" type="password" value="" autocomplete="new-password" placeholder="<?php echo '' === $s['secret_cipher'] ? '' : 'Để trống để giữ khóa đã lưu'; ?>"></td></tr>
				<tr><th scope="row">Form đặt lịch</th><td>
					<label><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?>> Yêu cầu xác minh khi gửi form đặt lịch</label>
					<?php ec_turnstile_admin_appearance( 'appearance', $s['appearance'] ); ?>
				</td></tr>
				<tr><th scope="row">Trang đăng nhập</th><td>
					<label><input type="checkbox" name="login" value="1" <?php checked( $s['login'] ); ?>> Yêu cầu xác minh khi đăng nhập WordPress</label>
					<?php ec_turnstile_admin_appearance( 'login_appearance', $s['login_appearance'] ); ?>
					<p class="description">Sau khi bật, mở trang đăng nhập trong cửa sổ ẩn danh và thử đăng nhập trước khi đăng xuất khỏi tài khoản này. Nếu bị kẹt, tắt bằng WP-CLI: <code>wp eval 'update_option("ec_booking_turnstile", array_merge(get_option("ec_booking_turnstile"), array("login" =&gt; false)), false);'</code></p>
				</td></tr>
			</tbody></table>
			<?php submit_button( 'Lưu cấu hình' ); ?>
		</form>
		<?php if ( function_exists( 'ec_login_guard_admin_card' ) ) { ec_login_guard_admin_card(); } ?>
	</div>
	<?php
}
