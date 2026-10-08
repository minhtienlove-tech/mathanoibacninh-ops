#!/usr/bin/env bash
set -euo pipefail

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/news-portrait-cards-20261008
paths=(
  functions.php
  assets/news.css
  template-parts/archive-news.php
  page-tin-tuc.php
  template-parts/single-news.php
  single-eyecare_bac_si.php
)
old=(
  85eb296356815998e2da742b92b48fe8d95cc45bb2b33b888531dbc844b4ce70
  a0fd86b9e7e73f06d7bfbefadb274852052e37975c3bf8a5b81c7cc7612398cd
  504856f093b2fb8737651565791ae18267dcc27d029d4e399e9277fcdcf40c43
  08bcfdb26a547b5531a9a6e28e5af931b28385cd45e62cb83b728be09e24d2a0
  92327ad253d9129c9c888c3fc3534a654cab4e463012de6efcbc1f71f5b4abe9
  eb0ec902373f99c6b5bed115b45976d7b44acbcbd39ddcfd0086cc429eb99e9b
)
new=(
  baa0172a174d8cfe70ff6544f431d7248e004e55cc764acc0cfa387c37740b08
  9a6e0e4fa82b147e042b5fe08e31744be25bc7f6fdfbef352af2c80aea5dde63
  5f96eba56204c323ea04a258685ff1ba1d9cadd84973a86756bc5e99f68d77c6
  5632c50a852a7e03053063c718f92edb539750a09ed77d6f026dbfcc43c8511e
  1dc41f0d09342fbd9056420262b9ecd984e674607f46ecea8da3cf71798bdddb
  e36e895c33846f424409af00b64c9acc2e3be12e8b1eed6700099e3415cd2ae0
)

exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active' >&2; exit 1; }

check_sha() {
  local path=$1 expected=$2 actual
  test -s "$path"
  actual=$(sha256sum "$path" | cut -d' ' -f1)
  [[ "$actual" == "$expected" ]] || { echo "Checksum mismatch: $path" >&2; exit 1; }
}

for i in "${!paths[@]}"; do
  check_sha "$theme/${paths[i]}" "${old[i]}"
  check_sha "$stage/${paths[i]}" "${new[i]}"
done

for path in "${paths[@]}"; do
  if [[ "$path" == *.php ]]; then php -l "$stage/$path"; fi
done

backup="/home/jwhxtzru/backups/news-portrait-cards-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup/assets" "$backup/template-parts"
for path in "${paths[@]}"; do cp -p "$theme/$path" "$backup/$path"; done
cd "$root"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
chmod 600 "$backup/database-before.sql"

applied=0
rollback_on_error() {
  status=$?
  trap - ERR
  if [[ "$applied" == 1 ]]; then
    for path in "${paths[@]}"; do cp -p "$backup/$path" "$theme/$path" || true; done
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
    echo "Deployment failed; restored child theme files from $backup" >&2
  fi
  exit "$status"
}
trap rollback_on_error ERR

applied=1
for i in "${!paths[@]}"; do
  cp -p "$stage/${paths[i]}" "$theme/${paths[i]}"
  check_sha "$theme/${paths[i]}" "${new[i]}"
  if [[ "${paths[i]}" == *.php ]]; then php -l "$theme/${paths[i]}"; fi
done
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=restore six child-theme files from this backup, then purge WordPress/LiteSpeed caches\n'
