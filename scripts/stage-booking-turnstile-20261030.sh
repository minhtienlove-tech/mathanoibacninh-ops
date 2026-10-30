#!/usr/bin/env bash
# Chạy trên máy local từ thư mục website-ops sau khi commit: dựng stage từ commit, tải lên server
# rồi chạy deploy-booking-turnstile-20261030.sh. STAGE_ONLY=<dir> chỉ dựng stage ở local.
set -euo pipefail
commit=${1:-HEAD}
changed_files=(inc/dat-lich-kham.php inc/lien-he-noi.php assets/lien-he-noi.js page-quyen-rieng-tu.php)
new_files=(inc/chong-bot-dat-lich.php)
# Chỉ để chạy test trên server, không chép vào theme.
test_support=(inc/api-dat-lich.php inc/gmail-dat-lich.php inc/quan-ly-dat-lich.php inc/sheets-dat-lich.php inc/zalo-dat-lich.php inc/zbs-admin.php inc/zbs-benh-nhan.php)
stage=$(mktemp -d)
trap 'rm -rf "$stage"' EXIT
out="$stage/booking-turnstile-20261030"
mkdir -p "$out/inc" "$out/assets" "$out/tests"
for file in "${changed_files[@]}" "${new_files[@]}" "${test_support[@]}"; do git show "$commit:theme/eyecare-child/$file" > "$out/$file"; done
for test in $(git ls-tree --name-only "$commit" theme/eyecare-child/tests/ | grep '/booking-.*\.php$'); do git show "$commit:$test" > "$out/tests/$(basename "$test")"; done
git show "$commit:scripts/deploy-booking-turnstile-20261030.sh" > "$out/deploy.sh"
# Hash production đã đối chiếu (trang chính sách trên server dùng CRLF, nội dung giống Git).
git show "$commit:scripts/booking-turnstile-20261030-prod-before.sha256" > "$out/before.sha256"
( cd "$out" && sha256sum "${changed_files[@]}" "${new_files[@]}" ) > "$out/after.sha256"
( cd "$out" && sha256sum "${changed_files[@]}" "${new_files[@]}" "${test_support[@]}" tests/*.php deploy.sh before.sha256 after.sha256 ) > "$out/expected.sha256"
if [[ -n "${STAGE_ONLY:-}" ]]; then
  rm -rf "$STAGE_ONLY" && cp -a "$out" "$STAGE_ONLY" && echo "Staged to $STAGE_ONLY" && exit 0
fi
ssh mathanoibacninh 'mkdir -m 700 -p ~/staging && rm -rf ~/staging/booking-turnstile-20261030'
scp -r -q "$out" mathanoibacninh:staging/
ssh mathanoibacninh 'bash ~/staging/booking-turnstile-20261030/deploy.sh'
