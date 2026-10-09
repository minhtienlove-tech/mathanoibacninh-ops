#!/usr/bin/env bash
# Publish one reviewed Bắc Giang eye-care article without changing theme/plugin files.
#
# Stage this script, content.html, featured.webp and expected.sha256 in:
#   /home/jwhxtzru/staging/bac-giang-eye-care-20261009/
# Create expected.sha256 from the FINAL local source files before uploading:
#   sha256sum content.html featured.webp > expected.sha256
# The manifest must be uploaded with the two assets; do not derive it from the
# already-uploaded server files. Run on the WordPress server with both variables:
#   EYECARE_POST_TITLE='...' EYECARE_POST_EXCERPT='...' bash publish-bac-giang-eye-care-20261009.sh
# On failure the new post remains (or is returned to) draft, and imported media
# is preserved for inspection. No post, media or database is deleted/restored.
set -euo pipefail
umask 077

root=/home/jwhxtzru/public_html
stage=/home/jwhxtzru/staging/bac-giang-eye-care-20261009
lock=/home/jwhxtzru/website-ops-deploy.lock
slug=benh-vien-mat-uy-tin-tai-bac-giang
title=${EYECARE_POST_TITLE:-}
excerpt=${EYECARE_POST_EXCERPT:-}

[[ -n "$title" && -n "$excerpt" ]] || {
  echo 'Set EYECARE_POST_TITLE and EYECARE_POST_EXCERPT to the reviewed Vietnamese title and excerpt.' >&2
  exit 1
}

exec 9>"$lock"
flock -n 9 || { echo 'Another deployment is active.' >&2; exit 1; }

manifest_hash() {
  local file=$1 count hash
  [[ -s "$stage/expected.sha256" ]] || { echo 'Missing expected.sha256.' >&2; exit 1; }
  count=$(awk -v file="$file" '$2 == file { n++ } END { print n + 0 }' "$stage/expected.sha256")
  [[ "$count" == 1 ]] || { echo "Missing/duplicate SHA-256 entry: $file" >&2; exit 1; }
  hash=$(awk -v file="$file" '$2 == file { print $1 }' "$stage/expected.sha256")
  [[ "$hash" =~ ^[[:xdigit:]]{64}$ ]] || { echo "Invalid SHA-256 entry: $file" >&2; exit 1; }
  printf '%s' "${hash,,}"
}

check_sha() {
  local file=$1 expected=$2 actual
  [[ -s "$file" ]] || { echo "Missing or empty file: $file" >&2; exit 1; }
  actual=$(sha256sum "$file" | cut -d' ' -f1)
  [[ "$actual" == "$expected" ]] || { echo "SHA-256 mismatch: $file" >&2; exit 1; }
}

content_sha=$(manifest_hash content.html)
image_sha=$(manifest_hash featured.webp)
check_sha "$stage/content.html" "$content_sha"
check_sha "$stage/featured.webp" "$image_sha"
[[ $(awk 'NF { n++ } END { print n + 0 }' "$stage/expected.sha256") == 2 ]] || {
  echo 'expected.sha256 must contain exactly two entries.' >&2
  exit 1
}

backup="/home/jwhxtzru/backups/bac-giang-eye-care-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$backup"
cp -p "$stage/content.html" "$backup/content.html"
cp -p "$stage/featured.webp" "$backup/featured.webp"
cp -p "$stage/expected.sha256" "$backup/expected.sha256"
check_sha "$backup/content.html" "$content_sha"
check_sha "$backup/featured.webp" "$image_sha"

# This WP-CLI-only helper reads immutable backup inputs. Preflight does not
# change WordPress. Apply creates a draft, attaches media, verifies the draft,
# then publishes as the last WordPress write.
cat > "$backup/publish.php" <<'PHP'
<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}

$base         = getenv( 'EYECARE_BACKUP' );
$mode         = getenv( 'EYECARE_MODE' );
$expected_html = getenv( 'EYECARE_CONTENT_SHA' );
$expected_img  = getenv( 'EYECARE_IMAGE_SHA' );
$title        = trim( (string) getenv( 'EYECARE_POST_TITLE' ) );
$excerpt      = trim( (string) getenv( 'EYECARE_POST_EXCERPT' ) );
$slug         = 'benh-vien-mat-uy-tin-tai-bac-giang';
$target       = home_url( '/khu-vuc/kham-mat-bac-giang/' );
$expected_url = home_url( '/kien-thuc/' . $slug . '/' );
$html_path    = $base . '/content.html';
$image_path   = $base . '/featured.webp';
$alt          = 'Minh họa gia đình cân nhắc chọn bệnh viện hoặc phòng khám mắt';
$caption      = 'Hình minh họa được tạo bằng AI; không mô tả cơ sở y tế hoặc nhân sự có thật.';

