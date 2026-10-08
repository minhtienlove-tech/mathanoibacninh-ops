#!/usr/bin/env bash
set -euo pipefail

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/service-news-card-20261008/style.css
target="$theme/style.css"
expected_old=ce85566abc373f7eec476f85c8b405bf23aa324d3ce095b13af09a185707e8bf
expected_new=0651024df9e1097ef70f8b93d9946d8d03fc07039b4e65176c80d9fc69b1424f

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

backup="/home/jwhxtzru/backups/service-news-card-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup"
cp -p "$target" "$backup/style.css"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
chmod 600 "$backup/database-before.sql"

applied=0
rollback_on_error() {
  status=$?
  trap - ERR
  if [[ "$applied" == 1 ]]; then
    cp -p "$backup/style.css" "$target" || true
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
printf 'ROLLBACK=restore style.css from this backup, then purge WordPress/LiteSpeed caches\n'
