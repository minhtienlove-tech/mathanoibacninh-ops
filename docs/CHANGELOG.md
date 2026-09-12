# Production changelog

## 2026-09-12

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
