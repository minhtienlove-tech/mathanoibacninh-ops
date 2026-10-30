(function () {
	'use strict';
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
	// Mã thiết bị ngẫu nhiên lưu trong trình duyệt; máy chủ chỉ lưu HMAC để chống spam.
	function deviceId() {
		try {
			let value = localStorage.getItem('ec_booking_device');
			if (!/^[a-f0-9-]{36}$/.test(value || '')) {
				value = requestId();
				localStorage.setItem('ec_booking_device', value);
			}
			return value;
		} catch (error) {
			return '';
		}
	}
	async function sha256(text) {
		if (!window.crypto || !crypto.subtle || !window.TextEncoder) return '';
		const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
		return Array.from(new Uint8Array(digest), b => b.toString(16).padStart(2, '0')).join('');
	}
	// Dấu vân tay phần cứng: màn hình, số nhân CPU, bộ nhớ, múi giờ, GPU. Không chứa dữ liệu cá nhân.
	async function deviceHash() {
		const parts = [screen.width + 'x' + screen.height + 'x' + screen.colorDepth, window.devicePixelRatio || 1, navigator.hardwareConcurrency || 0, navigator.deviceMemory || 0, navigator.maxTouchPoints || 0, navigator.platform || '', navigator.language || '', Intl.DateTimeFormat().resolvedOptions().timeZone || ''];
		try {
			const gl = document.createElement('canvas').getContext('webgl');
			const info = gl && gl.getExtension('WEBGL_debug_renderer_info');
			if (info) parts.push(gl.getParameter(info.UNMASKED_RENDERER_WEBGL));
		} catch (error) { /* GPU không đọc được thì bỏ qua. */ }
		try { return await sha256(parts.join('|')); } catch (error) { return ''; }
	}
	function normalizePhone(raw) {
		let value = String(raw || '').replace(/[０-９]/g, d => String.fromCharCode(d.charCodeAt(0) - 0xFEE0)).replace(/[\s().\/-]+/g, '').replace(/^00/, '+');
		if (/^[35789][0-9]{8}$/.test(value)) value = '0' + value;
		const vn = value.match(/^\+?840?([1-9][0-9]{8,9})$/);
		return vn ? '0' + vn[1] : value;
	}
	function isVnMobile(value) { return /^0[35789][0-9]{8}$/.test(value); }
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
		const phone = form.elements.phone;
		const phoneHint = form.querySelector('[data-booking-phone-hint]');
		const zaloRow = form.querySelector('[data-booking-zalo]');
		const zaloBox = form.elements.patient_zalo_consent;
		// Ô nhận tin Zalo chỉ hiện với số di động Việt Nam; số khác thì ẩn và bỏ chọn.
		function syncPhone() {
			const value = normalizePhone(phone.value);
			const mobile = isVnMobile(value);
			if (zaloRow) zaloRow.hidden = !mobile;
			if (zaloBox && !mobile) zaloBox.checked = false;
			phone.setCustomValidity('');
			if (phoneHint) phoneHint.textContent = '';
			if (!phone.value.trim()) return;
			if (!/^(0[1-9][0-9]{8,9}|\+[1-9][0-9]{7,14})$/.test(value)) {
				phone.setCustomValidity('Số điện thoại chưa đúng. Ví dụ: 0912 345 678.');
			} else if (phoneHint && value !== phone.value.replace(/\s+/g, '')) {
				phoneHint.textContent = 'Bệnh viện sẽ gọi số ' + value + '.';
			}
		}
		phone.addEventListener('input', syncPhone);
		syncPhone();
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
				form.dataset.formToken = config.formToken || '';
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
			syncPhone();
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
			if (!pendingBody) {
				body.set('phone', normalizePhone(phone.value));
				body.set('form_token', form.dataset.formToken || '');
				body.set('device_id', deviceId());
				body.set('device_hash', await deviceHash());
			}
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
				if (error.code === 'duplicate') {
					// Số này đã có lịch cùng ngày: hiển thị lịch cũ, không gửi lại.
					pendingBody = null;
					complete = true;
					message(error.message, 'warning');
					form.querySelector('[data-booking-fields]').hidden = true;
					submit.hidden = true;
					reset.textContent = 'Đặt lịch cho người khác';
					reset.hidden = false;
				}
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
