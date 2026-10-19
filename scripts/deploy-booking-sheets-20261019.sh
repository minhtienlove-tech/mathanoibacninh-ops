#!/usr/bin/env bash
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-sheets-20261019
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" )
for file in functions.php inc/dat-lich-kham.php inc/gmail-dat-lich.php inc/zalo-dat-lich.php inc/lien-he-noi.php inc/sheets-dat-lich.php; do php -l "$stage/$file"; done
php "$stage/tests/booking-sheets-test.php"
php "$stage/tests/booking-gmail-test.php"
php "$stage/tests/booking-admin-test.php"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
backup="/home/jwhxtzru/backups/booking-sheets-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc"
for file in functions.php inc/dat-lich-kham.php inc/gmail-dat-lich.php inc/zalo-dat-lich.php inc/lien-he-noi.php; do cp -p "$theme/$file" "$backup/$file"; done
if [[ -f "$theme/inc/sheets-dat-lich.php" ]]; then cp -p "$theme/inc/sheets-dat-lich.php" "$backup/inc/"; fi
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
    for file in functions.php inc/dat-lich-kham.php inc/gmail-dat-lich.php inc/zalo-dat-lich.php inc/lien-he-noi.php; do cp -p "$backup/$file" "$theme/$file"; done
    if [[ -f "$backup/inc/sheets-dat-lich.php" ]]; then cp -p "$backup/inc/sheets-dat-lich.php" "$theme/inc/"; fi
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Sheet bridge code deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
changed=1
for file in inc/sheets-dat-lich.php inc/dat-lich-kham.php inc/gmail-dat-lich.php inc/zalo-dat-lich.php inc/lien-he-noi.php functions.php; do cp -p "$stage/$file" "$theme/$file"; done
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
wp eval 'if (!function_exists("ec_sheets_enabled") || ec_sheets_enabled()) { throw new Exception("Expected bridge code installed but not activated before Google authorization."); } echo "Bridge installed; awaiting Google authorization and signed probe.\n";'
curl --fail --silent --show-error --location --max-time 30 "https://mathanoibacninh.com/dat-lich-kham/?sheet_qa=$(date +%s)" > "$backup/public-booking.html"
grep -Fq 'Nhận đặt lịch đến 16:00' "$backup/public-booking.html"
grep -Fq 'Google Sheet riêng tư' "$backup/public-booking.html"
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'DEPLOYED=Sheet bridge code only; Google integration remains disabled pending authorization and probe\n'
