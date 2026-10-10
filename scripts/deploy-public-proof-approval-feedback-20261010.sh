#!/usr/bin/env bash
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/proof-approval-fix
file=inc/lich-kham-cong-khai.php
lock=/home/jwhxtzru/website-ops-deploy.lock
old_hash=21b6670dd61e0e4e7966c79126630f5f88551310c397b16803ee101cef2918a9
new_hash=ff9c34523a091832de7f0269a86603b3000a587decf9d18eae62c3d3d5914810

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }

test "$(sha256sum "$theme/$file" | cut -d' ' -f1)" = "$old_hash"
test "$(sha256sum "$stage/$file" | cut -d' ' -f1)" = "$new_hash"
php -l "$stage/$file"
php "$stage/tests/public-proof-test.php"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"

backup="/home/jwhxtzru/backups/public-proof-approval-feedback-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc"
cp -p "$theme/$file" "$backup/$file"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
chmod 600 "$backup/database-before.sql"

changed=0
restore() {
  local code=$?
  trap - ERR
  if [[ "$changed" == 1 ]]; then
    cp -p "$backup/$file" "$theme/$file" || true
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap restore ERR

changed=1
cp -p "$stage/$file" "$theme/$file"
test "$(sha256sum "$theme/$file" | cut -d' ' -f1)" = "$new_hash"
php -l "$theme/$file"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

trap - ERR
printf 'BACKUP=%s\n' "$backup"
