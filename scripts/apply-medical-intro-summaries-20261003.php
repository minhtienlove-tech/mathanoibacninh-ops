<?php
/**
 * Guarded correction of 12 medical article introductions and short answers.
 *
 * Usage (read-only): MEDICAL_INTRO_MANIFEST=/absolute/path/intro-summary-manifest.json
 *                   wp eval-file /absolute/path/apply-medical-intro-summaries-20261003.php
 *
 * Apply only after physician signoff, a fresh DB backup, and under the deployment lock:
 * MEDICAL_INTRO_APPLY=1 MEDICAL_INTRO_REVIEW_APPROVED=1
 * MEDICAL_INTRO_DB_BACKUP=/home/jwhxtzru/backups/.../database-pre-medical.sql
 * MEDICAL_INTRO_BACKUP_DIR=/home/jwhxtzru/backups/medical-intros-YYYYMMDD-HHMMSS
 * MEDICAL_INTRO_MANIFEST=/absolute/path/intro-summary-manifest.json wp eval-file ...
 *
 * This file is prepared for review; no content is changed without all gates.
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WP_CLI' ) ) {
	return;
}

$manifest_path = getenv( 'MEDICAL_INTRO_MANIFEST' );
if ( ! $manifest_path ) {
	$manifest_path = dirname( __DIR__ ) . '/docs/faq-medical-review-20261003/intro-summary-manifest.json';
}
$raw = file_get_contents( $manifest_path );
if ( false === $raw ) {
	WP_CLI::error( 'Cannot read medical-intro manifest.' );
}
$manifest = json_decode( $raw, true );
if ( ! is_array( $manifest ) || ! isset( $manifest['posts'] ) || ! is_array( $manifest['posts'] ) ) {
	WP_CLI::error( 'Invalid medical-intro manifest.' );
}

$expected_ids = array( 566, 567, 568, 569, 571, 572, 575, 621, 628, 654, 656, 661 );
$seen = array();
$ready = array();
foreach ( $manifest['posts'] as $entry ) {
	$id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;
	if ( ! in_array( $id, $expected_ids, true ) || isset( $seen[ $id ] ) ) {
		WP_CLI::error( 'Unexpected or duplicate article ID: ' . $id );
	}
	$seen[ $id ] = true;
	$post = get_post( $id );
	if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ||
		! isset( $entry['slug'] ) || $post->post_name !== $entry['slug'] ) {
		WP_CLI::error( 'Article identity/status mismatch: ' . $id );
	}
	$old_content = $post->post_content;
	$old_hash = hash( 'sha256', $old_content );
	if ( empty( $entry['expected_sha256'] ) || ! hash_equals( $entry['expected_sha256'], $old_hash ) ) {
		WP_CLI::error( 'Content changed since snapshot: ' . $id . ' (' . $old_hash . ')' );
	}
	if ( empty( $entry['replacements'] ) || ! is_array( $entry['replacements'] ) ) {
		WP_CLI::error( 'No replacements in manifest: ' . $id );
	}
	$new_content = $old_content;
	$sections = array();
	foreach ( $entry['replacements'] as $index => $change ) {
		$section = isset( $change['section'] ) ? $change['section'] : '';
		$old = isset( $change['old'] ) && is_string( $change['old'] ) ? $change['old'] : '';
		$new = isset( $change['new'] ) && is_string( $change['new'] ) ? $change['new'] : '';
		if ( ! in_array( $section, array( 'intro', 'short_answer' ), true ) ||
			isset( $sections[ $section ] ) || '' === $old || '' === $new || $old === $new ||
			1 !== (int) ( isset( $change['count'] ) ? $change['count'] : 0 ) ||
			1 !== substr_count( $new_content, $old ) ||
			0 !== strpos( $old, '<p>' ) || '</p>' !== substr( $old, -4 ) ||
			0 !== strpos( $new, '<p>' ) || '</p>' !== substr( $new, -4 ) ) {
			WP_CLI::error( 'Replacement precondition failed: ' . $id . ':' . $index );
		}
		if ( 'short_answer' === $section &&
			( 0 !== strpos( $old, '<p><strong>Trả lời ngắn:</strong>' ) ||
			0 !== strpos( $new, '<p><strong>Trả lời ngắn:</strong>' ) ) ) {
			WP_CLI::error( 'Short-answer marker mismatch: ' . $id );
		}
		$sections[ $section ] = true;
		$new_content = str_replace( $old, $new, $new_content );
	}
	if ( ! isset( $sections['short_answer'] ) ) {
		WP_CLI::error( 'Missing short-answer replacement: ' . $id );
	}
	foreach ( array( '[faq]', '[/faq]', '[tac-gia]', '<p>', '</p>' ) as $marker ) {
		if ( substr_count( $old_content, $marker ) !== substr_count( $new_content, $marker ) ) {
			WP_CLI::error( 'Article structure changed: ' . $id . ' ' . $marker );
		}
	}
	$new_hash = hash( 'sha256', $new_content );
	if ( empty( $entry['expected_new_sha256'] ) || ! hash_equals( $entry['expected_new_sha256'], $new_hash ) ) {
		WP_CLI::error( 'Expected new hash mismatch: ' . $id );
	}
	$ready[ $id ] = array(
		'post' => $post,
		'new_content' => $new_content,
		'old_hash' => $old_hash,
		'new_hash' => $new_hash,
		'replacement_count' => count( $entry['replacements'] ),
		'reviewer_name' => isset( $entry['reviewer_name'] ) ? trim( (string) $entry['reviewer_name'] ) : '',
		'review_date' => isset( $entry['review_date'] ) ? trim( (string) $entry['review_date'] ) : '',
	);
}
sort( $expected_ids, SORT_NUMERIC );
$actual_ids = array_keys( $ready );
sort( $actual_ids, SORT_NUMERIC );
if ( $expected_ids !== $actual_ids ) {
	WP_CLI::error( 'Manifest must include all and only 12 audited articles.' );
}

WP_CLI::log( 'Preflight passed: 12 published articles, ' . array_sum( array_column( $ready, 'replacement_count' ) ) . ' exact paragraph replacements.' );
foreach ( $ready as $id => $item ) {
	WP_CLI::log( $id . ' ' . substr( $item['old_hash'], 0, 12 ) . ' -> ' . substr( $item['new_hash'], 0, 12 ) .
		' sections=' . $item['replacement_count'] . ' physician-review=' .
		( '' !== $item['reviewer_name'] && '' !== $item['review_date'] ? 'recorded' : 'PENDING' ) );
}
if ( '1' !== getenv( 'MEDICAL_INTRO_APPLY' ) ) {
	WP_CLI::log( 'Dry run only. No WordPress changes.' );
	return;
}

if ( '1' !== getenv( 'MEDICAL_INTRO_REVIEW_APPROVED' ) ) {
	WP_CLI::error( 'Physician approval gate is missing; no content changed.' );
}
foreach ( $ready as $id => $item ) {
	if ( '' === $item['reviewer_name'] || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $item['review_date'] ) ) {
		WP_CLI::error( 'Physician reviewer name/date missing or invalid for article ' . $id );
	}
}

$backup_dir = getenv( 'MEDICAL_INTRO_BACKUP_DIR' );
$db_backup = getenv( 'MEDICAL_INTRO_DB_BACKUP' );
if ( ! $backup_dir || ! preg_match( '#^/home/jwhxtzru/backups/[A-Za-z0-9_-]+$#', $backup_dir ) || file_exists( $backup_dir ) ) {
	WP_CLI::error( 'Specify a new safe backup directory under ~/backups/.' );
}
if ( ! $db_backup || ! preg_match( '#^/home/jwhxtzru/backups/[A-Za-z0-9_/-]+\.sql$#', $db_backup ) ||
	! is_file( $db_backup ) || filesize( $db_backup ) < 1024 ) {
	WP_CLI::error( 'Fresh database backup file under ~/backups/ is required.' );
}
if ( ! mkdir( $backup_dir, 0700, false ) ) {
	WP_CLI::error( 'Cannot create backup directory.' );
}
$originals = array();
foreach ( $ready as $id => $item ) {
	$post = $item['post'];
	$originals[] = array(
		'id' => $id,
		'slug' => $post->post_name,
		'old_sha256' => $item['old_hash'],
		'new_sha256' => $item['new_hash'],
		'post_content' => $post->post_content,
		'post_modified' => $post->post_modified,
		'post_modified_gmt' => $post->post_modified_gmt,
		'reviewer_name_in_manifest' => $item['reviewer_name'],
		'review_date_in_manifest' => $item['review_date'],
	);
}
$originals_json = wp_json_encode( $originals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
$files = array( 'manifest.json' => $raw, 'original-post-content.json' => $originals_json );
foreach ( $files as $name => $body ) {
	if ( false === $body || strlen( $body ) !== file_put_contents( $backup_dir . '/' . $name, $body ) ) {
		WP_CLI::error( 'Backup write failed; no content changed.' );
	}
	chmod( $backup_dir . '/' . $name, 0600 );
}

WP_CLI::log( 'Backups ready: ' . $backup_dir . ' ; DB: ' . $db_backup );
$updated = array();
foreach ( $ready as $id => $item ) {
	$result = wp_update_post( array( 'ID' => $id, 'post_content' => $item['new_content'] ), true );
	if ( is_wp_error( $result ) || (int) $result !== $id ) {
		WP_CLI::error( 'Update failed for ' . $id . '. Already updated IDs: ' . implode( ',', $updated ) . '. Restore from backup JSON before retry.' );
	}
	clean_post_cache( $id );
	$actual_hash = hash( 'sha256', get_post( $id )->post_content );
	if ( ! hash_equals( $item['new_hash'], $actual_hash ) ) {
		WP_CLI::error( 'Post-update hash mismatch for ' . $id . '. Restore from backup JSON before retry.' );
	}
	$updated[] = $id;
}
WP_CLI::success( 'Updated exactly 12 articles. Backup: ' . $backup_dir . '. Clear page caches and run public QA.' );
