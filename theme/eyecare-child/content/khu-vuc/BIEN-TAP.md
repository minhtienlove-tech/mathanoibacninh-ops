# Nội dung khu vực khám mắt — 24/09/2026

## Phạm vi và kế hoạch xuất bản

- Ba URL hiện có giữ nguyên: `/khu-vuc/`, `/khu-vuc/kham-mat-bac-giang/`, `/khu-vuc/kham-mat-bac-ninh/`. Nội dung ba trang nằm ở `hub.html`, `bac-giang.html`, `bac-ninh.html`, khoảng 3.500 từ hiển thị mỗi trang sau khi thay mục lục và FAQ.
- Danh mục `areas.json` gồm đủ 99 xã/phường tỉnh Bắc Ninh hiện hành theo Nghị quyết 1658/NQ-UBTVQH15: 66 xã, 33 phường; nhóm lịch sử Bắc Ninh cũ 42, Bắc Giang cũ 57. URL con: `/khu-vuc/kham-mat-bac-ninh/{xa|phuong}-{ten}/` hoặc `/khu-vuc/kham-mat-bac-giang/{xa|phuong}-{ten}/`.
- Mỗi trang xã/phường đọc một tệp HTML riêng trong `dia-ban/`, khoảng 603–750 từ, có trọng tâm nhãn khoa, phần giải thích địa danh và FAQ riêng. Không có đoạn văn dài trùng nguyên văn giữa hai trang; độ tương đồng Jaccard trên cụm năm từ cao nhất 0,158 ở lần kiểm tra ngày 24/09/2026. Không khẳng định bệnh viện có cơ sở tại 99 địa bàn.
- Bản xem thử cục bộ có đủ 102 trang. Sau khi người dùng chọn xuất bản cả 102 trang, script vẫn yêu cầu hai bước rõ ràng: tạo 99 draft bằng `EYECARE_KV_APPLY_DRAFTS=1`, rồi xuất bản bằng `EYECARE_KV_PUBLISH=1` sau backup và kiểm tra.
- Chưa có tên/ngày bác sĩ duyệt được xác minh để công bố, nên không gắn `reviewedBy` hoặc nhận là đã được một cá nhân duyệt. Bộ phận vận hành cần tiếp tục kiểm tra địa chỉ, giờ tiếp nhận, phạm vi dịch vụ/giấy phép, bảo hiểm và kênh liên hệ khi các thông tin này thay đổi. Nội dung mới chỉ hướng dẫn sức khỏe nói chung, không chẩn đoán hoặc kê đơn cá nhân.

## Nguồn kiểm chứng

- Địa danh: https://xaydungchinhsach.chinhphu.vn/toan-van-nghi-quyet-so-1658-nq-ubtvqh15-sap-xep-cac-dvhc-cap-xa-cua-tinh-bac-ninh-nam-2025-119250616193651987.htm
- Tổ chức hai cấp và sắp xếp 313 thành 99 đơn vị: https://www.bacninh.gov.vn/web/so-noi-vu/news/-/details/207614/ket-qua-sau-gan-mot-thang-thuc-hien-chinh-quyen-ia-phuong-02-cap-5505001
- Khám giãn đồng tử: https://www.nei.nih.gov/eye-health-information/healthy-vision/finding-eye-doctor/get-dilated-eye-exam
- Glôcôm: https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/glaucoma
- Bong võng mạc/cấp cứu: https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/retinal-detachment
- Đục thủy tinh thể: https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/cataracts
- Cơn mất thị lực thoáng qua và mức khẩn: https://www.stroke.org/en/about-stroke/types-of-stroke/tia-transient-ischemic-attack
- Địa chỉ, giờ và số điện thoại: `eyecare_du_lieu_thuc_the()` trên production, kiểm tra ngày 24/09/2026.

## Kiểm tra và khôi phục khi triển khai

1. `git pull`, đối chiếu theme đang chạy với repo và đảm bảo không có máy khác đang deploy. Backup database và các file liên quan vào `~/backups/` trên server trước mọi thay đổi production.
2. Chạy kiểm tra 99 tệp HTML riêng: độ dài, FAQ, nguồn y khoa, liên kết nội bộ, đoạn văn trùng và tương đồng n-gram. Lint tất cả PHP, xác nhận link bài kiến thức trả 200, kiểm tra schema và meta không trùng.
3. Triển khai file child theme. Khởi tạo 99 draft bằng script sau khi backup DB; chạy lại kiểm tra, sau đó xuất bản 99 trang bằng cờ `EYECARE_KV_PUBLISH=1`. Menu cấp trên trỏ tới ba trang chính; mục lục trên trang dẫn tới 99 trang con.
4. Test desktop/mobile, menu, mục lục, FAQ, schema, SEO title/description, no horizontal overflow, console, URL chính và rollback. Nếu cần khôi phục, chép lại đúng các file từ backup và phục hồi trạng thái những page mới bằng ID trong nhật ký; không restore toàn bộ database vì có thể xóa lịch khám phát sinh. Không xóa hàng loạt trang nếu chưa được xác nhận.
