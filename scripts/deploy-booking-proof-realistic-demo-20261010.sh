#!/usr/bin/env bash
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-proof-realistic-demo-20261010
lock=/home/jwhxtzru/website-ops-deploy.lock
file=inc/lich-kham-cong-khai.php

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
[[ -s "$stage/expected.sha256" && -s "$stage/before.sha256" && -s "$stage/$file" ]] || exit 1
( cd "$stage" && sha256sum --check --status expected.sha256 )
( cd "$theme" && sha256sum --check --status "$stage/before.sha256" )
php -l "$stage/$file"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"

backup="/home/jwhxtzru/backups/booking-proof-realistic-demo-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc"
cp -p "$theme/$file" "$backup/$file"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]]
chmod 600 "$backup/database-before.sql"

changed=0
restore() {
  local code=$?
  trap - ERR
  if [[ "$changed" == 1 ]]; then
    cp -p "$backup/$file" "$theme/$file" || true
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Demo-name deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap restore ERR

changed=1
cp -p "$stage/$file" "$theme/$file"
( cd "$theme" && sha256sum --check --status "$stage/expected.sha256" )
php -l "$theme/$file"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

curl --fail --silent --show-error --location --max-time 30 \
  "https://mathanoibacninh.com/?proof_demo_name_qa=$(date +%s)" > "$backup/public-home.html"
if grep -Fq 'DỮ LIỆU MẪU' "$backup/public-home.html"; then
  echo 'Demo badge leaked into the public homepage.' >&2
  false
fi
curl --fail --silent --show-error --location --max-time 30 \
  'https://mathanoibacninh.com/wp-admin/admin-ajax.php?action=ec_public_proof_next' > "$backup/public-api.json"
if grep -Fq '(mẫu)' "$backup/public-api.json"; then
  echo 'Synthetic label leaked into the public API.' >&2
  false
fi

trap - ERR
printf 'BACKUP=%s\n' "$backup"
