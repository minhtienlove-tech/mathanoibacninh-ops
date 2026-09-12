# Thông báo lịch khám bằng Gmail

Mở **Đặt lịch khám → Cài đặt Gmail**:
https://mathanoibacninh.com/wp-admin/edit.php?post_type=ec_appointment&page=ec-gmail-settings

1. Bật Xác minh 2 bước cho tài khoản Google gửi thư. Tạo mật khẩu ứng dụng tại https://myaccount.google.com/apppasswords với tên dễ nhận biết, ví dụ `mathanoibacninh`.
2. Nhập email gửi đầy đủ, mật khẩu ứng dụng 16 ký tự và một email nhận của nhân viên phụ trách. Có thể dùng cùng địa chỉ gửi và nhận. Bấm **Lưu cấu hình Gmail**.
3. Bấm **Gửi email kiểm tra**, kiểm tra hộp thư đến/thư rác của địa chỉ nhận đã lưu.
4. Bật thông báo tự động và lưu. Gmail và Zalo có thể dùng đồng thời hoặc bật/tắt riêng.

Mật khẩu mã hóa AES-256-GCM trong option riêng, dùng helper mã hóa đã có của tính năng Zalo; không xuất lại vào HTML, log hay Git. Để trống ô mật khẩu khi sửa mục khác để giữ mật khẩu hiện tại. Đổi email gửi cần nhập mật khẩu ứng dụng tương ứng. Đổi WordPress auth salt hoặc mật khẩu Google có thể yêu cầu nhập lại mật khẩu ứng dụng. Chỉ người có quyền `manage_options` được cấu hình/gửi thử/gửi lại.

Gửi bằng PHPMailer có sẵn trong WordPress, với một instance riêng: `smtp.gmail.com:587`, STARTTLS, xác thực bằng email và mật khẩu ứng dụng, xác minh chứng chỉ TLS, tắt SMTP debug. Không đổi bộ gửi mail chung của WordPress hoặc plugin khác.

Chỉ yêu cầu mới sau khi bật được xếp hàng qua WP Cron. Email gồm mã lịch, ngày giờ và liên kết admin; không gửi tên, điện thoại hoặc ghi chú bệnh nhân. Xem kết quả trong ô **Thông báo Gmail** ở chi tiết lịch. “Gmail đã chấp nhận thư” là xác nhận SMTP, chưa bảo đảm thư vào Inbox. Nếu lỗi chưa rõ kết quả, kiểm tra hộp thư trước khi gửi lại; không tự gửi lại để tránh trùng. Đổi cấu hình sẽ bỏ qua thông báo đang chờ theo phiên bản cũ. Lịch hẹn vẫn được giữ khi gửi lỗi; tốc độ Cron phụ thuộc lượt truy cập.

Tài liệu Google: [Mật khẩu ứng dụng](https://support.google.com/accounts/answer/185833?hl=vi), [Cấu hình SMTP Gmail](https://support.google.com/mail/answer/7104828?hl=en).

Kiểm thử: `theme/eyecare-child/tests/booking-gmail-test.php` dùng SMTP giả lập, không gửi thư thật. Khi triển khai đã kiểm tra bắt tay STARTTLS từ server, không đăng nhập hoặc gửi email. Cần nhập tài khoản thật trong admin và gửi thử để xác minh hoàn chỉnh.
