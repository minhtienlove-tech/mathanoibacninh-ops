#!/usr/bin/env bash
set -euo pipefail
umask 077
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active'; exit 1; }
backup=/home/jwhxtzru/backups/dtnt-toc-$(date +%Y%m%d-%H%M%S)
mkdir -p "$backup"
cp /home/jwhxtzru/staging/dtnt-school-20261015/refine-toc.php "$backup/"
cd /home/jwhxtzru/public_html
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
php -l "$backup/refine-toc.php"
export EYECARE_BACKUP="$backup"
wp eval-file "$backup/refine-toc.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
printf 'BACKUP=%s\n' "$backup"
