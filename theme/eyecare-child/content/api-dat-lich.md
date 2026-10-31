# API đặt lịch khám và form nhúng

**Tài liệu nội bộ, không lập chỉ mục tìm kiếm.** Bản đọc: [https://mathanoibacninh.com/api-dat-lich/](https://mathanoibacninh.com/api-dat-lich/) · Bản Markdown cho AI: [https://mathanoibacninh.com/api-dat-lich.md](https://mathanoibacninh.com/api-dat-lich.md)

Dành cho đối tác kết nối với hệ thống đặt lịch của Bệnh viện Mắt Hà Nội – Bắc Ninh, ví dụ Zalo bot, phần mềm phòng khám vệ tinh hoặc website đối tác.

Lịch gửi qua API hoặc form nhúng được kiểm tra giống form trên website: chuẩn hóa họ tên và số điện thoại, nhận lịch đến 16:00, mỗi số điện thoại chỉ có một lịch trong một ngày. Lịch mới cũng được đẩy sang Google Sheet và gửi thông báo Zalo/email như lịch đặt trên website.

## Cấp khóa

Quản trị viên vào **WordPress → Đặt lịch khám → API và form nhúng** để tạo đối tác. Mỗi đối tác có:

| Khóa | Dùng cho | Bí mật? |
|---|---|---|
| Mã đối tác (`id`) | Header `X-EC-Key` của API | Không |
| Khóa công khai `pk_…` | Mã nhúng form | Không |
| Khóa bí mật `sk_…` | Ký yêu cầu API | **Có.** Chỉ hiện một lần lúc tạo hoặc đổi khóa |

Khóa bí mật chỉ đặt trên máy chủ đối tác, không đưa vào trình duyệt, ứng dụng di động hay mã nguồn công khai. Nếu nghi lộ khóa, bấm **Đổi khóa bí mật**: khóa cũ ngừng hoạt động ngay.

## Form nhúng

Bệnh viện khai báo domain của website đối tác (bắt buộc `https://`, có thể dùng `https://*.doitac.vn`). Đối tác chèn đoạn mã hiển thị trong trang quản trị:

```html
<div data-ec-booking-key="pk_..."></div>
<script src="https://mathanoibacninh.com/wp-content/themes/eyecare-child/assets/dat-lich-nhung.js" async></script>
```

Script tạo iframe và tự chỉnh chiều cao theo nội dung form. Trình duyệt chặn iframe trên domain chưa khai báo (CSP `frame-ancestors`). Form nhúng dùng giới hạn chống spam theo IP và thiết bị như website. Trong quản trị, lịch có nguồn là "Form nhúng: tên đối tác".

## API máy chủ

Gốc: `https://mathanoibacninh.com/wp-json/ec-booking/v1`. Dữ liệu gửi và nhận là JSON UTF-8, giờ theo `Asia/Ho_Chi_Minh`.

### Ký yêu cầu

Mỗi yêu cầu gửi kèm ba header:

- `X-EC-Key`: mã đối tác.
- `X-EC-Timestamp`: thời điểm hiện tại tính bằng giây Unix. Lệch quá 5 phút so với máy chủ bệnh viện thì bị từ chối, nên máy chủ đối tác cần đồng bộ giờ NTP.
- `X-EC-Signature`: HMAC-SHA256 dạng hex, khóa là khóa bí mật, nội dung ký là:

```
{timestamp}\n{METHOD}\n{route}\n{thân yêu cầu nguyên văn}
```

`route` bắt đầu từ `/ec-booking/v1`, ví dụ `/ec-booking/v1/appointments`. Yêu cầu GET có thân rỗng. Phải ký đúng chuỗi byte sẽ gửi: tạo JSON một lần, ký chuỗi đó rồi gửi chính chuỗi đó.

Một IP ký sai 30 lần trong một giờ sẽ bị khóa tạm thời (HTTP 429).

### Kiểm tra cách ký

Dùng ví dụ này để kiểm tra mã ký trước khi gọi thật. Khóa mẫu không dùng được trên máy chủ.

- Khóa bí mật: `sk_test_example`
- `X-EC-Timestamp`: `1800000000`
- Phương thức và route: `POST` `/ec-booking/v1/appointments`
- Thân yêu cầu (JSON gọn, không khoảng trắng, chữ tiếng Việt giữ nguyên UTF-8, không đổi thành `\u...`):

```
{"name":"Phạm Văn Bình","phone":"0987654321","date":"2026-10-31","time":"08:00","consent":true,"request_id":"zalo-msg-123456"}
```

- Chữ ký đúng: `81840a02c2a93fca4d06d92c1eb2e1e470d820f93187a6474bf8c32d84fdf663`

Ra khác chữ ký này thường do thân JSON có khoảng trắng, chữ tiếng Việt bị đổi thành `\u...` (`JSON.stringify` không đổi; PHP cần `JSON_UNESCAPED_UNICODE`; Python cần `separators=(",", ":"), ensure_ascii=False`), hoặc dùng `\r\n` thay cho `\n`.

Ví dụ gọi bằng `curl` (lệnh này tạo lịch thật):

```bash
ts=$(date +%s)
body='{"name":"Phạm Văn Bình","phone":"0987654321","date":"2026-10-31","time":"08:00","consent":true,"request_id":"zalo-msg-123456"}'
sig=$(printf '%s\n%s\n%s\n%s' "$ts" POST /ec-booking/v1/appointments "$body" | openssl dgst -sha256 -hmac "$EC_BOOKING_SECRET" -hex | sed 's/^.* //')
curl -X POST https://mathanoibacninh.com/wp-json/ec-booking/v1/appointments \
  -H 'Content-Type: application/json' -H "X-EC-Key: $EC_BOOKING_ID" \
  -H "X-EC-Timestamp: $ts" -H "X-EC-Signature: $sig" --data-binary "$body"
```

### `GET /slots`

Khung giờ còn nhận trong 14 ngày tới. Endpoint này công khai, không cần chữ ký.

```json
{"ok": true, "timezone": "Asia/Ho_Chi_Minh", "open": "07:30", "close": "18:00", "max_date": "2027-01-27",
 "days": [{"date": "2026-10-31", "slots": ["07:30", "08:00", "..."]}]}
```

### `POST /appointments`

```json
{"name": "Phạm Văn Bình", "phone": "0987654321", "date": "2026-10-31", "time": "08:00",
 "consent": true, "patient_zalo_consent": false, "request_id": "zalo-msg-123456"}
```

- `consent` bắt buộc là `true`: đối tác xác nhận người bệnh đã đồng ý cho bệnh viện tiếp nhận và liên hệ về lịch khám.
- `patient_zalo_consent` (không bắt buộc): người bệnh đồng ý nhận tin xác nhận lịch qua Zalo. Chỉ áp dụng với số di động Việt Nam.
- `request_id` bắt buộc, 8–64 ký tự (chữ, số, `. _ : -`). Khi mạng lỗi, **gửi lại cùng `request_id`** để nhận lại đúng lịch đã tạo thay vì tạo lịch mới. Hai đối tác dùng trùng `request_id` không ảnh hưởng nhau.

Thành công trả `201`:

```json
{"ok": true, "appointment": {"appointment_id": 2051, "status": "confirmed", "status_label": "Đã xác nhận", "date": "2026-10-31", "time": "08:00"}}
```

Phản hồi không bao giờ chứa họ tên hoặc số điện thoại.

### `GET /appointments/{id}`

Trạng thái mới nhất của lịch do chính đối tác đó tạo (`pending`, `confirmed`, `contacted`, `completed`, `cancelled`). Lịch tạo từ nguồn khác trả 404.

### Lỗi

```json
{"ok": false, "code": "duplicate", "message": "Không tạo lịch mới: số điện thoại này đã có lịch khám lúc 08:00 ngày 31/10/2026 ...", "appointment": {"...": "..."}}
```

| HTTP | `code` | Ý nghĩa và cách xử lý |
|---|---|---|
| 400 | `input`, `name`, `phone`, `date`, `consent`, `request_id` | Dữ liệu sai. Hiển thị `message` cho người bệnh, không thử lại nguyên văn |
| 401 | `auth` | Sai mã đối tác, khóa hoặc chữ ký |
| 401 | `auth_clock` | Đồng hồ máy chủ đối tác lệch quá 5 phút |
| 404 | `not_found` | Không có lịch của đối tác này |
| 409 | `duplicate` | Số điện thoại đã có lịch trong ngày đó. Chỉ khi họ tên trùng khớp mới kèm `appointment` của lịch cũ. Muốn đổi giờ, người bệnh gọi hotline |
| 409 | `request_conflict` | Dùng lại `request_id` cũ cho dữ liệu khác. Tạo `request_id` mới |
| 409 | `busy` | Yêu cầu cùng `request_id` đang xử lý. Thử lại sau vài giây |
| 429 | `rate_limit`, `auth_throttled` | Vượt giới hạn. Mỗi đối tác có hạn mức lịch mỗi giờ riêng, xem trong trang quản trị |
| 503 | `storage` | Lỗi tạm thời. Thử lại với cùng `request_id` |

## SDK

Không cần thư viện ngoài.

**Node.js 18+**: tải [ec-booking.mjs](https://mathanoibacninh.com/api-dat-lich/sdk/ec-booking.mjs)

```js
import { EcBookingClient } from './ec-booking.mjs';
const api = new EcBookingClient({ clientId: process.env.EC_BOOKING_ID, secret: process.env.EC_BOOKING_SECRET });
const appointment = await api.createAppointment({ name: 'Phạm Văn Bình', phone: '0987654321', date: '2026-10-31', time: '08:00', consent: true, requestId: 'zalo-msg-123456' });
```

**PHP 7.4+, ext-curl**: tải [EcBookingClient.php.txt](https://mathanoibacninh.com/api-dat-lich/sdk/EcBookingClient.php.txt), đổi tên thành `EcBookingClient.php`

```php
$api = new EcBookingClient( getenv( 'EC_BOOKING_ID' ), getenv( 'EC_BOOKING_SECRET' ) );
$appointment = $api->create_appointment( 'Phạm Văn Bình', '0987654321', '2026-10-31', '08:00', 'zalo-msg-123456' );
```

Lỗi được ném ra dưới dạng `EcBookingError` / `EcBookingException`, có `status`, `code` và `appointment` (nếu có).

## Dùng với trợ lý AI (Codex, Claude)

Gửi cho AI link bản Markdown ở đầu trang. Không dán khóa bí mật vào cuộc trò chuyện: đặt mã đối tác và khóa bí mật vào biến môi trường `EC_BOOKING_ID`, `EC_BOOKING_SECRET` trên máy chạy code, rồi bảo AI đọc từ hai biến đó.

Không có môi trường thử riêng. Mỗi lần `POST /appointments` thành công là một lịch khám thật: ghi vào Google Sheet, báo cho nhân viên, và gửi tin Zalo cho người bệnh nếu có đồng ý. Khi thử:

- Kiểm tra mã ký bằng ví dụ ở mục "Kiểm tra cách ký", sau đó gọi `GET /slots`; hai bước này không tạo lịch.
- Chỉ tạo lịch khi người phụ trách đồng ý, dùng số điện thoại của chính người thử, rồi báo bệnh viện hủy lịch thử.
- Mỗi lịch một `request_id` mới; giữ nguyên `request_id` đó khi gửi lại sau lỗi mạng.
