<?php
/**
 * Quản lý thông tin liên hệ của bệnh viện.
 *
 * Dữ liệu lưu trong data/contact.json để không bị mất khi cơ sở dữ liệu
 * localhost được đồng bộ lại. Mọi điểm hiển thị và schema đều đọc qua
 * eyecare_du_lieu_thuc_the(), vì vậy thay đổi có hiệu lực toàn website.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Đường dẫn tệp cấu hình liên hệ. */
function eyecare_lien_he_duong_dan_tep() {
	return get_stylesheet_directory() . '/data/contact.json';
}

/** Các khóa được phép đọc từ tệp cấu hình. */
function eyecare_lien_he_khoa_cho_phep() {
	return array( 'dien_thoai', 'dien_thoai_hien', 'gio_mo', 'gio_dong', 'dia_chi', 'phuong', 'tinh', 'map_embed' );
}

/** Đọc cấu hình đã lưu, bỏ qua khóa lạ hoặc tệp JSON hỏng. */
function eyecare_lien_he_doc_cau_hinh() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$cache = array();
	$tep   = eyecare_lien_he_duong_dan_tep();
	if ( ! is_readable( $tep ) ) {
		return $cache;
	}

	$raw = file_get_contents( $tep ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$doc = json_decode( (string) $raw, true );
	if ( ! is_array( $doc ) ) {
		return $cache;
	}

	foreach ( eyecare_lien_he_khoa_cho_phep() as $khoa ) {
		if ( isset( $doc[ $khoa ] ) && is_string( $doc[ $khoa ] ) ) {
			$cache[ $khoa ] = $doc[ $khoa ];
		}
	}

	return $cache;
}

/** Chuẩn hóa số hiển thị thành giá trị dùng cho href="tel:". */
function eyecare_lien_he_chuan_hoa_so_goi( $so_hien ) {
	$so = preg_replace( '/[^0-9+]/', '', (string) $so_hien );
	if ( str_starts_with( $so, '0' ) ) {
		return '+84' . substr( $so, 1 );
	}
	if ( str_starts_with( $so, '84' ) ) {
		return '+' . $so;
	}
	return $so;
}

/** Kiểm tra giờ dạng HH:MM. */
function eyecare_lien_he_gio_hop_le( $gio, $mac_dinh ) {
	if ( ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $gio ) ) {
		return $mac_dinh;
	}
	return $gio;
}

/** Chỉ nhận URL nhúng từ tên miền Google Maps. */
function eyecare_lien_he_map_hop_le( $url ) {
	$url  = esc_url_raw( (string) $url );
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( '' === $url || ! preg_match( '/(^|\.)google\.com$/', $host ) || false === strpos( $url, '/maps/embed' ) ) {
		return '';
	}
	return $url;
}

/** Đăng ký mục quản trị. */
function eyecare_lien_he_them_menu() {
	add_menu_page(
		'Thông tin liên hệ',
		'Thông tin liên hệ',
		'manage_options',
		'eyecare-thong-tin-lien-he',
		'eyecare_lien_he_in_trang_quan_tri',
		'dashicons-phone',
		23
	);
}
add_action( 'admin_menu', 'eyecare_lien_he_them_menu' );

