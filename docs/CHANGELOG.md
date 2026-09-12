# Production changelog

## 2026-09-12

- Nâng cấp quản trị **Đặt lịch khám**: thẻ tổng yêu cầu/chờ xác nhận/lịch hôm nay, lọc ngày và trạng thái, tìm tên/điện thoại, ghi chú nội bộ, trạng thái Đã khám, thời điểm/người xử lý; thêm lối vào trên Bảng tin. Kiểm tra 105 tình huống backend/admin đạt; kiểm tra SQL và render màn hình trong WordPress staging đạt, không sửa lịch bệnh nhân. Backup triển khai: `/home/jwhxtzru/backups/booking-admin-20260912-083019/`.

- Đã triển khai `c4e1962`: giới hạn lịch khám mới đến 17:00 (bỏ 17:30), giữ giờ mở cửa bệnh viện; cập nhật hướng dẫn trong form. 78 kiểm tra backend, PHP lint, API production, trình duyệt desktop/mobile và smoke test đạt. Backup triển khai: `/home/jwhxtzru/backups/booking-cutoff-20260912-075534/`.

- Thêm nút gọi nổi góc trái với hiệu ứng sóng nhẹ, nhóm Zalo/Facebook/TikTok/YouTube thu gọn, tab đặt lịch riêng ở giữa mép phải. Zalo dùng số `0868 899 396` đã được xác nhận.
- Bổ sung form popup và trang `/dat-lich-kham/`: tên, điện thoại, ngày, giờ; đọc giờ làm việc từ cấu hình bệnh viện (hiện 07:30–18:00), khung 30 phút với giờ bắt đầu trước giờ đóng cửa, trong 90 ngày. Kiểm tra lại tại server theo múi giờ Việt Nam; lưu yêu cầu riêng tư trong quản trị **Lịch hẹn khám**, chờ nhân viên xác nhận. Không gửi email tự động.
- Chống gửi trùng khi mất mạng, giới hạn số lần gửi, xác nhận đồng ý liên hệ; dữ liệu lịch hẹn không xuất hiện trên REST API, tìm kiếm hoặc trang công khai.
- Kiểm tra local: 75 kiểm tra PHP về dữ liệu/lưu trữ/quyền riêng tư/gửi lại đạt; PHP lint và JavaScript syntax đạt; kiểm tra trình duyệt desktop/mobile về popup, thu gọn, bàn phím, khung giờ, gửi lại cùng mã yêu cầu và chế độ giảm chuyển động đạt. Backup file và database trước triển khai tại `/home/jwhxtzru/backups/contact-booking-20260912-074624/`.
- Đã triển khai `91ccd1c` lên production; PHP lint, đối chiếu SHA256, flush cache, 6 URL chính và SSH đều đạt. Chromium xác nhận popup hoạt động trên 1440px/390px, khung giờ API 07:30–17:30 theo bước 30 phút, nonce sai bị từ chối; không có lỗi JavaScript. Đường dẫn REST lịch hẹn trả 404, CPT chỉ cấp quyền quản trị. Không tạo lịch bệnh nhân thử trên production.
- Đã triển khai bổ sung `df84211`: trang `/dat-lich-kham/` có form riêng, ẩn bộ nút nổi tại trang này để không che các trường nhập trên điện thoại. Backup bổ sung `lien-he-noi-before-mobile-fix.php` và `database-before-mobile-fix.sql` trong cùng thư mục; kiểm tra lại trình duyệt production đạt.

## 2026-09-10

- Thiết kế lại trang đăng nhập trong child theme: logo bệnh viện, nhận diện xanh lá, bố cục hai cột trên desktop và một cột trên điện thoại; Việt hóa nhãn biểu mẫu. Giữ nguyên cơ chế xác thực WordPress.
- Đã triển khai production từ commit `ac61072`; backup database và các file liên quan tại `/home/jwhxtzru/backups/login-redesign-20260910-135108/` (`database.sql`, `login-files-before.tar.gz`). Chỉ cập nhật `inc/trang-dang-nhap.php` và `assets/dang-nhap.css`; logo production đã khớp bản trong Git. Dùng khóa `website-ops-deploy.lock`, kiểm tra hash file cũ trước khi cập nhật và đối chiếu hash bản mới sau triển khai.
- Kiểm tra sau triển khai: PHP lint trang đăng nhập/footer/liên hệ đạt, flush cache thành công; đường dẫn đăng nhập có `redirect_to` và `reauth=1` hiển thị thiết kế mới trên Chromium 1440px/390px, không tràn ngang. Hiện/ẩn mật khẩu hoạt động; giữ đúng form action và redirect quản trị; trang quên mật khẩu và interim login trả HTTP 200, không có lỗi JavaScript. Không thực hiện đăng nhập tài khoản hoặc gửi email đặt lại mật khẩu. Smoke test 6 URL chính và SSH đạt.

- Tạo SSH alias riêng `mathanoibacninh` cho tài khoản AZDIGI `jwhxtzru`.
- Cập nhật WordPress `home` và `siteurl` sang HTTPS.
- Flush rewrite rules cho URL `/kien-thuc/...`.
- Thêm Facebook, X, Pinterest, YouTube, Instagram và TikTok vào footer.
- Tăng tương phản breadcrumb trên hero trang chuyên mục.
- Xóa dải “Website mới đang được hoàn thiện” trên trang Liên hệ.
- Backup liên quan nằm trong `/home/jwhxtzru/backups/` trên server.
