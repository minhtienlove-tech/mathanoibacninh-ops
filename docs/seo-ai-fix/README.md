# Sửa nhận diện thực thể và SEO kỹ thuật — 30/09/2026

Phạm vi: WordPress production `https://mathanoibacninh.com/`, child theme `eyecare-child`, plugin đang hoạt động `obs-seo-suite 2.22.1i`. Bản quét công khai [trước](before.json), [sau đợt đầu](after-initial.json) và [sau cùng](after.json) chỉ chứa các trường công khai phục vụ đối chiếu, không có dữ liệu bệnh nhân. Script đọc: `scripts/seo-schema-audit.py`.

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

## Đã triển khai và đối chiếu sau sửa

| Vấn đề | Trước sửa | Nguồn/file/hook/option đã sửa | Sau sửa trên production | Trạng thái |
| --- | --- | --- | --- | --- |
| Gán bệnh viện là đại lý ô tô | Trang chủ có `/#dealer`; nhiều trang có `/#localbusiness` chứa `AutoDealer` | `class-obs-schema.php` / `wp_head` 10 / `obs_seo_schema.enable_dealer`; `class-obs-gmb.php` / `wp_head` 12 / `obs_seo_gmb.enable_schema` | Cả 11 URL mẫu không còn `AutoDealer`, `/#dealer` hay `/#localbusiness` | **ĐÃ SỬA** |
| Node địa phương trùng và điểm đánh giá chưa rõ nguồn | Hai `/#localbusiness` khác dữ liệu; GMB xuất `AggregateRating` 5/6 | `class-obs-local-seo.php` / `wp_head` 10 / `obs_seo_local_seo.schema_enable`; tắt GMB schema như trên | Không còn hai node địa phương hoặc rating trên 11 URL | **ĐÃ SỬA markup**; nguồn đánh giá **CẦN XÁC MINH** |
| Facebook `sameAs` mâu thuẫn | Hai URL Facebook khác nhau | `theme/eyecare-child/inc/schema-y-te.php`, fallback trong `inc/lien-he-noi.php` | Theo lựa chọn trực tiếp của người quản trị ngày 30/09/2026, Hospital xuất đúng `https://www.facebook.com/benhvienmathanoibacninh`; footer/liên hệ dùng cùng URL | **ĐÃ SỬA theo xác nhận người quản trị** |
| Giá trị y khoa không hợp lệ trong schema | Validator báo `Ophthalmologic` sai kiểu `medicalSpecialty`; `medicalAudience: "Patient"` sai kiểu; `worksFor` không thuộc `Physician` | `inc/schema-y-te.php`, `inc/tac-gia-bac-si.php`, `inc/noi-dung-lien-he-seo.php` | Dùng enum `https://schema.org/Ophthalmology`, object `Patient`, bỏ `worksFor`; Validator mẫu trang chủ, bác sĩ, bài viết, liên hệ đều 0 lỗi/0 cảnh báo | **ĐÃ SỬA** |
| Khẳng định đã được bác sĩ duyệt trước khi có chứng cứ | Schema bài viết luôn gắn `reviewedBy` dù không có meta ngày duyệt (đếm được 0 post có meta này) | `inc/tac-gia-bac-si.php` | Chỉ gắn `reviewedBy` cùng `lastReviewed` khi có meta `_bvmat_bac_si_duyet`; bài mẫu không còn lời khẳng định duyệt chưa xác minh | **ĐÃ SỬA markup**; quy trình duyệt thực tế **CẦN XÁC MINH** |
| Trang chủ thiếu canonical | Không có canonical HTML/HTTP | `theme/eyecare-child/inc/schema-y-te.php` / `wp_head` 4 | Một canonical tự trỏ HTTPS; các URL khác vẫn tự trỏ | **ĐÃ SỬA** |
| Tên địa giới hiện hành chưa đồng bộ | Một số schema/footer/trang khu vực ghi `Tỉnh Bắc Ninh` cho hiện tại | `schema-y-te.php`, `footer.php`, `inc/khu-vuc.php` và năm HTML ở `content/khu-vuc/` | Schema `addressRegion`, footer và các phần đã sửa ghi `Thành phố Bắc Ninh` | **ĐÃ SỬA phần trực tiếp**; nội dung khác thuộc đợt rà y khoa riêng |
| HTTP không chuyển sang HTTPS | `http://mathanoibacninh.com/` trả 200 | Cần cấu hình webserver/hosting ngoài child theme | Canonical trang chủ trỏ HTTPS nhưng HTTP vẫn cần redirect | **CHƯA SỬA — ngoài phạm vi chỉnh sửa của AGENTS.md** |

Mẫu định danh trước/sau: trước có `/#to-chuc` (`Hospital`, `MedicalOrganization`) cùng `/#dealer` (`AutoDealer`) và hai `/#localbusiness`; sau chỉ còn `/#to-chuc` mô tả cơ sở khám chữa bệnh. Bản ghi đầy đủ cho từng URL và mọi trường JSON-LD có trong hai tệp audit liên kết đầu tài liệu.

Đã triển khai commit `206bcf8`, `74bf209` và `f5e7cee` lên production ngày 30/09/2026: tắt ba emitter schema bằng `wp option patch update` (`obs_seo_schema.enable_dealer=0`, `obs_seo_gmb.enable_schema=0`, `obs_seo_local_seo.schema_enable=0`), sau đó sửa giá trị enum, fanpage và điều kiện review trong child theme. Không sửa plugin, core hay database bằng search-replace. Các tệp live đã đối chiếu SHA-256 với Git; `php -l` đạt trên tất cả PHP đã upload; `wp cache flush` và `wp litespeed-purge all` thành công sau mỗi đợt.

