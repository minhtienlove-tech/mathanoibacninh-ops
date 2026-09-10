<?php
/**
 * Quản lý ảnh giao diện dùng chung trên website.
 *
 * Ảnh được lưu bằng ID trong Thư viện Media, còn cấu hình vị trí ảnh lưu vào
 * data/visual-images.json để không mất khi cơ sở dữ liệu localhost bị đồng bộ.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Đường dẫn tệp cấu hình ảnh giao diện. */
function eyecare_anh_giao_dien_tep_cau_hinh() {
	return get_stylesheet_directory() . '/data/visual-images.json';
}

/**
 * Danh sách vị trí ảnh được quản lý trong admin.
 *
 * @return array<string,array<string,string>>
 */
function eyecare_anh_giao_dien_slots() {
	return array(
		'bac_si_le_nhu_tung' => array(
			'group' => 'Bác sĩ',
			'label' => 'Ảnh 1 — Ths.BS Lê Như Tùng',
			'desc'  => 'Ảnh chân dung đồng bộ với mục Đội ngũ bác sĩ, khối tác giả và các thẻ đội ngũ.',
			'alt'   => 'Ths.BS Lê Như Tùng',
		),
		'lien_he_kinh_1'     => array(
			'group' => 'Trang liên hệ',
			'label' => 'Hero kính mắt — mặt tiền bệnh viện',
			'desc'  => 'Ảnh đầu tiên trong hiệu ứng “chớp mắt/kính mắt” ở trang Liên hệ.',
			'alt'   => 'Mặt tiền Bệnh viện Mắt Hà Nội - Bắc Ninh',
		),
		'lien_he_kinh_2'     => array(
			'group' => 'Trang liên hệ',
			'label' => 'Hero kính mắt — quầy tiếp đón',
			'desc'  => 'Ảnh khu vực lễ tân hoặc quầy tiếp nhận người bệnh.',
			'alt'   => 'Quầy tiếp đón tại Bệnh viện Mắt Hà Nội - Bắc Ninh',
		),
		'lien_he_kinh_3'     => array(
			'group' => 'Trang liên hệ',
			'label' => 'Hero kính mắt — khu chờ và kính mắt',
			'desc'  => 'Ảnh không gian chờ, khu kính hoặc khu tư vấn.',
			'alt'   => 'Không gian chờ và khu kính tại Bệnh viện Mắt Hà Nội - Bắc Ninh',
		),
		'lien_he_kinh_4'     => array(
			'group' => 'Trang liên hệ',
			'label' => 'Hero kính mắt — không gian nội thất',
			'desc'  => 'Ảnh bổ sung trong slider kính mắt, có thể gỡ nếu chỉ muốn 3 ảnh.',
			'alt'   => 'Không gian nội thất Bệnh viện Mắt Hà Nội - Bắc Ninh',
		),
		've_chung_toi_1'     => array(
			'group' => 'Trang Về chúng tôi',
			'label' => 'Về chúng tôi — mặt tiền bệnh viện',
			'desc'  => 'Ảnh dùng trong cụm hình cơ sở vật chất của trang Về chúng tôi.',
			'alt'   => 'Mặt tiền Bệnh viện Mắt Hà Nội - Bắc Ninh',
		),
		've_chung_toi_2'     => array(
			'group' => 'Trang Về chúng tôi',
			'label' => 'Về chúng tôi — quầy tiếp đón',
			'desc'  => 'Ảnh dùng trong cụm hình cơ sở vật chất của trang Về chúng tôi.',
			'alt'   => 'Quầy tiếp đón tại Bệnh viện Mắt Hà Nội - Bắc Ninh',
		),
		've_chung_toi_3'     => array(
			'group' => 'Trang Về chúng tôi',
			'label' => 'Về chúng tôi — khu chờ',
			'desc'  => 'Ảnh dùng trong cụm hình cơ sở vật chất của trang Về chúng tôi.',
			'alt'   => 'Khu chờ tại Bệnh viện Mắt Hà Nội - Bắc Ninh',
		),
		'gioi_thieu_tam_nhin' => array(
			'group' => 'Trang Về chúng tôi',
			'label' => 'Khối Tầm nhìn và giá trị',
			'desc'  => 'Ảnh nền của thẻ lớn “Tầm nhìn và giá trị”. Nên dùng ảnh ngang, không chèn chữ trực tiếp vào ảnh.',
			'alt'   => 'Bác sĩ đồng hành cùng người bệnh trong không gian bệnh viện hiện đại',
		),
		'chuyen_khoa_nen'    => array(
			'group' => 'Trang Chuyên khoa',
			'label' => 'Nền trang Chuyên khoa — mắt và ánh sáng y tế',
			'desc'  => 'Ảnh nền nhẹ phía sau phần danh mục chuyên khoa. Có thể thay bằng ảnh ngang khác trong Thư viện Media.',
			'alt'   => 'Nền trang Chuyên khoa nhãn khoa',
		),
		'dich_vu_kham_mat_tong_quat' => array('group'=>'Ảnh nền từng dịch vụ','label'=>'Khám mắt tổng quát','desc'=>'Ảnh nền thẻ dịch vụ Khám mắt tổng quát.','alt'=>'Khám mắt tổng quát'),
		'dich_vu_chan_doan_nhan_khoa' => array('group'=>'Ảnh nền từng dịch vụ','label'=>'Chẩn đoán nhãn khoa','desc'=>'Ảnh nền thẻ dịch vụ Chẩn đoán nhãn khoa.','alt'=>'Thiết bị chẩn đoán nhãn khoa'),
		'dich_vu_phau_thuat_phaco' => array('group'=>'Ảnh nền từng dịch vụ','label'=>'Phẫu thuật thay thủy tinh thể','desc'=>'Ảnh nền thẻ dịch vụ Phẫu thuật Phaco.','alt'=>'Phẫu thuật Phaco'),
		'dich_vu_phau_thuat_khuc_xa' => array('group'=>'Ảnh nền từng dịch vụ','label'=>'Phẫu thuật khúc xạ','desc'=>'Ảnh nền thẻ dịch vụ Phẫu thuật khúc xạ.','alt'=>'Phẫu thuật khúc xạ'),
		'dich_vu_phau_thuat_mong' => array('group'=>'Ảnh nền từng dịch vụ','label'=>'Phẫu thuật mộng mắt','desc'=>'Ảnh nền thẻ dịch vụ Phẫu thuật mộng mắt.','alt'=>'Khám và điều trị mộng mắt'),
		'dich_vu_phau_thuat_quem' => array('group'=>'Ảnh nền từng dịch vụ','label'=>'Phẫu thuật quặm mi','desc'=>'Ảnh nền thẻ dịch vụ Phẫu thuật quặm mi.','alt'=>'Khám và điều trị quặm mi'),
		'dich_vu_mi_mat_le_dao' => array('group'=>'Ảnh nền từng dịch vụ','label'=>'Mi mắt và lệ đạo','desc'=>'Ảnh nền thẻ dịch vụ Mi mắt và lệ đạo.','alt'=>'Khám mi mắt và lệ đạo'),
		'dich_vu_xet_nghiem_truoc_phau_thuat' => array('group'=>'Ảnh nền từng dịch vụ','label'=>'Xét nghiệm trước phẫu thuật','desc'=>'Ảnh nền thẻ dịch vụ Xét nghiệm trước phẫu thuật.','alt'=>'Xét nghiệm trước phẫu thuật'),
	);
}

