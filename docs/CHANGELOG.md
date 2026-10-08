# Production changelog

## 2026-10-08 — thẻ “Bài viết mới” trong các trang dịch vụ

- Trang `/dich-vu/phau-thuat-quem/` dùng danh sách bài gọn riêng với thẻ lớn ở trang chủ. Trên điện thoại, tiêu đề hai dòng bị cắt và ảnh poster dọc trong ô nhỏ bị xén. Chỉ sửa `theme/eyecare-child/style.css`, giới hạn dưới `.eyecare-trang--dich-vu-con .eyecare-bai__cot-ben--moi`: thêm nền, viền bo 14 px, khoảng đệm, bóng nhẹ và hiệu ứng hover/focus cho từng bài; hiển thị toàn bộ ảnh bằng `object-fit: contain`; bỏ giới hạn hai dòng của tiêu đề ở màn hình dưới 550 px. Hỗ trợ `prefers-reduced-motion`.
- Sau `git pull`, triển khai bằng `scripts/deploy-service-news-card-20261008.sh` dưới khóa deploy và đối chiếu SHA-256. Backup `style.css` cùng database tại `/home/jwhxtzru/backups/service-news-card-20261008-105256/`; xóa WordPress/LiteSpeed cache. Mã nguồn và script ở commit `ffb1dab`.
- Kiểm tra production: `php -l` cho `footer.php` và `page-lien-he.php` đạt, `verify-live.ps1 -CheckSsh` đạt, sáu URL chính HTTP 200; checksum production khớp bản triển khai. Trình duyệt xác nhận thẻ có đệm 11,2 × 8,8 px, bo 14 px và bóng; ảnh không bị xén. Ở viewport 390 và 320 px, chiều cao tiêu đề bằng chiều cao cuộn nội dung, không bị cắt và trang không tràn ngang. Rollback chọn lọc: khôi phục `style.css` từ thư mục backup rồi xóa WordPress/LiteSpeed cache; database không thay đổi.

## 2026-10-08 — thẻ bài nổi bật “Bài viết mới” trên trang chủ

- Thẻ bài giới thiệu BSCKI. Đặng Công Hải trên trang chủ trước đây có phần chữ sát mép dưới và hai bên, viền bo nhỏ và thiếu chiều sâu. Chỉ sửa `theme/eyecare-child/assets/home-refresh.css`: bo góc 20 px, thêm viền và bóng nhẹ, tăng đệm nội dung (desktop 24/26/28 px, điện thoại 20/20/24 px), giãn khoảng cách giữa ngày đăng, tiêu đề, đoạn dẫn và liên kết. Thêm hiệu ứng nâng nhẹ khi rê chuột, trạng thái bàn phím `focus-within` và tắt chuyển động theo `prefers-reduced-motion`; ảnh poster dọc vẫn hiển thị trọn với `object-fit: contain`.
- Sau `git pull`, triển khai bằng `scripts/deploy-home-news-card-20261008.sh` dưới khóa deploy và đối chiếu SHA-256 trước/sau. Đã sao lưu CSS và database tại `/home/jwhxtzru/backups/home-news-card-20261008-103906/`, xóa WordPress/LiteSpeed cache. Mã nguồn và script ở commit `c294c79`.
- Kiểm tra production: `php -l` cho `footer.php` và `page-lien-he.php` đạt; `verify-live.ps1 -CheckSsh` đạt, sáu URL chính đều HTTP 200. Trình duyệt xác nhận bo góc 20 px, bóng, đệm desktop 24/26/28 px và điện thoại 20/20/24 px tại 390 px, không tràn ngang và không cắt ảnh bác sĩ. Rollback chọn lọc: khôi phục `assets/home-refresh.css` từ thư mục backup trên rồi xóa WordPress/LiteSpeed cache; database không thay đổi.

## 2026-10-08 — giữ trọn ảnh bác sĩ trong thẻ bài Tin bệnh viện

- Trang `/tin-tuc/goc-bac-si/` trước đây ép poster dọc 2:3 vào khung ngang 16:9 với `object-fit: cover`, làm cắt phần mặt của BSCKI. Đặng Công Hải và ThS.BS Lê Như Tùng. Thêm nhận diện tỷ lệ ảnh từ metadata WordPress và kiểu khung 3:4, `object-fit: contain` cho ảnh dọc; ảnh ngang giữ nguyên 16:9.
- Áp dụng cùng kiểu thẻ cho lưu trữ chuyên mục, `/tin-tuc/`, tin liên quan và danh sách bài trong hồ sơ bác sĩ. Chỉ sửa sáu tệp child theme: `functions.php`, `assets/news.css`, `template-parts/archive-news.php`, `page-tin-tuc.php`, `template-parts/single-news.php`, `single-eyecare_bac_si.php`. Không sửa ảnh gốc hoặc nội dung bài.
- Sau `git pull`, triển khai bằng `scripts/deploy-news-portrait-cards-20261008.sh` dưới khóa deploy, đối chiếu checksum production/staging, sao lưu sáu tệp và database tại `/home/jwhxtzru/backups/news-portrait-cards-20261008-084006/`. Script có khôi phục file nếu lỗi sau khi bắt đầu áp dụng; WordPress và LiteSpeed cache đã được xóa.
- Kiểm tra production: `php -l` năm tệp PHP mới và hai tệp tối thiểu đạt; `verify-live.ps1 -CheckSsh` đạt, URL chuyên mục trả 200. Trình duyệt xác nhận hai poster dùng class ảnh dọc, khung 3:4 và `object-fit: contain`, thấy toàn bộ khuôn mặt ở desktop và viewport 390 px; thẻ ảnh ngang vẫn 16:9 và trang di động không tràn ngang. Rollback chọn lọc: chép lại sáu tệp từ backup và xóa cache; database không bị thay đổi.

## 2026-10-08 — bài giới thiệu BSCKI. Đặng Công Hải và ảnh chân dung tại “Bài viết mới”

- Xuất bản bài ID 1736 tại `/tin-tuc/goc-bac-si/gioi-thieu-bscki-dang-cong-hai/`, thuộc chuyên mục Góc bác sĩ và liên kết với hồ sơ bác sĩ ID 336. Dùng lại ảnh bệnh viện đã cung cấp (media ID 1693), cập nhật alt mô tả đúng nhân vật. Các mốc hơn 20 năm kinh nghiệm và hơn 10.000 ca phẫu thuật dựa trên xác nhận của người phụ trách; không gán chức danh cũ tại Bệnh viện Mắt Sông Cầu vì các nguồn mô tả không thống nhất.
- Sửa `front-page.php` và `style.css` của child theme: thẻ bài viết có ảnh chân dung giữ toàn bộ ảnh trong khung phù hợp trên desktop và điện thoại; ảnh ngang vẫn dùng bố cục cũ. Bài mới của bác sĩ Hải và ảnh bác sĩ Lê Như Tùng ở các thẻ liên quan không còn bị cắt khuôn mặt.
- Đã `git pull` trước khi sửa, triển khai dưới khóa deploy với đối chiếu checksum. Backup theme và database: `/home/jwhxtzru/backups/home-doctor-portraits-20261008-081852/`; backup trước khi xuất bản bài và đổi alt: `/home/jwhxtzru/backups/doctor-hai-blog-20261008-082644/`. Lần chạy xuất bản đầu dừng ở bản nháp ID 1736 do WP-CLI chuẩn hóa một dấu xuống dòng cuối; script được sửa để tiếp tục đúng bản nháp, không tạo bài trùng.
- Kiểm tra production: `php -l` các file PHP liên quan đạt; `verify-live.ps1 -CheckSsh` đạt; bài công khai có một H1, canonical tự trỏ, mô tả, ảnh OG, Article/Breadcrumb schema và liên kết về hồ sơ bác sĩ; trang hồ sơ có liên kết ngược về bài. Xem trực tiếp trang chủ và bài ở desktop, thử viewport 390 px: ảnh chân dung hiển thị trọn và không tràn ngang.
- Rollback chọn lọc: chép lại hai file theme từ backup đầu; đưa bài ID 1736 về nháp và khôi phục alt ảnh từ backup thứ hai, sau đó xóa WordPress/LiteSpeed cache. Không cần khôi phục toàn bộ database nếu chỉ hoàn tác các thay đổi này.

## 2026-10-08 — mở rộng slider hero trang chủ

- Bố cục cũ đặt banner trong cột phải, ảnh hiển thị khoảng 866 × 371 px tại viewport 1280 px. Trên desktop, slider mới nằm sát dưới menu, rộng toàn bộ khung nhìn và giữ đúng tỷ lệ ảnh 1600:609; phần H1, giờ tiếp nhận và nút đặt lịch chuyển thành dải xanh gọn bên dưới. Giữ nguyên năm ảnh và bộ điều khiển trượt; không lấy ảnh hay nhận diện từ trang tham khảo.
- Chỉ đổi `assets/home-critical.css`, `inc/trang-chu.php` và `functions.php`. Thuộc tính `sizes` của ảnh và `imagesizes` của preload cùng phản ánh khổ mới để trình duyệt chọn ảnh đủ nét. Ảnh dùng `object-fit: contain`, không cắt chữ/logo in sẵn. Dưới 701 px giữ bố cục cũ của điện thoại.
- Xem trước bằng HTML công khai cùng CSS mới: tại 1280 px ảnh rộng 1265 px, cao 481 px; tại 1920 px rộng 1905 px, cao 725 px. Kiểm tra các viewport 390/701/1024/1920 px không tràn ngang. Triển khai qua `scripts/deploy-home-hero-20261008.sh` sau khi đối chiếu checksum production và staging. Backup ba file cùng database: `/home/jwhxtzru/backups/home-hero-20261008-072455/`; script có hoàn tác tự động nếu lỗi trong lúc áp file. Sau triển khai, trang công khai tại 1280 px vẫn có ảnh 1265 × 481 px, chọn nguồn ảnh full 1600 px; viewport 390 px không tràn ngang. Nút tạm dừng và chấm chọn ảnh hoạt động. `php -l` bốn file PHP liên quan/kiểm tra tối thiểu đạt, checksum production khớp staging 3/3, `verify-live.ps1 -CheckSsh` đạt. Rollback chọn lọc: chép lại ba file từ backup trên và xóa WordPress/LiteSpeed cache; database không thay đổi.

## 2026-10-07 — nội dung 6 hồ sơ nhân sự chuyên môn

- Biên tập lại 6 bản nháp trong `docs/drafts/bac-si/` từ dữ liệu hồ sơ hiện có; bỏ số ca, thành tích, kỹ thuật, lịch làm việc và mô tả quy trình cá nhân chưa có nguồn đối chiếu. Nội dung y khoa chung được tách khỏi thông tin cá nhân. Hai cử nhân khúc xạ/cận lâm sàng được gọi đúng chức danh, không giới thiệu là bác sĩ.
- `single-eyecare_bac_si.php` dùng nhãn “Hồ sơ nhân sự chuyên môn” cho cử nhân và chỉ in thẻ thành tích khi trường nguồn thành tích có dữ liệu. Vai trò Lê Như Tùng trong nội dung và metadata thống nhất là “Cố vấn chuyên môn”; bỏ chức danh Chủ tịch HĐQT chưa có văn bản xác nhận.
- Triển khai qua `scripts/deploy-doctor-bios-20261007.sh`, sao lưu file và database trước khi đổi nội dung WordPress. Lần chạy đầu dừng sau bài 337 do phép so hash khác đúng một LF cuối; đã khôi phục ngay nội dung, meta và template từ backup. Lần chạy lại dùng so sánh byte đầu ra WP-CLI với tệp nguồn và đăng đủ 6 bài; backup `/home/jwhxtzru/backups/doctor-bios-20261007-164919/`.
- Kiểm tra trang công khai thấy bộ tự chèn liên kết đưa tên bệnh viện trong nội dung sang một bài kiến thức không liên quan. Sáu bản HTML được thay bằng liên kết rõ ràng về trang chủ và cập nhật qua `scripts/deploy-doctor-bios-linkfix-20261007.sh` sau khi sao lưu lại database tại `/home/jwhxtzru/backups/doctor-bios-linkfix-20261007-165413/`. Preflight dừng hai lần trước khi đổi dữ liệu do hash nhập sai cho bài 674, đã sửa rồi chạy thành công.
- Sau triển khai: 6/6 URL `/bac-si/<slug>/` trả 200, có 905–1.349 từ nội dung hiển thị và không còn câu mặc định “đang được cập nhật”; 4 bác sĩ dùng schema `Physician`, hai cử nhân dùng `Person` và nhãn hồ sơ riêng; 6/6 liên kết tên bệnh viện trong phần giới thiệu về trang chủ, không còn liên kết sai. `php -l` với ba file yêu cầu và `verify-live.ps1 -CheckSsh` đạt. Rollback chọn lọc: dùng `database-before.sql` để đối chiếu, hoặc đưa `*-before.html` vào đúng post ID, khôi phục template và meta 337 từ backup đầu rồi xóa cache.

## 2026-10-07 — rà soát 6 bản nháp hồ sơ nhân sự (chưa xuất bản)

- Đối chiếu 6 tệp trong `docs/drafts/bac-si/` với dữ liệu hồ sơ WordPress; kiểm tra số từ và 37 đích liên kết nội bộ (đều HTTP 200). Báo cáo chi tiết ở `docs/drafts/bac-si/AUDIT-2026-10-07.md`.
- Sửa ba diễn đạt có thể gây hiểu nhầm về điều trị quặm, phạm vi công việc của cử nhân khúc xạ và mức đau của phép đo. Còn nhiều kỹ thuật, quy trình cá nhân và thành tích chưa có nguồn/duyệt; giữ toàn bộ nội dung ở bản nháp, không ghi vào database.

## 2026-10-07 — trang cá nhân riêng cho từng bác sĩ /bac-si/<slug>/ (đã triển khai ~15:10)

