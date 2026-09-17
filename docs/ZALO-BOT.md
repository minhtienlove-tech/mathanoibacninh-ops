# Thông báo đặt lịch qua Zalo Bot

Mở **Đặt lịch khám → Cài đặt Zalo** trong WordPress admin:
https://mathanoibacninh.com/wp-admin/edit.php?post_type=ec_appointment&page=ec-zalo-settings

## Kết nối lần đầu

1. Trong Zalo, tìm OA **Zalo Bot Manager**, chọn **Tạo bot** và đặt tên bắt đầu bằng “Bot”. Xem [hướng dẫn chính thức](https://docs.zaloplatforms.com/docs/BOT/create_bot).
2. Dán Bot Token được cấp vào trang cài đặt rồi lưu. Không gửi token qua chat hoặc đưa vào Git. Token đã lưu không được hiển thị lại; để trống ô token khi cập nhật các mục khác. Nếu token đã xuất hiện trong ảnh hoặc cuộc trò chuyện, tạo token mới trong Zalo Bot Manager trước khi lưu.
3. Bấm **Tạo Secret Webhook**, rồi bấm **Kích hoạt Webhook**. Website gửi URL HTTPS và secret trực tiếp cho Zalo; không cần tự nhập lại trong Bot Manager. Secret chỉ được lưu dạng mã hóa. Webhook xác thực header `X-Bot-Api-Secret-Token` trước khi đọc dữ liệu.
4. Dùng tài khoản nhận thông báo nhắn `/nhanlich` cho bot, sau đó tải lại trang cài đặt. Chọn Chat ID xuất hiện trong danh sách rồi lưu cấu hình. Chat ID không phải số điện thoại.
5. Bấm **Gửi tin kiểm tra đến Chat ID đã lưu**, kiểm tra đúng người nhận rồi bật **Thông báo tự động** và lưu.

## Hoạt động

- Mặc định tắt. Chỉ xếp hàng thông báo cho yêu cầu mới được lưu thành công sau khi bật; không gửi hàng loạt lịch cũ.
- Nội dung gồm mã lịch, ngày, giờ và liên kết admin. Không gửi tên, số điện thoại hoặc ghi chú bệnh nhân.
- WordPress Cron xử lý nền; thời gian gửi phụ thuộc hoạt động Cron/lượt truy cập. Lưu lịch vẫn thành công khi Zalo gặp lỗi.
- Trong chi tiết lịch hẹn có ô **Thông báo Zalo** ghi kết quả. Nếu kết quả chưa xác định, kiểm tra Zalo trước khi bấm gửi lại; hệ thống không tự gửi lại khi có nguy cơ trùng tin.
- Đổi token sẽ bỏ lựa chọn người nhận; cần chọn lại và kiểm tra kết nối. Thay đổi bot/người nhận sẽ bỏ qua thông báo đang chờ theo cấu hình cũ.
- Webhook là phương thức production. Nút **Tìm người nhận** chỉ là phương án dự phòng dùng `getUpdates`; thời gian chờ của Zalo được coi là không có tin nhắn mới, không phải lỗi cấu hình.
- Chỉ quản trị viên có quyền `manage_options` được cấu hình hoặc gửi thủ công. Token mã hóa AES-256-GCM trong database bằng khóa dẫn xuất từ WordPress auth salt; khi thay auth salt phải nhập lại token. Không đưa database hoặc token vào Git.

## Kiểm tra kỹ thuật

`theme/eyecare-child/tests/booking-zalo-test.php` kiểm tra bằng HTTP giả lập, không gửi tin thật. Tài liệu API: [getMe](https://docs.zaloplatforms.com/docs/BOT/apis/getMe), [getUpdates](https://docs.zaloplatforms.com/docs/BOT/apis/getUpdates), [sendMessage](https://docs.zaloplatforms.com/docs/BOT/apis/sendMessage).
