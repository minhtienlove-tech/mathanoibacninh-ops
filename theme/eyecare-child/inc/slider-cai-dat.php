<?php
/**
 * SLIDER TRANG CHỦ — trang cài đặt chọn ảnh, lưu vào FILE trong theme.
 *
 * VÌ SAO LƯU VÀO FILE CHỨ KHÔNG DÙNG CUSTOMIZER / OPTIONS:
 * Bản localhost này ĐỒNG BỘ ĐỊNH KỲ từ nguồn khác. Mọi thứ ghi vào cơ sở dữ
 * liệu (theme_mod, options, bản ghi Customizer) sẽ bị ĐÈ MẤT sau lần đồng bộ
 * kế tiếp — người chọn ảnh xong hôm nay, mai mở lại thấy trắng. Tệp trong thư
 * mục theme KHÔNG nằm trong phạm vi đồng bộ (xem chú thích header.php), nên
 * cấu hình slider được ghi vào data/slider.json và sống sót.
 *
 * NGƯỜI DÙNG LÀM GÌ:
 *   Giao diện → Slider trang chủ → "Chọn ảnh" (mở Thư viện) → sắp thứ tự bằng
 *   cách kéo → chọn hiệu ứng → Lưu. Không cần biết code.
 *
 * 🔴 CỬA AN TOÀN GIỮ NGUYÊN TINH THẦN CŨ:
 * Trước đây "có alt" là dấu cho biết ảnh đã được người xem kiểm (số tổng đài
 * cũ, cụm so sánh cấm, logo đối thủ — xem inc/trang-chu.php). Nay việc CHỌN ảnh
 * trong trang này CHÍNH LÀ hành động kiểm đó: người chọn phải nhìn ảnh. Trang
 * vẫn cảnh báo nếu ảnh được chọn còn thiếu alt, nhưng không chặn — vì người đã
 * chủ động chọn.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Đường dẫn tệp lưu cấu hình slider (trong theme con, ngoài phạm vi đồng bộ DB).
 */
function eyecare_slider_tep_cau_hinh() {
	return get_stylesheet_directory() . '/data/slider.json';
}

/**
 * Cấu hình mặc định — dùng khi chưa có tệp hoặc tệp hỏng.
 */
function eyecare_slider_mac_dinh() {
	return array(
		'anh'       => array(),   // Mảng ID ảnh trong Thư viện, theo thứ tự hiển thị.
		'hieu_ung'  => 'mo',      // mo | truot | khong  (fade | slide | không hiệu ứng)
		'tu_chay'   => 1,         // 1 = tự chuyển ảnh; 0 = chỉ chuyển khi người dùng bấm.
		'nhip'      => 6,         // Giây giữa hai ảnh khi tự chạy (4–12).
		'kieu'      => 'tach',    // tach = chữ trên dải nền riêng phía trên ảnh; phu = chữ đè lên ảnh (điện ảnh hơn).
		'ken_burns' => 1,         // 1 = ảnh phóng/trôi chậm khi đang hiện (Ken Burns); 0 = ảnh tĩnh.
	);
}

/**
 * Đọc cấu hình slider từ tệp. Luôn trả về mảng đầy đủ khoá (đã trộn mặc định).
 */
function eyecare_slider_doc_cau_hinh() {
	$mac_dinh = eyecare_slider_mac_dinh();
	$tep      = eyecare_slider_tep_cau_hinh();

	if ( ! file_exists( $tep ) ) {
		return $mac_dinh;
	}

	$json = file_get_contents( $tep );
	$data = json_decode( $json, true );

	if ( ! is_array( $data ) ) {
		return $mac_dinh;
	}

	$ch = array_merge( $mac_dinh, $data );

	// Chuẩn hoá kiểu dữ liệu — tệp có thể bị sửa tay.
	$ch['anh']       = array_values( array_filter( array_map( 'absint', (array) $ch['anh'] ) ) );
	$ch['hieu_ung']  = in_array( $ch['hieu_ung'], array( 'mo', 'truot', 'khong' ), true ) ? $ch['hieu_ung'] : 'mo';
	$ch['tu_chay']   = empty( $ch['tu_chay'] ) ? 0 : 1;
	$ch['nhip']      = min( 12, max( 4, absint( $ch['nhip'] ) ) );
	$ch['kieu']      = in_array( $ch['kieu'], array( 'tach', 'phu' ), true ) ? $ch['kieu'] : 'tach';
	$ch['ken_burns'] = empty( $ch['ken_burns'] ) ? 0 : 1;

	return $ch;
}

/**
 * Ghi cấu hình slider ra tệp JSON. Trả về true/false.
 */
