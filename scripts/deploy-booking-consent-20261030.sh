#!/usr/bin/env bash
# Chạy trên server sau khi scripts/stage-booking-consent-20261030.sh đã tải thư mục stage lên.
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-consent-20261030
changed_files=(inc/dat-lich-kham.php inc/lien-he-noi.php)
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" ) || { echo 'Production differs from the last deployed commit.' >&2; exit 1; }
for file in "${changed_files[@]}"; do php -l "$stage/$file"; done
for test in "$stage"/tests/booking-*.php; do php "$test" > /dev/null; done
backup="/home/jwhxtzru/backups/booking-consent-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc"
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
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Consent deployment failed and was rolled back. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
changed=1
for file in "${changed_files[@]}"; do cp -p "$stage/$file" "$theme/$file"; done
for file in "${changed_files[@]}"; do php -l "$theme/$file"; done
( cd "$theme" && sha256sum --check --status "$stage/after.sha256" )
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
curl --fail --silent --show-error --location --max-time 30 "https://mathanoibacninh.com/dat-lich-kham/?qa=$(date +%s)" > "$backup/public-booking.html"
grep -Fq 'name="consent_zalo" value="1"' "$backup/public-booking.html"
grep -Fq 'Zalo OA đến số di động đã nhập (không quảng cáo)' "$backup/public-booking.html"
if grep -Fq 'data-booking-zalo' "$backup/public-booking.html"; then false; fi
[[ "$(grep -c 'name="consent"' "$backup/public-booking.html")" == 1 ]]
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'DEPLOYED=single required consent including Zalo confirmation\n'
