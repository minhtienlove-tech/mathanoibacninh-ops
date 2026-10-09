#!/usr/bin/env bash
# Follow-up visual QA fix for the school-event photo gallery.
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
css="$root/wp-content/themes/eyecare-child/assets/news.css"
stage=/home/jwhxtzru/staging/school-gallery-masonry-20261009
lock=/home/jwhxtzru/website-ops-deploy.lock

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }

before=$(tr -d '\r\n' < "$stage/css-before.sha256")
after=$(tr -d '\r\n' < "$stage/css-after.sha256")
[[ "$before" =~ ^[[:xdigit:]]{64}$ && "$after" =~ ^[[:xdigit:]]{64}$ ]] || {
  echo 'Invalid CSS hash input.' >&2
  exit 1
}
[[ $(sha256sum "$css" | cut -d' ' -f1) == "$before" ]] || {
  echo 'Production CSS differs from the expected previous version.' >&2
  exit 1
}
[[ $(sha256sum "$stage/news.css" | cut -d' ' -f1) == "$after" ]] || {
  echo 'Staged CSS does not match the new source hash.' >&2
  exit 1
}

backup="/home/jwhxtzru/backups/school-gallery-masonry-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup"
cp -p "$css" "$backup/news.css.before"
cp -p "$stage/news.css" "$backup/news.css.after"
[[ $(sha256sum "$backup/news.css.before" | cut -d' ' -f1) == "$before" ]]
[[ $(sha256sum "$backup/news.css.after" | cut -d' ' -f1) == "$after" ]]

cd "$root"
php -l wp-content/themes/eyecare-child/footer.php
php -l wp-content/themes/eyecare-child/page-lien-he.php
wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]] || { echo 'Database backup failed.' >&2; exit 1; }
chmod 600 "$backup/database-before.sql"

changed=0
on_failure() {
  local code=$?
  trap - ERR
  if [[ "$changed" == 1 ]]; then
    cp -p "$backup/news.css.before" "$css" || true
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Gallery CSS deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR

cp -p "$backup/news.css.after" "$css"
changed=1
[[ $(sha256sum "$css" | cut -d' ' -f1) == "$after" ]]
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
curl --fail --silent --show-error --location --max-time 30 \
  "https://mathanoibacninh.com/wp-content/themes/eyecare-child/assets/news.css?gallery_qa=$(date +%s)" \
  > "$backup/public-news.css"
grep -Fq 'column-count: 3;' "$backup/public-news.css"

trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'CSS_SHA256=%s\n' "$after"
printf 'ROLLBACK=copy news.css.before from this backup over assets/news.css and purge caches\n'
