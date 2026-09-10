<?php
/**
 * Tạo ảnh đại diện bài viết qua HHTech API.
 *
 * Chỉ gọi API sau khi quản trị viên bấm nút trong trang quản trị. API key
 * được đọc từ constant/env hoặc option đã lưu, không ghi trong mã nguồn.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Trả về cấu hình HHTech ảnh với giá tiết kiệm làm mặc định. */
function eyecare_hhtech_cau_hinh() {
	$gateway = get_option( 'eyecare_hhtech_gateway', 'https://hhtechapi.net' );
	$gateway = untrailingslashit( (string) $gateway );
	$gateway = preg_replace( '#/v1$#i', '', $gateway );

	return array(
		'gateway'    => $gateway ?: 'https://hhtechapi.net',
		'model'      => get_option( 'eyecare_hhtech_image_model', 'grok-imagine-image' ),
		'resolution' => get_option( 'eyecare_hhtech_image_resolution', '1k' ),
		'aspect'     => get_option( 'eyecare_hhtech_image_aspect', '16:9' ),
	);
}

/** Đọc key theo thứ tự env/constant rồi mới đến option quản trị. */
function eyecare_hhtech_api_key() {
	$key = '';
	if ( defined( 'HHTECH_API_KEY' ) ) {
		$key = (string) HHTECH_API_KEY;
	}
	if ( '' === trim( $key ) ) {
		$key = (string) getenv( 'HHTECH_API_KEY' );
	}
	if ( '' === trim( $key ) ) {
		$key = (string) getenv( 'ANTHROPIC_AUTH_TOKEN' );
	}
	if ( '' === trim( $key ) ) {
		$key = (string) get_option( 'eyecare_hhtech_api_key', '' );
	}

	return trim( $key );
}

/** Tạo prompt tiếng Việt gọn, không tốn thêm request văn bản. */
function eyecare_hhtech_prompt_bai_viet( $post_id ) {
	$post = get_post( absint( $post_id ) );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$categories = get_the_category( $post->ID );
	$category   = ! empty( $categories[0] ) ? $categories[0]->name : 'kiến thức nhãn khoa';
	$title      = wp_strip_all_tags( $post->post_title );
	$excerpt    = wp_strip_all_tags( strip_shortcodes( $post->post_excerpt ) );
	if ( '' === trim( $excerpt ) ) {
		$excerpt = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	}
	$excerpt = wp_trim_words( preg_replace( '/\s+/u', ' ', $excerpt ), 42, '…' );

	return sprintf(
		'Photorealistic editorial medical photograph for an ophthalmology hospital blog. Subject: %s, related to %s. Modern Vietnamese eye hospital, professional clinician or safe clinical equipment, soft natural light, refined green and white palette, reassuring, clean landscape composition for a featured image. Absolutely no text, no letters, no words, no typography, no logo, no watermark, no signage, no identifiable patient face, no graphic surgery, no exaggerated medical claims. Visual scene only.',
		sanitize_text_field( $title ),
		sanitize_text_field( $category )
	);
}

