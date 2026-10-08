#!/usr/bin/env bash
set -euo pipefail

root=/home/jwhxtzru/public_html
stage=/home/jwhxtzru/staging/doctor-hai-blog-20261008
content="$stage/content.html"
expected_content_sha=8dce6917a349979a21f830057128de5dc303db5b58481e348836a71988db12b4
slug=gioi-thieu-bscki-dang-cong-hai

exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active' >&2; exit 1; }

test -s "$content"
[[ $(sha256sum "$content" | cut -d' ' -f1) == "$expected_content_sha" ]] || {
  echo 'Staged article does not match reviewed draft' >&2; exit 1;
}

cd "$root"
[[ $(wp post get 336 --field=post_status) == publish ]]
[[ $(wp post get 1693 --field=post_type) == attachment ]]
[[ $(wp term get category 24 --field=slug) == goc-bac-si ]]
[[ -z $(wp post list --post_type=post --post_status=any --name="$slug" --format=ids) ]] || {
  echo 'A post with this slug already exists' >&2; exit 1;
}

backup="/home/jwhxtzru/backups/doctor-hai-blog-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup"
cp -p "$content" "$backup/content.html"
cp -p wp-content/uploads/2026/08/2-1-scaled.png "$backup/"
wp post meta get 1693 _wp_attachment_image_alt > "$backup/image-alt-before.txt"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"

post_id=
rollback_on_error() {
  status=$?
  trap - ERR
  if [[ -n "$post_id" ]]; then
    wp post update "$post_id" --post_status=draft --quiet || true
  fi
  wp post meta update 1693 _wp_attachment_image_alt "$(cat "$backup/image-alt-before.txt")" --quiet || true
  wp cache flush --quiet || true
  wp litespeed-purge all >/dev/null 2>&1 || true
  echo "Publishing failed; kept new post as draft. Backup: $backup" >&2
  exit "$status"
}
trap rollback_on_error ERR

post_id=$(wp post create "$content" \
  --post_type=post \
  --post_status=draft \
  --post_author=0 \
  --post_category=24 \
  --comment_status=closed \
  --ping_status=closed \
  --post_name="$slug" \
  --post_title='Giới thiệu BSCKI. Đặng Công Hải – Giám đốc Bệnh viện Mắt Hà Nội – Bắc Ninh' \
  --post_excerpt='Gặp gỡ BSCKI. Đặng Công Hải, Giám đốc Bệnh viện Mắt Hà Nội – Bắc Ninh, với hơn 20 năm kinh nghiệm nhãn khoa và hơn 10.000 ca phẫu thuật.' \
  --porcelain)
printf '%s\n' "$post_id" > "$backup/new-post-id.txt"

wp post meta update "$post_id" _eyecare_bac_si_lien_quan 336 --quiet
wp post meta update "$post_id" _thumbnail_id 1693 --quiet
wp post meta update 1693 _wp_attachment_image_alt 'Ảnh giới thiệu BSCKI. Đặng Công Hải, Giám đốc Bệnh viện Mắt Hà Nội – Bắc Ninh' --quiet
wp post get "$post_id" --field=post_content | cmp -s - "$content"
[[ $(wp post meta get "$post_id" _eyecare_bac_si_lien_quan) == 336 ]]
[[ $(wp post meta get "$post_id" _thumbnail_id) == 1693 ]]

wp post update "$post_id" --post_status=publish --quiet
[[ $(wp post get "$post_id" --field=post_status) == publish ]]
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

trap - ERR
printf 'POST_ID=%s\n' "$post_id"
printf 'POST_URL=%s\n' "$(wp post url "$post_id")"
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=set post %s to draft and restore image alt from %s, then purge caches\n' "$post_id" "$backup/image-alt-before.txt"
