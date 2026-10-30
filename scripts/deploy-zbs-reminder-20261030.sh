#!/usr/bin/env bash
set -euo pipefail
umask 077
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active'; exit 1; }
root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/zbs-reminder-20261030
files=('inc/zbs-nhac-lich.php' 'inc/zbs-benh-nhan.php' 'inc/zbs-admin.php' 'inc/dat-lich-kham.php' 'inc/lien-he-noi.php')
old_hashes=(ABSENT e091ffcc193da5906acdc5cf724ca87ec62ea43db1b5f6efc1570a4a197ba52e bfa650cf786c3ce219852fbd82975bd810aaecbf64e68dc59aebd6ecd873f370 1697ef31c9d5776c5af9b5b879ebb81cee9f6e458570257ac658910e0624c90f 8207d0ce94122261c629cbe92c66f8bb4afebe6de377f0389954d2e58b06e059)
new_hashes=(37e4d438f0c3e580bc31ad12bf9851c6652fea5e8344813adf90be7bd0f3c6de d7f93545cacde01453b37e7ca57e685cd5cda6b210ed3698059d9acc113e0634 01fb1c5ec372d44b04fb49051757fba671be9f6b0a15d361a0f2ab6bb7aa3af0 37cc7391724ed89443df54750e3032bf2c6e06c73a4c77a401f265a29a703eef 9ebce711f957e6e5e202fd667f492ee20323db3c2be979c1842acdf50ab75d2b)
for i in "${!files[@]}"; do
 if [[ "${old_hashes[i]}" == ABSENT ]]; then test ! -e "$theme/${files[i]}"; else
  test "$(sha256sum "$theme/${files[i]}" | cut -d ' ' -f1)" = "${old_hashes[i]}"
 fi
 test "$(sha256sum "$stage/${files[i]}" | cut -d ' ' -f1)" = "${new_hashes[i]}"
 php -l "$stage/${files[i]}"
done
for test in booking-zbs-test booking-zbs-reminder-test booking-zbs-admin-test booking-antispam-test booking-api-test booking-sheets-test booking-admin-reschedule-test; do php "$stage/tests/$test.php"; done
php -l "$stage/configure.php"
php -l "$stage/rollback-settings.php"
backup=/home/jwhxtzru/backups/zbs-reminder-20261030-$(date +%H%M%S)
mkdir -p "$backup/inc"
for i in "${!files[@]}"; do
 if [[ "${old_hashes[i]}" != ABSENT ]]; then cp -p "$theme/${files[i]}" "$backup/${files[i]}"; fi
done
cp -p "$stage/source-commit.txt" "$backup/source-commit.txt"
cp -p "$stage/deploy.sh" "$backup/deploy.sh"
cd "$root"
wp db export "$backup/database.sql" --quiet
test -s "$backup/database.sql"
changed=0
rollback() {
 local result=$?
 trap - ERR
 wp eval-file "$stage/rollback-settings.php" "$backup" || true
 if [[ "$changed" == 1 ]]; then
  for ((i=${#files[@]}-1; i>=0; i--)); do
   if [[ "${old_hashes[i]}" != ABSENT ]] && test "$(sha256sum "$theme/${files[i]}" | cut -d ' ' -f1)" = "${new_hashes[i]}"; then
    cp -p "$backup/${files[i]}" "$theme/${files[i]}"
   fi
  done
 fi
 wp eval 'do_action("litespeed_purge_all");' || true
 echo "Reminder deployment failed. Backup=$backup"
 exit "$result"
}
trap rollback ERR
changed=1
for i in "${!files[@]}"; do
 cp "$stage/${files[i]}" "$theme/${files[i]}.website-ops-20261030"
 chmod 644 "$theme/${files[i]}.website-ops-20261030"
 mv -f "$theme/${files[i]}.website-ops-20261030" "$theme/${files[i]}"
 test "$(sha256sum "$theme/${files[i]}" | cut -d ' ' -f1)" = "${new_hashes[i]}"
done
wp eval-file "$stage/configure.php" "$backup"
php -l "$theme/footer.php"
php -l "$theme/page-lien-he.php"
wp eval 'do_action("litespeed_purge_all");'
trap - ERR
printf 'BACKUP=%s\n' "$backup"