- Post type `eyecare_bac_si` nay công khai với đường dẫn `/bac-si/<slug>/` (vẫn loại khỏi tìm kiếm nội bộ, không có trang lưu trữ; danh sách chung vẫn là `/doi-ngu-bac-si/`). Rewrite được làm mới một lần qua option `eyecare_bac_si_rewrite_version`. Thêm, sửa, xóa bác sĩ vẫn ở Admin → Đội ngũ bác sĩ; mỗi bác sĩ đã đăng tự có trang riêng.
- Hộp thông tin bác sĩ thêm hai ô: **Facebook phòng khám** (chỉ nhận HTTPS facebook.com) và **Liên kết và nguồn khác** (mỗi dòng `Tên | https://...`, tối đa 20, dòng không hợp lệ bị bỏ). Bật ô Tóm tắt (excerpt) làm mô tả ngắn và meta description. Hộp hiển thị đường dẫn trang cá nhân; danh sách bác sĩ có thao tác “Bài viết (n)” mở danh sách bài đã lọc theo bác sĩ.
- Bài viết có thêm ô chọn **“Bác sĩ được giới thiệu trong bài”** (nhiều bác sĩ, meta `_eyecare_bac_si_lien_quan`). Trang cá nhân liệt kê tối đa 12 bài đã đăng, mới nhất trước, gồm bài được giới thiệu và bài bác sĩ đứng tên viết/duyệt.
- `single-eyecare_bac_si.php`: ảnh đại diện, học vị, chức danh, chuyên môn, tóm tắt, nút Đặt lịch khám / Facebook cá nhân / Facebook phòng khám, mục Chuyên môn và kinh nghiệm (theo quy tắc nguồn đối chiếu sẵn có), giới thiệu dài, Liên kết và nguồn khác, lưới bài viết (dùng thẻ của trang tin), liên kết sang bác sĩ khác. Dùng `div` thay vì `main` để không lồng vào `<main id="noi-dung">`.
- `eyecare_bac_si_ho_so_url()` trả permalink trang cá nhân khi có bản ghi đã đăng (tìm theo ID, mảng, WP_Post hoặc tên kèm học vị bất kỳ), nên khối đội ngũ, tên bác sĩ trong bài, khung người viết/duyệt và schema đều dẫn về `/bac-si/<slug>/`; không có bản ghi thì vẫn về `/doi-ngu-bac-si/#bac-si-...`. Hồ sơ dạng gập trên `/doi-ngu-bac-si/` có thêm liên kết “Xem trang cá nhân và bài viết →”.
- SEO/schema (`inc/trang-bac-si.php`): một title, meta description, OG `profile`, Twitter; JSON-LD gộp vào @graph chung gồm ProfilePage + Physician (sameAs Facebook cá nhân và phòng khám, worksFor bệnh viện) + BreadcrumbList. Gỡ trên riêng trang này đầu ra OBS: Open Graph, Article schema, breadcrumb schema/hiển thị, author bio, reading time, TOC, khối AI citation, FAQ discovery.
- Dữ liệu: bác sĩ ID 337 (Lê Như Tùng) điền Facebook phòng khám `https://www.facebook.com/ThacsiBsiTung/`. Tạo **bản nháp** bài ID 1719 “Giới thiệu Cố vấn chuyên môn cao cấp – ThS.BS Lê Như Tùng” (chuyên mục Góc bác sĩ, gắn bác sĩ 337) từ nội dung người phụ trách gửi; chưa có ảnh đại diện, chờ người phụ trách xem và bấm Đăng.
- Test mới `tests/doctor-page-test.php` (post type công khai, URL hồ sơ = permalink, tìm theo tên kèm học vị, phân tích liên kết loại http/javascript, khối đội ngũ không dẫn Facebook); cập nhật `tests/home-team-section-test.php` sang URL mới. Trên server cả hai đạt. Lần chạy đầu phát hiện tên “Cử nhân …” không khớp, đã sửa hàm tìm bằng khớp đuôi slug dài nhất rồi triển khai lại.
- Backup `~/backups/trang-bac-si-20261007-151010/` (5 tệp gốc, `prod-before.md5`, `database-before.sql`, giá trị Facebook phòng khám cũ, `draft-created.txt`, `ROLLBACK.txt`). Production trước khi ghi khớp HEAD `684194a` cho 4 tệp sửa; staging khớp checksum local, `php -l` đạt, áp dưới `flock ~/website-ops-deploy.lock`, xóa object cache và LiteSpeed. Kiểm tra công khai: sáu `/bac-si/<slug>/` trả 200, slug không tồn tại trả 404; 1 H1, 1 description, 1 og:description, canonical tự trỏ; 1 khối JSON-LD; trang chủ dẫn đủ sáu trang cá nhân. Chrome headless: bấm ảnh cố vấn trên trang chủ và “Xem trang cá nhân” trên `/doi-ngu-bac-si/` đều tới đúng trang; 1280 px và 375 px không tràn ngang; không lỗi JS.

## 2026-10-07 — làm rõ giao diện trang giấy phép hoạt động

- Trang `/giay-phep-hoat-dong/` trước đây dùng chữ trắng trên nền xanh rất nhạt, tiêu đề quá lớn và các thẻ tóm tắt chồng lên phần nội dung. Đổi sang phần đầu màu sáng với chữ xanh đậm, thẻ hiển thị trực tiếp số giấy phép/cơ quan cấp/ngày cấp và liên kết bản chụp; thêm liên kết nhảy đến các mục chính.
- Chỉ sửa `theme/eyecare-child/page-giay-phep-hoat-dong.php` và `theme/eyecare-child/assets/page-layouts.css`; giữ nguyên nguồn dữ liệu pháp lý và nội dung các mục. Các mục nội dung thành thẻ dễ đọc; có bố cục riêng cho màn hình nhỏ và không che bản chụp giấy phép.
- Đã sao lưu hai file gốc và database tại `/home/jwhxtzru/backups/gphd-design-20261007-140933/`; script `scripts/deploy-gphd-design-20261007.sh` chỉ ghi khi checksum bản production khớp, có khóa deploy và in sẵn lệnh rollback chọn lọc. `php -l` trên staging/production đạt, xóa object/LiteSpeed cache, `verify-live.ps1 -CheckSsh` đạt. HTML công khai còn đủ số 444/BYT-GPHĐ, cơ quan cấp, ngày cấp, 5 mục nội dung và ảnh scan. Kiểm tra Chrome desktop và viewport 390 px thấy chữ đầu trang tương phản tốt, thẻ thông tin không đè nội dung, không tràn ngang.

## 2026-10-07 — ảnh riêng cho 12 bài giới thiệu chuyên mục (đã triển khai)

- Trước khi sửa, bài ID 1678–1689 đều không có `_thumbnail_id`; `inc/anh-bai-du-phong.php` đưa cùng một ảnh khám mắt dự phòng vào các thẻ bài. Tạo 12 ảnh biên tập minh họa khác nhau theo đúng 5 chủ đề kiến thức và 7 chủ đề tin bệnh viện; không dùng hình này làm bằng chứng về bác sĩ, người bệnh, ưu đãi hay hoạt động thực tế.
- Từ PNG nguồn tạo 12 WebP rộng 1200 px bằng `scripts/prepare-category-intro-images.py`; mỗi ảnh 34–82 KB, tổng 708.398 byte. Ảnh tối ưu, manifest SHA-256/alt và bảng ánh xạ bài–attachment lưu trong `docs/drafts/category-intros/images/`. PNG nguồn nằm cục bộ trong `image-source/`, không đưa vào Git.
- Dưới khóa `~/website-ops-deploy.lock`, `scripts/deploy-category-intro-images-20261007.sh` kiểm tra đủ 12 ID/slug/trạng thái, SHA và ảnh đại diện đang trống; sao lưu database, rồi nhập attachment **1707–1718** và gắn từng ảnh cho đúng bài. Media có alt mô tả cảnh, caption nêu rõ ảnh tạo bằng AI, meta `_eyecare_ai_illustration=1`. Không sửa theme hay bài viết. Đã xóa object cache và LiteSpeed cache.
- Backup và rollback chọn lọc: `/home/jwhxtzru/backups/category-images-20261007-112802/` có `database-before.sql`, WebP gốc, `featured-before.tsv`, `imported.tsv`, `ROLLBACK.txt`. Nếu cần hoàn tác, chỉ gỡ `_thumbnail_id` cho 12 ID theo bảng cũ; giữ attachment cho đến khi có xác nhận xóa.
- Kiểm tra sau triển khai: 12/12 URL bài chuẩn và 12/12 URL ảnh HTTP 200, đúng 12 file WebP khác nhau và MIME `image/webp`; ảnh đại diện xuất hiện trong HTML công khai, không redirect. Kiểm tra trực quan `/tin-tuc/` thấy các thẻ dùng ảnh khác nhau. PHP lint `footer.php`/`page-lien-he.php` và `verify-live.ps1 -CheckSsh` đều đạt.

## 2026-10-07 — bấm bác sĩ nào cũng mở đúng hồ sơ trên /doi-ngu-bac-si/ (đã triển khai ~11:29)

- Trước đây bấm ảnh/tên ba bác sĩ có Facebook (Lê Như Tùng, Đặng Công Hải, Bùi Văn Cảnh) mở thẳng Facebook ở tab mới; ba người còn lại về `/doi-ngu-bac-si/#bac-si-...` nhưng sáu hồ sơ luôn mở hết.
- `eyecare_bac_si_trang_ca_nhan_url()` nay luôn trả hồ sơ nội bộ `/doi-ngu-bac-si/#bac-si-<slug>`. Áp dụng cho khối đội ngũ (trang chủ, Giới thiệu, Đội ngũ), tên bác sĩ trong bài viết, khung người đứng tên/duyệt bài và chân trang. Liên kết Facebook vẫn hiện trong hồ sơ dưới dạng “Trang cá nhân Facebook ↗”.
- Hồ sơ trên `/doi-ngu-bac-si/` thành accordion: tiêu đề là nút có `aria-expanded`/`aria-controls`, bấm mở một người thì những người khác gập lại. `assets/doctor-profiles.js` (chỉ nạp ở trang này, `defer`) mở đúng người theo `#bac-si-...` và cuộn tới; không có hash thì mở người đầu tiên; bấm lại cùng hash vẫn mở được. Không có JS thì toàn bộ hồ sơ vẫn mở (HTML mặc định không `hidden`). Bỏ dòng chức danh lặp trong khung vì đã có ở tiêu đề.
- `tests/home-team-section-test.php` thêm kiểm tra: khối đội ngũ không có `facebook.com`/`target="_blank"` và có đủ sáu liên kết hồ sơ nội bộ.
- Backup `~/backups/doi-ngu-accordion-20261007-112854/` (5 tệp gốc + `database-before.sql`). Staging `~/staging/doi-ngu-accordion/`, checksum khớp local, `php -l` đạt, áp dưới `flock ~/website-ops-deploy.lock`, xóa object cache và LiteSpeed. Smoke test trên server đạt. Chrome headless trên production: từ trang chủ và Giới thiệu bấm bác sĩ → về đúng `#bac-si-...`, chỉ người đó mở, đầu khung cách đỉnh 125 px (dưới header); đổi người, gập rồi bấm lại cùng hash, mở trực tiếp không hash đều đúng; 375 px không tràn ngang; không lỗi JS.

## 2026-10-07 — khối đội ngũ đồng bộ ảnh và chức danh từ Admin (đã triển khai ~10:58)

- Trước đây khối đội ngũ (trang chủ, `/gioi-thieu/`, `/doi-ngu-bac-si/`) luôn dùng ảnh chân dung trong `assets/` của theme, và đổi chức danh có chữ “Chủ tịch HĐQT” thành “Cố vấn chuyên môn cao cấp”, nên ảnh đại diện mới tải lên Admin lúc 08:42–08:47 và chức danh nhập tay không hiện ra.
- Theo yêu cầu người phụ trách, khối này nay lấy ảnh đại diện (cỡ `large` + srcset của WordPress) và chức danh đúng như Admin. Ảnh trong theme chỉ còn là ảnh dự phòng khi bác sĩ chưa có ảnh đại diện; chức danh “Cố vấn chuyên môn cao cấp” chỉ dùng khi ô chức danh trống. Bệnh viện chịu trách nhiệm nội dung in trên poster và chức danh nhập trong Admin.
- `tests/home-team-section-test.php` kiểm tra ảnh và chức danh khớp bản ghi Admin, so tên không phân biệt hoa thường (test cũ đã fail vì tên trong Admin viết hoa).
- Backup: `~/backups/doi-ngu-sync-admin-20261007/` (3 tệp gốc + meta bản ghi 337). Kiểm tra: `php -l` đạt, smoke test đạt trên server, ba URL trả 200 và hiển thị ảnh `2-1…2-6-683x1024.png`.

## 2026-10-07 — công bố giấy phép hoạt động (chưa triển khai)

- Người phụ trách cung cấp ảnh chụp giấy phép và xác nhận số đọc được: **444/BYT-GPHĐ**, Bộ Y tế cấp **06/10/2026**, người ký Thứ trưởng Thường trực Vũ Mạnh Hà, hình thức tổ chức “Bệnh viện chuyên khoa”, chủ sở hữu Công ty CP Bệnh viện Mắt Hà Nội – Bắc Ninh. Trước đợt này `so_gphd` để rỗng nên schema không có khối giấy phép.
- `inc/schema-y-te.php` nhận số giấy phép cùng ngày cấp, cơ quan cấp, người ký và đường dẫn ảnh; thêm `hasCredential` (EducationalOccupationalCredential + recognizedBy GovernmentOrganization + validFrom) bên cạnh `identifier` sẵn có. Thêm bốn hàm đọc dùng chung: `eyecare_co_gphd()`, `eyecare_gphd_url()`, `eyecare_gphd_ngay_hien()`, `eyecare_gphd_anh_url()`. Mọi điểm hiển thị gọi qua các hàm này, nên khi `so_gphd` rỗng thì trang công bố, liên kết chân trang và khối schema tự ẩn — không có chỗ nào gõ lại số bằng tay.
- Trang công bố mới `page-giay-phep-hoat-dong.php` tại `/giay-phep-hoat-dong/`: bảng thông tin dạng `dl` để trình đọc màn hình đọc đúng cặp nhãn–giá trị, khối trích nguyên văn địa chỉ trên bản giấy, mục giờ tiếp nhận, bản chụp giấy phép bấm xem cỡ lớn và mục đối chiếu/khiếu nại. Số thứ tự mục đếm theo mục thật sự được in để không ra hai mục cùng số khi một mục bị ẩn. Ảnh chụp lưu tại `assets/giay-phep/gphd-444-byt-2026.webp` (1496×2000); `eyecare_gphd_anh_url()` kiểm tra tệp tồn tại nên ảnh bị xóa thì mục ảnh biến mất thay vì hiện ô lỗi.
- **Khác biệt địa giới xử lý theo hướng giữ cả hai**: giấy phép cấp 06/10/2026 ghi “tỉnh Bắc Ninh”, còn địa danh hành chính hiện hành là “Thành phố Bắc Ninh” theo Nghị quyết 202/2025/QH15 và 39/2026/QH16. Schema, chân trang và phần chỉ đường tiếp tục dùng địa danh hiện hành; trang công bố trích nguyên văn bản giấy trong khối riêng và giải thích rõ đây là cùng một địa điểm, không phải hai cơ sở.
- **Giờ hoạt động chưa đồng bộ theo giấy phép.** Giấy ghi “24/24 giờ” nhưng bệnh viện chưa xác nhận có trực đêm tiếp nhận người bệnh, nên trường `gphd_gio_tren_giay` để rỗng và trang chỉ công bố giờ tiếp nhận khám theo lịch 07:30–18:00 cùng hướng dẫn gọi tổng đài trước khi đến ngoài giờ. Không mời người bệnh đến vào khung giờ chưa chắc có người tiếp. Mục này còn kèm cảnh báo cấp cứu cho mất thị lực đột ngột, đau mắt dữ dội, hóa chất/dị vật.
- `inc/giay-phep-seo.php` xuất đúng một bộ title, meta description, Open Graph và Twitter cho trang này và gỡ bộ Open Graph trùng của OBS trên riêng trang giấy phép — cùng cách đã áp dụng cho nhóm trang khu vực; dữ liệu OBS giữ nguyên để khôi phục được. Cần thiết vì nội dung nằm trong template nên `post_content` rỗng, để OBS tự sinh thì mô tả sẽ rỗng.
- Chân trang thêm liên kết “Giấy phép hoạt động số 444/BYT-GPHĐ” trong cụm pháp lý, hiển thị đậm hơn hai liên kết còn lại. Khối “Hồ sơ pháp lý” trên trang Về chúng tôi trước đây không có đích nào để đi, nay dẫn sang trang công bố. Không thêm vào menu chính: đây là trang tra cứu khi cần, không phải đích điều hướng thường xuyên.
- `scripts/create-gphd-page-20261007.php` tạo trang WordPress cho slug `giay-phep-hoat-dong`; mặc định chỉ tiền kiểm, dừng nếu theme chưa có số giấy phép, template không tồn tại, hoặc slug đã bị post khác chiếm; lưu ID và trạng thái cũ để rollback chọn lọc. Áp dụng cần `EYECARE_GPHD_APPLY=yes` sau khi backup database.
- Kiểm tra: PHP lint tám tệp PHP mới/sửa đạt; `tests/gphd-helper-test.php` chạy thật bốn hàm đọc với stub WordPress, 7/7 đạt (số giấy phép, URL trang, ngày cấp dạng d/m/Y, URL ảnh, địa chỉ hiển thị dùng địa danh hiện hành, giờ trên giấy để rỗng); JSON-LD sinh ra đúng `identifier` và `hasCredential`; CSS cân ngoặc 797/797 và 1757/1757; dòng kết chuẩn hóa CRLF theo các tệp cùng thư mục; `git diff --check` đạt.
### Đã triển khai 09:58–10:10 ngày 07/10/2026

