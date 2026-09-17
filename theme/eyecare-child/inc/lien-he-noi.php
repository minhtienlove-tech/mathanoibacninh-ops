<?php
/** Floating contact shortcuts and appointment form; public theme only. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function eyecare_contact_assets() {
	foreach ( array( 'css', 'js' ) as $extension ) {
		$file = '/assets/lien-he-noi.' . $extension;
		if ( 'css' === $extension ) {
			wp_enqueue_style( 'eyecare-contact-float', get_stylesheet_directory_uri() . $file, array( 'eyecare-child-style' ), filemtime( get_stylesheet_directory() . $file ) );
		} else {
			wp_enqueue_script( 'eyecare-contact-float', get_stylesheet_directory_uri() . $file, array(), filemtime( get_stylesheet_directory() . $file ), true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'eyecare_contact_assets', 120 );

function eyecare_contact_body_class( $classes ) {
	if ( ! is_page( 'dat-lich-kham' ) ) { $classes[] = 'ec-contact-dock'; }
	return $classes;
}
add_filter( 'body_class', 'eyecare_contact_body_class' );

/** Fixed icon paths only; callers cannot supply arbitrary SVG markup. */
function eyecare_contact_icon( $name ) {
	$paths = array(
		'phone' => '<path d="M7 3h3l2 5-2 1.5a15 15 0 0 0 4.5 4.5L16 12l5 2v3c0 2.2-1.8 4-4 4C9.3 21 3 14.7 3 7c0-2.2 1.8-4 4-4Z"/>',
		'chat' => '<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-2 2V11.5A8.5 8.5 0 0 1 10.5 3h2a8.5 8.5 0 0 1 8.5 8.5Z"/><path d="M7 11h.01M12 11h.01M17 11h.01"/>',
		'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-13 5 3 3 5-5"/>',
		'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
		'facebook' => '<path d="M14 21v-8h3l.5-4H14V7c0-1 .5-2 2-2h2V1h-3c-4 0-6 2-6 6v2H6v4h3v8"/>',
		'youtube' => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3Z"/>',
		'tiktok' => '<path d="M14 3v12a4 4 0 1 1-4-4M14 3c1 4 3 5 6 5"/>',
	);
	return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/** Shared markup for the modal and the existing appointment page. */
function eyecare_booking_form( $context = 'modal' ) {
	$id = 'ec-booking-' . sanitize_html_class( $context );
	$info = eyecare_du_lieu_thuc_the();
	?>
	<form class="ec-booking-form" data-ec-booking-form data-endpoint="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" action="<?php echo esc_url( home_url( '/dat-lich-kham/' ) ); ?>">
		<p class="ec-booking-form__hours">Giờ làm việc: <strong><?php echo esc_html( $info['gio_mo'] . '–' . $info['gio_dong'] ); ?></strong> · Tất cả các ngày trong tuần</p>
		<div class="ec-booking-form__grid" data-booking-fields>
			<label for="<?php echo esc_attr( $id ); ?>-name">Họ và tên <span aria-hidden="true">*</span><input id="<?php echo esc_attr( $id ); ?>-name" name="name" type="text" autocomplete="name" maxlength="100" minlength="2" required placeholder="Nhập họ và tên"></label>
			<label for="<?php echo esc_attr( $id ); ?>-phone">Số điện thoại <span aria-hidden="true">*</span><input id="<?php echo esc_attr( $id ); ?>-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="20" required placeholder="Số điện thoại liên hệ"></label>
			<label for="<?php echo esc_attr( $id ); ?>-date">Ngày khám <span aria-hidden="true">*</span><input id="<?php echo esc_attr( $id ); ?>-date" name="date" type="date" required disabled></label>
			<label for="<?php echo esc_attr( $id ); ?>-time">Giờ khám <span aria-hidden="true">*</span><select id="<?php echo esc_attr( $id ); ?>-time" name="time" required disabled><option value="">Đang tải khung giờ…</option></select></label>
			<p class="ec-booking-form__wide ec-booking-form__hint">Nhận đặt lịch đến 17:00, theo từng khung 30 phút. Bệnh viện sẽ liên hệ xác nhận lịch hẹn.</p>
			<label class="ec-booking-form__wide ec-booking-form__consent" for="<?php echo esc_attr( $id ); ?>-consent"><input id="<?php echo esc_attr( $id ); ?>-consent" name="consent" type="checkbox" value="1" required><span>Tôi đồng ý để bệnh viện sử dụng thông tin trên, gồm họ tên và số điện thoại, cho nhân viên được ủy quyền qua hệ thống thông báo nội bộ Zalo để liên hệ xác nhận lịch hẹn. <a href="<?php echo esc_url( get_privacy_policy_url() ?: home_url( '/quyen-rieng-tu/' ) ); ?>" target="_blank" rel="noopener">Chính sách bảo mật</a>.</span></label>
		</div>
		<div class="ec-booking-form__honeypot" aria-hidden="true"><label>Để trống trường này<input name="website" type="text" tabindex="-1" autocomplete="off"></label></div>
		<p class="ec-booking-form__status" data-booking-status role="status" aria-live="polite" tabindex="-1"></p>
		<button class="ec-booking-form__submit" type="submit" data-booking-submit disabled>Gửi yêu cầu đặt lịch</button>
		<button class="ec-booking-form__retry" type="button" data-booking-retry hidden>Tải lại khung giờ</button>
		<button class="ec-booking-form__retry" type="button" data-booking-reset hidden>Đặt lịch khác</button>
		<noscript><p>Vui lòng bật JavaScript để chọn lịch hoặc gọi <a href="tel:<?php echo esc_attr( $info['dien_thoai'] ); ?>"><?php echo esc_html( $info['dien_thoai_hien'] ); ?></a> để được hỗ trợ.</p></noscript>
	</form>
	<?php
}

function eyecare_floating_contact() {
	// Trang đặt lịch đã có form đầy đủ; tránh nút nổi che các trường trên mobile.
	if ( is_page( 'dat-lich-kham' ) ) {
		return;
	}
	$info = eyecare_du_lieu_thuc_the();
	$socials = array(
		'zalo' => array( 'Zalo', 'https://zalo.me/0868899396' ),
		'facebook' => array( 'Facebook', $info['same_as'][0] ?? 'https://www.facebook.com/BenhVienMatHNBN' ),
		'tiktok' => array( 'TikTok', 'https://www.tiktok.com/@bnh.vin.mt.h.ni.b' ),
		'youtube' => array( 'YouTube', 'https://www.youtube.com/@BenhVienMatHNBN' ),
	);
	?>
	<div class="ec-contact" aria-label="Liên hệ bệnh viện">
		<a class="ec-contact__call" href="tel:<?php echo esc_attr( $info['dien_thoai'] ); ?>" aria-label="<?php echo esc_attr( 'Gọi bệnh viện ' . $info['dien_thoai_hien'] ); ?>"><span class="ec-contact__phone-icon"><?php echo eyecare_contact_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span class="ec-contact__call-text">Gọi bệnh viện<strong><?php echo esc_html( $info['dien_thoai_hien'] ); ?></strong></span></a>
		<a class="ec-booking-tab" href="<?php echo esc_url( home_url( '/dat-lich-kham/' ) ); ?>" data-booking-open aria-haspopup="dialog" aria-controls="ec-booking-dialog"><?php echo eyecare_contact_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span>Đặt lịch khám</span></a>
		<nav class="ec-contact__socials" id="ec-contact-socials" aria-label="Mạng xã hội bệnh viện">
			<?php foreach ( $socials as $key => $social ) : ?>
				<a class="ec-contact__social ec-contact__social--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $social[1] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social[0] . ' bệnh viện (mở tab mới)' ); ?>">
					<?php echo 'zalo' === $key ? '<span class="ec-contact__zalo" aria-hidden="true">Zalo</span>' : eyecare_contact_icon( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="ec-contact__label"><?php echo esc_html( $social[0] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>
	</div>
	<dialog class="ec-booking-dialog" id="ec-booking-dialog" aria-labelledby="ec-booking-title" aria-describedby="ec-booking-intro">
		<button type="button" class="ec-booking-dialog__close" data-booking-close aria-label="Đóng form đặt lịch"><?php echo eyecare_contact_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
		<p class="ec-booking-dialog__eyebrow">Bệnh viện Mắt Hà Nội – Bắc Ninh</p>
		<h2 class="ec-booking-dialog__title" id="ec-booking-title">Đặt lịch khám</h2>
		<p class="ec-booking-dialog__intro" id="ec-booking-intro">Chọn thời gian thuận tiện để chúng tôi đón tiếp bạn.</p>
		<?php eyecare_booking_form(); ?>
	</dialog>
	<?php
}
add_action( 'wp_footer', 'eyecare_floating_contact', 15 );
