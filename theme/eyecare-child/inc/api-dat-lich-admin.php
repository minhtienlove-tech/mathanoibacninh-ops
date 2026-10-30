<?php
/**
 * Quản trị đối tác API đặt lịch và form nhúng: tạo khóa, giới hạn domain, đổi khóa bí mật, tạm dừng.
 * Khóa bí mật chỉ hiện một lần ngay sau khi tạo hoặc đổi; sau đó chỉ còn bản mã hóa.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ec_booking_api_admin_url() {
	return admin_url( 'edit.php?post_type=ec_appointment&page=ec-booking-api' );
}

function ec_booking_api_admin_notice( $message, $error = false, $secret = null ) {
	$notice = array( 'message' => $message, 'error' => (bool) $error );
	if ( is_array( $secret ) ) {
		// Khóa vừa tạo giữ mã hóa trong 10 phút để hiển thị đúng một lần.
		$cipher = ec_zalo_encrypt( $secret['secret'] );
		if ( ! is_wp_error( $cipher ) ) {
			$notice['secret'] = array( 'id' => $secret['id'], 'cipher' => $cipher );
		}
	}
	set_transient( 'ec_booking_api_notice_' . get_current_user_id(), $notice, 10 * MINUTE_IN_SECONDS );
	wp_safe_redirect( ec_booking_api_admin_url() );
	exit;
}

function ec_booking_api_admin_menu() {
	add_submenu_page( 'edit.php?post_type=ec_appointment', 'API và form nhúng', 'API và form nhúng', 'manage_options', 'ec-booking-api', 'ec_booking_api_admin_page' );
}
add_action( 'admin_menu', 'ec_booking_api_admin_menu' );

/** Đọc các trường chung của biểu mẫu tạo/sửa đối tác. */
function ec_booking_api_admin_fields( $post ) {
	$name = isset( $post['name'] ) && is_string( $post['name'] ) ? trim( sanitize_text_field( wp_unslash( $post['name'] ) ) ) : '';
	if ( '' === $name || strlen( $name ) > 80 ) {
		return new WP_Error( 'name', 'Tên đối tác bắt buộc, tối đa 80 ký tự.' );
	}
	$limit = isset( $post['limit'] ) ? (int) $post['limit'] : 120;
	if ( $limit < 1 || $limit > 2000 ) {
		return new WP_Error( 'limit', 'Giới hạn mỗi giờ phải từ 1 đến 2000 lịch.' );
	}
	$origins_text = isset( $post['origins'] ) && is_string( $post['origins'] ) ? wp_unslash( $post['origins'] ) : '';
	return array(
		'name'        => $name,
		'channel'     => isset( $post['channel'] ) && 'Zalo' === $post['channel'] ? 'Zalo' : 'Website',
		'origins'     => ec_booking_api_clean_origins( $origins_text ),
		'limit'       => $limit,
		'allow_api'   => ! empty( $post['allow_api'] ),
		'allow_embed' => ! empty( $post['allow_embed'] ),
		'active'      => ! empty( $post['active'] ),
	);
}

