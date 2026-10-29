#!/usr/bin/env bash
set -euo pipefail
umask 077
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active'; exit 1; }
stage=/home/jwhxtzru/staging/zalo-oa-links-20261029
theme=/home/jwhxtzru/public_html/wp-content/themes/eyecare-child
files=('inc/lien-he-noi.php' 'assets/lien-he-noi.css' 'footer.php')
old_hashes=(824e053392b2cec9eb0fc2db9427551bb727346e595ad067488adae7b71343aa aebd27f798f672f75bc57fedc2141b8bf26bcf20e531da6400fb2ba66f35f72a e0a6ac20d1b9a20d889da408a0420eeed379fb31d96ba2af692f68e44d1a0923)
new_hashes=(c3263232371694abd2b043dff528849be0778fb16d57d6847e9b639de372781a 3ba2a567ed4dcdd72cefbb282999afc499c551b38d9c0a69a76aafe3ab3b296a b5d166d684de8a567b5cd7ab1cd675c57d5b35d0a34b37d130324636098c40df)
for i in "${!files[@]}"; do
    test "$(sha256sum "$theme/${files[i]}" | cut -d ' ' -f1)" = "${old_hashes[i]}"
    test "$(sha256sum "$stage/${files[i]}" | cut -d ' ' -f1)" = "${new_hashes[i]}"
done
php -l "$stage/inc/lien-he-noi.php"
php -l "$stage/footer.php"
backup=/home/jwhxtzru/backups/zalo-oa-links-20261029-$(date +%H%M%S)
mkdir -p "$backup/inc" "$backup/assets"
for file in "${files[@]}"; do cp -p "$theme/$file" "$backup/$file"; done
cp -p "$stage/deploy.sh" "$backup/deploy.sh"
cp -p "$stage/source-commit.txt" "$backup/source-commit.txt"
cd /home/jwhxtzru/public_html
wp db export "$backup/database.sql" --quiet
test -s "$backup/database.sql"
rollback() {
    local result=$?
    trap - ERR
    for ((i=${#files[@]}-1; i>=0; i--)); do
        if test "$(sha256sum "$theme/${files[i]}" | cut -d ' ' -f1)" = "${new_hashes[i]}"; then
            cp -p "$backup/${files[i]}" "$theme/${files[i]}"
        fi
    done
    wp eval 'do_action("litespeed_purge_all");' || true
    echo "OA links deployment failed. Backup=$backup"
    exit "$result"
}
trap rollback ERR
for i in "${!files[@]}"; do
    cp "$stage/${files[i]}" "$theme/${files[i]}.website-ops-20261029"
    chmod 644 "$theme/${files[i]}.website-ops-20261029"
    mv -f "$theme/${files[i]}.website-ops-20261029" "$theme/${files[i]}"
    test "$(sha256sum "$theme/${files[i]}" | cut -d ' ' -f1)" = "${new_hashes[i]}"
done
php -l "$theme/footer.php"
php -l "$theme/inc/lien-he-noi.php"
php -l "$theme/page-lien-he.php"
wp eval 'if (eyecare_zalo_oa_url() !== "https://zalo.me/2368653938911696310") { throw new Exception("Wrong OA URL"); } do_action("litespeed_purge_all");'
trap - ERR
printf 'BACKUP=%s\n' "$backup"
