<?php
/**
 * Quản lý khối “Dịch vụ của chúng tôi” trên trang chủ.
 *
 * Mỗi thẻ là một bản ghi riêng để người quản trị có thể thêm, sửa, ẩn bằng
 * cách chuyển sang bản nháp, đổi ảnh đại diện, liên kết và thứ tự hiển thị.
 * Tiêu đề và lời dẫn của cả khối được lưu ở một trang thiết lập riêng.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const EYECARE_POST_TYPE_LINH_VUC = 'eyecare_linh_vuc';

/**
 * Nội dung mặc định dùng để tạo dữ liệu ban đầu.
 *
 * @return array<int,array<string,mixed>>
 */
function eyecare_linh_vuc_du_lieu_mac_dinh() {
	return array(
		array(
			'slug'        => 'kiem-soat-can-thi',
			'ten'         => 'Kiểm soát cận thị',
			'nhan'        => 'Khúc xạ',
			'mo_ta'       => 'Theo dõi tiến triển cận thị và tư vấn giải pháp phù hợp theo tuổi, thói quen sinh hoạt.',
			'icon'        => 'khuc-xa',
			'mau'         => 'forest',
			'link_paths'  => array( 'dich-vu/phau-thuat-khuc-xa', 'chuyen-khoa/tat-khuc-xa' ),
			'anh_mau'     => 'Do-khuc-xa-kinh-mat-810x640-1.png',
		),
		array(
			'slug'        => 'phau-thuat-khuc-xa',
			'ten'         => 'Phẫu thuật khúc xạ',
			'nhan'        => 'Khúc xạ',
			'mo_ta'       => 'Thăm khám và trao đổi lựa chọn can thiệp tật khúc xạ khi có chỉ định phù hợp.',
			'icon'        => 'khuc-xa',
			'mau'         => 'mint',
			'link_paths'  => array( 'dich-vu/phau-thuat-khuc-xa', 'chuyen-khoa/tat-khuc-xa' ),
			'anh_mau'     => 'Cover Do khuc xa - kinh mat.jpg',
		),
		array(
			'slug'        => 'phau-thuat-lao-thi',
			'ten'         => 'Phẫu thuật lão thị',
			'nhan'        => 'Phẫu thuật',
			'mo_ta'       => 'Đánh giá thị lực nhìn gần và xa để bác sĩ tư vấn hướng xử trí phù hợp với từng người.',
			'icon'        => 'thuy-tinh-the',
			'mau'         => 'forest',
			'link_paths'  => array( 'dich-vu/phau-thuat-khuc-xa', 'chuyen-khoa/mat-nguoi-cao-tuoi' ),
			'anh_mau'     => 'MAY-PHACO-OERHI-02-2.jpeg',
		),
		array(
			'slug'        => 'phau-thuat-duc-thuy-tinh-the',
			'ten'         => 'Phẫu thuật đục thủy tinh thể',
			'nhan'        => 'Phẫu thuật',
			'mo_ta'       => 'Khám đánh giá đục thủy tinh thể, tư vấn phương án điều trị và kế hoạch theo dõi sau khám.',
			'icon'        => 'thuy-tinh-the',
			'mau'         => 'mint',
			'link_paths'  => array( 'dich-vu/phau-thuat-phaco', 'chuyen-khoa/mat-nguoi-cao-tuoi' ),
			'anh_mau'     => 'trung-tam-phaco.jpg',
		),
		array(
			'slug'        => 'benh-ly-mat-tre-em',
			'ten'         => 'Điều trị bệnh lý mắt trẻ em',
			'nhan'        => 'Mắt trẻ em',
			'mo_ta'       => 'Khám lác, nhược thị, tật khúc xạ và các vấn đề thị giác thường gặp ở trẻ.',
			'icon'        => 'tre-em',
			'mau'         => 'forest',
			'link_paths'  => array( 'chuyen-khoa/mat-tre-em', 'hoi-dap/hoi-dap-mat-tre-em' ),
			'anh_mau'     => 'lacay_1.png',
		),
		array(
			'slug'        => 'benh-ly-mat-nguoi-cao-tuoi',
			'ten'         => 'Điều trị bệnh lý mắt người cao tuổi',
			'nhan'        => 'Mắt người cao tuổi',
			'mo_ta'       => 'Tầm soát các thay đổi thị lực theo tuổi và theo dõi những bệnh mắt cần chăm sóc lâu dài.',
			'icon'        => 'mat',
			'mau'         => 'mint',
			'link_paths'  => array( 'chuyen-khoa/mat-nguoi-cao-tuoi', 'chuyen-khoa/duc-thuy-tinh-the' ),
			'anh_mau'     => 'kham-mat-chuyen-sau-810x640-1.png',
		),
		array(
			'slug'        => 'dieu-tri-tang-nhan-ap',
			'ten'         => 'Điều trị tăng nhãn áp',
			'nhan'        => 'Glôcôm',
			'mo_ta'       => 'Đánh giá nhãn áp, thần kinh thị giác và lịch theo dõi để bảo vệ chức năng nhìn.',
			'icon'        => 'glocom',
			'mau'         => 'forest',
			'link_paths'  => array( 'chuyen-khoa/glocom', 'dich-vu/chan-doan-nhan-khoa' ),
			'anh_mau'     => 'MAY-CHUP-DAY-MAT-CRYSTAL-VUE-1.jpg',
		),
		array(
			'slug'        => 'dieu-tri-lac-le',
			'ten'         => 'Điều trị lác (lé)',
			'nhan'        => 'Mắt trẻ em',
			'mo_ta'       => 'Khám và đánh giá tình trạng lệch trục nhìn, phối hợp hai mắt và thị lực theo từng độ tuổi.',
			'icon'        => 'tre-em',
			'mau'         => 'mint',
			'link_paths'  => array( 'chuyen-khoa/mat-tre-em', 'dich-vu/phau-thuat-quem' ),
			'anh_mau'     => 'lacay_2.png',
		),
	);
}

