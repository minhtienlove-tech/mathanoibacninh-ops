# Deployment guide

## SSH target

```text
Host mathanoibacninh
    HostName 45.252.249.45
    User jwhxtzru
    Port 2210
    IdentityFile ~/.ssh/mathanoibacninh_ed25519
    IdentitiesOnly yes
```

Trên Windows, file cấu hình nằm tại `C:\Users\<user>\.ssh\config`.
Mỗi máy phải tạo key riêng và authorize file `.pub` trong AZDIGI/cPanel.
Không copy private key giữa các máy.

## Kiểm tra kết nối

```powershell
ssh mathanoibacninh "cd ~/public_html && wp core version && pwd"
```

Remote WordPress path: `/home/jwhxtzru/public_html`.

## Backup trước production

```bash
cd ~/public_html
stamp=$(date +%Y%m%d-%H%M%S)
backup=~/backups/mathanoibacninh-$stamp
mkdir -p "$backup"
cp -p wp-content/themes/eyecare-child/footer.php "$backup/"
cp -p wp-content/themes/eyecare-child/style.css "$backup/"
wp db export "$backup/database.sql" --quiet
```

## Đồng bộ child theme

Mọi thay đổi phải nằm trong `theme/eyecare-child` và được commit trước khi
đưa lên production. Ưu tiên upload đúng file đã thay đổi bằng `scp`; không dùng
`rm -rf`, không ghi đè database nếu chưa có backup và xác nhận.

```powershell
scp theme\eyecare-child\footer.php mathanoibacninh:~/public_html/wp-content/themes/eyecare-child/footer.php
scp theme\eyecare-child\style.css mathanoibacninh:~/public_html/wp-content/themes/eyecare-child/style.css
ssh mathanoibacninh "cd ~/public_html && php -l wp-content/themes/eyecare-child/footer.php && wp cache flush"
.\scripts\verify-live.ps1 -CheckSsh
```

## Rollback

Khôi phục file từ thư mục backup tương ứng bằng `cp -p`, sau đó chạy smoke test.
Không xóa backup cũ cho tới khi người phụ trách xác nhận website ổn định.
