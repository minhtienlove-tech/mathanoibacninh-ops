#!/usr/bin/env bash
set -euo pipefail

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/home-news-card-20261008/home-refresh.css
target="$theme/assets/home-refresh.css"
expected_old=b0fd7cdc36c257c12a626c7c622f28cbbaa00c0347f15a0698954ffbc14f9645
expected_new=34182a6e66426c89c2f174d29257f5dbfc731b7fb9721e358ba2b809bd9b7854

exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active' >&2; exit 1; }

check_sha() {
  local path=$1 expected=$2 actual
  test -s "$path"
  actual=$(sha256sum "$path" | cut -d' ' -f1)
  [[ "$actual" == "$expected" ]] || { echo "Checksum mismatch: $path" >&2; exit 1; }
}

check_sha "$target" "$expected_old"
check_sha "$stage" "$expected_new"

backup="/home/jwhxtzru/backups/home-news-card-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup/assets"
cp -p "$target" "$backup/assets/home-refresh.css"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
chmod 600 "$backup/database-before.sql"

applied=0
rollback_on_error() {
  status=$?
  trap - ERR
  if [[ "$applied" == 1 ]]; then
    cp -p "$backup/assets/home-refresh.css" "$target" || true
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
    echo "Deployment failed; restored CSS from $backup" >&2
  fi
  exit "$status"
}
trap rollback_on_error ERR

applied=1
cp -p "$stage" "$target"
check_sha "$target" "$expected_new"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=restore assets/home-refresh.css from this backup, then purge WordPress/LiteSpeed caches\n'
