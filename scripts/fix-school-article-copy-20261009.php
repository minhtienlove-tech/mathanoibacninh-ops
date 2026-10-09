<?php
/** Apply two reviewed factual corrections to the published school article via WP-CLI. */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$post_id = 1748;
$slug    = 'kham-mat-hoc-duong-thpt-chuyen-bac-giang';
$mode    = getenv( 'EYECARE_MODE' ) ?: 'preflight';
$post    = get_post( $post_id );

if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || $slug !== $post->post_name ) {
	WP_CLI::error( 'Article identity or status does not match the reviewed target.' );
}

if ( 'rollback' === $mode ) {
	$backup = getenv( 'EYECARE_BACKUP' );
	$file   = $backup ? $backup . '/post-content.before.html' : '';
	if ( ! $file || ! is_file( $file ) || ! is_readable( $file ) ) {
		WP_CLI::error( 'Missing original article content for selective rollback.' );
	}
	$result = wp_update_post( array( 'ID' => $post_id, 'post_content' => file_get_contents( $file ) ), true );
	if ( is_wp_error( $result ) || $post_id !== $result ) {
		WP_CLI::error( 'Unable to restore original article content.' );
	}
	WP_CLI::success( 'Restored original article content from backup.' );
	return;
}

if ( ! in_array( $mode, array( 'preflight', 'apply' ), true ) ) {
	WP_CLI::error( 'Unknown mode.' );
}

$replacements = array(
	'Phường Bắc Giang, Thành phố Bắc Ninh' => 'Phường Bắc Giang, Tỉnh Bắc Ninh',
	'Ảnh chỉ ghi lại hoạt động; không công bố kết quả khám hoặc danh tính học sinh.' => 'Ảnh ghi lại hoạt động; bài viết không nêu tên hoặc kết quả khám của từng học sinh.',
);

$content = $post->post_content;
foreach ( $replacements as $old => $new ) {
	if ( 1 !== substr_count( $content, $old ) || 0 !== substr_count( $content, $new ) ) {
		WP_CLI::error( 'Expected exactly one original sentence and no existing replacement.' );
	}
	$content = str_replace( $old, $new, $content );
}

if ( 'preflight' === $mode ) {
	WP_CLI::success( 'Article identity and both exact replacements verified.' );
	return;
}

$result = wp_update_post( array( 'ID' => $post_id, 'post_content' => $content ), true );
if ( is_wp_error( $result ) || $post_id !== $result ) {
	WP_CLI::error( 'Could not update article content.' );
}

$updated = get_post( $post_id );
if ( ! $updated || $content !== $updated->post_content || 0 !== (int) $updated->post_author || 'pending-author-and-medical-review' !== get_post_meta( $post_id, '_eyecare_content_review_status', true ) ) {
	WP_CLI::error( 'Updated article content or attribution metadata did not verify.' );
}

WP_CLI::success( 'Corrected article address and photo caption; attribution remains pending review.' );