if ( ! in_array( $mode, array( 'preflight', 'apply' ), true ) || ! is_dir( $base ) ) {
    WP_CLI::error( 'Invalid publication mode or backup directory.' );
}
foreach ( array( $html_path => $expected_html, $image_path => $expected_img ) as $file => $hash ) {
    if ( ! is_file( $file ) || ! preg_match( '/\A[0-9a-f]{64}\z/', $hash ) || hash_file( 'sha256', $file ) !== $hash ) {
        WP_CLI::error( 'Source file changed or failed SHA-256 verification: ' . $file );
    }
}
if ( strlen( $title ) < 30 || strlen( $title ) > 240 || strlen( $excerpt ) < 80 || strlen( $excerpt ) > 900 ) {
    WP_CLI::error( 'Title or excerpt length is outside the reviewed range.' );
}
if ( strlen( $title ) !== strlen( wp_strip_all_tags( $title ) ) || strlen( $excerpt ) !== strlen( wp_strip_all_tags( $excerpt ) ) ) {
    WP_CLI::error( 'Title and excerpt must contain plain text only.' );
}

$page = get_post( 55 );
if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status || 'kham-mat-bac-giang' !== $page->post_name || get_permalink( $page ) !== $target ) {
    WP_CLI::error( 'Bắc Giang target page #55 is missing or its URL changed.' );
}
$parent = get_term( 3, 'category' );
$child  = get_term( 16, 'category' );
if ( ! $parent || is_wp_error( $parent ) || 'kien-thuc' !== $parent->slug ||
     ! $child || is_wp_error( $child ) || 'chuan-bi-kham-bao-hiem' !== $child->slug || 3 !== (int) $child->parent ) {
    WP_CLI::error( 'Expected category #3/#16 structure changed.' );
}
global $wpdb;
$collision = $wpdb->get_var( $wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type IN ('post','page') LIMIT 1",
    $slug
) );
if ( $collision ) {
    WP_CLI::error( 'Slug is already used by post/page #' . $collision );
}

$body = trim( (string) file_get_contents( $html_path ) );
$clean_body = wp_kses_post( $body );
if ( $body !== $clean_body ) {
    WP_CLI::error( 'HTML would be altered by WordPress sanitization; review the draft source.' );
}
$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $body ) ) );
if ( ! is_array( $words ) || count( $words ) < 800 || count( $words ) > 5000 ||
     ! preg_match( '~<a\b[^>]*href=["\']https://mathanoibacninh\.com/khu-vuc/kham-mat-bac-giang/["\']~i', $body ) ) {
    WP_CLI::error( 'Article length or required link to page #55 failed.' );
}
$image_info = getimagesize( $image_path );
if ( ! is_array( $image_info ) || IMAGETYPE_WEBP !== (int) $image_info[2] ||
     $image_info[0] < 800 || $image_info[1] < 450 || filesize( $image_path ) > 25000000 ) {
    WP_CLI::error( 'Featured image is not a suitable WebP asset.' );
}
if ( 'preflight' === $mode ) {
    WP_CLI::success( sprintf( 'Preflight passed: %d words, page #55, categories #3/#16, WebP %dx%d; no write.', count( $words ), $image_info[0], $image_info[1] ) );
    return;
}

