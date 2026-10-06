# Danh mục URL công khai — kiểm tra ngày 05/10/2026

Phạm vi: `https://mathanoibacninh.com/` production; crawl chỉ đọc, tối đa khoảng 1,4 request/giây cho domain, từ 01:06:43 đến 01:11:42 UTC, rồi đối chiếu 5 URL lịch sử của Search Console. **Không** gửi form đặt lịch hoặc yêu cầu có tác dụng ghi. Không đồng nhất kết quả crawl với trạng thái index thực của Google.

## Nguồn và độ bao phủ

| Nguồn | URL duy nhất |
|---|---:|
| `/wp-sitemap.xml` / `/obs-sitemap.xml`: 200 bài, 138 trang, 12 chuyên mục | 349 (một URL `/kien-thuc/` nằm ở cả sitemap trang và chuyên mục) |
| WordPress REST công khai: `posts`, `pages` trạng thái publish | 344 (200 bài, 144 trang) |
| Hợp sitemap + REST + trang chủ | 356 |
| Thêm qua liên kết `<a href>` trong HTML các trang đã tải | 45 |
| Thêm 5 ví dụ lỗi lịch sử từ Search Console | 5 |
| **Tổng phát hiện và đã fetch** | **406/406** |

401 URL từ sitemap/REST/liên kết được crawl tuần tự; 5 URL GSC được recheck sau lượt đó. Kết quả cuối: **400 HTTP 200, 6 HTTP 404, 0 HTTP 5xx, 0 URL bị chặn**. 14 URL nguồn đi qua redirect tới URL cuối HTTP 200. **0 URL đã render bằng trình duyệt trong phần danh mục này**; các phát hiện liên kết chỉ dựa trên HTML ban đầu. Trong hàng đợi URL hữu hạn đã phát hiện, 0 bị loại và 0 chưa fetch; asset, trang quản trị, URL ngoại miền và chuỗi query tracking bị lọc trước khi lập hàng đợi, không thống kê số lượng từng href bị lọc.

`robots.txt` trả 200, chỉ `Disallow: /wp-admin/` và `Allow: /wp-admin/admin-ajax.php`. Cả `/wp-sitemap.xml` và `/obs-sitemap.xml` trả 200. 349/349 URL duy nhất trong sitemap trả HTTP 200 và không có `noindex`; 11 trang chuyên mục trong sitemap không xuất thẻ canonical (xem URL-02).

Sáu trang đã publish trong REST nhưng không ở sitemap đều 200, `noindex, follow`, self-canonical: `/dat-lich-thanh-cong/`, `/gioi-thieu/ho-so-phap-ly/`, `/gioi-thieu/tam-nhin-gia-tri/`, `/tin-tuc/`, `/trang-dang-ky/`, `/tuyen-dung/`. Việc loại khỏi sitemap phù hợp với chỉ thị noindex hiện có; chưa coi là lỗi. 11 URL không nhận liên kết nội bộ trong tập crawl gồm đúng 6 trang noindex này và 5 URL lịch sử GSC; **không tìm thấy trang indexable mồ côi trong tập URL đã phát hiện**. Kết luận này không bao gồm liên kết chỉ sinh sau JavaScript hoặc các route chưa được phát hiện.

## Phát hiện có bằng chứng

