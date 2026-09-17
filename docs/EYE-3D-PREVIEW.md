# Mô hình mắt 3D — bản xem thử, 16/09/2026

## Phiên bản hình ảnh mới — ĐÃ TRIỂN KHAI

Theo phản hồi “đẹp hơn, nhìn thật hơn”, người dùng đã duyệt bản mới và yêu cầu lên hosting. Đã triển khai ngày 16/09/2026, hoàn tất kiểm tra trình duyệt ngày 17/09/2026. URL: https://mathanoibacninh.com/kien-thuc/#cau-tao-mat-3d. Mã nguồn: 1025101 + c5cd0fa.

- Thêm `appearance.js`: chất liệu vật lý, texture mô sinh bằng Canvas có tính xác định, mống mắt nâu với sợi hướng tâm, vòng rìa và vùng vân không đều; lòng trắng hơi hồng, mô võng mạc/hắc mạc. Không tải ảnh, font hoặc thư viện từ bên ngoài.
- Giác mạc truyền sáng và phản chiếu các đèn studio bằng môi trường PMREM; thủy tinh thể trong hơn. Màu hắc mạc tối hơn, hoàng điểm/đĩa thị có mép mềm. Mạch máu thuôn nhỏ dần và có nhánh nhỏ; các mạch ở nửa vỏ đã cắt không còn hiển thị lơ lửng.
- Tăng độ mịn lưới, đổi góc camera để thấy mống mắt rõ; mặc định nguyên vẹn, giữ tự chuyển mặt cắt khi chọn phần bên trong. Giữ xoay, zoom, tự xoay, reset, chú thích và đường sáng.
- So sánh tự động toàn bộ 12 mục dữ liệu với commit production trước sửa: giống nguyên văn. Không thay bố cục giải phẫu lớn hoặc tuyến đường ánh sáng. Những chi tiết hình ảnh cần bác sĩ duyệt: màu/vân mô, phân nhánh và độ dày mạch minh họa, độ trong, cách thể hiện hoàng điểm/đĩa thị. Đây vẫn là mô hình giáo dục được đơn giản hóa, không phải bản quét mắt thật.
- Kiểm tra Chromium: mô hình tải, đủ 12 lựa chọn và nội dung khớp qua công cụ chọn của chính trang; chọn chú thích Võng mạc trên mobile khớp. Đã thử nguyên vẹn/mặt cắt, zoom +/−, tự xoay, reset, phím xoay, chú thích và đường sáng. Không có lỗi JavaScript ở mô hình chạy bình thường; còn cảnh báo Three.js UMD cũ. Đã sửa cảnh báo PMREM blur trong quá trình thử.
- Desktop 1440px, trang nhúng mobile 390px và 320px: không tràn ngang; 390px trang 375px, iframe 343px, cao 1849px đủ nội dung 1848px; 320px trang 305px, iframe 273px. Đã quan sát mô hình ở mobile và các phần cũ vẫn có trong trang preview. Chưa thử cảm ứng hai ngón trên điện thoại thật, Safari/iOS, hoặc đo FPS/pin trên máy yếu.
- Fixture WebGL thất bại: hiện thông báo dự phòng, nút 3D bị vô hiệu, vẫn chọn và đọc được Dây thần kinh thị giác. Node syntax check hai file JS, kiểm tra diff và smoke 6 URL đạt. PHP không thay đổi; lint hai file tối thiểu footer/page-lien-he trên server đạt (không có PHP CLI tại máy local).
- Khôi phục bản hình ảnh trước: lấy `index.html`, `model.js`, `model.css` trong `assets/eye-anatomy/` từ commit `bbce33b`; `appearance.js` có thể giữ trên ổ đĩa vì bản HTML cũ không tải nó. Backup server `/home/jwhxtzru/backups/eye-realism-20260916-155209/files/` có 3 file này và `mo-hinh-mat.php`; restore đúng từng file tương ứng nếu cần, rồi purge cache. Không restore database vì lần này không sửa dữ liệu.

### Kiểm tra sau triển khai bản mới

