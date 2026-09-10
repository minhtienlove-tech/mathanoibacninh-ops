(function () {
	'use strict';

	function setOpen(card, open) {
		var toggle = card.querySelector('[data-lv-toggle="true"]');
		var detail = card.querySelector('.eyecare-chu__lv-chi-tiet');

		if (!toggle || !detail) {
			return;
		}

		card.classList.toggle('is-open', open);
		toggle.setAttribute('aria-expanded', String(open));
		toggle.setAttribute('aria-hidden', String(open));
		toggle.setAttribute('tabindex', open ? '-1' : '0');
		toggle.setAttribute('title', open ? 'Đã mở chi tiết' : 'Mở chi tiết');

		if (open) {
			detail.hidden = false;
		} else {
			detail.hidden = true;
		}
	}

	document.addEventListener('click', function (event) {
		var toggle = event.target.closest('[data-lv-toggle="true"]');
		var close = event.target.closest('[data-lv-close="true"]');
		var card = (toggle || close) && (toggle || close).closest('[data-lv-card="true"]');

		if (!card) {
			return;
		}

		event.preventDefault();
		setOpen(card, toggle ? card.classList.contains('is-open') === false : false);

		if (close) {
			var trigger = card.querySelector('[data-lv-toggle="true"]');
			if (trigger) {
				trigger.focus();
			}
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') {
			return;
		}

		var openCard = event.target.closest('[data-lv-card="true"].is-open');
		if (!openCard) {
			return;
		}

		setOpen(openCard, false);
		var trigger = openCard.querySelector('[data-lv-toggle="true"]');
		if (trigger) {
			trigger.focus();
		}
	});
})();
