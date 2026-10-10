<?php
/** Refresh the introductory offer post's excerpt after the dated announcement. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}
$post = get_post( 1688 );
$old = 'Cách đọc thông tin ưu đãi và hỗ trợ; hiện chưa thông báo chương trình cụ thể trong bài mở đầu.';
$new = 'Cách đọc điều kiện ưu đãi và tìm thông báo ba chương trình tri ân áp dụng từ 12/10 đến 20/11/2026.';
if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
	WP_CLI::error( 'Introduction post #1688 is missing or unpublished.' );
}
if ( $post->post_excerpt === $new ) {
	WP_CLI::success( 'Introduction excerpt is already current.' );
	return;
}
if ( $post->post_excerpt !== $old ) {
	WP_CLI::error( 'Introduction excerpt changed; refusing to overwrite it.' );
}
$result = wp_update_post( array( 'ID' => 1688, 'post_excerpt' => $new ), true );
if ( is_wp_error( $result ) || get_post( 1688 )->post_excerpt !== $new ) {
	WP_CLI::error( 'Could not update introduction excerpt.' );
}
WP_CLI::success( 'Introduction excerpt now points readers to the dated campaign.' );