- Push nhánh `codex/chuyen-muc-bac-si` lên GitHub (chưa từng có trên remote, nên commit chuyên mục `291a2bf` cũng lên cùng đợt).
- Backup `/home/jwhxtzru/backups/gphd-20261007-095757/`: 8 tệp theme bị sửa + `database-before.sql` (18,3 MB, chmod 600). Trạng thái trang ghi ở `page-created.json`.
- **Phát hiện trước khi ghi: hosting có 155 dòng CSS không nằm trong bất kỳ nhánh Git nào** (bố cục hero đội ngũ trang Giới thiệu, `.eyecare-gioi-thieu__hero-visual` và liên quan, sửa trực tiếp trên hosting). Nếu copy đè `assets/page-layouts.css` thì mất. Xử lý bằng cách **ghép** thay vì ghi đè: bản production = (file đang chạy) + (359 dòng giấy phép của commit này), script dừng nếu bản local không bắt đầu đúng bằng bản `291a2bf`. Sau khi ghép, production còn đủ 15 chỗ `eyecare-gioi-thieu__hero-visual` và có 7 chỗ `eyecare-gphd__bang`, tổng 5824 dòng, ngoặc cân 820/820, giữ dòng kết LF như file gốc. **Cần commit 155 dòng đó vào Git để lần deploy sau không phải ghép tay.**
- **Phát hiện: gói 12 chuyên mục đã được áp lên production lúc 08:07 cùng ngày** (212 bài, 12 bài giới thiệu ID 1678–1689) trong khi mục changelog phía trên vẫn ghi "chưa ghi WordPress" — có vẻ triển khai từ máy khác. Đối chiếu checksum 8 tệp theme: 7 tệp khớp commit `291a2bf` sau khi bỏ CRLF, chỉ CSS lệch như trên.
- 12 tệp dàn lên `/home/jwhxtzru/staging/gphd/`, checksum 12/12 khớp bản local, lint PHP 8.2 đạt, rồi áp vào theme dưới `flock /home/jwhxtzru/website-ops-deploy.lock`. Checksum theme vs staging 11/11 khớp.
- Trang tạo qua script: ID **1706** tại `/giay-phep-hoat-dong/`. Xóa `wp cache flush`, `wp litespeed-purge all`, `wp rewrite flush`.
- **Sửa phát sinh trong QA:** HTML công khai lần đầu có 3 khối JSON-LD — OBS sinh thêm `Article` (sai loại cho trang pháp lý, `description` và `author.name` rỗng vì `post_content` rỗng) và `BreadcrumbList` thứ hai trùng nội dung, có emoji trong `name`. `inc/giay-phep-seo.php` gỡ thêm `OBS_Module_Schema::render` (priority 10) và `OBS_Module_Breadcrumb::render_schema` (priority 15) trên riêng trang này; ba khóa `OBS_Loader::get()` đã xác nhận trả đúng instance trên production. Sau khi sửa: còn **1 khối JSON-LD** gồm Hospital+MedicalOrganization (có `hasCredential` 444/BYT-GPHĐ / Bộ Y tế), WebSite, BreadcrumbList.
- QA production đạt: HTTP 200; 1 H1, 1 title, 1 meta description, 1 canonical, 1 og:description, 1 twitter:card; title và description chứa đúng số giấy phép; ảnh `.webp` HTTP 200 `image/webp` 489 KB; 5 mục đánh số 01–05 liên tục; cảnh báo cấp cứu hiển thị; **không có chuỗi "24/24" nào trên trang**; liên kết chân trang xuất hiện trên trang chủ, Giới thiệu, Liên hệ; bốn trang khác vẫn HTTP 200 với 1 H1; `tests/gphd-helper-test.php` chạy trên PHP 8.2 production 7/7 đạt; tính tràn khung ở 320px còn 243 px cho cột `minmax(210px, 1fr)`, không tràn; `error_log` sau deploy không có lỗi từ code này (chỉ warning `igbinary` có từ trước).
- **Chưa làm:** `BreadcrumbList` trùng lặp vẫn còn trên toàn site, chỉ gỡ ở trang giấy phép — cần xử lý riêng. Trường `gphd_gio_tren_giay` vẫn rỗng, chờ bệnh viện xác nhận có trực đêm thật hay không trước khi công bố giờ 24/24 trên giấy. Kiểm tra bố cục trên thiết bị thật chưa làm (bản cài không bật Browser preview), chỉ tính theo CSS.
### Đồng bộ ngược production → Git, 07/10/2026

- So checksum toàn bộ 96 tệp PHP/CSS/JS/JSON của theme (bỏ CRLF) giữa Git và hosting. Ngoài 155 dòng CSS hero đội ngũ nêu ở trên, còn **4 tệp mô hình mắt sửa trực tiếp trên hosting ngày 18–19/09/2026** mà không nhánh Git nào có: `assets/eye-anatomy/appearance.js` (−2), `model.css` (+28), `model.js` (+87/−24), `inc/mo-hinh-mat.php` (±3). Commit cuối của Git cho các tệp này là `c5cd0fa` ngày 16/09.
- Đưa nguyên trạng 5 tệp đó từ hosting vào Git (giữ dòng kết theo Git), checksum sau khi đưa vào khớp hosting 5/5. Không có thay đổi nào trên production trong bước này.
- Hosting còn hai tệp mồ côi ở gốc theme không có trong Git: `equipment-slider.js` (trùng hệt `assets/equipment-slider.js`) và `home-refresh.css` (bản cũ, khác `assets/home-refresh.css`). `functions.php` chỉ nạp bản trong `assets/`, nên hai tệp gốc không được dùng. **Chưa xóa**, chờ xác nhận.

## 2026-10-07 — chuẩn bị chuyên mục mới và hồ sơ bác sĩ

- Tiền kiểm production: 200 bài viết, 144 trang và sáu hồ sơ đội ngũ đang công khai. Trang `/tin-tuc/` và `/tuyen-dung/` còn là trang giữ chỗ, `noindex` và bị loại khỏi OBS sitemap bởi tùy chọn `exclude_ids=7,10,53,59,63,64`. Sáu hồ sơ bác sĩ chưa có nội dung tiểu sử trong WordPress.
- Sao lưu toàn bộ child theme và database trước khi ghi production tại `/home/jwhxtzru/backups/categories-doctors-20261007-prepare1/` (`child-theme-before.tar.gz`, `database-before.sql`); đã kiểm tra archive và SQL. Trình nhập 12 bài chạy dry-run, đối chiếu hai trang ID 53/59 và sitemap option; chưa ghi WordPress tại thời điểm commit này.
- Child theme bổ sung năm chuyên mục kiến thức và bảy chuyên mục tin bệnh viện vào menu; mẫu trang Tin bệnh viện/Tuyển dụng, mẫu lưu trữ và bài tin riêng; kho Kiến thức chỉ liệt kê bài thuộc cây kiến thức. Bộ 12 bài giới thiệu nằm trong `docs/drafts/category-intros/`, chỉ nói về phạm vi chuyên mục, không giả lập phỏng vấn, tuyển dụng, ưu đãi hay lịch khám cộng đồng.
- Quản trị hồ sơ bác sĩ có ô Facebook, nguồn đối chiếu thành tích và nội dung giới thiệu dài; bài/trang có ô chọn người viết, người duyệt và ngày duyệt thực tế. Liên kết tên bác sĩ trỏ Facebook do bệnh viện cung cấp khi có, còn schema tham chiếu hồ sơ nội bộ; gỡ việc tự gán ThS.BS Lê Như Tùng cho bài chưa xác nhận. Thay ảnh poster có chữ thành ảnh chân dung, ẩn các mốc số ca/năm, giải thưởng và chức vụ cũ thiếu nguồn. Người phụ trách xác nhận đã có giấy phép hoạt động nhưng chưa cung cấp số giấy phép, nên schema để trống số.
- Script `scripts/publish-approved-categories-20261007.php` mặc định chỉ tiền kiểm, khi áp dụng sẽ tạo đúng 12 danh mục/bài mở đầu, cập nhật hai trang giữ chỗ và gỡ riêng ID 53/59 khỏi danh sách loại sitemap; lưu ID và giá trị cũ để rollback có chọn lọc. Bài giới thiệu tuyển dụng được đánh dấu riêng để không xuất hiện như một vị trí đang tuyển.
- Kiểm tra trước triển khai: lint 22 tệp PHP mới/sửa bằng PHP local và toàn bộ PHP của gói staged bằng PHP 8.2 trên server; `git diff --check` đạt. Bước ghi production và kiểm tra HTML công khai sẽ cập nhật sau.

## 2026-10-05 — chuẩn bị phiếu bác sĩ duyệt 12 bài y khoa

- Tạo `docs/faq-medical-review-20261003/intro-summary-physician-review.md` từ manifest đã chụp: 12 bài, 20 đoạn trước–sau, nguồn đối chiếu và ô duyệt riêng từng bài. Script tạo phiếu là `scripts/build-medical-review-sheet-20261003.py`; không thay đổi nội dung công khai.
- Chạy lại tiền kiểm WP-CLI trên production ngày 05/10: 12/12 bài còn đúng ID, slug, trạng thái, SHA-256; 20 thay thế nguyên văn và hash đầu ra đều đạt. Kết quả `physician-review=PENDING` cho cả 12; chế độ chỉ đọc xác nhận không ghi WordPress. Chưa có tên/ngày bác sĩ duyệt nên **chưa xuất bản gói 12 bài**. Khi có phiếu duyệt thực tế, tạo backup database mới, chạy dưới khóa triển khai, xóa cache và QA 12 URL.
- Rà soát thân bài sau bản dự thảo thấy thêm bốn nhóm vấn đề trong sáu đoạn ngoài manifest cần bác sĩ xét riêng (bài 569: tiêu chí ‘bình thường’; 572: triệu chứng tự hết không loại trừ TIA; 654: glôcôm có thể không tăng nhãn áp; 656: thời gian cải thiện thị lực sau bóc màng). Đã ghi nhận trong phiếu duyệt với nguồn đối chiếu chính thức; chưa chỉnh nội dung công khai hay tự kết luận chuyên môn thay bác sĩ.
- Bản dự thảo tiếp theo thêm **sáu** thay thế thân bài cho bốn chủ đề trên (hai đoạn ở bài 569, một ở 572, hai ở 654, một ở 656); manifest và phiếu duyệt hiện có tổng 26 đoạn. Script WP-CLI vẫn chỉ kiểm tra theo mặc định, từ chối ghi khi thiếu thông tin bác sĩ duyệt, backup mới và cờ duyệt. Đây là đề xuất cần bác sĩ nhãn khoa rà soát nguyên văn, chưa triển khai.
- Staged đúng script/manifest bản 26 đoạn tại `/home/jwhxtzru/backups/medical-intro-stage-20261005/`; SHA-256 local/remote khớp, PHP lint đạt. Chạy WP-CLI dry-run trên 12/12 bài: 26/26 đoạn khớp nguyên văn, hash đầu ra dự kiến đúng, `physician-review=PENDING` ở cả 12, kết quả `Dry run only. No WordPress changes.` Không có database backup mới vì chưa mở bước ghi production.

## 2026-10-03 — hoàn thiện tín hiệu tìm kiếm cho Bắc Giang

- Tiền kiểm: trang `/khu-vuc/kham-mat-bac-giang/` đã có tiêu đề, H1, mô tả, canonical và liên kết theo ngữ cảnh từ trang chủ, thư viện khu vực, Giới thiệu và Liên hệ; 144/200 bài viết công khai thiếu thẻ HTML `meta description`. Trang Giới thiệu và Liên hệ nhận Article/BreadcrumbList trùng từ OBS SEO Suite; Liên hệ còn có hai mô tả Open Graph. Sitemap của ba trang khu vực vẫn ghi `lastmod` 05/08 dù nội dung và schema trong child theme đã thay đổi 03/10.
- Backup database và đúng ba tệp child theme trước triển khai tại `/home/jwhxtzru/backups/bac-giang-seo-20261003-150343/`; cùng thư mục lưu ba giá trị `post_modified` cũ. Script `scripts/deploy-bac-giang-seo-20261003.sh` kiểm tra SHA của tệp cũ và mới, chạy trong khóa triển khai, rồi chỉ cập nhật `lastmod` của ba trang khu vực ID 54–56 đến thời điểm thay đổi nội dung thực tế.
- Triển khai ba tệp từ commit `000ca0e`: `inc/content-plan-seo.php` thêm mô tả riêng cho bài viết thiếu meta (ưu tiên tóm tắt biên tập, còn thiếu thì dùng tiêu đề với câu trung tính; không tự lấy nhận định y khoa chưa duyệt từ thân bài); `inc/schema-y-te.php` loại JSON-LD OBS trùng trên đúng trang Giới thiệu/Liên hệ; `inc/noi-dung-lien-he-seo.php` xuất một bộ Open Graph/Twitter nhất quán cho Liên hệ. Không sửa plugin. Đã xóa WordPress object cache và LiteSpeed cache.
- Kiểm tra vòng đầu: PHP lint ba tệp sửa cùng `footer.php`/`page-lien-he.php` đạt, SHA tệp trên hosting khớp bản staged, backup SQL tồn tại; smoke sáu URL chính và SSH đạt. HTML công khai 200/200 bài có đúng một title, H1, meta description, OG description và canonical khớp URL; ba trang khu vực có `lastmod` 03/10; trang Bắc Giang có một Hospital/FAQPage; Liên hệ và Giới thiệu không còn OG hoặc breadcrumb trùng.
- QA nội dung meta phát hiện 88 tóm tắt cũ quá ngắn, 47 mô tả dự phòng bị cắt bằng dấu ba chấm và tám mô tả của loạt bài môi trường làm việc bị cắt giữa từ. Triển khai tiếp commit `42b0426`: mô tả dự phòng thành câu hoàn chỉnh theo tiêu đề, nối câu trung tính với tóm tắt ngắn; khôi phục mô tả của đúng tám ID 1474, 1475, 1476, 1478, 1482, 1485, 1486, 1488 từ excerpt đầy đủ sau khi tiền kiểm nguyên văn cũ. Backup file, database và tám giá trị trước–sau tại `/home/jwhxtzru/backups/seo-description-polish-20261003-151452/`; xóa hai lớp cache. QA lại 200/200 bài: không còn mô tả dưới 75 ký tự, dấu ba chấm ở mẫu dự phòng, câu cắt giữa từ hoặc mô tả trùng nguyên văn; PHP lint và smoke sáu URL đều đạt.
- Kiểm tra luồng liên kết công khai: 4/4 trang đầu vào (trang chủ, Khu vực, Giới thiệu, Liên hệ) có liên kết theo ngữ cảnh trong nội dung đến trang Bắc Giang. Trang này dẫn đến đúng 57 trang xã/phường thuộc địa bàn Bắc Giang cũ; 57/57 URL trả 200, tự liên kết ngược về trang Bắc Giang trong thân bài, không `noindex`. Trang trụ cột tự canonical và có thể lập chỉ mục. Không cần thêm liên kết nhồi từ khóa.
- Rollback chọn lọc: khôi phục ba tệp tương ứng từ backup vòng đầu; chỉ hoàn nguyên `post_modified` của ID 54–56 theo `area-page-lastmod-before.json` sau khi xác nhận chưa có biên tập mới hơn. Với vòng polish, khôi phục riêng `inc/content-plan-seo.php` từ backup vòng hai và tám meta theo `workplace-meta-before-after.json` nếu chưa có biên tập mới; xóa hai lớp cache và chạy lại smoke test. Không nhập đè toàn bộ database đang có dữ liệu mới.
- Việc ngoài quyền truy cập hiện tại: tài khoản Google Business Profile đang mở không quản lý hồ sơ bệnh viện (tìm đúng tên không có kết quả), nên chưa thể chuẩn hóa hồ sơ Google Maps; không tự tạo địa điểm thứ hai. Không thể bảo đảm vị trí xếp hạng hoặc việc ChatGPT/Gemini/Google AI trích dẫn ngay sau khi sửa website.

