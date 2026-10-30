#!/usr/bin/env bash
# Chạy trên server sau khi scripts/stage-booking-duplicate-notice-20261030.sh đã tải thư mục stage lên.
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-duplicate-notice-20261030
changed_files=(inc/dat-lich-kham.php assets/lien-he-noi.js assets/lien-he-noi.css)
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" ) || { echo 'Production differs from the last deployed commit.' >&2; exit 1; }
php -l "$stage/inc/dat-lich-kham.php"
for test in "$stage"/tests/booking-*.php; do php "$test" > /dev/null; done
backup="/home/jwhxtzru/backups/booking-duplicate-notice-$(date +%Y%m%d-%H%M%S)"
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
    ( cd "$root" && wp cache flush --quiet ) || true
    ( cd "$root" && wp litespeed-purge all >/dev/null 2>&1 ) || true
  fi
  echo "Duplicate notice deployment failed and was rolled back. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
changed=1
for file in "${changed_files[@]}"; do cp -p "$stage/$file" "$theme/$file"; done
php -l "$theme/inc/dat-lich-kham.php"
( cd "$theme" && sha256sum --check --status "$stage/after.sha256" )
cd "$root"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
stamp=$(date +%s)
curl --fail --silent --show-error --location --max-time 30 "https://mathanoibacninh.com/dat-lich-kham/?qa=$stamp" > "$backup/public-booking.html"
grep -Fq 'data-ec-booking-form' "$backup/public-booking.html"
css=$(grep -o 'https://mathanoibacninh.com/wp-content/themes/eyecare-child/assets/lien-he-noi.css?ver=[0-9]*' "$backup/public-booking.html" | head -1)
js=$(grep -o 'https://mathanoibacninh.com/wp-content/themes/eyecare-child/assets/lien-he-noi.js?ver=[0-9]*' "$backup/public-booking.html" | head -1)
curl --fail --silent --show-error --max-time 30 "$css" | grep -Fq 'data-state="warning"'
curl --fail --silent --show-error --max-time 30 "$js" | grep -Fq "message(error.message, 'warning')"
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'DEPLOYED=duplicate booking notice shown as warning\n'