/** Cấu hình mặc định cho các slot ảnh. */
function eyecare_anh_giao_dien_mac_dinh() {
	return array_fill_keys( array_keys( eyecare_anh_giao_dien_slots() ), 0 );
}

/**
 * Đọc cấu hình ảnh giao diện từ tệp.
 *
 * @return array<string,int>
 */
function eyecare_anh_giao_dien_doc_cau_hinh() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$cache = eyecare_anh_giao_dien_mac_dinh();
	$tep   = eyecare_anh_giao_dien_tep_cau_hinh();

	if ( ! is_readable( $tep ) ) {
		return $cache;
	}

	$raw = file_get_contents( $tep ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$doc = json_decode( (string) $raw, true );

	if ( ! is_array( $doc ) ) {
		return $cache;
	}

	foreach ( $cache as $key => $id ) {
		if ( isset( $doc[ $key ] ) ) {
			$cache[ $key ] = absint( $doc[ $key ] );
		}
	}

	return $cache;
}

/**
 * Ghi cấu hình ảnh giao diện.
 *
 * @param array<string,int> $data Dữ liệu ảnh theo slot.
 * @return bool
 */
function eyecare_anh_giao_dien_ghi_cau_hinh( $data ) {
	$thu_muc = dirname( eyecare_anh_giao_dien_tep_cau_hinh() );

	if ( ! is_dir( $thu_muc ) ) {
		wp_mkdir_p( $thu_muc );
	}

	$hop_le = eyecare_anh_giao_dien_mac_dinh();
	foreach ( $hop_le as $key => $id ) {
		$hop_le[ $key ] = isset( $data[ $key ] ) ? absint( $data[ $key ] ) : 0;
	}

	$json = wp_json_encode( $hop_le, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

	return false !== file_put_contents( eyecare_anh_giao_dien_tep_cau_hinh(), $json . PHP_EOL, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
}

/**
 * Lấy thông tin một ảnh giao diện.
 *
 * @param string $key  Khóa slot.
 * @param string $size Kích thước ảnh WordPress.
 * @return array<string,mixed>|null
 */
function eyecare_anh_giao_dien_lay( $key, $size = 'large' ) {
	$slots = eyecare_anh_giao_dien_slots();
	if ( ! isset( $slots[ $key ] ) ) {
		return null;
	}

	$config = eyecare_anh_giao_dien_doc_cau_hinh();
	$id     = isset( $config[ $key ] ) ? absint( $config[ $key ] ) : 0;
	if ( ! $id ) {
		return null;
	}

	$src = wp_get_attachment_image_src( $id, $size );
	if ( ! $src ) {
		return null;
	}

	$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
	if ( '' === trim( (string) $alt ) ) {
		$alt = $slots[ $key ]['alt'];
	}

	return array(
		'id'     => $id,
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'alt'    => (string) $alt,
		'title'  => get_the_title( $id ),
	);
}

/**
 * Lấy nhiều ảnh giao diện, tự bỏ slot chưa có ảnh.
 *
 * @param string[] $keys Danh sách khóa slot.
 * @param string   $size Kích thước ảnh WordPress.
 * @return array<int,array<string,mixed>>
 */
function eyecare_anh_giao_dien_lay_nhieu( $keys, $size = 'large' ) {
	$ra = array();

	foreach ( $keys as $key ) {
		$anh = eyecare_anh_giao_dien_lay( $key, $size );
		if ( $anh ) {
			$anh['key'] = $key;
			$ra[]       = $anh;
		}
	}

	return $ra;
}

/** Tìm bản ghi bác sĩ Lê Như Tùng trong post type Đội ngũ bác sĩ. */
function eyecare_anh_giao_dien_tim_bac_si_le_nhu_tung() {
	$post_type = defined( 'EYECARE_POST_TYPE_BAC_SI' ) ? EYECARE_POST_TYPE_BAC_SI : 'eyecare_bac_si';
	if ( ! post_type_exists( $post_type ) ) {
		return 0;
	}

	$posts = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $post_id ) {
		if ( 'le-nhu-tung' === sanitize_title( get_the_title( $post_id ) ) ) {
			return (int) $post_id;
		}
	}

	return 0;
}

/** Lấy ảnh đại diện hiện tại của bác sĩ Lê Như Tùng. */
function eyecare_anh_giao_dien_anh_bac_si_le_nhu_tung() {
	$post_id = eyecare_anh_giao_dien_tim_bac_si_le_nhu_tung();
	return $post_id ? (int) get_post_thumbnail_id( $post_id ) : 0;
}

/**
 * Đồng bộ slot ảnh bác sĩ ở trang Ảnh giao diện về bản ghi Đội ngũ bác sĩ.
 *
 * @param int $anh_id ID ảnh mới, hoặc 0 để gỡ ảnh đại diện.
 */
function eyecare_anh_giao_dien_dong_bo_bac_si_le_nhu_tung( $anh_id ) {
	$post_id = eyecare_anh_giao_dien_tim_bac_si_le_nhu_tung();
	if ( ! $post_id ) {
		return;
	}

	$anh_id = absint( $anh_id );
	if ( $anh_id && wp_attachment_is_image( $anh_id ) ) {
		set_post_thumbnail( $post_id, $anh_id );
		update_post_meta( $post_id, '_eyecare_anh_can_chinh', '' );
		return;
	}

	delete_post_thumbnail( $post_id );
}

/** Thêm trang quản trị dưới menu Giao diện. */
function eyecare_anh_giao_dien_them_menu() {
	add_theme_page(
		'Ảnh giao diện',
		'Ảnh giao diện',
		'edit_theme_options',
		'eyecare-anh-giao-dien',
		'eyecare_anh_giao_dien_in_trang_quan_tri'
	);
}
add_action( 'admin_menu', 'eyecare_anh_giao_dien_them_menu' );

/** Nạp media uploader cho trang Ảnh giao diện. */
function eyecare_anh_giao_dien_nap_admin_assets( $hook ) {
	if ( 'appearance_page_eyecare-anh-giao-dien' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	$tep_js = get_stylesheet_directory() . '/assets/visual-images-admin.js';
	wp_enqueue_script(
		'eyecare-visual-images-admin',
		get_stylesheet_directory_uri() . '/assets/visual-images-admin.js',
		array( 'jquery' ),
		file_exists( $tep_js ) ? filemtime( $tep_js ) : '1.0',
		true
	);

	wp_localize_script(
		'eyecare-visual-images-admin',
		'eyecareVisualImagesAdmin',
		array(
			'title'      => 'Chọn ảnh giao diện',
			'button'     => 'Dùng ảnh này',
			'placeholder' => 'Chưa chọn ảnh',
			'editUrl'    => admin_url( 'post.php?post=__ID__&action=edit' ),
		)
	);

	$tep_css = get_stylesheet_directory() . '/assets/visual-images-admin.css';
	wp_enqueue_style(
		'eyecare-visual-images-admin',
		get_stylesheet_directory_uri() . '/assets/visual-images-admin.css',
		array(),
		file_exists( $tep_css ) ? filemtime( $tep_css ) : '1.0'
	);
}
add_action( 'admin_enqueue_scripts', 'eyecare_anh_giao_dien_nap_admin_assets' );

/** In trang quản trị ảnh giao diện. */
function eyecare_anh_giao_dien_in_trang_quan_tri() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền sửa giao diện.', 'eyecare-child' ) );
	}

	$thong_bao = '';
	if ( isset( $_POST['eyecare_anh_giao_dien_luu'] ) ) {
		check_admin_referer( 'eyecare_anh_giao_dien_luu', 'eyecare_anh_giao_dien_nonce' );

		$raw  = isset( $_POST['eyecare_visual_images'] ) && is_array( $_POST['eyecare_visual_images'] ) ? wp_unslash( $_POST['eyecare_visual_images'] ) : array();
		$data = eyecare_anh_giao_dien_mac_dinh();

		foreach ( $data as $key => $id ) {
			$anh_id = isset( $raw[ $key ] ) ? absint( $raw[ $key ] ) : 0;
			if ( $anh_id && ! wp_attachment_is_image( $anh_id ) ) {
				$anh_id = 0;
			}
			$data[ $key ] = $anh_id;
		}

		eyecare_anh_giao_dien_dong_bo_bac_si_le_nhu_tung( $data['bac_si_le_nhu_tung'] );

		if ( eyecare_anh_giao_dien_ghi_cau_hinh( $data ) ) {
			$thong_bao = '<div class="notice notice-success is-dismissible"><p>Đã lưu ảnh giao diện.</p></div>';
		} else {
			$thong_bao = '<div class="notice notice-error"><p>Không ghi được tệp <code>data/visual-images.json</code>. Kiểm tra quyền ghi thư mục theme.</p></div>';
		}
	}

	$config = eyecare_anh_giao_dien_doc_cau_hinh();
	$anh_bac_si = eyecare_anh_giao_dien_anh_bac_si_le_nhu_tung();
	if ( $anh_bac_si ) {
		$config['bac_si_le_nhu_tung'] = $anh_bac_si;
	}
	$slots  = eyecare_anh_giao_dien_slots();
	$groups = array();
	foreach ( $slots as $key => $slot ) {
		$groups[ $slot['group'] ][ $key ] = $slot;
	}
	?>
	<div class="wrap eyecare-visual-admin">
		<h1>Ảnh giao diện</h1>
		<?php echo $thong_bao; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML thông báo tự dựng. ?>
		<p class="eyecare-visual-admin__intro">
			Chọn ảnh dùng cho các khối giao diện như hero trang Liên hệ, trang Về chúng tôi
			và ảnh bác sĩ. Cấu hình lưu ở <code>data/visual-images.json</code>, ảnh vẫn nằm
			trong Thư viện Media nên anh có thể thay, sửa alt hoặc tải ảnh mới như WordPress bình thường.
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'eyecare_anh_giao_dien_luu', 'eyecare_anh_giao_dien_nonce' ); ?>

			<?php foreach ( $groups as $group_name => $group_slots ) : ?>
				<section class="eyecare-visual-admin__group">
					<h2><?php echo esc_html( $group_name ); ?></h2>
					<div class="eyecare-visual-admin__grid">
						<?php foreach ( $group_slots as $key => $slot ) : ?>
							<?php
							$anh_id    = isset( $config[ $key ] ) ? absint( $config[ $key ] ) : 0;
							$edit_link = $anh_id ? get_edit_post_link( $anh_id, '' ) : '';
							?>
							<article class="eyecare-visual-slot" data-visual-slot>
								<div class="eyecare-visual-slot__preview" data-visual-preview>
									<?php
									if ( $anh_id && wp_get_attachment_image_src( $anh_id ) ) {
										echo wp_get_attachment_image( $anh_id, 'medium', false, array( 'alt' => '', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP tự escape ảnh.
									} else {
										echo '<span>' . esc_html__( 'Chưa chọn ảnh', 'eyecare-child' ) . '</span>';
									}
									?>
								</div>
								<div class="eyecare-visual-slot__body">
									<h3><?php echo esc_html( $slot['label'] ); ?></h3>
									<p><?php echo esc_html( $slot['desc'] ); ?></p>
									<input type="hidden" name="eyecare_visual_images[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $anh_id ); ?>" data-visual-input>
									<div class="eyecare-visual-slot__actions">
										<button type="button" class="button button-primary" data-visual-select>Chọn / đổi ảnh</button>
										<button type="button" class="button" data-visual-remove>Gỡ ảnh</button>
										<a class="button<?php echo $edit_link ? '' : ' is-disabled'; ?>" href="<?php echo esc_url( $edit_link ? $edit_link : '#' ); ?>" target="_blank" rel="noopener" data-visual-edit>Sửa alt</a>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>

			<?php submit_button( 'Lưu ảnh giao diện', 'primary', 'eyecare_anh_giao_dien_luu' ); ?>
		</form>
	</div>
	<?php
}
