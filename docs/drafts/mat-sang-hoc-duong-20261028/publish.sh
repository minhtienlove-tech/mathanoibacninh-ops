#!/usr/bin/env bash
set -euo pipefail
umask 077
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active'; exit 1; }
stage=/home/jwhxtzru/staging/mat-sang-hoc-duong-20261028
cd "$stage"
sha256sum --check --status expected.sha256
backup=/home/jwhxtzru/backups/mat-sang-hoc-duong-20261028-$(date +%H%M%S)
mkdir -p "$backup"
cp -a "$stage/." "$backup/"
cd /home/jwhxtzru/public_html
wp db export "$backup/database.sql" --quiet
test -s "$backup/database.sql"
tar -czf "$backup/child-theme-before.tar.gz" wp-content/themes/eyecare-child
php -l "$backup/publish.php"
php -l wp-content/themes/eyecare-child/footer.php
php -l wp-content/themes/eyecare-child/page-lien-he.php
export EYECARE_BACKUP="$backup"
wp eval-file "$backup/publish.php"
wp cache flush --quiet
printf 'BACKUP=%s\n' "$backup"