- Bổ sung version theo filemtime cho iframe và link mở riêng trong `inc/mo-hinh-mat.php`; PHP lint module/footer/page-lien-he đạt trước/sau triển khai. Backup database 11MB và đối chiếu 4 file cũ với baseline Git trước ghi. SHA256 5 file staged khớp local.
- Đã purge WordPress/LiteSpeed, 6 URL smoke test trả 200. Browser trên production thấy iframe version `1789549081`, mắt nguyên vẹn với vân/chất liệu mới; thử mặt cắt, đường sáng, phóng to và chọn Võng mạc khớp mô tả. Không có lỗi JS; chỉ cảnh báo Three.js UMD cũ.
- Mobile 390px: trang 375px/scrollWidth 375px, iframe 343px/scrollWidth 343px; khi bật đường sáng chiều cao iframe và nội dung đều 2120px. Các phần thư mục/bài viết/phân trang bên dưới còn nguyên. Giới hạn kiểm tra thiết bị thật như ghi ở trên.

## Lịch sử bản đầu

Trạng thái: **ĐÃ TRIỂN KHAI PRODUCTION ngày 2026-09-16 theo duyệt của người dùng.**

URL production: https://mathanoibacninh.com/kien-thuc/#cau-tao-mat-3d

URL preview cũ trên máy Codex: http://127.0.0.1:8783/kien-thuc/#cau-tao-mat-3d. Địa chỉ preview chỉ hoạt động trên máy này khi tiến trình preview còn chạy; không phải URL công khai để gửi sang điện thoại khác.

## Vị trí và phạm vi

- Người dùng xác nhận nhúng trực tiếp vào `/kien-thuc/`, sau phần giới thiệu đầu trang, trước thư mục chủ đề. Trang WordPress ID 52, template `page-kien-thuc.php`.
- Không có trang riêng tên “Cấu tạo của mắt” trong lần kiểm kê 215 bài/trang. Không tạo URL hoặc bài viết mới.
- Giữ nguyên tìm kiếm, thư mục chủ đề, bài viết, phân trang, liên hệ, bản đồ, chân trang và dữ liệu SEO. Không sửa database hoặc khối bác sĩ.
- Preview trước triển khai dùng bản HTML công khai của trang hiện tại cộng đoạn nhúng do PHP mới render. Kiểm tra loại bỏ phần chèn trả lại HTML ban đầu byte-for-byte. Production đã upload đúng các file child theme đã duyệt; không sửa database.

## Thay đổi

- `functions.php`: nạp module mới `inc/mo-hinh-mat.php`.
- `page-kien-thuc.php`: thêm duy nhất lời gọi render giữa hero và thư mục.
- Module chỉ enqueue CSS/JS nhúng ở trang `kien-thuc`; iframe dùng tài nguyên trong child theme cùng tên miền và lazy load.
- `assets/eye-anatomy/`: tách HTML, CSS, Three.js r158 đã có trong bản gốc, mã hình học và cầu nối chiều cao. Giữ thông báo giấy phép MIT của Three.js.
- Bỏ header/tiêu đề lớn của tài liệu độc lập để tránh lặp. Giữ nguồn tham khảo và ghi chú mô hình minh họa; dùng tên Bệnh viện Mắt Hà Nội – Bắc Ninh và màu #06A1B9.
- Iframe tự báo chiều cao theo nội dung, xác thực origin/source ở cả hai phía; bảng giải thích không có thanh cuộn riêng. Ngừng render khi ngoài vùng nhìn hoặc tab ẩn.
- Nút tối thiểu 44px, chú thích dạng nút trên màn hình nhỏ; có liên kết mở mô hình riêng. Chỉ trên trang Kiến thức, thanh mạng xã hội ở điện thoại chuyển xuống hàng ngang phía trên nút gọi/đặt lịch để không che mô hình và bảng giải thích. Desktop dành khoảng trống bên phải cho thanh mạng xã hội hiện có.
- Có fallback khi WebGL không hoạt động và bản giải thích tĩnh đủ 12 bộ phận khi tắt JavaScript.

## Nội dung y khoa cần duyệt

Không sửa dữ liệu chuyên môn so với `F:\Benhvienmathanoibacgiang\mắt.html`. Đã so sánh nguyên văn 12 mục (id, tên, tiếng Anh, mô tả, vị trí, ghi nhớ) và toàn bộ hàm hình học `createEyeModel`: khớp, chỉ chuẩn hóa xuống dòng để so sánh. Bản fallback sao chép nội dung gốc. Giữ đường đi ánh sáng và chú thích mô hình đơn giản hóa. Bác sĩ vẫn cần duyệt tính phù hợp của mô hình gốc trước công bố.

