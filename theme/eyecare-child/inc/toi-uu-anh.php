<?php
/** Reversible, opt-in media optimization. Original files and post content stay intact. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ec_media_local_file( $file ) {
	$uploads = wp_upload_dir();
	$base = realpath( $uploads['basedir'] );
	$real = realpath( $file );
	return $base && $real && is_file( $real ) && 0 === strpos( wp_normalize_path( $real ), trailingslashit( wp_normalize_path( $base ) ) ) ? $real : false;
}

function ec_media_slug( $title, $id ) {
	$slug = sanitize_title( remove_accents( $title ) );
	$slug = trim( substr( preg_replace( '/[^a-z0-9-]/', '', $slug ), 0, 110 ), '-' );
	return ( $slug ?: 'hinh-anh' ) . '-anh-' . absint( $id );
}

function ec_media_valid_owner( $post ) {
	if ( ! $post || in_array( $post->post_type, array( 'attachment', 'revision' ), true ) || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) { return false; }
	$type = get_post_type_object( $post->post_type );
	return $type && $type->public;
}

/** Parent, featured image, block/class reference or exact upload URL; no guessed title. */
function ec_media_owners( $id ) {
	global $wpdb;
	$attachment = get_post( $id );
	$file = get_post_meta( $id, '_wp_attached_file', true );
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_thumbnail_id' AND meta_value=%s LIMIT 20", (string) $id ) );
	if ( $attachment && $attachment->post_parent ) { $ids[] = $attachment->post_parent; }
	if ( $file ) {
		$content_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment','revision','nav_menu_item') AND post_status NOT IN ('trash','auto-draft') AND (post_content LIKE %s OR post_content REGEXP %s) LIMIT 20", '%' . $wpdb->esc_like( $file ) . '%', 'wp-image-' . absint( $id ) . '([^0-9]|$)' ) );
		$ids = array_merge( $ids, $content_ids );
	}
	$out = array();
	foreach ( array_unique( array_map( 'absint', $ids ) ) as $post_id ) {
		$p = get_post( $post_id );
		if ( ec_media_valid_owner( $p ) && current_user_can( 'edit_post', $post_id ) ) { $out[ $post_id ] = get_the_title( $p ); }
	}
	return $out;
}

/** Produce a WebP copy, preserving each existing crop. Never overwrite a source. */
function ec_media_encode( $source, $target, $width, $quality ) {
	$source = ec_media_local_file( $source );
	if ( ! $source ) { return new WP_Error( 'path', 'Không đọc được ảnh trong thư mục uploads.' ); }
	$info = wp_getimagesize( $source );
	if ( ! $info || $info[0] * $info[1] > 40000000 || filesize( $source ) > 40 * MB_IN_BYTES ) { return new WP_Error( 'size', 'Ảnh quá lớn để xử lý an toàn. Giới hạn 40 MB và 40 megapixel.' ); }
	$mime = wp_get_image_mime( $source );
	if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) { return new WP_Error( 'format', 'Chỉ xử lý JPEG, PNG và WebP tĩnh.' ); }
	// Do not flatten animated PNG/WebP into a single frame.
	$head = file_get_contents( $source, false, null, 0, 1048576 );
	if ( ( 'image/webp' === $mime && false !== strpos( $head, 'ANIM' ) ) || ( 'image/png' === $mime && false !== strpos( $head, 'acTL' ) ) ) { return new WP_Error( 'animated', 'Bỏ qua ảnh động để giữ chuyển động.' ); }
	$editor = wp_get_image_editor( $source );
	if ( is_wp_error( $editor ) ) { return new WP_Error( 'editor', 'Máy chủ không mở được ảnh này.' ); }
	$result = $editor->set_quality( $quality );
	if ( is_wp_error( $result ) ) { return $result; }
	if ( max( $info[0], $info[1] ) > $width ) {
		$result = $editor->resize( $width, $width, false );
		if ( is_wp_error( $result ) ) { return $result; }
	}
	$result = $editor->save( $target, 'image/webp' );
	if ( is_wp_error( $result ) || ! is_file( $target ) || ! filesize( $target ) ) { return new WP_Error( 'write', 'Không ghi được bản WebP.' ); }
	return $result;
}

function ec_media_copy( $source, $target ) {
	$source = ec_media_local_file( $source );
	if ( ! $source || ! copy( $source, $target ) || hash_file( 'sha256', $source ) !== hash_file( 'sha256', $target ) ) { return new WP_Error( 'copy', 'Không tạo được bản đổi tên; ảnh cũ vẫn được giữ.' ); }
	$info = wp_getimagesize( $target );
	return $info ? array( 'width' => $info[0], 'height' => $info[1] ) : new WP_Error( 'copy', 'Không đọc được kích thước ảnh.' );
}

