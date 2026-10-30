/*
 * Mã nhúng form đặt lịch Bệnh viện Mắt Hà Nội – Bắc Ninh cho website đối tác.
 * <div data-ec-booking-key="pk_..."></div>
 * <script src="https://mathanoibacninh.com/wp-content/themes/eyecare-child/assets/dat-lich-nhung.js" async></script>
 * Domain của trang phải được bệnh viện khai báo cho khóa này; nếu không, trình duyệt sẽ chặn iframe.
 */
(function () {
	'use strict';
	var script = document.currentScript || document.querySelector('script[src*="dat-lich-nhung.js"]');
	if (!script) return;
	var origin = new URL(script.src).origin;
	var frames = [];
	function mount(box) {
		var key = box.getAttribute('data-ec-booking-key') || '';
		if (box.dataset.ecBookingMounted || !/^pk_[a-f0-9]{32}$/.test(key)) return;
		box.dataset.ecBookingMounted = '1';
		var frame = document.createElement('iframe');
		frame.src = origin + '/?ec_booking_embed=' + encodeURIComponent(key);
		frame.title = 'Đặt lịch khám – Bệnh viện Mắt Hà Nội – Bắc Ninh';
		frame.loading = 'lazy';
		frame.referrerPolicy = 'strict-origin-when-cross-origin';
		frame.style.cssText = 'display:block;width:100%;max-width:640px;height:720px;border:0;border-radius:12px;background:#fff';
		box.appendChild(frame);
		frames.push(frame);
	}
	// Chỉ nhận chiều cao từ đúng iframe và đúng domain bệnh viện.
	window.addEventListener('message', function (event) {
		if (event.origin !== origin || !event.data || event.data.type !== 'ec-booking-height') return;
		var height = Number(event.data.height);
		frames.forEach(function (frame) {
			if (frame.contentWindow === event.source && height > 200 && height < 4000) frame.style.height = height + 'px';
		});
	});
	function scan() { document.querySelectorAll('[data-ec-booking-key]').forEach(mount); }
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
}());
