<?php
/**
 * Quản lý danh mục và mức giá dịch vụ trong WordPress Admin.
 *
 * Mỗi dịch vụ là một bản ghi riêng để người quản trị có thể thêm, sửa, xoá,
 * đổi nhóm, đơn vị tính và mức giá mà không phải sửa mã nguồn giao diện.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'EYECARE_POST_TYPE_BANG_GIA' ) ) {
	define( 'EYECARE_POST_TYPE_BANG_GIA', 'eyecare_bang_gia' );
}

/**
 * Đăng ký loại nội dung bảng giá dùng riêng trong Admin.
 *
 * @return void
 */
function eyecare_bang_gia_dang_ky_post_type() {
	register_post_type(
		EYECARE_POST_TYPE_BANG_GIA,
		array(
			'labels' => array(
				'name'               => 'Bảng giá dịch vụ',
				'singular_name'      => 'Dịch vụ trong bảng giá',
				'menu_name'          => 'Bảng giá dịch vụ',
				'add_new'            => 'Thêm dịch vụ',
				'add_new_item'       => 'Thêm dịch vụ vào bảng giá',
				'edit_item'          => 'Sửa dịch vụ',
				'new_item'           => 'Dịch vụ mới',
				'view_item'          => 'Xem dịch vụ',
				'search_items'       => 'Tìm dịch vụ',
				'not_found'          => 'Chưa có dịch vụ nào.',
				'not_found_in_trash' => 'Không có dịch vụ trong thùng rác.',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'menu_position'       => 23,
			'menu_icon'           => 'dashicons-money-alt',
			'supports'            => array( 'title' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'show_in_rest'        => false,
		)
	);
}
add_action( 'init', 'eyecare_bang_gia_dang_ky_post_type' );

/**
 * Thêm hộp thông tin cho từng dịch vụ.
 *
 * @return void
 */
function eyecare_bang_gia_them_meta_box() {
	add_meta_box(
		'eyecare-bang-gia-chi-tiet',
		'Thông tin bảng giá',
		'eyecare_bang_gia_in_meta_box',
		EYECARE_POST_TYPE_BANG_GIA,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'eyecare_bang_gia_them_meta_box' );

/**
 * In hộp chỉnh sửa thông tin bảng giá.
 *
 * @param WP_Post $post Bản ghi hiện tại.
 * @return void
 */
function eyecare_bang_gia_in_meta_box( $post ) {
	$nhom         = (string) get_post_meta( $post->ID, '_eyecare_gia_nhom', true );
	$don_vi       = (string) get_post_meta( $post->ID, '_eyecare_gia_don_vi', true );
	$gia          = (string) get_post_meta( $post->ID, '_eyecare_gia_muc', true );
	$thu_tu_nhom  = (int) get_post_meta( $post->ID, '_eyecare_gia_nhom_thu_tu', true );
	$thu_tu       = (int) get_post_meta( $post->ID, '_eyecare_gia_thu_tu', true );

	wp_nonce_field( 'eyecare_bang_gia_luu', 'eyecare_bang_gia_nonce' );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="eyecare-gia-nhom">Nhóm dịch vụ</label></th>
			<td><input class="regular-text" id="eyecare-gia-nhom" name="eyecare_gia_nhom" type="text" maxlength="180" value="<?php echo esc_attr( $nhom ); ?>" required></td>
		</tr>
		<tr>
			<th scope="row"><label for="eyecare-gia-don-vi">Đơn vị tính</label></th>
			<td><input class="regular-text" id="eyecare-gia-don-vi" name="eyecare_gia_don_vi" type="text" maxlength="60" value="<?php echo esc_attr( $don_vi ); ?>" placeholder="Lần, mắt, gói…"></td>
		</tr>
		<tr>
			<th scope="row"><label for="eyecare-gia-muc">Mức giá</label></th>
			<td><input id="eyecare-gia-muc" name="eyecare_gia_muc" type="number" min="0" step="1000" value="<?php echo esc_attr( $gia ); ?>"> <span>đồng</span></td>
		</tr>
		<tr>
			<th scope="row"><label for="eyecare-gia-nhom-thu-tu">Thứ tự nhóm</label></th>
			<td><input id="eyecare-gia-nhom-thu-tu" name="eyecare_gia_nhom_thu_tu" type="number" min="1" max="999" value="<?php echo esc_attr( max( 1, $thu_tu_nhom ) ); ?>"></td>
		</tr>
		<tr>
			<th scope="row"><label for="eyecare-gia-thu-tu">Thứ tự dịch vụ</label></th>
			<td><input id="eyecare-gia-thu-tu" name="eyecare_gia_thu_tu" type="number" min="1" max="9999" value="<?php echo esc_attr( max( 1, $thu_tu ) ); ?>"></td>
		</tr>
	</table>
	<?php
}

/**
 * Lưu thông tin bảng giá, có kiểm tra quyền và nonce.
 *
 * @param int $post_id ID bản ghi.
 * @return void
 */
function eyecare_bang_gia_luu_meta( $post_id ) {
	if ( ! isset( $_POST['eyecare_bang_gia_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['eyecare_bang_gia_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'eyecare_bang_gia_luu' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( EYECARE_POST_TYPE_BANG_GIA !== get_post_type( $post_id ) ) {
		return;
	}

	$nhom = isset( $_POST['eyecare_gia_nhom'] )
		? sanitize_text_field( wp_unslash( $_POST['eyecare_gia_nhom'] ) )
		: '';
	$don_vi = isset( $_POST['eyecare_gia_don_vi'] )
		? sanitize_text_field( wp_unslash( $_POST['eyecare_gia_don_vi'] ) )
		: '';
	$gia = isset( $_POST['eyecare_gia_muc'] ) ? absint( wp_unslash( $_POST['eyecare_gia_muc'] ) ) : 0;
	$thu_tu_nhom = isset( $_POST['eyecare_gia_nhom_thu_tu'] ) ? max( 1, absint( wp_unslash( $_POST['eyecare_gia_nhom_thu_tu'] ) ) ) : 1;
	$thu_tu = isset( $_POST['eyecare_gia_thu_tu'] ) ? max( 1, absint( wp_unslash( $_POST['eyecare_gia_thu_tu'] ) ) ) : 1;

	update_post_meta( $post_id, '_eyecare_gia_nhom', $nhom );
	update_post_meta( $post_id, '_eyecare_gia_don_vi', $don_vi );
	update_post_meta( $post_id, '_eyecare_gia_muc', $gia );
	update_post_meta( $post_id, '_eyecare_gia_nhom_thu_tu', $thu_tu_nhom );
	update_post_meta( $post_id, '_eyecare_gia_thu_tu', $thu_tu );
}
add_action( 'save_post_' . EYECARE_POST_TYPE_BANG_GIA, 'eyecare_bang_gia_luu_meta' );

/**
 * Thêm trang cài đặt hiển thị dưới menu Bảng giá dịch vụ.
 *
 * @return void
 */
function eyecare_bang_gia_them_trang_cai_dat() {
	add_submenu_page(
		'edit.php?post_type=' . EYECARE_POST_TYPE_BANG_GIA,
		'Cài đặt bảng giá',
		'Cài đặt hiển thị',
		'manage_options',
		'eyecare-bang-gia-cai-dat',
		'eyecare_bang_gia_in_trang_cai_dat'
	);
}
add_action( 'admin_menu', 'eyecare_bang_gia_them_trang_cai_dat' );

/**
 * Xử lý và in trang cài đặt bảng giá.
 *
 * @return void
 */
function eyecare_bang_gia_in_trang_cai_dat() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'eyecare-child' ) );
	}

	if ( isset( $_POST['eyecare_bang_gia_cai_dat_nonce'] ) ) {
		check_admin_referer( 'eyecare_bang_gia_cai_dat_luu', 'eyecare_bang_gia_cai_dat_nonce' );

		$hien_gia = isset( $_POST['eyecare_bang_gia_hien_gia'] ) ? '1' : '0';
		$ghi_chu  = isset( $_POST['eyecare_bang_gia_ghi_chu'] )
			? sanitize_textarea_field( wp_unslash( $_POST['eyecare_bang_gia_ghi_chu'] ) )
			: '';

		update_option( 'eyecare_bang_gia_hien_gia', $hien_gia, false );
		update_option( 'eyecare_bang_gia_ghi_chu', $ghi_chu, false );

		echo '<div class="notice notice-success is-dismissible"><p>Đã lưu cài đặt bảng giá.</p></div>';
	}

	$hien_gia = '1' === get_option( 'eyecare_bang_gia_hien_gia', '0' );
	$ghi_chu  = (string) get_option( 'eyecare_bang_gia_ghi_chu', '' );
	?>
	<div class="wrap">
		<h1>Cài đặt hiển thị bảng giá</h1>
		<p>Các mức giá đã nhập vẫn được lưu trong Admin. Chỉ bật công tắc dưới đây sau khi bệnh viện xác nhận dữ liệu được phép công khai.</p>
		<form method="post">
			<?php wp_nonce_field( 'eyecare_bang_gia_cai_dat_luu', 'eyecare_bang_gia_cai_dat_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Hiển thị mức giá công khai</th>
					<td><label><input name="eyecare_bang_gia_hien_gia" type="checkbox" value="1" <?php checked( $hien_gia ); ?>> Hiện số tiền trên trang Bảng giá</label></td>
				</tr>
				<tr>
					<th scope="row"><label for="eyecare-bang-gia-ghi-chu">Ghi chú chung</label></th>
					<td><textarea class="large-text" id="eyecare-bang-gia-ghi-chu" name="eyecare_bang_gia_ghi_chu" rows="5"><?php echo esc_textarea( $ghi_chu ); ?></textarea></td>
				</tr>
			</table>
			<?php submit_button( 'Lưu cài đặt' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Các cột hữu ích trong danh sách quản trị.
 *
 * @param array $columns Cột mặc định.
 * @return array
 */
function eyecare_bang_gia_cot_quan_tri( $columns ) {
	return array(
		'cb'               => isset( $columns['cb'] ) ? $columns['cb'] : '<input type="checkbox">',
		'title'            => 'Tên dịch vụ',
		'eyecare_gia_nhom' => 'Nhóm',
		'eyecare_gia_dvt'  => 'Đơn vị',
		'eyecare_gia_muc'  => 'Mức giá',
		'date'             => 'Cập nhật',
	);
}
add_filter( 'manage_' . EYECARE_POST_TYPE_BANG_GIA . '_posts_columns', 'eyecare_bang_gia_cot_quan_tri' );

/**
 * In giá trị các cột quản trị.
 *
 * @param string $column  Tên cột.
 * @param int    $post_id ID dịch vụ.
 * @return void
 */
function eyecare_bang_gia_in_cot_quan_tri( $column, $post_id ) {
	if ( 'eyecare_gia_nhom' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_eyecare_gia_nhom', true ) );
	} elseif ( 'eyecare_gia_dvt' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_eyecare_gia_don_vi', true ) );
	} elseif ( 'eyecare_gia_muc' === $column ) {
		$gia = absint( get_post_meta( $post_id, '_eyecare_gia_muc', true ) );
		echo $gia > 0 ? esc_html( number_format_i18n( $gia ) . ' đ' ) : '—';
	}
}
add_action( 'manage_' . EYECARE_POST_TYPE_BANG_GIA . '_posts_custom_column', 'eyecare_bang_gia_in_cot_quan_tri', 10, 2 );

/**
 * Lấy toàn bộ dịch vụ đã công bố để trang bảng giá nhóm và hiển thị.
 *
 * @return WP_Post[]
 */
function eyecare_bang_gia_lay_danh_sach() {
	return get_posts(
		array(
			'post_type'      => EYECARE_POST_TYPE_BANG_GIA,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
}

