#!/usr/bin/env bash
set -euo pipefail
umask 077
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active'; exit 1; }
stage=/home/jwhxtzru/staging/school-policy-table-20261029
target=/home/jwhxtzru/public_html/wp-content/themes/eyecare-child/assets/news.css
old_hash=5c0b0b00cf9f61285412bd65c1a6e33ed3412e0d64f765df54ba40a575d9f98c
new_hash=${1:?Expected new CSS hash required}
[[ "$new_hash" =~ ^[0-9a-f]{64}$ ]] || exit 1
test "$(sha256sum "$target" | cut -d ' ' -f1)" = "$old_hash"
test "$(sha256sum "$stage/news.css" | cut -d ' ' -f1)" = "$new_hash"
backup=/home/jwhxtzru/backups/school-policy-table-20261029-$(date +%H%M%S)
mkdir -p "$backup"
cp -p "$target" "$backup/news.css"
cp -p "$stage/deploy.sh" "$backup/deploy.sh"
cp -p "$stage/source-commit.txt" "$backup/source-commit.txt"
cd /home/jwhxtzru/public_html
wp db export "$backup/database.sql" --quiet
test -s "$backup/database.sql"
php -l wp-content/themes/eyecare-child/footer.php
php -l wp-content/themes/eyecare-child/page-lien-he.php
rollback() {
    local result=$?
    trap - ERR
    if test "$(sha256sum "$target" | cut -d ' ' -f1)" = "$new_hash"; then
        cp -p "$backup/news.css" "$target"
        wp eval 'do_action("litespeed_purge_url",get_permalink(2163));' || true
    fi
    echo "CSS deployment failed. Backup=$backup"
    exit "$result"
}
trap rollback ERR
cp "$stage/news.css" "$target.website-ops-20261029"
chmod 644 "$target.website-ops-20261029"
mv -f "$target.website-ops-20261029" "$target"
test "$(sha256sum "$target" | cut -d ' ' -f1)" = "$new_hash"
wp eval 'do_action("litespeed_purge_url",get_permalink(2163));'
trap - ERR
printf 'BACKUP=%s\nCSS_SHA256=%s\n' "$backup" "$new_hash"
