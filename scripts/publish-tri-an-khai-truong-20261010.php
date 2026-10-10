<?php
/** Publish the supplied three-program announcement via WP-CLI. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$base = (string) getenv( 'EYECARE_BACKUP' );
$mode = (string) getenv( 'EYECARE_MODE' );
if ( ! in_array( $mode, array( 'preflight', 'apply' ), true ) || ! is_dir( $base ) ) {
	WP_CLI::error( 'Invalid mode or staging/backup directory.' );
}

$metadata = json_decode( (string) file_get_contents( $base . '/post.json' ), true );
$manifest = json_decode( (string) file_get_contents( $base . '/media-manifest.json' ), true );
$template = trim( (string) file_get_contents( $base . '/content-template.html' ) );
if ( ! is_array( $metadata ) || ! is_array( $manifest ) || ! is_array( $manifest['assets'] ?? null ) || 4 !== count( $manifest['assets'] ) ) {
	WP_CLI::error( 'Publication inputs are incomplete.' );
}

$slug = 'ba-chuong-trinh-tri-an-12-10-2026';
$expected_url = home_url( '/tin-tuc/uu-dai-va-ho-tro-nguoi-benh/' . $slug . '/' );
$title = trim( (string) ( $metadata['title'] ?? '' ) );
$excerpt = trim( (string) ( $metadata['excerpt'] ?? '' ) );
if ( $slug !== ( $metadata['slug'] ?? '' ) || 28 !== (int) ( $metadata['category_id'] ?? 0 ) ||
	$title !== wp_strip_all_tags( $title ) || $excerpt !== wp_strip_all_tags( $excerpt ) ||
	strlen( $title ) < 50 || strlen( $excerpt ) < 90 || strlen( $excerpt ) > 450 ) {
	WP_CLI::error( 'Article metadata failed validation.' );
}

$category = get_term( 28, 'category' );
$intro = get_post( 1688 );
if ( ! $category || is_wp_error( $category ) || 'uu-dai-va-ho-tro-nguoi-benh' !== $category->slug || 4 !== (int) $category->parent ||
	! $intro || 'post' !== $intro->post_type || 'publish' !== $intro->post_status ) {
	WP_CLI::error( 'Expected category #28 or introduction post #1688 changed.' );
}
global $wpdb;
$collision = $wpdb->get_var( $wpdb->prepare(
	"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type IN ('post', 'page') LIMIT 1",
	$slug
) );
if ( $collision ) {
	WP_CLI::error( 'Slug already belongs to post/page #' . $collision );
}

$old_intro_start = 'Bài mở đầu này chưa công bố mức giảm giá hoặc quyền lợi cụ thể nào.';
$new_intro_start = 'Bài mở đầu này giải thích cách kiểm tra điều kiện; thông báo từng chương trình được đăng thành bài riêng.';
$old_intro_end = 'Hiện chuyên mục mới có bài giới thiệu, chưa có chương trình cụ thể được thông báo tại đây. Để tìm hiểu quy trình khám, bạn có thể xem <a href="/dat-lich-kham/">trang đặt lịch</a>; để tham khảo thông tin sức khỏe mắt, xem <a href="/kien-thuc/">Kiến thức nhãn khoa</a>.';
$new_intro_end = 'Chương trình tri ân từ 12/10 đến hết 20/11/2026 gồm khám mắt miễn phí, tặng gọng khi mua mắt kính theo điều kiện áp dụng và phẫu thuật miễn phí cho người bệnh thuộc nhóm ưu tiên đủ điều kiện. Xem <a href="/tin-tuc/uu-dai-va-ho-tro-nguoi-benh/' . $slug . '/">thông báo và câu hỏi thường gặp</a> để đối chiếu thời hạn, phạm vi và cách đăng ký. Bạn cũng có thể xem <a href="/dat-lich-kham/">trang đặt lịch</a> hoặc gọi bệnh viện để xác nhận trước khi đến.';
if ( 1 !== substr_count( $intro->post_content, $old_intro_start ) || 1 !== substr_count( $intro->post_content, $old_intro_end ) ) {
	WP_CLI::error( 'Introduction wording changed; refusing to overwrite it.' );
}

$assets = array();
foreach ( $manifest['assets'] as $asset ) {
	$key = (string) ( $asset['key'] ?? '' );
	$filename = (string) ( $asset['filename'] ?? '' );
	$alt = trim( (string) ( $asset['alt'] ?? '' ) );
	$hash = (string) ( $asset['sha256'] ?? '' );
	$file = $base . '/images/' . $filename;
	$image = is_file( $file ) ? getimagesize( $file ) : false;
	if ( ! in_array( $key, array( 'overview', 'exam', 'glasses', 'surgery' ), true ) || isset( $assets[ $key ] ) ||
		! preg_match( '/\A[a-z0-9-]+\.webp\z/', $filename ) || ! preg_match( '/\A[0-9a-f]{64}\z/', $hash ) ||
		strlen( $alt ) < 40 || ! $image || IMAGETYPE_WEBP !== (int) $image[2] ||
		(int) $image[0] !== (int) $asset['width'] || (int) $image[1] !== (int) $asset['height'] ||
		hash_file( 'sha256', $file ) !== $hash || filesize( $file ) > 1000000 ) {
		WP_CLI::error( 'Media validation failed: ' . $filename );
	}
	$assets[ $key ] = $asset;
}
if ( count( $assets ) !== 4 || 'overview' !== ( $manifest['featured_key'] ?? '' ) ||
	str_contains( $template, '{{PHOTO:overview}}' ) ||
	! str_contains( $template, '[faq]' ) || ! str_contains( $template, '[/faq]' ) ||
	! str_contains( $template, 'https://mathanoibacninh.com/dat-lich-kham/' ) ||
	! str_contains( $template, 'https://mathanoibacninh.com/giay-phep-hoat-dong/' ) ) {
	WP_CLI::error( 'Template structure or required links failed validation.' );
}
foreach ( array( 'exam', 'glasses', 'surgery' ) as $key ) {
	if ( 1 !== substr_count( $template, '{{PHOTO:' . $key . '}}' ) ) {
		WP_CLI::error( 'Missing or repeated image placeholder: ' . $key );
	}
}
if ( function_exists( 'eyecare_tach_faq' ) && 6 !== count( eyecare_tach_faq( preg_replace( '/.*?\[faq\](.*?)\[\/faq\].*/s', '$1', $template ) ) ) ) {
	WP_CLI::error( 'Expected six complete FAQ answers.' );
}
if ( count( preg_split( '/\s+/u', wp_strip_all_tags( $template ) ) ) < 600 ) {
	WP_CLI::error( 'Article is unexpectedly short.' );
}
if ( 'preflight' === $mode ) {
	WP_CLI::success( 'Preflight passed: category, unused URL, intro, four WebP images, links and FAQ; no write.' );
	return;
}