## 2026-10-03 — bỏ FAQ trùng và sửa câu trả lời y khoa dễ gây hiểu nhầm

- Trước sửa, 46 bài có cả FAQ shortcode của child theme và FAQ Discovery của OBS, dẫn tới hai khối câu hỏi/FAQPage trên mỗi bài. Sao lưu file liên quan và database tại `/home/jwhxtzru/backups/faq-dedup-20261003-0912/`; sao lưu nguyên văn 21 bài cùng metadata tại `/home/jwhxtzru/backups/faq-safety-20261003-144241/` trước khi sửa nội dung.
- Triển khai commit `026356e` để chỉ ngừng đầu ra FAQ Discovery trên bài đã có FAQ shortcode hợp lệ; giữ dữ liệu OBS để có thể khôi phục. Sửa có tiền kiểm đúng 21 bài: 32 đáp án FAQ shortcode, 20 đoạn thân bài, 16 đáp án FAQ metadata. Chủ đề gồm mất thị lực thoáng qua, hóa chất/dị vật, đỏ mắt ở người đeo kính áp tròng hoặc trẻ sơ sinh, chớp sáng/màn che, bệnh võng mạc và tăng huyết áp. Không đổi URL/tiêu đề, không tự ghi tên bác sĩ hoặc ngày duyệt.
- HTML công khai 46/46 bài có đúng một khối FAQ và một FAQPage; 273/273 câu hỏi hiển thị khớp schema. Bài ID 590 chỉ dùng FAQ OBS vẫn giữ một bộ. PHP lint, hash production, xóa cache và smoke test đều đạt. Phiếu đối chiếu trước–sau và các nguồn y khoa nằm ở `docs/faq-medical-review-20261003/`; rollback chọn lọc và dry run 21/21 bài nằm trong thư mục backup trên hosting.
- **Chưa có bác sĩ ký duyệt nội dung mới.** Rà soát tiếp phần mở đầu/“Trả lời ngắn” của 21 bài phát hiện 12 bài còn diễn đạt quá chắc hoặc chưa nhất quán với FAQ đã sửa; không dùng các đoạn đó để tự tạo meta description. Cần biên tập và bác sĩ duyệt trước khi gắn tên/ngày duyệt. Bài 662 còn cần phối hợp bác sĩ sản/nội tiết.
- Đã chuẩn bị riêng manifest trước–sau và script WP-CLI có chốt kiểm tra cho 12 bài/20 đoạn mở đầu, “Trả lời ngắn” trong `docs/faq-medical-review-20261003/` và `scripts/`. PHP lint và dry-run trên hosting đạt; **chưa áp dụng lên website**. Script chỉ cho phép ghi sau khi có tên/ngày bác sĩ duyệt và bản sao lưu mới. Bác sĩ cần đọc toàn bài để phát hiện các câu còn mâu thuẫn ngoài các đoạn này.

## 2026-10-03 — đồng bộ tín hiệu Bắc Giang và sửa bài cũ

- Kiểm tra trước sửa: nhóm `/khu-vuc/` còn nhận Article/OG và BreadcrumbList cũ từ OBS SEO Suite bên cạnh schema của child theme; trang Giới thiệu và Liên hệ chỉ dẫn đến trang Bắc Giang từ menu. Bài ID 590 `/kien-thuc/bac-giang-gio-thuoc-tinh-nao/` còn mô tả Bắc Giang là tỉnh độc lập, có hướng dẫn BHYT dựa trên mã tỉnh cũ và hai khối FAQ; hồ sơ thương hiệu OBS còn ghi địa chỉ “Tỉnh Bắc Ninh”.
- Sao lưu database, bốn file child theme, nguyên văn bài 590, excerpt, sáu FAQ và option hồ sơ thương hiệu trước thay đổi tại `/home/jwhxtzru/backups/bg-followup-20261003-0825/`. Thư mục backup giới hạn quyền truy cập; không đưa database/option vào Git.
- Triển khai bốn file từ commit `27c4896`: `inc/khu-vuc.php`, `content/khu-vuc/bac-giang.html`, `page-gioi-thieu.php`, `template-parts/noi-dung-lien-he-seo.php`. Child theme ngừng hook OG/Article/Breadcrumb của OBS chỉ trên nhóm trang khu vực, giữ WebPage/Breadcrumb/FAQ đúng ngữ cảnh và tự xuất OG thống nhất; địa chỉ trang Bắc Giang lấy từ dữ liệu địa chỉ trung tâm. Thêm liên kết trong nội dung Giới thiệu và Liên hệ tới trang khám mắt Bắc Giang. Không sửa mã plugin.
- Cập nhật có điều kiện đúng bài 590: đối chiếu Nghị quyết 202/2025/QH15 và 39/2026/QH16, dùng địa danh thành phố Bắc Ninh từ 20/09/2026; bỏ khẳng định BHYT theo mã tỉnh/tuyến cố định. Giữ URL/tiêu đề và 16 đích liên kết nội bộ; sửa excerpt, bốn đáp án FAQ và các trường địa chỉ liên quan trong một option hồ sơ thương hiệu. Bỏ FAQ shortcode trùng để chỉ còn một bộ sáu FAQ của OBS. Gắn cờ chờ xác minh người biên soạn và bác sĩ duyệt; trang không tự nhận bác sĩ làm tác giả hay công bố ngày duyệt chưa xác minh. Nội dung chuyên môn và hướng dẫn BHYT vẫn cần bệnh viện duyệt theo hồ sơ thực tế.
- Kiểm tra production: PHP lint ba file PHP đạt, SHA-256 bốn file khớp bản staged; xóa object cache và LiteSpeed cache. Bảy URL liên quan trả HTTP 200; ba trang khu vực mỗi trang có một OG title, một BreadcrumbList, không có Article sai; trang Bắc Giang có một meta description và địa chỉ mới, không còn placeholder. Bài 590 có một FAQPage, một khối FAQ hiển thị, không có Article gán bác sĩ hoặc các câu địa giới cũ; 16/16 liên kết nội bộ trả 200. `scripts/verify-live.ps1 -CheckSsh` đạt.
- Rollback chọn lọc: khôi phục đúng bốn file từ thư mục backup vào cùng vị trí child theme; khôi phục nguyên văn bài 590 và excerpt từ `post-590-content.html`/`post-590-fields.json`, FAQ từ `post-590-faq.json`, option hồ sơ từ `obs-profile.json`, rồi bỏ meta chờ duyệt được thêm trong đợt này. Xóa hai lớp cache và kiểm tra lại các URL. Không nhập đè toàn bộ `database.sql` vì có thể xóa dữ liệu mới phát sinh.
- Rà soát 200 bài/144 trang công khai phát hiện 17 FAQ metadata còn ghi đúng chuỗi địa chỉ “Phường Bắc Giang, Tỉnh Bắc Ninh”. Sao lưu database và nguyên trạng 17 mảng FAQ tại `/home/jwhxtzru/backups/bg-faq-meta-20261003-0849/`, rồi cập nhật có tiền kiểm theo đúng 17 ID và đúng một trường trả lời ở mỗi bài; không chạy SQL search-replace hoặc sửa phần nội dung y khoa. Sau xóa cache, 17/17 URL trả 200, không còn địa chỉ cũ trong HTML, FAQ/schema dùng địa chỉ thành phố Bắc Ninh. Smoke test sáu URL chính tiếp tục đạt. Rollback bằng đúng mảng FAQ trong `faq-17-before-20261003-015151.json` qua WordPress API, sau khi kiểm tra chưa có biên tập mới; không nhập đè database toàn site.
- Việc cần bác sĩ/biên tập xem tiếp: 17 bài trên vẫn có hai khối FAQ hiển thị và hai FAQPage do shortcode của child theme cùng OBS FAQ Discovery; kiểm tra dữ liệu cho thấy 46 bài công khai có cả hai nguồn FAQ. Chưa tự bỏ một bộ vì các câu hỏi/đáp án khác nhau và bài ID 567 có hướng dẫn về thời gian theo dõi đau hốc mắt cần bác sĩ duyệt. Option theme mods còn một địa chỉ cũ trong đoạn HTML tùy chỉnh nhưng đoạn này không xuất hiện trên trang chủ hoặc Liên hệ công khai; chưa sửa cấu hình không hoạt động.

## 2026-10-03 — làm rõ bệnh viện ở địa bàn Bắc Giang

- Kiểm tra trước sửa: `/khu-vuc/kham-mat-bac-giang/` đã được Google lập chỉ mục, nhưng tiêu đề, mô tả và H1 chỉ giới thiệu danh mục khám mắt; trang chưa trả lời ngay truy vấn tìm bệnh viện. Trang chủ có đoạn nói về Bắc Giang nhưng thiếu liên kết theo ngữ cảnh đến trang này và còn gọi đơn vị hiện hành là “tỉnh Bắc Ninh” trong đoạn giải thích.
- Triển khai commit `9e911cd` chỉ trong child theme: đổi tiêu đề, mô tả và H1 của trang Bắc Giang; đặt câu trả lời ngắn về tên chính thức, **một** địa chỉ đã công bố và liên kết tới bản đồ/liên hệ trước danh mục 57 xã, phường; nối đoạn trên trang chủ tới trang Bắc Giang và sửa tên địa giới thành “thành phố Bắc Ninh”. `WebPage.about` của trang Bắc Giang tham chiếu thực thể Hospital sẵn có, không tạo thực thể/chi nhánh mới. Không đổi nội dung hướng dẫn y khoa hoặc URL.
- Sao lưu ba tệp và database trước triển khai tại `/home/jwhxtzru/backups/bac-giang-entity-20261003-072753/`. PHP lint hai tệp PHP thay đổi đạt; SHA-256 ba tệp production khớp bản đã kiểm thử; xóa object cache và LiteSpeed cache. HTML công khai trang chủ, trang Bắc Giang và trang Bắc Ninh đều 200; trang Bắc Giang có một H1, một meta description, một canonical và một Hospital JSON-LD. Kiểm tra trực quan trang Bắc Giang ở khung điện thoại: câu trả lời đầu trang hiển thị đủ, không tràn ngang. `scripts/verify-live.ps1 -CheckSsh` đạt sáu URL chính.
- Google Search Console xác nhận URL Bắc Giang đã nằm trên Google; đã gửi yêu cầu thu thập lại và được thêm vào hàng đợi ưu tiên. Chưa thể xác nhận trang sẽ được ChatGPT, Gemini hoặc Google AI trích dẫn. Còn cần kiểm tra/hoàn thiện Google Business Profile, tọa độ và giấy phép hoạt động khi bệnh viện cung cấp bằng chứng; không tự khai các trường chưa xác minh.
- Rollback có chọn lọc: chép lại đúng ba tệp từ thư mục backup vào cùng đường dẫn child theme rồi chạy `wp cache flush` và `wp litespeed-purge all`; kiểm tra lại URL. Database không bị sửa trong đợt này, vì vậy không nhập đè SQL backup lên dữ liệu mới.
- Triển khai tiếp commit `bdddf5e` sau khi đối chiếu hồ sơ Google Maps công khai đúng tên, số điện thoại và website bệnh viện: thêm `hasMap` vào thực thể Hospital hiện có và cho liên kết “Mở Google Maps” ở chân trang trỏ thẳng đến hồ sơ này thay vì trang kết quả tìm kiếm. Không tạo địa điểm hay cơ sở mới. Sao lưu database cùng `footer.php` và `inc/schema-y-te.php` tại `/home/jwhxtzru/backups/bac-giang-maps-20261003-074831/`; PHP lint, đối chiếu SHA-256 tệp production, xóa hai lớp cache và smoke sáu URL đều đạt. HTML công khai có một Hospital JSON-LD, URL `hasMap` khớp liên kết ở chân trang. Rollback riêng đợt này bằng cách khôi phục đúng hai tệp từ backup và xóa cache; không nhập đè database.
- Search Console trong khoảng 02/08–29/09/2026 ghi nhận nhóm truy vấn chứa “bắc giang” có 41 lượt hiển thị và 0 lượt nhấp; truy vấn chính xác “bệnh viện mắt bắc giang” không hiện lượt hiển thị trong báo cáo lọc. Báo cáo thử nghiệm tính năng AI của Google ghi nhận một lượt hiển thị cho trang Bắc Giang, không có dữ liệu truy vấn tương ứng. Đây không phải số liệu của ChatGPT hoặc Gemini. Tài khoản Google đang mở xem được Search Console nhưng không thấy hồ sơ bệnh viện trong danh sách Business Profile có quyền quản lý; việc chuẩn hóa địa chỉ và các tín hiệu địa phương trên hồ sơ Maps cần chủ sở hữu hồ sơ thực hiện.

## 2026-10-02 — sửa luồng liên kết từ trang chủ

