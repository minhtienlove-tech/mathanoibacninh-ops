<?php
/**
 * Tao va quan ly anh dai dien cho bai viet kien thuc.
 *
 * Anh duoc tao rieng theo tieu de va chuyen muc, sau do luu thanh attachment
 * WordPress that. Quan tri vien van co the thay anh bang hop "Anh dai dien"
 * mac dinh; code khong bao gio ghi de anh da duoc chon thu cong.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lay chuyen muc cu the nhat cua bai viet.
 *
 * @param int $post_id ID bai viet.
 * @return WP_Term|null
 */
function eyecare_chuyen_muc_anh_dai_dien( $post_id ) {
	$cac_muc = get_the_category( absint( $post_id ) );

	foreach ( $cac_muc as $muc ) {
		if ( $muc instanceof WP_Term && $muc->parent ) {
			return $muc;
		}
	}

	return $cac_muc ? $cac_muc[0] : null;
}

/**
 * Bang mau cua anh bia theo chuyen muc.
 *
 * @param string $slug Slug chuyen muc.
 * @return array<string,array<int,int>>
 */
function eyecare_mau_anh_dai_dien( $slug ) {
	$bang_mau = array(
		'can-thi-tre-em'             => array( array( 239, 247, 255 ), array( 208, 230, 251 ), array( 41, 100, 181 ) ),
		'chuan-bi-kham-bao-hiem'     => array( array( 240, 250, 245 ), array( 219, 241, 229 ), array( 14, 119, 71 ) ),
		'dau-hieu-can-kham-ngay'     => array( array( 255, 245, 240 ), array( 253, 222, 212 ), array( 179, 52, 52 ) ),
		'dich-kinh-vong-mac'         => array( array( 244, 241, 255 ), array( 223, 216, 250 ), array( 91, 69, 164 ) ),
		'giac-mac-ket-mac-kho-mat'  => array( array( 237, 251, 249 ), array( 207, 239, 235 ), array( 8, 127, 120 ) ),
		'glocom-cuom-nuoc'           => array( array( 238, 249, 241 ), array( 205, 235, 214 ), array( 8, 123, 66 ) ),
		'mat-nguoi-cao-tuoi'         => array( array( 255, 249, 232 ), array( 248, 229, 177 ), array( 139, 94, 0 ) ),
		'mat-va-moi-truong-lam-viec' => array( array( 238, 249, 253 ), array( 207, 235, 247 ), array( 23, 109, 145 ) ),
		'nhuoc-thi-lac-tre-em'       => array( array( 253, 242, 249 ), array( 245, 216, 233 ), array( 151, 54, 108 ) ),
		'tat-khuc-xa-nguoi-lon'      => array( array( 237, 246, 255 ), array( 207, 229, 250 ), array( 23, 104, 172 ) ),
	);

	return isset( $bang_mau[ $slug ] )
		? $bang_mau[ $slug ]
		: array( array( 239, 249, 243 ), array( 207, 235, 216 ), array( 10, 110, 62 ) );
}

/**
 * Do rong chu TrueType.
 *
 * @param string $chu  Chuoi can do.
 * @param int    $co   Co chu.
 * @param string $font Duong dan font.
 * @return int
 */
function eyecare_do_rong_chu_anh( $chu, $co, $font ) {
	$hop = imagettfbbox( $co, 0, $font, $chu );
	return is_array( $hop ) ? absint( $hop[2] - $hop[0] ) : 0;
}

/**
 * Chia tieu de thanh cac dong vua khung anh.
 *
 * @param string $chu      Tieu de.
 * @param int    $co       Co chu.
 * @param string $font     Duong dan font.
 * @param int    $rong     Chieu rong toi da.
 * @param int    $so_dong  So dong toi da.
 * @return array<int,string>
 */