SHA256 tệp nguồn: `81bf24cff9f818a1c0585696241be4ac59b96f8b00da04a85a11a1c777472cd2`.

## Kiểm tra đã thực hiện

- PHP lint: `functions.php`, `page-kien-thuc.php`, `inc/mo-hinh-mat.php`, `footer.php`, `page-lien-he.php` đạt. Node syntax check model.js đạt.
- PHP harness: chỉ enqueue hai tài nguyên ở trang đích, không enqueue ở trang khác; render đoạn nhúng thành công.
- Trình duyệt Chromium trong Codex: mô hình WebGL hiển thị; thử 12 lựa chọn, tên và phần giải thích đổi tương ứng; chọn chú thích Võng mạc trực tiếp khớp bảng giải thích.
- Thử nguyên vẹn/mặt cắt, bật/tắt chú thích, tự xoay, kéo chuột, xoay bằng phím, phóng to tới giới hạn, thu nhỏ, đặt lại và bật đường đi ánh sáng. Quan sát đường sáng trên canvas.
- Desktop 1440px, mobile giả lập 390px và 320px: không tràn ngang trong lần đo. Ở 320px chiều rộng trang 305px (15px scrollbar), iframe 273px; chiều cao nội dung 2022.98px được iframe làm tròn thành 2023px. Ở desktop nội dung 976.66px, iframe 977px. Bảng giải thích đọc theo dòng trang.
- Mobile: chọn chú thích Võng mạc mở đúng nội dung. Chưa thử cử chỉ hai ngón trên điện thoại thật hoặc Safari/iOS.
- Fixture chủ động làm WebGL thất bại: thông báo dự phòng xuất hiện, các nút 3D bị vô hiệu hóa, vẫn chọn và đọc được Dây thần kinh thị giác. Phần noscript đủ 12 mục đã kiểm tra trong HTML; chưa thử trình duyệt tắt toàn bộ JavaScript.
- Console mô hình production chỉ ghi cảnh báo bản Three.js UMD cũ, giữ thư viện gốc theo yêu cầu. Lỗi MutationObserver từng thấy trong preview không xuất hiện trong lần kiểm tra production bằng Codex browser.
- `scripts/verify-live.ps1 -CheckSsh`: 6 URL production trả 200. Server đã lint functions.php, page-kien-thuc.php, inc/mo-hinh-mat.php, footer.php và page-lien-he.php; đã purge LiteSpeed.

## Chạy lại preview trên máy này

Các tệp phục vụ preview nằm trong `.tmp/eye3d-preview/`, không đưa HTML snapshot/database/media vào Git. Nếu preview đã tắt, tại thư mục repo chạy Node với `.tmp/eye3d-preview/server.cjs`; server chỉ bind `127.0.0.1:8783`. Khi chạy nền trên Windows phải dùng `Start-Process -WindowStyle Hidden`. File `server.pid` lưu PID của lần chạy hiện tại. Trang preview có banner nội bộ, robots noindex và response X-Robots-Tag noindex.

## Triển khai và khôi phục

1. Deploy đã dùng khóa `/home/jwhxtzru/backups/website-ops-deploy.lock` và backup `/home/jwhxtzru/backups/eye-model-20260916-140351/`.
2. Đã upload module và assets mới trước, sau đó functions.php/page-kien-thuc.php. Không upload `.tmp` hoặc tài liệu HTML độc lập gốc. Không thay đổi bài viết hay SEO trong database.
3. Khôi phục bằng bản backup functions.php và page-kien-thuc.php của chính lần deploy, rồi purge cache, lint và smoke test. File assets/module mới có thể để nguyên khi không còn được nạp; không cần xóa. Nếu có thay đổi khác sau triển khai, hoàn tác riêng dòng include/render thay vì chép đè các sửa đổi mới.
4. Không tự động restore database: thay đổi này không ghi database và restore có thể làm mất lịch khám phát sinh. Bản database backup chỉ dùng khi cần, có xác nhận riêng.

Tab production đã được mở để kiểm tra. Có thể đóng tab xem thử cũ; nếu cần dừng server preview, kiểm tra PID trong `server.pid` đúng là Node chạy `server.cjs` trước khi dừng.
