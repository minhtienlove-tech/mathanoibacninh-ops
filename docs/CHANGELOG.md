# Production changelog

## 2026-09-10

- Thiết kế lại trang đăng nhập trong child theme: logo bệnh viện, nhận diện xanh lá, bố cục hai cột trên desktop và một cột trên điện thoại; Việt hóa nhãn biểu mẫu. Giữ nguyên cơ chế xác thực WordPress.
- Kiểm tra bản local: PHP lint trang đăng nhập/footer/liên hệ đạt; xem trước với HTML đăng nhập production ở 1440px và 390px, không tràn ngang; smoke test 6 URL production và SSH đạt. Chưa triển khai thay đổi giao diện đăng nhập lên production.
- Chuẩn bị triển khai giao diện đăng nhập theo yêu cầu; backup/staging: `/home/jwhxtzru/backups/login-redesign-20260910-135108/`. Chỉ cập nhật `inc/trang-dang-nhap.php` và `assets/dang-nhap.css`; logo production đã khớp bản trong Git. Sử dụng khóa `website-ops-deploy.lock` và kiểm tra hash file cũ trước khi cập nhật.

- Tạo SSH alias riêng `mathanoibacninh` cho tài khoản AZDIGI `jwhxtzru`.
- Cập nhật WordPress `home` và `siteurl` sang HTTPS.
- Flush rewrite rules cho URL `/kien-thuc/...`.
- Thêm Facebook, X, Pinterest, YouTube, Instagram và TikTok vào footer.
- Tăng tương phản breadcrumb trên hero trang chuyên mục.
- Xóa dải “Website mới đang được hoàn thiện” trên trang Liên hệ.
- Backup liên quan nằm trong `/home/jwhxtzru/backups/` trên server.