| ID | Trạng thái/mức | Bằng chứng từ CSV | Hàm ý và cách kiểm tra tiếp |
|---|---|---|---|
| URL-01 | Đã xác nhận, P2 | `https://mathanoibacninh.com/?page_id=13` trả 404; có 6 cạnh nội bộ đến URL này từ các trang cận thị, loạn thị, viễn thị (3 URL hiện tại và 3 bản cũ chuyển hướng). | Kiểm tra link gốc trong nội dung 3 trang đang publish; thay bằng đích chính xác khi biết page ID 13 từng trỏ đến đâu. Không đoán URL thay thế. |
| URL-02 | Đã xác nhận, P2 đề xuất | 22 URL archive chuyên mục `kien-thuc/{category}/` và phân trang của chúng trả 200/indexable nhưng không có `<link rel="canonical">`; 11 URL chuyên mục chính có trong sitemap. | Canonical HTML là tín hiệu tùy chọn, không thiếu là lỗi index tất yếu. Kiểm tra OBS SEO và rewrite chuyên mục trong child theme; cân nhắc self-canonical hợp lệ trên từng archive/page khi cần thống nhất variant. |
| URL-03 | Đã xác nhận, P2 cần đánh giá | Cả 19 URL `/kien-thuc/page/2/` đến `/page/20/` trả 200 nhưng canonical về `/kien-thuc/`. Tiêu đề riêng theo số trang; `page-kien-thuc.php` dùng `posts_per_page=10`, `paged`. | Trang 1 đồng thời có thư mục link đủ 200 bài, nên chưa có bằng chứng bài sâu bị mất đường dẫn. Đối chiếu card/content từng trang trước khi đổi canonical; nếu trang phân trang là nội dung riêng, nên tự canonical. |
| URL-04 | Đã xác nhận, P3 | 14 URL nội bộ được liên kết nhưng chuyển hướng: 13 đường dẫn còn tiền tố cũ `/benhvienmathanoibacninh/`, 1 URL `/chuyen-khoa/mat-nguoi-cao-tu/` sang `/chuyen-khoa/mat-nguoi-cao-tuoi/`; xuất hiện từ 16 trang nguồn. | Sửa href gốc về URL cuối để giảm hop. `rg` child theme không thấy href literal; khả năng cao nằm trong nội dung CMS, cần kiểm chứng trước khi sửa. Redirect hiện hoạt động nên không kết luận link hỏng. |
| URL-05 | Đã xác nhận, P3 | Trong 380 URL 200, không noindex, không redirect: 0 thiếu `<title>`; 2 cặp dùng title giống hệt: `/chuyen-khoa/dich-kinh-vong-mac/` với `/kien-thuc/dich-kinh-vong-mac/`, và `/chuyen-khoa/mat-nguoi-cao-tuoi/` với `/kien-thuc/mat-nguoi-cao-tuoi/`. | Hai loại trang có mục đích khác nhau; có thể phân biệt tên chuyên khoa và danh mục bài viết. Không suy ra nội dung trùng chỉ từ title. |
| URL-06 | Đã xác nhận hiện trạng, chưa kết luận lỗi mới | Cả 5 URL lịch sử GSC đều 404 ngày 05/10. GSC từng thấy 3 URL 404 (20–21/09) và 2 URL 5xx (03–04/09); hai URL 5xx hiện không còn trả 5xx mà là 404. Không thấy liên kết nội bộ tới 5 URL này trong crawl. | Tách dữ liệu GSC cũ khỏi hiện trạng. Nếu URL bài cũ có đích thay thế thật hoặc backlink giá trị, cân nhắc redirect phù hợp; `hello-world` và `/Phone` có thể là URL rác. Không tự redirect tất cả về trang chủ. |
| URL-07 | Đã xác nhận, P2 cho trang điều hướng chính | Trong 380 URL 200/indexable/cuối: 38 không có `meta name="description"`. Gồm 19 trang `/kien-thuc/page/2/`–`/page/20/` và 19 trang khác, trong đó có `/kien-thuc/`, `/dich-vu/`, `/hoi-dap/` và 10 trang chuyên khoa. | Meta description không bắt buộc để lập chỉ mục, nhưng nên bổ sung mô tả hữu ích và riêng cho các trang điều hướng quan trọng; ưu tiên trang chính trước trang phân trang. Google có thể tự chọn đoạn trích khác. |

## Dữ liệu và giới hạn

- [DANH-MUC-URL.csv](DANH-MUC-URL.csv) có URL gốc/cuối, nguồn, status cuối, robots, canonical, title, meta description, H1, template, trạng thái fetch/render và ID vấn đề. Mô tả meta được ghép từ [meta-descriptions-raw.csv](meta-descriptions-raw.csv) bằng **406 URL khớp chính xác**, cùng status HTTP; đây là lượt crawl thứ hai, không phải cùng response với các trường còn lại. Trong 380 URL 200/indexable/cuối: 0 thiếu title, 38 thiếu meta description, 0 thiếu H1. Có 10 nhóm mô tả meta trùng trên 21 URL, đều là trang chuyên mục kiến thức và phân trang của chính chuyên mục đó; H1 trùng ở 13 nhóm/47 URL, phần lớn do phân trang nên không tự coi là lỗi.
- [internal-link-edges.csv](internal-link-edges.csv) giữ 23.624 cạnh nguồn–đích duy nhất; một cạnh là một cặp URL, không đếm số lần cùng href lặp trong trang.
- [gsc-historical-recheck.csv](gsc-historical-recheck.csv) giữ 5 URL được cung cấp từ GSC cùng status hiện tại và redirect chain (nếu có).
- [inventory-stats-final.json](inventory-stats-final.json) và [url-seeds.json](url-seeds.json) giữ checkpoint, số URL theo nguồn và danh sách seed.
- Danh mục này không xác minh DOM sau JavaScript, lỗi console, traffic, Googlebot, hay URL thực sự được Google chọn làm canonical. Những phần đó thuộc báo cáo tổng hợp và dữ liệu Search Console.