function eyecare_chia_dong_tieu_de_anh( $chu, $co, $font, $rong, $so_dong = 4 ) {
	$cac_tu   = preg_split( '/\s+/u', trim( wp_strip_all_tags( $chu ) ), -1, PREG_SPLIT_NO_EMPTY );
	$cac_dong = array();
	$dong     = '';

	foreach ( $cac_tu as $tu ) {
		$thu = '' === $dong ? $tu : $dong . ' ' . $tu;
		if ( '' === $dong || eyecare_do_rong_chu_anh( $thu, $co, $font ) <= $rong ) {
			$dong = $thu;
			continue;
		}

		$cac_dong[] = $dong;
		$dong       = $tu;
	}

	if ( '' !== $dong ) {
		$cac_dong[] = $dong;
	}

	if ( count( $cac_dong ) > $so_dong ) {
		$cac_dong = array_slice( $cac_dong, 0, $so_dong );
		$cuoi     = rtrim( $cac_dong[ $so_dong - 1 ], " .,;:-" );
		while ( eyecare_do_rong_chu_anh( $cuoi . '...', $co, $font ) > $rong && mb_strlen( $cuoi ) > 4 ) {
			$cuoi = rtrim( mb_substr( $cuoi, 0, -1 ) );
		}
		$cac_dong[ $so_dong - 1 ] = $cuoi . '...';
	}

	return $cac_dong;
}

/**
 * Ve anh bia va gan lam featured image.
 *
 * @param int $post_id ID bai viet.
 * @return int|WP_Error Attachment ID hoac loi.
 */
