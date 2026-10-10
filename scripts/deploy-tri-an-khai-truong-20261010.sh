#!/usr/bin/env bash
# Run on the production host after staging the article inputs in a backup directory.
set -euo pipefail

backup=${1:?Usage: deploy-tri-an-khai-truong-20261010.sh /home/jwhxtzru/backups/<directory>}
case "$backup" in
  /home/jwhxtzru/backups/tri-an-khai-truong-20261010-*) ;;
  *) echo 'Unexpected backup directory.' >&2; exit 1 ;;
esac
test -d "$backup"
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
cd "$root"

for item in post.json media-manifest.json content-template.html publish.php news.css front-page.php; do
  test -s "$backup/$item"
done
test -d "$backup/images"

# Keep a complete rollback source before changing theme files or WordPress data.
cp -p "$theme/assets/news.css" "$backup/news.css.before"
cp -p "$theme/front-page.php" "$backup/front-page.php.before"
wp post get 1688 --field=post_content > "$backup/intro-before.html"
wp db export "$backup/database.sql" --quiet
chmod 600 "$backup/database.sql"
php -l "$backup/publish.php"
php -l "$backup/front-page.php"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"

EYECARE_BACKUP="$backup" EYECARE_MODE=preflight wp eval-file "$backup/publish.php"
cp -p "$backup/news.css" "$theme/assets/news.css"
cp -p "$backup/front-page.php" "$theme/front-page.php"
cmp -s "$backup/news.css" "$theme/assets/news.css"
cmp -s "$backup/front-page.php" "$theme/front-page.php"

EYECARE_BACKUP="$backup" EYECARE_MODE=apply wp eval-file "$backup/publish.php"
wp cache flush >/dev/null
if wp help litespeed-purge >/dev/null 2>&1; then
  wp litespeed-purge all >/dev/null
fi
echo "BACKUP=$backup"
echo "CSS_SHA256=$(sha256sum "$theme/assets/news.css" | cut -d ' ' -f 1)"
echo "POST_ID=$(cat "$backup/post-id")"