/** Lấy bytes ảnh từ phản hồi b64 hoặc URL của HHTech. */
function eyecare_hhtech_doc_bytes( $item ) {
	if ( ! is_array( $item ) ) {
		return new WP_Error( 'eyecare_hhtech_empty_image', 'API không trả dữ liệu ảnh.' );
	}

	if ( ! empty( $item['b64_json'] ) ) {
		$bytes = base64_decode( (string) $item['b64_json'], true );
		return false !== $bytes ? $bytes : new WP_Error( 'eyecare_hhtech_bad_base64', 'Dữ liệu ảnh từ API không hợp lệ.' );
	}

	$url = ! empty( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
	if ( '' === $url ) {
		return new WP_Error( 'eyecare_hhtech_no_url', 'API không trả URL ảnh.' );
	}

	if ( 0 === strpos( $url, 'data:image/' ) ) {
		$parts = explode( ',', $url, 2 );
		$bytes = isset( $parts[1] ) ? base64_decode( $parts[1], true ) : false;
		return false !== $bytes ? $bytes : new WP_Error( 'eyecare_hhtech_bad_data_url', 'Data URL ảnh không hợp lệ.' );
	}

	$remote = wp_safe_remote_get( $url, array( 'timeout' => 60, 'redirection' => 3 ) );
	if ( is_wp_error( $remote ) ) {
		return $remote;
	}
	if ( 200 !== (int) wp_remote_retrieve_response_code( $remote ) ) {
		return new WP_Error( 'eyecare_hhtech_download_failed', 'Không tải được ảnh từ URL API.' );
	}

	return wp_remote_retrieve_body( $remote );
}

/** Gọi endpoint /v1/images/generations và lưu ảnh thành attachment WordPress. */
function eyecare_hhtech_tao_anh( $post_id, $prompt, $overwrite = false ) {
	$post_id = absint( $post_id );
	$post    = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
		return new WP_Error( 'eyecare_hhtech_invalid_post', 'Bài viết không hợp lệ.' );
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return new WP_Error( 'eyecare_hhtech_forbidden', 'Bạn không có quyền sửa bài viết này.' );
	}
	if ( has_post_thumbnail( $post_id ) && ! $overwrite ) {
		return new WP_Error( 'eyecare_hhtech_existing_image', 'Bài viết đã có ảnh đại diện. Hãy bật tùy chọn thay ảnh hiện tại.' );
	}

	$key = eyecare_hhtech_api_key();
	if ( '' === $key ) {
		return new WP_Error( 'eyecare_hhtech_missing_key', 'Chưa cấu hình HHTech API key trong trang Ảnh AI.' );
	}
	$config = eyecare_hhtech_cau_hinh();
	$prompt = trim( sanitize_textarea_field( $prompt ) );
	if ( '' === $prompt ) {
		return new WP_Error( 'eyecare_hhtech_missing_prompt', 'Prompt ảnh không được để trống.' );
	}

	$body = array(
		'model'          => sanitize_text_field( $config['model'] ),
		'prompt'         => $prompt,
		'response_format'=> 'b64_json',
		'aspect_ratio'   => sanitize_text_field( $config['aspect'] ),
		'resolution'     => sanitize_text_field( $config['resolution'] ),
	);
	$response = wp_remote_post(
		trailingslashit( $config['gateway'] ) . 'v1/images/generations',
		array(
			'timeout' => 120,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json; charset=UTF-8',
			),
			'body'    => wp_json_encode( $body, JSON_UNESCAPED_UNICODE ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	$data   = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $status < 200 || $status >= 300 ) {
		$message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'HHTech API trả lỗi HTTP ' . $status . '.';
		return new WP_Error( 'eyecare_hhtech_api_error', sanitize_text_field( $message ) );
	}
	$item = ! empty( $data['data'][0] ) ? $data['data'][0] : array();
	$bytes = eyecare_hhtech_doc_bytes( $item );
	if ( is_wp_error( $bytes ) ) {
		return $bytes;
	}
	if ( '' === $bytes || strlen( $bytes ) > 15 * 1024 * 1024 ) {
		return new WP_Error( 'eyecare_hhtech_image_size', 'Ảnh trả về không hợp lệ hoặc vượt quá dung lượng cho phép.' );
	}
	$info = getimagesizefromstring( $bytes );
	$allowed = array( 'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp' );
	$mime = is_array( $info ) && ! empty( $info['mime'] ) ? strtolower( $info['mime'] ) : '';
	if ( ! isset( $allowed[ $mime ] ) ) {
		return new WP_Error( 'eyecare_hhtech_image_type', 'API không trả định dạng ảnh được hỗ trợ.' );
	}

	$filename = 'ai-' . $post_id . '-' . sanitize_title( $post->post_name ) . '-' . gmdate( 'YmdHis' ) . '.' . $allowed[ $mime ];
	$upload   = wp_upload_bits( $filename, null, $bytes );
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'eyecare_hhtech_upload_failed', $upload['error'] );
	}
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $mime,
			'post_title'     => 'Ảnh AI - ' . $post->post_title,
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$post_id,
		true
	);
	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $upload['file'] );
		return $attachment_id;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
	if ( $metadata ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $post->post_title ) );
	update_post_meta( $attachment_id, '_eyecare_hhtech_generated', 1 );
	update_post_meta( $attachment_id, '_eyecare_hhtech_model', sanitize_text_field( $config['model'] ) );
	update_post_meta( $attachment_id, '_eyecare_hhtech_prompt', $prompt );
	set_post_thumbnail( $post_id, $attachment_id );

	return (int) $attachment_id;
}

