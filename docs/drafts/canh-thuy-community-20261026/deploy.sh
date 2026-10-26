#!/usr/bin/env bash
set -euo pipefail
umask 077
phase=${1:?Choose draft or publish}
[[ "$phase" == draft || "$phase" == publish ]]
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active'; exit 1; }
stage=/home/jwhxtzru/staging/canh-thuy-community-20261026
backup=/home/jwhxtzru/backups/canh-thuy-community-20261026
cd "$stage"
sha256sum --check --status expected.sha256
if [[ ! -d "$backup" ]]; then
  mkdir -p "$backup"
  cp -a "$stage/." "$backup/"
fi
cd "$backup"
sha256sum --check --status expected.sha256
cd /home/jwhxtzru/public_html
if [[ ! -s "$backup/database.sql" ]]; then
  wp db export "$backup/database.sql" --quiet
  tar -czf "$backup/child-theme-before.tar.gz" wp-content/themes/eyecare-child
fi
test -s "$backup/database.sql"
php -l "$backup/publish.php"
php -l wp-content/themes/eyecare-child/footer.php
php -l wp-content/themes/eyecare-child/page-lien-he.php
if [[ "$phase" == publish ]]; then
  test -s "$backup/preview-reviewed.json"
  wp db export "$backup/database-before-publish.sql" --quiet
fi
export EYECARE_BACKUP="$backup"
export EYECARE_MODE="$phase"
wp eval-file "$backup/publish.php"
if [[ "$phase" == publish ]]; then
  wp cache flush --quiet
  wp litespeed-purge all >/dev/null 2>&1 || true
fi
printf 'BACKUP=%s\n' "$backup"