$post_id = wp_insert_post( array(
	'post_type'      => 'post',
	'post_status'    => 'draft',
	'post_author'    => 0,
	'post_name'      => $slug,
	'post_title'     => $title,
	'post_excerpt'   => $excerpt,
	'post_content'   => '<p>Đang chuẩn bị nội dung và hình ảnh.</p>',
	'post_category'  => array( 28 ),
	'comment_status' => 'closed',
	'ping_status'    => 'closed',
), true );
if ( is_wp_error( $post_id ) || ! $post_id ) {
	WP_CLI::error( 'Could not create post draft.' );
}
if ( false === file_put_contents( $base . '/post-id', $post_id . "\n", LOCK_EX ) ) {
	WP_CLI::error( 'Could not record draft ID; draft retained.' );
}
update_post_meta( $post_id, '_eyecare_content_review_status', 'pending-author-and-medical-review' );
update_post_meta( $post_id, '_eyecare_home_featured_until', '2026-11-20' );

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$ids = array();
foreach ( $assets as $key => $asset ) {
	$filename = (string) $asset['filename'];
	$tmp = wp_tempnam( $filename );
	if ( ! $tmp || ! copy( $base . '/images/' . $filename, $tmp ) ) {
		WP_CLI::error( 'Could not stage image: ' . $filename );
	}
	$id = media_handle_sideload( array(
		'name' => $filename, 'tmp_name' => $tmp, 'type' => 'image/webp',
		'size' => filesize( $tmp ), 'error' => 0,
	), $post_id, (string) $asset['alt'] );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( 'Could not import image ' . $filename . ': ' . $id->get_error_message() );
	}
	$ids[ $key ] = (int) $id;
	if ( false === file_put_contents( $base . '/media-ids.tsv', $key . "\t" . $id . "\n", FILE_APPEND | LOCK_EX ) ) {
		WP_CLI::error( 'Could not record image ID; draft retained.' );
	}
	update_post_meta( $id, '_wp_attachment_image_alt', (string) $asset['alt'] );
	$result = wp_update_post( array( 'ID' => $id, 'post_excerpt' => (string) $asset['caption'] ), true );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( 'Could not save image caption: ' . $filename );
	}
}
if ( ! set_post_thumbnail( $post_id, $ids['overview'] ) ) {
	WP_CLI::error( 'Could not set featured image.' );
}

