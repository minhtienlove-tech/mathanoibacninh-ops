#!/usr/bin/env bash
set -euo pipefail
umask 077
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/zalo-verification-20261026
file=zalo_verifierVywq9BdPEIXugxygYOrl3ac4n1cbyXz3DZOu.html
url="https://mathanoibacninh.com/$file"
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }
( cd "$stage" && sha256sum --check --status expected.sha256 )
echo 'a8a437b1ab61c47657d3680f1125e67e1dc892679397cf2357e4ced78de1dc73  inc/seo-trang-giu-cho.php' | ( cd "$theme" && sha256sum --check --status )
test ! -e "$theme/verification/$file"
php -l "$stage/inc/seo-trang-giu-cho.php"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
backup="/home/jwhxtzru/backups/zalo-verification-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 -p "$backup/inc"
cp -p "$theme/inc/seo-trang-giu-cho.php" "$backup/inc/"
cp -p "$stage/verification/$file" "$backup/"
cd "$root"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"
chmod 600 "$backup/database-before.sql"
changed=0
on_failure() {
  local code=$?
  trap - ERR
  if [[ "$changed" == 1 ]]; then
    cp -p "$backup/inc/seo-trang-giu-cho.php" "$theme/inc/"
    wp cache flush --quiet || true
    wp litespeed-purge all >/dev/null 2>&1 || true
  fi
  echo "Zalo verification deployment failed. Backup: $backup" >&2
  exit "$code"
}
trap on_failure ERR
mkdir -p "$theme/verification"
install -m 644 "$stage/verification/$file" "$theme/verification/$file"
install -m 644 "$stage/inc/seo-trang-giu-cho.php" "$theme/inc/.seo-trang-giu-cho-zalo-next.php"
changed=1
mv -f "$theme/inc/.seo-trang-giu-cho-zalo-next.php" "$theme/inc/seo-trang-giu-cho.php"
php -l "$theme/inc/seo-trang-giu-cho.php"
cmp "$stage/inc/seo-trang-giu-cho.php" "$theme/inc/seo-trang-giu-cho.php"
cmp "$stage/verification/$file" "$theme/verification/$file"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
status=$(curl --silent --show-error --max-time 30 --dump-header "$backup/verification-headers.txt" --output "$backup/verification-response.html" --write-out '%{http_code}' "$url")
test "$status" = 200
cmp "$stage/verification/$file" "$backup/verification-response.html"
head_status=$(curl --silent --show-error --max-time 30 --head --output "$backup/verification-head.txt" --write-out '%{http_code}' "$url")
test "$head_status" = 200
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'VERIFIER_HTTP=200; HEAD=200; body matches supplied HTML\n'
