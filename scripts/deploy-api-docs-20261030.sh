#!/usr/bin/env bash
# Chạy trên server sau khi scripts/stage-api-docs-20261030.sh đã tải thư mục stage lên.
# Đưa tài liệu API nội bộ lên /api-dat-lich/ và /api-dat-lich.md (noindex). Không đổi database.
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/api-docs-20261030
site=https://mathanoibacninh.com
changed_files=(functions.php inc/api-dat-lich-admin.php)
new_files=(inc/tai-lieu-api.php content/api-dat-lich.md content/sdk/ec-booking.mjs content/sdk/EcBookingClient.php.txt)
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" ) || { echo 'Production differs from the last deployed commit.' >&2; exit 1; }
for file in "${new_files[@]}"; do [[ ! -e "$theme/$file" ]] || { echo "Already exists: $file" >&2; exit 1; }; done
[[ ! -e "$theme/content/sdk" ]] || { echo 'Already exists: content/sdk' >&2; exit 1; }
for file in "${changed_files[@]}" inc/tai-lieu-api.php; do php -l "$stage/$file"; done
for test in "$stage"/tests/*.php; do php "$test" > /dev/null; done
backup="/home/jwhxtzru/backups/api-docs-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc"
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
    rmdir "$theme/content/sdk" 2>/dev/null || true
    ( cd "$root" && wp cache flush --quiet ) || true
    ( cd "$root" && wp litespeed-purge all >/dev/null 2>&1 ) || true
  fi
  echo "API docs deployment failed and was rolled back. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
changed=1
# Cùng quyền với content/khu-vuc.
mkdir -m 775 "$theme/content/sdk"
for file in "${new_files[@]}" inc/api-dat-lich-admin.php; do cp -p "$stage/$file" "$theme/$file"; done
cp -p "$stage/functions.php" "$theme/functions.php"
for file in "${changed_files[@]}" inc/tai-lieu-api.php; do php -l "$theme/$file"; done
( cd "$theme" && sha256sum --check --status "$stage/after.sha256" )
cd "$root"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
stamp=$(date +%s)
curl --fail --silent --show-error --max-time 30 -D "$backup/docs-headers.txt" "$site/api-dat-lich/?qa=$stamp" > "$backup/docs.html"
grep -Eiq '^x-robots-tag: noindex, nofollow' "$backup/docs-headers.txt"
grep -Fq '<meta name="robots" content="noindex,nofollow">' "$backup/docs.html"
grep -Fq '<h1>API đặt lịch khám và form nhúng</h1>' "$backup/docs.html"
curl --fail --silent --show-error --max-time 30 -D "$backup/md-headers.txt" "$site/api-dat-lich.md?qa=$stamp" > "$backup/docs.md"
grep -Eiq '^content-type: text/markdown' "$backup/md-headers.txt"
grep -Eiq '^x-robots-tag: noindex, nofollow' "$backup/md-headers.txt"
cmp -s "$backup/docs.md" "$theme/content/api-dat-lich.md"
curl --fail --silent --show-error --max-time 30 "$site/api-dat-lich/sdk/ec-booking.mjs?qa=$stamp" | grep -Fq 'export class EcBookingClient'
curl --fail --silent --show-error --max-time 30 "$site/api-dat-lich/sdk/EcBookingClient.php.txt?qa=$stamp" | grep -Fq 'class EcBookingClient'
[[ "$(curl --silent --max-time 30 -o /dev/null -w '%{http_code}' "$site/api-dat-lich/sdk/EcBookingClient.php?qa=$stamp")" == 404 ]]
curl --fail --silent --show-error --location --max-time 30 "$site/dat-lich-kham/?qa=$stamp" | grep -Fq 'data-ec-booking-form'
wp eval --user=1 'ob_start(); ec_booking_api_admin_page(); $h = ob_get_clean(); if ( false === strpos( $h, "/api-dat-lich.md" ) ) { fwrite( STDERR, "admin link missing\n" ); exit( 1 ); }'
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'DEPLOYED=internal API docs at /api-dat-lich/ and /api-dat-lich.md (noindex)\n'