/** Đăng ký màn hình quản trị. */
function eyecare_hhtech_admin_menu() {
	add_submenu_page( 'edit.php', 'Tạo ảnh AI HHTech', 'Tạo ảnh AI', 'edit_others_posts', 'eyecare-hhtech-images', 'eyecare_hhtech_admin_page' );
}
add_action( 'admin_menu', 'eyecare_hhtech_admin_menu' );

/** Tạo prompt mặc định khi chọn một bài. */
function eyecare_hhtech_admin_page() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'eyecare-child' ) );
	}
	$config  = eyecare_hhtech_cau_hinh();
	$key     = eyecare_hhtech_api_key();
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$selected = $post_id ? get_post( $post_id ) : null;
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'DESC' ) );
	$notice = isset( $_GET['hhtech_notice'] ) ? sanitize_key( wp_unslash( $_GET['hhtech_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$error  = isset( $_GET['hhtech_error'] ) ? sanitize_text_field( wp_unslash( $_GET['hhtech_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="wrap">
		<h1>Tạo ảnh bài viết bằng HHTech API</h1>
		<p>Dùng model tiết kiệm <code><?php echo esc_html( $config['model'] ); ?></code>, ảnh <?php echo esc_html( $config['resolution'] ); ?> tỉ lệ <?php echo esc_html( $config['aspect'] ); ?>. API chỉ được gọi khi anh bấm nút tạo ảnh.</p>
		<?php if ( 'success' === $notice ) : ?><div class="notice notice-success is-dismissible"><p>Đã tạo và đặt ảnh đại diện cho bài viết.</p></div><?php endif; ?>
		<?php if ( $error ) : ?><div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div><?php endif; ?>
		<h2>Cấu hình kết nối</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="eyecare_hhtech_save_settings">
			<?php wp_nonce_field( 'eyecare_hhtech_save_settings' ); ?>
			<table class="form-table" role="presentation"><tbody>
				<tr><th><label for="eyecare_hhtech_api_key">API key</label></th><td><input type="password" class="regular-text" id="eyecare_hhtech_api_key" name="api_key" autocomplete="new-password" placeholder="<?php echo $key ? esc_attr( 'Đã cấu hình ' . substr( $key, 0, 4 ) . '***' ) : esc_attr( 'Dán key HHTech tại đây' ); ?>"><p class="description">Ưu tiên constant/env; nếu chưa có, key này được lưu trong option WordPress và chỉ hiển thị dạng che.</p><label><input type="checkbox" name="clear_api_key" value="1"> Xóa key đang lưu trong WordPress</label></td></tr>
				<tr><th><label for="eyecare_hhtech_gateway">Gateway</label></th><td><input type="url" class="regular-text" id="eyecare_hhtech_gateway" name="gateway" value="<?php echo esc_attr( $config['gateway'] ); ?>"></td></tr>
				<tr><th><label for="eyecare_hhtech_model">Model ảnh</label></th><td><select id="eyecare_hhtech_model" name="model"><option value="grok-imagine-image" <?php selected( $config['model'], 'grok-imagine-image' ); ?>>Grok Imagine Image 1K — tiết kiệm</option><option value="grok-imagine-image-quality" <?php selected( $config['model'], 'grok-imagine-image-quality' ); ?>>Grok Imagine Image Quality — nét hơn</option><option value="gemini-2.5-flash-image" <?php selected( $config['model'], 'gemini-2.5-flash-image' ); ?>>Gemini 2.5 Flash Image</option><option value="gemini-3-pro-image" <?php selected( $config['model'], 'gemini-3-pro-image' ); ?>>Gemini 3 Pro Image</option><option value="gpt-image-2" <?php selected( $config['model'], 'gpt-image-2' ); ?>>GPT Image 2</option></select></td></tr>
				<tr><th><label for="eyecare_hhtech_resolution">Độ phân giải</label></th><td><select id="eyecare_hhtech_resolution" name="resolution"><option value="1k" <?php selected( $config['resolution'], '1k' ); ?>>1K — tiết kiệm</option><option value="2k" <?php selected( $config['resolution'], '2k' ); ?>>2K — chi tiết hơn</option></select></td></tr>
				<tr><th><label for="eyecare_hhtech_aspect">Tỉ lệ</label></th><td><select id="eyecare_hhtech_aspect" name="aspect"><option value="16:9" <?php selected( $config['aspect'], '16:9' ); ?>>16:9 — ảnh bài viết</option><option value="4:3" <?php selected( $config['aspect'], '4:3' ); ?>>4:3</option><option value="1:1" <?php selected( $config['aspect'], '1:1' ); ?>>1:1</option></select></td></tr>
			</tbody></table>
			<?php submit_button( 'Lưu cấu hình HHTech' ); ?>
		</form>
		<hr><h2>Tạo ảnh cho bài viết</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="eyecare_hhtech_generate_image"><input type="hidden" name="return_url" value="<?php echo esc_url( admin_url( 'edit.php?page=eyecare-hhtech-images' ) ); ?>">
			<?php wp_nonce_field( 'eyecare_hhtech_generate_image' ); ?>
			<table class="form-table" role="presentation"><tbody><tr><th><label for="eyecare_hhtech_post_id">Bài viết</label></th><td><select class="regular-text" id="eyecare_hhtech_post_id" name="post_id" required><option value="">— Chọn bài viết —</option><?php foreach ( $posts as $post ) : ?><option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $selected instanceof WP_Post ? $selected->ID : 0, $post->ID ); ?>><?php echo esc_html( $post->post_title ); ?><?php echo has_post_thumbnail( $post->ID ) ? ' (đã có ảnh)' : ''; ?></option><?php endforeach; ?></select></td></tr>
			<tr><th><label for="eyecare_hhtech_prompt">Prompt ảnh</label></th><td><textarea class="large-text" rows="5" id="eyecare_hhtech_prompt" name="prompt" required><?php echo esc_textarea( $selected instanceof WP_Post ? eyecare_hhtech_prompt_bai_viet( $selected->ID ) : '' ); ?></textarea><p class="description">Có thể chỉnh prompt để đổi bối cảnh. Không đưa thông tin nhận diện bệnh nhân vào prompt.</p><label><input type="checkbox" name="overwrite" value="1"> Thay ảnh đại diện hiện tại</label></td></tr></tbody></table>
			<?php submit_button( 'Tạo ảnh và đặt làm ảnh đại diện', 'primary' ); ?>
		</form>
	</div>
	<script>(function(){var s=document.getElementById('eyecare_hhtech_post_id'),p=document.getElementById('eyecare_hhtech_prompt');if(!s||!p)return;s.addEventListener('change',function(){if(!s.value)return;fetch(ajaxurl+'?action=eyecare_hhtech_prompt&post_id='+encodeURIComponent(s.value)+'&nonce=<?php echo esc_js( wp_create_nonce( 'eyecare_hhtech_prompt' ) ); ?>',{credentials:'same-origin'}).then(function(r){return r.json()}).then(function(r){if(r.success)p.value=r.data.prompt;});});}());</script>
	<?php
}

/** Lưu cấu hình không làm lộ key trong thông báo/log. */
function eyecare_hhtech_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'eyecare-child' ) );
	}
	check_admin_referer( 'eyecare_hhtech_save_settings' );
	$gateway = isset( $_POST['gateway'] ) ? esc_url_raw( wp_unslash( $_POST['gateway'] ) ) : 'https://hhtechapi.net';
	$model   = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : 'grok-imagine-image';
	$models  = array( 'grok-imagine-image', 'grok-imagine-image-quality', 'gemini-2.5-flash-image', 'gemini-3-pro-image', 'gpt-image-2' );
	$res     = isset( $_POST['resolution'] ) ? sanitize_text_field( wp_unslash( $_POST['resolution'] ) ) : '1k';
	$aspect  = isset( $_POST['aspect'] ) ? sanitize_text_field( wp_unslash( $_POST['aspect'] ) ) : '16:9';
	if ( ! in_array( $model, $models, true ) ) { $model = 'grok-imagine-image'; }
	if ( ! in_array( $res, array( '1k', '2k' ), true ) ) { $res = '1k'; }
	if ( ! in_array( $aspect, array( '16:9', '4:3', '1:1' ), true ) ) { $aspect = '16:9'; }
	update_option( 'eyecare_hhtech_gateway', $gateway ?: 'https://hhtechapi.net', false );
	update_option( 'eyecare_hhtech_image_model', $model, false );
	update_option( 'eyecare_hhtech_image_resolution', $res, false );
	update_option( 'eyecare_hhtech_image_aspect', $aspect, false );
	if ( ! empty( $_POST['clear_api_key'] ) ) {
		delete_option( 'eyecare_hhtech_api_key' );
	} elseif ( ! empty( $_POST['api_key'] ) ) {
		update_option( 'eyecare_hhtech_api_key', sanitize_text_field( wp_unslash( $_POST['api_key'] ) ), false );
	}
	wp_safe_redirect( admin_url( 'edit.php?page=eyecare-hhtech-images&hhtech_notice=settings' ) );
	exit;
}
add_action( 'admin_post_eyecare_hhtech_save_settings', 'eyecare_hhtech_save_settings' );

