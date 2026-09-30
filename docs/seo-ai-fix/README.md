# Sửa nhận diện thực thể và SEO kỹ thuật — 30/09/2026

Phạm vi: WordPress production `https://mathanoibacninh.com/`, child theme `eyecare-child`, plugin đang hoạt động `obs-seo-suite 2.22.1i`. Bản quét công khai trước thay đổi: [before.json](before.json). Tệp này chỉ chứa các trường công khai phục vụ đối chiếu, không có dữ liệu bệnh nhân. Script đọc: `scripts/seo-schema-audit.py`.

## Hiện trạng đã kiểm tra

| Vấn đề | Nguồn xác định | Hướng sửa |
| --- | --- | --- |
| Trang chủ có `AutoDealer` `/#dealer`; bảy trang mẫu khác có `LocalBusiness` + `AutoDealer` | `obs-seo-suite/includes/modules/class-obs-schema.php`, `wp_head` 10; `class-obs-gmb.php`, `wp_head` 12 | Đặt `obs_seo_schema.enable_dealer=0` và `obs_seo_gmb.enable_schema=0` qua WP-CLI, không sửa plugin. |
| Hai node `/#localbusiness` mô tả cùng bệnh viện khác dữ liệu | `class-obs-local-seo.php`, `wp_head` 10, và GMB | Đặt `obs_seo_local_seo.schema_enable=0`; giữ shortcode NAP/GMB và các chức năng khác. |
| `AggregateRating` 5/6 chưa rõ nguồn | `obs_seo_gmb.rating_avg=5`, `rating_count=6`; emitter GMB | Ngừng xuất schema GMB. Không khẳng định số liệu là giả; cần xác minh nguồn trước khi dùng lại. |
| Hai URL Facebook khác nhau trong `sameAs` | Child theme `inc/schema-y-te.php`; option `obs_seo_schema.facebook_url` | Ngừng xuất `sameAs` trên schema bệnh viện khi chưa xác minh; giữ liên kết giao diện để quản trị viên đối chiếu. |
| Trang chủ thiếu canonical trong HTML và HTTP `Link` header | Child theme và WordPress core hiện không xuất canonical cho trang chủ tĩnh | Thêm canonical tự trỏ HTTPS trong child theme; không đổi canonical trang đơn. |
| Schema/địa chỉ hiển thị vẫn gọi `Tỉnh Bắc Ninh` sau 20/09/2026 | `inc/schema-y-te.php`, `footer.php`, `inc/khu-vuc.php`, nội dung khu vực liên quan | Sửa tên đơn vị hành chính hiện hành thành `Thành phố Bắc Ninh`; giữ cách gọi tỉnh cũ ở ngữ cảnh lịch sử. Căn cứ [Nghị quyết 39/2026/QH16](https://vanban.chinhphu.vn/?docid=219328&pageid=27160). |

Trước sửa, cả tám URL mẫu trả 200, không có `noindex` hay `X-Robots-Tag`; homepage có 2 node AutoDealer, mỗi trang mẫu khác trừ bài kiến thức có 1. Bản quét đã parse JSON-LD object, array và `@graph`, không có lỗi JSON. Schema y tế chuẩn `/#to-chuc` do child theme phát ra, cùng `WebSite`, breadcrumb, Article/MedicalWebPage/Physician/FAQ liên quan. Hai node WebSite chung `/#website` có tên và URL thống nhất nên không tắt module Article của plugin chỉ để bỏ node lặp này.

`robots.txt` trả 200, cho phép trang công khai và chỉ chặn `/wp-admin/`. Sitemap đang dùng là `/wp-sitemap.xml`, dẫn tới ba sitemap OBS; `/sitemap_index.xml` trả 404 nên không phải sitemap đang dùng. HTTPS `www` chuyển về non-www, nhưng `http://mathanoibacninh.com/` vẫn trả 200 và chưa có redirect HTTPS; canonical sẽ trỏ HTTPS, còn quy tắc redirect ở webserver cần xử lý theo phạm vi cấu hình hosting. Không có log WAF/crawler trong quyền SSH hiện có; không thể kết luận bot thật đã hoặc chưa bị chặn.

## Sao lưu và rollback

Backup production riêng tư: `/home/jwhxtzru/backups/seo-entity-20260930-154327/`; gồm tám tệp nguồn trước thay đổi và `database.sql` (13 MB). Tám tệp đã được đối chiếu khớp Git HEAD trước khi sửa. Không đưa SQL vào Git.

Khôi phục riêng đợt này: chép lại tám tệp từ `.../files/wp-content/themes/eyecare-child/` vào đúng đường dẫn child theme; đặt ba option `obs_seo_schema.enable_dealer`, `obs_seo_gmb.enable_schema`, `obs_seo_local_seo.schema_enable` về `1` qua `wp option patch update`; xóa object cache và LiteSpeed cache; kiểm tra lại HTML. Không nhập lại toàn bộ database khi chỉ cần khôi phục ba option.

## Cần xác minh

- Fanpage nào là chính thức: `BenhVienMatHNBN` hay `benhvienmathanoibacninh`, hoặc cả hai có cùng Page ID. HTTP 200 ở cả hai URL không chứng minh cùng chủ sở hữu.
- Nguồn, quyền sử dụng và điều kiện hiển thị công khai của điểm đánh giá 5/6 trong GMB; chưa có chứng cứ để công bố dưới dạng `AggregateRating`.
- Hồ sơ pháp lý/giấy phép, dữ liệu bác sĩ và các nội dung y khoa đã được đánh dấu trong `docs/content-audit/2026-09-29/` cần người phụ trách chuyên môn duyệt. Đợt sửa này không thay nội dung chuyên môn.
- Search Console, WAF/CDN và log truy cập bot thật chưa có quyền kiểm tra; HTTP 200 từ trình duyệt không chứng minh đã index hay xuất hiện trong ChatGPT Search.