function ec_media_optimize( $id, $post_id, $width = 1920, $quality = 82, $rename_only = false ) {
	$attachment = get_post( $id ); $owner = get_post( $post_id );
	if ( ! $attachment || 'attachment' !== $attachment->post_type || ! ec_media_valid_owner( $owner ) ) { return new WP_Error( 'invalid', 'Chọn ảnh và bài viết/trang thuộc loại nội dung công khai.' ); }
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) || ! current_user_can( 'edit_post', $post_id ) ) { return new WP_Error( 'permission', 'Không có quyền tối ưu ảnh này.' ); }
	if ( get_post_meta( $id, '_ec_media_original', true ) ) { return new WP_Error( 'already', 'Ảnh đã tối ưu. Khôi phục trước nếu muốn đổi thiết lập.' ); }
	if ( ! $rename_only && ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) { return new WP_Error( 'webp', 'Hosting chưa hỗ trợ WebP.' ); }
	$width = max( 800, min( 2560, (int) $width ) ); $quality = max( 65, min( 90, (int) $quality ) );
	$source = ec_media_local_file( get_attached_file( $id, true ) );
	$old_meta = get_post_meta( $id, '_wp_attachment_metadata', true );
	$old_file = get_post_meta( $id, '_wp_attached_file', true );
	if ( ! $source || ! is_array( $old_meta ) || empty( $old_meta['width'] ) ) { return new WP_Error( 'source', 'Ảnh thiếu file hoặc thông tin kích thước.' ); }
	$formats = array( 'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp' );
	$source_mime = wp_get_image_mime( $source );
	if ( ! isset( $formats[ $source_mime ] ) ) { return new WP_Error( 'format', 'Chỉ hỗ trợ JPEG, PNG và WebP.' ); }
	$mime = $rename_only ? $source_mime : 'image/webp'; $extension = $formats[ $mime ];
	$uploads = wp_upload_dir();
	$relative = 'ec-optimized/' . $id . '/' . wp_generate_uuid4();
	$folder = trailingslashit( $uploads['basedir'] ) . $relative;
	if ( ! wp_mkdir_p( $folder ) || 0 !== strpos( wp_normalize_path( realpath( $folder ) ), trailingslashit( wp_normalize_path( realpath( $uploads['basedir'] ) ) ) ) ) { return new WP_Error( 'folder', 'Không tạo được thư mục ảnh tối ưu.' ); }
	$stem = ec_media_slug( get_the_title( $owner ), $id );
	$full = $rename_only ? ec_media_copy( $source, "$folder/$stem.$extension" ) : ec_media_encode( $source, "$folder/$stem.$extension", $width, $quality );
	if ( is_wp_error( $full ) ) { return $full; }
	$baseurl = trailingslashit( $uploads['baseurl'] );
	$olddir = '.' === dirname( $old_file ) ? '' : trailingslashit( dirname( $old_file ) );
	$oldurl = $baseurl . $old_file; $newurl = $baseurl . "$relative/$stem.$extension";
	$urls = array( $oldurl => array( 'url' => $newurl, 'width' => $full['width'] ) );
	$before = filesize( $source ); $after = filesize( "$folder/$stem.$extension" ); $encoded_files = array();
	$new_meta = array( 'width' => $full['width'], 'height' => $full['height'], 'file' => "$relative/$stem.$extension", 'filesize' => $after, 'sizes' => array(), 'image_meta' => $old_meta['image_meta'] ?? array() );
	foreach ( $old_meta['sizes'] ?? array() as $size => $item ) {
		if ( empty( $item['file'] ) || basename( $item['file'] ) !== $item['file'] ) { return new WP_Error( 'crop', 'Thông tin ảnh thu nhỏ không hợp lệ; chưa đổi ảnh.' ); }
		$file = dirname( $source ) . '/' . $item['file'];
		if ( ! ec_media_local_file( $file ) ) { return new WP_Error( 'crop', 'Thiếu file ảnh thu nhỏ; chưa đổi ảnh. Hãy tạo lại thumbnail trước.' ); }
		if ( isset( $encoded_files[ $file ] ) ) { $new_meta['sizes'][ $size ] = $encoded_files[ $file ]; continue; }
		$crop_mime = $rename_only ? wp_get_image_mime( $file ) : 'image/webp';
		if ( ! isset( $formats[ $crop_mime ] ) ) { return new WP_Error( 'crop', 'Định dạng ảnh thu nhỏ không hợp lệ.' ); }
		$name = $stem . '-' . sanitize_key( $size ) . '.' . $formats[ $crop_mime ];
		$encoded = $rename_only ? ec_media_copy( $file, "$folder/$name" ) : ec_media_encode( $file, "$folder/$name", $width, $quality );
		if ( is_wp_error( $encoded ) ) { return $encoded; }
		$bytes = filesize( "$folder/$name" );
		$new_meta['sizes'][ $size ] = array( 'file' => $name, 'width' => $encoded['width'], 'height' => $encoded['height'], 'mime-type' => $crop_mime, 'filesize' => $bytes );
		$encoded_files[ $file ] = $new_meta['sizes'][ $size ];
		$urls[ $baseurl . $olddir . $item['file'] ] = array( 'url' => $baseurl . "$relative/$name", 'width' => $encoded['width'] );
		$before += filesize( $file ); $after += $bytes;
	}
	if ( ! empty( $old_meta['original_image'] ) && basename( $old_meta['original_image'] ) === $old_meta['original_image'] ) { $urls[ $baseurl . $olddir . $old_meta['original_image'] ] = array( 'url' => $newurl, 'width' => $full['width'] ); }
	if ( ! $rename_only && $after >= $before ) { return new WP_Error( 'not_smaller', 'Bản WebP không nhẹ hơn. Website tiếp tục dùng ảnh cũ; có thể chọn “Chỉ đổi tên” để giữ nguyên chất lượng.' ); }
	$backup = array( 'file' => $old_file, 'metadata' => $old_meta, 'mime' => $attachment->post_mime_type, 'before' => $before, 'after' => $after, 'owner' => $post_id, 'urls' => $urls, 'time' => time() );
	if ( ! add_post_meta( $id, '_ec_media_original', $backup, true ) ) { return new WP_Error( 'backup', 'Không lưu được thông tin khôi phục; chưa đổi ảnh.' ); }
	try {
		update_attached_file( $id, trailingslashit( $uploads['basedir'] ) . $new_meta['file'] );
		wp_update_attachment_metadata( $id, $new_meta );
		$result = wp_update_post( array( 'ID' => $id, 'post_mime_type' => $mime ), true );
		if ( is_wp_error( $result ) || get_post_meta( $id, '_wp_attached_file', true ) !== $new_meta['file'] || get_post_meta( $id, '_wp_attachment_metadata', true ) !== $new_meta ) { throw new RuntimeException( 'Activation failed' ); }
	} catch ( Throwable $error ) {
		$restored = ec_media_restore( $id );
		return new WP_Error( 'save', is_wp_error( $restored ) ? 'Chưa lưu/khôi phục hoàn tất. Bản gốc vẫn được giữ; hãy dùng nút khôi phục.' : 'Không lưu được thay đổi; đã khôi phục ảnh gốc.' );
	}
	delete_transient( 'ec_media_url_map' );
	return array( 'message' => ( $rename_only ? 'Đã đổi tên, giữ chất lượng: ' : 'Đã tối ưu: ' ) . size_format( $before ) . ' → ' . size_format( $after ) . '. Tên: ' . $stem . '.' . $extension, 'before' => $before, 'after' => $after );
}