- Kiểm tra 200 bài, 144 trang và 12 danh mục trong sitemap. Sau backup database tại `/home/jwhxtzru/backups/link-trust-20261002-084027/`, làm mới quy tắc URL và LiteSpeed cache để sửa danh mục phẫu thuật khúc xạ trả 404.
- Thêm 59 liên kết đọc tiếp theo chủ đề vào 50 bài nguồn, nối 59 bài trước đó không có liên kết từ thân bài khác. Nội dung gốc của 50 bài được lưu tại `original-linked-post-content.json`; mapping cụ thể ở `docs/link-trust-20261002/plan.json`.
- Thêm liên kết từ trang Giới thiệu đến trang Về chúng tôi. Sáu trang giữ chỗ hoặc trang xác nhận được đánh dấu `noindex, follow` trong child theme và loại khỏi sitemap qua cấu hình OBS, giữ nguyên nội dung và quyền truy cập. File theme cũ và cấu hình sitemap cũ nằm trong thư mục backup trên.
- Xác nhận HTML công khai: 50/50 bài nguồn trả HTTP 200, 59/59 liên kết xuất hiện trong thân bài; danh mục cũ 200, liên kết Giới thiệu → Về chúng tôi có mặt; sáu trang đều 200 + `noindex` và không còn trong sitemap. PHP lint các file mới đạt, `scripts/verify-live.ps1 -CheckSsh` đạt.
- Kiểm tra và rollback chi tiết tại `docs/link-trust-20261002/README.md`.

## 2026-10-02 — hoàn thành 200 bài viết công khai

- Sau khi gộp ba cặp bài gần trùng, tạo 26 bài riêng trong `docs/content-200-26/drafts/` (1.509–1.910 từ/bài), danh mục và danh sách tại `metadata.json`. Mỗi bài có FAQ, nguồn y khoa gốc, tối thiểu hai liên kết tới bài đang xuất bản và dùng ảnh đại diện phù hợp từ thư viện hiện có. Không gán tác giả hay bác sĩ duyệt khi chưa có tên/ngày được xác nhận; meta nội bộ vẫn ghi chờ duyệt chuyên môn.
- Backup database trước thay đổi tại `/home/jwhxtzru/backups/content-200-26-20261002-0425/database.sql`; cùng thư mục có package, script import/publish và backup excerpt `original-excerpts.json`. Import 26 bản nháp ID 1550–1575, kiểm tra trên Chrome, rồi xuất bản đúng 26 bài. Không sửa WordPress core, plugin hay mã child theme.
- Sửa dấu Markdown lọt vào mô tả SEO/excerpt của loạt bài; đồng bộ bộ tạo package để không tái phát. Xóa object cache và LiteSpeed cache. WP-CLI xác nhận **200 bài đã xuất bản**; HTTP 200, H1 đơn nhất, canonical, meta description và liên kết nội bộ đạt 26/26. `scripts/verify-live.ps1 -CheckSsh` đạt với các URL chính.
- Rollback chọn lọc: chuyển đúng ID 1550–1575 về nháp, khôi phục excerpt/meta từ `original-excerpts.json` nếu cần, xóa cache và kiểm tra lại số bài. Backup SQL chỉ dùng để phục hồi toàn diện sau khi đánh giá mọi thay đổi phát sinh từ thời điểm backup; không nhập đè trực tiếp vào website đang có dữ liệu mới.
- Còn cần bác sĩ/bệnh viện xác nhận người biên soạn, người duyệt và ngày duyệt chuyên môn. Ảnh đại diện hiện được dùng lại từ bài liên quan, chưa tạo ảnh độc quyền cho từng bài.

## 2026-10-02 — gộp ba cặp bài trùng, chuyển hướng URL cũ

- Người quản lý chọn gộp ba cặp để xây kho 200 bài nội dung riêng. Backup database và `inc/quan-ly-bai-viet.php` tại `/home/jwhxtzru/backups/merge-three-posts-20261002-102441/`; script nhập còn lưu nguyên văn ba bài trùng và năm bài/trang có liên kết cần sửa trong `original-content.json` cùng thư mục.
- Thêm đúng ba redirect 301 trong child theme. Cập nhật liên kết nội bộ từ URL `-2` sang URL gốc trong năm bài/trang, chuyển ID 107, 108, 109 sang nháp; giữ bản gốc ID 116, 119, 120 công khai. Không xóa nội dung hoặc media. Số bài công khai từ 177 còn 174. Kế hoạch tăng lên 26 bài mới.
- PHP lint cả hai file PHP đạt, dry run xác nhận đúng ba cặp và năm nội dung liên kết. Xóa WordPress/LiteSpeed cache; cả ba URL `-2` trả 301 về đúng URL gốc; smoke sáu URL chính và SSH đạt. Rollback chọn lọc: khôi phục file child theme từ backup, trả ba bài về `publish`, phục hồi năm `post_content` theo `original-content.json`, xóa cache rồi kiểm tra URL. Không dùng `wp db import` nếu website đã có thay đổi mới hơn.

## 2026-10-02 — audit lại kế hoạch 200 bài (chỉ đọc)

- WP-CLI xác nhận 177 bài công khai, cả 177 có ảnh đại diện; 15 bài dưới 1.400 từ theo bộ đếm nội dung, một bài không có liên kết nội bộ trong `post_content`.
- Kiểm tra nguyên văn ba cặp nghi trùng: ID 107/116 và 108/119 giống khoảng 99,6%; ID 109/120 giống khoảng 91%; cả sáu URL HTTP 200 với canonical riêng. Chưa gộp, xóa, chuyển hướng hoặc sửa production.
- Báo cáo và hai phương án mốc 200 tại `docs/content-200-audit-20261002.md`; 23 bài mới vẫn chỉ ở mức kế hoạch.

## 2026-10-02 — ảnh riêng cho 15 bài mắt và môi trường làm việc

- Tạo 15 ảnh minh họa riêng theo đúng tình huống của 15 bài ID 1474–1488; ảnh không mô tả nhân viên hoặc cơ sở thật của bệnh viện. Đổi tên theo slug bài viết, xuất WebP 1200×675 ở 42–92 KB/ảnh (tổng 1.053.434 byte). Giữ ảnh gốc và bản WebP trong thư mục `docs/workplace-15/images/` tại workspace, thư mục này được loại khỏi Git vì media production nằm trong WordPress.
- Trước khi sửa, xác nhận cả 15 bài đều chưa có featured image. Backup database tại `/home/jwhxtzru/backups/workplace-images-20261002-094009/database.sql`; nhập 15 ảnh vào Media Library (ID 1530–1544), gắn lần lượt vào bài và thêm alt/caption ghi rõ tính minh họa. Không sửa nội dung y khoa, URL, tiêu đề hoặc metadata SEO cũ.
- PHP lint script nhập đạt; xóa WordPress object cache và LiteSpeed cache. Kiểm tra 15/15 URL công khai HTTP 200 có đúng ảnh trong nội dung và `og:image`; bài mẫu hiển thị ảnh hoàn chỉnh ở 319 px và 1280 px, không tràn ngang. `scripts/verify-live.ps1 -CheckSsh` đạt. Cách khôi phục: gỡ `_thumbnail_id` của đúng 15 bài (trước đó đều trống) sau khi xác nhận; ảnh đã tải lên có thể giữ nguyên để tránh xóa media nhầm. Backup DB chỉ dùng khi cần khôi phục rộng hơn.

## 2026-10-01 — liên kết hai chiều cho 15 bài mắt và môi trường làm việc

- Đã kiểm tra nội dung 15 bài mới và 11 bài cũ liên quan: bài mới mới chỉ có một liên kết tới bài cũ; chưa có liên kết nội dung từ bài cũ đến loạt mới. Chuẩn bị `scripts/link-workplace-series.php` để thêm hai liên kết cùng ngữ cảnh giữa các bài mới và một khối liên kết từ mỗi bài cũ đến các bài mới tương ứng. Script kiểm tra chính xác 26 bài, lưu nguyên bản nội dung từng bài trước khi ghi; không đổi phần giải thích y khoa cũ.
- Đã backup database và nguyên văn 26 bài tại `/home/jwhxtzru/backups/workplace-links-20261001-160029/`, rồi cập nhật 15 bài mới và 11 bài cũ. Cache WordPress/LiteSpeed đã xóa. Kiểm tra HTML công khai: 15/15 bài mới có đúng hai liên kết cùng loạt và một liên kết đến bài cũ; 11/11 bài cũ dẫn ngược tới đủ 15 bài mới. Bài mẫu trên Chrome 375 px không tràn ngang, hiển thị khối liên kết trong mục lục. `scripts/verify-live.ps1 -CheckSsh`, PHP lint và 15 URL công khai đều đạt. Rollback chọn lọc có script và đã kiểm tra dry run 26/26 bài, chưa thực thi rollback.
- Kế hoạch tiếp theo để từ 177 lên 200 bài công khai: `docs/content-plan-to-200.md` với 23 đề tài riêng, mốc viết/duyệt/xuất bản và cảnh báo ba cặp trùng tiêu đề cần rà. Chưa tạo hoặc đăng 23 bài này.


## 2026-10-01 — chuẩn bị 15 bài “Mắt và môi trường làm việc”

- Soạn 15 bài độc lập dài 1.461–1.587 từ, đúng nhóm công việc và nguy cơ mắt riêng; giữ nguồn NEI/NIOSH/OSHA/CDC/EPA, FAQ và liên kết nội bộ. Không tự ghi tên tác giả hoặc bác sĩ duyệt.
- Thêm bộ dựng HTML và importer WP-CLI có kiểm tra trước, ban đầu chỉ tạo bản nháp. Backup database `/home/jwhxtzru/backups/workplace-15-20261001-154151/database.sql`; nhập 15 bản nháp ID 1474–1488. Đã xem bản nháp trên Chrome 375 px và desktop: một H1, nguồn tham khảo và mục lục có neo, không tràn ngang, không lỗi JS ở ba bài mẫu. Chi tiết kiểm tra, triển khai và rollback tại `docs/workplace-15/README.md`.
- Đã xuất bản đúng 15 bài ID 1474–1488 sau preflight; chuyên mục `mat-va-moi-truong-lam-viec` tăng từ 1 lên 16 bài. Xóa WordPress object cache và LiteSpeed cache. Kiểm tra HTML công khai: 15/15 URL HTTP 200, mỗi bài một H1, một meta description, một canonical đúng URL, có nguồn tham khảo, và đều được liên kết từ `/kien-thuc/`. Smoke sáu URL chính + SSH đạt; PHP lint hai script nhập/xuất bản và `footer.php`, `page-lien-he.php` đạt. Bác sĩ/người biên soạn và ngày duyệt vẫn chờ bệnh viện cung cấp; không hiển thị ghi công chưa xác minh.

## 2026-10-01 — thu gọn mục bài viết và dải dẫn nhanh theo phản hồi giao diện

- Trang `/kien-thuc/`: danh mục 162 bài giờ chia thành các chủ đề đóng mặc định; mở một chủ đề thấy tám bài đầu và có lựa chọn xem thêm. Giữ đủ liên kết HTML, không đổi URL hay nội dung bài.
- Trang chủ: thay bốn thẻ dẫn nhanh cao bằng một dải liên kết thấp, cùng màu thương hiệu, bố cục hai cột trên điện thoại. Giữ bốn đích liên kết và khả năng dùng bàn phím.
- Chỉ sửa `inc/tra-cuu-bai-viet.php`, `style.css`, `assets/home-critical.css`. Backup trước triển khai: `/home/jwhxtzru/backups/compact-navigation-20261001080228/` (ba tệp và database). Rollback giao diện bằng cách chép lại đúng ba tệp từ backup rồi xóa object cache/LiteSpeed; không cần phục hồi database vì đợt này không sửa dữ liệu.
- PHP lint đạt, smoke sáu URL chính và SSH đạt. Trên Chrome, nhóm 54 bài đóng mặc định, mở ra thấy tám bài và nút xem thêm 46 bài. Ở 375 px không tràn ngang; dải dẫn nhanh cao khoảng 142 px (hai hàng), giữ đủ bốn liên kết, không thấy lỗi JS.

## 2026-10-01 — rút gọn tra cứu bài viết trên trang chi tiết

- Chuyển danh mục đầy đủ các bài kiến thức sang `/kien-thuc/#toan-bo-bai-viet`; mỗi bài chi tiết chỉ hiện tối đa sáu bài mới cùng chuyên mục và một liên kết đến danh mục đầy đủ. Giữ liên kết HTML thông thường và thao tác mở rộng bằng `details` trên trang thư viện.
- Thay đổi chỉ ở `inc/tra-cuu-bai-viet.php`, `page-kien-thuc.php` và `style.css`; không đổi nội dung y khoa, URL bài, tiêu đề hoặc metadata SEO. Trạng thái triển khai, backup, QA và rollback được cập nhật sau khi kiểm tra production.
- Sao lưu ba tệp và database tại `/home/jwhxtzru/backups/article-directory-20261001071715/` trước triển khai. PHP lint đạt; sau purge, `/kien-thuc/` trả 200 và có 162 liên kết trong thư mục, bài `/kien-thuc/chon-noi-kham-mat-o-bac-ninh/` trả 200 và chỉ còn sáu bài liên quan. HTML thô bài mẫu giảm từ 116.070 xuống 85.781 byte. Trình duyệt 375 px không tràn ngang và không ghi lỗi JS. Rollback: khôi phục ba tệp từ thư mục backup, xóa cache; chỉ khôi phục DB nếu cần hoàn tác thay đổi ảnh đại diện.
- Chuẩn bị `scripts/set-content-plan-featured.php` để dùng 12 ảnh hiện có, đúng chủ đề cho 12 bài ID 1441–1452 còn thiếu ảnh. Script xem trước đã xác nhận mọi ID bài và media; chỉ gắn khi chưa có ảnh, không đổi alt của media dùng chung.
- Đã chạy script ở chế độ áp dụng sau backup, 12/12 bài có ảnh đại diện; 12/12 URL bài trả 200 và `og:image` không còn là favicon. Không tải ảnh mới hoặc sửa media cũ. Smoke 6 URL chính + SSH đạt. Bản bổ sung bài 08 để bác sĩ/biên tập duyệt nằm tại `docs/content-plan-20/review-08-addendum.md`, chưa đăng.

## 2026-10-01 — đã xuất bản bộ 20 nội dung sau lệnh “áp dụng hết”

- Tạo bản HTML công khai riêng từ 20 bản thảo; loại bỏ ghi chú nội bộ và thông tin chưa xác minh, giữ bản nháp gốc để đối chiếu. Nội dung y khoa chung được đối chiếu với NEI, FDA, AAPOS và luật BHYT; không tự nhận bác sĩ đã duyệt.
- Bổ sung đường hiển thị phần giải thích vào template đặt lịch và bảng giá mà vẫn giữ biểu mẫu và bảng giá; chuẩn bị khối tra cứu bài đã xuất bản trong child theme. Script WP-CLI kiểm tra chính xác 8 URL cũ và 12 bản nháp trước khi ghi, sau đó bổ sung vào nội dung cũ thay vì xóa nội dung đang có.
- Đã backup database và theme tại `/home/jwhxtzru/backups/content-plan-publish-20261001-090959/`; triển khai child theme, áp dụng 8 bổ sung trên URL cũ và xuất bản 12 bài ID 1441–1452. Thêm tiêu đề/mô tả SEO đã chuẩn bị, giữ thẻ mô tả có sẵn trên Liên hệ/Đội ngũ để tránh trùng. Không gán bác sĩ/tác giả khi người quản lý chưa cung cấp tên và ngày duyệt; 3 bài cũ mới bổ sung cũng chuyển sang trạng thái chờ xác minh ghi công.
- PHP lint trên server đạt; cache WordPress/LiteSpeed đã xóa; 20/20 URL HTTP 200, H1/mục lục/mô tả SEO hợp lệ, không lộ ghi chú nội bộ; smoke sáu URL + SSH đạt. Chrome đã thử bài mới và form ở 375 px, bảng giá ở 375 px, khối tra cứu ở desktop, không thấy tràn ngang hoặc lỗi JS tại các trang mẫu. Chi tiết và rollback trong `docs/content-plan-20/README.md`.

