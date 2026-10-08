#!/usr/bin/env bash
# Deploy optional homepage-slider links and the reveal/fade transition.
# Stage reviewed files and checksum manifests before running this on production.
set -euo pipefail

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/slider-links-effect-20261008
config="$theme/data/slider.json"
lock=/home/jwhxtzru/website-ops-deploy.lock
paths=(
  assets/home-critical.css
  assets/slider-admin.css
  assets/slider-admin.js
  assets/slider.js
  inc/slider-cai-dat.php
  inc/trang-chu.php
  style.css
)

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active' >&2; exit 1; }

# Manifest lines are "<sha256><two spaces><relative path>". These manifests
# must be made from the reviewed production snapshot and final local sources.
manifest_hash() {
  local manifest=$1 path=$2 hash count
  test -s "$manifest"
  count=$(awk -v path="$path" '$2 == path { n++ } END { print n + 0 }' "$manifest")
  [[ "$count" == 1 ]] || { echo "Missing or duplicate manifest entry: $path" >&2; exit 1; }
  hash=$(awk -v path="$path" '$2 == path { print $1 }' "$manifest")
  [[ "$hash" =~ ^[[:xdigit:]]{64}$ ]] || { echo "Invalid SHA-256 for $path" >&2; exit 1; }
  printf '%s' "${hash,,}"
}

check_sha() {
  local path=$1 expected=$2 actual
  test -s "$path" || { echo "Missing or empty file: $path" >&2; exit 1; }
  actual=$(sha256sum "$path" | cut -d' ' -f1)
  [[ "$actual" == "$expected" ]] || { echo "Checksum mismatch: $path" >&2; exit 1; }
}

old_manifest="$stage/baseline.sha256"
new_manifest="$stage/candidate.sha256"
for path in "${paths[@]}"; do
  check_sha "$theme/$path" "$(manifest_hash "$old_manifest" "$path")"
  check_sha "$stage/$path" "$(manifest_hash "$new_manifest" "$path")"
done
check_sha "$stage/tests/slider-links-test.php" "$(manifest_hash "$new_manifest" 'tests/slider-links-test.php')"

test -s "$stage/slider.before.sha256"
read -r old_config_hash < "$stage/slider.before.sha256"
[[ "$old_config_hash" =~ ^[[:xdigit:]]{64}$ ]] || { echo 'Invalid slider.before.sha256' >&2; exit 1; }
old_config_hash="${old_config_hash,,}"
check_sha "$config" "$old_config_hash"

php -l "$stage/inc/slider-cai-dat.php"
php -l "$stage/inc/trang-chu.php"
php -l "$stage/tests/slider-links-test.php"

backup="/home/jwhxtzru/backups/slider-links-effect-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup/assets" "$backup/inc" "$backup/data"
for path in "${paths[@]}"; do cp -p "$theme/$path" "$backup/$path"; done
cp -p "$config" "$backup/data/slider.json"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
chmod 600 "$backup/database-before.sql"

applied=0
config_touched=0
new_config_hash=
rollback_on_error() {
  local status=$?
  trap - ERR
  if [[ "$applied" == 1 ]]; then
    for path in "${paths[@]}"; do cp -p "$backup/$path" "$theme/$path" || true; done
    if [[ "$config_touched" == 1 ]]; then
      current_hash=$(sha256sum "$config" 2>/dev/null | cut -d' ' -f1 || true)
      if [[ "$current_hash" == "$new_config_hash" ]]; then
        cp -p "$backup/data/slider.json" "$config" || true
      else
        echo 'Slider config changed after deployment; preserving it for manual review' >&2
      fi
    fi
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
    echo "Deployment failed; restored child-theme files from $backup" >&2
  fi
  exit "$status"
}
trap rollback_on_error ERR

applied=1
for path in "${paths[@]}"; do
  cp -p "$stage/$path" "$theme/$path"
  check_sha "$theme/$path" "$(manifest_hash "$new_manifest" "$path")"
done
php -l "$theme/inc/slider-cai-dat.php"
php -l "$theme/inc/trang-chu.php"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
wp eval-file "$stage/tests/slider-links-test.php"

# Recheck the live configuration immediately before the change. The exact six
# selected media IDs, link map, timing, and layout remain untouched.
check_sha "$config" "$old_config_hash"
SLIDER_EXPECTED_SHA="$old_config_hash" wp eval '
$file = eyecare_slider_tep_cau_hinh();
$raw = file_get_contents( $file );
if ( false === $raw || hash( "sha256", $raw ) !== getenv( "SLIDER_EXPECTED_SHA" ) ) {
    WP_CLI::error( "Slider configuration changed during deployment" );
}
$data = json_decode( $raw, true );
if ( ! is_array( $data ) || ( $data["anh"] ?? null ) !== array( 991, 992, 993, 994, 995, 1738 ) || ( $data["hieu_ung"] ?? null ) !== "truot" ) {
    WP_CLI::error( "Unexpected live slide selection or transition; leaving configuration alone" );
}
$data["hieu_ung"] = "mo_rong";
$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
if ( ! is_string( $json ) ) {
    WP_CLI::error( "Could not encode slider transition" );
}
$temp = tempnam( dirname( $file ), ".slider-deploy-" );
if ( false === $temp ) {
    WP_CLI::error( "Could not create temporary slider configuration" );
}
$mode = fileperms( $file ) & 0777;
if ( false === file_put_contents( $temp, $json, LOCK_EX ) || ! chmod( $temp, $mode ) ) {
    @unlink( $temp );
    WP_CLI::error( "Could not prepare temporary slider configuration" );
}
if ( hash_file( "sha256", $file ) !== getenv( "SLIDER_EXPECTED_SHA" ) || ! rename( $temp, $file ) ) {
    @unlink( $temp );
    WP_CLI::error( "Slider configuration changed or atomic replacement failed" );
}
'
new_config_hash=$(sha256sum "$config" | cut -d' ' -f1)
config_touched=1

SLIDER_BACKUP_PATH="$backup/data/slider.json" wp eval '
$before = json_decode( file_get_contents( getenv( "SLIDER_BACKUP_PATH" ) ), true );
$after = json_decode( file_get_contents( eyecare_slider_tep_cau_hinh() ), true );
if ( ! is_array( $before ) || ! is_array( $after ) || ( $after["hieu_ung"] ?? null ) !== "mo_rong" ) {
    WP_CLI::error( "Slider transition verification failed" );
}
unset( $before["hieu_ung"], $after["hieu_ung"] );
if ( $before !== $after ) {
    WP_CLI::error( "Slider configuration changed beyond the requested effect" );
}
'

wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

# Check the public HTML after cache purge. A query string avoids stale edge HTML.
curl --fail --silent --show-error --location --max-time 30 \
  "https://mathanoibacninh.com/?slider_qa=$(date +%s)" > "$backup/home-after.html"
grep -q 'data-hieu-ung="mo_rong"' "$backup/home-after.html"
curl --fail --silent --show-error --location --max-time 30 --output /dev/null \
  'https://mathanoibacninh.com/wp-content/themes/eyecare-child/assets/slider.js'
curl --fail --silent --show-error --location --max-time 30 --output /dev/null \
  'https://mathanoibacninh.com/wp-content/themes/eyecare-child/style.css'

trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=restore the seven child-theme files and data/slider.json from this backup, then purge WordPress/LiteSpeed caches\n'