function ec_media_restore( $id ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) ) { return new WP_Error( 'permission', 'Không có quyền khôi phục.' ); }
	$old = get_post_meta( $id, '_ec_media_original', true ); $uploads = wp_upload_dir();
	if ( ! is_array( $old ) || empty( $old['file'] ) || ! ec_media_local_file( trailingslashit( $uploads['basedir'] ) . $old['file'] ) ) { return new WP_Error( 'restore', 'Không tìm thấy file gốc để khôi phục.' ); }
	update_attached_file( $id, trailingslashit( $uploads['basedir'] ) . $old['file'] ); wp_update_attachment_metadata( $id, $old['metadata'] );
	$result = wp_update_post( array( 'ID' => $id, 'post_mime_type' => $old['mime'] ), true );
	if ( is_wp_error( $result ) || get_post_meta( $id, '_wp_attached_file', true ) !== $old['file'] || get_post_meta( $id, '_wp_attachment_metadata', true ) !== $old['metadata'] ) { return new WP_Error( 'restore_save', 'Chưa khôi phục hoàn tất; thông tin bản gốc vẫn được giữ.' ); }
	delete_post_meta( $id, '_ec_media_original' ); delete_transient( 'ec_media_url_map' );
	return array( 'message' => 'Đã khôi phục ảnh gốc. Các file WebP được giữ để đường dẫn đã chia sẻ vẫn hoạt động.' );
}

