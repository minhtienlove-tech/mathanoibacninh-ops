(function () {
	'use strict';

	var items = Array.prototype.slice.call(document.querySelectorAll('[data-doctor-profile="true"]'));
	if (!items.length) {
		return;
	}

	function setOpen(item, open) {
		var button = item.querySelector('.eyecare-doctor-profiles__toggle');
		var panel = item.querySelector('.eyecare-doctor-profiles__panel');
		if (!button || !panel) {
			return;
		}
		item.classList.toggle('is-open', open);
		button.setAttribute('aria-expanded', String(open));
		panel.hidden = !open;
	}

	// Mở đúng bác sĩ theo #bac-si-..., cuộn các hồ sơ còn lại.
	function openFromHash(scroll) {
		var id = decodeURIComponent((window.location.hash || '').slice(1));
		var target = id ? document.getElementById(id) : null;
		if (!target || items.indexOf(target) === -1) {
			target = null;
		}
		items.forEach(function (item, index) {
			setOpen(item, target ? item === target : index === 0);
		});
		if (target && scroll) {
			target.scrollIntoView({ block: 'start' });
		}
	}

	// Bấm lại đúng bác sĩ đang có trên URL không phát sinh hashchange, nên tự mở.
	document.addEventListener('click', function (event) {
		var link = event.target.closest('a[href*="#bac-si-"]');
		if (!link || link.hash !== window.location.hash || link.pathname !== window.location.pathname) {
			return;
		}
		event.preventDefault();
		openFromHash(true);
	});

	document.addEventListener('click', function (event) {
		var button = event.target.closest('.eyecare-doctor-profiles__toggle');
		if (!button) {
			return;
		}
		var item = button.closest('[data-doctor-profile="true"]');
		var open = button.getAttribute('aria-expanded') !== 'true';
		items.forEach(function (other) {
			setOpen(other, other === item ? open : false);
		});
		if (open && item.id && window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + item.id);
		}
	});

	window.addEventListener('hashchange', function () {
		openFromHash(true);
	});

	openFromHash(true);
})();
