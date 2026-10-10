#!/usr/bin/env bash
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-proof-demo-20261010
lock=/home/jwhxtzru/website-ops-deploy.lock
existing=(inc/lich-kham-cong-khai.php)
added=(assets/lich-kham-cong-khai-demo.css assets/lich-kham-cong-khai-demo.js)

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
[[ -s "$stage/expected.sha256" && -s "$stage/before.sha256" ]] || exit 1
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" )
for path in "${added[@]}"; do
  [[ ! -e "$theme/$path" ]] || { echo "Demo asset already exists on production: $path" >&2; exit 1; }
done
for path in "${existing[@]}" "${added[@]}"; do [[ -s "$stage/$path" ]] || exit 1; done
php -l "$stage/inc/lich-kham-cong-khai.php"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
if command -v node >/dev/null 2>&1; then node --check "$stage/assets/lich-kham-cong-khai-demo.js"; fi

backup="/home/jwhxtzru/backups/booking-proof-demo-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc"
for path in "${existing[@]}"; do cp -p "$theme/$path" "$backup/$path"; done
cd "$root"
wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]]
chmod 600 "$backup/database-before.sql"

changed=0
restore() {
  local code=$?
  trap - ERR
  if [[ "$changed" == 1 ]]; then
    for path in "${existing[@]}"; do cp -p "$backup/$path" "$theme/$path" || true; done
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Admin demo deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap restore ERR

changed=1
for path in "${added[@]}"; do cp -p "$stage/$path" "$theme/$path"; done
cp -p "$stage/inc/lich-kham-cong-khai.php" "$theme/inc/lich-kham-cong-khai.php"
( cd "$theme" && sha256sum --check --status "$stage/expected.sha256" )
php -l "$theme/inc/lich-kham-cong-khai.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

curl --fail --silent --show-error --location --max-time 30 \
  "https://mathanoibacninh.com/?proof_demo_qa=$(date +%s)" > "$backup/public-home.html"
if grep -Fq 'Khách mẫu' "$backup/public-home.html"; then
  echo 'Synthetic labels leaked into the public homepage.' >&2
  false
fi
curl --fail --silent --show-error --location --max-time 30 \
  'https://mathanoibacninh.com/wp-admin/admin-ajax.php?action=ec_public_proof_next' > "$backup/public-api.json"
if grep -Fq 'Khách mẫu' "$backup/public-api.json"; then
  echo 'Synthetic labels leaked into the public API.' >&2
  false
fi

trap - ERR
printf 'BACKUP=%s\n' "$backup"
