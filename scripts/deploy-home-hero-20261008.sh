#!/usr/bin/env bash
set -euo pipefail

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/home-hero-20261008
lock=/home/jwhxtzru/website-ops-deploy.lock

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active'; exit 1; }

check_sha() {
  local path=$1 expected=$2 actual
  test -s "$path"
  actual=$(sha256sum "$path" | cut -d' ' -f1)
  [[ "$actual" == "$expected" ]] || { echo "Checksum mismatch: $path"; exit 1; }
}

# Stop if another machine changed any production file after the audit.
check_sha "$theme/assets/home-critical.css" e10accd17be63636bdb65d62b0495bbc69ffa8bd9352b4ca3abe13f83b5adf06
check_sha "$theme/functions.php" 284217803f6155849cb5af9ff8cd7140d3c6c7862f74128be494a026d2d170e3
check_sha "$theme/inc/trang-chu.php" 345824a00f3ea1c486b89569e7734e5c5171f3d8ccd0b0d53fdb09e5d3f37482

check_sha "$stage/home-critical.css" 56323f775aa796042db3be06503969c671c4d1f14bc4b0989031b99df1a7f1fc
check_sha "$stage/functions.php" 85eb296356815998e2da742b92b48fe8d95cc45bb2b33b888531dbc844b4ce70
check_sha "$stage/trang-chu.php" 7856b7ff369792862131f8baa86358b4d3dc8403e4a851144e9a8dd4cdac782c
php -l "$stage/functions.php"
php -l "$stage/trang-chu.php"

backup="/home/jwhxtzru/backups/home-hero-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup/assets" "$backup/inc"
cp -p "$theme/assets/home-critical.css" "$backup/assets/home-critical.css"
cp -p "$theme/functions.php" "$backup/functions.php"
cp -p "$theme/inc/trang-chu.php" "$backup/inc/trang-chu.php"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
chmod 600 "$backup/database-before.sql"

applied=0
rollback_on_error() {
  if [[ "$applied" == 1 ]]; then
    cp -p "$backup/assets/home-critical.css" "$theme/assets/home-critical.css"
    cp -p "$backup/functions.php" "$theme/functions.php"
    cp -p "$backup/inc/trang-chu.php" "$theme/inc/trang-chu.php"
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
    echo "Restored original theme files from $backup"
  fi
}
trap rollback_on_error ERR

applied=1
cp -p "$stage/home-critical.css" "$theme/assets/home-critical.css"
cp -p "$stage/functions.php" "$theme/functions.php"
cp -p "$stage/trang-chu.php" "$theme/inc/trang-chu.php"
php -l "$theme/functions.php"
php -l "$theme/inc/trang-chu.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
trap - ERR

printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=restore assets/home-critical.css, functions.php, inc/trang-chu.php from this backup; then flush WordPress/LiteSpeed cache\n'
