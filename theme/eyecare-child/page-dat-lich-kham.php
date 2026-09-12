<?php
/** The published booking URL shares the floating appointment workflow. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<section class="ec-booking-page eyecare-khung-trang" aria-labelledby="ec-booking-page-title">
	<div class="ec-booking-page__card">
		<p class="ec-booking-dialog__eyebrow">Bệnh viện Mắt Hà Nội – Bắc Ninh</p>
		<h1 class="ec-booking-dialog__title" id="ec-booking-page-title">Đặt lịch khám</h1>
		<p class="ec-booking-dialog__intro">Chọn ngày, giờ khám và để lại số điện thoại. Bệnh viện sẽ liên hệ xác nhận lịch hẹn với bạn.</p>
		<?php eyecare_booking_form( 'page' ); ?>
	</div>
</section>
<?php get_footer(); ?>
