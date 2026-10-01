# Mắt và môi trường làm việc — 15 bài mới

Ngày soạn: 01/10/2026. Chuyên mục WordPress: `mat-va-moi-truong-lam-viec` (ID 15), thuộc `kien-thuc` (ID 3).

## Nội dung và kiểm tra

- `articles.py`, `extensions.py`, `more*.py`, `last.py`: bản thảo có chủ đề riêng, dùng để tạo 15 bài. `package/01.html`–`15.html` là HTML sẽ nhập; `package/review.html` là bản đọc liên tục, `package/manifest.json` ghi URL và số từ.
- Mỗi bài 1.511–1.638 từ trên HTML hiển thị theo cách đếm tách bằng khoảng trắng, gồm trả lời nhanh, nội dung nghề nghiệp, dấu hiệu cần khám, câu hỏi thường gặp, liên kết nội bộ và nguồn tham khảo. Không gán tên bác sĩ hoặc ngày duyệt chưa được cung cấp.
- Chạy `python scripts/build-workplace-series.py` để dựng lại và kiểm tra ngưỡng 1.450–1.650 từ; importer kiểm tra số bài, slug, URL, tiêu đề, cấu trúc, va chạm slug trước khi ghi.
- 11 URL liên kết nội bộ đã trả HTTP 200 ngày 01/10/2026. Nguồn y khoa chủ yếu từ NEI, NIOSH, OSHA, CDC và EPA. Nội dung minh họa, không thay chỉ định cá nhân hoặc quy trình an toàn của nơi làm việc.

## Triển khai và khôi phục

Trước khi ghi WordPress, tạo backup database trong `~/backups/` trên server. Upload đúng thư mục `package` và `scripts/import-workplace-series.php` vào một thư mục tạm **ngoài web root**. Chạy importer qua WP-CLI với `EYECARE_IMPORT_WORKPLACE=1` và `EYECARE_WORKPLACE_PACKAGE_DIR` trỏ đến thư mục chứa `manifest.json`; importer tạo **draft** và đặt trạng thái ghi công chờ xác minh. Kiểm tra bản xem trước từng bài, sau đó mới đổi trạng thái thành công khai nếu đạt QA.

Đợt nhập thực tế: backup `/home/jwhxtzru/backups/workplace-15-20261001-154151/database.sql`, bản nháp ID 1474–1488. Đã kiểm tra giao diện Chrome ở ba bài 1474, 1480, 1488 trên desktop và 375 px: một H1, 15 H2, nguồn tham khảo, không tràn ngang hoặc lỗi JavaScript. Script `scripts/publish-workplace-series.php` kiểm tra lại 15 bản nháp, chuyên mục, số từ, trạng thái ghi công rồi mới chuyển sang `publish`.

15 bài ID 1474–1488 đã xuất bản ngày 01/10/2026. Đã xóa WordPress object cache và LiteSpeed cache. `python scripts/verify-workplace-series.py` đạt: 15/15 URL HTTP 200, mỗi bài có một H1, một meta description, một canonical đúng URL, nguồn tham khảo; cả 15 được liên kết từ `/kien-thuc/`. Chuyên mục tăng từ 1 lên 16 bài. `scripts/verify-live.ps1 -CheckSsh` và PHP lint các tệp được yêu cầu đều đạt. Thông tin biên soạn, bác sĩ duyệt và ngày duyệt chưa được cung cấp; đang để trạng thái chờ xác minh, không tạo ghi công giả.

Rollback nhanh nếu đã công khai: đổi trạng thái 15 bài theo `_eyecare_workplace_series_id` về `draft`, xóa cache WordPress/LiteSpeed và kiểm tra `/kien-thuc/`. Không xóa bài hoặc phục hồi toàn bộ database khi chỉ cần ẩn loạt bài này. Backup database là bản khôi phục cuối cùng nếu có lỗi dữ liệu ngoài phạm vi 15 bài.

Với đợt này, các ID cụ thể là 1474–1488. Có thể đổi từng bài bằng `wp post update ID --post_status=draft` sau khi kiểm tra ID/slug; sau đó chạy `wp cache flush` và `wp litespeed-purge all`. Cách này giữ nội dung để chỉnh sửa và xuất bản lại. Không dùng `wp db import` để rollback riêng loạt bài vì sẽ ghi đè cả thay đổi WordPress phát sinh sau thời điểm backup.

## Liên kết hai chiều

Mỗi bài mới đã có một liên kết tới một bài cũ. Đợt bổ sung dùng `scripts/link-workplace-series.php` để thêm hai liên kết sang bài mới cùng tình huống và thêm liên kết chiều ngược từ 11 bài cũ tới đủ 15 bài mới. Khối mới có dấu `eyecare-workplace-links:v1` để kiểm tra và khôi phục chọn lọc. Script lưu nguyên văn `post_content` của 26 bài trong một JSON riêng trên server trước khi cập nhật, ngoài bản backup database.
