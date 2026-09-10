# Production changelog

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
