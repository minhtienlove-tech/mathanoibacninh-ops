#!/usr/bin/env bash
# Run through flock /home/jwhxtzru/website-ops-deploy.lock on the hosting account.
set -euo pipefail

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/backups/faq-safety-stage-20261003
cd "$root"

check_hash() {
  local file="$1" expected="$2" actual
  actual=$(sha256sum "$file")
  actual=${actual%% *}
  if [[ "$actual" != "$expected" ]]; then
    printf 'SHA mismatch: %s expected %s got %s\n' "$file" "$expected" "$actual" >&2
    exit 1
  fi
}

check_hash "$theme/inc/content-plan-seo.php" 2b8c4ccf399a114ed2846ed1d24cf12f56fe2eacc8bb34aa22cb7a6e0b1b9786
check_hash "$stage/content-plan-seo-polished.php" 89762bcbffb013b118f1acf4ffaa5d745ecb04735b14144c8d37fd063c897d8d
check_hash "$stage/fix-workplace-meta-20261003.php" 51355e75790807f6ab5b370364d24f375ebb89864bacd8ccd422bc695352ed74
php -l "$stage/content-plan-seo-polished.php"
php -l "$stage/fix-workplace-meta-20261003.php"
wp eval-file "$stage/fix-workplace-meta-20261003.php"

umask 077
backup="/home/jwhxtzru/backups/seo-description-polish-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup"
cp -p "$theme/inc/content-plan-seo.php" "$backup/content-plan-seo.php"
wp db export "$backup/database-pre-polish.sql" --quiet
chmod 600 "$backup/database-pre-polish.sql"
printf 'BACKUP=%s\n' "$backup"

cp "$stage/content-plan-seo-polished.php" "$theme/inc/content-plan-seo.php"
php -l "$theme/inc/content-plan-seo.php"
check_hash "$theme/inc/content-plan-seo.php" 89762bcbffb013b118f1acf4ffaa5d745ecb04735b14144c8d37fd063c897d8d

WORKPLACE_META_APPLY=1 BG_SEO_BACKUP_DIR="$backup" wp eval-file "$stage/fix-workplace-meta-20261003.php"
wp cache flush
wp litespeed-purge all
printf 'DEPLOYED=%s\n' "$backup"
