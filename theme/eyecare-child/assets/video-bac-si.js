(function () {
	'use strict';
	var carousels = new WeakMap();

	function setupCarousel(section) {
		var track = section.querySelector('.eyecare-video-bs__grid');
		var viewport = section.querySelector('.eyecare-video-bs__viewport');
		var slides = Array.from(track ? track.children : []);
		var dots = Array.from(section.querySelectorAll('.eyecare-video-bs__dot'));
		var previous = section.querySelector('.eyecare-video-bs__previous');
		var next = section.querySelector('.eyecare-video-bs__next');
		var pauseButton = section.querySelector('.eyecare-video-bs__pause');
		var announcement = section.querySelector('.eyecare-video-bs__announcement');
		if (!viewport || slides.length < 2 || dots.length !== slides.length || !previous || !next || !pauseButton) {
			return;
		}

		var index = -1;
		var timer;
		var hovered = false;
		var focused = false;
		var inView = true;
		var paused = false;
		var playbackOpen = false;
		var touching = false;
		var motion = window.matchMedia('(prefers-reduced-motion: reduce)');

		function stopPlayer() {
			var stage = slides[index].querySelector('.eyecare-video-bs__stage');
			if (stage && stage._eyecarePosterLink) {
				stage.replaceChildren(stage._eyecarePosterLink);
				stage._eyecarePosterLink = null;
			}
			playbackOpen = false;
		}

		function updatePauseButton() {
			var stopped = paused || playbackOpen;
			pauseButton.textContent = stopped ? 'Tiếp tục' : 'Tạm dừng';
			pauseButton.setAttribute('aria-pressed', stopped ? 'true' : 'false');
			pauseButton.setAttribute('aria-label', playbackOpen ? 'Dừng video và tiếp tục tự chuyển slide' : (stopped ? 'Tiếp tục tự chuyển video' : 'Tạm dừng tự chuyển video'));
		}

		function schedule() {
			window.clearTimeout(timer);
			if (paused || playbackOpen || hovered || focused || touching || !inView || document.hidden || motion.matches) {
				return;
			}
			timer = window.setTimeout(function () {
				show((index + 1) % slides.length, false);
			}, 11000);
		}

		function show(newIndex, manual) {
			if (newIndex === index) {
				return;
			}
			if (playbackOpen) {
				stopPlayer();
			}
			index = newIndex;
			track.style.transform = 'translateX(-' + (index * 100) + '%)';
			slides.forEach(function (slide, position) {
				var active = position === index;
				slide.inert = !active;
				slide.setAttribute('aria-hidden', active ? 'false' : 'true');
			});
			dots.forEach(function (dot, position) {
				if (position === index) {
					dot.setAttribute('aria-current', 'true');
				} else {
					dot.removeAttribute('aria-current');
				}
			});
			if (manual) {
				updatePauseButton();
				if (announcement) {
					announcement.textContent = 'Video ' + (index + 1) + ' / ' + slides.length + ': ' + slides[index].querySelector('h3').textContent;
				}
			}
			schedule();
		}

		previous.addEventListener('click', function () {
			show((index + slides.length - 1) % slides.length, true);
		});
		next.addEventListener('click', function () {
			show((index + 1) % slides.length, true);
		});
		dots.forEach(function (dot, position) {
			dot.addEventListener('click', function () {
				show(position, true);
			});
		});
		pauseButton.addEventListener('click', function () {
			if (playbackOpen) {
				stopPlayer();
				paused = false;
			} else {
				paused = !paused;
			}
			updatePauseButton();
			schedule();
		});

		section.addEventListener('pointerenter', function (event) {
			if (event.pointerType === 'mouse') {
				hovered = true;
				schedule();
			}
		});
		section.addEventListener('pointerleave', function (event) {
			if (event.pointerType === 'mouse') {
				hovered = false;
				schedule();
			}
		});
		section.addEventListener('focusin', function () {
			focused = true;
			schedule();
		});
		section.addEventListener('focusout', function (event) {
			if (!section.contains(event.relatedTarget)) {
				focused = false;
				schedule();
			}
		});
		document.addEventListener('visibilitychange', schedule);
		if (typeof IntersectionObserver !== 'undefined') {
			var observer = new IntersectionObserver(function (entries) {
				inView = entries[0].isIntersecting;
				schedule();
			}, { threshold: 0.2 });
			observer.observe(section);
		}
		if (motion.addEventListener) {
			motion.addEventListener('change', schedule);
		} else {
			motion.addListener(schedule);
		}

		var touchX = null;
		var touchY = null;
		viewport.addEventListener('touchstart', function (event) {
			touching = true;
			schedule();
			if (event.touches.length === 1) {
				touchX = event.touches[0].clientX;
				touchY = event.touches[0].clientY;
			}
		}, { passive: true });
		viewport.addEventListener('touchend', function (event) {
			touching = false;
			schedule();
			if (touchX === null || !event.changedTouches.length) {
				return;
			}
			var distanceX = event.changedTouches[0].clientX - touchX;
			var distanceY = event.changedTouches[0].clientY - touchY;
			touchX = null;
			touchY = null;
			if (Math.abs(distanceX) < 50 || Math.abs(distanceX) < Math.abs(distanceY) * 1.4) {
				return;
			}
			event.preventDefault();
			show((index + (distanceX < 0 ? 1 : slides.length - 1)) % slides.length, true);
		}, { passive: false });
		viewport.addEventListener('touchcancel', function () {
			touching = false;
			touchX = null;
			touchY = null;
			schedule();
		});

		carousels.set(section, {
			videoOpened: function (stage, link) {
				stage._eyecarePosterLink = link;
				playbackOpen = true;
				updatePauseButton();
				schedule();
			}
		});
		section.classList.add('is-ready');
		show(0, false);
		updatePauseButton();
		schedule();
	}

	document.querySelectorAll('.eyecare-video-bs--carousel').forEach(setupCarousel);

	document.addEventListener('click', function (event) {
		var link = event.target instanceof Element ? event.target.closest('.eyecare-video-bs__load') : null;
		if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}

		var stage = link.closest('.eyecare-video-bs__stage');
		var kind = link.getAttribute('data-eyecare-video-kind');
		var source = link.getAttribute('data-eyecare-video-src');
		var title = link.getAttribute('data-eyecare-video-title') || 'Video bác sĩ';
		if (!stage || !source) {
			return;
		}

		var player;
		if (kind === 'embed') {
			player = document.createElement('iframe');
			player.title = title;
			player.src = source;
			player.setAttribute('allow', 'encrypted-media; gyroscope; picture-in-picture; web-share');
			player.setAttribute('allowfullscreen', '');
			player.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
		} else if (kind === 'mp4') {
			player = document.createElement('video');
			player.controls = true;
			player.preload = 'none';
			player.playsInline = true;
			player.setAttribute('aria-label', title);
			var media = document.createElement('source');
			media.src = source;
			media.type = 'video/mp4';
			player.appendChild(media);
		} else {
			return;
		}

		player.className = 'eyecare-video-bs__player';
		player.tabIndex = -1;
		event.preventDefault();
		if (link.getAttribute('data-eyecare-video-portrait') === '1') {
			stage.classList.add('is-portrait');
		}
		stage.replaceChildren(player);
		var carousel = stage.closest('.eyecare-video-bs--carousel');
		if (carousel && carousels.has(carousel)) {
			carousels.get(carousel).videoOpened(stage, link);
		}
		player.focus();
	});
}());
