# Kế hoạch 20 chủ đề — bản làm việc, 30/09/2026

Trạng thái: **chưa xuất bản nội dung mới**. Bản kiểm kê `inventory.json` lấy từ WP-CLI gồm 294 URL công khai (150 bài viết, 144 trang), có ID, URL, loại, chuyên mục, tác giả WordPress, ngày sửa và liên kết trong `post_content`. Nội dung sinh động qua template không nằm trong trường này; vì vậy không thể kết luận trang không có liên kết chỉ từ mảng `internal_links` rỗng. Trang thư viện hiện có là `/kien-thuc/`; category gốc `Kiến thức nhãn khoa` có slug `kien-thuc`. Menu WordPress `Menu chinh` có 8 mục, gồm trang thư viện; trang tác giả `/author/bvmat/` đang HTTP 200, tiêu đề chỉ là “bvmat”, cần hồ sơ biên tập thực. URL `/`, `/bang-gia/` và `/khu-vuc/` đều đang hoạt động. Có hai cặp bài trùng tiêu đề 107/116 và 109/120 cần xử lý riêng; không xóa hay đổi URL trong đợt này.

## Dữ liệu bệnh viện đã đối chiếu

| Dữ liệu | Giá trị / mức chắc chắn | Nguồn |
|---|---|---|
| Tên | Bệnh viện Mắt Hà Nội – Bắc Ninh | Website bệnh viện, trang giới thiệu và liên hệ, kiểm 30/09/2026 |
| Địa chỉ công bố | Lô 4, đường Hùng Vương, phường Bắc Giang, thành phố Bắc Ninh | `/lien-he/`; đối chiếu thay đổi đơn vị hành chính tại Nghị quyết 39/2026/QH16 |
| Hotline | 0868 899 396 | Header/footer và `/lien-he/` |
| Giờ thường lệ | 07:30–18:00, bảy ngày/tuần | Header website; ngày lễ và thay đổi đột xuất phải hỏi lại |
| Đặt hẹn | `/dat-lich-kham/`; bệnh viện gọi xác nhận, yêu cầu gửi form không phải lịch đã được bảo đảm | Trang đặt lịch công khai |
| Giá | `/bang-gia/` đang đăng danh mục dịch vụ, nhưng ngày hiệu lực và bao gồm/chưa bao gồm chưa được cung cấp | Trang bảng giá công khai; cần phòng tài chính xác nhận |
| BHYT | Có tiếp nhận BHYT | Người dùng xác nhận 30/09/2026; thiếu hợp đồng/phạm vi thanh toán/thủ tục cụ thể |
| Đo và cắt kính | Có | Người dùng xác nhận 30/09/2026; thiếu quy trình, thời gian, báo giá |
| Phẫu thuật khúc xạ | Có | Người dùng xác nhận 30/09/2026; chưa xác minh danh mục kỹ thuật đang triển khai và hồ sơ phép |
| Bác sĩ/tác giả/người duyệt | Website có hồ sơ bác sĩ; chưa có xác nhận ai thực sự viết và duyệt 20 bản thảo | Cần bệnh viện chỉ định; không gắn tên bác sĩ trước khi duyệt |

Không coi danh mục giá trên web là xác nhận mỗi kỹ thuật đang được thực hiện tại thời điểm xuất bản. Không ghi mức hưởng BHYT hay chỉ định lâm sàng cá nhân khi thiếu hồ sơ.

## Bản đồ chủ đề

Quy ước: `U` là soạn bản cập nhật cho URL đang có nhưng **không ghi đè nội dung công khai trước duyệt**; `N` là bài mới ở trạng thái nháp cục bộ, chưa nhập WordPress. Tác giả và người duyệt của cả 20 mục: **chờ bệnh viện chỉ định/xác nhận**. Các file `drafts/NN.md` chứa brief, SEO, bài hoàn chỉnh, FAQ, nguồn, ảnh, link và điểm cần duyệt. Ưu tiên P1: dữ liệu bệnh viện/khám; P2: khúc xạ; P3: phẫu thuật.

