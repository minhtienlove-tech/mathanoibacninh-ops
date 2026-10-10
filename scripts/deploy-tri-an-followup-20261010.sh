#!/usr/bin/env bash
set -euo pipefail
backup=${1:?Usage: deploy-tri-an-followup-20261010.sh /home/jwhxtzru/backups/<directory>}
case "$backup" in
  /home/jwhxtzru/backups/tri-an-followup-20261010-*) ;;
  *) echo 'Unexpected backup directory.' >&2; exit 1 ;;
esac
test -s "$backup/front-page.php"
test -s "$backup/fix-excerpt.php"
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
cd "$root"
cp -p "$theme/front-page.php" "$backup/front-page.php.before"
wp post get 1688 --field=post_excerpt > "$backup/intro-excerpt.before.txt"
wp db export "$backup/database.sql" --quiet
chmod 600 "$backup/database.sql"
php -l "$backup/front-page.php"
php -l "$backup/fix-excerpt.php"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
cp -p "$backup/front-page.php" "$theme/front-page.php"
cmp -s "$backup/front-page.php" "$theme/front-page.php"
wp eval-file "$backup/fix-excerpt.php"
wp cache flush >/dev/null
if wp help litespeed-purge >/dev/null 2>&1; then
  wp litespeed-purge all >/dev/null
fi
echo "BACKUP=$backup"
echo "FRONT_PAGE_SHA256=$(sha256sum "$theme/front-page.php" | cut -d ' ' -f 1)"
