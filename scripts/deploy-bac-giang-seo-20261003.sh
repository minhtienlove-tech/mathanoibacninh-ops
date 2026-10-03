#!/usr/bin/env bash
# Run on the hosting account through flock /home/jwhxtzru/website-ops-deploy.lock.
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

check_hash "$theme/inc/content-plan-seo.php" 3d5fd25d05c8229811c1ef473dcb0cdaa7b40bd34d2d51205ae841294b5d5992
check_hash "$theme/inc/schema-y-te.php" 28a7f1509fdd69d4b07bd481568d10c5f05c20680962d11769bd6f6003c073ad
check_hash "$theme/inc/noi-dung-lien-he-seo.php" 43d97f4d9ac88c5d1a880097b1e28c784af6fa6f7f93f7a9d0900523349bf573
check_hash "$stage/content-plan-seo-new.php" 2b8c4ccf399a114ed2846ed1d24cf12f56fe2eacc8bb34aa22cb7a6e0b1b9786
check_hash "$stage/schema-y-te-new.php" 9ca45154b6273ccaead3556544747864ce0a2ac75dad335c414abbdd604257e3
check_hash "$stage/noi-dung-lien-he-seo-new.php" cb38fef983cae4eb13af290ae90c06bfb8ad181f50a39d075c30634d4e684039

php -l "$stage/content-plan-seo-new.php"
php -l "$stage/schema-y-te-new.php"
php -l "$stage/noi-dung-lien-he-seo-new.php"
wp eval-file "$stage/bg-sitemap-lastmod-20261003.php"

umask 077
backup="/home/jwhxtzru/backups/bac-giang-seo-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup"
cp -p "$theme/inc/content-plan-seo.php" "$backup/content-plan-seo.php"
cp -p "$theme/inc/schema-y-te.php" "$backup/schema-y-te.php"
cp -p "$theme/inc/noi-dung-lien-he-seo.php" "$backup/noi-dung-lien-he-seo.php"
wp db export "$backup/database-pre-seo.sql" --quiet
chmod 600 "$backup/database-pre-seo.sql"
printf 'BACKUP=%s\n' "$backup"

cp "$stage/content-plan-seo-new.php" "$theme/inc/content-plan-seo.php"
cp "$stage/schema-y-te-new.php" "$theme/inc/schema-y-te.php"
cp "$stage/noi-dung-lien-he-seo-new.php" "$theme/inc/noi-dung-lien-he-seo.php"

php -l "$theme/inc/content-plan-seo.php"
php -l "$theme/inc/schema-y-te.php"
php -l "$theme/inc/noi-dung-lien-he-seo.php"
check_hash "$theme/inc/content-plan-seo.php" 2b8c4ccf399a114ed2846ed1d24cf12f56fe2eacc8bb34aa22cb7a6e0b1b9786
check_hash "$theme/inc/schema-y-te.php" 9ca45154b6273ccaead3556544747864ce0a2ac75dad335c414abbdd604257e3
check_hash "$theme/inc/noi-dung-lien-he-seo.php" cb38fef983cae4eb13af290ae90c06bfb8ad181f50a39d075c30634d4e684039

BG_LASTMOD_APPLY=1 BG_SEO_BACKUP_DIR="$backup" wp eval-file "$stage/bg-sitemap-lastmod-20261003.php"
wp cache flush
wp litespeed-purge all
printf 'DEPLOYED=%s\n' "$backup"
