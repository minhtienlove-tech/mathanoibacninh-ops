(function () {
	'use strict';

	function initializeReviewSlider(slider) {
		var track = slider.querySelector('[data-review-track="true"]');
		var previousButton = slider.querySelector('[data-review-prev="true"]');
		var nextButton = slider.querySelector('[data-review-next="true"]');
		var pauseButton = slider.querySelector('[data-review-pause="true"]');
		var reduceMotion = window.matchMedia
			&& window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var timer = 0;
		var updateFrame = 0;
		var pausedByUser = false;
		var pausedByInteraction = false;

		if (!track) {
			return;
		}

		function getStep() {
			var card = track.querySelector('[data-review-card="true"]');
			var styles = window.getComputedStyle(track);
			var gap = parseFloat(styles.columnGap || styles.gap) || 0;
			return card ? card.getBoundingClientRect().width + gap : track.clientWidth;
		}

		function hasOverflow() {
			return track.scrollWidth - track.clientWidth > 2;
		}

		function updateControls() {
			updateFrame = 0;
			var maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
			var current = Math.max(0, Math.min(track.scrollLeft, maxScroll));
			var overflow = maxScroll > 2;

			slider.classList.toggle('has-no-overflow', !overflow);
			if (previousButton) previousButton.disabled = !overflow || current <= 2;
			if (nextButton) nextButton.disabled = !overflow || current >= maxScroll - 2;
			if (pauseButton) pauseButton.disabled = !overflow || reduceMotion;
		}

		function requestUpdate() {
			if (!updateFrame) updateFrame = window.requestAnimationFrame(updateControls);
		}

		function stopAuto() {
			if (timer) {
				window.clearTimeout(timer);
				timer = 0;
			}
		}

		function canAutoPlay() {
			return !reduceMotion && !pausedByUser && !pausedByInteraction && !document.hidden && hasOverflow();
		}

		function scheduleAuto() {
			stopAuto();
			if (!canAutoPlay()) return;

			timer = window.setTimeout(function () {
				var maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
				if (track.scrollLeft >= maxScroll - 2) {
					track.scrollTo({ left: 0, behavior: 'smooth' });
				} else {
					track.scrollBy({ left: getStep(), behavior: 'smooth' });
				}
				scheduleAuto();
			}, 5200);
		}

		function move(direction) {
			track.scrollBy({ left: getStep() * direction, behavior: reduceMotion ? 'auto' : 'smooth' });
			scheduleAuto();
		}

		if (previousButton) previousButton.addEventListener('click', function () { move(-1); });
		if (nextButton) nextButton.addEventListener('click', function () { move(1); });
		if (pauseButton) {
			pauseButton.addEventListener('click', function () {
				pausedByUser = !pausedByUser;
				pauseButton.setAttribute('aria-pressed', String(pausedByUser));
				pauseButton.setAttribute('aria-label', pausedByUser ? 'Tiếp tục tự động chuyển đánh giá' : 'Tạm dừng tự động chuyển đánh giá');
				pauseButton.textContent = pausedByUser ? '▶' : 'Ⅱ';
				scheduleAuto();
			});
		}

		track.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
				event.preventDefault();
				move(event.key === 'ArrowLeft' ? -1 : 1);
			}
		});
		track.addEventListener('scroll', requestUpdate, { passive: true });
		slider.addEventListener('mouseenter', function () { pausedByInteraction = true; stopAuto(); });
		slider.addEventListener('mouseleave', function () { pausedByInteraction = false; scheduleAuto(); });
		slider.addEventListener('focusin', function () { pausedByInteraction = true; stopAuto(); });
		slider.addEventListener('focusout', function (event) {
			if (slider.contains(event.relatedTarget)) return;
			pausedByInteraction = false;
			scheduleAuto();
		});

		track.querySelectorAll('video').forEach(function (video) {
			var card = video.closest('[data-review-card="true"]');
			video.addEventListener('play', function () {
				if (card) card.classList.add('is-playing');
				pausedByInteraction = true;
				stopAuto();
			});
			video.addEventListener('pause', function () {
				if (card) card.classList.remove('is-playing');
				pausedByInteraction = false;
				scheduleAuto();
			});
			video.addEventListener('ended', function () {
				if (card) card.classList.remove('is-playing');
				pausedByInteraction = false;
				scheduleAuto();
			});
		});

		document.addEventListener('visibilitychange', scheduleAuto);
		if ('ResizeObserver' in window) {
			var resizeObserver = new ResizeObserver(requestUpdate);
			resizeObserver.observe(track);
		} else {
			window.addEventListener('resize', requestUpdate, { passive: true });
		}

		requestUpdate();
		scheduleAuto();
	}

	function initializeReviewReveal() {
		var section = document.querySelector('.eyecare-chu__danh-gia');
		if (!section) return;

		var reduceMotion = window.matchMedia
			&& window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (reduceMotion || !('IntersectionObserver' in window)) {
			section.classList.add('is-visible');
			return;
		}

		section.classList.add('is-motion-ready');
		var observer = new IntersectionObserver(function (entries) {
			if (!entries[0].isIntersecting) return;
			section.classList.add('is-visible');
			observer.disconnect();
		}, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
		observer.observe(section);
	}

	function initializeReviews() {
		document.querySelectorAll('[data-review-slider="true"]').forEach(initializeReviewSlider);
		initializeReviewReveal();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeReviews, { once: true });
	} else {
		initializeReviews();
	}
})();
