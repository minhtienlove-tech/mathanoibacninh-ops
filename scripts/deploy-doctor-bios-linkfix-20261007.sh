#!/usr/bin/env bash
set -euo pipefail

root=/home/jwhxtzru/public_html
stage=/home/jwhxtzru/staging/doctor-bios-linkfix-20261007
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9 || { echo 'Another deployment is active'; exit 1; }
cd "$root"

ids=(337 336 338 674 673 675)
slugs=(le-nhu-tung dang-cong-hai bui-van-canh tran-khanh-thang nguyen-dang-dat tran-duc-thinh)
expected=(
	15ece9f776c5239c3d95a2d7e5ea739b9b9814fbb4318aad417bbacfb13baf1b
	1fc9aaf2e2072c6e34a9eef7015bec41c3f051114f7b8cd184acd95ecca8c102
	d5594a8b457527ea71eabe0b87da6ef1815aafc66108aa3d81aaee756e14854d
	5bfd7f6c6d5cbb303393a628c8140ccb8e8c09482e9325e0523aefaf0d221279
	376933706d8140980602115445efa359f5f9e8b5f551701246fa1fb534b826c0
	24bc0279702e72b35ad9ff7ea3be5c5c9ce0684d94ba55b20ed48f13e546e74d
)
for i in "${!ids[@]}"; do
	test -s "$stage/${slugs[$i]}.html"
	actual=$(wp post get "${ids[$i]}" --field=post_content | sha256sum | cut -d' ' -f1)
	[[ "$actual" == "${expected[$i]}" ]] || { echo "Content diverged for ${ids[$i]}"; exit 1; }
done

backup="/home/jwhxtzru/backups/doctor-bios-linkfix-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup"
for id in "${ids[@]}"; do
	wp post get "$id" --field=post_content > "$backup/$id-before.html"
done
wp db export "$backup/database-before.sql" --quiet
test -s "$backup/database-before.sql"

rollback_on_error() {
	status=$?
	trap - ERR
	for id in "${ids[@]}"; do
		wp post update "$id" --post_content="$(cat "$backup/$id-before.html")" --quiet || true
	done
	wp cache flush --quiet || true
	wp litespeed-purge all >/dev/null 2>&1 || true
	echo "Update failed; attempted rollback from $backup" >&2
	exit "$status"
}
trap rollback_on_error ERR

for i in "${!ids[@]}"; do
	id=${ids[$i]}
	slug=${slugs[$i]}
	wp post update "$id" --post_content="$(cat "$stage/$slug.html")" --quiet
	wp post get "$id" --field=post_content | cmp -s - "$stage/$slug.html"
	echo "UPDATED $id $slug"
done
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
trap - ERR
printf 'BACKUP=%s\n' "$backup"
