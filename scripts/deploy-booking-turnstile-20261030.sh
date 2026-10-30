#!/usr/bin/env bash
# Chạy trên server sau khi scripts/stage-booking-turnstile-20261030.sh đã tải thư mục stage lên.
# Triển khai mã Turnstile ở trạng thái tắt: form và trang chính sách không đổi cho tới khi nhập khóa.
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-turnstile-20261030
changed_files=(inc/dat-lich-kham.php inc/lien-he-noi.php assets/lien-he-noi.js page-quyen-rieng-tu.php)
new_files=(inc/chong-bot-dat-lich.php)
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" ) || { echo 'Production differs from the reviewed hashes.' >&2; exit 1; }
for file in "${new_files[@]}"; do [[ ! -e "$theme/$file" ]] || { echo "Already exists: $file" >&2; exit 1; }; done
for file in "${changed_files[@]}" "${new_files[@]}"; do if [[ "$file" == *.php ]]; then php -l "$stage/$file"; fi; done
for test in "$stage"/tests/booking-*.php; do php "$test" > /dev/null; done
backup="/home/jwhxtzru/backups/booking-turnstile-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc" "$backup/assets"
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
    for file in "${new_files[@]}"; do rm -f "$theme/$file"; done
    ( cd "$root" && wp cache flush --quiet ) || true
    ( cd "$root" && wp litespeed-purge all >/dev/null 2>&1 ) || true
  fi
  echo "Turnstile deployment failed and was rolled back. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
changed=1
# Module mới trước; lien-he-noi.php nạp nó nên chép sau.
for file in "${new_files[@]}" "${changed_files[@]}"; do cp -p "$stage/$file" "$theme/$file"; done
for file in "${changed_files[@]}" "${new_files[@]}"; do if [[ "$file" == *.php ]]; then php -l "$theme/$file"; fi; done
( cd "$theme" && sha256sum --check --status "$stage/after.sha256" )
cd "$root"
[[ "$(wp eval 'echo ec_turnstile_active() ? "on" : "off";')" == off ]]
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
stamp=$(date +%s)
curl --fail --silent --show-error --location --max-time 30 "https://mathanoibacninh.com/dat-lich-kham/?qa=$stamp" > "$backup/public-booking.html"
grep -Fq 'data-ec-booking-form' "$backup/public-booking.html"
if grep -Fq 'data-booking-captcha' "$backup/public-booking.html"; then false; fi
js=$(grep -o 'https://mathanoibacninh.com/wp-content/themes/eyecare-child/assets/lien-he-noi.js?ver=[0-9]*' "$backup/public-booking.html" | head -1)
curl --fail --silent --show-error --max-time 30 "$js" | grep -Fq 'function loadTurnstile'
# Trang chính sách nằm dưới /chinh-sach/; lấy URL từ WordPress thay vì đoán.
privacy_url=$(wp eval 'echo get_privacy_policy_url();')
[[ "$privacy_url" == https://mathanoibacninh.com/* ]]
curl --fail --silent --show-error --location --max-time 30 "${privacy_url}?qa=$stamp" > "$backup/privacy.html"
grep -Fq 'Cookie, nhật ký truy cập và dịch vụ bên ngoài' "$backup/privacy.html"
if grep -Fq 'Cloudflare Turnstile' "$backup/privacy.html"; then false; fi
# Nonce sai: chứng minh handler gửi form chạy không lỗi PHP, không tạo lịch.
ajax=$(curl --silent --max-time 30 -o "$backup/ajax-nonce.json" -w '%{http_code}' -X POST -d 'action=ec_booking_submit&nonce=invalid' "https://mathanoibacninh.com/wp-admin/admin-ajax.php")
[[ "$ajax" == 403 ]]
grep -Fq '"code":"nonce"' "$backup/ajax-nonce.json"
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'DEPLOYED=Turnstile code, switched off\n'
