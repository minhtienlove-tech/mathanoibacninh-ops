#!/usr/bin/env bash
# Stage this script, publish-school-eye-care-20261009.php, news.css, article
# inputs, images/, expected.sha256 and css-before.sha256 under the stage path.
# The script verifies hashes, locks deployment, backs up CSS and DB, imports
# media to a draft and publishes only after the draft passes verification.
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
stage=/home/jwhxtzru/staging/thpt-chuyen-bac-giang-20261009
css="$root/wp-content/themes/eyecare-child/assets/news.css"
lock=/home/jwhxtzru/website-ops-deploy.lock
slug=kham-mat-hoc-duong-thpt-chuyen-bac-giang

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }

[[ -s "$stage/expected.sha256" && -s "$stage/css-before.sha256" ]] || {
  echo 'Missing local-source hash manifests.' >&2
  exit 1
}
cd "$stage"
sha256sum --check --status expected.sha256 || {
  echo 'Staged file checksum mismatch.' >&2
  exit 1
}

expected_css=$(tr -d '\r\n' < "$stage/css-before.sha256")
[[ "$expected_css" =~ ^[[:xdigit:]]{64}$ ]] || { echo 'Invalid CSS baseline hash.' >&2; exit 1; }
actual_css=$(sha256sum "$css" | cut -d' ' -f1)
[[ "$actual_css" == "$expected_css" ]] || {
  echo 'Production news.css differs from the pulled Git baseline; aborting.' >&2
  exit 1
}

backup="/home/jwhxtzru/backups/thpt-chuyen-bac-giang-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup"
cp -a "$stage/." "$backup/"
cp -p "$css" "$backup/news.css.before"
cd "$backup"
sha256sum --check --status expected.sha256 || { echo 'Backup input checksum mismatch.' >&2; exit 1; }
[[ $(sha256sum "$backup/news.css.before" | cut -d' ' -f1) == "$expected_css" ]] || {
  echo 'CSS backup checksum mismatch.' >&2
  exit 1
}

php -l "$backup/publish-school-eye-care-20261009.php"
cd "$root"
php -l wp-content/themes/eyecare-child/footer.php
php -l wp-content/themes/eyecare-child/page-lien-he.php
export EYECARE_BACKUP="$backup"
EYECARE_MODE=preflight wp eval-file "$backup/publish-school-eye-care-20261009.php"

wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]] || { echo 'Database backup failed.' >&2; exit 1; }
chmod 600 "$backup/database-before.sql"

theme_changed=0
on_failure() {
  local code=$? id status current_slug
  trap - ERR
  if [[ -s "$backup/post-id" ]]; then
    read -r id < "$backup/post-id"
    if [[ "$id" =~ ^[0-9]+$ ]]; then
      status=$(wp post get "$id" --field=post_status 2>/dev/null || true)
      current_slug=$(wp post get "$id" --field=post_name 2>/dev/null || true)
      if [[ "$status" == publish && "$current_slug" == "$slug" ]]; then
        wp post update "$id" --post_status=draft >/dev/null 2>&1 || true
      fi
    fi
  fi
  if [[ "$theme_changed" == 1 ]]; then
    cp -p "$backup/news.css.before" "$css" || true
  fi
  wp cache flush --quiet || true
  wp litespeed-purge all >/dev/null 2>&1 || true
  echo "Publication failed. Draft/media and backup were retained at: $backup" >&2
  exit "$code"
}
trap on_failure ERR

cd "$stage"
sha256sum --check --status expected.sha256
cp -p "$backup/news.css" "$css"
theme_changed=1
[[ $(sha256sum "$css" | cut -d' ' -f1) == $(sha256sum "$backup/news.css" | cut -d' ' -f1) ]]

cd "$root"
EYECARE_MODE=apply wp eval-file "$backup/publish-school-eye-care-20261009.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

url="https://mathanoibacninh.com/tin-tuc/hoat-dong-cong-dong/$slug/"
curl --fail --silent --show-error --location --max-time 30 \
  "$url?publish_qa=$(date +%s)" > "$backup/public-after.html"
grep -Fq 'Khám mắt học đường tại THPT Chuyên Bắc Giang' "$backup/public-after.html"
grep -Fq 'eyecare-school-gallery' "$backup/public-after.html"
grep -Fq 'https://mathanoibacninh.com/kien-thuc/kham-mat-hoc-duong-cho-hoc-sinh/' "$backup/public-after.html"
grep -Fq 'https://mathanoibacninh.com/khu-vuc/kham-mat-bac-giang/' "$backup/public-after.html"

trap - ERR
printf 'POST_ID=%s\n' "$(cat "$backup/post-id")"
printf 'MEDIA_IDS=%s\n' "$(wc -l < "$backup/media-ids.tsv")"
printf 'URL=%s\n' "$url"
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=change only this post to draft; restore news.css from backup; retain media and DB export\n'
