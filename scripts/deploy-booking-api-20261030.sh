#!/usr/bin/env bash
# Chạy trên server sau khi scripts/stage-booking-api-20261030.sh đã tải thư mục stage lên.
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-api-20261030
changed_files=(functions.php inc/dat-lich-kham.php inc/lien-he-noi.php inc/sheets-dat-lich.php inc/zalo-dat-lich.php assets/lien-he-noi.css assets/lien-he-noi.js)
new_files=(inc/api-dat-lich.php inc/api-dat-lich-admin.php inc/dat-lich-nhung.php assets/dat-lich-nhung.js)
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
# Production phải đúng bản Git trước khi sửa; lệch thì dừng để đối chiếu, không ghi đè.
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" ) || { echo 'Production differs from Git HEAD before this change.' >&2; exit 1; }
for file in "${new_files[@]}"; do [[ ! -e "$theme/$file" ]] || { echo "Already exists: $file" >&2; exit 1; }; done
for file in "${changed_files[@]}" "${new_files[@]}"; do if [[ "$file" == *.php ]]; then php -l "$stage/$file"; fi; done
for test in "$stage"/tests/booking-*.php; do php "$test" > /dev/null; done
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
backup="/home/jwhxtzru/backups/booking-api-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc" "$backup/assets"
for file in "${changed_files[@]}"; do cp -p "$theme/$file" "$backup/$file"; done
cp -a "$stage/." "$backup/staged/"
cd "$root"
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
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Booking API deployment failed and was rolled back. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
changed=1
# File mới trước, functions.php cuối cùng để không có lúc require trỏ tới file chưa có.
for file in "${new_files[@]}" "${changed_files[@]}"; do if [[ "$file" != functions.php ]]; then cp -p "$stage/$file" "$theme/$file"; fi; done
cp -p "$stage/functions.php" "$theme/functions.php"
for file in "${changed_files[@]}" "${new_files[@]}"; do if [[ "$file" == *.php ]]; then php -l "$theme/$file"; fi; done
( cd "$theme" && sha256sum --check --status "$stage/after.sha256" )
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
stamp=$(date +%s)
curl --fail --silent --show-error --max-time 30 "https://mathanoibacninh.com/wp-json/ec-booking/v1/slots?qa=$stamp" > "$backup/slots.json"
grep -Fq '"ok":true' "$backup/slots.json"
curl --fail --silent --show-error --location --max-time 30 "https://mathanoibacninh.com/dat-lich-kham/?qa=$stamp" > "$backup/public-booking.html"
grep -Fq 'data-ec-booking-form' "$backup/public-booking.html"
curl --silent --max-time 30 -D "$backup/embed-headers.txt" -o /dev/null "https://mathanoibacninh.com/?ec_booking_embed=pk_00000000000000000000000000000000&qa=$stamp"
grep -Eq '^HTTP/[0-9.]+ 404' "$backup/embed-headers.txt"
grep -Fiq "frame-ancestors 'none'" "$backup/embed-headers.txt"
unsigned=$(curl --silent --max-time 30 -o /dev/null -w '%{http_code}' -X POST -H 'Content-Type: application/json' -d '{}' "https://mathanoibacninh.com/wp-json/ec-booking/v1/appointments")
[[ "$unsigned" == 401 ]]
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'DEPLOYED=booking form, anti-spam, partner API and embed widget\n'
