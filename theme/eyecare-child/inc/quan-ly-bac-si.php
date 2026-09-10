<?php
/**
 * Quản lý đội ngũ bác sĩ trong WordPress.
 *
 * Mỗi bác sĩ là một bản ghi riêng để người quản trị có thể thêm, sửa, đưa vào
 * thùng rác và thay ảnh bằng giao diện Ảnh đại diện có sẵn của WordPress.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Tên post type dùng cho đội ngũ bác sĩ. */
const EYECARE_POST_TYPE_BAC_SI = 'eyecare_bac_si';

/**
 * Đăng ký mục “Đội ngũ bác sĩ” trong trang quản trị.
 */
function eyecare_dang_ky_post_type_bac_si() {
	$labels = array(
		'name'                  => 'Đội ngũ bác sĩ',
		'singular_name'         => 'Bác sĩ',
		'menu_name'             => 'Đội ngũ bác sĩ',
		'name_admin_bar'        => 'Bác sĩ',
		'add_new'               => 'Thêm bác sĩ',
		'add_new_item'          => 'Thêm bác sĩ mới',
		'new_item'              => 'Bác sĩ mới',
		'edit_item'             => 'Sửa thông tin bác sĩ',
		'view_item'             => 'Xem bác sĩ',
		'all_items'             => 'Tất cả bác sĩ',
		'search_items'          => 'Tìm bác sĩ',
		'not_found'             => 'Chưa có bác sĩ nào.',
		'not_found_in_trash'    => 'Không có bác sĩ trong thùng rác.',
		'featured_image'        => 'Ảnh bác sĩ',
		'set_featured_image'    => 'Chọn hoặc tải ảnh bác sĩ',
		'remove_featured_image' => 'Xóa ảnh bác sĩ',
		'use_featured_image'    => 'Dùng ảnh này',
	);

	register_post_type(
		EYECARE_POST_TYPE_BAC_SI,
		array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'show_in_nav_menus'   => false,
			'menu_position'       => 21,
			'menu_icon'           => 'dashicons-businessperson',
			'supports'            => array( 'title', 'thumbnail' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'eyecare_dang_ky_post_type_bac_si', 5 );

/**
 * Thêm hộp nhập thông tin chuyên môn.
 */
function eyecare_them_hop_thong_tin_bac_si() {
	add_meta_box(
		'eyecare-thong-tin-bac-si',
		'Thông tin hiển thị trên trang chủ',
		'eyecare_in_hop_thong_tin_bac_si',
		EYECARE_POST_TYPE_BAC_SI,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'eyecare_them_hop_thong_tin_bac_si' );

/**
 * In giao diện hộp thông tin bác sĩ.
 *
 * @param WP_Post $post Bản ghi bác sĩ hiện tại.
 */
function eyecare_in_hop_thong_tin_bac_si( $post ) {
	wp_nonce_field( 'eyecare_luu_bac_si', 'eyecare_bac_si_nonce' );

	$hoc_vi     = get_post_meta( $post->ID, '_eyecare_hoc_vi', true );
	$chuc_danh  = get_post_meta( $post->ID, '_eyecare_chuc_danh', true );
	$badges     = get_post_meta( $post->ID, '_eyecare_badges', true );
	if ( empty( $badges ) ) {
		$badges = get_post_meta( $post->ID, '_eyecare_badge', true );
	}
	$highlights = get_post_meta( $post->ID, '_eyecare_highlights', true );
	$thu_tu     = get_post_meta( $post->ID, '_eyecare_thu_tu', true );
	$anh_mau    = get_post_meta( $post->ID, '_eyecare_anh_url', true );

	if ( is_array( $highlights ) ) {
		$highlights = implode( "\n", $highlights );
	}
	if ( is_array( $badges ) ) {
		$badges = implode( "\n", $badges );
	}

	if ( '' === (string) $thu_tu ) {
		$thu_tu = max( 1, (int) $post->menu_order );
	}
	?>
	<style>
		.eyecare-admin-doctor-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px 22px; }
		.eyecare-admin-doctor-field label { display:block; margin-bottom:6px; font-weight:600; }
		.eyecare-admin-doctor-field input,
		.eyecare-admin-doctor-field textarea { width:100%; }
		.eyecare-admin-doctor-field--wide { grid-column:1 / -1; }
		.eyecare-admin-doctor-help { margin:6px 0 0; color:#646970; font-size:12px; }
		@media (max-width:782px) { .eyecare-admin-doctor-grid { grid-template-columns:1fr; } .eyecare-admin-doctor-field--wide { grid-column:auto; } }
	</style>
	<p><strong>Cập nhật hình ảnh:</strong> dùng hộp “Ảnh bác sĩ” ở cột bên phải để chọn ảnh từ Thư viện hoặc tải ảnh mới lên.</p>
	<?php if ( ! has_post_thumbnail( $post->ID ) && $anh_mau ) : ?>
		<div style="display:flex;align-items:center;gap:12px;margin:12px 0 18px;padding:12px;background:#f6faf7;border:1px solid #dce9df;border-radius:8px">
			<img src="<?php echo esc_url( $anh_mau ); ?>" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover">
			<p style="margin:0">Đây là ảnh mẫu đang dùng ngoài trang chủ. Chọn “Ảnh bác sĩ” mới ở cột bên phải để thay thế.</p>
		</div>
	<?php endif; ?>
	<div class="eyecare-admin-doctor-grid">
		<div class="eyecare-admin-doctor-field">
			<label for="eyecare-hoc-vi">Học vị</label>
			<input id="eyecare-hoc-vi" name="eyecare_hoc_vi" type="text" value="<?php echo esc_attr( $hoc_vi ); ?>" placeholder="Ví dụ: BSCKI.">
		</div>
		<div class="eyecare-admin-doctor-field">
			<label for="eyecare-badges">Thông tin nổi trên ảnh</label>
			<textarea id="eyecare-badges" name="eyecare_badges" rows="4" placeholder="Mỗi dòng một thông tin, ví dụ:\n20+ năm\nHơn 10.000 ca"><?php echo esc_textarea( (string) $badges ); ?></textarea>
			<p class="eyecare-admin-doctor-help">Mỗi dòng là một badge. Các badge sẽ tự luân phiên theo thông tin của bác sĩ.</p>
		</div>
		<div class="eyecare-admin-doctor-field eyecare-admin-doctor-field--wide">
			<label for="eyecare-chuc-danh">Chức danh</label>
			<input id="eyecare-chuc-danh" name="eyecare_chuc_danh" type="text" value="<?php echo esc_attr( $chuc_danh ); ?>" placeholder="Ví dụ: GIÁM ĐỐC BỆNH VIỆN">
		</div>
		<div class="eyecare-admin-doctor-field eyecare-admin-doctor-field--wide">
			<label for="eyecare-highlights">Thành tích và chuyên môn</label>
			<textarea id="eyecare-highlights" name="eyecare_highlights" rows="9" placeholder="Mỗi thành tích nhập trên một dòng"><?php echo esc_textarea( (string) $highlights ); ?></textarea>
			<p class="eyecare-admin-doctor-help">Mỗi dòng là một dấu tích. Trang chủ hiển thị 3 dòng đầu, phần còn lại nằm trong nút “Xem thêm”.</p>
		</div>
		<div class="eyecare-admin-doctor-field">
			<label for="eyecare-thu-tu">Thứ tự hiển thị</label>
			<input id="eyecare-thu-tu" name="eyecare_thu_tu" type="number" min="0" step="1" value="<?php echo esc_attr( (string) $thu_tu ); ?>">
			<p class="eyecare-admin-doctor-help">Số nhỏ đứng trước. Ví dụ: 1, 2, 3.</p>
		</div>
	</div>
	<?php
}

/**
 * Lưu dữ liệu từ hộp thông tin bác sĩ.
 *
 * @param int     $post_id ID bác sĩ.
 * @param WP_Post $post    Bản ghi bác sĩ.
 */
function eyecare_luu_thong_tin_bac_si( $post_id, $post ) {
	if ( ! isset( $_POST['eyecare_bac_si_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eyecare_bac_si_nonce'] ) ), 'eyecare_luu_bac_si' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$truong_chu = array(
		'_eyecare_hoc_vi'    => 'eyecare_hoc_vi',
		'_eyecare_chuc_danh' => 'eyecare_chuc_danh',
	);

	foreach ( $truong_chu as $meta_key => $post_key ) {
		$value = isset( $_POST[ $post_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : '';
		update_post_meta( $post_id, $meta_key, $value );
	}

	$badges_raw = isset( $_POST['eyecare_badges'] )
		? sanitize_textarea_field( wp_unslash( $_POST['eyecare_badges'] ) )
		: '';
	$badges = preg_split( '/\R/u', $badges_raw );
	$badges = array_values( array_filter( array_map( 'trim', (array) $badges ) ) );
	update_post_meta( $post_id, '_eyecare_badges', $badges );
	update_post_meta( $post_id, '_eyecare_badge', ! empty( $badges ) ? $badges[0] : '' );

	$highlights_raw = isset( $_POST['eyecare_highlights'] )
		? sanitize_textarea_field( wp_unslash( $_POST['eyecare_highlights'] ) )
		: '';
	$highlights     = preg_split( '/\R/u', $highlights_raw );
	$highlights     = array_values( array_filter( array_map( 'trim', (array) $highlights ) ) );
	update_post_meta( $post_id, '_eyecare_highlights', $highlights );

	$thu_tu = isset( $_POST['eyecare_thu_tu'] ) ? absint( wp_unslash( $_POST['eyecare_thu_tu'] ) ) : 0;
	update_post_meta( $post_id, '_eyecare_thu_tu', $thu_tu );

	if ( (int) $post->menu_order !== $thu_tu ) {
		remove_action( 'save_post_' . EYECARE_POST_TYPE_BAC_SI, 'eyecare_luu_thong_tin_bac_si' );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'menu_order' => $thu_tu,
			)
		);
		add_action( 'save_post_' . EYECARE_POST_TYPE_BAC_SI, 'eyecare_luu_thong_tin_bac_si', 10, 2 );
	}
}
add_action( 'save_post_' . EYECARE_POST_TYPE_BAC_SI, 'eyecare_luu_thong_tin_bac_si', 10, 2 );

/**
 * Tạo các hồ sơ bác sĩ mẫu đúng dữ liệu đang hiển thị, chỉ một lần.
 */
function eyecare_tao_bac_si_mau() {
	if ( get_option( 'eyecare_doi_ngu_da_tao_mau' ) ) {
		eyecare_bo_sung_cach_cat_anh_mau();
		eyecare_bo_sung_badge_xoay_mau();
		return;
	}

	$da_co = get_posts(
		array(
			'post_type'      => EYECARE_POST_TYPE_BAC_SI,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( $da_co ) {
		update_option( 'eyecare_doi_ngu_da_tao_mau', 1, false );
		eyecare_bo_sung_badge_xoay_mau();
		return;
	}

	if ( ! function_exists( 'eyecare_du_lieu_doi_ngu_mac_dinh' ) ) {
		return;
	}

	$so_da_tao = 0;
	foreach ( eyecare_du_lieu_doi_ngu_mac_dinh() as $index => $bac_si ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => EYECARE_POST_TYPE_BAC_SI,
				'post_status' => 'publish',
				'post_title'  => $bac_si['ho_ten'],
				'menu_order'  => $index + 1,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			continue;
		}

		update_post_meta( $post_id, '_eyecare_hoc_vi', $bac_si['hoc_vi'] );
		update_post_meta( $post_id, '_eyecare_chuc_danh', $bac_si['chuc_danh'] );
		update_post_meta( $post_id, '_eyecare_badge', $bac_si['badge'] );
		update_post_meta( $post_id, '_eyecare_badges', ! empty( $bac_si['badges'] ) ? $bac_si['badges'] : array( $bac_si['badge'] ) );
		update_post_meta( $post_id, '_eyecare_highlights', $bac_si['highlights'] );
		update_post_meta( $post_id, '_eyecare_thu_tu', $index + 1 );
		update_post_meta( $post_id, '_eyecare_anh_url', $bac_si['anh_url'] );
		if ( ! empty( $bac_si['anh_can_chinh'] ) ) {
			update_post_meta( $post_id, '_eyecare_anh_can_chinh', $bac_si['anh_can_chinh'] );
		}
		++$so_da_tao;
	}

	if ( $so_da_tao > 0 ) {
		update_option( 'eyecare_doi_ngu_da_tao_mau', 1, false );
	}
}
add_action( 'init', 'eyecare_tao_bac_si_mau', 20 );

/**
 * Bổ sung thiết lập crop cho các bản ghi mẫu đã được tạo ở phiên bản trước.
 * Không ghi đè ảnh đại diện hoặc dữ liệu người quản trị đã sửa.
 */
function eyecare_bo_sung_cach_cat_anh_mau() {
	if ( ! function_exists( 'eyecare_du_lieu_doi_ngu_mac_dinh' ) ) {
		return;
	}

	$defaults = eyecare_du_lieu_doi_ngu_mac_dinh();
	$posts    = get_posts(
		array(
			'post_type'      => EYECARE_POST_TYPE_BAC_SI,
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);

	foreach ( $posts as $post ) {
		if ( get_post_meta( $post->ID, '_eyecare_anh_can_chinh', true ) ) {
			continue;
		}

		foreach ( $defaults as $default ) {
			if ( $post->post_title === $default['ho_ten'] && ! empty( $default['anh_can_chinh'] ) ) {
				update_post_meta( $post->ID, '_eyecare_anh_can_chinh', $default['anh_can_chinh'] );
				break;
			}
		}
	}
}

/**
 * Bổ sung danh sách badge luân phiên cho các bác sĩ mẫu đã được tạo từ phiên bản cũ.
 * Chỉ điền khi bản ghi chưa có trường mới, không ghi đè nội dung quản trị đã nhập.
 */
function eyecare_bo_sung_badge_xoay_mau() {
	if ( get_option( 'eyecare_badge_xoay_mau_v1' ) || ! function_exists( 'eyecare_du_lieu_doi_ngu_mac_dinh' ) ) {
		return;
	}

	$defaults = eyecare_du_lieu_doi_ngu_mac_dinh();
	$posts    = get_posts(
		array(
			'post_type'      => EYECARE_POST_TYPE_BAC_SI,
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);

	foreach ( $posts as $post ) {
		if ( get_post_meta( $post->ID, '_eyecare_badges', true ) ) {
			continue;
		}

		foreach ( $defaults as $default ) {
			if ( $post->post_title === $default['ho_ten'] && ! empty( $default['badges'] ) ) {
				update_post_meta( $post->ID, '_eyecare_badges', $default['badges'] );
				break;
			}
		}
	}

	update_option( 'eyecare_badge_xoay_mau_v1', 1, false );
}

/**
 * Bổ sung các hồ sơ bác sĩ đọc từ bộ poster mới (migration v4).
 *
 * Migration này chạy một lần, tạo các hồ sơ còn thiếu và chỉ điền phần dữ
 * liệu đang trống của hồ sơ cũ. Thành tích/badge mới được nối thêm, vì vậy
 * nội dung quản trị viên đã nhập không bị ghi đè.
 */
function eyecare_bo_sung_doi_ngu_poster_v4() {
	if ( get_option( 'eyecare_doi_ngu_poster_v4' ) || ! function_exists( 'eyecare_du_lieu_doi_ngu_mac_dinh' ) ) {
		return;
	}

	$defaults = eyecare_du_lieu_doi_ngu_mac_dinh();
	$posts    = get_posts(
		array(
			'post_type'      => EYECARE_POST_TYPE_BAC_SI,
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);
	$by_slug = array();

	foreach ( $posts as $post ) {
		$by_slug[ sanitize_title( $post->post_title ) ] = $post;
	}

	foreach ( $defaults as $index => $bac_si ) {
		$slug    = sanitize_title( $bac_si['ho_ten'] );
		$existing = isset( $by_slug[ $slug ] ) ? $by_slug[ $slug ] : null;

		if ( ! $existing ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => EYECARE_POST_TYPE_BAC_SI,
					'post_status' => 'publish',
					'post_title'  => $bac_si['ho_ten'],
					'menu_order'  => $index + 1,
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			$existing = get_post( $post_id );
			$by_slug[ $slug ] = $existing;
		}

		if ( ! $existing ) {
			continue;
		}

		$meta_if_empty = array(
			'_eyecare_hoc_vi'      => isset( $bac_si['hoc_vi'] ) ? $bac_si['hoc_vi'] : '',
			'_eyecare_chuc_danh'   => isset( $bac_si['chuc_danh'] ) ? $bac_si['chuc_danh'] : '',
			'_eyecare_anh_url'     => isset( $bac_si['anh_url'] ) ? $bac_si['anh_url'] : '',
			'_eyecare_anh_can_chinh' => isset( $bac_si['anh_can_chinh'] ) ? $bac_si['anh_can_chinh'] : '',
			'_eyecare_thu_tu'      => $index + 1,
		);

		foreach ( $meta_if_empty as $meta_key => $value ) {
			if ( '' === (string) get_post_meta( $existing->ID, $meta_key, true ) && '' !== (string) $value ) {
				update_post_meta( $existing->ID, $meta_key, $value );
			}
		}

		$old_badges = get_post_meta( $existing->ID, '_eyecare_badges', true );
		if ( is_string( $old_badges ) ) {
			$old_badges = preg_split( '/\R/u', $old_badges );
		}
		$old_badges = array_values( array_filter( array_map( 'trim', is_array( $old_badges ) ? $old_badges : array() ) ) );
		if ( empty( $old_badges ) ) {
			$old_badge = trim( (string) get_post_meta( $existing->ID, '_eyecare_badge', true ) );
			$old_badges = '' !== $old_badge ? array( $old_badge ) : array();
		}
		$new_badges = isset( $bac_si['badges'] ) && is_array( $bac_si['badges'] ) ? $bac_si['badges'] : array();
		$merged_badges = array_values( array_unique( array_merge( $old_badges, array_filter( array_map( 'trim', $new_badges ) ) ) ) );
		if ( ! empty( $merged_badges ) ) {
			update_post_meta( $existing->ID, '_eyecare_badges', $merged_badges );
			update_post_meta( $existing->ID, '_eyecare_badge', $merged_badges[0] );
		}

		$old_highlights = get_post_meta( $existing->ID, '_eyecare_highlights', true );
		if ( is_string( $old_highlights ) ) {
			$old_highlights = preg_split( '/\R/u', $old_highlights );
		}
		$old_highlights = array_values( array_filter( array_map( 'trim', is_array( $old_highlights ) ? $old_highlights : array() ) ) );
		$new_highlights = isset( $bac_si['highlights'] ) && is_array( $bac_si['highlights'] ) ? $bac_si['highlights'] : array();
		$legacy_highlights = array(
			'Chuyên sâu dịch kính - võng mạc, kiểm soát cận thị' => 'Có nhiều năm kinh nghiệm điều trị bệnh dịch kính - võng mạc, kiểm soát cận thị, giác mạc',
			'Đào tạo phẫu thuật tại HN, Lào, Bắc Ninh, Thái Nguyên' => 'Đào tạo phẫu thuật tại Hà Nội, Viên Chăn (Lào), Bắc Ninh, Thái Nguyên, Thanh Hóa',
			'Nguyên Phó GĐ chuyên môn BV Mắt Thanh An' => 'Phó giám đốc phụ trách chuyên môn tại BV Mắt Thanh An, BV Mắt Bắc Trung Nam, BV Mắt Sông Cầu và hệ thống BV Mắt Hà Nội',
			'Chuyên mộng, quặm và thẩm mỹ' => 'Chuyên phẫu thuật mộng, quặm và thẩm mỹ mắt',
			'Thành viên Hội Nhãn khoa VN' => 'Thành viên Hội Nhãn khoa Việt Nam',
		);
		$old_highlights = array_map(
			function ( $highlight ) use ( $legacy_highlights ) {
				return isset( $legacy_highlights[ $highlight ] ) ? $legacy_highlights[ $highlight ] : $highlight;
			},
			$old_highlights
		);
		$merged_highlights = array_values( array_unique( array_merge( $old_highlights, array_filter( array_map( 'trim', $new_highlights ) ) ) ) );
		if ( ! empty( $merged_highlights ) ) {
			update_post_meta( $existing->ID, '_eyecare_highlights', $merged_highlights );
		}
	}

	update_option( 'eyecare_doi_ngu_poster_v4', 1, false );
}
add_action( 'init', 'eyecare_bo_sung_doi_ngu_poster_v4', 21 );

/**
 * Đổi gợi ý ô tiêu đề thành họ tên bác sĩ.
 *
 * @param string $title Gợi ý mặc định.
 * @param WP_Post $post Bản ghi hiện tại.
 * @return string
 */
function eyecare_goi_y_ten_bac_si( $title, $post ) {
	if ( $post && EYECARE_POST_TYPE_BAC_SI === $post->post_type ) {
		return 'Nhập họ tên bác sĩ';
	}
	return $title;
}
add_filter( 'enter_title_here', 'eyecare_goi_y_ten_bac_si', 10, 2 );

/**
 * Các cột gọn, dễ kiểm tra ở màn hình danh sách.
 *
 * @param array $columns Cột mặc định.
 * @return array
 */
function eyecare_cot_danh_sach_bac_si( $columns ) {
	return array(
		'cb'                   => $columns['cb'],
		'eyecare_doctor_image' => 'Ảnh',
		'title'                => 'Họ tên bác sĩ',
		'eyecare_doctor_role'  => 'Chức danh',
		'eyecare_doctor_badge' => 'Badge',
		'eyecare_doctor_order' => 'Thứ tự',
		'date'                 => $columns['date'],
	);
}
add_filter( 'manage_' . EYECARE_POST_TYPE_BAC_SI . '_posts_columns', 'eyecare_cot_danh_sach_bac_si' );

/**
 * Nội dung cột danh sách bác sĩ.
 *
 * @param string $column  Tên cột.
 * @param int    $post_id ID bác sĩ.
 */
function eyecare_noi_dung_cot_bac_si( $column, $post_id ) {
	if ( 'eyecare_doctor_image' === $column ) {
		if ( has_post_thumbnail( $post_id ) ) {
			echo get_the_post_thumbnail( $post_id, array( 54, 54 ), array( 'style' => 'width:54px;height:54px;border-radius:50%;object-fit:cover' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			$anh_mau = get_post_meta( $post_id, '_eyecare_anh_url', true );
			if ( $anh_mau ) {
				echo '<img src="' . esc_url( $anh_mau ) . '" alt="" style="width:54px;height:54px;border-radius:50%;object-fit:cover">';
			} else {
				echo '<span aria-hidden="true">—</span>';
			}
		}
	} elseif ( 'eyecare_doctor_role' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_eyecare_chuc_danh', true ) );
	} elseif ( 'eyecare_doctor_badge' === $column ) {
		$badges = get_post_meta( $post_id, '_eyecare_badges', true );
		if ( is_array( $badges ) && ! empty( $badges ) ) {
			echo esc_html( implode( ' · ', $badges ) );
		} else {
			echo esc_html( get_post_meta( $post_id, '_eyecare_badge', true ) );
		}
	} elseif ( 'eyecare_doctor_order' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_eyecare_thu_tu', true ) );
	}
}
add_action( 'manage_' . EYECARE_POST_TYPE_BAC_SI . '_posts_custom_column', 'eyecare_noi_dung_cot_bac_si', 10, 2 );
