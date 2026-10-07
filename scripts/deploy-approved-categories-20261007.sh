#!/usr/bin/env bash
# Apply the committed child-theme delta and the preflighted 12-category import.
set -euo pipefail

backup="$HOME/backups/categories-doctors-20261007-prepare1"
root="$HOME/public_html"
theme="$root/wp-content/themes/eyecare-child"
stage="$backup/staged/eyecare-child"
list="$backup/theme-files.txt"

exec 9>"$HOME/backups/.eyecare-deploy.lock"
flock -n 9 || { echo 'Another deployment holds the lock.' >&2; exit 1; }

test -s "$backup/child-theme-before.tar.gz"
test -s "$backup/database-before.sql"
test -s "$backup/category-preflight.json"
test -s "$backup/import.php"
test -s "$list"
test -d "$stage"
test -d "$theme"

while IFS= read -r rel; do
  test -n "$rel" || continue
  case "$rel" in
    /*|*..*) echo "Unsafe file path: $rel" >&2; exit 1 ;;
  esac
  test -f "$stage/$rel" || { echo "Missing staged file: $rel" >&2; exit 1; }
done < "$list"

rsync -a --files-from="$list" "$stage/" "$theme/"
while IFS= read -r rel; do
  test -n "$rel" || continue
  cmp -s "$stage/$rel" "$theme/$rel" || { echo "Copied file differs: $rel" >&2; exit 1; }
  case "$rel" in
    *.php) php -l "$theme/$rel" >/dev/null ;;
  esac
done < "$list"

EYECARE_CATEGORY_APPLY=yes wp --path="$root" eval-file "$backup/import.php"
wp --path="$root" cache flush
wp --path="$root" litespeed-purge all || true
echo 'Theme files and category import applied.'