## 2026-10-01 — kiểm tra 12 draft trên WordPress và sửa ghi công sai/bố cục mobile

- Đăng nhập xem đủ 12 bản nháp trên theme thật ở 1280×800 và 375×812: một H1/bài, 8 neo mục lục hợp lệ, không tràn ngang; thử bấm mục lục bài 20 thành công. Ảnh QA trong `docs/content-plan-20/`.
- Trên preview, plugin tự gán bác sĩ/người quản trị làm tác giả dù chưa xác minh. Sửa riêng trong child theme `inc/tac-gia-bac-si.php`: bài có meta chờ duyệt không hiển thị các khối ghi công/plugin AI, không xuất Article schema sai, không chèn breadcrumb và thời gian đọc trùng. Bài công khai mẫu vẫn giữ các tính năng như trước.
- `assets/lien-he-noi.css`: ẩn cụm biểu tượng mạng xã hội nổi dưới 768 px vì che chữ; link footer và nút gọi/đặt lịch vẫn còn. Backup file+database tại `/home/jwhxtzru/backups/draft-attribution-20261001-083352/` và `/home/jwhxtzru/backups/mobile-contact-dock-20261001-084604/`. PHP lint, purge cache, smoke sáu URL đạt; post công khai vẫn 150, draft vẫn 12. Rollback chi tiết: `docs/content-plan-20/README.md`.

## 2026-10-01 — nhập 12 bài kiến thức mới dưới dạng bản nháp, CHƯA XUẤT BẢN

- Sau phê duyệt bản xem thử, sao lưu database và tệp theme liên quan tại `/home/jwhxtzru/backups/content-plan-drafts-20261001-081537/`; dùng `scripts/import-content-plan-drafts.php` nhập bản 07, 08, 09, 11, 12, 13, 15–20 thành post ID 1441–1452 ở trạng thái `draft`. Tạo chuyên mục con phẫu thuật khúc xạ ID 17. Chưa gán tác giả/bác sĩ khi chưa xác minh, không cập nhật 8 URL cũ hoặc deploy code khối tra cứu.
- WP-CLI xác nhận 12 draft, 150 post công khai giữ nguyên; URL dự kiến của bài 07 trả 404 cho người chưa đăng nhập. PHP lint importer + bốn tệp theme live đạt, smoke sáu URL + SSH đạt. Chưa kiểm preview WordPress trên trình duyệt có đăng nhập. Chi tiết và rollback có chọn lọc: `docs/content-plan-20/README.md`.

## 2026-10-01 — bộ 20 nội dung mắt và khối tra cứu, CHỈ BẢN NHÁP CỤC BỘ

- Kiểm kê 294 URL công khai qua WP-CLI, lập bản đồ ý định tìm kiếm cho 20 chủ đề, viết 20 bản thảo riêng (8 đề xuất cập nhật, 12 bài mới) cùng gói HTML và bản xem thử nội bộ tại `docs/content-plan-20/`.
- Chuẩn bị trong child theme khối thu gọn liên kết tới tất cả bài viết công khai khác trên từng bài, nhóm chuyên mục, cache và tự làm mới khi nội dung đổi. Mã **chưa triển khai** vì bộ bài còn cần tác giả/bác sĩ duyệt và kiểm thử WordPress thực tế.
- QA cấu trúc 20/20 đạt, 74 liên kết nội bộ trong bản đồ trỏ URL công khai, PHP lint ba tệp thay đổi đạt; trình duyệt kiểm tra bản nháp mẫu ở 1280/375 px. Chưa sửa WordPress DB, category, menu hay production theme; chưa cần rollback production. Báo cáo, giới hạn và bước sau duyệt: `docs/content-plan-20/README.md`.

## 2026-09-30 — fanpage xác nhận và sửa thêm giá trị schema y tế

- Người quản trị chọn `https://www.facebook.com/benhvienmathanoibacninh`. Triển khai commit `74bf209`: dùng URL này cho link giao diện và `Hospital.sameAs`; đổi `medicalSpecialty` sai `Ophthalmologic` sang enum `https://schema.org/Ophthalmology` cho Hospital/Physician.
- Schema Markup Validator còn phát hiện `medicalAudience` dạng chuỗi và `worksFor` trên Physician sai kiểu. Triển khai commit `f5e7cee`: dùng object `Patient`, bỏ quan hệ `worksFor` không hợp lệ; chỉ xuất `reviewedBy` khi có meta ngày duyệt thật (hiện 0 post có meta đó). Không đổi nội dung y khoa hiển thị.
- Đã sao lưu file/database riêng từng đợt tại `/home/jwhxtzru/backups/seo-entity-followup-20260930/` và `/home/jwhxtzru/backups/seo-validator-followup-20260930/`. PHP lint, SHA-256 file live, purge cache, smoke test đều đạt. Validator công khai trang chủ, bác sĩ, bài kiến thức và liên hệ sau sửa: mỗi trang 0 lỗi/0 cảnh báo. Báo cáo và rollback: `docs/seo-ai-fix/README.md`.

## 2026-09-30 — đã triển khai sửa schema thực thể và canonical

- Triển khai commit `206bcf8`: tám tệp child theme được upload đúng bản Git, SHA-256 local/live khớp; ba option OBS SEO Suite chỉ tắt emitter `AutoDealer`, GMB schema và LocalBusiness trùng (`enable_dealer=0`, `enable_schema=0`, `schema_enable=0`). Không sửa mã plugin/core.
- Trước triển khai đã sao lưu tám tệp và database tại `/home/jwhxtzru/backups/seo-entity-20260930-154327/`. Sau triển khai lint PHP 3/3, xóa object cache và LiteSpeed, smoke 6 URL + SSH đạt.
- Bản audit HTML sau purge gồm 11 URL: HTTP 200, canonical HTTPS đúng một thẻ, không `AutoDealer`, `/#localbusiness`, `AggregateRating`, không lỗi JSON-LD; mỗi URL giữ `Hospital/MedicalOrganization` `/#to-chuc`. Trang chủ có canonical mới, tên địa giới hiện hành đã đồng bộ trong schema/footer/nội dung khu vực liên quan. `robots.txt` và sitemap hoạt động.
- Trình duyệt desktop/mobile đã kiểm tra giao diện chính; Google Search Console live test trang chủ cho phép lập chỉ mục và đã xác nhận URL vào hàng đợi ưu tiên. Chi tiết bằng chứng, việc còn cần xác minh và rollback: `docs/seo-ai-fix/README.md`.

## 2026-09-30 — chuẩn bị sửa schema thực thể và canonical

- Kiểm tra tám URL công khai và nguồn schema: plugin OBS SEO Suite phát `AutoDealer` và hai `LocalBusiness` trùng định danh; child theme đã có `Hospital/MedicalOrganization` đúng `/#to-chuc`. Chưa xác minh hai URL Facebook và nguồn điểm đánh giá 5/6.
- Sửa child theme: canonical tự trỏ cho trang chủ; ngừng khẳng định Facebook `sameAs` khi chưa xác minh; đồng bộ tên địa giới hiện hành `Thành phố Bắc Ninh` trong schema, footer và nội dung/SEO các trang khu vực liên quan. Không đổi nội dung y khoa hoặc URL.
- Sao lưu tám tệp và database trước deploy tại `/home/jwhxtzru/backups/seo-entity-20260930-154327/`; mã production trước sửa khớp Git HEAD, ba tệp PHP mới đã lint đạt ở thư mục staging riêng. Chi tiết/bằng chứng và rollback: `docs/seo-ai-fix/README.md`.
- **Trạng thái khi commit này:** chưa triển khai. Sau khi upload sẽ tắt riêng ba emitter schema qua option WordPress, xóa cache và ghi kết quả kiểm tra sau sửa.

## 2026-09-30 — rà soát 294 bài/trang, CHƯA TRIỂN KHAI SỬA NỘI DUNG

- Xuất và đọc toàn văn 150 bài viết, 144 trang đã công bố (trong đó 99 trang xã/phường lấy HTML từ child theme). Lập báo cáo, chỉ mục đủ 294 URL và sáu tệp phát hiện tại `docs/content-audit/2026-09-29/`; 228 điểm cần xem lại, 49 URL ưu tiên sửa, 76 URL cần bác sĩ duyệt. Đối chiếu câu trích, ID, URL và nguồn chuyên môn gốc; hạ mức các cảnh báo chưa đủ bằng chứng sau QA độc lập.
- Nêu các nhóm cần duyệt trước: phân luồng mất thị lực/TIA/bong võng mạc, hóa chất/chấn thương, đồng tử trắng ở trẻ, mô tả bệnh và thuốc. Rà riêng địa danh theo Nghị quyết 39/2026/QH16 có hiệu lực 20/09/2026: bài ID 590 sai thông tin hành chính, schema địa chỉ dùng chung và SEO 99 trang còn ghi “Tỉnh Bắc Ninh”, xã Đại Sơn nằm sai nhánh lịch sử. Liệt kê câu lịch sử cần giữ, không thay chuỗi hàng loạt.
- Chỉ thêm tài liệu trong Git; không sửa WordPress DB, child theme hoặc website production. Đề xuất sửa y khoa cần bác sĩ bệnh viện duyệt, phần BHYT và địa chỉ cần đơn vị có thẩm quyền xác nhận. Chưa kiểm tra toàn bộ 294 bản kết xuất bằng trình duyệt.
- Kiểm tra sau rà soát: sáu JSON hợp lệ, đủ 294 ID/URL duy nhất và 228 vấn đề; smoke test 6 URL chính + SSH đạt; PHP lint read-only trên production cho `footer.php` và `page-lien-he.php` đạt. Máy local không có lệnh `php`, nên dùng PHP trên server để kiểm tra hai tệp không thay đổi.

## 2026-09-25 — sửa thẻ bác sĩ trang Chuyên khoa

- Sửa CSS riêng cho thẻ thông tin bác sĩ ở cuối `/chuyen-khoa/`: đặt ảnh cạnh tên, bỏ khoảng cách tiêu đề của bài viết, tăng tương phản nhãn/tên/ghi chú, thu gọn số liệu và ngày cập nhật. Không đổi nội dung y khoa hoặc HTML.
- Ở màn hình dưới 768 px, ẩn dải mạng xã hội cố định riêng trên trang này vì che chữ; liên kết mạng xã hội vẫn có ở chân trang, nút gọi và đặt lịch giữ nguyên.
- Bản xem thử dùng HTML production và CSS mới đã kiểm tra trực quan ở desktop và 375/320 px: thẻ không tràn ngang, màu chữ dễ đọc, chiều cao desktop giảm từ khoảng 740 px còn 345 px.
- Đã triển khai commit `e089ea0` lên production sau khi sao lưu CSS và database tại `/home/jwhxtzru/backups/chuyen-khoa-eeat-20260925-082005/`. SHA256 CSS staged/live khớp; PHP lint 3/3, purge object cache/LiteSpeed, smoke 6 URL + SSH đạt. Trình duyệt production xác nhận CSS version mới, thẻ gọn và dễ đọc ở desktop/375/320 px, không tràn ngang hay lỗi JavaScript. Khôi phục bằng cách chép lại `style.css` trong thư mục backup lên child theme rồi purge cache.

## 2026-09-24 — đã xuất bản 102 trang khu vực, 99 bài xã/phường riêng

- Theo yêu cầu xuất bản toàn bộ 102 trang và không lặp nội dung, thay mẫu xã/phường dùng chung bằng 99 tệp HTML riêng. Mỗi trang con có khoảng 603–750 từ, hai FAQ, địa danh theo Nghị quyết 1658 và chủ đề nhãn khoa riêng. Kiểm tra không có đoạn văn dài trùng nguyên văn; mức giao nhau cụm năm từ cao nhất 0,158.
- Ba trang trụ cột giữ nguyên URL, khoảng 3.500 từ mỗi trang. Sửa mức khẩn của cơn mất thị lực thoáng qua theo nguồn American Stroke Association; bỏ câu biên tập nội bộ khỏi nội dung công khai. Trên nhánh khu vực, ẩn thẻ chân trang ghi tên bác sĩ chưa xác nhận duyệt riêng các bài này. Ở điện thoại, ẩn hàng mạng xã hội nổi để không che bài; liên kết vẫn ở chân trang.
- Đã triển khai nội dung commit `6d1dc5d` và quy tắc bảo vệ tệp nguồn từ `e121a03` lên child theme; tạo rồi xuất bản đúng 99 trang con (57 địa bàn Bắc Giang cũ, 42 địa bàn Bắc Ninh cũ). Backup trước khi thay đổi: `/home/jwhxtzru/backups/khu-vuc-20260924-193032/` gồm toàn bộ child theme, database, gói release, danh sách ID và log xuất bản. Không gắn `reviewedBy` khi chưa có tên/ngày bác sĩ duyệt được xác minh.
- Kiểm tra: SHA256 gói release staged khớp local; PHP lint 7/7; 102/102 URL công khai HTTP 200, có nội dung riêng, FAQ + schema, một meta description, không `noindex`; trang hub liên kết đủ 99/99 trang con. Browser thật: desktop và mobile 390/320 px, không tràn ngang trên các trang mẫu, không có lỗi JavaScript; smoke test 6 URL chính + SSH đạt. Đã xóa object cache và LiteSpeed cache. Chặn truy cập HTTP trực tiếp tới tệp nguồn HTML/JSON/MD/PHP trong thư mục nội dung để tránh URL bản sao; WordPress vẫn đọc tệp nội bộ. Hướng dẫn khôi phục tại `docs/KHU-VUC-ROLLBACK.md`.

## 2026-09-24 — bản thảo khu vực khám mắt, CHƯA TRIỂN KHAI

- Viết ba trang trụ cột hiện có, mỗi trang khoảng 3.500 từ sau khi hiển thị mục lục/FAQ; giữ URL và các phần cũ. Tạo danh mục 99 xã/phường hiện hành theo Nghị quyết 1658/NQ-UBTVQH15 và khung nội dung cho 99 trang con, phân nhóm địa bàn Bắc Ninh cũ (42) và Bắc Giang cũ (57).
- Chuẩn bị tích hợp child theme: nguồn HTML/JSON, menu “Khu vực khám mắt”, mục lục, SEO title/meta description, WebPage/CollectionPage + FAQ schema, CSS chỉ tải trên trang đích và script WP-CLI chỉ tạo draft khi bật rõ biến môi trường. Chưa gắn tên bác sĩ hoặc ngày duyệt chưa được xác nhận.
- Bản xem thử cục bộ `http://127.0.0.1:8784/khu-vuc/` có đủ 102 đường dẫn, header `X-Robots-Tag: noindex`; không upload file vào production theme, không sửa database live. Đã lint PHP trong thư mục backup tách biệt trên server và kiểm tra danh mục 99, 38 liên kết bài viết, nội dung dài cùng bố cục. Chờ bác sĩ và người dùng duyệt trước khi triển khai; các trang con cần thêm chi tiết địa phương độc đáo trước khi index. Xem `theme/eyecare-child/content/khu-vuc/BIEN-TAP.md`.

