# Production changelog

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
