#!/usr/bin/env bash
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-public-proof-mobile-20261010
target=assets/lich-kham-cong-khai.css

exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }

[[ -s "$stage/$target" && -s "$stage/before.sha256" && -s "$stage/expected.sha256" ]] || exit 1
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" )
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"

backup="/home/jwhxtzru/backups/booking-proof-mobile-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/assets"
cp -p "$theme/$target" "$backup/$target"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]]
chmod 600 "$backup/database-before.sql"

restore() {
  local code=$?
  trap - ERR
  cp -p "$backup/$target" "$theme/$target" || true
  wp cache flush --quiet || true
  wp litespeed-purge all >/dev/null 2>&1 || true
  echo "CSS deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap restore ERR
cp -p "$stage/$target" "$theme/$target"
( cd "$theme" && sha256sum --check --status "$stage/expected.sha256" )
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
curl --fail --silent --show-error --location --max-time 30 \
  "https://mathanoibacninh.com/wp-content/themes/eyecare-child/$target?qa=$(date +%s)" > "$backup/public-css.txt"
grep -Fq 'top: calc(126px + env(safe-area-inset-top))' "$backup/public-css.txt"
trap - ERR
printf 'BACKUP=%s\n' "$backup"
