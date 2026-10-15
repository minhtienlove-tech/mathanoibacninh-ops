#!/usr/bin/env bash
set -euo pipefail
exec 9>/home/jwhxtzru/website-ops-deploy.lock
flock -n 9
cd /home/jwhxtzru/public_html
backup=/home/jwhxtzru/backups/doctor-canh-alt-$(date +%Y%m%d-%H%M%S)
mkdir -p "$backup"
wp db export "$backup/database-before.sql" --quiet
wp post meta get 1697 _wp_attachment_image_alt > "$backup/alt-before.txt"
cp -p wp-content/uploads/2026/08/2-5-scaled.png "$backup/"
wp post meta update 1697 _wp_attachment_image_alt 'Ảnh giới thiệu BSCKI. Bùi Văn Cảnh – Phó Giám đốc chuyên môn Bệnh viện Mắt Hà Nội – Bắc Ninh' --quiet
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true
printf 'BACKUP=%s\n' "$backup"