$post_id = wp_insert_post( array(
    'post_type'     => 'post',
    'post_status'   => 'draft',
    'post_author'   => 0,
    'post_name'     => $slug,
    'post_title'    => $title,
    'post_excerpt'  => $excerpt,
    'post_content'  => $clean_body,
    'post_category' => array( 3, 16 ),
    'comment_status' => 'closed',
    'ping_status'    => 'closed',
), true );
if ( is_wp_error( $post_id ) || ! $post_id ) {
    WP_CLI::error( 'Could not create draft: ' . ( is_wp_error( $post_id ) ? $post_id->get_error_message() : 'unknown error' ) );
}
if ( false === file_put_contents( $base . '/post-id', $post_id . "\n", LOCK_EX ) ) {
    WP_CLI::error( 'Could not record draft ID; draft retained.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$tmp = wp_tempnam( $slug . '.webp' );
if ( ! $tmp || ! copy( $image_path, $tmp ) ) {
    WP_CLI::error( 'Could not prepare the image for media import; draft retained.' );
}
$attachment_id = media_handle_sideload( array(
    'name'     => $slug . '.webp',
    'tmp_name' => $tmp,
    'type'     => 'image/webp',
    'size'     => filesize( $tmp ),
    'error'    => 0,
), $post_id, $alt );
if ( is_wp_error( $attachment_id ) ) {
    WP_CLI::error( 'Media import failed; draft retained: ' . $attachment_id->get_error_message() );
}
if ( false === file_put_contents( $base . '/media-id', $attachment_id . "\n", LOCK_EX ) ) {
    WP_CLI::error( 'Could not record imported media ID; draft and media retained.' );
}
$attachment_update = wp_update_post( array(
    'ID'           => $attachment_id,
    'post_excerpt' => $caption,
), true );
if ( is_wp_error( $attachment_update ) || ! update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt ) ||
     ! update_post_meta( $attachment_id, '_eyecare_ai_illustration', '1' ) ||
     ! set_post_thumbnail( $post_id, $attachment_id ) ) {
    WP_CLI::error( 'Could not set image caption/alt/featured image; draft retained.' );
}

$draft = get_post( $post_id );
$assigned = wp_get_post_categories( $post_id );
if ( ! $draft || 'draft' !== $draft->post_status || 'post' !== $draft->post_type ||
     $slug !== $draft->post_name || 0 !== (int) $draft->post_author ||
     $title !== $draft->post_title || $excerpt !== $draft->post_excerpt ||
     $clean_body !== $draft->post_content || ! in_array( 3, $assigned, true ) ||
     ! in_array( 16, $assigned, true ) || (int) get_post_thumbnail_id( $post_id ) !== (int) $attachment_id ||
     $alt !== get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ||
     '1' !== get_post_meta( $attachment_id, '_eyecare_ai_illustration', true ) ||
     $caption !== get_post_field( 'post_excerpt', $attachment_id ) ) {
    WP_CLI::error( 'Draft content, link, author, category or image verification failed; draft retained.' );
}
if ( ! str_contains( $draft->post_content, $target ) ) {
    WP_CLI::error( 'Draft lost the link to the Bắc Giang page; draft retained.' );
}

$result = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ), true );
if ( is_wp_error( $result ) || (int) $result !== (int) $post_id ||
     'publish' !== get_post_status( $post_id ) || get_permalink( $post_id ) !== $expected_url ) {
    WP_CLI::error( 'Publication/permalink verification failed; return post to draft.' );
}
WP_CLI::success( 'Published post #' . $post_id . ' with featured media #' . $attachment_id . ': ' . $expected_url );
PHP

cd "$root"
export EYECARE_BACKUP="$backup" EYECARE_CONTENT_SHA="$content_sha" EYECARE_IMAGE_SHA="$image_sha"
export EYECARE_POST_TITLE="$title" EYECARE_POST_EXCERPT="$excerpt"
EYECARE_MODE=preflight wp eval-file "$backup/publish.php"

wp db export "$backup/database-before.sql" --quiet
[[ -s "$backup/database-before.sql" ]] || { echo 'Database backup failed.' >&2; exit 1; }
chmod 600 "$backup/database-before.sql"

on_failure() {
  local code=$? id status current_slug
  trap - ERR
  if [[ -s "$backup/post-id" ]]; then
    read -r id < "$backup/post-id"
    if [[ "$id" =~ ^[0-9]+$ ]]; then
      status=$(wp post get "$id" --field=post_status 2>/dev/null || true)
      current_slug=$(wp post get "$id" --field=post_name 2>/dev/null || true)
      if [[ "$status" == publish && "$current_slug" == "$slug" ]]; then
        wp post update "$id" --post_status=draft >/dev/null 2>&1 || true
      fi
    fi
  fi
  wp cache flush --quiet || true
  wp litespeed-purge all >/dev/null 2>&1 || true
  echo "Publication failed. Retained draft/media and backup at: $backup" >&2
  echo 'ROLLBACK: inspect post-id/media-id; keep or unpublish the post as draft. The DB export is for disaster recovery; do not import it over later changes.' >&2
  exit "$code"
}
trap on_failure ERR

# Recheck the staged files immediately before the WordPress write.
check_sha "$stage/content.html" "$content_sha"
check_sha "$stage/featured.webp" "$image_sha"
EYECARE_MODE=apply wp eval-file "$backup/publish.php"
wp cache flush --quiet
wp litespeed-purge all >/dev/null 2>&1 || true

url="https://mathanoibacninh.com/kien-thuc/$slug/"
curl --fail --silent --show-error --location --max-time 30 \
  "$url?publish_qa=$(date +%s)" > "$backup/public-after.html"
grep -Fq "$title" "$backup/public-after.html"
grep -Fq 'https://mathanoibacninh.com/khu-vuc/kham-mat-bac-giang/' "$backup/public-after.html"

trap - ERR
printf 'POST_ID=%s\n' "$(cat "$backup/post-id")"
printf 'MEDIA_ID=%s\n' "$(cat "$backup/media-id")"
printf 'URL=%s\n' "$url"
printf 'BACKUP=%s\n' "$backup"
printf 'ROLLBACK=change only this post to draft using its post-id; imported media and backup remain for review\n'
