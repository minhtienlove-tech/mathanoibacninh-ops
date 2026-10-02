# Luồng liên kết từ trang chủ — 2026-10-02

Kiểm tra sitemap công khai và HTML của 200 bài, 144 trang, 12 danh mục. Trước sửa: 200/200 bài đi tới được từ trang chủ trong tối đa hai lần nhấp, nhưng 59 bài không có liên kết trỏ tới từ thân bài khác. URL danh mục `/kien-thuc/kien-thuc-phau-thuat-khuc-xa/` trả 404. Bảy trang không có liên kết từ bất kỳ trang nào trong sitemap; `/ve-chung-toi/` có nội dung thực, sáu trang còn lại là trang giữ chỗ hoặc xác nhận.

`plan.json` ghép 59 bài đích với 50 bài nguồn theo liên kết ngược đã có trong nội dung đích. Script `scripts/link-trust-59.php` kiểm tra trạng thái, slug và liên kết ngược trước khi thêm một khối đọc tiếp trong thân bài nguồn. Không thay đổi phần giải thích chuyên môn đã có. Nội dung gốc của 50 bài nằm trong bản sao lưu máy chủ `original-linked-post-content.json`.

Sáu trang giữ chỗ/tiện ích ID 7, 10, 53, 59, 63, 64 được đánh dấu `noindex, follow` qua child theme và loại khỏi OBS sitemap bằng tùy chọn `exclude_ids`; tùy chọn cũ được sao lưu. Trang `/ve-chung-toi/` nhận liên kết trực tiếp từ `/gioi-thieu/`.

Backup: `/home/jwhxtzru/backups/link-trust-20261002-084027/`. Rollback chọn lọc: khôi phục hai file theme từ `*.before`, bỏ file `inc/seo-trang-giu-cho.php` khỏi bản triển khai khi `functions.php` cũ đã được khôi phục, cập nhật lại nội dung 50 bài từ `original-linked-post-content.json`, khôi phục tùy chọn `obs_seo_sitemap` từ `original-obs-sitemap-option.json`, làm mới rewrite/cache và kiểm tra các URL. Không nhập toàn bộ `database.sql` nếu đã có nội dung mới phát sinh sau backup.