## 2026-09-24 — CSS, font và nhãn truy cập trang chủ

- Chuyển Be Vietnam Pro sang WOFF2 cùng tên miền, giữ các mức đậm đang dùng và giấy phép OFL; bỏ stylesheet Google Fonts trên trang chủ và các trang khác.
- Tách CSS vùng hero/thẻ truy cập đầu trang sang `home-critical.css`; tải CSS các phần bên dưới và thanh liên hệ trang chủ không chặn hiển thị, vẫn có bản dự phòng khi JavaScript tắt. Không thay đổi nội dung hoặc bố cục mong muốn.
- Bỏ vai trò ARIA `listitem` không phù hợp trên thẻ `article` của khối bác sĩ và bài viết mới; vùng chứa được gắn nhãn nhóm.
- Đã triển khai commit `e9194e9`; backup database và 5 file cũ tại `/home/jwhxtzru/backups/home-css-20260924-090142/`. PHP lint 5/5, đối chiếu 22 file staged/live, 6 URL smoke và SSH đạt. Trình duyệt desktop/mobile 390 px không tràn ngang, CSS/font và thẻ nội dung hiển thị, không có lỗi JavaScript. Chưa đo lại PageSpeed Insights sau deploy.

## 2026-09-24 — ảnh responsive trang chủ

- Sửa `sizes` ảnh và preload slider theo cột hiển thị thực tế để trình duyệt chọn bản WebP 768/1024 px thay vì 1536 px trên màn hình desktop thông thường. Không thay nội dung hoặc thứ tự slider.
- Thêm ảnh WebP 160/320 px cho logo, 400/800 px cho poster bác sĩ, 500 px cho ảnh thiết bị; giữ ảnh gốc và ảnh lớn làm nguồn cho màn hình mật độ cao. Chỉ thay nguồn ảnh trong khối bác sĩ, không đổi bố cục hoặc nội dung.
- Dùng `srcset` sẵn có của WordPress cho ảnh thẻ dịch vụ, gồm bản 300 px; ảnh và bài viết vẫn lấy từ cấu hình hiện tại. Đã triển khai commit `96e6618`. Backup database và 7 file PHP cũ tại `/home/jwhxtzru/backups/home-images-20260924/`; ảnh gốc vẫn giữ nguyên. PHP lint 7/7, đối chiếu file staged/live, 6 URL smoke và SSH đạt. Trình duyệt desktop/mobile 390 px không tràn ngang, logo/thiết bị chọn ảnh nhỏ, không có lỗi JavaScript. Chưa chạy lại PageSpeed Insights sau deploy; điểm 94/LCP 1,3 giây là số đo người dùng gửi trước thay đổi.

## 2026-09-23 — giảm tài nguyên chặn hiển thị và tăng cache theme

- Trang chủ bỏ CSS/JS của mục lục OBS vì không có thành phần `.obs-toc`; giữ plugin trên các trang bài viết có mục lục. CSS khối bác sĩ ở dưới vùng nhìn đầu được tải không chặn hiển thị, có bản dự phòng khi JavaScript tắt; HTML và nội dung khối không thay đổi.
- Slider giữ ảnh đầu tải trước; đợi trang tải xong rồi mới nạp ảnh kề và bắt đầu tự chuyển, để ảnh sau không cạnh tranh với ảnh LCP. Đổi thao tác đọc `offsetWidth` bắt buộc thành hai khung `requestAnimationFrame` để khởi động lại thanh tiến trình.
- Thêm chính sách cache 30 ngày cho các file tĩnh thuộc child theme bằng `.htaccess`; file uploads và plugin vẫn theo cấu hình hosting. Backup file và database tại `/home/jwhxtzru/backups/performance-20260923/`.

## 2026-09-23 — sửa font HTTPS và mô tả trang chủ

- Sửa hai URL font ABeeZee lưu từ trước bằng HTTP khi WordPress kết xuất Global Styles: chỉ đổi sang HTTPS với font cùng tên miền trong `/wp-content/uploads/fonts/`, không sửa dữ liệu WordPress hay font gốc. Hai file font đã xác nhận tải được qua HTTPS. Backup file và database: `/home/jwhxtzru/backups/font-https-seo-20260923/`.
- Thêm một thẻ meta description cho trang chủ để trình tìm kiếm có phần mô tả rõ ràng. Không thay đổi nội dung hiển thị hay khối bác sĩ.

## 2026-09-23 — tối ưu hàng loạt theo yêu cầu

- Đã tối ưu 171 attachment có một nội dung liên quan rõ ràng và file chính từ 100 KB trở lên; không có lỗi. Tổng file đang dùng + crop của nhóm này từ khoảng 93 MiB xuống 11 MiB; 5 slide trang chủ từ khoảng 32 MiB xuống 2,7 MiB. Ảnh <100 KB đã đủ nhẹ; ảnh chưa rõ bài liên quan hoặc dùng chung được giữ để tránh đặt tên sai. File và metadata gốc giữ nguyên để khôi phục từng ảnh. Backup đầy đủ database, uploads và file module tại `/home/jwhxtzru/backups/media-batch-20260923/`.
- Ưu tiên tải ảnh slider đầu trong `<head>` với `imagesrcset` responsive; bỏ `fetchpriority="high"` khỏi ảnh thiết bị ở dưới vùng nhìn đầu để ảnh LCP được ưu tiên. Bố cục, nội dung và khối bác sĩ không thay đổi.
- Thêm ảnh WebP 600/960 px cho 6 thẻ thiết bị và `srcset` theo kích thước hiển thị; ảnh JPG gốc vẫn được giữ. Tổng dung lượng 6 ảnh 600 px là 334 KB so với khoảng 1,4 MB của 6 JPG. Bổ sung nhận diện ảnh slider trang chủ theo cấu hình 5 attachment để đặt tên `trang-chu-slider-anh-ID.webp`; nhận diện ảnh gắn hồ sơ bác sĩ và đánh giá từ post cha. Kiểm thử 20 assertion PHP.
- PageSpeed Insights máy tính sau tối ưu ảnh slider và ưu tiên tải: hiệu suất 96, LCP 1,3 giây trong một lần đo ngày 23/09 (trước đó ảnh người dùng gửi là 74 và 5,9 giây). Số đo có thể dao động giữa các lần chạy.

## 2026-09-23

- Đã triển khai `9cd3ae6` (3 file runtime) lên hosting. Backup file/database: `/home/jwhxtzru/backups/media-optimizer-20260923/`. Baseline `functions.php` khớp production trước thay đổi; SHA256 staged/live khớp; PHP lint functions/module/footer/liên hệ và smoke 6 URL + SSH đạt. WordPress xác nhận module/AJAX hook hoạt động và render 25 ảnh; AJAX chưa đăng nhập bị từ chối HTTP 400. Chưa có ảnh production được chuyển đổi (`_ec_media_original` = 0); người quản trị tự chọn ảnh và thao tác trong Media.

- Thêm màn hình Media → **Tối ưu ảnh & tên file**, xem trước tên từ bài viết, lựa chọn ảnh theo trang, xử lý tuần tự, dừng và khôi phục. Có chế độ WebP/resize và chế độ chỉ đổi tên giữ nguyên byte; nguồn/crop gốc được giữ. Không xử lý hàng loạt thư viện khi triển khai. Chỉ cập nhật metadata ảnh đã chọn; URL trong nội dung được ánh xạ lúc render, không search-replace dữ liệu. Hướng dẫn/giới hạn tại `docs/MEDIA-OPTIMIZATION.md`.
- Kiểm tra trước triển khai: 15 assertion PHP độc lập, 9 kiểm tra WordPress CLI với encoder thật và HTML; PNG mẫu 2.235.985 → 127.212 byte, nguồn không đổi. UI preview desktop/mobile đạt. Chưa kiểm tra AJAX qua admin thật do browser chưa đăng nhập.

## 2026-09-18

- Thêm khối **Máy móc hiện đại** vào trang chủ trước phần Dịch vụ của chúng tôi, gồm 6 ảnh thiết bị JPG đã tối ưu và slider tự chuyển. Có nút trước/sau, chấm chọn, tạm dừng/tiếp tục, vuốt trên điện thoại, bàn phím, fallback scroll-snap và hỗ trợ `prefers-reduced-motion`.
- Khôi phục include `inc/mo-hinh-mat.php` trong `functions.php` để trang `/kien-thuc/` đăng ký lại hàm mô hình mắt 3D và không phát sinh lỗi 500.

## 2026-09-17

- Cho phép chọn tối đa 10 tài khoản Zalo đã nhắn riêng cho bot để cùng nhận thông báo lịch hẹn. Trang quản trị hiển thị tên Zalo, có nút cập nhật Chat ID từ Webhook và gửi kiểm tra đến toàn bộ tài khoản đã chọn. Khi gửi một phần thất bại, lịch hẹn chỉ gửi lại cho tài khoản chưa xác nhận; metadata lịch chỉ lưu mã băm người nhận đã gửi thành công. Backup triển khai: `/home/jwhxtzru/backups/zalo-multi-recipient-20260917-154612/`.

- Chặn event chẩn đoán Webhook bị nhận nhầm là người nhận Zalo. Danh sách chỉ hiển thị Chat ID có event tin nhắn chính thức của Zalo và đã xác nhận chat riêng; ID cũ/chẩn đoán không còn xuất hiện hoặc chọn được. Backup triển khai: `/home/jwhxtzru/backups/zalo-webhook-diagnostic-filter-20260917-152500/`.

- Sửa chọn người nhận Zalo: mọi tin nhắn đến từ chat riêng đã xác thực đều được lưu Chat ID, hiển thị rõ trong danh sách quản trị để chọn và bật thông báo. Bỏ yêu cầu bắt buộc `/nhanlich`; chat nhóm vẫn bị chặn. Khi chưa chọn Chat ID, thao tác bật thông báo hiển thị lý do thay vì tự tắt âm thầm. Backup triển khai: `/home/jwhxtzru/backups/zalo-chat-id-selection-20260917-150315/`.

- Đã triển khai `17e4a0a` để chỉ cho phép người nhận Zalo từ event `/nhanlich` trong chat riêng, chặn candidate cũ/nhóm chưa được xác nhận và thay lỗi gửi chung bằng hướng dẫn an toàn theo trạng thái. Backup file/database tại `/home/jwhxtzru/backups/zalo-private-recipient-20260917-140900/`. Production xác nhận Bot, URL Webhook và endpoint đều đạt; Chat ID đã chọn chưa được xác nhận chat riêng nên thông báo tự động vẫn tắt. PHP lint, hash đối chiếu, endpoint không secret trả 403 và 6 URL smoke test đạt.

- Đã triển khai chẩn đoán Zalo Webhook `866a7d8` và điều chỉnh tiến độ chọn người nhận `846e1e0`. Sao lưu file/database tại `/home/jwhxtzru/backups/zalo-diagnostics-20260917-113747/` và `/home/jwhxtzru/backups/zalo-diagnostics-selection-20260917-114239/`. Trang Cài đặt Zalo có nút kiểm tra Bot, URL Webhook và endpoint riêng, bảng trạng thái an toàn, event Webhook gần nhất và bước cần làm tiếp theo; không hiển thị token, secret hay nội dung tin nhắn. Không dùng `getUpdates` khi Webhook đang hoạt động. Production xác nhận Bot, URL và endpoint đều đạt; có 4 người nhận nhưng chưa chọn Chat ID, thông báo tự động đang tắt. PHP lint, đối chiếu hash, endpoint không có secret trả 403, 6 URL smoke test đều 200.

- Sửa kết nối thông báo Zalo Bot: mã `408 Request timeout` của `getUpdates` nay được hiểu là chưa có tin nhắn mới, không còn báo nhầm lỗi token/Chat ID. Bổ sung Webhook HTTPS có xác thực `X-Bot-Api-Secret-Token`, chống nhận trùng và ghi nhận người đã nhắn `/nhanlich` để quản trị viên chọn Chat ID. Bổ sung tạo secret mã hóa và kích hoạt Webhook từ trang Cài đặt Zalo; thông báo tự động vẫn tắt cho tới khi quản trị viên chọn người nhận và tự bật.

- Endpoint Webhook phản hồi `200` cho yêu cầu kiểm tra đã xác thực của Zalo, không ghi nhận hay xử lý dữ liệu khi gói kiểm tra không phải sự kiện tin nhắn.

- Bổ sung đọc JSON thô khi Zalo không gửi `Content-Type: application/json`, giúp WordPress vẫn nhận được event `message.text.received` và lưu Chat ID sau lệnh `/nhanlich`.

## 2026-09-17

- Hoàn tất kiểm tra bản mô hình mắt chân thực đã lên hosting ngày 16/09 theo duyệt người dùng (1025101 + c5cd0fa). Đã sao lưu database và 4 file hiện hữu tại `/home/jwhxtzru/backups/eye-realism-20260916-155209/`, đối chiếu baseline, kiểm tra SHA256 toàn bộ file staged, lint PHP, xóa cache WordPress/LiteSpeed và smoke 6 URL đạt.
- URL iframe có version theo filemtime để tránh cache cũ. Kiểm tra trực tiếp website: chất liệu/vân mới hiển thị, mặt cắt/đường sáng/zoom/chọn Võng mạc hoạt động. Mobile 390px: trang 375px, iframe 343px, không tràn ngang; chiều cao iframe 2120px bằng nội dung khi bật đường sáng. Không có lỗi JavaScript; chỉ cảnh báo Three.js UMD đã biết. Giữ nội dung cũ, không cập nhật database. Chi tiết rollback trong `docs/EYE-3D-PREVIEW.md`.

## 2026-09-16

- **BẢN XEM THỬ (sau đó đã được duyệt và deploy, xem mục 17/09):** nâng cấp hình ảnh mô hình mắt theo phản hồi: vật liệu PBR, vân mống mắt theo bán kính, mô củng mạc/võng mạc, giác mạc truyền sáng và phản chiếu đèn studio; mạch máu phân nhánh thuôn dần, hoàng điểm có biên mềm, thủy tinh thể trong hơn. Mở đầu bằng nguyên vẹn; chọn phần bên trong tự mở mặt cắt. Giữ nguyên 12 giải thích y khoa, vị trí giải phẫu và đường sáng. Tài nguyên tự sinh trong trình duyệt, không có yêu cầu mạng mới. Preview `http://127.0.0.1:8783/kien-thuc/#cau-tao-mat-3d`; đã được người dùng duyệt đưa lên hosting sau bước xem thử. QA/giới hạn/rollback tại `docs/EYE-3D-PREVIEW.md`.

