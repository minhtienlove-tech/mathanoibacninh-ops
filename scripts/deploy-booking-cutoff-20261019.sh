#!/usr/bin/env bash
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-cutoff-20261019
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" )
php -l "$stage/inc/dat-lich-kham.php"
php -l "$stage/inc/lien-he-noi.php"
php -l "$stage/booking-cutoff-20261019.php"
php "$stage/tests/booking-validation-test.php"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
backup="/home/jwhxtzru/backups/booking-cutoff-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc"
cp -p "$theme/inc/dat-lich-kham.php" "$backup/inc/"
cp -p "$theme/inc/lien-he-noi.php" "$backup/inc/"
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
    cp -p "$backup/inc/dat-lich-kham.php" "$theme/inc/"
    cp -p "$backup/inc/lien-he-noi.php" "$theme/inc/"
    if [[ -f "$backup/page57-before.html" ]]; then EYECARE_BOOKING_CUTOFF_MODE=rollback EYECARE_BOOKING_CUTOFF_BACKUP="$backup" wp eval-file "$stage/booking-cutoff-20261019.php"; fi
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Cutoff deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
changed=1
cp -p "$stage/inc/dat-lich-kham.php" "$theme/inc/"
cp -p "$stage/inc/lien-he-noi.php" "$theme/inc/"
EYECARE_BOOKING_CUTOFF_MODE=apply EYECARE_BOOKING_CUTOFF_BACKUP="$backup" wp eval-file "$stage/booking-cutoff-20261019.php"
php -l "$theme/inc/dat-lich-kham.php"
php -l "$theme/inc/lien-he-noi.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
curl --fail --silent --show-error --location --max-time 30 "https://mathanoibacninh.com/dat-lich-kham/?cutoff_qa=$(date +%s)" > "$backup/public-booking.html"
grep -Fq 'Nhận đặt lịch đến 16:00' "$backup/public-booking.html"
grep -Fq 'mốc hẹn đến 16:00' "$backup/public-booking.html"
if grep -Fq 'Nhận đặt lịch đến 17:00' "$backup/public-booking.html"; then false; fi
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'DEPLOYED=booking cutoff16:00; code and page57 only\n'
