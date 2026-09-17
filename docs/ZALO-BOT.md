# Thông báo đặt lịch qua Zalo Bot

Mở **Đặt lịch khám → Cài đặt Zalo** trong WordPress admin:
https://mathanoibacninh.com/wp-admin/edit.php?post_type=ec_appointment&page=ec-zalo-settings

## Kết nối lần đầu

1. Trong Zalo, tìm OA **Zalo Bot Manager**, chọn **Tạo bot** và đặt tên bắt đầu bằng “Bot”. Xem [hướng dẫn chính thức](https://docs.zaloplatforms.com/docs/BOT/create_bot).
2. Dán Bot Token được cấp vào trang cài đặt rồi lưu. Không gửi token qua chat hoặc đưa vào Git. Token đã lưu không được hiển thị lại; để trống ô token khi cập nhật các mục khác. Nếu token đã xuất hiện trong ảnh hoặc cuộc trò chuyện, tạo token mới trong Zalo Bot Manager trước khi lưu.
3. Bấm **Tạo Secret Webhook**, rồi bấm **Kích hoạt Webhook**. Website gửi URL HTTPS và secret trực tiếp cho Zalo; không cần tự nhập lại trong Bot Manager. Secret chỉ được lưu dạng mã hóa. Webhook xác thực header `X-Bot-Api-Secret-Token` trước khi đọc dữ liệu.
4. Dùng tài khoản nhận thông báo nhắn `/nhanlich` **trong chat riêng với bot**, sau đó tải lại trang cài đặt. Chọn Chat ID có nhãn **Chat riêng đã xác nhận** rồi lưu cấu hình. Chat ID không phải số điện thoại.
5. Bấm **Gửi tin kiểm tra đến Chat ID đã lưu**, kiểm tra đúng người nhận rồi bật **Thông báo tự động** và lưu.

## Xem tiến độ và kiểm tra lỗi

- Bấm **Kiểm tra Webhook ngay**. Nút này không gửi tin nhắn và không cần Chat ID; nó kiểm tra riêng Bot Token, URL Webhook đang lưu tại Zalo và việc Zalo gọi được endpoint HTTPS của website.
- Bảng **Trạng thái kết nối** chỉ hiện trạng thái an toàn, không hiện Bot Token, Secret, Chat ID hay nội dung tin nhắn. Dòng **Sự kiện Webhook gần nhất** cho biết website đã nhận request xác thực từ Zalo chưa; dòng **Lệnh /nhanlich** cho biết event gần nhất có đúng lệnh chọn người nhận hay không.
- Nếu Bot, URL Webhook và endpoint đều đạt nhưng chưa có sự kiện, kết nối đã đúng và website đang chờ Zalo chuyển tin `/nhanlich`. Nhắn đúng lệnh cho bot rồi tải lại trang cài đặt để xem kết quả.
- Kết quả kiểm tra được dùng lại trong một phút để không vượt giới hạn kiểm tra của Zalo. Bấm lại sau một phút khi cần kiểm tra mới.
- Nếu Zalo từ chối gửi tin kiểm tra, không bấm gửi lặp lại. Nhắn `/nhanlich` mới trong **chat riêng** với bot, tải lại trang, chọn lại Chat ID có nhãn đã xác nhận rồi mới gửi thử. Candidate cũ hoặc từ nhóm không được dùng để nhận thông báo.

## Hoạt động

- Mặc định tắt. Chỉ xếp hàng thông báo cho yêu cầu mới được lưu thành công sau khi bật; không gửi hàng loạt lịch cũ.
- Nội dung gồm mã lịch, ngày, giờ và liên kết admin. Không gửi tên, số điện thoại hoặc ghi chú bệnh nhân.
- WordPress Cron xử lý nền; thời gian gửi phụ thuộc hoạt động Cron/lượt truy cập. Lưu lịch vẫn thành công khi Zalo gặp lỗi.
- Trong chi tiết lịch hẹn có ô **Thông báo Zalo** ghi kết quả. Nếu kết quả chưa xác định, kiểm tra Zalo trước khi bấm gửi lại; hệ thống không tự gửi lại khi có nguy cơ trùng tin.
- Đổi token sẽ bỏ lựa chọn người nhận; cần chọn lại và kiểm tra kết nối. Thay đổi bot/người nhận sẽ bỏ qua thông báo đang chờ theo cấu hình cũ.
- Webhook là phương thức production. `getUpdates` không hoạt động đồng thời với Webhook nên nút tìm người nhận chỉ hiện trước khi tạo Secret Webhook; khi đã dùng Webhook, theo dõi tiến độ bằng bảng trạng thái và event thật từ Zalo.
- Nhóm chat là tính năng beta của Zalo Bot. Bot chỉ nhận tin trả lời trực tiếp hoặc được nhắc tên trong nhóm; hệ thống này chỉ dùng chat riêng để nhận thông báo đặt lịch.
- Chỉ quản trị viên có quyền `manage_options` được cấu hình hoặc gửi thủ công. Token mã hóa AES-256-GCM trong database bằng khóa dẫn xuất từ WordPress auth salt; khi thay auth salt phải nhập lại token. Không đưa database hoặc token vào Git.

## Kiểm tra kỹ thuật

`theme/eyecare-child/tests/booking-zalo-test.php` kiểm tra bằng HTTP giả lập, không gửi tin thật. Tài liệu API: [getMe](https://docs.zaloplatforms.com/docs/BOT/apis/getMe), [getWebhookInfo](https://docs.zaloplatforms.com/docs/BOT/apis/getWebhookInfo), [testWebhook](https://docs.zaloplatforms.com/docs/BOT/apis/testWebhook) và [sendMessage](https://docs.zaloplatforms.com/docs/BOT/apis/sendMessage).
