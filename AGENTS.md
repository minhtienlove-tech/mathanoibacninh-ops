# Bệnh viện Mắt Hà Nội - Bắc Ninh

## Phạm vi

Repository này chứa child theme WordPress đang chạy trên `mathanoibacninh.com`.
Mã nguồn production nằm trong `theme/eyecare-child`.

## Production

- Domain: `https://mathanoibacninh.com`
- SSH alias: `mathanoibacninh`
- Remote WordPress path: `/home/jwhxtzru/public_html`
- Remote child theme: `/home/jwhxtzru/public_html/wp-content/themes/eyecare-child`
- PHP: 8.2.x; WP-CLI: `wp`

## Quy tắc bắt buộc

1. Chỉ sửa child theme; không sửa WordPress core, plugin hoặc theme bên thứ ba.
2. Không ghi secret, private key, database password hoặc `wp-config.php` vào repository.
3. Trước production, backup file liên quan và database vào `~/backups/` trên server.
4. Sau thay đổi chạy `php -l`, kiểm tra HTML/CSS và smoke test URL chính.
5. Không xóa file/database hoặc search-replace hàng loạt nếu chưa được xác nhận.
6. Luôn `git pull` trước khi sửa và ghi thay đổi vào `docs/CHANGELOG.md`.
7. Không triển khai đồng thời từ hai máy.

## Nguồn sự thật

- Mã nguồn: Git repository này.
- Database/media/options: backup production/WP-CLI, không đưa vào Git.
- Trạng thái deploy: `docs/CHANGELOG.md`.
- SSH/deploy: `docs/DEPLOYMENT.md`.

## Kiểm tra tối thiểu

```powershell
php -l theme\eyecare-child\footer.php
php -l theme\eyecare-child\page-lien-he.php
.\scripts\verify-live.ps1 -CheckSsh
```
