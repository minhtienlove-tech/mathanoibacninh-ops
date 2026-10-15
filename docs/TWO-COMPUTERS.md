# Làm việc trên hai máy

Repository: https://github.com/minhtienlove-tech/mathanoibacninh-ops

Nhánh đang làm việc ngày 15/10/2026: `codex/chuyen-muc-bac-si`.
Tên repository/tài khoản trong ảnh hướng dẫn cũ không phải cấu hình của checkout này.

## Máy thứ hai

1. Đăng nhập GitHub Desktop bằng tài khoản có quyền truy cập repository trên.
2. Chọn File → Clone repository → URL và nhập URL repository.
3. Chọn Current branch → `codex/chuyen-muc-bac-si`, rồi Fetch origin → Pull origin nếu có.
4. Mở thư mục vừa clone bằng công cụ làm việc. Đọc `AGENTS.md` trước khi sửa.

Chỉ viết và sửa mã/bản thảo thì không cần SSH production. Nếu cần triển khai từ
máy thứ hai, tạo SSH key riêng và cấp quyền theo [DEPLOYMENT.md](DEPLOYMENT.md).
Không sao chép private key hoặc token giữa hai máy.

## Mỗi lần chuyển máy

Trên máy đang làm: xem Changes, bỏ secret/database/dữ liệu cá nhân khỏi thay đổi,
Commit vào nhánh đang dùng rồi Push origin. Chỉ bàn giao sau khi Push thành công.

Trên máy nhận: chọn đúng repository và cùng nhánh, Fetch origin rồi Pull origin
trước khi sửa. Nếu còn thay đổi chưa commit, lưu và xử lý riêng trước khi Pull;
không Discard hoặc ghi đè để bỏ qua xung đột.

Lệnh tương đương khi checkout sạch và đã ở đúng nhánh:

```powershell
git status --short --branch
git pull --ff-only
# Sửa và kiểm tra; stage riêng những file đã xem xét.
git add -- <duong-dan-file>
git commit -m "Mo ta thay doi"
git push
```

Không sửa cùng một bài trên hai máy cùng lúc; chỉ một máy triển khai production
tại một thời điểm. Mọi triển khai vẫn cần backup, kiểm tra và ghi CHANGELOG theo
`AGENTS.md`. Git đồng bộ mã và tài liệu; database/media/options lấy từ production,
không đưa vào repository.
