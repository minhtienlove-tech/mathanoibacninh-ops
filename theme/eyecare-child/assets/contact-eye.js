(function () {
	'use strict';

	var eye = document.querySelector('[data-eyecare-contact-eye]');
	if (!eye) {
		return;
	}

	var slides = Array.prototype.slice.call(eye.querySelectorAll('[data-eyecare-contact-eye-slide]'));
	var eyebrow = eye.querySelector('[data-eyecare-contact-eye-eyebrow]');
	var label = eye.querySelector('[data-eyecare-contact-eye-label]');
	var toggle = eye.querySelector('[data-eyecare-contact-eye-toggle]');
	var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
	var active = 0;
	var timer = 0;
	var paused = false;

	if (slides.length < 2 || reduceMotion.matches) {
		if (toggle) {
			toggle.hidden = true;
		}
		return;
	}

	function showNext() {
		if (paused) {
			return;
		}
		eye.classList.add('is-blinking');

		window.setTimeout(function () {
			active = (active + 1) % slides.length;
			slides.forEach(function (slide, index) {
				slide.classList.toggle('is-active', index === active);
			});
			if (eyebrow && label) {
				eyebrow.textContent = slides[active].getAttribute('data-eyecare-contact-eye-eyebrow') || 'Không gian bệnh viện';
				label.textContent = slides[active].getAttribute('data-eyecare-contact-eye-label-text') || 'Rõ ràng trước khi đến bệnh viện';
			}
		}, 250);

		window.setTimeout(function () {
			eye.classList.remove('is-blinking');
			schedule();
		}, 620);
	}

	function schedule() {
		window.clearTimeout(timer);
		if (document.hidden || reduceMotion.matches || paused) {
			return;
		}
		timer = window.setTimeout(showNext, 5200);
	}

	if (toggle) {
		toggle.addEventListener('click', function () {
			paused = !paused;
			toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
			toggle.querySelector('span').textContent = paused ? 'Tiếp tục chuyển động' : 'Tạm dừng chuyển động';
			if (paused) {
				window.clearTimeout(timer);
				eye.classList.remove('is-blinking');
			} else {
				schedule();
			}
		});
	}

	document.addEventListener('visibilitychange', schedule);
	reduceMotion.addEventListener('change', schedule);
	schedule();
}());
