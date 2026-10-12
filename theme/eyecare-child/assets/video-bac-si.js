(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var link = event.target.closest('.eyecare-video-bs__load');
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
		player.focus();
	});
}());