function eyecare_tao_anh_dai_dien_bai_viet( $post_id ) {
	$post_id = absint( $post_id );
	$bai     = get_post( $post_id );

	if ( ! $bai instanceof WP_Post || 'post' !== $bai->post_type ) {
		return new WP_Error( 'eyecare_invalid_post', 'Bai viet khong hop le.' );
	}

	if ( has_post_thumbnail( $post_id ) ) {
		return (int) get_post_thumbnail_id( $post_id );
	}

	if ( ! extension_loaded( 'gd' ) || ! function_exists( 'imagettftext' ) ) {
		return new WP_Error( 'eyecare_missing_gd', 'May chu chua bat GD/FreeType de tao anh.' );
	}

	$anh_cu = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_eyecare_generated_featured_for',
			'meta_value'     => $post_id,
		)
	);
	if ( $anh_cu ) {
		set_post_thumbnail( $post_id, (int) $anh_cu[0] );
		return (int) $anh_cu[0];
	}

	$font_dam = get_stylesheet_directory() . '/assets/fonts/utm-banqueb.ttf';
	$font_phu = 'C:/Windows/Fonts/arial.ttf';
	if ( ! is_readable( $font_dam ) || ! is_readable( $font_phu ) ) {
		return new WP_Error( 'eyecare_missing_font', 'Khong tim thay font tao anh.' );
	}

	$muc      = eyecare_chuyen_muc_anh_dai_dien( $post_id );
	$slug_muc = $muc instanceof WP_Term ? $muc->slug : 'kien-thuc-nhan-khoa';
	$ten_muc  = $muc instanceof WP_Term ? $muc->name : 'Kien thuc nhan khoa';
	$mau      = eyecare_mau_anh_dai_dien( $slug_muc );
	$rong     = 1200;
	$cao      = 675;
	$anh      = imagecreatetruecolor( $rong, $cao );

	if ( ! $anh ) {
		return new WP_Error( 'eyecare_image_create_failed', 'Khong the khoi tao anh.' );
	}

	imagealphablending( $anh, true );
	imagesavealpha( $anh, true );

	for ( $y = 0; $y < $cao; $y++ ) {
		$ti_le = $y / max( 1, $cao - 1 );
		$r     = (int) round( $mau[0][0] + ( $mau[1][0] - $mau[0][0] ) * $ti_le );
		$g     = (int) round( $mau[0][1] + ( $mau[1][1] - $mau[0][1] ) * $ti_le );
		$b     = (int) round( $mau[0][2] + ( $mau[1][2] - $mau[0][2] ) * $ti_le );
		imageline( $anh, 0, $y, $rong, $y, imagecolorallocate( $anh, $r, $g, $b ) );
	}

	$mau_nhan = imagecolorallocate( $anh, $mau[2][0], $mau[2][1], $mau[2][2] );
	$mau_chu  = imagecolorallocate( $anh, 16, 50, 35 );
	$mau_trang = imagecolorallocate( $anh, 255, 255, 255 );
	$mau_mo    = imagecolorallocatealpha( $anh, $mau[2][0], $mau[2][1], $mau[2][2], 108 );
	$mau_sang  = imagecolorallocatealpha( $anh, 255, 255, 255, 44 );

	imagefilledellipse( $anh, 1110, 70, 360, 360, $mau_sang );
	imagefilledellipse( $anh, 1020, 600, 530, 350, $mau_mo );
	imagefilledellipse( $anh, 90, 650, 260, 210, $mau_sang );

	// Nhan chuyen muc.
	$nhan = mb_strtoupper( $ten_muc, 'UTF-8' );
	$nhan_rong = eyecare_do_rong_chu_anh( $nhan, 18, $font_phu );
	imagefilledrectangle( $anh, 68, 104, 102 + $nhan_rong, 145, $mau_nhan );
	imagettftext( $anh, 18, 0, 85, 132, $mau_trang, $font_phu, $nhan );

	// Tieu de bai viet.
	$cac_dong = eyecare_chia_dong_tieu_de_anh( $bai->post_title, 43, $font_dam, 690, 4 );
	$y_chu    = 225;
	foreach ( $cac_dong as $dong ) {
		imagettftext( $anh, 43, 0, 68, $y_chu, $mau_chu, $font_dam, $dong );
		$y_chu += 66;
	}

	// Bieu tuong mat o nua phai.
	$cx = 965;
	$cy = 324;
	imagefilledellipse( $anh, $cx, $cy, 350, 198, $mau_trang );
	imageellipse( $anh, $cx, $cy, 350, 198, $mau_nhan );
	imageellipse( $anh, $cx, $cy, 342, 190, $mau_nhan );
	imagefilledellipse( $anh, $cx, $cy, 132, 132, $mau_nhan );
	imagefilledellipse( $anh, $cx, $cy, 64, 64, $mau_chu );
	imagefilledellipse( $anh, $cx + 20, $cy - 20, 18, 18, $mau_trang );

	// Thuong hieu o hai dau anh.
	imagettftext( $anh, 16, 0, 69, 625, $mau_nhan, $font_phu, 'BỆNH VIỆN MẮT HÀ NỘI - BẮC NINH' );
	imagettftext( $anh, 14, 0, 932, 625, $mau_nhan, $font_phu, 'KIẾN THỨC NHÃN KHOA' );

	$thu_muc_tai = wp_upload_dir();
	if ( ! empty( $thu_muc_tai['error'] ) ) {
		imagedestroy( $anh );
		return new WP_Error( 'eyecare_upload_dir_error', $thu_muc_tai['error'] );
	}

	$thu_muc_anh = trailingslashit( $thu_muc_tai['basedir'] ) . 'anh-dai-dien-bai-viet';
	if ( ! wp_mkdir_p( $thu_muc_anh ) ) {
		imagedestroy( $anh );
		return new WP_Error( 'eyecare_upload_create_failed', 'Khong tao duoc thu muc anh.' );
	}

	$ten_co_so = 'anh-dai-dien-' . $post_id . '-' . sanitize_title( $bai->post_name );
	$ho_tro_webp = function_exists( 'imagewebp' );
	$duoi        = $ho_tro_webp ? 'webp' : 'jpg';
	$mime        = $ho_tro_webp ? 'image/webp' : 'image/jpeg';
	$tep         = trailingslashit( $thu_muc_anh ) . wp_unique_filename( $thu_muc_anh, $ten_co_so . '.' . $duoi );
	$da_luu      = $ho_tro_webp ? imagewebp( $anh, $tep, 88 ) : imagejpeg( $anh, $tep, 90 );
	imagedestroy( $anh );

	if ( ! $da_luu ) {
		return new WP_Error( 'eyecare_image_save_failed', 'Khong luu duoc anh.' );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'guid'           => trailingslashit( $thu_muc_tai['baseurl'] ) . 'anh-dai-dien-bai-viet/' . wp_basename( $tep ),
			'post_mime_type' => $mime,
			'post_title'     => 'Anh dai dien - ' . $bai->post_title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$tep,
		$post_id,
		true
	);

	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $tep );
		return $attachment_id;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	$metadata = wp_generate_attachment_metadata( $attachment_id, $tep );
	if ( $metadata ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $bai->post_title ) );
	update_post_meta( $attachment_id, '_eyecare_generated_featured_for', $post_id );
	set_post_thumbnail( $post_id, $attachment_id );

	return (int) $attachment_id;
}

