// SDK Node.js (18+) cho API đặt lịch Bệnh viện Mắt Hà Nội – Bắc Ninh. Không cần thư viện ngoài.
// Tài liệu: https://mathanoibacninh.com/api-dat-lich/ (Markdown: https://mathanoibacninh.com/api-dat-lich.md)
import { createHmac, randomUUID } from 'node:crypto';

export class EcBookingError extends Error {
	constructor(status, body) {
		super(body && body.message || 'HTTP ' + status);
		this.status = status;
		this.code = body && body.code;
		this.appointment = body && body.appointment;
	}
}

export class EcBookingClient {
	/** @param {{clientId: string, secret: string, baseUrl?: string, timeoutMs?: number}} options */
	constructor({ clientId, secret, baseUrl = 'https://mathanoibacninh.com/wp-json', timeoutMs = 20000 }) {
		this.clientId = clientId;
		this.secret = secret;
		this.baseUrl = baseUrl.replace(/\/+$/, '');
		this.timeoutMs = timeoutMs;
	}

	async request(method, path, payload) {
		const route = '/ec-booking/v1' + path;
		const body = payload === undefined ? '' : JSON.stringify(payload);
		const timestamp = String(Math.floor(Date.now() / 1000));
		const signature = createHmac('sha256', this.secret).update(timestamp + '\n' + method + '\n' + route + '\n' + body).digest('hex');
		const response = await fetch(this.baseUrl + route, {
			method,
			headers: { 'Content-Type': 'application/json', 'X-EC-Key': this.clientId, 'X-EC-Timestamp': timestamp, 'X-EC-Signature': signature },
			body: body || undefined,
			signal: AbortSignal.timeout(this.timeoutMs),
		});
		const result = await response.json().catch(() => null);
		if (!response.ok || !result || !result.ok) throw new EcBookingError(response.status, result);
		return result;
	}

	/** Khung giờ còn nhận trong 14 ngày tới. */
	slots() { return this.request('GET', '/slots'); }

	/**
	 * Đặt lịch. Giữ nguyên requestId khi thử lại để không tạo lịch trùng.
	 * @param {{name: string, phone: string, date: string, time: string, consent: true, patientZaloConsent?: boolean, requestId?: string}} booking
	 */
	async createAppointment({ name, phone, date, time, consent, patientZaloConsent = false, requestId = randomUUID() }) {
		const result = await this.request('POST', '/appointments', { name, phone, date, time, consent: consent === true, patient_zalo_consent: patientZaloConsent, request_id: requestId });
		return result.appointment;
	}

	/** Trạng thái lịch do chính đối tác này tạo. */
	async appointment(id) { return (await this.request('GET', '/appointments/' + Number(id))).appointment; }
}