function eyecare_slider_ghi_cau_hinh( $ch ) {
	$thu_muc = dirname( eyecare_slider_tep_cau_hinh() );

	if ( ! is_dir( $thu_muc ) ) {
		wp_mkdir_p( $thu_muc );
	}

	$json = wp_json_encode( $ch, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	// LOCK_EX: tránh hai lần lưu cùng lúc ghi đè nhau.
	return false !== file_put_contents( eyecare_slider_tep_cau_hinh(), $json, LOCK_EX );
}

/* ==========================================================================
 * TRANG QUẢN TRỊ — Giao diện → Slider trang chủ
 * ========================================================================== */

/**
 * Thêm mục menu dưới "Giao diện".
 */
function eyecare_slider_them_menu() {
	add_theme_page(
		'Slider trang chủ',
		'Slider trang chủ',
		'edit_theme_options',
		'eyecare-slider',
		'eyecare_slider_trang_quan_tri'
	);
}
add_action( 'admin_menu', 'eyecare_slider_them_menu' );

/**
 * Nạp wp.media và mã chọn ảnh — chỉ trên trang cài đặt slider.
 */
function eyecare_slider_nap_media( $hook ) {
	if ( 'appearance_page_eyecare-slider' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	$tep_js = get_stylesheet_directory() . '/assets/slider-admin.js';
	wp_enqueue_script(
		'eyecare-slider-admin',
		get_stylesheet_directory_uri() . '/assets/slider-admin.js',
		array( 'jquery', 'jquery-ui-sortable' ),
		file_exists( $tep_js ) ? filemtime( $tep_js ) : '1.0',
		true
	);

	$tep_css = get_stylesheet_directory() . '/assets/slider-admin.css';
	wp_enqueue_style(
		'eyecare-slider-admin',
		get_stylesheet_directory_uri() . '/assets/slider-admin.css',
		array(),
		file_exists( $tep_css ) ? filemtime( $tep_css ) : '1.0'
	);
}
add_action( 'admin_enqueue_scripts', 'eyecare_slider_nap_media' );

/**
 * Xử lý lưu, rồi in trang cài đặt.
 */
function eyecare_slider_trang_quan_tri() {

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền sửa giao diện.', 'eyecare-child' ) );
	}

	$thong_bao = '';

	/* ---- Lưu ---------------------------------------------------------- */
	if ( isset( $_POST['eyecare_slider_luu'] ) ) {

		check_admin_referer( 'eyecare_slider_luu', 'eyecare_slider_nonce' );

		$anh_raw = isset( $_POST['eyecare_slider_anh'] ) ? sanitize_text_field( wp_unslash( $_POST['eyecare_slider_anh'] ) ) : '';
		$anh_ids = array_values( array_filter( array_map( 'absint', explode( ',', $anh_raw ) ) ) );

		$ch = array(
			'anh'       => $anh_ids,
			'hieu_ung'  => isset( $_POST['eyecare_slider_hieu_ung'] ) ? sanitize_key( wp_unslash( $_POST['eyecare_slider_hieu_ung'] ) ) : 'mo',
			'tu_chay'   => empty( $_POST['eyecare_slider_tu_chay'] ) ? 0 : 1,
			'nhip'      => isset( $_POST['eyecare_slider_nhip'] ) ? absint( wp_unslash( $_POST['eyecare_slider_nhip'] ) ) : 6,
			'kieu'      => isset( $_POST['eyecare_slider_kieu'] ) ? sanitize_key( wp_unslash( $_POST['eyecare_slider_kieu'] ) ) : 'tach',
			'ken_burns' => empty( $_POST['eyecare_slider_ken_burns'] ) ? 0 : 1,
		);

		// Chuẩn hoá qua bộ đọc để đồng nhất giới hạn.
		$ch['hieu_ung'] = in_array( $ch['hieu_ung'], array( 'mo', 'truot', 'khong' ), true ) ? $ch['hieu_ung'] : 'mo';
		$ch['nhip']     = min( 12, max( 4, $ch['nhip'] ) );
		$ch['kieu']     = in_array( $ch['kieu'], array( 'tach', 'phu' ), true ) ? $ch['kieu'] : 'tach';

		if ( eyecare_slider_ghi_cau_hinh( $ch ) ) {
			$thong_bao = '<div class="notice notice-success is-dismissible"><p>Đã lưu cấu hình slider vào tệp theme.</p></div>';
		} else {
			$thong_bao = '<div class="notice notice-error"><p><strong>Không ghi được tệp</strong> <code>data/slider.json</code>. Kiểm tra quyền ghi thư mục theme.</p></div>';
		}
	}

	$ch = eyecare_slider_doc_cau_hinh();
	?>
	<div class="wrap eyecare-slider-cai-dat">
		<h1>Slider trang chủ</h1>

		<?php echo $thong_bao; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML thông báo tự dựng ?>

		<p class="eyecare-slider-mo-ta">
			Chọn ảnh cho slider đầu trang chủ. Cấu hình lưu vào tệp
			<code>data/slider.json</code> trong theme — không lưu vào cơ sở dữ liệu,
			nên không bị mất khi đồng bộ.
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'eyecare_slider_luu', 'eyecare_slider_nonce' ); ?>

			<h2 class="title">Ảnh slider</h2>
			<p>
				<button type="button" class="button button-primary" id="eyecare-slider-chon">Chọn ảnh từ Thư viện</button>
				<button type="button" class="button" id="eyecare-slider-xoa-het">Bỏ tất cả</button>
			</p>
			<p class="description">Kéo–thả để đổi thứ tự. Ảnh đầu tiên là ảnh hiển thị khi trang vừa mở.</p>

			<ul id="eyecare-slider-ds" class="eyecare-slider-ds"></ul>

			<input type="hidden" name="eyecare_slider_anh" id="eyecare-slider-anh"
				value="<?php echo esc_attr( implode( ',', $ch['anh'] ) ); ?>">

			<h2 class="title">Bố cục và hiệu ứng</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="eyecare-slider-kieu">Kiểu hero</label></th>
					<td>
						<select name="eyecare_slider_kieu" id="eyecare-slider-kieu">
							<option value="tach" <?php selected( $ch['kieu'], 'tach' ); ?>>Tách đôi — chữ trên dải nền xanh phía trên ảnh</option>
							<option value="phu"  <?php selected( $ch['kieu'], 'phu' ); ?>>Chữ đè lên ảnh — kiểu điện ảnh (có lớp phủ tối để chữ đọc được)</option>
						</select>
						<p class="description">
							<strong>Tách đôi</strong>: tương phản chữ đo được chính xác, an toàn nhất.
							<strong>Chữ đè lên ảnh</strong>: trông sang hơn; một lớp phủ gradient tối
							được thêm tự động để chữ trắng luôn đọc rõ trên mọi ảnh.
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="eyecare-slider-hieu-ung">Kiểu hiệu ứng</label></th>
					<td>
						<select name="eyecare_slider_hieu_ung" id="eyecare-slider-hieu-ung">
							<option value="mo"    <?php selected( $ch['hieu_ung'], 'mo' ); ?>>Mờ dần (fade)</option>
							<option value="truot" <?php selected( $ch['hieu_ung'], 'truot' ); ?>>Trượt ngang (slide)</option>
							<option value="khong" <?php selected( $ch['hieu_ung'], 'khong' ); ?>>Không hiệu ứng</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">Ken Burns</th>
					<td>
						<label>
							<input type="checkbox" name="eyecare_slider_ken_burns" value="1" <?php checked( $ch['ken_burns'], 1 ); ?>>
							Ảnh phóng to và trôi rất chậm khi đang hiển thị
						</label>
						<p class="description">
							Hiệu ứng "điện ảnh" khiến ảnh tĩnh có chiều sâu. Chỉ dùng CSS, không tải
							thêm gì. Máy của người dùng đặt "giảm chuyển động" thì hiệu ứng tự tắt.
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Tự chuyển ảnh</th>
					<td>
						<label>
							<input type="checkbox" name="eyecare_slider_tu_chay" value="1" <?php checked( $ch['tu_chay'], 1 ); ?>>
							Tự động chuyển sang ảnh kế tiếp
						</label>
						<p class="description">
							Khi bật, slider có nút <strong>tạm dừng</strong> để người đọc giữ quyền
							điều khiển (WCAG 2.2.2). Máy của người dùng đặt "giảm chuyển động"
							thì slider tự tắt tự chạy.
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="eyecare-slider-nhip">Nhịp chuyển (giây)</label></th>
					<td>
						<input type="number" name="eyecare_slider_nhip" id="eyecare-slider-nhip"
							min="4" max="12" step="1" value="<?php echo esc_attr( $ch['nhip'] ); ?>" class="small-text">
						<p class="description">Từ 4 đến 12 giây. Chỉ có tác dụng khi bật tự chuyển.</p>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Lưu cấu hình', 'primary', 'eyecare_slider_luu' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Đưa dữ liệu ảnh đã chọn xuống JS (để dựng lại danh sách thumbnail khi mở trang).
 */
function eyecare_slider_du_lieu_cho_js() {
	$screen = get_current_screen();
	if ( ! $screen || 'appearance_page_eyecare-slider' !== $screen->id ) {
		return;
	}

	$ch  = eyecare_slider_doc_cau_hinh();
	$ds  = array();

	foreach ( $ch['anh'] as $id ) {
		$thumb = wp_get_attachment_image_src( $id, 'thumbnail' );
		if ( ! $thumb ) {
			continue; // Ảnh đã bị xoá khỏi Thư viện — bỏ qua.
		}
		$alt  = get_post_meta( $id, '_wp_attachment_image_alt', true );
		$meta = wp_get_attachment_metadata( $id );
		$ds[] = array(
			'id'    => $id,
			'thumb' => $thumb[0],
			'alt'   => $alt,
			'ten'   => get_the_title( $id ),
			'rong'  => ! empty( $meta['width'] ) ? (int) $meta['width'] : 0,
			'cao'   => ! empty( $meta['height'] ) ? (int) $meta['height'] : 0,
		);
	}

	printf(
		'<script>window.eyecareSliderData = %s;</script>',
		wp_json_encode( $ds )
	);
}
add_action( 'admin_footer', 'eyecare_slider_du_lieu_cho_js' );