/** Rewrite image attributes at render time, never bulk replace stored post content. */
function ec_media_content( $html ) {
	if ( is_admin() || ! is_string( $html ) || false === strpos( $html, '<' ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) { return $html; }
	$map = get_transient( 'ec_media_url_map' );
	if ( false === $map ) {
		global $wpdb; $map = array();
		$rows = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_ec_media_original'" );
		foreach ( $rows as $row ) { $record = maybe_unserialize( $row ); if ( is_array( $record ) ) { $map = array_replace( $map, $record['urls'] ?? array() ); } }
		set_transient( 'ec_media_url_map', $map, HOUR_IN_SECONDS );
	}
	if ( ! $map ) { return $html; }
	$tags = new WP_HTML_Tag_Processor( $html );
	while ( $tags->next_tag() ) {
		if ( ! in_array( $tags->get_tag(), array( 'IMG', 'SOURCE', 'A' ), true ) ) { continue; }
		$changed_source = false;
		foreach ( array( 'src', 'data-src', 'data-lazy-src', 'href' ) as $attr ) {
			$value = $tags->get_attribute( $attr ); if ( is_string( $value ) && isset( $map[ $value ] ) ) { $tags->set_attribute( $attr, $map[ $value ]['url'] ); }
		}
		foreach ( array( 'srcset', 'data-srcset', 'data-lazy-srcset' ) as $attr ) {
			$value = $tags->get_attribute( $attr ); if ( ! is_string( $value ) ) { continue; }
			$changed = false; $items = array();
			foreach ( explode( ',', $value ) as $item ) {
				$bits = preg_split( '/\s+/', trim( $item ) );
				if ( isset( $map[ $bits[0] ] ) ) { $record = $map[ $bits[0] ]; $bits[0] = $record['url']; if ( isset( $bits[1] ) && preg_match( '/^\d+w$/', $bits[1] ) ) { $bits[1] = $record['width'] . 'w'; } $changed = true; }
				$items[] = implode( ' ', $bits );
			}
			if ( $changed ) {
				// Resizing can collapse two old widths to one; avoid invalid duplicate descriptors.
				$seen = array(); $unique = array();
				foreach ( $items as $candidate ) {
					preg_match( '/\s+(\d+w|[\d.]+x)$/', $candidate, $descriptor );
					$key = $descriptor[1] ?? $candidate;
					if ( ! isset( $seen[ $key ] ) ) { $unique[] = $candidate; $seen[ $key ] = true; }
				}
				$tags->set_attribute( $attr, implode( ', ', $unique ) ); if ( 'srcset' === $attr ) { $changed_source = true; }
			}
		}
		if ( $changed_source && 'SOURCE' === $tags->get_tag() && 'image/webp' !== $tags->get_attribute( 'type' ) ) {
			// Only change the MIME declaration if every source now really is WebP.
			$srcset = $tags->get_attribute( 'srcset' );
			if ( is_string( $srcset ) && preg_match( '/\.webp(?:\s|$)/', $srcset ) && ! preg_match( '/\.(?:png|jpe?g)(?:\s|$)/i', $srcset ) ) { $tags->set_attribute( 'type', 'image/webp' ); }
		}
	}
	return $tags->get_updated_html();
}
add_filter( 'the_content', 'ec_media_content', 30 );
add_filter( 'widget_text_content', 'ec_media_content', 30 );

function ec_media_ajax() {
	check_ajax_referer( 'ec_media_optimize', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 ); }
	$mode = sanitize_key( $_POST['mode'] ?? '' );
	if ( ! in_array( $mode, array( 'preview', 'optimize', 'rename', 'restore' ), true ) ) { wp_send_json_error( array( 'message' => 'Thao tác không hợp lệ.' ), 400 ); }
	$id = absint( $_POST['id'] ?? 0 ); $lock = 'ec_media_lock_' . $id;
	if ( 'preview' === $mode ) {
		$p = get_post( absint( $_POST['post_id'] ?? 0 ) );
		if ( ! ec_media_valid_owner( $p ) || ! current_user_can( 'edit_post', $p->ID ) || ! current_user_can( 'edit_post', $id ) ) { wp_send_json_error( array( 'message' => 'Không tìm thấy bài viết/trang hợp lệ hoặc không có quyền.' ) ); }
		wp_send_json_success( array( 'title' => get_the_title( $p ), 'filename' => ec_media_slug( get_the_title( $p ), $id ) . '.webp', 'post_id' => $p->ID ) );
	}
	if ( ! add_option( $lock, time(), '', false ) ) { wp_send_json_error( array( 'message' => 'Ảnh đang được xử lý. Nếu lượt trước bị ngắt, hãy thử lại sau 5 phút.' ), 409 ); }
	wp_schedule_single_event( time() + 300, 'ec_media_release_lock', array( $lock ) );
	try {
		$result = 'restore' === $mode ? ec_media_restore( $id ) : ec_media_optimize( $id, absint( $_POST['post_id'] ?? 0 ), (int) ( $_POST['width'] ?? 1920 ), (int) ( $_POST['quality'] ?? 82 ), 'rename' === $mode );
	} catch ( Throwable $error ) { $result = new WP_Error( 'processing', 'Máy chủ chưa xử lý xong ảnh. Tải lại danh sách để kiểm tra trạng thái trước khi thử tiếp.' ); }
	finally { delete_option( $lock ); wp_clear_scheduled_hook( 'ec_media_release_lock', array( $lock ) ); }
	if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ) ); }
	do_action( 'litespeed_purge_all' );
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_ec_media_optimize', 'ec_media_ajax' );
add_action( 'ec_media_release_lock', function( $lock ) { if ( preg_match( '/^ec_media_lock_\d+$/D', $lock ) && (int) get_option( $lock ) < time() - 290 ) { delete_option( $lock ); } } );

