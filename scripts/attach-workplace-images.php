<?php
/** WP-CLI: assign one generated featured image per workplace article. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    exit( 1 );
}

$source_dir = isset( $args[0] ) ? rtrim( $args[0], '/' ) : '';
$manifest_path = isset( $args[1] ) ? $args[1] : '';
$items = json_decode( file_get_contents( $manifest_path ), true );
if ( ! is_array( $items ) || count( $items ) !== 15 ) {
    WP_CLI::error( 'Expected exactly 15 manifest entries.' );
}

$ready = array();
foreach ( $items as $item ) {
    $slug = $item['slug'];
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    $file = $source_dir . '/' . $slug . '.webp';
    if ( ! $post || 'publish' !== $post->post_status || get_post_thumbnail_id( $post->ID ) ) {
        WP_CLI::error( 'Post missing, unpublished, or already has featured image: ' . $slug );
    }
    if ( ! is_readable( $file ) || filesize( $file ) < 30000 || filesize( $file ) > 200000 ) {
        WP_CLI::error( 'Image missing or unexpected size: ' . $file );
    }
    $ready[] = array( $post, $file, $item );
}
WP_CLI::log( 'Preflight passed for all 15 posts and images.' );

require_once ABSPATH . 'wp-admin/includes/image.php';
foreach ( $ready as $entry ) {
    list( $post, $file, $item ) = $entry;
    $filename = basename( $file );
    $upload = wp_upload_bits( $filename, null, file_get_contents( $file ) );
    if ( ! empty( $upload['error'] ) ) {
        WP_CLI::error( 'Upload failed for ' . $filename . ': ' . $upload['error'] );
    }
    $attachment_id = wp_insert_attachment( array(
        'post_mime_type' => 'image/webp',
        'post_title'     => $item['title'],
        'post_excerpt'   => 'Ảnh minh họa được tạo bằng AI; không phải nhân viên hoặc cơ sở của bệnh viện.',
        'post_status'    => 'inherit',
    ), $upload['file'], $post->ID, true );
    if ( is_wp_error( $attachment_id ) ) {
        WP_CLI::error( 'Attachment failed for ' . $filename . ': ' . $attachment_id->get_error_message() );
    }
    wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
    update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Ảnh minh họa: ' . $item['title'] );
    if ( ! set_post_thumbnail( $post->ID, $attachment_id ) ) {
        WP_CLI::error( 'Could not assign thumbnail to post ' . $post->ID );
    }
    WP_CLI::log( $post->ID . ' ' . $attachment_id . ' ' . $filename );
}
WP_CLI::success( 'Assigned 15 distinct featured images.' );
