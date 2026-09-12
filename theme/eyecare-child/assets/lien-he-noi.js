(function () {
	'use strict';
	const contact = document.querySelector('.ec-contact');
	const toggle = document.querySelector('.ec-contact__social-toggle');
	const socials = document.getElementById('ec-contact-socials');
	function closeSocials(returnFocus) {
		if (!toggle || !socials) return;
		const wasOpen = !socials.hidden;
		socials.hidden = true;
		toggle.setAttribute('aria-expanded', 'false');
		toggle.setAttribute('aria-label', 'Mở các kênh liên hệ');
		if (wasOpen && returnFocus) toggle.focus();
	}
	if (toggle && socials) {
		toggle.hidden = false;
		toggle.addEventListener('click', function () {
			const open = socials.hidden;
			socials.hidden = !open;
			toggle.setAttribute('aria-expanded', String(open));
			toggle.setAttribute('aria-label', open ? 'Đóng các kênh liên hệ' : 'Mở các kênh liên hệ');
			if (open) socials.querySelector('a').focus();
		});
		document.addEventListener('click', event => {
			if (!contact.contains(event.target)) closeSocials(false);
		});
		document.addEventListener('keydown', event => {
			if (event.key === 'Escape') closeSocials(contact.contains(document.activeElement));
		});
	}

	const controllers = new Map();
	const formatter = new Intl.DateTimeFormat('en-CA', {timeZone: 'Asia/Ho_Chi_Minh', year: 'numeric', month: '2-digit', day: '2-digit'});
	function vietnamDate(ms) {
		const parts = Object.fromEntries(formatter.formatToParts(new Date(ms)).map(part => [part.type, part.value]));
		return parts.year + '-' + parts.month + '-' + parts.day;
	}
	function requestId() {
		if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
			const r = crypto.getRandomValues(new Uint8Array(1))[0] & 15;
			return (c === 'x' ? r : (r & 3) | 8).toString(16);
		});
	}
	async function callApi(endpoint, options) {
		const abort = new AbortController();
		const timeout = setTimeout(() => abort.abort(), 20000);
		try {
			const response = await fetch(endpoint, Object.assign({credentials: 'same-origin', cache: 'no-store', signal: abort.signal}, options));
			const result = await response.json();
			if (!response.ok || !result.success) {
				const error = new Error(result.data && result.data.message || 'Chưa thể xử lý yêu cầu. Vui lòng thử lại.');
				error.status = response.status;
				error.code = result.data && result.data.code;
				throw error;
			}
			return result.data;
		} catch (error) {
			if (error.name === 'AbortError' || error instanceof TypeError || error instanceof SyntaxError) {
				throw new Error('Kết nối bị gián đoạn. Vui lòng thử lại hoặc gọi tổng đài.');
			}
			throw error;
		} finally {
			clearTimeout(timeout);
		}
	}

	document.querySelectorAll('[data-ec-booking-form]').forEach(form => {
		const date = form.elements.date;
		const time = form.elements.time;
		const status = form.querySelector('[data-booking-status]');
		const submit = form.querySelector('[data-booking-submit]');
		const retry = form.querySelector('[data-booking-retry]');
		const reset = form.querySelector('[data-booking-reset]');
		let config = null;
		let receivedAt = 0;
		let loading = false;
		let sending = false;
		let complete = false;
		let id = '';
		let pendingBody = null;
		function freezeFields(frozen) {
			form.querySelectorAll('[data-booking-fields] input, [data-booking-fields] select').forEach(field => { field.disabled = frozen; });
		}
		function message(text, state) {
			status.textContent = text;
			status.dataset.state = state || '';
		}
		function now() { return Date.parse(config.now) + performance.now() - receivedAt; }
		function slotsFor(day) {
			return config.slots.filter(slot => Date.parse(day + 'T' + slot + ':00+07:00') > now());
		}
		function refreshTimes() {
			if (!config || pendingBody) return;
			const previous = time.value;
			const slots = date.value && date.value >= date.min && date.value <= date.max ? slotsFor(date.value) : [];
			time.replaceChildren(new Option(slots.length ? 'Chọn giờ khám' : 'Không còn khung giờ phù hợp', ''));
			slots.forEach(slot => time.add(new Option(slot, slot)));
			if (slots.includes(previous)) time.value = previous;
			time.disabled = !slots.length;
			submit.disabled = !slots.length || sending || loading;
		}
		async function load(force) {
			if (loading || sending || complete || pendingBody) return;
			if (config && !force && performance.now() - receivedAt < 60000) {
				refreshTimes();
				return;
			}
			loading = true;
			retry.hidden = true;
			submit.disabled = true;
			date.disabled = true;
			time.disabled = true;
			message('Đang tải khung giờ làm việc…');
			try {
				const url = new URL(form.dataset.endpoint);
				url.searchParams.set('action', 'ec_booking_config');
				config = await callApi(url);
				receivedAt = performance.now();
				date.min = config.today;
				date.max = config.maxDate;
				if (!date.value || date.value < date.min || date.value > date.max) {
					date.value = config.today;
					if (!slotsFor(date.value).length) date.value = vietnamDate(Date.parse(config.today + 'T12:00:00+07:00') + 86400000);
				}
				const hours = form.querySelector('.ec-booking-form__hours strong');
				if (hours) hours.textContent = config.open + '–' + config.close;
				date.disabled = false;
				message('');
			} catch (error) {
				config = null;
				message(error.message, 'error');
				retry.hidden = false;
			} finally {
				loading = false;
				refreshTimes();
			}
		}
		date.addEventListener('change', refreshTimes);
		retry.addEventListener('click', () => load(true));
		reset.addEventListener('click', () => {
			form.reset();
			complete = false;
			id = '';
			pendingBody = null;
			freezeFields(false);
			form.querySelector('[data-booking-fields]').hidden = false;
			submit.hidden = false;
			reset.hidden = true;
			load(true);
		});
		form.addEventListener('submit', async event => {
			event.preventDefault();
			if (sending || complete || !config || (!pendingBody && !form.reportValidity())) return;
			refreshTimes();
			if (!pendingBody && (!time.value || time.disabled)) {
				message('Giờ đã chọn không còn phù hợp. Vui lòng chọn lại ngày hoặc giờ khám.', 'error');
				return;
			}
			sending = true;
			submit.disabled = true;
			submit.textContent = 'Đang gửi yêu cầu…';
			form.setAttribute('aria-busy', 'true');
			retry.hidden = true;
			message('');
			id = id || requestId();
			const body = pendingBody || new FormData(form);
			body.set('action', 'ec_booking_submit');
			body.set('nonce', config.nonce);
			body.set('request_id', id);
			pendingBody = body;
			freezeFields(true);
			try {
				const result = await callApi(form.dataset.endpoint, {method: 'POST', body});
				complete = true;
				pendingBody = null;
				form.querySelector('[data-booking-fields]').hidden = true;
				submit.hidden = true;
				reset.hidden = false;
				reset.textContent = 'Đặt lịch khác';
				message(result.message, 'success');
				status.focus();
			} catch (error) {
				message(error.message, 'error');
				if (error.status >= 400 && error.status < 500 && error.status !== 409) {
					pendingBody = null;
					freezeFields(false);
					retry.hidden = false;
				}
				if (error.code === 'nonce') config = null;
				if (error.code === 'request_conflict') {
					reset.textContent = 'Bắt đầu yêu cầu mới';
					reset.hidden = false;
				}
				status.focus();
			} finally {
				sending = false;
				submit.textContent = pendingBody ? 'Thử gửi lại yêu cầu này' : 'Gửi yêu cầu đặt lịch';
				if (pendingBody) submit.disabled = false;
				form.removeAttribute('aria-busy');
				refreshTimes();
			}
		});
		controllers.set(form, load);
		if (!form.closest('dialog')) load();
	});

	const dialog = document.getElementById('ec-booking-dialog');
	if (!dialog || typeof dialog.showModal !== 'function') return;
	let opener = null;
	document.querySelectorAll('[data-booking-open]').forEach(button => button.addEventListener('click', event => {
		event.preventDefault();
		closeSocials(false);
		opener = button;
		dialog.showModal();
		document.body.classList.add('ec-booking-open');
		const load = controllers.get(dialog.querySelector('form'));
		if (load) load();
	}));
	dialog.querySelector('[data-booking-close]').addEventListener('click', () => dialog.close());
	dialog.addEventListener('click', event => {
		const bounds = dialog.getBoundingClientRect();
		if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
	});
	dialog.addEventListener('close', () => {
		document.body.classList.remove('ec-booking-open');
		if (opener) opener.focus();
	});
}());
