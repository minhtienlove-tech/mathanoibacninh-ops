#!/usr/bin/env bash
set -euo pipefail
umask 077

# Stage this script, the PHP importer, the listed child-theme files, and four
# poster JPEGs under /home/jwhxtzru/staging/doctor-videos-20261012 first.
# --check validates the stage without writing. --approve confirms publication
# rights/consent for the four supplied videos and posters, then backs up and
# deploys them.
mode=${1:-}
if [[ "$mode" != --check && "$mode" != --approve || $# -ne 1 ]]; then
  echo "Usage: $0 --check|--approve" >&2
  exit 2
fi

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/doctor-videos-20261012
lock=/home/jwhxtzru/website-ops-deploy.lock
importer="$stage/scripts/import-doctor-videos-20261012.php"
files=(
  functions.php
  front-page.php
  single-eyecare_bac_si.php
  single.php
  template-parts/single-news.php
  style.css
  inc/quan-ly-danh-gia.php
  inc/video-bac-si.php
  assets/video-bac-si.css
  assets/video-bac-si.js
)
php_files=(
  functions.php
  front-page.php
  single-eyecare_bac_si.php
  single.php
  template-parts/single-news.php
  inc/quan-ly-danh-gia.php
  inc/video-bac-si.php
)

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
cd "$root"
test "$(wp option get stylesheet)" = eyecare-child
test -f "$importer"
for file in "${files[@]}"; do
  test -f "$stage/theme/eyecare-child/$file" || { echo "Missing staged theme file: $file" >&2; exit 1; }
done
for file in "${php_files[@]}"; do
  php -l "$stage/theme/eyecare-child/$file" >/dev/null
done
php -l "$importer" >/dev/null
php -l "$theme/footer.php" >/dev/null
php -l "$theme/page-lien-he.php" >/dev/null

printf '%s\n' \
  '38ab51322938f69b427cbcb8edd1bb8bf742bac64bd39c689736c81823ba1236  wyri86ODYrA.jpg' \
  '8fa14a0ac80fba7e18ad99df364190f957538757d13d0316c2686945a134b50d  SlToWMv-_4E.jpg' \
  '19add1fdd6d86d44688a210fde3463321bba40ca7a70cd44a8344e2fc78d30d9  LqxNF7hxLhU.jpg' \
  '4a423b8522e8918c6ecb5f5d2cc8fe3f4a7c13eb9a42cc620f02d93d5154c7af  3qVuG1DgNmk.jpg' \
  | ( cd "$stage/posters" && sha256sum --check --status )

# Validate the required relationships before any production write.
test "$(wp post get 674 --field=post_type)" = eyecare_bac_si
test "$(wp post get 674 --field=post_status)" = publish
for id in 144 1445 140 116; do
  test "$(wp post get "$id" --field=post_type)" = post
  test "$(wp post get "$id" --field=post_status)" = publish
done

if [[ "$mode" == --check ]]; then
  echo 'Stage, PHP syntax, poster hashes, and WordPress relationships passed. No production changes.'
  exit 0
fi

backup="/home/jwhxtzru/backups/doctor-videos-$(date +%Y%m%d-%H%M%S)"
test ! -e "$backup" || { echo "Backup directory already exists: $backup" >&2; exit 1; }
mkdir -m 700 "$backup"
report_failure() {
  local code=$?
  trap - ERR
  echo "Deployment stopped. Inspect any partial changes before a selective rollback. Backup: $backup" >&2
  exit "$code"
}
trap report_failure ERR
tar -C "$root/wp-content/themes" -czf "$backup/child-theme-before.tar.gz" eyecare-child
test -s "$backup/child-theme-before.tar.gz"
tar -tzf "$backup/child-theme-before.tar.gz" >/dev/null
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
chmod 600 "$backup/database-before.sql" "$backup/child-theme-before.tar.gz"
printf 'BACKUP=%s\n' "$backup"

for file in "${files[@]}"; do
  install -D -m 644 "$stage/theme/eyecare-child/$file" "$theme/$file"
  cmp -s "$stage/theme/eyecare-child/$file" "$theme/$file"
done
for file in "${php_files[@]}"; do
  php -l "$theme/$file" >/dev/null
done

export EYECARE_DOCTOR_VIDEO_POSTERS="$stage/posters"
export EYECARE_DOCTOR_VIDEO_BACKUP="$backup"
EYECARE_DOCTOR_VIDEO_APPLY=0 wp eval-file "$importer"
EYECARE_DOCTOR_VIDEO_APPLY=1 wp eval-file "$importer"
EYECARE_DOCTOR_VIDEO_APPLY=0 wp eval-file "$importer"

wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

home_url=$(wp option get home)
doctor_url=$(wp eval 'echo get_permalink(674);')
curl -fsSL --max-time 30 "$home_url" -o "$backup/home-after.html"
curl -fsSL --max-time 30 "$doctor_url" -o "$backup/doctor-after.html"
for video_id in wyri86ODYrA SlToWMv-_4E; do
  grep -Fq "$video_id" "$backup/home-after.html"
done
for video_id in wyri86ODYrA SlToWMv-_4E LqxNF7hxLhU 3qVuG1DgNmk; do
  grep -Fq "$video_id" "$backup/doctor-after.html"
done
for pair in 144:wyri86ODYrA 1445:SlToWMv-_4E 140:LqxNF7hxLhU 116:3qVuG1DgNmk; do
  article_id=${pair%%:*}
  video_id=${pair#*:}
  article_url=$(wp eval "echo get_permalink($article_id);")
  curl -fsSL --max-time 30 "$article_url" -o "$backup/article-$article_id-after.html"
  grep -Fq "$video_id" "$backup/article-$article_id-after.html"
done
theme_url=$(wp eval 'echo get_stylesheet_directory_uri();')
curl -fsSL --max-time 30 "$theme_url/assets/video-bac-si.css" -o "$backup/video-bac-si-after.css"
grep -Fq '.eyecare-video-bs' "$backup/video-bac-si-after.css"

trap - ERR
echo "Deployment and public HTML/CSS checks passed. Backup: $backup"