/** In trang quản trị. */
function eyecare_lien_he_in_trang_quan_tri() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'eyecare-child' ) );
	}

	$d = eyecare_du_lieu_thuc_the();
	?>
	<div class="wrap eyecare-contact-admin">
		<h1>Thông tin liên hệ</h1>
		<p>Thay đổi tại đây sẽ cập nhật đồng thời header, trang liên hệ, nút gọi điện và schema của toàn website.</p>
		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu thông tin liên hệ.</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="eyecare_luu_lien_he">
			<?php wp_nonce_field( 'eyecare_luu_lien_he', 'eyecare_lien_he_nonce' ); ?>
			<div class="eyecare-contact-admin__grid">
				<section class="eyecare-contact-admin__card">
					<h2>Điện thoại và giờ làm việc</h2>
					<label for="eyecare-phone">Số điện thoại hiển thị</label>
					<input id="eyecare-phone" name="dien_thoai_hien" type="text" class="regular-text" value="<?php echo esc_attr( $d['dien_thoai_hien'] ); ?>" required placeholder="0868 899 396">
					<p class="description">Hệ thống tự tạo số bấm gọi từ giá trị này.</p>
					<div class="eyecare-contact-admin__two">
						<div><label for="eyecare-open">Giờ mở cửa</label><input id="eyecare-open" name="gio_mo" type="time" value="<?php echo esc_attr( $d['gio_mo'] ); ?>" required></div>
						<div><label for="eyecare-close">Giờ đóng cửa</label><input id="eyecare-close" name="gio_dong" type="time" value="<?php echo esc_attr( $d['gio_dong'] ); ?>" required></div>
					</div>
				</section>

				<section class="eyecare-contact-admin__card">
					<h2>Địa chỉ bệnh viện</h2>
					<label for="eyecare-address">Số nhà, đường</label><input id="eyecare-address" name="dia_chi" type="text" class="large-text" value="<?php echo esc_attr( $d['dia_chi'] ); ?>" required>
					<label for="eyecare-ward">Phường / xã</label><input id="eyecare-ward" name="phuong" type="text" class="large-text" value="<?php echo esc_attr( $d['phuong'] ); ?>" required>
					<label for="eyecare-province">Tỉnh / thành phố</label><input id="eyecare-province" name="tinh" type="text" class="large-text" value="<?php echo esc_attr( $d['tinh'] ); ?>" required>
				</section>

				<section class="eyecare-contact-admin__card eyecare-contact-admin__card--wide">
					<h2>Bản đồ Google</h2>
					<label for="eyecare-map">URL trong thuộc tính <code>src</code> của mã iframe Google Maps</label>
					<textarea id="eyecare-map" name="map_embed" class="large-text code" rows="4"><?php echo esc_textarea( $d['map_embed'] ?? '' ); ?></textarea>
					<p class="description">Chỉ dán URL bắt đầu bằng https://www.google.com/maps/embed…; không dán toàn bộ thẻ iframe.</p>
				</section>
			</div>
			<?php submit_button( 'Lưu thông tin liên hệ' ); ?>
		</form>
	</div>
	<style>
		.eyecare-contact-admin{max-width:1050px}.eyecare-contact-admin__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:22px}.eyecare-contact-admin__card{padding:22px;background:#fff;border:1px solid #dcdcde;border-radius:10px;box-shadow:0 4px 18px rgba(0,0,0,.04)}.eyecare-contact-admin__card--wide{grid-column:1/-1}.eyecare-contact-admin__card h2{margin-top:0}.eyecare-contact-admin__card label{display:block;margin:15px 0 6px;font-weight:600}.eyecare-contact-admin__card input[type=text],.eyecare-contact-admin__card input[type=time],.eyecare-contact-admin__card textarea{width:100%}.eyecare-contact-admin__two{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:782px){.eyecare-contact-admin__grid,.eyecare-contact-admin__two{grid-template-columns:1fr}.eyecare-contact-admin__card--wide{grid-column:auto}}
	</style>
	<?php
}

/** Lưu cấu hình sau khi kiểm tra quyền và nonce. */
function eyecare_lien_he_luu() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'eyecare-child' ) );
	}
	check_admin_referer( 'eyecare_luu_lien_he', 'eyecare_lien_he_nonce' );

	$hien_tai = eyecare_du_lieu_thuc_the();
	$so_hien  = isset( $_POST['dien_thoai_hien'] ) ? sanitize_text_field( wp_unslash( $_POST['dien_thoai_hien'] ) ) : $hien_tai['dien_thoai_hien'];
	$so_goi   = eyecare_lien_he_chuan_hoa_so_goi( $so_hien );
	if ( strlen( preg_replace( '/\D/', '', $so_goi ) ) < 9 ) {
		wp_die( esc_html__( 'Số điện thoại chưa hợp lệ.', 'eyecare-child' ) );
	}

	$data = array(
		'dien_thoai_hien' => $so_hien,
		'dien_thoai'      => $so_goi,
		'gio_mo'          => eyecare_lien_he_gio_hop_le( sanitize_text_field( wp_unslash( $_POST['gio_mo'] ?? '' ) ), $hien_tai['gio_mo'] ),
		'gio_dong'        => eyecare_lien_he_gio_hop_le( sanitize_text_field( wp_unslash( $_POST['gio_dong'] ?? '' ) ), $hien_tai['gio_dong'] ),
		'dia_chi'         => sanitize_text_field( wp_unslash( $_POST['dia_chi'] ?? '' ) ),
		'phuong'          => sanitize_text_field( wp_unslash( $_POST['phuong'] ?? '' ) ),
		'tinh'            => sanitize_text_field( wp_unslash( $_POST['tinh'] ?? '' ) ),
		'map_embed'       => eyecare_lien_he_map_hop_le( wp_unslash( $_POST['map_embed'] ?? '' ) ),
	);

	$thu_muc = dirname( eyecare_lien_he_duong_dan_tep() );
	if ( ! is_dir( $thu_muc ) ) {
		wp_mkdir_p( $thu_muc );
	}
	$ket_qua = file_put_contents( eyecare_lien_he_duong_dan_tep(), wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	if ( false === $ket_qua ) {
		wp_die( esc_html__( 'Không thể ghi tệp cấu hình liên hệ. Hãy kiểm tra quyền ghi thư mục data.', 'eyecare-child' ) );
	}

	wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=eyecare-thong-tin-lien-he' ) ) );
	exit;
}
add_action( 'admin_post_eyecare_luu_lien_he', 'eyecare_lien_he_luu' );

