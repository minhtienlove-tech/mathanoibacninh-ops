#!/usr/bin/env bash
# Deploy the opt-in booking notice only after file/database backup and hash checks.
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-public-proof-20261010
lock=/home/jwhxtzru/website-ops-deploy.lock

existing=(
  functions.php
  inc/dat-lich-kham.php
  inc/lien-he-noi.php
  page-quyen-rieng-tu.php
)
added=(
  inc/lich-kham-cong-khai.php
  assets/lich-kham-cong-khai.css
  assets/lich-kham-cong-khai.js
)
all=("${existing[@]}" "${added[@]}")

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }

[[ -s "$stage/expected.sha256" && -s "$stage/before.sha256" ]] || {
  echo 'Missing source or production hash manifest.' >&2
  exit 1
}
( cd "$stage" && sha256sum --check --status expected.sha256 ) || {
  echo 'Staged source checksum mismatch.' >&2
  exit 1
}
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" ) || {
  echo 'Production theme differs from the checked baseline.' >&2
  exit 1
}
for path in "${added[@]}"; do
  [[ ! -e "$theme/$path" ]] || { echo "New destination already exists: $path" >&2; exit 1; }
done
for path in "${all[@]}"; do
  [[ -s "$stage/$path" ]] || { echo "Missing staged source: $path" >&2; exit 1; }
done

for path in "${all[@]}"; do
  if [[ "$path" == *.php ]]; then php -l "$stage/$path"; fi
done
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
if command -v node >/dev/null 2>&1; then node --check "$stage/assets/lich-kham-cong-khai.js"; fi

backup="/home/jwhxtzru/backups/booking-public-proof-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup"
for path in "${existing[@]}"; do
  mkdir -p "$backup/$(dirname "$path")"
  cp -p "$theme/$path" "$backup/$path"
done
cp -a "$stage/." "$backup/staged/"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]] || { echo 'Database backup failed.' >&2; exit 1; }
chmod 600 "$backup/database-before.sql"

changed=0
on_failure() {
  local code=$?
  trap - ERR
  if [[ "$changed" == 1 ]]; then
    # Restore functions first so newly added files stop loading; retain those files for review.
    cp -p "$backup/functions.php" "$theme/functions.php" || true
    for path in "${existing[@]:1}"; do cp -p "$backup/$path" "$theme/$path" || true; done
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Booking notice deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR

changed=1
for path in "${added[@]}"; do cp -p "$stage/$path" "$theme/$path"; done
for path in "${existing[@]:1}"; do cp -p "$stage/$path" "$theme/$path"; done
cp -p "$stage/functions.php" "$theme/functions.php"
( cd "$theme" && sha256sum --check --status "$stage/expected.sha256" )
for path in "${all[@]}"; do if [[ "$path" == *.php ]]; then php -l "$theme/$path"; fi; done
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

curl --fail --silent --show-error --location --max-time 30 \
  "https://mathanoibacninh.com/dat-lich-kham/?proof_qa=$(date +%s)" > "$backup/public-booking.html"
grep -Fq 'name="public_share_consent"' "$backup/public-booking.html"
curl --fail --silent --show-error --location --max-time 30 \
  "https://mathanoibacninh.com/chinh-sach/quyen-rieng-tu/?proof_qa=$(date +%s)" > "$backup/public-privacy.html"
grep -Fq 'tên rút gọn đã duyệt' "$backup/public-privacy.html"
curl --fail --silent --show-error --location --max-time 30 \
  "https://mathanoibacninh.com/?proof_qa=$(date +%s)" > "$backup/public-home.html"

trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=restore the four backed-up theme files, retain new inert files, then purge caches\n'
