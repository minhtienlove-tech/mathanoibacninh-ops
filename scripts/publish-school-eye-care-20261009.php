<?php
/** Publish the reviewed THPT Chuyên Bắc Giang event post via WP-CLI only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$base = (string) getenv( 'EYECARE_BACKUP' );
$mode = (string) getenv( 'EYECARE_MODE' );
if ( ! in_array( $mode, array( 'preflight', 'apply' ), true ) || ! is_dir( $base ) ) {
	WP_CLI::error( 'Invalid publication mode or backup directory.' );
}

$manifest = json_decode( (string) file_get_contents( $base . '/media-manifest.json' ), true );
$post_data = json_decode( (string) file_get_contents( $base . '/post.json' ), true );
$template = trim( (string) file_get_contents( $base . '/content-template.html' ) );
if ( ! is_array( $manifest ) || ! is_array( $post_data ) || ! is_array( $manifest['assets'] ?? null ) || 14 !== count( $manifest['assets'] ) ) {
	WP_CLI::error( 'Missing or malformed publication inputs.' );
}

$slug = 'kham-mat-hoc-duong-thpt-chuyen-bac-giang';
$expected_url = home_url( '/tin-tuc/hoat-dong-cong-dong/' . $slug . '/' );
$title = trim( (string) ( $post_data['title'] ?? '' ) );
$excerpt = trim( (string) ( $post_data['excerpt'] ?? '' ) );
$featured_source = (string) ( $manifest['featured_source'] ?? '' );
$inline_sources = $manifest['inline_sources'] ?? array();
if ( $slug !== ( $manifest['slug'] ?? '' ) || 26 !== (int) ( $manifest['category_id'] ?? 0 ) ||
	strlen( $title ) < 25 || strlen( $title ) > 220 || strlen( $excerpt ) < 90 || strlen( $excerpt ) > 500 ||
	$title !== wp_strip_all_tags( $title ) || $excerpt !== wp_strip_all_tags( $excerpt ) ||
	! is_array( $inline_sources ) || 4 !== count( $inline_sources ) ) {
	WP_CLI::error( 'Article metadata failed preflight.' );
}

$term = get_term( 26, 'category' );
if ( ! $term || is_wp_error( $term ) || 'hoat-dong-cong-dong' !== $term->slug || 4 !== (int) $term->parent ) {
	WP_CLI::error( 'Expected community activity category #26 changed.' );
}
global $wpdb;
$collision = $wpdb->get_var( $wpdb->prepare(
	"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type IN ('post', 'page') LIMIT 1",
	$slug
) );
if ( $collision ) {
	WP_CLI::error( 'Slug already belongs to post/page #' . $collision );
}

$assets = array();
$names = array();
foreach ( $manifest['assets'] as $asset ) {
	$source = (string) ( $asset['source'] ?? '' );
	$filename = (string) ( $asset['filename'] ?? '' );
	$hash = (string) ( $asset['sha256'] ?? '' );
	$alt = trim( (string) ( $asset['alt'] ?? '' ) );
	if ( ! preg_match( '/\ADSCF[0-9]{4}\.(?:jpg|png)\z/', $source ) ||
		! preg_match( '/\Athpt-chuyen-bac-giang-[a-z0-9-]+\.webp\z/', $filename ) ||
		! preg_match( '/\A[0-9a-f]{64}\z/', $hash ) ||
		strlen( $alt ) < 25 || strlen( $alt ) > 500 ||
		isset( $assets[ $source ] ) || isset( $names[ $filename ] ) ) {
		WP_CLI::error( 'Invalid or duplicate media manifest entry: ' . $source );
	}
	$file = $base . '/images/' . $filename;
	$image = is_file( $file ) ? getimagesize( $file ) : false;
	if ( ! $image || IMAGETYPE_WEBP !== (int) $image[2] ||
		(int) $image[0] !== (int) $asset['width'] || (int) $image[1] !== (int) $asset['height'] ||
		hash_file( 'sha256', $file ) !== $hash || filesize( $file ) > 1000000 ) {
		WP_CLI::error( 'Media file failed size, type, dimensions or SHA-256 check: ' . $filename );
	}
	$assets[ $source ] = $asset;
	$names[ $filename ] = true;
}

if ( ! isset( $assets[ $featured_source ] ) || count( array_unique( $inline_sources ) ) !== count( $inline_sources ) ||
	in_array( $featured_source, $inline_sources, true ) || 1 !== substr_count( $template, '{{GALLERY}}' ) ) {
	WP_CLI::error( 'Featured image or gallery placeholders are invalid.' );
}
foreach ( $inline_sources as $source ) {
	if ( ! isset( $assets[ $source ] ) || 1 !== substr_count( $template, '{{PHOTO:' . $source . '}}' ) ) {
		WP_CLI::error( 'Missing or repeated inline photo placeholder: ' . $source );
	}
}
if ( ! str_contains( $template, 'https://mathanoibacninh.com/kien-thuc/kham-mat-hoc-duong-cho-hoc-sinh/' ) ||
	! str_contains( $template, 'https://mathanoibacninh.com/khu-vuc/kham-mat-bac-giang/' ) ||
	count( preg_split( '/\s+/u', wp_strip_all_tags( $template ) ) ) < 500 ) {
	WP_CLI::error( 'Body is too short or required internal links are missing.' );
}
if ( 'preflight' === $mode ) {
	WP_CLI::success( 'Preflight passed: unused slug, category #26, 14 WebP files, article template and links; no write.' );
	return;
}

$post_id = wp_insert_post( array(
	'post_type' => 'post',
	'post_status' => 'draft',
	'post_author' => 0,
	'post_name' => $slug,
	'post_title' => $title,
	'post_excerpt' => $excerpt,
	'post_content' => '<p>Đang chuẩn bị nội dung và hình ảnh.</p>',
	'post_category' => array( 26 ),
	'comment_status' => 'closed',
	'ping_status' => 'closed',
), true );
if ( is_wp_error( $post_id ) || ! $post_id ) {
	WP_CLI::error( 'Could not create draft post.' );
}
file_put_contents( $base . '/post-id', $post_id . "\n", LOCK_EX );
if ( ! update_post_meta( $post_id, '_eyecare_content_review_status', 'pending-author-and-medical-review' ) ) {
	WP_CLI::error( 'Could not mark attribution/review as pending; draft retained.' );
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$ids = array();
foreach ( $assets as $source => $asset ) {
	$filename = $asset['filename'];
	$tmp = wp_tempnam( $filename );
	if ( ! $tmp || ! copy( $base . '/images/' . $filename, $tmp ) ) {
		WP_CLI::error( 'Could not prepare media sideload: ' . $filename );
	}
	$id = media_handle_sideload( array(
		'name' => $filename,
		'tmp_name' => $tmp,
		'type' => 'image/webp',
		'size' => filesize( $tmp ),
		'error' => 0,
	), $post_id, (string) $asset['alt'] );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( 'Media import failed for ' . $filename . ': ' . $id->get_error_message() );
	}
	$ids[ $source ] = (int) $id;
	file_put_contents( $base . '/media-ids.tsv', $source . "\t" . $id . "\n", FILE_APPEND | LOCK_EX );
	if ( ! update_post_meta( $id, '_wp_attachment_image_alt', (string) $asset['alt'] ) ) {
		WP_CLI::error( 'Could not save alt text for media #' . $id );
	}
	$caption = trim( (string) ( $asset['caption'] ?? '' ) );
	if ( $caption ) {
		$result = wp_update_post( array( 'ID' => $id, 'post_excerpt' => $caption ), true );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( 'Could not save caption for media #' . $id );
		}
	}
}
if ( ! set_post_thumbnail( $post_id, $ids[ $featured_source ] ) ) {
	WP_CLI::error( 'Could not set the featured image.' );
}

$render_photo = static function ( $source, $gallery = false ) use ( $assets, $ids ) {
	$asset = $assets[ $source ];
	$img = wp_get_attachment_image( $ids[ $source ], 'large', false, array( 'loading' => 'lazy', 'decoding' => 'async' ) );
	if ( ! $img ) {
		WP_CLI::error( 'Could not render media #' . $ids[ $source ] );
	}
	$caption = $gallery ? '' : trim( (string) ( $asset['caption'] ?? '' ) );
	return '<figure class="eyecare-school-photo">' . $img . ( $caption ? '<figcaption>' . esc_html( $caption ) . '</figcaption>' : '' ) . '</figure>';
};

$body = $template;
foreach ( $inline_sources as $source ) {
	$body = str_replace( '{{PHOTO:' . $source . '}}', $render_photo( $source ), $body );
}
$gallery = '<div class="eyecare-school-gallery">';
foreach ( array_keys( $assets ) as $source ) {
	if ( $source !== $featured_source && ! in_array( $source, $inline_sources, true ) ) {
		$gallery .= $render_photo( $source, true );
	}
}
$gallery .= '</div>';
$body = str_replace( '{{GALLERY}}', $gallery, $body );
$body = wp_kses_post( $body );
if ( str_contains( $body, '{{' ) || 13 !== substr_count( $body, '<img ' ) ||
	13 !== substr_count( $body, 'alt="' ) ||
	1 !== substr_count( $body, '<div class="eyecare-school-gallery">' ) ||
	9 !== substr_count( $gallery, '<figure ' ) ) {
	WP_CLI::error( 'Rendered article image verification failed.' );
}
$result = wp_update_post( array( 'ID' => $post_id, 'post_content' => wp_slash( $body ) ), true );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( 'Could not save article body; draft retained.' );
}
$draft = get_post( $post_id );
$assigned = wp_get_post_categories( $post_id );
if ( ! $draft || 'draft' !== $draft->post_status || $slug !== $draft->post_name ||
	$title !== $draft->post_title || $excerpt !== $draft->post_excerpt ||
	! in_array( 26, $assigned, true ) || (int) get_post_thumbnail_id( $post_id ) !== $ids[ $featured_source ] ||
	'pending-author-and-medical-review' !== get_post_meta( $post_id, '_eyecare_content_review_status', true ) ||
	13 !== substr_count( $draft->post_content, '<img ' ) ) {
	WP_CLI::error( 'Draft verification failed; draft retained.' );
}
foreach ( $ids as $source => $id ) {
	if ( get_post_meta( $id, '_wp_attachment_image_alt', true ) !== $assets[ $source ]['alt'] ||
		! str_contains( (string) get_attached_file( $id ), (string) $assets[ $source ]['filename'] ) ) {
		WP_CLI::error( 'Imported filename or alt verification failed for #' . $id );
	}
}

$result = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ), true );
if ( is_wp_error( $result ) || 'publish' !== get_post_status( $post_id ) || get_permalink( $post_id ) !== $expected_url ) {
	WP_CLI::error( 'Publication or permalink verification failed; return post to draft.' );
}
WP_CLI::success( 'Published post #' . $post_id . ' with 14 renamed/alt-tagged images: ' . $expected_url );