/**
 * Các icon được phép chọn trong admin.
 *
 * @return array<string,string>
 */
function eyecare_linh_vuc_icon_options() {
	return array(
		'mat'           => 'Mắt tổng quát',
		'khuc-xa'       => 'Khúc xạ / kính',
		'thuy-tinh-the' => 'Thủy tinh thể',
		'glocom'        => 'Glôcôm / nhãn áp',
		'vong-mac'      => 'Võng mạc',
		'tre-em'        => 'Mắt trẻ em',
	);
}

/** Đăng ký post type quản trị các thẻ dịch vụ. */
function eyecare_dang_ky_post_type_linh_vuc() {
	register_post_type(
		EYECARE_POST_TYPE_LINH_VUC,
		array(
			'labels' => array(
				'name'               => 'Dịch vụ & lĩnh vực khám',
				'singular_name'      => 'Thẻ dịch vụ',
				'menu_name'          => 'Dịch vụ & lĩnh vực khám',
				'name_admin_bar'     => 'Thẻ dịch vụ',
				'add_new'            => 'Thêm thẻ',
				'add_new_item'       => 'Thêm thẻ dịch vụ',
				'edit_item'          => 'Sửa thẻ dịch vụ',
				'new_item'           => 'Thẻ dịch vụ mới',
				'all_items'          => 'Tất cả thẻ dịch vụ',
				'view_item'          => 'Xem thẻ',
				'search_items'       => 'Tìm thẻ dịch vụ',
				'not_found'          => 'Chưa có thẻ dịch vụ nào.',
				'featured_image'     => 'Ảnh thẻ dịch vụ',
				'set_featured_image' => 'Chọn hoặc tải ảnh thẻ',
				'remove_featured_image' => 'Gỡ ảnh thẻ',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_position'       => 22,
			'menu_icon'           => 'dashicons-screenoptions',
			'supports'            => array( 'title', 'thumbnail' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'eyecare_dang_ky_post_type_linh_vuc', 5 );

/** In hộp thông tin hiển thị của thẻ. */
function eyecare_in_hop_thong_tin_linh_vuc( $post ) {
	$mo_ta = get_post_meta( $post->ID, '_eyecare_linh_vuc_mo_ta', true );
	$nhan  = get_post_meta( $post->ID, '_eyecare_linh_vuc_nhan', true );
	$link  = get_post_meta( $post->ID, '_eyecare_linh_vuc_link', true );
	$icon  = get_post_meta( $post->ID, '_eyecare_linh_vuc_icon', true );
	$mau   = get_post_meta( $post->ID, '_eyecare_linh_vuc_mau', true );
	$thu_tu = get_post_meta( $post->ID, '_eyecare_linh_vuc_thu_tu', true );

	if ( '' === (string) $thu_tu ) {
		$thu_tu = max( 1, (int) $post->menu_order );
	}
	if ( ! isset( eyecare_linh_vuc_icon_options()[ $icon ] ) ) {
		$icon = 'mat';
	}
	if ( ! in_array( $mau, array( 'forest', 'mint', 'sage', 'blue' ), true ) ) {
		$mau = 'forest';
	}
	?>
	<?php wp_nonce_field( 'eyecare_luu_linh_vuc', 'eyecare_linh_vuc_nonce' ); ?>
	<style>
		.eyecare-admin-service-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 22px}
		.eyecare-admin-service-field label{display:block;margin:0 0 6px;font-weight:600}
		.eyecare-admin-service-field input,.eyecare-admin-service-field textarea,.eyecare-admin-service-field select{width:100%}
		.eyecare-admin-service-field--wide{grid-column:1/-1}.eyecare-admin-service-help{margin:6px 0 0;color:#646970;font-size:12px}
		@media(max-width:782px){.eyecare-admin-service-grid{grid-template-columns:1fr}.eyecare-admin-service-field--wide{grid-column:auto}}
	</style>
	<p><strong>Ảnh:</strong> dùng hộp “Ảnh thẻ dịch vụ” ở cột bên phải. Nên chọn ảnh ngang, chủ thể rõ, tối thiểu 900 × 650px.</p>
	<div class="eyecare-admin-service-grid">
		<div class="eyecare-admin-service-field">
			<label for="eyecare-linh-vuc-nhan">Nhãn nhỏ trên thẻ</label>
			<input id="eyecare-linh-vuc-nhan" name="eyecare_linh_vuc_nhan" type="text" value="<?php echo esc_attr( $nhan ); ?>" placeholder="Ví dụ: Khúc xạ">
		</div>
		<div class="eyecare-admin-service-field">
			<label for="eyecare-linh-vuc-icon">Icon dự phòng khi chưa có ảnh</label>
			<select id="eyecare-linh-vuc-icon" name="eyecare_linh_vuc_icon">
				<?php foreach ( eyecare_linh_vuc_icon_options() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="eyecare-admin-service-field eyecare-admin-service-field--wide">
			<label for="eyecare-linh-vuc-mo-ta">Mô tả ngắn</label>
			<textarea id="eyecare-linh-vuc-mo-ta" name="eyecare_linh_vuc_mo_ta" rows="3" placeholder="Một đến hai câu mô tả rõ dịch vụ."><?php echo esc_textarea( $mo_ta ); ?></textarea>
			<p class="eyecare-admin-service-help">Mô tả sẽ hiển thị trên thẻ; nên dài khoảng 90–150 ký tự để không làm thẻ quá cao.</p>
		</div>
		<div class="eyecare-admin-service-field eyecare-admin-service-field--wide">
			<label for="eyecare-linh-vuc-link">Liên kết khi bấm “Xem chi tiết”</label>
			<input id="eyecare-linh-vuc-link" name="eyecare_linh_vuc_link" type="text" value="<?php echo esc_attr( $link ); ?>" placeholder="https://… hoặc /chuyen-khoa/…">
			<p class="eyecare-admin-service-help">Có thể dùng URL nội bộ dạng /duong-dan/. Nếu để trống, thẻ sẽ dẫn về trang Chuyên khoa.</p>
		</div>
		<div class="eyecare-admin-service-field">
			<label for="eyecare-linh-vuc-mau">Tông màu thẻ</label>
			<select id="eyecare-linh-vuc-mau" name="eyecare_linh_vuc_mau">
				<option value="forest" <?php selected( $mau, 'forest' ); ?>>Xanh rừng</option>
				<option value="mint" <?php selected( $mau, 'mint' ); ?>>Xanh mint</option>
				<option value="sage" <?php selected( $mau, 'sage' ); ?>>Xanh sage</option>
				<option value="blue" <?php selected( $mau, 'blue' ); ?>>Xanh lam dịu</option>
			</select>
		</div>
		<div class="eyecare-admin-service-field">
			<label for="eyecare-linh-vuc-thu-tu">Thứ tự hiển thị</label>
			<input id="eyecare-linh-vuc-thu-tu" name="eyecare_linh_vuc_thu_tu" type="number" min="0" step="1" value="<?php echo esc_attr( (string) $thu_tu ); ?>">
			<p class="eyecare-admin-service-help">Số nhỏ sẽ đứng trước. Chuyển thẻ sang Bản nháp để ẩn khỏi trang chủ.</p>
		</div>
	</div>
	<?php
}

function eyecare_them_hop_thong_tin_linh_vuc() {
	add_meta_box( 'eyecare-thong-tin-linh-vuc', 'Nội dung hiển thị trên thẻ', 'eyecare_in_hop_thong_tin_linh_vuc', EYECARE_POST_TYPE_LINH_VUC, 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'eyecare_them_hop_thong_tin_linh_vuc' );

/** Lưu meta thẻ dịch vụ với nonce, quyền và sanitize đầy đủ. */
function eyecare_luu_thong_tin_linh_vuc( $post_id, $post ) {
	if ( ! isset( $_POST['eyecare_linh_vuc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eyecare_linh_vuc_nonce'] ) ), 'eyecare_luu_linh_vuc' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$mo_ta = isset( $_POST['eyecare_linh_vuc_mo_ta'] ) ? sanitize_textarea_field( wp_unslash( $_POST['eyecare_linh_vuc_mo_ta'] ) ) : '';
	$nhan  = isset( $_POST['eyecare_linh_vuc_nhan'] ) ? sanitize_text_field( wp_unslash( $_POST['eyecare_linh_vuc_nhan'] ) ) : '';
	$link  = isset( $_POST['eyecare_linh_vuc_link'] ) ? esc_url_raw( wp_unslash( $_POST['eyecare_linh_vuc_link'] ) ) : '';
	$icon  = isset( $_POST['eyecare_linh_vuc_icon'] ) ? sanitize_key( wp_unslash( $_POST['eyecare_linh_vuc_icon'] ) ) : 'mat';
	$mau   = isset( $_POST['eyecare_linh_vuc_mau'] ) ? sanitize_key( wp_unslash( $_POST['eyecare_linh_vuc_mau'] ) ) : 'forest';
	$thu_tu = isset( $_POST['eyecare_linh_vuc_thu_tu'] ) ? absint( wp_unslash( $_POST['eyecare_linh_vuc_thu_tu'] ) ) : 0;

	if ( ! isset( eyecare_linh_vuc_icon_options()[ $icon ] ) ) {
		$icon = 'mat';
	}
	if ( ! in_array( $mau, array( 'forest', 'mint', 'sage', 'blue' ), true ) ) {
		$mau = 'forest';
	}

	update_post_meta( $post_id, '_eyecare_linh_vuc_mo_ta', $mo_ta );
	update_post_meta( $post_id, '_eyecare_linh_vuc_nhan', $nhan );
	update_post_meta( $post_id, '_eyecare_linh_vuc_link', $link );
	update_post_meta( $post_id, '_eyecare_linh_vuc_icon', $icon );
	update_post_meta( $post_id, '_eyecare_linh_vuc_mau', $mau );
	update_post_meta( $post_id, '_eyecare_linh_vuc_thu_tu', $thu_tu );

	if ( (int) $post->menu_order !== $thu_tu ) {
		remove_action( 'save_post_' . EYECARE_POST_TYPE_LINH_VUC, 'eyecare_luu_thong_tin_linh_vuc', 10 );
		wp_update_post( array( 'ID' => $post_id, 'menu_order' => $thu_tu ) );
		add_action( 'save_post_' . EYECARE_POST_TYPE_LINH_VUC, 'eyecare_luu_thong_tin_linh_vuc', 10, 2 );
	}
}
add_action( 'save_post_' . EYECARE_POST_TYPE_LINH_VUC, 'eyecare_luu_thong_tin_linh_vuc', 10, 2 );

/** Tìm ảnh mẫu trong uploads để lần đầu khối có hình minh họa. */
function eyecare_linh_vuc_tim_anh_mau( $filename ) {
	$filename = basename( (string) $filename );
	if ( '' === $filename ) {
		return '';
	}

	$path = WP_CONTENT_DIR . '/uploads/' . $filename;
	if ( is_readable( $path ) ) {
		return content_url( 'uploads/' . rawurlencode( $filename ) );
	}

	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array( 'key' => '_wp_attached_file', 'value' => $filename, 'compare' => 'LIKE' ),
			),
		)
	);

	return ! empty( $attachments ) ? (string) wp_get_attachment_url( (int) $attachments[0] ) : '';
}

/** Tạo dữ liệu ban đầu một lần, không ghi đè thẻ đã được quản trị sửa. */
function eyecare_tao_linh_vuc_mau() {
	if ( get_option( 'eyecare_linh_vuc_da_tao_mau' ) ) {
		return;
	}

	$da_co = get_posts( array( 'post_type' => EYECARE_POST_TYPE_LINH_VUC, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( $da_co ) {
		update_option( 'eyecare_linh_vuc_da_tao_mau', 1, false );
		return;
	}

	foreach ( eyecare_linh_vuc_du_lieu_mac_dinh() as $index => $item ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => EYECARE_POST_TYPE_LINH_VUC,
				'post_status' => 'publish',
				'post_title'  => $item['ten'],
				'menu_order'  => $index + 1,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			continue;
		}

		update_post_meta( $post_id, '_eyecare_linh_vuc_mo_ta', $item['mo_ta'] );
		update_post_meta( $post_id, '_eyecare_linh_vuc_nhan', $item['nhan'] );
		update_post_meta( $post_id, '_eyecare_linh_vuc_icon', $item['icon'] );
		update_post_meta( $post_id, '_eyecare_linh_vuc_mau', $item['mau'] );
		update_post_meta( $post_id, '_eyecare_linh_vuc_thu_tu', $index + 1 );

		$link = '';
		foreach ( (array) $item['link_paths'] as $path ) {
			$page = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );
			if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
				$link = get_permalink( $page->ID );
				break;
			}
		}
		update_post_meta( $post_id, '_eyecare_linh_vuc_link', $link ? $link : home_url( '/chuyen-khoa/' ) );

		$anh_mau = eyecare_linh_vuc_tim_anh_mau( $item['anh_mau'] );
		if ( $anh_mau ) {
			update_post_meta( $post_id, '_eyecare_linh_vuc_anh_url', $anh_mau );
		}
	}

	update_option( 'eyecare_linh_vuc_da_tao_mau', 1, false );
}
add_action( 'init', 'eyecare_tao_linh_vuc_mau', 20 );

/** Sửa một liên kết mẫu cũ nếu nó từng rơi nhầm về trang mắt trẻ em. */
function eyecare_sua_lien_ket_linh_vuc_mau() {
	if ( get_option( 'eyecare_linh_vuc_lien_ket_mau_v2' ) ) {
		return;
	}

	$old_link = home_url( '/chuyen-khoa/mat-tre-em/' );
	$new_link = home_url( '/dich-vu/phau-thuat-khuc-xa/' );
	$posts = get_posts(
		array(
			'post_type'      => EYECARE_POST_TYPE_LINH_VUC,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( $posts as $post ) {
		if ( 'Kiểm soát cận thị' !== $post->post_title ) {
			continue;
		}
		$link = get_post_meta( $post->ID, '_eyecare_linh_vuc_link', true );
		if ( $old_link === $link ) {
			update_post_meta( $post->ID, '_eyecare_linh_vuc_link', $new_link );
		}
	}

	update_option( 'eyecare_linh_vuc_lien_ket_mau_v2', 1, false );
}
add_action( 'init', 'eyecare_sua_lien_ket_linh_vuc_mau', 21 );

/** Thiết lập mặc định của phần tiêu đề. */
function eyecare_linh_vuc_cau_hinh_mac_dinh() {
	return array(
		'nhan'   => 'Chuyên khoa mắt',
		'tieu_de' => 'Dịch vụ của chúng tôi',
		'mo_ta'  => 'Khám, chẩn đoán và điều trị các vấn đề về mắt với thông tin rõ ràng, dễ hiểu trước khi bạn đến bệnh viện.',
	);
}

function eyecare_linh_vuc_cau_hinh() {
	$saved = get_option( 'eyecare_linh_vuc_cau_hinh', array() );
	$saved = is_array( $saved ) ? $saved : array();
	return wp_parse_args( $saved, eyecare_linh_vuc_cau_hinh_mac_dinh() );
}

/** Đăng ký trang thiết lập khối dưới post type. */
function eyecare_linh_vuc_them_trang_cai_dat() {
	add_submenu_page( 'edit.php?post_type=' . EYECARE_POST_TYPE_LINH_VUC, 'Thiết lập khối trang chủ', 'Thiết lập khối', 'edit_theme_options', 'eyecare-linh-vuc-settings', 'eyecare_linh_vuc_in_trang_cai_dat' );
}
add_action( 'admin_menu', 'eyecare_linh_vuc_them_trang_cai_dat' );

function eyecare_linh_vuc_in_trang_cai_dat() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền sửa giao diện.', 'eyecare-child' ) );
	}

	$notice = '';
	if ( isset( $_POST['eyecare_linh_vuc_luu_cau_hinh'] ) ) {
		check_admin_referer( 'eyecare_linh_vuc_luu_cau_hinh', 'eyecare_linh_vuc_cau_hinh_nonce' );
		$settings = array(
			'nhan'    => isset( $_POST['eyecare_linh_vuc_nhan_khoi'] ) ? sanitize_text_field( wp_unslash( $_POST['eyecare_linh_vuc_nhan_khoi'] ) ) : '',
			'tieu_de' => isset( $_POST['eyecare_linh_vuc_tieu_de_khoi'] ) ? sanitize_text_field( wp_unslash( $_POST['eyecare_linh_vuc_tieu_de_khoi'] ) ) : '',
			'mo_ta'   => isset( $_POST['eyecare_linh_vuc_mo_ta_khoi'] ) ? sanitize_textarea_field( wp_unslash( $_POST['eyecare_linh_vuc_mo_ta_khoi'] ) ) : '',
		);
		update_option( 'eyecare_linh_vuc_cau_hinh', $settings, false );
		$notice = '<div class="notice notice-success is-dismissible"><p>Đã lưu thiết lập khối dịch vụ.</p></div>';
	}

	$settings = eyecare_linh_vuc_cau_hinh();
	?>
	<div class="wrap">
		<h1>Thiết lập khối dịch vụ trang chủ</h1>
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- notice do WordPress dựng. ?>
		<p>Chỉnh tiêu đề và lời dẫn của khối trên trang chủ. Muốn thêm, sửa, đổi ảnh hoặc đổi thứ tự từng thẻ, hãy vào mục <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . EYECARE_POST_TYPE_LINH_VUC ) ); ?>">Tất cả thẻ dịch vụ</a>.</p>
		<form method="post">
			<?php wp_nonce_field( 'eyecare_linh_vuc_luu_cau_hinh', 'eyecare_linh_vuc_cau_hinh_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="eyecare-linh-vuc-nhan-khoi">Nhãn nhỏ</label></th><td><input class="regular-text" id="eyecare-linh-vuc-nhan-khoi" name="eyecare_linh_vuc_nhan_khoi" value="<?php echo esc_attr( $settings['nhan'] ); ?>" /></td></tr>
				<tr><th scope="row"><label for="eyecare-linh-vuc-tieu-de-khoi">Tiêu đề</label></th><td><input class="regular-text" id="eyecare-linh-vuc-tieu-de-khoi" name="eyecare_linh_vuc_tieu_de_khoi" value="<?php echo esc_attr( $settings['tieu_de'] ); ?>" /></td></tr>
				<tr><th scope="row"><label for="eyecare-linh-vuc-mo-ta-khoi">Lời dẫn</label></th><td><textarea class="large-text" rows="3" id="eyecare-linh-vuc-mo-ta-khoi" name="eyecare_linh_vuc_mo_ta_khoi"><?php echo esc_textarea( $settings['mo_ta'] ); ?></textarea></td></tr>
			</table>
			<?php submit_button( 'Lưu thiết lập', 'primary', 'eyecare_linh_vuc_luu_cau_hinh' ); ?>
		</form>
	</div>
	<?php
}

/** Đọc danh sách thẻ đã publish để template dùng chung. */
function eyecare_linh_vuc_lay_ds( $limit = 12 ) {
	$posts = get_posts(
		array(
			'post_type'      => EYECARE_POST_TYPE_LINH_VUC,
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, absint( $limit ) ),
			'orderby'         => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'no_found_rows'  => true,
		)
	);

	$items = array();
	foreach ( $posts as $post ) {
		$image_id = get_post_thumbnail_id( $post->ID );
		$image = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : get_post_meta( $post->ID, '_eyecare_linh_vuc_anh_url', true );
		$icon  = get_post_meta( $post->ID, '_eyecare_linh_vuc_icon', true );
		$mau   = get_post_meta( $post->ID, '_eyecare_linh_vuc_mau', true );
		$link  = get_post_meta( $post->ID, '_eyecare_linh_vuc_link', true );
		$mo_ta = get_post_meta( $post->ID, '_eyecare_linh_vuc_mo_ta', true );
		$nhan  = get_post_meta( $post->ID, '_eyecare_linh_vuc_nhan', true );

		if ( ! isset( eyecare_linh_vuc_icon_options()[ $icon ] ) ) {
			$icon = 'mat';
		}
		if ( ! in_array( $mau, array( 'forest', 'mint', 'sage', 'blue' ), true ) ) {
			$mau = 'forest';
		}
		if ( '' === trim( $mo_ta ) ) {
			$mo_ta = 'Xem thông tin phạm vi dịch vụ và hướng thăm khám phù hợp.';
		}
		if ( '' === trim( $link ) ) {
			$link = home_url( '/chuyen-khoa/' );
		}

		$items[] = array(
			'id'    => (int) $post->ID,
			'ten'   => get_the_title( $post ),
			'nhan'  => $nhan ? $nhan : 'Chuyên khoa mắt',
			'mo_ta' => $mo_ta,
			'link'  => $link,
			'image' => $image,
			'image_id' => $image_id,
			'icon'  => $icon,
			'mau'   => $mau,
		);
	}

	return $items;
}