function ec_booking_api_admin_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Bạn không có quyền cấu hình API.', '', array( 'response' => 403 ) );
	}
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( 'Phương thức không hợp lệ.', '', array( 'response' => 405 ) );
	}
	check_admin_referer( 'ec_booking_api', 'ec_booking_api_nonce' );
	$task    = isset( $_POST['task'] ) && is_string( $_POST['task'] ) ? $_POST['task'] : '';
	$id      = isset( $_POST['client'] ) && is_string( $_POST['client'] ) ? $_POST['client'] : '';
	$lock    = ec_booking_lock( 'booking-api-clients' );
	if ( ! $lock ) {
		ec_booking_api_admin_notice( 'Đang có thao tác khác. Vui lòng thử lại sau ít giây.', true );
	}
	$secret  = null;
	$message = '';
	try {
		$raw     = get_option( 'ec_booking_api_clients', array() );
		$clients = is_array( $raw ) ? $raw : array();
		if ( 'create' === $task ) {
			$fields = ec_booking_api_admin_fields( $_POST );
			if ( is_wp_error( $fields ) ) {
				throw new RuntimeException( $fields->get_error_message() );
			}
			if ( count( $clients ) >= 50 ) {
				throw new RuntimeException( 'Đã đạt tối đa 50 đối tác.' );
			}
			$credentials = ec_booking_api_new_credentials();
			$cipher      = ec_zalo_encrypt( $credentials['secret'] );
			if ( is_wp_error( $cipher ) ) {
				throw new RuntimeException( 'Máy chủ chưa mã hóa được khóa bí mật.' );
			}
			$clients[ $credentials['id'] ] = array_merge( $fields, array( 'public_key' => $credentials['public_key'], 'secret' => $cipher, 'created_at' => time() ) );
			$secret  = $credentials;
			$message = 'Đã tạo đối tác ' . $fields['name'] . '. Sao chép khóa bí mật ngay, khóa chỉ hiện một lần.';
		} elseif ( isset( $clients[ $id ] ) && 'update' === $task ) {
			$fields = ec_booking_api_admin_fields( $_POST );
			if ( is_wp_error( $fields ) ) {
				throw new RuntimeException( $fields->get_error_message() );
			}
			$clients[ $id ] = array_merge( $clients[ $id ], $fields );
			$message        = 'Đã lưu đối tác ' . $fields['name'] . '.';
		} elseif ( isset( $clients[ $id ] ) && 'rotate' === $task ) {
			$credentials = ec_booking_api_new_credentials();
			$cipher      = ec_zalo_encrypt( $credentials['secret'] );
			if ( is_wp_error( $cipher ) ) {
				throw new RuntimeException( 'Máy chủ chưa mã hóa được khóa bí mật.' );
			}
			$clients[ $id ]['secret'] = $cipher;
			$secret  = array( 'id' => $id, 'secret' => $credentials['secret'] );
			$message = 'Đã đổi khóa bí mật. Khóa cũ ngừng hoạt động ngay; cập nhật máy chủ đối tác bằng khóa mới.';
		} else {
			throw new RuntimeException( 'Thao tác hoặc đối tác không hợp lệ.' );
		}
		update_option( 'ec_booking_api_clients', $clients, false );
		if ( get_option( 'ec_booking_api_clients' ) !== $clients ) {
			throw new RuntimeException( 'Chưa lưu được cấu hình. Vui lòng thử lại.' );
		}
	} catch ( RuntimeException $error ) {
		$message = $error->getMessage();
		$secret  = false;
	} finally {
		ec_booking_unlock( $lock );
	}
	if ( false === $secret ) {
		ec_booking_api_admin_notice( $message, true );
	}
	ec_booking_api_admin_notice( $message, false, $secret );
}
add_action( 'admin_post_ec_booking_api', 'ec_booking_api_admin_save' );

/** Trường chung cho biểu mẫu tạo và sửa đối tác. */
function ec_booking_api_admin_form_fields( $client ) {
	$key = sanitize_html_class( $client['id'] ?? 'new' );
	?>
	<table class="form-table" role="presentation"><tbody>
		<tr><th scope="row"><label for="ec-api-name-<?php echo esc_attr( $key ); ?>">Tên đối tác</label></th><td><input class="regular-text" id="ec-api-name-<?php echo esc_attr( $key ); ?>" name="name" maxlength="80" required value="<?php echo esc_attr( $client['name'] ?? '' ); ?>" placeholder="Ví dụ: Zalo bot, Phòng khám vệ tinh"></td></tr>
		<tr><th scope="row"><label for="ec-api-channel-<?php echo esc_attr( $key ); ?>">Kênh ghi nhận</label></th><td><select id="ec-api-channel-<?php echo esc_attr( $key ); ?>" name="channel"><option value="Website">Website</option><option value="Zalo" <?php selected( $client['channel'] ?? '', 'Zalo' ); ?>>Zalo</option></select></td></tr>
		<tr><th scope="row"><label for="ec-api-origins-<?php echo esc_attr( $key ); ?>">Domain được nhúng form</label></th><td><textarea class="large-text code" rows="3" id="ec-api-origins-<?php echo esc_attr( $key ); ?>" name="origins" placeholder="https://doitac.vn&#10;https://*.doitac.vn"><?php echo esc_textarea( implode( "\n", $client['origins'] ?? array() ) ); ?></textarea><p class="description">Mỗi dòng một domain, bắt buộc https. Để trống thì form nhúng không hiển thị ở đâu.</p></td></tr>
		<tr><th scope="row"><label for="ec-api-limit-<?php echo esc_attr( $key ); ?>">Tối đa lịch mỗi giờ</label></th><td><input type="number" min="1" max="2000" id="ec-api-limit-<?php echo esc_attr( $key ); ?>" name="limit" value="<?php echo esc_attr( (string) ( $client['limit'] ?? 120 ) ); ?>"><p class="description">Áp cho API máy chủ. Form nhúng dùng giới hạn theo IP/thiết bị như website.</p></td></tr>
		<tr><th scope="row">Quyền</th><td>
			<label><input type="checkbox" name="allow_api" value="1" <?php checked( $client['allow_api'] ?? true ); ?>> API máy chủ</label><br>
			<label><input type="checkbox" name="allow_embed" value="1" <?php checked( $client['allow_embed'] ?? true ); ?>> Form nhúng</label><br>
			<label><input type="checkbox" name="active" value="1" <?php checked( $client['active'] ?? true ); ?>> Đang hoạt động</label>
		</td></tr>
	</tbody></table>
	<?php
}

