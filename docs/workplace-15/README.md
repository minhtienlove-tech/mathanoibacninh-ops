# Mắt và môi trường làm việc — 15 bài mới

Ngày soạn: 01/10/2026. Chuyên mục WordPress: `mat-va-moi-truong-lam-viec` (ID 15), thuộc `kien-thuc` (ID 3).

## Nội dung và kiểm tra

- `articles.py`, `extensions.py`, `more*.py`, `last.py`: bản thảo có chủ đề riêng, dùng để tạo 15 bài. `package/01.html`–`15.html` là HTML sẽ nhập; `package/review.html` là bản đọc liên tục, `package/manifest.json` ghi URL và số từ.
- Mỗi bài 1.461–1.587 từ theo cách đếm tách bằng khoảng trắng, gồm trả lời nhanh, nội dung nghề nghiệp, dấu hiệu cần khám, câu hỏi thường gặp, liên kết nội bộ và nguồn tham khảo. Không gán tên bác sĩ hoặc ngày duyệt chưa được cung cấp.
- Chạy `python scripts/build-workplace-series.py` để dựng lại và kiểm tra ngưỡng 1.450–1.650 từ; importer kiểm tra số bài, slug, URL, tiêu đề, cấu trúc, va chạm slug trước khi ghi.
- 11 URL liên kết nội bộ đã trả HTTP 200 ngày 01/10/2026. Nguồn y khoa chủ yếu từ NEI, NIOSH, OSHA, CDC và EPA. Nội dung minh họa, không thay chỉ định cá nhân hoặc quy trình an toàn của nơi làm việc.

## Triển khai và khôi phục

Trước khi ghi WordPress, tạo backup database trong `~/backups/` trên server. Upload đúng thư mục `package` và `scripts/import-workplace-series.php` vào một thư mục tạm **ngoài web root**. Chạy importer qua WP-CLI với `EYECARE_IMPORT_WORKPLACE=1` và `EYECARE_WORKPLACE_PACKAGE_DIR` trỏ đến thư mục chứa `manifest.json`; importer tạo **draft** và đặt trạng thái ghi công chờ xác minh. Kiểm tra bản xem trước từng bài, sau đó mới đổi trạng thái thành công khai nếu đạt QA.

Rollback nhanh nếu đã công khai: đổi trạng thái 15 bài theo `_eyecare_workplace_series_id` về `draft`, xóa cache WordPress/LiteSpeed và kiểm tra `/kien-thuc/`. Không xóa bài hoặc phục hồi toàn bộ database khi chỉ cần ẩn loạt bài này. Backup database là bản khôi phục cuối cùng nếu có lỗi dữ liệu ngoài phạm vi 15 bài.
