#!/usr/bin/env bash
set -euo pipefail

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/gphd-design-20261007
lock=/home/jwhxtzru/website-ops-deploy.lock
exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active'; exit 1; }

template=page-giay-phep-hoat-dong.php
css=assets/page-layouts.css
expected_template=327ea0e80994428e690129e8e003a94cf00d834625cf928a1e79f857bc1605c2
expected_css=c1ad86a043d7f0e2b36b33f56a985cccd1338a40efa956e467bcd4934477adcd

# Template đang lưu CRLF trên production; so nội dung LF để tránh ghi đè thay đổi thực.
current_template=$(tr -d '\r' < "$theme/$template" | sha256sum | cut -d' ' -f1)
current_css=$(sha256sum "$theme/$css" | cut -d' ' -f1)
[[ "$current_template" == "$expected_template" ]] || { echo 'Production template diverged'; exit 1; }
[[ "$current_css" == "$expected_css" ]] || { echo 'Production CSS diverged'; exit 1; }

test -s "$stage/$template"
test -s "$stage/page-layouts.css"
php -l "$stage/$template"

backup="/home/jwhxtzru/backups/gphd-design-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup"
cp -p "$theme/$template" "$backup/$template"
cp -p "$theme/$css" "$backup/page-layouts.css"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"

cp "$stage/$template" "$theme/$template"
cp "$stage/page-layouts.css" "$theme/$css"
php -l "$theme/$template"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=cp -p %s/%s %s/%s; cp -p %s/page-layouts.css %s/%s; cd %s; wp cache flush; wp litespeed-purge all\n' "$backup" "$template" "$theme" "$template" "$backup" "$theme" "$css" "$root"
