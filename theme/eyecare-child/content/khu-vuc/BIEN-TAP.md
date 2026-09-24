# Bản thảo khu vực khám mắt — 24/09/2026

## Phạm vi và kế hoạch xuất bản

- Ba URL hiện có giữ nguyên: `/khu-vuc/`, `/khu-vuc/kham-mat-bac-giang/`, `/khu-vuc/kham-mat-bac-ninh/`. Nội dung ba trang nằm ở `hub.html`, `bac-giang.html`, `bac-ninh.html`, khoảng 3.500 từ hiển thị mỗi trang sau khi thay mục lục và FAQ.
- Danh mục `areas.json` gồm đủ 99 xã/phường tỉnh Bắc Ninh hiện hành theo Nghị quyết 1658/NQ-UBTVQH15: 66 xã, 33 phường; nhóm lịch sử Bắc Ninh cũ 42, Bắc Giang cũ 57. Các trang con được tạo từ một nguồn dữ liệu thống nhất. URL dự kiến: `/khu-vuc/kham-mat-bac-ninh/{xa|phuong}-{ten}/` hoặc `/khu-vuc/kham-mat-bac-giang/{xa|phuong}-{ten}/`.
- Bản xem thử cục bộ có đủ 102 trang. Chưa ghi nội dung y khoa vào production; ba trang hiện có và menu production vẫn giữ nguyên. PHP trong child theme đã sẵn sàng nhưng chưa upload vào theme đang chạy.
- Trước xuất bản, bác sĩ chuyên khoa phải rà soát nội dung, ký tên và ngày duyệt thật. Bộ phận vận hành phải xác nhận địa chỉ, giờ tiếp nhận, phạm vi dịch vụ/giấy phép hoạt động, thông tin bảo hiểm và các tuyến liên hệ. Không tự gắn `reviewedBy` hoặc tên bác sĩ cho bản thảo.
- 99 trang xã/phường có khung thông tin thật về tên và đơn vị gốc, triệu chứng, chuẩn bị và liên kết; nội dung y khoa dùng chung có chủ đích. **Chỉ nên publish/index từng trang sau khi bổ sung thông tin địa phương độc đáo và hữu ích**, tránh 99 trang gần giống nhau chỉ thay tên địa danh. Chạy `tao-ban-nhap.php` mặc định chỉ kiểm kê; với biến môi trường `EYECARE_KV_APPLY_DRAFTS=1` mới tạo draft, không tự publish.

## Nguồn kiểm chứng

- Địa danh: https://xaydungchinhsach.chinhphu.vn/toan-van-nghi-quyet-so-1658-nq-ubtvqh15-sap-xep-cac-dvhc-cap-xa-cua-tinh-bac-ninh-nam-2025-119250616193651987.htm
- Tổ chức hai cấp và sắp xếp 313 thành 99 đơn vị: https://www.bacninh.gov.vn/web/so-noi-vu/news/-/details/207614/ket-qua-sau-gan-mot-thang-thuc-hien-chinh-quyen-ia-phuong-02-cap-5505001
- Khám giãn đồng tử: https://www.nei.nih.gov/eye-health-information/healthy-vision/finding-eye-doctor/get-dilated-eye-exam
- Glôcôm: https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/glaucoma
- Bong võng mạc/cấp cứu: https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/retinal-detachment
- Đục thủy tinh thể: https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/cataracts
- Địa chỉ, giờ và số điện thoại: `eyecare_du_lieu_thuc_the()` trên production, kiểm tra ngày 24/09/2026.

## Kiểm tra và khôi phục khi được duyệt

1. `git pull`, đối chiếu theme đang chạy với repo và đảm bảo không có máy khác đang deploy. Backup database và các file liên quan vào `~/backups/` trên server trước mọi thay đổi production.
2. Rà soát bác sĩ, sửa bản thảo theo yêu cầu, đối chiếu lại danh sách xã/phường nếu có nghị quyết mới. Lint tất cả PHP, xác nhận các link bài kiến thức trả 200, kiểm tra schema và meta không trùng.
3. Triển khai file child theme đã duyệt. Khởi tạo 99 draft bằng script sau khi backup DB; chỉ publish những trang đã có nội dung địa phương riêng và bác sĩ duyệt. Menu chỉ trỏ tới các trang cha đã publish.
4. Test desktop/mobile, menu, mục lục, FAQ, schema, SEO title/description, no horizontal overflow, console, URL chính và rollback. Nếu cần khôi phục, chép lại đúng các file từ backup và phục hồi trạng thái những page mới bằng ID trong nhật ký; không restore toàn bộ database vì có thể xóa lịch khám phát sinh. Không xóa hàng loạt trang nếu chưa được xác nhận.
