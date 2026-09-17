# Thông báo đặt lịch qua Zalo Bot

Mở **Đặt lịch khám → Cài đặt Zalo** trong WordPress admin:
https://mathanoibacninh.com/wp-admin/edit.php?post_type=ec_appointment&page=ec-zalo-settings

## Kết nối lần đầu

1. Trong Zalo, tìm OA **Zalo Bot Manager**, chọn **Tạo bot** và đặt tên bắt đầu bằng “Bot”. Xem [hướng dẫn chính thức](https://docs.zaloplatforms.com/docs/BOT/create_bot).
2. Dán Bot Token được cấp vào trang cài đặt rồi lưu. Không gửi token qua chat hoặc đưa vào Git. Token đã lưu không được hiển thị lại; để trống ô token khi cập nhật các mục khác. Nếu token đã xuất hiện trong ảnh hoặc cuộc trò chuyện, tạo token mới trong Zalo Bot Manager trước khi lưu.
3. Bấm **Tạo Secret Webhook**, rồi bấm **Kích hoạt Webhook**. Website gửi URL HTTPS và secret trực tiếp cho Zalo; không cần tự nhập lại trong Bot Manager. Secret chỉ được lưu dạng mã hóa. Webhook xác thực header `X-Bot-Api-Secret-Token` trước khi đọc dữ liệu.
4. Mỗi tài khoản cần nhận thông báo nhắn một tin **trong chat riêng với bot**, sau đó bấm **Cập nhật Chat ID vừa nhắn**. Danh sách hiển thị tên Zalo, Chat ID và trạng thái chat riêng đã xác nhận. Tích chọn các tài khoản cần nhận, tối đa 10 tài khoản, rồi lưu cấu hình. Chat ID không phải số điện thoại.
5. Bấm **Gửi tin kiểm tra đến Chat ID đã lưu**, kiểm tra đúng tất cả người nhận rồi bật **Thông báo tự động** và lưu.

## Xem tiến độ và kiểm tra lỗi

- Bấm **Kiểm tra Webhook ngay**. Nút này không gửi tin nhắn và không cần Chat ID; nó kiểm tra riêng Bot Token, URL Webhook đang lưu tại Zalo và việc Zalo gọi được endpoint HTTPS của website.
- Bảng **Trạng thái kết nối** không hiện Bot Token, Secret hay nội dung tin nhắn. Chat ID và tên Zalo chỉ hiện trong danh sách người nhận dành cho quản trị viên. Dòng **Sự kiện Webhook gần nhất** và **Tin nhắn chat riêng** cho biết website đã nhận tin từ Zalo chưa.
- Nếu Bot, URL Webhook và endpoint đều đạt nhưng chưa có sự kiện, kết nối đã đúng và website đang chờ một tin nhắn chat riêng. Nhắn một tin cho bot rồi tải lại trang cài đặt để xem kết quả.
- ID tạo bởi kiểm tra Webhook hoặc ID cũ chưa có xác nhận chat riêng sẽ không xuất hiện trong danh sách chọn. Chỉ tin nhắn thật từ tài khoản Zalo trong chat riêng mới tạo được một Chat ID có thể chọn.
- Kết quả kiểm tra được dùng lại trong một phút để không vượt giới hạn kiểm tra của Zalo. Bấm lại sau một phút khi cần kiểm tra mới.
- Nếu Zalo từ chối gửi tin kiểm tra, không bấm gửi lặp lại. Nhắn một tin mới trong **chat riêng** với bot, bấm **Cập nhật Chat ID vừa nhắn**, chọn lại tài khoản có trạng thái **Chat riêng đã xác nhận** rồi mới gửi thử. Candidate cũ hoặc từ nhóm không được dùng để nhận thông báo.

## Hoạt động

- Mặc định tắt. Chỉ xếp hàng thông báo cho yêu cầu mới được lưu thành công sau khi bật; không gửi hàng loạt lịch cũ.
- Nội dung gồm mã lịch, họ tên, số điện thoại, ngày, giờ và liên kết admin để nhân viên đã được chọn có thể xác nhận lịch. Không gửi ghi chú, lý do khám hoặc dữ liệu khám chữa bệnh khác; không ghi thông tin người đặt vào nhật ký, trạng thái gửi hoặc Git.
- WordPress Cron xử lý nền; thời gian gửi phụ thuộc hoạt động Cron/lượt truy cập. Lưu lịch vẫn thành công khi Zalo gặp lỗi.
- Trong chi tiết lịch hẹn có ô **Thông báo Zalo** ghi kết quả. Nếu một số tài khoản không nhận được, trạng thái là **Đã gửi đến một phần tài khoản Zalo**; lần gửi lại chỉ gửi cho tài khoản chưa được xác nhận. Nếu kết quả chưa xác định, kiểm tra Zalo trước khi bấm gửi lại; hệ thống không tự gửi lại khi có nguy cơ trùng tin.
- Đổi token sẽ bỏ lựa chọn người nhận; cần chọn lại và kiểm tra kết nối. Thay đổi bot/người nhận sẽ bỏ qua thông báo đang chờ theo cấu hình cũ.
- Webhook là phương thức production. `getUpdates` không hoạt động đồng thời với Webhook nên nút tìm người nhận chỉ hiện trước khi tạo Secret Webhook; khi đã dùng Webhook, theo dõi tiến độ bằng bảng trạng thái và event thật từ Zalo.
- Nhóm chat là tính năng beta của Zalo Bot. Bot chỉ nhận tin trả lời trực tiếp hoặc được nhắc tên trong nhóm; hệ thống này chỉ dùng chat riêng để nhận thông báo đặt lịch.
- Chỉ quản trị viên có quyền `manage_options` được cấu hình hoặc gửi thủ công. Token mã hóa AES-256-GCM trong database bằng khóa dẫn xuất từ WordPress auth salt; khi thay auth salt phải nhập lại token. Không đưa database hoặc token vào Git.

## Kiểm tra kỹ thuật

`theme/eyecare-child/tests/booking-zalo-test.php` kiểm tra bằng HTTP giả lập, không gửi tin thật. Tài liệu API: [getMe](https://bot.zapps.me/docs/apis/getMe/), [getWebhookInfo](https://bot.zapps.me/docs/apis/getWebhookInfo/), [testWebhook](https://bot.zapps.me/docs/apis/testWebhook/) và [sendMessage](https://bot.zapps.me/docs/apis/sendMessage/).
