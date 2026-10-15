#!/usr/bin/env bash
set -euo pipefail
umask 077
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active'; exit 1; }
stage=/home/jwhxtzru/staging/fpt-school-20261015
cd "$stage"
sha256sum --check --status expected.sha256
backup=/home/jwhxtzru/backups/fpt-school-$(date +%Y%m%d-%H%M%S)
mkdir -p "$backup"
cp -a "$stage/." "$backup/"
cd /home/jwhxtzru/public_html
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
tar -czf "$backup/child-theme-before.tar.gz" wp-content/themes/eyecare-child
php -l "$backup/publish.php"
php -l wp-content/themes/eyecare-child/footer.php
php -l wp-content/themes/eyecare-child/page-lien-he.php
export EYECARE_BACKUP="$backup"
on_failure() {
 local status=$?
 trap - ERR
 if [[ -s "$backup/post-id" ]]; then wp post update "$(cat "$backup/post-id")" --post_status=draft --quiet || true; fi
 if [[ -s "$backup/community-intro-before.html" ]]; then wp post update 1686 "$backup/community-intro-before.html" --quiet || true; fi
 wp cache flush --quiet || true
 wp litespeed-purge all >/dev/null 2>&1 || true
 echo "Failure; draft and media retained. Backup=$backup"
 exit "$status"
}
trap on_failure ERR
wp eval-file "$backup/publish.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
trap - ERR
printf 'BACKUP=%s\nPOST_ID=%s\n' "$backup" "$(cat "$backup/post-id")"
