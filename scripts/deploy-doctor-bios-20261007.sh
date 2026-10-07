#!/usr/bin/env bash
set -euo pipefail

root=/home/jwhxtzru/public_html
theme="$root/wp-content/themes/eyecare-child"
stage=/home/jwhxtzru/staging/doctor-bios-20261007
template=single-eyecare_bac_si.php
expected_template=17ff13e8b44722dcea7389664576d8297c535d66d6782a1c95ce17690660fb67
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active'; exit 1; }

[[ $(sha256sum "$theme/$template" | cut -d' ' -f1) == "$expected_template" ]] || {
	echo 'Production template diverged'; exit 1;
}
php -l "$stage/$template"
cd "$root"

ids=(337 336 338 674 673 675)
slugs=(le-nhu-tung dang-cong-hai bui-van-canh tran-khanh-thang nguyen-dang-dat tran-duc-thinh)
for i in "${!ids[@]}"; do
	id=${ids[$i]}
	slug=${slugs[$i]}
	test -s "$stage/$slug.html"
	[[ $(wp post get "$id" --field=post_name) == "$slug" ]] || { echo "Slug mismatch for $id"; exit 1; }
	[[ $(wp post get "$id" --field=post_status) == publish ]] || { echo "Unpublished $id"; exit 1; }
	[[ -z $(wp post get "$id" --field=post_content) ]] || { echo "Content no longer empty for $id"; exit 1; }
done

backup="/home/jwhxtzru/backups/doctor-bios-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup"
cp -p "$theme/$template" "$backup/$template"
for id in "${ids[@]}"; do
	wp post get "$id" --field=post_content > "$backup/$id-before.html"
done
wp post meta get 337 _eyecare_chuc_danh > "$backup/337-chuc-danh-before.txt"
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"

rollback_on_error() {
	status=$?
	trap - ERR
	cp -p "$backup/$template" "$theme/$template" || true
	wp post meta update 337 _eyecare_chuc_danh "$(cat "$backup/337-chuc-danh-before.txt")" --quiet || true
	for i in "${!ids[@]}"; do
		wp post update "${ids[$i]}" --post_content="$(cat "$backup/${ids[$i]}-before.html")" --quiet || true
	done
	wp cache flush --quiet || true
	wp litespeed-purge all >/dev/null 2>&1 || true
	echo "Deployment failed; attempted selective rollback from $backup" >&2
	exit "$status"
}
trap rollback_on_error ERR

cp "$stage/$template" "$theme/$template"
php -l "$theme/$template"
# Gỡ chức danh quản trị chưa có văn bản xác nhận, giữ vai trò chuyên môn.
wp post meta update 337 _eyecare_chuc_danh 'CỐ VẤN CHUYÊN MÔN' --quiet
for i in "${!ids[@]}"; do
	id=${ids[$i]}
	slug=${slugs[$i]}
	wp post update "$id" --post_content="$(cat "$stage/$slug.html")" --quiet
	# wp post update bỏ LF cuối qua command substitution; wp post get thêm LF
	# khi in ra. So byte đầu ra công khai của WP với tệp nguồn.
	wp post get "$id" --field=post_content | cmp -s - "$stage/$slug.html"
	echo "PUBLISHED $id $slug"
done
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
trap - ERR
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=restore %s, six before.html files and 337-chuc-danh-before.txt, then purge caches\n' "$template"