/** Các cột hữu ích trong danh sách quản trị. */
function eyecare_cot_danh_sach_linh_vuc( $columns ) {
	return array(
		'cb' => $columns['cb'],
		'eyecare_linh_vuc_image' => 'Ảnh',
		'title' => 'Tên thẻ',
		'eyecare_linh_vuc_label' => 'Nhãn',
		'eyecare_linh_vuc_order' => 'Thứ tự',
		'date' => $columns['date'],
	);
}
add_filter( 'manage_' . EYECARE_POST_TYPE_LINH_VUC . '_posts_columns', 'eyecare_cot_danh_sach_linh_vuc' );

function eyecare_noi_dung_cot_linh_vuc( $column, $post_id ) {
	if ( 'eyecare_linh_vuc_image' === $column ) {
		if ( has_post_thumbnail( $post_id ) ) {
			echo get_the_post_thumbnail( $post_id, array( 64, 48 ), array( 'style' => 'width:64px;height:48px;border-radius:8px;object-fit:cover' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<span aria-hidden="true">—</span>';
		}
	} elseif ( 'eyecare_linh_vuc_label' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_eyecare_linh_vuc_nhan', true ) );
	} elseif ( 'eyecare_linh_vuc_order' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_eyecare_linh_vuc_thu_tu', true ) );
	}
}
add_action( 'manage_' . EYECARE_POST_TYPE_LINH_VUC . '_posts_custom_column', 'eyecare_noi_dung_cot_linh_vuc', 10, 2 );