Audit HTML công khai sau purge trên 11 URL gồm trang chủ, giới thiệu, bác sĩ, liên hệ, dịch vụ, bảng giá, bài kiến thức và các trang khu vực: 11/11 HTTP 200, canonical HTTPS tự trỏ đúng một thẻ, không `noindex`/`X-Robots-Tag`, không lỗi parse JSON-LD. `AutoDealer`, `/#dealer`, `/#localbusiness` và `AggregateRating` đều không còn; mỗi URL vẫn có đúng một thực thể `Hospital`/`MedicalOrganization` `/#to-chuc`, địa chỉ vùng là `Thành phố Bắc Ninh`, `medicalSpecialty` hợp lệ và `sameAs` Facebook do người quản trị xác nhận. Trang chủ giảm từ 6 xuống 3 node JSON-LD; các schema `Article`, `FAQ`, `Physician` liên quan vẫn có trên URL tương ứng. Hai node `WebSite` chung `/#website` còn lại thống nhất tên và URL; chưa tắt plugin tổng thể để tránh mất schema trang/bài hợp lệ. Schema Markup Validator kiểm tra trực tiếp trang chủ, bác sĩ, bài kiến thức và liên hệ: mỗi URL 0 lỗi, 0 cảnh báo sau sửa.

`/robots.txt`, `/wp-sitemap.xml` và các sitemap OBS được kiểm tra lại, đều HTTP 200. `scripts/verify-live.ps1 -CheckSsh` đạt cho sáu URL chính và SSH/WP. Trình duyệt desktop và khung mobile khoảng 518 px đã kiểm tra trang chủ, `/khu-vuc/`, footer, menu và giao diện đặt lịch; không thấy tràn ngang. Form đặt lịch chỉ được kiểm tra giao diện và trạng thái nhập liệu, không gửi lịch thử.

Google Search Console: kiểm tra trực tiếp bản live của trang chủ ngày 30/09/2026, trả “Google có thể lập chỉ mục URL này”, “Trang có thể lập chỉ mục” và “URL không có tính năng nâng cao”. Đã bấm yêu cầu lập chỉ mục; Search Console xác nhận “Đã yêu cầu lập chỉ mục”, URL vào hàng đợi ưu tiên thu thập. Báo cáo dữ liệu đã lập chỉ mục trước đó vẫn có thể hiển thị review snippet cũ cho đến khi Google thu thập lại; việc gửi yêu cầu không bảo đảm thời điểm hoặc vị trí xếp hạng.

## Sao lưu và rollback

Backup production riêng tư: `/home/jwhxtzru/backups/seo-entity-20260930-154327/` (tám tệp và database trước đợt đầu), `/home/jwhxtzru/backups/seo-entity-followup-20260930/` (ba tệp và database trước sửa fanpage/enum), `/home/jwhxtzru/backups/seo-validator-followup-20260930/` (hai tệp và database trước sửa audience/review). Không đưa SQL vào Git.

Khôi phục riêng đợt này: chép lại tám tệp từ `.../files/wp-content/themes/eyecare-child/` vào đúng đường dẫn child theme; đặt ba option `obs_seo_schema.enable_dealer`, `obs_seo_gmb.enable_schema`, `obs_seo_local_seo.schema_enable` về `1` qua `wp option patch update`; xóa object cache và LiteSpeed cache; kiểm tra lại HTML. Không nhập lại toàn bộ database khi chỉ cần khôi phục ba option.

Khôi phục riêng hai đợt bổ sung mà vẫn giữ sửa AutoDealer/canonical: chép file từ `seo-validator-followup-20260930/files/inc/` để đảo audience/review, sau đó từ `seo-entity-followup-20260930/files/inc/` để đảo Facebook/enum; xóa cache và kiểm tra HTML. Việc đảo theo thứ tự ngược giúp giữ đúng trạng thái ngay trước mỗi đợt.

## Cần xác minh

- Người quản trị website đã chọn `https://www.facebook.com/benhvienmathanoibacninh` ngày 30/09/2026; chưa đối chiếu Page ID/quyền sở hữu trực tiếp trong Meta. URL cũ `BenhVienMatHNBN` không còn dùng trong schema hoặc link Facebook của child theme.
- Nguồn, quyền sử dụng và điều kiện hiển thị công khai của điểm đánh giá 5/6 trong GMB; chưa có chứng cứ để công bố dưới dạng `AggregateRating`.
- Hồ sơ pháp lý/giấy phép, dữ liệu bác sĩ và các nội dung y khoa đã được đánh dấu trong `docs/content-audit/2026-09-29/` cần người phụ trách chuyên môn duyệt. Đợt sửa này không thay nội dung chuyên môn.
- Nguồn và sự đồng ý sử dụng các lời đánh giá/ảnh bệnh nhân vẫn đang hiển thị trên website cần xác nhận riêng; đã bỏ `AggregateRating` chưa chứng minh, chưa đụng vào nội dung lời chứng thực.
- HTTP → HTTPS cần quy tắc ở lớp webserver/hosting; không sửa ngoài child theme trong đợt này. WAF/CDN và log truy cập bot thật chưa có dữ liệu để xác nhận; HTML công khai và Search Console không chứng minh đã xuất hiện trong ChatGPT Search.
