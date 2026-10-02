<?php
/** Add reciprocal, topic-matched links to articles lacking editorial inbound links. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_LINK_TRUST_59' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI gate required.' );
}
$plan_file = (string) getenv( 'EYECARE_LINK_TRUST_PLAN' );
$backup_dir = realpath( (string) getenv( 'EYECARE_LINK_TRUST_BACKUP' ) );
$plan = json_decode( (string) file_get_contents( $plan_file ), true );
if ( ! is_array( $plan ) || 59 !== count( $plan ) || ! $backup_dir ) {
	throw new RuntimeException( 'Expected 59 planned links and an existing backup directory.' );
}
$sources = array();
$targets = array();
foreach ( $plan as $row ) {
	$source = get_post( (int) $row['source_id'] );
	$target = get_post( (int) $row['target_id'] );
	if ( ! $source instanceof WP_Post || ! $target instanceof WP_Post || 'post' !== $source->post_type ||
		'post' !== $target->post_type || 'publish' !== $source->post_status || 'publish' !== $target->post_status ||
		$source->ID === $target->ID || $source->post_name !== $row['source_slug'] || $target->post_name !== $row['target_slug'] ||
		isset( $targets[$target->ID] ) || ! str_contains( $target->post_content, get_permalink( $source->ID ) ) ||
		str_contains( $source->post_content, get_permalink( $target->ID ) ) ) {
		throw new RuntimeException( 'Link identity or reciprocal relevance failed: ' . $row['target_slug'] );
	}
	$targets[$target->ID] = true;
	$sources[$source->ID][] = $target;
}
if ( '1' !== getenv( 'EYECARE_LINK_TRUST_59_EXECUTE' ) ) {
	WP_CLI::success( count( $sources ) . ' sources and 59 reciprocal targets passed; no writes.' );
	return;
}
$original = array();
foreach ( $sources as $source_id => $rows ) {
	$original[$source_id] = get_post_field( 'post_content', $source_id, 'raw' );
}
$backup_file = $backup_dir . '/original-linked-post-content.json';
if ( false === file_put_contents( $backup_file, wp_json_encode( $original, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) ) {
	throw new RuntimeException( 'Failed to back up original post content.' );
}
foreach ( $sources as $source_id => $rows ) {
	$items = array();
	foreach ( $rows as $target ) {
		$items[] = '<li><a href="' . esc_url( get_permalink( $target->ID ) ) . '">' . esc_html( get_the_title( $target->ID ) ) . '</a></li>';
	}
	$block = "\n<!-- eyecare-link-trust-59 -->\n<section class=\"eyecare-bai-doc-tiep\" aria-label=\"Bài đọc tiếp theo cùng chủ đề\"><h2>Bài đọc tiếp theo cùng chủ đề</h2><ul>" . implode( '', $items ) . "</ul></section>\n";
	$result = wp_update_post( array( 'ID' => $source_id, 'post_content' => $original[$source_id] . $block ), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( 'Post update failed for ' . $source_id . ': ' . $result->get_error_message() );
	}
}
WP_CLI::success( 'Added 59 relevant editorial links across ' . count( $sources ) . ' articles; backup: ' . $backup_file );
