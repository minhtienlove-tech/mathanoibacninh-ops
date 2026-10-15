#!/usr/bin/env bash
set -euo pipefail
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment active'; exit 1; }
cd /home/jwhxtzru/public_html
stage=/home/jwhxtzru/staging/doctor-canh-20261013
backup=/home/jwhxtzru/backups/doctor-canh-20261013-$(date +%Y%m%d-%H%M%S)
mkdir -p "$backup"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
cp -p "$stage/content-template.html" "$stage/publish.php" "$backup/"
tar -czf "$backup/child-theme-before.tar.gz" wp-content/themes/eyecare-child
php -l "$stage/publish.php"
php -l wp-content/themes/eyecare-child/footer.php
php -l wp-content/themes/eyecare-child/page-lien-he.php
wp eval-file "$stage/publish.php"
cp -p "$stage/new-post-id.txt" "$backup/"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
printf 'BACKUP=%s\n' "$backup"