| ID | Tiêu đề/ý định chính | Từ khóa chính và ngữ nghĩa | Nhóm | URL thật hoặc đề xuất | Loại | Nguồn chính | Ưu tiên |
|---|---|---|---|---|---|---|---|
| 01 | Giới thiệu bệnh viện và cách đi khám | Bệnh viện Mắt Hà Nội Bắc Ninh; chuyên khoa, quy trình | Thông tin khám | `/gioi-thieu/` | U | Website, giấy phép còn thiếu | P1 |
| 02 | Địa chỉ, đường đi | địa chỉ Bệnh viện Mắt Hà Nội Bắc Ninh; Hùng Vương, Bắc Giang | Thông tin khám | `/lien-he/` | U | Liên hệ, bản đồ cần xác minh | P1 |
| 03 | Giờ khám, đặt hẹn | giờ khám Bệnh viện Mắt Hà Nội Bắc Ninh; cuối tuần, xác nhận | Thông tin khám | `/dat-lich-kham/` | U | Header, trang đặt lịch | P1 |
| 04 | Bảng giá chính thức | bảng giá Bệnh viện Mắt Hà Nội Bắc Ninh; phí, đơn vị | Chi phí/BHYT | `/bang-gia/` | U | Bảng giá và phiên bản hiệu lực cần xác minh | P1 |
| 05 | Chuẩn bị lần khám đầu | đi khám mắt cần chuẩn bị gì; giấy tờ, kính, kết quả cũ | Thông tin khám | `/kien-thuc/di-kham-mat-can-chuan-bi-gi/` (ID 285) | U | NEI, quy trình bệnh viện cần xác minh | P1 |
| 06 | Tìm bác sĩ và lịch khám | bác sĩ Bệnh viện Mắt Hà Nội Bắc Ninh; hồ sơ, lịch | Thông tin khám | `/doi-ngu-bac-si/` | U | Hồ sơ và lịch xác nhận của bệnh viện | P1 |
| 07 | BHYT tại bệnh viện | BHYT Bệnh viện Mắt Hà Nội Bắc Ninh; giấy tờ, phần tự trả | Chi phí/BHYT | `/kien-thuc/bhyt-kham-mat-tai-benh-vien-mat-ha-noi-bac-ninh/` | N | Người dùng; luật BHYT; hợp đồng cần xác minh | P1 |
| 08 | Chọn nơi khám mắt | khám mắt Bắc Ninh; chuyên môn, giá, tái khám | Thông tin khám | `/kien-thuc/chon-noi-kham-mat-o-bac-ninh/` | N | Website, NEI | P1 |
| 09 | Cấu phần chi phí khám cận | khám mắt cận thị bao nhiêu tiền; khám, khúc xạ | Chi phí/BHYT | `/kien-thuc/kham-can-thi-bao-nhieu-tien/` | N | Bảng giá bệnh viện | P2 |
| 10 | Các bước khám cận | khám cận thị gồm những gì; thị lực, khúc xạ | Tật khúc xạ ở người lớn | `/kien-thuc/kham-khuc-xa-gom-nhung-gi/` (ID 120) | U | NEI, quy trình bệnh viện | P2 |
| 11 | Đo kính so với khám mắt | đo mắt và khám mắt khác nhau thế nào; bệnh lý mắt | Tật khúc xạ ở người lớn | `/kien-thuc/do-mat-lam-kinh-va-kham-mat-khac-nhau/` | N | NEI | P2 |
| 12 | Trẻ khám cận | khám cận thị cho trẻ ở Bắc Ninh; học đường, phụ huynh | Cận thị trẻ em | `/kien-thuc/kham-can-thi-cho-tre-o-bac-ninh/` | N | AAPOS, NEI | P2 |
| 13 | Cận nhẹ 0,75 D | cận 0.75 độ có cần đeo kính; nhu cầu nhìn, đơn kính | Tật khúc xạ ở người lớn | `/kien-thuc/can-075-do-co-can-deo-kinh/` | N | NEI | P2 |
| 14 | Trẻ tăng độ nhanh | cận thị ở trẻ tăng nhanh; tiến triển, theo dõi | Cận thị trẻ em | `/kien-thuc/cach-han-che-tang-do-can-cho-tre/` (ID 140) | U | AAPOS | P2 |
| 15 | Đo/cắt kính tại bệnh viện | đo mắt cắt kính tại bệnh viện; đơn kính, tròng | Tật khúc xạ ở người lớn | `/kien-thuc/do-mat-cat-kinh-tai-benh-vien/` | N | Người dùng; quy trình bệnh viện còn thiếu | P2 |
| 16 | Điều kiện mổ cận | cận bao nhiêu độ thì mổ được; giác mạc, ổn định | Kiến thức phẫu thuật khúc xạ* | `/kien-thuc/can-bao-nhieu-do-thi-mo-duoc/` | N | FDA, NEI | P3 |
| 17 | Cấu phần chi phí mổ cận | chi phí mổ cận; một/hai mắt, tái khám | Chi phí/BHYT | `/kien-thuc/chi-phi-mo-can-gom-nhung-gi/` | N | Bảng giá bệnh viện, điều khoản giá cần xác minh | P3 |
| 18 | Các phương pháp | các phương pháp mổ cận; laser, thấu kính | Kiến thức phẫu thuật khúc xạ* | `/kien-thuc/cac-phuong-phap-mo-can/` | N | FDA, NEI | P3 |
| 19 | Mổ cận sau 40 tuổi | trên 40 tuổi có mổ cận được không; lão thị, kỳ vọng | Kiến thức phẫu thuật khúc xạ* | `/kien-thuc/tren-40-tuoi-co-mo-can-duoc-khong/` | N | FDA, NEI | P3 |
| 20 | Khám trước mổ | khám trước mổ cận; giác mạc, tiền sử | Kiến thức phẫu thuật khúc xạ* | `/kien-thuc/kham-truoc-mo-can-gom-nhung-gi/` | N | FDA | P3 |

