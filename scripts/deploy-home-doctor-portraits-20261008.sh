#!/usr/bin/env bash
set -euo pipefail

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/home-doctor-portraits-20261008
expected_old_php=5b0692eb03134a87a4c7ac5bc32cecf7697b9b87e7a3498d80fc43c2d001511b
expected_old_css=2003e025365e7ade26f59a4c9b627494c9f1321e3fcd9bfb5593c4b66279c21e
expected_new_php=e577667923f5cb3f13277b8737cf3dbc25db683b48b41840e0f4e71bcc7c7c1b
expected_new_css=ce85566abc373f7eec476f85c8b405bf23aa324d3ce095b13af09a185707e8bf

exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active' >&2; exit 1; }

[[ $(sha256sum "$theme/front-page.php" | cut -d' ' -f1) == "$expected_old_php" ]] || {
  echo 'Production front-page.php diverged' >&2; exit 1;
}
[[ $(sha256sum "$theme/style.css" | cut -d' ' -f1) == "$expected_old_css" ]] || {
  echo 'Production style.css diverged' >&2; exit 1;
}
[[ $(sha256sum "$stage/front-page.php" | cut -d' ' -f1) == "$expected_new_php" ]] || {
  echo 'Staged front-page.php does not match reviewed source' >&2; exit 1;
}
[[ $(sha256sum "$stage/style.css" | cut -d' ' -f1) == "$expected_new_css" ]] || {
  echo 'Staged style.css does not match reviewed source' >&2; exit 1;
}

php -l "$stage/front-page.php"
cd "$root"
backup="/home/jwhxtzru/backups/home-doctor-portraits-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup"
cp -p "$theme/front-page.php" "$backup/front-page.php"
cp -p "$theme/style.css" "$backup/style.css"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"

rollback_on_error() {
  status=$?
  trap - ERR
  cp -p "$backup/front-page.php" "$theme/front-page.php" || true
  cp -p "$backup/style.css" "$theme/style.css" || true
  wp cache flush --quiet || true
  wp litespeed-purge all >/dev/null 2>&1 || true
  echo "Deployment failed; restored both theme files from $backup" >&2
  exit "$status"
}
trap rollback_on_error ERR

cp -p "$stage/front-page.php" "$theme/front-page.php"
cp -p "$stage/style.css" "$theme/style.css"
php -l "$theme/front-page.php"
[[ $(sha256sum "$theme/front-page.php" | cut -d' ' -f1) == "$expected_new_php" ]]
[[ $(sha256sum "$theme/style.css" | cut -d' ' -f1) == "$expected_new_css" ]]
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=copy front-page.php and style.css from %s, then purge caches\n' "$backup"