$body = $template;
foreach ( array( 'exam', 'glasses', 'surgery' ) as $key ) {
	$img = wp_get_attachment_image( $ids[ $key ], 'full', false, array( 'loading' => 'lazy', 'decoding' => 'async' ) );
	if ( ! $img ) {
		WP_CLI::error( 'Could not render image: ' . $key );
	}
	$figure = '<figure class="eyecare-promo-photo">' . $img . '<figcaption>' . esc_html( $assets[ $key ]['caption'] ) . '</figcaption></figure>';
	$body = str_replace( '{{PHOTO:' . $key . '}}', $figure, $body );
}
$body = wp_kses_post( $body );
if ( str_contains( $body, '{{' ) || 3 !== substr_count( $body, '<img ' ) || 3 !== substr_count( $body, 'alt="' ) ) {
	WP_CLI::error( 'Rendered article image verification failed.' );
}
$result = wp_update_post( array( 'ID' => $post_id, 'post_content' => wp_slash( $body ) ), true );
if ( is_wp_error( $result ) ) {
	WP_CLI::error( 'Could not save article body.' );
}
$draft = get_post( $post_id );
if ( ! $draft || 'draft' !== $draft->post_status || $slug !== $draft->post_name ||
	! in_array( 28, wp_get_post_categories( $post_id ), true ) ||
	(int) get_post_thumbnail_id( $post_id ) !== $ids['overview'] ||
	3 !== substr_count( $draft->post_content, '<img ' ) ||
	'2026-11-20' !== get_post_meta( $post_id, '_eyecare_home_featured_until', true ) ||
	'pending-author-and-medical-review' !== get_post_meta( $post_id, '_eyecare_content_review_status', true ) ) {
	WP_CLI::error( 'Saved draft verification failed.' );
}
foreach ( $ids as $key => $id ) {
	$attachment = get_post( $id );
	if ( ! $attachment || get_post_meta( $id, '_wp_attachment_image_alt', true ) !== $assets[ $key ]['alt'] ||
		$attachment->post_excerpt !== $assets[ $key ]['caption'] ||
		! str_contains( (string) get_attached_file( $id ), (string) $assets[ $key ]['filename'] ) ) {
		WP_CLI::error( 'Imported image metadata failed verification: ' . $key );
	}
}

$result = wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ), true );
if ( is_wp_error( $result ) || 'publish' !== get_post_status( $post_id ) || get_permalink( $post_id ) !== $expected_url ) {
	WP_CLI::error( 'Publication or permalink verification failed.' );
}

$new_intro = str_replace( $old_intro_start, $new_intro_start, $intro->post_content );
$new_intro = str_replace( $old_intro_end, $new_intro_end, $new_intro );
$result = wp_update_post( array( 'ID' => 1688, 'post_content' => wp_slash( $new_intro ) ), true );
if ( is_wp_error( $result ) || get_post( 1688 )->post_content !== $new_intro ) {
	WP_CLI::error( 'Article published, but intro update failed; repair intro from backup.' );
}
WP_CLI::success( 'Published post #' . $post_id . ' with four images and linked introduction: ' . $expected_url );
