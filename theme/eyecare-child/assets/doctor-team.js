(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var button = event.target.closest('[data-doctor-toggle="true"]');

		if (!button) {
			return;
		}

		var card = button.closest('[data-doctor-card="true"]');
		var label = button.querySelector('span');

		if (!card || !label) {
			return;
		}

		var isExpanded = button.getAttribute('aria-expanded') === 'true';
		var nextExpanded = !isExpanded;
		var nextLabel = nextExpanded
			? button.getAttribute('data-collapse-label')
			: button.getAttribute('data-expand-label');

		card.classList.toggle('is-expanded', nextExpanded);
		button.setAttribute('aria-expanded', String(nextExpanded));
		label.textContent = nextLabel || '';
	});

	function formatNumber(value) {
		try {
			return new Intl.NumberFormat('vi-VN').format(value);
		} catch (error) {
			return String(value);
		}
	}

	function animateCount(element) {
		if (!element || element.getAttribute('data-doctor-count-animated') === 'true') {
			return;
		}

		var target = parseInt(element.getAttribute('data-doctor-count'), 10);
		var suffix = element.getAttribute('data-doctor-count-suffix') || '';

		if (!Number.isFinite(target) || target < 0) {
			return;
		}

		element.setAttribute('data-doctor-count-animated', 'true');
		var startedAt = null;
		var duration = target >= 1000 ? 1400 : 1050;

		function draw(timestamp) {
			if (startedAt === null) {
				startedAt = timestamp;
			}

			var progress = Math.min((timestamp - startedAt) / duration, 1);
			var eased = 1 - Math.pow(1 - progress, 3);
			var current = Math.floor(target * eased);
			element.textContent = formatNumber(current) + suffix;

			if (progress < 1) {
				window.requestAnimationFrame(draw);
			} else {
				element.textContent = formatNumber(target) + suffix;
			}
		}

		window.requestAnimationFrame(draw);
	}

	function initializeDoctorBadges(section) {
		var reduceMotion = window.matchMedia
			&& window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		if (reduceMotion) {
			return;
		}

		section.querySelectorAll('[data-doctor-badge="true"]').forEach(function (badge, badgeIndex) {
			var items = [];
			var card = badge.closest('[data-doctor-card="true"]');
			var index = 0;
			var timer = 0;
			var swapTimer = 0;
			var paused = false;
			var firstCycle = true;

			try {
				items = JSON.parse(badge.getAttribute('data-doctor-badges') || '[]');
			} catch (error) {
				items = [];
			}

			items = Array.isArray(items) ? items.filter(Boolean) : [];
			if (items.length < 2 || !card) {
				return;
			}

			function stop() {
				if (timer) {
					window.clearTimeout(timer);
					timer = 0;
				}
				if (swapTimer) {
					window.clearTimeout(swapTimer);
					swapTimer = 0;
					badge.textContent = items[index];
					badge.classList.remove('is-changing');
				}
			}

			function schedule() {
				stop();
				if (paused || document.hidden) {
					return;
				}

				var delay = firstCycle ? 3200 + (badgeIndex * 650) : 3600;
				timer = window.setTimeout(function () {
					firstCycle = false;
					index = (index + 1) % items.length;
					badge.classList.add('is-changing');
					swapTimer = window.setTimeout(function () {
						badge.textContent = items[index];
						badge.classList.remove('is-changing');
						schedule();
					}, 180);
				}, delay);
			}

			card.addEventListener('mouseenter', function () {
				paused = true;
				stop();
			});
			card.addEventListener('mouseleave', function () {
				paused = false;
				schedule();
			});
			card.addEventListener('focusin', function () {
				paused = true;
				stop();
			});
			card.addEventListener('focusout', function (event) {
				if (card.contains(event.relatedTarget)) {
					return;
				}
				paused = false;
				schedule();
			});
			document.addEventListener('visibilitychange', schedule);

			schedule();
		});
	}

	function initializeDoctorSliders() {
			document.querySelectorAll('[data-doctor-slider="true"]').forEach(function (slider) {
			var track = slider.querySelector('[data-doctor-track="true"]');
			var previousButton = slider.querySelector('[data-doctor-prev="true"]');
			var nextButton = slider.querySelector('[data-doctor-next="true"]');
			var pauseButton = slider.querySelector('[data-doctor-pause="true"]');
			var pauseIcon = slider.querySelector('[data-doctor-pause-icon="true"]');
			var progress = slider.querySelector('[data-doctor-progress="true"]');
			var reduceMotion = window.matchMedia
				&& window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			var updateFrame = 0;
			var autoTimer = 0;
			var motionTimer = 0;
			var touchResumeTimer = 0;
			var userPaused = false;
			var interactionPaused = false;

			if (!track) {
				return;
			}

			function getStep() {
				var card = track.querySelector('[data-doctor-card="true"]');
				var styles = window.getComputedStyle(track);
				var gap = parseFloat(styles.columnGap || styles.gap) || 0;

				return card ? card.getBoundingClientRect().width + gap : track.clientWidth;
			}

			function updateControls() {
				updateFrame = 0;
				var maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
				var current = Math.max(0, Math.min(track.scrollLeft, maxScroll));
				var hasOverflow = maxScroll > 2;

				slider.classList.toggle('has-no-overflow', !hasOverflow);

				if (previousButton) {
					previousButton.disabled = !hasOverflow || current <= 2;
				}

				if (nextButton) {
					nextButton.disabled = !hasOverflow || current >= maxScroll - 2;
				}

				if (pauseButton) {
					pauseButton.disabled = !hasOverflow || reduceMotion;
				}

				if (progress) {
					var thumbWidth = hasOverflow
						? Math.max(18, Math.min(100, (track.clientWidth / track.scrollWidth) * 100))
						: 100;
					var ratio = hasOverflow ? current / maxScroll : 0;
					progress.style.width = thumbWidth + '%';
					progress.style.left = (ratio * (100 - thumbWidth)) + '%';
				}
			}

			function stopAuto() {
				if (autoTimer) {
					window.clearTimeout(autoTimer);
					autoTimer = 0;
				}
				if (motionTimer) {
					window.clearTimeout(motionTimer);
					motionTimer = 0;
					slider.classList.remove('is-auto-moving');
				}
			}

			function canAutoPlay() {
				return !reduceMotion
					&& !userPaused
					&& !interactionPaused
					&& !document.hidden
					&& track.scrollWidth - track.clientWidth > 2;
			}

			function scheduleAuto() {
				stopAuto();

				if (!canAutoPlay()) {
					return;
				}

				autoTimer = window.setTimeout(function () {
					var maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
					var atEnd = track.scrollLeft >= maxScroll - 2;
					slider.classList.add('is-auto-moving');

					if (atEnd) {
						track.scrollTo({ left: 0, behavior: 'smooth' });
					} else {
						track.scrollBy({ left: getStep(), behavior: 'smooth' });
					}

					scheduleAuto();

					/* Lên lịch lượt kế tiếp trước, rồi mới giữ lớp hiệu ứng hiện tại;
					 * scheduleAuto() gọi stopAuto() nên thứ tự này tránh xoá hiệu ứng. */
					motionTimer = window.setTimeout(function () {
						slider.classList.remove('is-auto-moving');
						motionTimer = 0;
					}, 900);
				}, window.innerWidth >= 901 ? 3200 : 4200);
			}

			function requestUpdate() {
				if (updateFrame) {
					return;
				}
				updateFrame = window.requestAnimationFrame(updateControls);
			}

			function move(direction) {
				track.scrollBy({
					left: getStep() * direction,
					behavior: reduceMotion ? 'auto' : 'smooth'
				});
				scheduleAuto();
			}

			if (previousButton) {
				previousButton.addEventListener('click', function () {
					move(-1);
				});
			}

			if (nextButton) {
				nextButton.addEventListener('click', function () {
					move(1);
				});
			}

			if (pauseButton) {
				pauseButton.addEventListener('click', function () {
					userPaused = !userPaused;
					pauseButton.setAttribute('aria-pressed', String(userPaused));
					pauseButton.setAttribute('aria-label', userPaused
						? 'Tiếp tục tự động chuyển bác sĩ'
						: 'Tạm dừng tự động chuyển bác sĩ');

					if (pauseIcon) {
						pauseIcon.textContent = userPaused ? '▶' : 'Ⅱ';
					}

					scheduleAuto();
				});
			}

			track.addEventListener('keydown', function (event) {
				if (event.key === 'ArrowLeft') {
					event.preventDefault();
					move(-1);
				} else if (event.key === 'ArrowRight') {
					event.preventDefault();
					move(1);
				}
			});

			track.addEventListener('scroll', requestUpdate, { passive: true });

			slider.addEventListener('mouseenter', function () {
				interactionPaused = true;
				stopAuto();
			});

			slider.addEventListener('mouseleave', function () {
				interactionPaused = false;
				scheduleAuto();
			});

			slider.addEventListener('focusin', function () {
				interactionPaused = true;
				stopAuto();
			});

			slider.addEventListener('focusout', function (event) {
				if (slider.contains(event.relatedTarget)) {
					return;
				}
				interactionPaused = false;
				scheduleAuto();
			});

			track.addEventListener('pointerdown', function (event) {
				if (event.pointerType !== 'touch') {
					return;
				}

				interactionPaused = true;
				stopAuto();
				window.clearTimeout(touchResumeTimer);
				touchResumeTimer = window.setTimeout(function () {
					interactionPaused = false;
					scheduleAuto();
				}, 7000);
			}, { passive: true });

			document.addEventListener('visibilitychange', scheduleAuto);

			if ('ResizeObserver' in window) {
				var resizeObserver = new ResizeObserver(requestUpdate);
				resizeObserver.observe(track);
			} else {
				window.addEventListener('resize', requestUpdate, { passive: true });
			}

			window.addEventListener('load', requestUpdate, { once: true });
			requestUpdate();
			scheduleAuto();
		});
	}

	function initializePremiumEffects() {
		var section = document.querySelector('.eyecare-doi-ngu');

		if (!section) {
			return;
		}

		var reduceMotion = window.matchMedia
			&& window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		if (reduceMotion || !('IntersectionObserver' in window)) {
			section.classList.add('is-visible');
			if (!reduceMotion) {
				section.querySelectorAll('[data-doctor-count]').forEach(animateCount);
				initializeDoctorBadges(section);
			}
			return;
		}

		section.classList.add('eyecare-doi-ngu--motion-ready');

		var observer = new IntersectionObserver(function (entries) {
			if (!entries[0].isIntersecting) {
				return;
			}

			section.classList.add('is-visible');
			section.querySelectorAll('[data-doctor-count]').forEach(animateCount);
			initializeDoctorBadges(section);
			observer.disconnect();
		}, {
			threshold: 0.12,
			rootMargin: '0px 0px -8% 0px'
		});

		observer.observe(section);
	}

	function initializeDoctorTeam() {
		initializeDoctorSliders();
		initializePremiumEffects();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeDoctorTeam, { once: true });
	} else {
		initializeDoctorTeam();
	}
})();
