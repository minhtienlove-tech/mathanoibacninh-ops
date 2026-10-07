#!/usr/bin/env bash
# Run on the production host under ~/website-ops-deploy.lock.
# The argument is an absolute staging directory inside ~/backups containing
# deployment.tsv and the 12 WebP files. No theme file is changed.
set -Eeuo pipefail

stage="${1:?Usage: bash deploy-category-intro-images-20261007.sh /home/jwhxtzru/backups/STAGE}"
site='/home/jwhxtzru/public_html'
case "$stage" in
  /home/jwhxtzru/backups/category-images-20261007-*) ;;
  *) echo 'Stage must be a category-images-20261007 backup directory.' >&2; exit 2 ;;
esac
[[ -d "$stage" && -f "$stage/deployment.tsv" ]] || { echo 'Missing staging files.' >&2; exit 2; }
[[ "$(wc -l < "$stage/deployment.tsv")" -eq 12 ]] || { echo 'Expected exactly 12 images.' >&2; exit 2; }
cd "$site"

# Check every target before making any database change.
while IFS=$'\t' read -r post_id post_slug title filename alt caption expected_sha; do
  [[ "$post_id" =~ ^(1678|1679|1680|1681|1682|1683|1684|1685|1686|1687|1688|1689)$ ]] || exit 3
  [[ "$post_slug" =~ ^[a-z0-9-]+$ && "$filename" =~ ^[a-z0-9-]+\.webp$ ]] || exit 3
  [[ "$expected_sha" =~ ^[0-9a-f]{64}$ ]] || exit 3
  [[ -s "$stage/$filename" ]] || { echo "Missing $filename" >&2; exit 3; }
  [[ "$(sha256sum "$stage/$filename" | cut -d' ' -f1)" == "$expected_sha" ]] || { echo "Checksum mismatch: $filename" >&2; exit 3; }
  php -r '$i=getimagesize($argv[1]); if (!$i || $i[2] !== IMAGETYPE_WEBP || $i[0] < 900 || $i[1] < 600) exit(1);' "$stage/$filename" || { echo "Invalid WebP: $filename" >&2; exit 3; }
  [[ "$(wp post get "$post_id" --field=post_type)" == 'post' ]] || exit 3
  [[ "$(wp post get "$post_id" --field=post_status)" == 'publish' ]] || exit 3
  [[ "$(wp post get "$post_id" --field=post_name)" == "$post_slug" ]] || { echo "Post slug changed: $post_id" >&2; exit 3; }
  [[ -z "$(wp post meta get "$post_id" _thumbnail_id 2>/dev/null || true)" ]] || { echo "Thumbnail already set: $post_id" >&2; exit 3; }
done < "$stage/deployment.tsv"

wp db export "$stage/database-before.sql" --add-drop-table >/dev/null
[[ -s "$stage/database-before.sql" ]] || { echo 'Database backup failed.' >&2; exit 4; }
printf 'post_id\tprevious_thumbnail_id\n' > "$stage/featured-before.tsv"
while IFS=$'\t' read -r post_id _; do
  printf '%s\t\n' "$post_id" >> "$stage/featured-before.tsv"
done < "$stage/deployment.tsv"
printf 'post_id\tattachment_id\tfile\turl\n' > "$stage/imported.tsv"
printf 'To roll back only these featured-image assignments, inspect featured-before.tsv and run: wp post meta delete POST_ID _thumbnail_id\nDo not delete imported media without separate approval. Full database backup: database-before.sql\n' > "$stage/ROLLBACK.txt"

while IFS=$'\t' read -r post_id post_slug title filename alt caption expected_sha; do
  attachment_id="$(wp media import "$stage/$filename" --post_id="$post_id" --title="$title" --alt="$alt" --featured_image --porcelain)"
  [[ "$attachment_id" =~ ^[0-9]+$ ]] || { echo "Media import returned invalid ID for $post_id: $attachment_id" >&2; exit 5; }
  wp post update "$attachment_id" --post_excerpt="$caption" >/dev/null
  wp post meta update "$attachment_id" _eyecare_ai_illustration 1 >/dev/null
  [[ "$(wp post meta get "$post_id" _thumbnail_id)" == "$attachment_id" ]] || { echo "Featured image not assigned: $post_id" >&2; exit 5; }
  [[ "$(wp post get "$attachment_id" --field=post_mime_type)" == 'image/webp' ]] || exit 5
  file_url="$(wp eval "echo wp_get_attachment_url($attachment_id);")"
  [[ "$file_url" == https://mathanoibacninh.com/* ]] || { echo "Unexpected media URL for $post_id" >&2; exit 5; }
  printf '%s\t%s\t%s\t%s\n' "$post_id" "$attachment_id" "$filename" "$file_url" >> "$stage/imported.tsv"
  printf 'Assigned post %s -> image %s (%s)\n' "$post_id" "$attachment_id" "$filename"
done < "$stage/deployment.tsv"

[[ "$(tail -n +2 "$stage/imported.tsv" | wc -l)" -eq 12 ]] || exit 6
[[ "$(tail -n +2 "$stage/imported.tsv" | cut -f2 | sort -u | wc -l)" -eq 12 ]] || exit 6
wp cache flush >/dev/null
wp litespeed-purge all >/dev/null 2>&1 || true
echo '12 unique featured images imported; cache purged.'
