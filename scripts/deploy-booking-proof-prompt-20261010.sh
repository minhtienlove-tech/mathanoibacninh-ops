#!/usr/bin/env bash
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/booking-proof-cta-20261010
lock=/home/jwhxtzru/website-ops-deploy.lock
files=(inc/lich-kham-cong-khai.php assets/lich-kham-cong-khai.js assets/lich-kham-cong-khai.css)
demo_files=(assets/lich-kham-cong-khai-demo.js assets/lich-kham-cong-khai-demo.css)

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }

# Fail if production has changed since preflight or the staged bytes differ.
( cd "$theme" && sha256sum --check --status <<'HASHES'
627aeade8ad297aa0b11e5d6d63c8c40959e138b6194e48bc10aadaed3b963eb  inc/lich-kham-cong-khai.php
1407615ba257c861b855b3467cb58cf651d4affbce689f7ee26bcec515dde540  assets/lich-kham-cong-khai.js
5970b0712d23f0dd10a4fe48faeb0e6d04f00d8d184a07a78e98ed507dfd0cf2  assets/lich-kham-cong-khai.css
8f5e6d36e0669f2188dad86646a7776d139c2dc786979ba22a8739abe636d193  assets/lich-kham-cong-khai-demo.js
1f692806ae452e07ebf5d08ad3979cbfa19bf9f1e959e4c059510689ad55b86b  assets/lich-kham-cong-khai-demo.css
HASHES
)
( cd "$stage" && sha256sum --check --status <<'HASHES'
21b6670dd61e0e4e7966c79126630f5f88551310c397b16803ee101cef2918a9  inc/lich-kham-cong-khai.php
6323ef7e197762734d6de91fb85512e626badde18bfc1010587c05c3c28c4878  assets/lich-kham-cong-khai.js
56d0262d779dcb5832ce74d018a00907fa3fbabbd5ab4e691d6f46d9b8b4f4da  assets/lich-kham-cong-khai.css
HASHES
)
php -l "$stage/inc/lich-kham-cong-khai.php"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
php "$stage/tests/public-proof-test.php"

backup="/home/jwhxtzru/backups/booking-proof-prompt-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc" "$backup/assets"
for file in "${files[@]}" "${demo_files[@]}"; do
  cp -p "$theme/$file" "$backup/$file"
done
cd "$root"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
chmod 600 "$backup/database-before.sql"
wp option get ec_public_proof_settings --format=json > "$backup/settings-before.json"

changed=0
restore() {
  local code=$?
  trap - ERR
  if [[ "$changed" == 1 ]]; then
    for file in "${files[@]}" "${demo_files[@]}"; do
      cp -p "$backup/$file" "$theme/$file" || true
    done
    wp option update ec_public_proof_settings "$(cat "$backup/settings-before.json")" --format=json --quiet || true
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap restore ERR

changed=1
for file in "${files[@]}"; do
  cp -p "$stage/$file" "$theme/$file"
done
for file in "${demo_files[@]}"; do
  rm -- "$theme/$file"
done
# Five seconds makes the first truthful notice easy to observe.
wp option patch update ec_public_proof_settings delay 5 --quiet

( cd "$theme" && sha256sum --check --status <<'HASHES'
21b6670dd61e0e4e7966c79126630f5f88551310c397b16803ee101cef2918a9  inc/lich-kham-cong-khai.php
6323ef7e197762734d6de91fb85512e626badde18bfc1010587c05c3c28c4878  assets/lich-kham-cong-khai.js
56d0262d779dcb5832ce74d018a00907fa3fbabbd5ab4e691d6f46d9b8b4f4da  assets/lich-kham-cong-khai.css
HASHES
)
for file in "${demo_files[@]}"; do test ! -e "$theme/$file"; done
php -l "$theme/inc/lich-kham-cong-khai.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

curl --fail --silent --show-error --location --max-time 30 \
  "https://mathanoibacninh.com/?proof_prompt_qa=$(date +%s)" > "$backup/public-home.html"
grep -Fq 'id="ec-public-proof"' "$backup/public-home.html"
grep -Fq 'Gửi yêu cầu trực tuyến, bệnh viện sẽ liên hệ xác nhận thời gian.' "$backup/public-home.html"
if grep -Fq 'DỮ LIỆU MẪU' "$backup/public-home.html"; then false; fi
curl --fail --silent --show-error --location --max-time 30 \
  'https://mathanoibacninh.com/wp-admin/admin-ajax.php?action=ec_public_proof_next' > "$backup/public-api.json"
grep -Fq '"mode":"booking_prompt"' "$backup/public-api.json"
if grep -Fq '"label":"Anh ' "$backup/public-api.json"; then false; fi

trap - ERR
printf 'BACKUP=%s\n' "$backup"
