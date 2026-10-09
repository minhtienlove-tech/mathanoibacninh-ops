#!/usr/bin/env bash
# Selectively correct two sentences in post 1748 after backing up its content and database.
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
stage=/home/jwhxtzru/staging/school-article-copy-20261009
script="$stage/fix-school-article-copy-20261009.php"
lock=/home/jwhxtzru/website-ops-deploy.lock
url=https://mathanoibacninh.com/tin-tuc/hoat-dong-cong-dong/kham-mat-hoc-duong-thpt-chuyen-bac-giang/

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }

expected=$(tr -d '\r\n' < "$stage/php.sha256")
[[ "$expected" =~ ^[[:xdigit:]]{64}$ ]] || { echo 'Invalid source hash.' >&2; exit 1; }
[[ $(sha256sum "$script" | cut -d' ' -f1) == "$expected" ]] || {
  echo 'Staged PHP checksum mismatch.' >&2
  exit 1
}
php -l "$script"

cd "$root"
php -l wp-content/themes/eyecare-child/footer.php
php -l wp-content/themes/eyecare-child/page-lien-he.php
EYECARE_MODE=preflight wp eval-file "$script"

backup="/home/jwhxtzru/backups/school-article-copy-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup"
cp -p "$script" "$backup/fix-school-article-copy-20261009.php"
wp post get 1748 --field=post_content > "$backup/post-content.before.html"
[[ -s "$backup/post-content.before.html" ]]
wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]]
chmod 600 "$backup/database-before.sql" "$backup/post-content.before.html"

updated=0
on_failure() {
  local code=$?
  trap - ERR
  if [[ "$updated" == 1 ]]; then
    EYECARE_MODE=rollback EYECARE_BACKUP="$backup" wp eval-file "$script" || true
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Article copy deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR

updated=1
EYECARE_MODE=apply wp eval-file "$script"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
curl --fail --silent --show-error --location --max-time 30 "$url?copy_qa=$(date +%s)" > "$backup/public-after.html"
grep -Fq 'Phường Bắc Giang, Tỉnh Bắc Ninh' "$backup/public-after.html"
grep -Fq 'bài viết không nêu tên hoặc kết quả khám của từng học sinh' "$backup/public-after.html"
! grep -Fq 'Phường Bắc Giang, Thành phố Bắc Ninh' "$backup/public-after.html"
! grep -Fq 'không công bố kết quả khám hoặc danh tính học sinh' "$backup/public-after.html"

trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=EYECARE_MODE=rollback EYECARE_BACKUP=%s wp eval-file %s\n' "$backup" "$script"
