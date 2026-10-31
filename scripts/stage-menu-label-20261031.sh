#!/usr/bin/env bash
# Chạy trên máy local từ thư mục website-ops sau khi commit: dựng stage từ commit, tải lên server
# rồi chạy deploy-menu-label-20261031.sh. STAGE_ONLY=<dir> chỉ dựng stage ở local.
set -euo pipefail
commit=${1:-HEAD}
# Bản đang chạy trên production (đã đối chiếu hash 31/10).
deployed=${2:-4398717}
changed_files=(content/api-dat-lich.md inc/chong-bot-dat-lich.php inc/chong-bot-dang-nhap.php)
# Chỉ để chạy test trên server, không chép vào theme: mọi file inc/*.php và SDK ở commit,
# để test mới cần file mới (như inc/zbs-nhac-lich.php) không bị thiếu.
mapfile -t test_support < <(git ls-tree -r --name-only "$commit" theme/eyecare-child/inc/ theme/eyecare-child/content/sdk/ | sed 's|^theme/eyecare-child/||' | grep -E '^(inc/[^/]+\.php|content/sdk/.+)$')
stage=$(mktemp -d)
trap 'rm -rf "$stage"' EXIT
out="$stage/menu-label-20261031"
mkdir -p "$out/inc" "$out/content/sdk" "$out/tests"
for file in "${changed_files[@]}" "${test_support[@]}"; do git show "$commit:theme/eyecare-child/$file" > "$out/$file"; done
for test in $(git ls-tree --name-only "$commit" theme/eyecare-child/tests/ | grep -E '/(booking-.*|login-guard-test|api-docs-test)\.php$'); do git show "$commit:$test" > "$out/tests/$(basename "$test")"; done
git show "$commit:scripts/deploy-menu-label-20261031.sh" > "$out/deploy.sh"
( for file in "${changed_files[@]}"; do printf '%s  %s\n' "$(git show "$deployed:theme/eyecare-child/$file" | sha256sum | cut -d' ' -f1)" "$file"; done ) > "$out/before.sha256"
( cd "$out" && sha256sum "${changed_files[@]}" ) > "$out/after.sha256"
( cd "$out" && sha256sum "${test_support[@]}" content/api-dat-lich.md tests/*.php deploy.sh before.sha256 after.sha256 ) > "$out/expected.sha256"
if [[ -n "${STAGE_ONLY:-}" ]]; then
  rm -rf "$STAGE_ONLY" && cp -a "$out" "$STAGE_ONLY" && echo "Staged to $STAGE_ONLY" && exit 0
fi
ssh mathanoibacninh 'mkdir -m 700 -p ~/staging && rm -rf ~/staging/menu-label-20261031'
scp -r -q "$out" mathanoibacninh:staging/
ssh mathanoibacninh 'bash ~/staging/menu-label-20261031/deploy.sh'
