#!/usr/bin/env bash
set -euo pipefail
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9
cd /home/jwhxtzru/public_html
backup=/home/jwhxtzru/backups/fpt-statistics-$(date +%Y%m%d-%H%M%S)
mkdir -p "$backup"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
cp -p /home/jwhxtzru/staging/fpt-school-20261015/statistics.html /home/jwhxtzru/staging/fpt-school-20261015/update-statistics.php "$backup/"
php -l "$backup/update-statistics.php"
php -l wp-content/themes/eyecare-child/footer.php
php -l wp-content/themes/eyecare-child/page-lien-he.php
EYECARE_BACKUP="$backup" wp eval-file "$backup/update-statistics.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
printf 'BACKUP=%s\n' "$backup"