- Đã triển khai mô hình mắt 3D vào `/kien-thuc/`, sau hero và trước thư mục; toàn bộ nội dung cũ/SEO giữ nguyên. Tách tài nguyên trong child theme, iframe tự đổi chiều cao, đủ 12 bộ phận, fallback, màu #06A1B9. Chỉnh thanh xã hội mobile riêng trang này xuống dưới để tránh che mô hình.
- Backup production trước triển khai tại `/home/jwhxtzru/backups/eye-model-20260916-140351/`; không sửa database ngoài thao tác export backup. PHP lint, purge LiteSpeed, smoke test 6 URL, kiểm tra production desktop/mobile và iframe 3D đạt. Trình duyệt chỉ ghi cảnh báo Three.js UMD deprecated từ thư viện tự host, không có lỗi JavaScript.
- Chi tiết giới hạn kiểm tra và cách khôi phục: `docs/EYE-3D-PREVIEW.md`.

## 2026-09-15

- Đã hoàn tất ánh xạ 3 ảnh theo `1a730be`: backup database/file, cập nhật thumbnail, purge LiteSpeed; PHP lint và 6 URL đạt. Trình duyệt xác nhận đúng 3 tệp ảnh mới tải thành công trên các thẻ trẻ em/người cao tuổi/tăng nhãn áp; các thẻ khác không đổi.

- Theo chỉ định ảnh mới: mắt trẻ em (669) dùng attachment 1230, mắt người cao tuổi (670) dùng 1227, tăng nhãn áp (671) dùng 1228. Đã đối chiếu SHA256 ba tệp người dùng gửi khớp ảnh đã nhập, tái sử dụng media. Chỉ đổi 3 thumbnail; backup `/home/jwhxtzru/backups/service-image-mapping-20260915/`.

- Hoàn tất thay 8 featured image (attachment 1223–1230, post 665–672), mỗi bản WebP 1200px khoảng 64–86KB. Kiểm tra dữ liệu xác nhận chỉ thumbnail đổi, toàn bộ nội dung/thứ tự/liên kết giữ nguyên; file bác sĩ không đổi. PHP lint và 6 URL đạt. Đã purge LiteSpeed toàn bộ sau khi purge riêng URL chưa loại hết HTML cũ, tải lại tab và xác nhận đủ 8 ảnh mới tải thành công theo thứ tự 1–8. Backup có `before.json`, `imports.json`, `complete.json` để đối chiếu/khôi phục.

- Thay 8 ảnh thẻ dịch vụ trang chủ theo đúng thứ tự ảnh người dùng gửi, từ trái sang phải và từ trên xuống dưới (post ID 665–672). Nhập ảnh mới vào thư viện, tối ưu WebP, cập nhật featured image; giữ tên, liên kết, thứ tự, hero và bác sĩ. Ảnh gốc/cấu hình cũ/database được sao lưu tại `/home/jwhxtzru/backups/service-images-20260915/`; media không đưa vào Git.

## 2026-09-14

- Đã triển khai `93ff49e`: backup CSS/database tại `/home/jwhxtzru/backups/home-shortcuts-20260914/`, hash và khóa deploy đạt. PHP lint, 6 URL smoke test đạt; trình duyệt 1440/320px không tràn ngang, đủ 4 thẻ, tiêu đề nằm gọn; khối bác sĩ còn nguyên.

- Làm mới 4 thẻ dẫn nhanh: icon lớn, nền chuyển sắc nhẹ, mũi tên và thẻ đặt lịch xanh đậm; gom 3 thông tin cơ bản thành dải có vạch phân chia. Mobile thẻ 2 cột và thông tin xếp dọc. Chỉ sửa CSS hai vùng, giữ hero và khối bác sĩ. Backup `/home/jwhxtzru/backups/home-shortcuts-20260914/`.

- Đã triển khai `66d7504`: backup database/CSS/PHP, kiểm tra baseline và khóa deploy. PHP lint, 6 URL smoke test đạt; kiểm tra trực tiếp 1920/390/320px không tràn ngang, tiêu đề nằm gọn trong khung; giữ khối bác sĩ và đã tải lại tab trang chủ.

- Thiết kế lại cột nội dung hero thành thẻ xanh đậm, tiêu đề trắng, thông điệp nhấn xanh vàng, giờ mở cửa phân tầng và CTA tương phản. Giữ banner lớn và khối bác sĩ. Backup `/home/jwhxtzru/backups/hero-copy-20260914/`.

- Đã triển khai `158627d`, backup database/CSS tại `/home/jwhxtzru/backups/hero-large-20260914/`. PHP lint, 6 URL đạt. Kiểm tra trình duyệt: banner desktop 1920px rộng khoảng 1161px (trước 775px), mobile 390px không tràn ngang; đã tải lại tab người dùng.

- Mở rộng hero desktop từ khung 1280px lên 1600px, tăng tỷ lệ cột ảnh, giảm khoảng trống dọc còn 48px. Banner giữ nguyên tỷ lệ, không cắt ảnh; không sửa khối bác sĩ. Backup `/home/jwhxtzru/backups/hero-large-20260914/`.

- Đã triển khai hero `c67d9fb`, backup database/3 file tại `/home/jwhxtzru/backups/hero-glass-20260914/`, khóa deploy và kiểm tra hash trước/sau. PHP lint, 6 URL smoke test và QA production slider/progress/vuốt/giảm chuyển động/ngoài màn hình đạt; 320/390/768px không tràn ngang, popup hoạt động, khối bác sĩ không đổi; không gửi lịch thử.

- Hero lấy cảm hứng kính quang học: nền SeaGreen/GreenYellow, vòng kính có chiều sâu theo con trỏ desktop, ánh sáng nhẹ, chữ xuất hiện theo lớp, CTA DarkGreen. Thêm số ảnh/thanh tiến trình đồng bộ, dừng khi hover/focus/ẩn tab/ra ngoài màn hình; hỗ trợ vuốt ở chế độ mờ và giảm chuyển động. Cấu hình chuyển mờ 6 giây, tắt zoom để giữ đủ banner. Chỉ sửa CSS hero, slider JS và cấu hình slider; khối bác sĩ giữ nguyên. QA 1440/768/390/320px, tự chạy/dừng/vuốt, giảm chuyển động, popup và computed styles bác sĩ đạt. Backup dự kiến `/home/jwhxtzru/backups/hero-glass-20260914/`.

- Đã triển khai trang chủ `7b4a0e5`, PHP lint và 6 URL đạt; khối bác sĩ giữ nguyên. Bổ sung vị trí điều khiển slider trên mobile tránh cột mạng xã hội; QA 390/768/320px đạt thao tác slider, cẩm nang, popup, không tràn ngang và computed styles bác sĩ không đổi. Backup bổ sung trong `/home/jwhxtzru/backups/home-refresh-20260914-103820/`.

- Làm mới giao diện trang chủ bằng `assets/home-refresh.css` chỉ nạp trên front page: hero hai cột, thẻ dẫn nhanh nhẹ, dịch vụ ảnh + nội dung trên nền trắng, tiêu đề dễ đọc, khối kiến thức/đặt lịch gọn. Cẩm nang cuối trang dùng details để người đọc chủ động mở, toàn bộ nội dung vẫn trong HTML. Không sửa hoặc di chuyển khối bác sĩ: giữ ngay sau dịch vụ; QA 1440/768/390/320px đối chiếu nguyên HTML và computed styles/kích thước mọi phần tử khối bác sĩ không đổi, không tràn ngang, cẩm nang mở/đóng đạt. Backup triển khai: `/home/jwhxtzru/backups/home-refresh-20260914-103820/`.

- Đã triển khai `3d835d3`: backup database/footer/style tại `/home/jwhxtzru/backups/footer-social-icons-20260914-082721/`, đối chiếu baseline (khác xuống dòng), khóa deploy, đối chiếu file và flush cache. PHP lint, 6 URL smoke test và trình duyệt production 1440/390/320px đạt, đủ 6 SVG tải thành công.

- Thay chữ f/X/P/YT/IG/TK ở footer bằng 6 SVG Bootstrap Icons v1.13.1 tự host, kèm giấy phép MIT. Nút bo góc 44px, logo trắng 22px, màu từng kênh khi hover, focus rõ; màn hình 320px xếp 3 cột. Giữ nguyên URL và tên truy cập. PHP lint và QA 1440/390/320px đạt: đủ 6 ảnh tải, không tràn ngang. Backup triển khai: `/home/jwhxtzru/backups/footer-social-icons-20260914-082721/`.

## 2026-09-12

- Đã triển khai `bff7d10`, backup CSS/database tại `/home/jwhxtzru/backups/desktop-social-rail-20260912-144119/`. Khóa deploy, hash, PHP lint, flush cache và 6 URL đạt; QA trình duyệt production 5 kích thước xác nhận cột biểu tượng bên phải trên desktop, không chồng nút và popup vẫn hoạt động.

- Chuyển mạng xã hội trên desktop/tablet từ hàng dưới thành cột dọc giữa mép phải, tooltip hướng vào trong màn hình. Giữ gọi/đặt lịch ở đáy và bố cục điện thoại. QA 5 kích thước, popup, vùng bấm và giảm chuyển động đạt.

- Đã triển khai `d7eda76` sau backup database và `page.php` tại `/home/jwhxtzru/backups/sidebar-thumbnails-20260912-142909/`. File production cũ khác kiểu xuống dòng, đã đối chiếu diff trước khi cập nhật. PHP lint, flush cache, smoke test 6 URL đạt; Chromium 1440px/390px xác nhận đủ 5 ảnh tải thành công trên trang Glôcôm, không tràn ngang.

- Sửa cột **Bài viết mới nhất** của trang chuyên khoa/trang nội dung trong `page.php`: trước đây hardcode biểu tượng mắt, nay dùng featured image từng bài và ảnh dự phòng khi chưa có. Audit production: 150 bài đã xuất bản đều có thumbnail và file ảnh tồn tại; không cần đổi database/media. PHP lint và render WordPress trang `/chuyen-khoa/glocom/` xác nhận 5 ảnh thật. Backup: `/home/jwhxtzru/backups/sidebar-thumbnails-20260912-142909/`.

- Đã triển khai `f22fdfc`: sao lưu CSS/database, đối chiếu file, flush cache, PHP lint và smoke test 6 URL đạt. Trình duyệt production xác nhận cột mạng xã hội giữa mép phải trên mobile dọc, hàng dưới trên desktop/ngang, popup hoạt động ở 5 kích thước và không lỗi JavaScript.

- Theo ảnh đánh dấu, chuyển mạng xã hội trên điện thoại dọc thành cột sát mép phải, căn giữa màn hình. Hai nút gọi/đặt lịch vẫn ở đáy; desktop và điện thoại ngang giữ hàng dưới để phù hợp chiều cao. Giảm khoảng chừa cuối trang mobile; QA 5 kích thước, vùng bấm, popup và giảm chuyển động đạt. Backup: `/home/jwhxtzru/backups/contact-rail-20260912-142411/`.

- Đã triển khai cụm liên hệ `ec51bf5`: sao lưu database/file và khóa deploy tại `/home/jwhxtzru/backups/contact-dock-20260912-141801/`, đối chiếu file và flush cache. Smoke test 6 URL, PHP lint và kiểm tra trình duyệt production đạt; API vẫn nhận khung giờ cuối 17:00. Không gửi yêu cầu đặt lịch thử hoặc thay đổi cấu hình thông báo.

- Thiết kế lại cụm liên hệ nổi: Zalo/Facebook/TikTok/YouTube hiện sẵn thành hàng ở góc dưới phải, bỏ nút bung/thu gọn. Desktop đặt nút gọi bên trái và đặt lịch cạnh mạng xã hội ở dưới; mobile dùng hai nút gọi/đặt lịch bên dưới hàng biểu tượng, có khoảng an toàn và chừa chỗ cuối trang. Giữ hiệu ứng gọi nhẹ, hỗ trợ giảm chuyển động và form đặt lịch. PHP/JS syntax, QA trình duyệt ở 1440/768/390/320px và 667px ngang đạt: không tràn ngang/chồng nút, vùng bấm tối thiểu 44px, popup/Escape hoạt động. Backup triển khai: `/home/jwhxtzru/backups/contact-dock-20260912-141801/`.

- Đã triển khai Gmail `f66108c` sau backup file/database và khóa deploy tại `/home/jwhxtzru/backups/booking-gmail-20260912-140046/`. Đối chiếu file, PHP lint, render/menu admin và quyền quản trị, hook độc lập Gmail/Zalo, smoke test 6 URL đạt. Mặc định Gmail đang tắt, chưa có tài khoản/mật khẩu ứng dụng; chưa gửi thư thật hoặc thay đổi lịch bệnh nhân.

- Thêm **Đặt lịch khám → Cài đặt Gmail**: email gửi, mật khẩu ứng dụng mã hóa, email nhận, gửi kiểm tra và bật/tắt độc lập Zalo. PHPMailer riêng với Gmail STARTTLS, gửi nền sau khi lưu lịch mới, kết quả và gửi lại trong admin. 127 kiểm tra kết hợp booking/Zalo/Gmail và 105 kiểm tra booking/admin đạt; PHP lint, giao diện 1200px/390px và bắt tay SMTP STARTTLS thực tế đạt. Chưa gửi email thật. Backup triển khai: `/home/jwhxtzru/backups/booking-gmail-20260912-140046/`. Hướng dẫn: `docs/GMAIL.md`.

- Đã triển khai Zalo Bot `552a80d`: backup file và database tại `/home/jwhxtzru/backups/booking-zalo-20260912-093819/`; dùng khóa deploy, kiểm tra hash bản cũ và đối chiếu file mới. PHP lint, menu/capability/render admin thực tế, mã hóa token trên server, smoke test 6 URL và kiểm tra trình duyệt desktop/mobile đạt; form đặt lịch vẫn kết thúc ở 17:00, không lỗi JavaScript. Cấu hình đang tắt và chưa lưu token; chưa kiểm tra gửi Zalo thật vì người dùng chưa tạo bot. Không tạo/sửa lịch bệnh nhân để thử.

- Bổ sung **Đặt lịch khám → Cài đặt Zalo**: lưu token mã hóa, chọn Chat ID từ tin `/nhanlich`, gửi kiểm tra, bật/tắt thông báo. Lịch mới gửi nền qua WP Cron, có trạng thái gửi và nút gửi lại trong chi tiết lịch; không gửi tên/điện thoại/ghi chú bệnh nhân. Mặc định tắt vì chưa tạo bot. Kiểm thử Zalo giả lập 102 kiểm tra và admin 105 kiểm tra đạt; giao diện 1200px/390px không tràn ngang. Hướng dẫn: `docs/ZALO-BOT.md`.

- Đã triển khai quản lý đặt lịch `5ee5f30`: menu **Đặt lịch khám** và ô truy cập trên **Bảng tin**. Production đã qua kiểm tra native WP admin list, SQL lọc ngày/trạng thái, phân quyền, đối chiếu SHA256, PHP lint và smoke test 6 URL; form public trên desktop/mobile vẫn hoạt động, khung cuối 17:00. Kiểm tra lưu ghi chú/trạng thái bằng dữ liệu trong bộ nhớ; không chỉnh sửa lịch bệnh nhân trên production.

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

## 2026-09-17

- Thông báo đặt lịch Zalo gồm họ tên và số điện thoại liên hệ cho các tài khoản nhân viên chat riêng đã xác thực và được chọn; biểu mẫu đặt lịch nêu rõ việc dùng nội bộ này. Đã sao lưu file/database tại `/home/jwhxtzru/backups/mathanoibacninh-zalo-contact-20260917-165120/`.