add_action( 'admin_menu', function() { add_media_page( 'Tối ưu ảnh & tên file', 'Tối ưu ảnh & tên file', 'manage_options', 'ec-media-optimize', 'ec_media_page' ); } );
add_action( 'admin_enqueue_scripts', function( $hook ) {
	if ( 'media_page_ec-media-optimize' !== $hook ) { return; }
	wp_enqueue_script( 'ec-media-optimize', get_stylesheet_directory_uri() . '/assets/media-optimize.js', array(), filemtime( get_stylesheet_directory() . '/assets/media-optimize.js' ), true );
	wp_localize_script( 'ec-media-optimize', 'ecMedia', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'ec_media_optimize' ) ) );
} );

function ec_media_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
	$query = new WP_Query( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/webp' ), 'posts_per_page' => 25, 'paged' => $page, 'orderby' => 'ID', 'order' => 'DESC' ) );
	?>
	<div class="wrap"><h1>Tối ưu ảnh &amp; tên file</h1>
	<style>.ec-media-table{overflow-x:auto}.ec-media-table td{vertical-align:top}.ec-media-filename,.ec-media-result{overflow-wrap:anywhere}.ec-media-table th{font-weight:600}.ec-media-table td:nth-child(2){max-width:280px;overflow-wrap:anywhere}.ec-media-table td:last-child{min-width:220px}.ec-media-result[data-error="true"]{color:#b32d2e}@media(max-width:782px){.ec-media-table table,.ec-media-table tbody{display:block}.ec-media-table tr{display:grid;grid-template-columns:32px minmax(0,1fr);padding:8px;border-bottom:1px solid #ddd}.ec-media-table td{display:block;grid-column:2;max-width:none!important;min-width:0!important;padding:8px}.ec-media-table td:first-child{grid-column:1;grid-row:1/4;padding:8px 0}.ec-media-table select{width:100%}.ec-media-table thead{display:block}.ec-media-table th:not(:first-child){display:none}.ec-media-table td:nth-child(3):before{content:'Bài viết dùng để đặt tên';display:block;font-weight:600;margin-bottom:6px}}</style>
	<p>Tạo bản WebP nhẹ hơn, giữ mã ảnh và đặt tên theo bài viết được chọn. Không đổi độ sáng, màu sắc, chú thích hoặc nội dung bài viết.</p>
	<p><strong>Bản gốc được giữ để khôi phục và các liên kết cũ vẫn hoạt động.</strong> Công cụ giảm dung lượng ảnh tải trên website; chưa giải phóng dung lượng ổ đĩa hosting. Ảnh chèn bằng URL trong trình dựng trang hoặc mã riêng cần kiểm tra riêng.</p>
	<p><label>Cạnh dài tối đa <select id="ec-media-width"><option>1600</option><option selected>1920</option><option>2560</option></select> px</label> &nbsp; <label>Chất lượng WebP <input id="ec-media-quality" type="number" min="65" max="90" value="82" style="width:70px"></label></p>
	<p><button class="button button-primary" id="ec-media-start">Tối ưu &amp; đổi tên</button> <button class="button" id="ec-media-rename">Chỉ đổi tên, giữ chất lượng</button> <button class="button" id="ec-media-stop" disabled>Dừng sau ảnh hiện tại</button> <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ec-media-optimize', 'paged' => $page ), admin_url( 'upload.php' ) ) ); ?>">Tải lại danh sách</a></p>
	<p>Tên dự kiến bên dưới dành cho WebP. Khi chọn “Chỉ đổi tên”, phần đuôi giữ định dạng gốc (JPG/PNG/WebP), không nén lại và không thu nhỏ.</p>
	<p id="ec-media-progress" role="status" aria-live="polite">Chọn ảnh và kiểm tra bài viết/tên file trước khi xử lý. Mỗi ảnh được xử lý lần lượt.</p>
	<div class="ec-media-table"><table class="widefat striped"><thead><tr><th><input type="checkbox" id="ec-media-all" aria-label="Chọn tất cả ảnh đủ điều kiện trong trang"></th><th>Ảnh hiện tại</th><th>Bài viết dùng để đặt tên</th><th>Tên dự kiến / kết quả</th></tr></thead><tbody>
	<?php foreach ( $query->posts as $image ) :
		$id = $image->ID; $file = ec_media_local_file( get_attached_file( $id ) ); $meta = wp_get_attachment_metadata( $id ); $old = get_post_meta( $id, '_ec_media_original', true ); $owners = ec_media_owners( $id ); $chosen = 1 === count( $owners ) ? (int) array_key_first( $owners ) : 0;
		?>
		<tr data-media-id="<?php echo (int) $id; ?>"><td><input type="checkbox" class="ec-media-select" aria-label="Chọn ảnh <?php echo (int) $id; ?>" <?php disabled( (bool) $old || ! $file ); ?>></td>
		<td><?php echo wp_get_attachment_image( $id, array( 90, 70 ), false, array( 'style' => 'max-width:90px;max-height:70px;object-fit:contain' ) ); ?><br><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">#<?php echo (int) $id; ?></a> <?php echo esc_html( basename( $file ?: '' ) ); ?><br><?php echo esc_html( $file ? size_format( filesize( $file ) ) . ' · ' . ( $meta['width'] ?? '?' ) . ' × ' . ( $meta['height'] ?? '?' ) . ' px' : 'Không tìm thấy file' ); ?></td>
		<td><select class="ec-media-owner" style="max-width:300px" <?php disabled( (bool) $old ); ?>><option value="">Chọn bài viết…</option><?php foreach ( $owners as $post_id => $title ) : ?><option value="<?php echo (int) $post_id; ?>" data-filename="<?php echo esc_attr( ec_media_slug( $title, $id ) . '.webp' ); ?>" <?php selected( $chosen, $post_id ); ?>><?php echo esc_html( $title ); ?></option><?php endforeach; ?></select>
		<?php if ( ! $old ) : ?><p>Hoặc nhập ID bài viết: <input type="number" min="1" class="ec-media-owner-id" style="width:90px" aria-label="ID bài viết cho ảnh <?php echo (int) $id; ?>"> <button class="button ec-media-preview">Xem tên</button></p><?php endif; ?>
		<?php if ( count( $owners ) > 1 ) : ?><p>Ảnh dùng chung: chọn bài làm tên chính.</p><?php elseif ( ! $owners ) : ?><p>Chưa xác định bài sử dụng. Cần chọn ID bài trước khi tối ưu.</p><?php endif; ?></td>
		<td><code class="ec-media-filename"><?php echo esc_html( $chosen ? ec_media_slug( $owners[ $chosen ], $id ) . '.webp' : 'Chưa chọn bài viết' ); ?></code><p class="ec-media-result" role="status"><?php echo $old ? esc_html( 'Đã tối ưu: ' . size_format( $old['before'] ) . ' → ' . size_format( $old['after'] ) ) : ''; ?></p><?php if ( $old ) : ?><button class="button ec-media-restore">Khôi phục ảnh gốc</button><?php endif; ?></td></tr>
	<?php endforeach; ?></tbody></table></div>
	<div class="tablenav"><div class="tablenav-pages"><?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'total' => $query->max_num_pages, 'current' => $page ) ) ); ?></div></div></div>
	<?php
}
