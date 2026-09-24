# Khôi phục bản trước khi xuất bản khu vực khám mắt

Gói xuất bản `6d1dc5d` được triển khai ngày 24/09/2026. Bản sao trước thay đổi ở `/home/jwhxtzru/backups/khu-vuc-20260924-193032/`, gồm `theme-before.tar.gz`, `database.sql`, `drafts.log` (99 ID WordPress), release và log thao tác. Giữ nguyên thư mục này.

Trước khi khôi phục, kiểm tra liệu người quản trị đã chỉnh tay bất kỳ trang nào trong 99 trang hay các tệp theme liên quan sau thời điểm triển khai. Nếu có, sao lưu phần thay đổi mới trước; các lệnh dưới đây đưa trạng thái xuất bản và năm tệp runtime về mốc trước triển khai.

```bash
ssh mathanoibacninh
cd /home/jwhxtzru/public_html
backup=/home/jwhxtzru/backups/khu-vuc-20260924-193032
theme=/home/jwhxtzru/public_html/wp-content/themes/eyecare-child

# Chỉ 99 ID do đợt này tạo; chuyển về nháp, không xóa bài hay dữ liệu.
awk '/^Draft / {print $2}' "$backup/drafts.log" | while read -r id; do
  wp post update "$id" --post_status=draft --quiet
done

# Khôi phục năm tệp runtime cũ từ bản sao toàn theme.
for file in functions.php page.php footer.php inc/dau-trang.php inc/schema-y-te.php; do
  tar -xOzf "$backup/theme-before.tar.gz" "eyecare-child/$file" > "$theme/$file.rollback"
  mv -f "$theme/$file.rollback" "$theme/$file"
  php -l "$theme/$file"
done

wp cache flush
wp litespeed-purge all
```

Các tệp HTML/CSS/module được thêm trong release vẫn nằm trên hosting nhưng không còn được theme cũ nạp; không cần xóa chúng để khôi phục hành vi trước đó. Sau khi khôi phục, chạy `./scripts/verify-live.ps1 -CheckSsh` từ máy quản trị và kiểm tra `/`, `/khu-vuc/`, hai trang cha, một trang xã/phường. Bản sao database chỉ dùng để phục hồi sự cố nghiêm trọng sau khi xem xét dữ liệu mới phát sinh, không nhập đè tự động.
