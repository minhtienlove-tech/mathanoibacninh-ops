# Audit trước khi mở rộng lên 200 bài — 02/10/2026

**Cập nhật sau audit:** Người quản lý đã chọn gộp ba cặp. ID 107, 108, 109 hiện là nháp; URL `-2` chuyển 301 về ID 116, 119, 120; còn 174 bài công khai. Kế hoạch 26 bài mới ở `content-plan-to-200.md`. Các số 177/ba cặp canonical riêng dưới đây là ảnh chụp hiện trạng **trước** khi gộp.

## Phạm vi và bằng chứng

Đối chiếu trực tiếp WordPress production qua WP-CLI: danh sách 177 bài `post` đang `publish`, metadata và số từ của toàn bộ bài, nội dung nguyên văn của ba cặp nghi trùng, ảnh đại diện của 177 bài. Kiểm tra HTML công khai và canonical của sáu URL nghi trùng. Không sửa bài, theme, media hoặc cấu hình production trong đợt audit này.

## Kết quả

- **177 bài công khai**, đúng mốc nền của kế hoạch. **177/177 bài đã có ảnh đại diện** (sau khi bổ sung 15 ảnh ngày 02/10). Vì vậy việc tạo thêm ảnh cho bài cũ không còn là hạng mục tồn đọng của kế hoạch này.
- **Ba cặp bài gần trùng nội dung**, đều trùng tiêu đề. Sau khi bỏ HTML và chuẩn hóa khoảng trắng, độ tương đồng ký tự lần lượt khoảng 99,6%, 99,6% và 91%:

  | Chủ đề | Hai ID | URL thứ hai có hậu tố | Canonical hiện tại |
  |---|---:|---|---|
  | Cận thị là gì | 107 / 116 | `/can-thi-la-gi-nguyen-nhan-va-dau-hieu-2/` | Mỗi URL trỏ về chính mình |
  | Đổi kính liên tục vẫn mờ | 108 / 119 | `/doi-kinh-lien-tuc-ma-van-mo-2/` | Mỗi URL trỏ về chính mình |
  | Khám khúc xạ | 109 / 120 | `/kham-khuc-xa-gom-nhung-gi-2/` | Mỗi URL trỏ về chính mình |

  Cả sáu URL đều HTTP 200. Cặp ID 109/120 có phần khác biệt đáng kể hơn, cần so kỹ nội dung và các liên kết đến trước khi chọn bản chuẩn. Chưa chuyển nháp, xóa hoặc redirect URL nào.
  Trong liên kết nội bộ lưu ở nội dung post/page, các URL không có hậu tố `-2` nhận lần lượt 9, 13 và 32 liên kết đến; các URL `-2` chỉ nhận 2 liên kết mỗi URL. Ba URL không có `-2` cũng được xuất bản trước. Đây là tín hiệu để ưu tiên xem xét giữ các URL gốc, chưa thay cho dữ liệu lượt truy cập và backlink từ Search Console.
- **15 bài dưới 1.400 từ** theo bộ đếm từ trong nội dung WordPress; 13 bài dưới 500 từ. Phần lớn là 12 bài hỏi đáp/dịch vụ ID 1441–1452 được chủ ý viết ngắn. Đây là cơ hội biên tập theo nhu cầu người bệnh, không phải lý do tự kéo dài đồng loạt lên 1.500 từ.
- **Một bài không có liên kết nội bộ trong `post_content`**: ID 74, “Không nên tự ý mua kháng sinh trị đau mắt đỏ”. Danh sách bài liên quan do theme sinh không được tính vào chỉ số này.
- 23 đề tài trong `docs/content-plan-to-200.md` chưa có bài mới nào được nhập. Đối chiếu 177 tiêu đề không thấy đề tài nào trùng nguyên tiêu đề. Cần tách rõ bài #02 (giác mạc hình chóp) với #13 (loạn thị không đều), #21 (trực màn hình camera) với bài màn hình máy tính hiện có, và #19 (dầu nóng) với bài sơ cứu chấn thương/hóa chất để tránh lặp ý định tìm kiếm.

## Quyết định biên tập cần chốt

Nếu giữ nguyên cả 177 URL công khai, viết thêm 23 bài sẽ đạt **200 URL**, nhưng vẫn còn ba cặp gần trùng. Nếu xử lý trùng bằng cách giữ một URL/bộ và chuyển ba URL dư sang chuyển hướng, kho còn **174 URL nội dung riêng**; khi đó cần **26 bài mới** để đạt 200 URL nội dung riêng. Trước khi đổi bất kỳ URL nào, cần kiểm tra lưu lượng, backlink và liên kết nội bộ của từng cặp, chọn URL giữ lại, backup database và lập redirect tương ứng. Không áp dụng gộp/xóa hàng loạt trong audit này.

Kế hoạch 23 bài hiện là bản brief, chưa phải nội dung được bác sĩ duyệt. Cần viết, đối chiếu nguồn y khoa, kiểm tra bài tương tự, biên tập ngôn ngữ bệnh nhân, gắn liên kết hai chiều và nhập bản nháp để duyệt trước khi xuất bản. Chưa ghi tên hoặc ngày duyệt khi bệnh viện chưa cung cấp.