/** Tao anh cho bai moi ngay khi chuyen sang trang thai cong khai. */
function eyecare_tao_anh_khi_xuat_ban( $trang_thai_moi, $trang_thai_cu, $post ) {
	if ( 'publish' !== $trang_thai_moi || 'publish' === $trang_thai_cu || ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
		return;
	}

	$ket_qua = eyecare_tao_anh_dai_dien_bai_viet( $post->ID );
	if ( is_wp_error( $ket_qua ) ) {
		error_log( 'EYECARE_FEATURED_IMAGE [publish]: ' . $ket_qua->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}
add_action( 'transition_post_status', 'eyecare_tao_anh_khi_xuat_ban', 20, 3 );

/** Them cot anh vao danh sach bai viet trong Admin. */
function eyecare_them_cot_anh_bai_viet( $cot ) {
	$ket_qua = array();
	foreach ( $cot as $khoa => $nhan ) {
		$ket_qua[ $khoa ] = $nhan;
		if ( 'cb' === $khoa ) {
			$ket_qua['eyecare_anh_dai_dien'] = 'Ảnh';
		}
	}
	return $ket_qua;
}
add_filter( 'manage_posts_columns', 'eyecare_them_cot_anh_bai_viet' );

/** Hien thumbnail trong cot Admin. */
function eyecare_hien_cot_anh_bai_viet( $cot, $post_id ) {
	if ( 'eyecare_anh_dai_dien' !== $cot ) {
		return;
	}

	if ( has_post_thumbnail( $post_id ) ) {
		echo get_the_post_thumbnail( $post_id, array( 72, 48 ), array( 'style' => 'width:72px;height:48px;object-fit:cover;border-radius:6px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo '<span style="color:#b32d2e;font-weight:600">Chưa có</span>';
	}
}
add_action( 'manage_posts_custom_column', 'eyecare_hien_cot_anh_bai_viet', 10, 2 );

/** Trang cong cu de tao lai cac anh dang thieu. */
function eyecare_dang_ky_trang_anh_dai_dien() {
	add_submenu_page(
		'edit.php',
		'Ảnh đại diện bài viết',
		'Ảnh đại diện',
		'edit_others_posts',
		'eyecare-anh-dai-dien',
		'eyecare_trang_anh_dai_dien'
	);
}
add_action( 'admin_menu', 'eyecare_dang_ky_trang_anh_dai_dien' );

/** Render trang cong cu anh dai dien. */
function eyecare_trang_anh_dai_dien() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'eyecare-child' ) );
	}

	$tong = (int) wp_count_posts( 'post' )->publish;
	$co_anh = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_thumbnail_id',
			'fields'         => 'ids',
		)
	);
	$thieu = max( 0, $tong - (int) $co_anh->found_posts );
	?>
	<div class="wrap">
		<h1>Ảnh đại diện bài viết</h1>
		<p><strong><?php echo esc_html( number_format_i18n( $tong ) ); ?></strong> bài công khai; <strong><?php echo esc_html( number_format_i18n( $thieu ) ); ?></strong> bài đang thiếu ảnh.</p>
		<p>Ảnh tự động chỉ được tạo cho bài đang thiếu. Ảnh do quản trị viên chọn sẽ luôn được giữ nguyên.</p>
		<?php if ( isset( $_GET['da_tao'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p>Đã tạo <?php echo esc_html( (string) absint( $_GET['da_tao'] ) ); ?> ảnh đại diện.</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="eyecare_tao_anh_dai_dien">
			<?php wp_nonce_field( 'eyecare_tao_anh_dai_dien' ); ?>
			<?php submit_button( $thieu ? 'Tạo ảnh cho các bài đang thiếu' : 'Không còn bài thiếu ảnh', 'primary', 'submit', false, array( 'disabled' => ! $thieu ) ); ?>
		</form>
	</div>
	<?php
}

/** Xu ly nut tao anh trong Admin. */
function eyecare_xu_ly_tao_anh_dai_dien() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'eyecare-child' ) );
	}
	check_admin_referer( 'eyecare_tao_anh_dai_dien' );

	$bai_thieu = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	$da_tao = 0;
	foreach ( $bai_thieu as $post_id ) {
		$ket_qua = eyecare_tao_anh_dai_dien_bai_viet( $post_id );
		if ( ! is_wp_error( $ket_qua ) ) {
			$da_tao++;
		}
	}

	wp_safe_redirect( add_query_arg( 'da_tao', $da_tao, admin_url( 'edit.php?page=eyecare-anh-dai-dien' ) ) );
	exit;
}
add_action( 'admin_post_eyecare_tao_anh_dai_dien', 'eyecare_xu_ly_tao_anh_dai_dien' );

