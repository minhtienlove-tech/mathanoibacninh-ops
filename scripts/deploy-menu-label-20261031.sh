#!/usr/bin/env bash
# Chạy trên server sau khi scripts/stage-menu-label-20261031.sh đã tải thư mục stage lên.
# Sửa tên menu "Lịch hẹn" thành "Đặt lịch khám" trong tài liệu API và ghi chú code. Không đổi database.
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/menu-label-20261031
site=https://mathanoibacninh.com
changed_files=(content/api-dat-lich.md inc/chong-bot-dat-lich.php inc/chong-bot-dang-nhap.php)
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" ) || { echo 'Production differs from the last deployed commit.' >&2; exit 1; }
for file in inc/chong-bot-dat-lich.php inc/chong-bot-dang-nhap.php; do php -l "$stage/$file"; done
for test in "$stage"/tests/*.php; do php "$test" > /dev/null; done
backup="/home/jwhxtzru/backups/menu-label-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc" "$backup/content"
for file in "${changed_files[@]}"; do cp -p "$theme/$file" "$backup/$file"; done
cp -a "$stage/." "$backup/staged/"
( cd "$root" && wp db export "$backup/database-before.sql" --quiet )
[[ -s "$backup/database-before.sql" ]]
chmod 600 "$backup/database-before.sql"
changed=0
on_failure() {
  local code=$?
  trap - ERR
  if [[ "$changed" == 1 ]]; then
    for file in "${changed_files[@]}"; do cp -p "$backup/$file" "$theme/$file"; done
    ( cd "$root" && wp cache flush --quiet ) || true
    ( cd "$root" && wp litespeed-purge all >/dev/null 2>&1 ) || true
  fi
  echo "Menu label deployment failed and was rolled back. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
changed=1
for file in "${changed_files[@]}"; do cp -p "$stage/$file" "$theme/$file"; done
for file in inc/chong-bot-dat-lich.php inc/chong-bot-dang-nhap.php; do php -l "$theme/$file"; done
( cd "$theme" && sha256sum --check --status "$stage/after.sha256" )
cd "$root"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
stamp=$(date +%s)
# Ghi ra file rồi mới grep (grep -q thoát sớm làm curl lỗi dưới pipefail).
curl --fail --silent --show-error --max-time 30 "$site/api-dat-lich.md?qa=$stamp" > "$backup/docs.md"
cmp -s "$backup/docs.md" "$theme/content/api-dat-lich.md"
grep -Fq 'WordPress → Đặt lịch khám → API và form nhúng' "$backup/docs.md"
if grep -Fq 'Lịch hẹn →' "$backup/docs.md"; then false; fi
curl --fail --silent --show-error --max-time 30 "$site/api-dat-lich/?qa=$stamp" > "$backup/docs.html"
grep -Fq 'Đặt lịch khám → API và form nhúng' "$backup/docs.html"
curl --fail --silent --show-error --max-time 30 "$site/wp-login.php?qa=$stamp" > "$backup/login.html"
grep -Fq 'id="loginform"' "$backup/login.html"
curl --fail --silent --show-error --location --max-time 30 "$site/dat-lich-kham/?qa=$stamp" > "$backup/public-booking.html"
grep -Fq 'data-ec-booking-form' "$backup/public-booking.html"
wp eval --user=1 'ob_start(); ec_turnstile_admin_page(); $h = ob_get_clean(); if ( false === strpos( $h, "Chống dò mật khẩu" ) ) { fwrite( STDERR, "admin page incomplete\n" ); exit( 1 ); }'
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'DEPLOYED=menu label Đặt lịch khám in API docs and code comments\n'
