#!/usr/bin/env bash
# Chạy trên máy local từ thư mục website-ops sau khi commit: dựng stage từ commit, tải lên server
# rồi chạy deploy-api-docs-20261030.sh. STAGE_ONLY=<dir> chỉ dựng stage ở local.
set -euo pipefail
commit=${1:-HEAD}
# Bản đang chạy trên production (đã đối chiếu hash 30/10).
deployed=${2:-923a1da}
changed_files=(functions.php inc/api-dat-lich-admin.php)
new_files=(inc/tai-lieu-api.php content/api-dat-lich.md content/sdk/ec-booking.mjs content/sdk/EcBookingClient.php.txt)
# Chỉ để chạy test trên server, không chép vào theme.
test_support=(inc/api-dat-lich.php)
stage=$(mktemp -d)
trap 'rm -rf "$stage"' EXIT
out="$stage/api-docs-20261030"
mkdir -p "$out/inc" "$out/content/sdk" "$out/tests"
for file in "${changed_files[@]}" "${new_files[@]}" "${test_support[@]}"; do git show "$commit:theme/eyecare-child/$file" > "$out/$file"; done
git show "$commit:theme/eyecare-child/tests/api-docs-test.php" > "$out/tests/api-docs-test.php"
git show "$commit:scripts/deploy-api-docs-20261030.sh" > "$out/deploy.sh"
( for file in "${changed_files[@]}"; do printf '%s  %s\n' "$(git show "$deployed:theme/eyecare-child/$file" | sha256sum | cut -d' ' -f1)" "$file"; done ) > "$out/before.sha256"
( cd "$out" && sha256sum "${changed_files[@]}" "${new_files[@]}" ) > "$out/after.sha256"
( cd "$out" && sha256sum "${changed_files[@]}" "${new_files[@]}" "${test_support[@]}" tests/*.php deploy.sh before.sha256 after.sha256 ) > "$out/expected.sha256"
if [[ -n "${STAGE_ONLY:-}" ]]; then
  rm -rf "$STAGE_ONLY" && cp -a "$out" "$STAGE_ONLY" && echo "Staged to $STAGE_ONLY" && exit 0
fi
ssh mathanoibacninh 'mkdir -m 700 -p ~/staging && rm -rf ~/staging/api-docs-20261030'
scp -r -q "$out" mathanoibacninh:staging/
ssh mathanoibacninh 'bash ~/staging/api-docs-20261030/deploy.sh'
