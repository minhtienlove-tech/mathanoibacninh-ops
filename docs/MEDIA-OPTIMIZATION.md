# Tối ưu ảnh và tên file

Vào **Media / Thư viện → Tối ưu ảnh & tên file**:
`https://mathanoibacninh.com/wp-admin/upload.php?page=ec-media-optimize`.

1. Chọn ảnh trong trang (25 ảnh/lượt). Xem dung lượng, kích thước và ảnh thu nhỏ.
2. Chọn bài viết/trang dùng để đặt tên. Công cụ tìm bài đính kèm, ảnh đại diện và tham chiếu trong nội dung, bao gồm hồ sơ bác sĩ và đánh giá đang hiển thị. Ảnh thuộc slider trang chủ được đặt tên theo “Trang chủ slider”. Với ảnh dùng chung, chọn một bài làm tên chính. Nếu chưa nhận diện được, nhập ID bài viết rồi bấm **Xem tên**.
3. **Tối ưu & đổi tên** tạo WebP, cạnh dài tối đa 1600/1920/2560 px, chất lượng 65–90 (mặc định 82). Chỉ kích hoạt khi tổng dung lượng ảnh và các crop nhẹ hơn.
4. **Chỉ đổi tên, giữ chất lượng** sao chép chính xác byte ảnh, giữ định dạng và kích thước; phù hợp ảnh đã nhẹ hoặc cần giữ chi tiết.
5. Theo dõi từng ảnh; có thể dừng sau ảnh đang xử lý. Tải lại danh sách để thấy trạng thái mới và nút **Khôi phục ảnh gốc**.

Tên mới dạng `tieu-de-bai-viet-anh-123.webp`, nằm trong thư mục riêng `uploads/ec-optimized/ID/UUID/`. Hậu tố ID tránh trùng tên; với chế độ chỉ đổi tên, đuôi file giữ JPG/PNG/WebP. Không đổi ID attachment, alt, caption, tiêu đề bài hay dữ liệu y khoa. Không xử lý tự động ảnh mới upload.

## Liên kết và dữ liệu khôi phục

- Cả file gốc và thumbnail gốc đều được giữ. Công cụ **giảm dung lượng truyền tải**, không dọn ổ đĩa; dung lượng lưu trữ tăng bởi các bản sao. Bản xử lý lỗi/bị bỏ qua có thể vẫn còn trong thư mục riêng; không tự xóa file.
- Metadata cũ lưu tại `_ec_media_original`. Các ảnh gọi bằng WordPress attachment API dùng bản mới. URL trong `the_content` và widget text được đổi khi xuất HTML, không search-replace database. Có xử lý `srcset`, ảnh lazy load và `picture/source`.
- URL gốc vẫn hoạt động. URL ghi cứng ở CSS, metadata trình dựng trang, theme_mod hoặc mã riêng có thể tiếp tục dùng bản gốc; cần kiểm tra riêng. Không cam kết mọi bản sao ảnh trên hosting đều được tối ưu.
- Không chỉnh độ sáng, màu sắc, thông tin chuyên môn. PNG/WebP động không được nén; chế độ chỉ đổi tên giữ nguyên byte. WebP là nén mất dữ liệu, nên ảnh chẩn đoán đòi hỏi nguyên bản nên dùng chế độ chỉ đổi tên.
- Quyền `manage_options` + `edit_post`, nonce AJAX; khóa từng attachment để chặn xử lý trùng; chỉ nhận file bên trong uploads. Không có endpoint công khai ghi dữ liệu.

## Kiểm tra 23/09/2026

- 15 kiểm tra PHP độc lập với dữ liệu giả lập: quyền, đường dẫn, tên tiếng Việt, owner nội bộ, crop, trùng crop, resize, không nhẹ hơn, activation rollback, restore, đổi tên giữ byte.
- 9 kiểm tra bằng WordPress CLI trên hosting: WebP thật, MIME, hash gốc bất biến, nhẹ hơn, đổi URL HTML/picture, srcset không trùng descriptor, alt/link ngoài giữ nguyên, render 25 attachment thật.
- Bản mã hóa thử attachment 344 ghi **ngoài webroot**, không thay attachment: 2.235.985 → 127.212 byte, 1536×1024.
- Browser kiểm tra bản HTML admin render từ WordPress, JS thật: chặn chưa chọn ảnh, chọn bài cập nhật filename; desktop và mobile 390px không tràn ngang; không có lỗi JS trong các thao tác đã thử.
- Phiên browser chưa đăng nhập admin: chưa thử luồng AJAX tối ưu/khôi phục qua giao diện admin thật. Kiểm tra PHP mô phỏng state, encoder/HTML thật không thay thế kiểm tra end-to-end dữ liệu WordPress. Chưa tối ưu hàng loạt thư viện production.

## Rollback

Khôi phục từng ảnh bằng nút **Khôi phục ảnh gốc** trước khi gỡ chức năng. Công cụ khôi phục đường dẫn, metadata và MIME gốc; giữ file mới để liên kết đã chia sẻ không hỏng.

Rollback mã: khôi phục `functions.php` từ backup triển khai, giữ các file mới trên đĩa (không được include sẽ không chạy), purge cache LiteSpeed và smoke test. Không restore toàn bộ database vì có thể làm mất lịch hẹn/nội dung tạo sau backup. Backup database chỉ dùng cho cứu hộ có kiểm soát. Đường dẫn backup thực tế được ghi ở CHANGELOG.
