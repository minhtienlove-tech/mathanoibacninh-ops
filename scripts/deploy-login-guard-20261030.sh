#!/usr/bin/env bash
# Chạy trên server sau khi scripts/stage-login-guard-20261030.sh đã tải thư mục stage lên.
# Chống dò mật khẩu bật ngay; Turnstile trang đăng nhập để tắt; form đặt lịch giữ nguyên trạng thái.
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/login-guard-20261030
site=https://mathanoibacninh.com
changed_files=(functions.php inc/chong-bot-dat-lich.php inc/lien-he-noi.php assets/lien-he-noi.js)
new_files=(inc/chong-bot-dang-nhap.php)
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" ) || { echo 'Production differs from the last deployed commit.' >&2; exit 1; }
for file in "${new_files[@]}"; do [[ ! -e "$theme/$file" ]] || { echo "Already exists: $file" >&2; exit 1; }; done
for file in "${changed_files[@]}" "${new_files[@]}"; do if [[ "$file" == *.php ]]; then php -l "$stage/$file"; fi; done
for test in "$stage"/tests/*.php; do php "$test" > /dev/null; done
cd "$root"
booking_before=$(wp eval 'echo ec_turnstile_active() ? "on" : "off";')
backup="/home/jwhxtzru/backups/login-guard-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc" "$backup/assets"
for file in "${changed_files[@]}"; do cp -p "$theme/$file" "$backup/$file"; done
cp -a "$stage/." "$backup/staged/"
wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]]
chmod 600 "$backup/database-before.sql"
changed=0
on_failure() {
  local code=$?
  trap - ERR
  if [[ "$changed" == 1 ]]; then
    for file in "${changed_files[@]}"; do cp -p "$backup/$file" "$theme/$file"; done
    for file in "${new_files[@]}"; do rm -f "$theme/$file"; done
    ( cd "$root" && wp cache flush --quiet ) || true
    ( cd "$root" && wp litespeed-purge all >/dev/null 2>&1 ) || true
  fi
  echo "Login guard deployment failed and was rolled back. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
changed=1
# Module mới trước; functions.php nạp nó nên chép cuối.
for file in "${new_files[@]}" "${changed_files[@]}"; do if [[ "$file" != functions.php ]]; then cp -p "$stage/$file" "$theme/$file"; fi; done
cp -p "$stage/functions.php" "$theme/functions.php"
for file in "${changed_files[@]}" "${new_files[@]}"; do if [[ "$file" == *.php ]]; then php -l "$theme/$file"; fi; done
( cd "$theme" && sha256sum --check --status "$stage/after.sha256" )
[[ "$(wp eval 'echo ec_turnstile_active() ? "on" : "off";')" == "$booking_before" ]]
[[ "$(wp eval 'echo ec_turnstile_login_active() ? "on" : "off";')" == off ]]
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
stamp=$(date +%s)
curl --fail --silent --show-error --max-time 30 "$site/wp-login.php?qa=$stamp" > "$backup/login.html"
grep -Fq 'id="loginform"' "$backup/login.html"
if grep -Fq 'cf-turnstile' "$backup/login.html"; then false; fi
# Một lần đăng nhập sai với tên giả: thông báo chung, không nêu tên; rồi xóa bộ đếm vừa tạo.
curl --silent --max-time 30 -d "log=ec-deploy-probe-$stamp&pwd=wrong-$stamp" "$site/wp-login.php" > "$backup/login-fail.html"
grep -Fq 'Tên đăng nhập hoặc mật khẩu không đúng' "$backup/login-fail.html"
if grep -Fq "<strong>ec-deploy-probe-$stamp" "$backup/login-fail.html"; then false; fi
[[ "$(wp eval 'echo ec_login_guard_unlock_all();')" -ge 1 ]]
curl --fail --silent --show-error --location --max-time 30 "$site/dat-lich-kham/?qa=$stamp" > "$backup/public-booking.html"
grep -Fq 'data-ec-booking-form' "$backup/public-booking.html"
if [[ "$booking_before" == on ]]; then grep -Fq 'data-appearance="interaction-only"' "$backup/public-booking.html"; fi
ajax=$(curl --silent --max-time 30 -o "$backup/ajax-nonce.json" -w '%{http_code}' -X POST -d 'action=ec_booking_submit&nonce=invalid' "$site/wp-admin/admin-ajax.php")
[[ "$ajax" == 403 ]]
wp eval --user=1 'ob_start(); ec_turnstile_admin_page(); $h = ob_get_clean(); foreach ( array( "Chống dò mật khẩu", "Mở khóa tất cả", "name=\"login\"", "name=\"login_appearance\"", "name=\"appearance\"" ) as $s ) { if ( false === strpos( $h, $s ) ) { fwrite( STDERR, "missing $s\n" ); exit( 1 ); } }'
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'BOOKING_TURNSTILE=%s\n' "$booking_before"
printf 'DEPLOYED=login brute-force limit on, login Turnstile off, show/hide switches\n'