*Chuyên mục chưa có: đề xuất tạo đúng một chuyên mục con của `Kiến thức nhãn khoa`, slug `kien-thuc-phau-thuat-khuc-xa`; mô tả là kiến thức phục vụ trao đổi với bác sĩ, không đại diện danh mục kỹ thuật bệnh viện. Tái dùng `Chuẩn bị đi khám và bảo hiểm`, `Tật khúc xạ ở người lớn`, `Cận thị trẻ em`. Trang tĩnh không ép gán category.

## Quy tắc liên kết và triển khai

- Bài nháp chỉ liên kết trong nội dung đến URL **đã xuất bản**: `/gioi-thieu/`, `/lien-he/`, `/dat-lich-kham/`, `/bang-gia/`, `/doi-ngu-bac-si/`, `/kien-thuc/` và bài chuyên môn thật sự liên quan. Link giữa hai bài còn nháp chỉ đưa trong bản đồ, chưa đưa vào HTML công khai.
- Khi từng bài mới được duyệt và xuất bản, cập nhật 1–3 bài cũ có ngữ cảnh phù hợp để trỏ lại. Không đặt link đến bài nháp trên trang sống.
- Khối “Tra cứu toàn bộ bài viết” có thể sinh động bằng `WP_Query` chỉ lấy `post_status=publish`, nhóm category, bỏ ID hiện tại. Cần đo HTML và hiệu năng trước khi triển khai lặp ở 150 bài; nếu gây tải/UX kém, báo số đo và xin phương án thư viện phân trang. Không chèn 150 link vào nội dung biên tập.
- Bài viết dùng `Article` hiện có; không tự thêm `FAQPage` hay `reviewedBy`. Canonical self và index chỉ cho bài đã xuất bản; bản nháp không public. Mục lục phải từ H2 thật, ID duy nhất.

## Dữ liệu cần bệnh viện bổ sung trước duyệt

1. Người viết/bộ phận biên tập thật và bác sĩ duyệt từng nhóm, ngày duyệt, hồ sơ công khai tương ứng.
2. Giấy phép và danh mục chuyên môn/kỹ thuật thực tế; xác nhận kỹ thuật khúc xạ nào đang triển khai.
3. Chính sách BHYT của bệnh viện: hợp đồng, đối tượng, phần được thanh toán, hồ sơ/cách tra cứu, người liên hệ xác nhận.
4. Bảng giá phiên bản có ngày hiệu lực, đơn vị một/hai mắt, dịch vụ/phụ phí đã gồm hay chưa.
5. Quy trình tiếp đón, điều phối bác sĩ, phòng kính, cắt kính, thời gian nhận kính, thanh toán, tái khám.
6. Bản đồ/chân dung/ảnh cơ sở được phép dùng và mô tả thay thế chính xác.

Các bản nháp có ký hiệu `[CẦN XÁC MINH: …]` chỉ lưu nội bộ; không được xuất bản nguyên trạng.
