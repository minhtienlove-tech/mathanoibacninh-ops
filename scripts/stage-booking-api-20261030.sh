#!/usr/bin/env bash
# Chạy trên máy local từ thư mục website-ops sau khi commit: dựng thư mục stage từ commit,
# tải lên server rồi chạy deploy-booking-api-20261030.sh dưới khóa deploy.
set -euo pipefail
commit=${1:-HEAD}
changed_files=(functions.php inc/dat-lich-kham.php inc/lien-he-noi.php inc/sheets-dat-lich.php inc/zalo-dat-lich.php assets/lien-he-noi.css assets/lien-he-noi.js)
new_files=(inc/api-dat-lich.php inc/api-dat-lich-admin.php inc/dat-lich-nhung.php assets/dat-lich-nhung.js)
# Chỉ để chạy test trên server, không chép vào theme.
test_support=(inc/gmail-dat-lich.php inc/quan-ly-dat-lich.php inc/zbs-admin.php inc/zbs-benh-nhan.php)
stage=$(mktemp -d)
trap 'rm -rf "$stage"' EXIT
mkdir -p "$stage/booking-api-20261030/inc" "$stage/booking-api-20261030/assets" "$stage/booking-api-20261030/tests"
out="$stage/booking-api-20261030"
for file in "${changed_files[@]}" "${new_files[@]}" "${test_support[@]}"; do git show "$commit:theme/eyecare-child/$file" > "$out/$file"; done
for test in $(git ls-tree --name-only "$commit" theme/eyecare-child/tests/ | grep '/booking-.*\.php$'); do git show "$commit:$test" > "$out/tests/$(basename "$test")"; done
git show "$commit:scripts/deploy-booking-api-20261030.sh" > "$out/deploy.sh"
# Hash production đã đối chiếu ngày 30/10 (file CRLF trên server; functions.php có thêm seo-hubs).
# Server khác các hash này thì deploy dừng trước khi ghi.
git show "$commit:scripts/booking-api-20261030-prod-before.sha256" > "$out/before.sha256"
( cd "$out" && sha256sum "${changed_files[@]}" "${new_files[@]}" ) > "$out/after.sha256"
( cd "$out" && sha256sum "${changed_files[@]}" "${new_files[@]}" "${test_support[@]}" tests/*.php deploy.sh after.sha256 before.sha256 ) > "$out/expected.sha256"
if [[ -n "${STAGE_ONLY:-}" ]]; then
  rm -rf "$STAGE_ONLY" && cp -a "$out" "$STAGE_ONLY" && echo "Staged to $STAGE_ONLY" && exit 0
fi
ssh mathanoibacninh 'mkdir -m 700 -p ~/staging && rm -rf ~/staging/booking-api-20261030'
scp -r -q "$out" mathanoibacninh:staging/
ssh mathanoibacninh 'bash ~/staging/booking-api-20261030/deploy.sh'