function ec_booking_api_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Bạn không có quyền truy cập.', '', array( 'response' => 403 ) );
	}
	$notice = get_transient( 'ec_booking_api_notice_' . get_current_user_id() );
	delete_transient( 'ec_booking_api_notice_' . get_current_user_id() );
	$shown  = is_array( $notice ) && ! empty( $notice['secret']['cipher'] ) ? ec_zalo_token( array( 'cipher' => $notice['secret']['cipher'] ) ) : '';
	$base   = rest_url( 'ec-booking/v1' );
	$action = esc_url( admin_url( 'admin-post.php' ) );
	?>
	<div class="wrap" style="max-width:1000px">
		<h1>API đặt lịch và form nhúng</h1>
		<p>Cấp khóa cho hệ thống bên ngoài (Zalo bot, phần mềm đối tác) gửi lịch qua API, hoặc cho website đối tác nhúng form đặt lịch. Mọi lịch đi qua cùng kiểm tra, chống trùng và thông báo như form trên website. Tài liệu kỹ thuật: <code>docs/API-DAT-LICH.md</code>.</p>
		<?php if ( is_array( $notice ) ) : ?>
			<div class="notice <?php echo ! empty( $notice['error'] ) ? 'notice-error' : 'notice-success'; ?>"><p><?php echo esc_html( $notice['message'] ?? '' ); ?></p>
			<?php if ( '' !== $shown ) : ?>
				<p>Mã đối tác: <code><?php echo esc_html( $notice['secret']['id'] ); ?></code><br>Khóa bí mật: <code style="user-select:all"><?php echo esc_html( $shown ); ?></code></p>
			<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php foreach ( ec_booking_api_clients() as $client ) : ?>
			<div class="card" style="max-width:none;padding:12px 24px;margin-top:16px">
				<h2><?php echo esc_html( $client['name'] ); ?> <?php echo $client['active'] ? '' : '<span style="color:#b32d2e">(tạm dừng)</span>'; ?></h2>
				<p>Mã đối tác: <code><?php echo esc_html( $client['id'] ); ?></code> · Khóa công khai: <code><?php echo esc_html( $client['public_key'] ); ?></code></p>
				<?php if ( $client['allow_embed'] ) : ?>
					<p>Mã nhúng cho website đối tác:</p>
					<textarea class="large-text code" rows="2" readonly><?php echo esc_textarea( '<div data-ec-booking-key="' . $client['public_key'] . '"></div>' . "\n" . '<script src="' . get_stylesheet_directory_uri() . '/assets/dat-lich-nhung.js" async></script>' ); ?></textarea>
					<?php if ( ! $client['origins'] ) : ?><p style="color:#b32d2e">Chưa khai báo domain nên form nhúng chưa hiển thị ở đâu.</p><?php endif; ?>
				<?php endif; ?>
				<?php if ( $client['allow_api'] ) : ?>
					<p>API: <code><?php echo esc_html( $base ); ?></code></p>
				<?php endif; ?>
				<details><summary>Sửa đối tác</summary>
					<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
						<input type="hidden" name="action" value="ec_booking_api"><input type="hidden" name="task" value="update"><input type="hidden" name="client" value="<?php echo esc_attr( $client['id'] ); ?>">
						<?php wp_nonce_field( 'ec_booking_api', 'ec_booking_api_nonce' ); ?>
						<?php ec_booking_api_admin_form_fields( $client ); ?>
						<?php submit_button( 'Lưu đối tác', 'primary', 'submit', false ); ?>
					</form>
				</details>
				<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" style="margin-top:12px" onsubmit="return confirm('Khóa bí mật cũ sẽ ngừng hoạt động ngay. Tiếp tục?')">
					<input type="hidden" name="action" value="ec_booking_api"><input type="hidden" name="task" value="rotate"><input type="hidden" name="client" value="<?php echo esc_attr( $client['id'] ); ?>">
					<?php wp_nonce_field( 'ec_booking_api', 'ec_booking_api_nonce' ); ?>
					<?php submit_button( 'Đổi khóa bí mật', 'secondary', 'submit', false ); ?>
				</form>
			</div>
		<?php endforeach; ?>
		<div class="card" style="max-width:none;padding:12px 24px;margin-top:16px">
			<h2>Thêm đối tác</h2>
			<form method="post" action="<?php echo $action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
				<input type="hidden" name="action" value="ec_booking_api"><input type="hidden" name="task" value="create">
				<?php wp_nonce_field( 'ec_booking_api', 'ec_booking_api_nonce' ); ?>
				<?php ec_booking_api_admin_form_fields( array() ); ?>
				<?php submit_button( 'Tạo khóa', 'primary', 'submit', false ); ?>
			</form>
		</div>
	</div>
	<?php
}