/** Xử lý nút tạo ảnh, mỗi lần bấm là một request có chủ đích. */
function eyecare_hhtech_generate_image_action() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'eyecare-child' ) );
	}
	check_admin_referer( 'eyecare_hhtech_generate_image' );
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$prompt  = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
	$replace = ! empty( $_POST['overwrite'] );
	$result  = eyecare_hhtech_tao_anh( $post_id, $prompt, $replace );
	$url     = admin_url( 'edit.php?page=eyecare-hhtech-images&post_id=' . $post_id );
	if ( is_wp_error( $result ) ) {
		$url = add_query_arg( 'hhtech_error', rawurlencode( $result->get_error_message() ), $url );
	} else {
		$url = add_query_arg( 'hhtech_notice', 'success', $url );
	}
	wp_safe_redirect( $url );
	exit;
}
add_action( 'admin_post_eyecare_hhtech_generate_image', 'eyecare_hhtech_generate_image_action' );

/** Cập nhật prompt khi đổi bài viết trong màn hình công cụ. */
function eyecare_hhtech_prompt_ajax() {
	if ( ! current_user_can( 'edit_others_posts' ) ) { wp_send_json_error(); }
	check_ajax_referer( 'eyecare_hhtech_prompt', 'nonce' );
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	wp_send_json_success( array( 'prompt' => eyecare_hhtech_prompt_bai_viet( $post_id ) ) );
}
add_action( 'wp_ajax_eyecare_hhtech_prompt', 'eyecare_hhtech_prompt_ajax' );

/** Thêm đường dẫn nhanh ở mỗi bài trong Admin. */
function eyecare_hhtech_row_action( $actions, $post ) {
	if ( $post instanceof WP_Post && 'post' === $post->post_type && current_user_can( 'edit_others_posts' ) ) {
		$actions['eyecare_hhtech'] = '<a href="' . esc_url( admin_url( 'edit.php?page=eyecare-hhtech-images&post_id=' . $post->ID ) ) . '">Tạo ảnh AI</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'eyecare_hhtech_row_action', 10, 2 );
