<?php
/**
 * Quản lý đánh giá bệnh nhân trên trang chủ.
 *
 * Mỗi đánh giá là một bản ghi riêng để quản trị viên có thể thêm, sửa, xóa,
 * chọn ảnh đại diện/ảnh bìa hoặc gắn video từ Thư viện phương tiện.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const EYECARE_POST_TYPE_DANH_GIA = 'eyecare_danh_gia';

/** Đăng ký mục quản trị “Đánh giá bệnh nhân”. */
function eyecare_dang_ky_post_type_danh_gia() {
	$labels = array(
		'name'               => 'Đánh giá bệnh nhân',
		'singular_name'      => 'Đánh giá bệnh nhân',
		'menu_name'          => 'Đánh giá bệnh nhân',
		'add_new'            => 'Thêm đánh giá',
		'add_new_item'       => 'Thêm đánh giá bệnh nhân',
		'edit_item'          => 'Sửa đánh giá bệnh nhân',
		'new_item'           => 'Đánh giá mới',
		'view_item'          => 'Xem đánh giá',
		'all_items'          => 'Tất cả đánh giá',
		'search_items'       => 'Tìm đánh giá',
		'not_found'          => 'Chưa có đánh giá nào.',
		'featured_image'     => 'Ảnh bệnh nhân hoặc ảnh bìa video',
		'set_featured_image' => 'Chọn ảnh bệnh nhân / ảnh bìa video',
		'remove_featured_image' => 'Xóa ảnh',
		'use_featured_image' => 'Dùng ảnh này',
	);

	register_post_type(
		EYECARE_POST_TYPE_DANH_GIA,
		array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'show_in_nav_menus'   => false,
			'menu_position'       => 22,
			'menu_icon'           => 'dashicons-format-chat',
			'supports'            => array( 'title', 'thumbnail' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'eyecare_dang_ky_post_type_danh_gia', 5 );

/** Nạp trình chọn ảnh/video của WordPress ở màn hình đánh giá. */
function eyecare_nap_media_danh_gia( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || EYECARE_POST_TYPE_DANH_GIA !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'eyecare_nap_media_danh_gia' );

/** Thêm hộp nhập nội dung đánh giá. */
function eyecare_them_hop_danh_gia() {
	add_meta_box(
		'eyecare-thong-tin-danh-gia',
		'Thông tin hiển thị trên trang chủ',
		'eyecare_in_hop_danh_gia',
		EYECARE_POST_TYPE_DANH_GIA,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'eyecare_them_hop_danh_gia' );

/** In giao diện hộp thông tin đánh giá. */
function eyecare_in_hop_danh_gia( $post ) {
	wp_nonce_field( 'eyecare_luu_danh_gia', 'eyecare_danh_gia_nonce' );

	$noi_dung  = get_post_meta( $post->ID, '_eyecare_review_text', true );
	$vai_tro   = get_post_meta( $post->ID, '_eyecare_review_role', true );
	$so_sao    = get_post_meta( $post->ID, '_eyecare_review_rating', true );
	$loai_media = get_post_meta( $post->ID, '_eyecare_review_media_type', true );
	$video_url = get_post_meta( $post->ID, '_eyecare_review_video_url', true );
	$thu_tu    = get_post_meta( $post->ID, '_eyecare_review_order', true );
	$nguon_goc = get_post_meta( $post->ID, '_eyecare_review_source_ref', true );
	$dong_y    = get_post_meta( $post->ID, '_eyecare_review_consent_ref', true );
	$duyet     = '1' === (string) get_post_meta( $post->ID, '_eyecare_review_public_approved', true );
	$hien_ten  = '1' === (string) get_post_meta( $post->ID, '_eyecare_review_show_name', true );
	$hien_media = '1' === (string) get_post_meta( $post->ID, '_eyecare_review_show_media', true );
	$bai_lien_quan = (int) get_post_meta( $post->ID, '_eyecare_review_related_post_id', true );
	$bai_viet = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 300, 'orderby' => 'title', 'order' => 'ASC' ) );

	$so_sao     = max( 0, min( 5, (int) $so_sao ) );
	$loai_media = in_array( $loai_media, array( 'none', 'image', 'video' ), true ) ? $loai_media : 'image';
	// Nếu đã chọn ảnh nhưng chế độ cũ vẫn là video và URL video trống, ưu tiên
	// ảnh. Trước đây trường hợp này làm giao diện rơi về ô chữ cái dù ảnh đã lưu.
	if ( has_post_thumbnail( $post->ID ) && ( 'video' !== $loai_media || '' === trim( (string) $video_url ) ) ) {
		$loai_media = 'image';
	}
	if ( '' === (string) $thu_tu ) {
		$thu_tu = max( 1, (int) $post->menu_order );
	}
	?>
	<style>
		.eyecare-admin-review-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px 22px; }
		.eyecare-admin-review-field label { display:block; margin-bottom:6px; font-weight:600; }
		.eyecare-admin-review-field input,
		.eyecare-admin-review-field textarea,
		.eyecare-admin-review-field select { width:100%; }
		.eyecare-admin-review-field--wide { grid-column:1 / -1; }
		.eyecare-admin-review-help { margin:6px 0 0; color:#646970; font-size:12px; }
		.eyecare-admin-review-video-row { display:flex; gap:8px; align-items:center; }
		.eyecare-admin-review-video-row input { flex:1; }
		@media (max-width:782px) { .eyecare-admin-review-grid { grid-template-columns:1fr; } .eyecare-admin-review-field--wide { grid-column:auto; } }
	</style>
	<p><strong>Tiêu đề bản ghi:</strong> lưu tên để quản lý nội bộ. Tên và ảnh/video chỉ hiện khi được cho phép riêng ở bên dưới. Bản ghi chỉ hiện công khai khi đã đăng và có đủ nguồn gốc, xác nhận đồng ý, dấu duyệt.</p>
	<div class="eyecare-admin-review-grid">
		<div class="eyecare-admin-review-field eyecare-admin-review-field--wide">
			<label for="eyecare-review-text">Nội dung đánh giá</label>
			<textarea id="eyecare-review-text" name="eyecare_review_text" rows="6" placeholder="Nhập nguyên văn chia sẻ đã được bệnh nhân cho phép sử dụng..."><?php echo esc_textarea( $noi_dung ); ?></textarea>
		</div>
		<div class="eyecare-admin-review-field">
			<label for="eyecare-review-role">Thông tin ngắn</label>
			<input id="eyecare-review-role" name="eyecare_review_role" type="text" value="<?php echo esc_attr( $vai_tro ); ?>" placeholder="Ví dụ: Đã phẫu thuật Phaco">
		</div>
		<div class="eyecare-admin-review-field">
			<label for="eyecare-review-rating">Số sao</label>
			<select id="eyecare-review-rating" name="eyecare_review_rating">
				<option value="0" <?php selected( $so_sao, 0 ); ?>>Không hiển thị số sao</option>
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $so_sao, $i ); ?>><?php echo esc_html( (string) $i ); ?> sao</option>
				<?php endfor; ?>
			</select>
		</div>
		<div class="eyecare-admin-review-field">
			<label for="eyecare-review-media-type">Loại hiển thị</label>
			<select id="eyecare-review-media-type" name="eyecare_review_media_type">
				<option value="image" <?php selected( $loai_media, 'image' ); ?>>Ảnh</option>
				<option value="video" <?php selected( $loai_media, 'video' ); ?>>Video</option>
				<option value="none" <?php selected( $loai_media, 'none' ); ?>>Chỉ nội dung</option>
			</select>
			<p class="eyecare-admin-review-help">Ảnh dùng ô “Ảnh bệnh nhân hoặc ảnh bìa video” bên phải.</p>
		</div>
		<div class="eyecare-admin-review-field">
			<label for="eyecare-review-video-url">Đường dẫn video</label>
			<div class="eyecare-admin-review-video-row">
				<input id="eyecare-review-video-url" name="eyecare_review_video_url" type="url" value="<?php echo esc_attr( $video_url ); ?>" placeholder="YouTube, Vimeo hoặc MP4">
				<button type="button" class="button" id="eyecare-review-video-picker">Chọn video</button>
			</div>
			<p class="eyecare-admin-review-help">Có thể dán link YouTube/Vimeo hoặc chọn file MP4 từ Thư viện.</p>
		</div>
		<div class="eyecare-admin-review-field">
			<label for="eyecare-review-order">Thứ tự hiển thị</label>
			<input id="eyecare-review-order" name="eyecare_review_order" type="number" min="0" step="1" value="<?php echo esc_attr( (string) $thu_tu ); ?>">
		</div>
		<div class="eyecare-admin-review-field">
			<label for="eyecare-review-related-post">Bài viết liên quan (tùy chọn)</label>
			<select id="eyecare-review-related-post" name="eyecare_review_related_post_id">
				<option value="0">Chỉ hiện trên trang chủ</option>
				<?php foreach ( $bai_viet as $bai ) : ?>
					<option value="<?php echo esc_attr( (string) $bai->ID ); ?>" <?php selected( $bai_lien_quan, $bai->ID ); ?>><?php echo esc_html( get_the_title( $bai ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="eyecare-admin-review-help">Chỉ chọn bài nói đúng dịch vụ hoặc trải nghiệm trong nhận xét.</p>
		</div>
		<div class="eyecare-admin-review-field">
			<label for="eyecare-review-source-ref">Mã nguồn nhận xét gốc (nội bộ)</label>
			<input id="eyecare-review-source-ref" name="eyecare_review_source_ref" type="text" value="<?php echo esc_attr( $nguon_goc ); ?>" placeholder="Ví dụ: mã phiếu phản hồi hoặc URL video gốc">
		</div>
		<div class="eyecare-admin-review-field">
			<label for="eyecare-review-consent-ref">Mã hồ sơ đồng ý công khai (nội bộ)</label>
			<input id="eyecare-review-consent-ref" name="eyecare_review_consent_ref" type="text" value="<?php echo esc_attr( $dong_y ); ?>" placeholder="Ví dụ: mã giấy đồng ý có chữ ký">
		</div>
		<div class="eyecare-admin-review-field eyecare-admin-review-field--wide">
			<label><input name="eyecare_review_public_approved" type="checkbox" value="1" <?php checked( $duyet ); ?> style="width:auto"> Đã đối chiếu đúng lời nhận xét, được phép công khai nội dung và duyệt hiển thị</label>
			<label><input name="eyecare_review_show_name" type="checkbox" value="1" <?php checked( $hien_ten ); ?> style="width:auto"> Người bệnh đồng ý công khai tên ghi ở tiêu đề</label>
			<label><input name="eyecare_review_show_media" type="checkbox" value="1" <?php checked( $hien_media ); ?> style="width:auto"> Người bệnh đồng ý công khai đúng ảnh/video đã chọn</label>
			<p class="eyecare-admin-review-help">Khi không chọn, trang công khai dùng “Người bệnh” và không hiện ảnh/video. Chỉ chọn số sao nếu người bệnh đã chấm điểm. Không ghi số điện thoại hoặc tài liệu đồng ý vào các ô công khai.</p>
		</div>
	</div>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			var button = document.getElementById('eyecare-review-video-picker');
			var field = document.getElementById('eyecare-review-video-url');
			var type = document.getElementById('eyecare-review-media-type');
			if (!button || !field || !window.wp || !wp.media) return;
			button.addEventListener('click', function () {
				var frame = wp.media({ title: 'Chọn video đánh giá', button: { text: 'Dùng video này' }, library: { type: 'video' }, multiple: false });
				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					field.value = attachment.url || '';
					if (type && field.value) type.value = 'video';
				});
				frame.open();
			});
		});
	</script>
	<?php
}

/** Lưu dữ liệu đánh giá với nonce và kiểm tra quyền chỉnh sửa. */
function eyecare_luu_danh_gia( $post_id, $post ) {
	if ( EYECARE_POST_TYPE_DANH_GIA !== $post->post_type
		|| ! isset( $_POST['eyecare_danh_gia_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eyecare_danh_gia_nonce'] ) ), 'eyecare_luu_danh_gia' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| wp_is_post_revision( $post_id )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$noi_dung = isset( $_POST['eyecare_review_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['eyecare_review_text'] ) ) : '';
	$vai_tro  = isset( $_POST['eyecare_review_role'] ) ? sanitize_text_field( wp_unslash( $_POST['eyecare_review_role'] ) ) : '';
	$rating   = isset( $_POST['eyecare_review_rating'] ) ? max( 0, min( 5, absint( wp_unslash( $_POST['eyecare_review_rating'] ) ) ) ) : 0;
	$type     = isset( $_POST['eyecare_review_media_type'] ) ? sanitize_key( wp_unslash( $_POST['eyecare_review_media_type'] ) ) : 'none';
	$type     = in_array( $type, array( 'none', 'image', 'video' ), true ) ? $type : 'none';
	$video    = isset( $_POST['eyecare_review_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['eyecare_review_video_url'] ) ) : '';
	$order    = isset( $_POST['eyecare_review_order'] ) ? absint( wp_unslash( $_POST['eyecare_review_order'] ) ) : 0;
	$source_ref = isset( $_POST['eyecare_review_source_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['eyecare_review_source_ref'] ) ) : '';
	$consent_ref = isset( $_POST['eyecare_review_consent_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['eyecare_review_consent_ref'] ) ) : '';
	$approved = isset( $_POST['eyecare_review_public_approved'] ) && '1' === (string) wp_unslash( $_POST['eyecare_review_public_approved'] );
	$approved = $approved && '' !== $source_ref && '' !== $consent_ref && '' !== $noi_dung;
	$show_name = isset( $_POST['eyecare_review_show_name'] ) && '1' === (string) wp_unslash( $_POST['eyecare_review_show_name'] );
	$show_media = isset( $_POST['eyecare_review_show_media'] ) && '1' === (string) wp_unslash( $_POST['eyecare_review_show_media'] );
	$related_id = isset( $_POST['eyecare_review_related_post_id'] ) ? absint( wp_unslash( $_POST['eyecare_review_related_post_id'] ) ) : 0;
	$related_id = $related_id && 'post' === get_post_type( $related_id ) && 'publish' === get_post_status( $related_id ) ? $related_id : 0;

	// Có ảnh đại diện nhưng không có URL video thì không giữ trạng thái video
	// rỗng. Chuyển về ảnh để lần lưu sau và phần hiển thị luôn nhất quán.
	if ( 'video' === $type && '' === $video && has_post_thumbnail( $post_id ) ) {
		$type = 'image';
	}

	update_post_meta( $post_id, '_eyecare_review_text', $noi_dung );
	update_post_meta( $post_id, '_eyecare_review_role', $vai_tro );
	update_post_meta( $post_id, '_eyecare_review_rating', $rating );
	update_post_meta( $post_id, '_eyecare_review_media_type', $type );
	update_post_meta( $post_id, '_eyecare_review_video_url', $video );
	update_post_meta( $post_id, '_eyecare_review_order', $order );
	update_post_meta( $post_id, '_eyecare_review_source_ref', $source_ref );
	update_post_meta( $post_id, '_eyecare_review_consent_ref', $consent_ref );
	update_post_meta( $post_id, '_eyecare_review_public_approved', $approved ? '1' : '0' );
	update_post_meta( $post_id, '_eyecare_review_show_name', $show_name ? '1' : '0' );
	update_post_meta( $post_id, '_eyecare_review_show_media', $show_media ? '1' : '0' );
	update_post_meta( $post_id, '_eyecare_review_related_post_id', $related_id );

	if ( (int) $post->menu_order !== $order ) {
		remove_action( 'save_post_' . EYECARE_POST_TYPE_DANH_GIA, 'eyecare_luu_danh_gia', 10 );
		wp_update_post( array( 'ID' => $post_id, 'menu_order' => $order ) );
		add_action( 'save_post_' . EYECARE_POST_TYPE_DANH_GIA, 'eyecare_luu_danh_gia', 10, 2 );
	}
}
add_action( 'save_post_' . EYECARE_POST_TYPE_DANH_GIA, 'eyecare_luu_danh_gia', 10, 2 );

/** Chỉ đọc nhận xét đã có nguồn gốc và hồ sơ đồng ý công khai. */
function eyecare_du_lieu_danh_gia() {
	$posts = get_posts(
		array(
			'post_type'      => EYECARE_POST_TYPE_DANH_GIA,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array( array( 'key' => '_eyecare_review_public_approved', 'value' => '1' ) ),
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'order'          => 'ASC',
		)
	);

	if ( ! $posts ) {
		return array();
	}

	$items = array();
	foreach ( $posts as $post ) {
		if ( '' === trim( (string) get_post_meta( $post->ID, '_eyecare_review_source_ref', true ) )
			|| '' === trim( (string) get_post_meta( $post->ID, '_eyecare_review_consent_ref', true ) )
			|| '' === trim( (string) get_post_meta( $post->ID, '_eyecare_review_text', true ) ) ) {
			continue;
		}
		$show_name  = '1' === (string) get_post_meta( $post->ID, '_eyecare_review_show_name', true );
		$show_media = '1' === (string) get_post_meta( $post->ID, '_eyecare_review_show_media', true );
		$image_id   = $show_media ? (int) get_post_thumbnail_id( $post->ID ) : 0;
		$media_type = $show_media ? (string) get_post_meta( $post->ID, '_eyecare_review_media_type', true ) : 'none';
		$video_url  = $show_media ? (string) get_post_meta( $post->ID, '_eyecare_review_video_url', true ) : '';
		if ( $image_id && ( 'video' !== $media_type || '' === trim( $video_url ) ) ) {
			$media_type = 'image';
		}
		$items[]  = array(
			'post_id'    => $post->ID,
			'ten'        => $show_name ? get_the_title( $post ) : 'Người bệnh',
			'vai_tro'    => $show_name ? (string) get_post_meta( $post->ID, '_eyecare_review_role', true ) : '',
			'rating'     => max( 0, min( 5, (int) get_post_meta( $post->ID, '_eyecare_review_rating', true ) ) ),
			'noi_dung'   => (string) get_post_meta( $post->ID, '_eyecare_review_text', true ),
			'media_type' => $media_type,
			'video_url'  => $video_url,
			'image_id'   => $image_id,
			'related_post_id' => (int) get_post_meta( $post->ID, '_eyecare_review_related_post_id', true ),
		);
	}

	return $items;
}

/** In slider đánh giá bệnh nhân. */
function eyecare_danh_gia_benh_nhan_in() {
	$items = eyecare_du_lieu_danh_gia();
	if ( empty( $items ) ) {
		return;
	}

	$slider_id = wp_unique_id( 'eyecare-review-slider-' );
	echo '<section class="eyecare-chu__danh-gia" aria-label="Đánh giá từ bệnh nhân">';
	echo '<div class="eyecare-chu__khung">';
	echo '<header class="eyecare-review__head"><p class="eyecare-review__eyebrow"><span aria-hidden="true">✦</span> Trải nghiệm thực tế</p><h2 class="eyecare-review__title">Đánh giá từ <span>bệnh nhân</span></h2><p class="eyecare-review__intro">Những chia sẻ được bệnh viện đăng tải với sự đồng ý của người bệnh. Đây là trải nghiệm cá nhân; kết quả thăm khám hoặc điều trị có thể khác nhau.</p></header>';
	echo '<div class="eyecare-review__slider" data-review-slider="true">';
	echo '<div class="eyecare-review__track" id="' . esc_attr( $slider_id ) . '" data-review-track="true" tabindex="0" aria-label="Danh sách đánh giá bệnh nhân">';

	foreach ( $items as $index => $item ) {
		$name       = isset( $item['ten'] ) ? $item['ten'] : '';
		$role       = isset( $item['vai_tro'] ) ? $item['vai_tro'] : '';
		$quote      = isset( $item['noi_dung'] ) ? $item['noi_dung'] : '';
		$rating     = max( 0, min( 5, (int) ( $item['rating'] ?? 0 ) ) );
		$media_type = isset( $item['media_type'] ) ? $item['media_type'] : 'none';
		$video_url  = isset( $item['video_url'] ) ? $item['video_url'] : '';
		$image_id   = ! empty( $item['image_id'] ) ? (int) $item['image_id'] : 0;
		$initial    = 'Người bệnh' === $name ? '“' : ( function_exists( 'mb_substr' ) ? mb_substr( trim( $name ), 0, 1, 'UTF-8' ) : substr( trim( $name ), 0, 1 ) );
		$video_rendered = false;

		echo '<article class="eyecare-review__card" data-review-card="true">';
		echo '<div class="eyecare-review__media">';
		if ( 'video' === $media_type && $video_url ) {
			$host = wp_parse_url( $video_url, PHP_URL_HOST );
			if ( $host && ( false !== strpos( $host, 'youtube.com' ) || false !== strpos( $host, 'youtu.be' ) || false !== strpos( $host, 'vimeo.com' ) ) ) {
				$embed = wp_oembed_get( esc_url_raw( $video_url ), array( 'width' => 640, 'height' => 360 ) );
				if ( $embed ) {
					$allowed_embed = array(
						'iframe' => array( 'src' => true, 'width' => true, 'height' => true, 'frameborder' => true, 'allow' => true, 'allowfullscreen' => true, 'title' => true, 'loading' => true, 'referrerpolicy' => true ),
					);
					echo '<div class="eyecare-review__embed">' . wp_kses( $embed, $allowed_embed ) . '</div>';
					$video_rendered = true;
				} else {
					echo '<span class="eyecare-review__initial" aria-hidden="true">' . esc_html( $initial ) . '</span>';
				}
			} else {
				echo '<video controls playsinline preload="metadata"';
				if ( $image_id ) {
					$image_src = wp_get_attachment_image_url( $image_id, 'medium_large' );
					if ( $image_src ) echo ' poster="' . esc_url( $image_src ) . '"';
				}
				echo '><source src="' . esc_url( $video_url ) . '"></video>';
				$video_rendered = true;
			}
		} elseif ( 'image' === $media_type && $image_id ) {
			echo wp_get_attachment_image( $image_id, 'medium_large', false, array( 'class' => 'eyecare-review__image', 'alt' => 'Ảnh của ' . $name, 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<span class="eyecare-review__initial" aria-hidden="true">' . esc_html( $initial ) . '</span>';
		}
		if ( $video_rendered ) echo '<span class="eyecare-review__play" aria-hidden="true">▶</span>';
		echo '</div>';
		echo '<div class="eyecare-review__body">';
		if ( $rating > 0 ) {
			echo '<div class="eyecare-review__stars" role="img" aria-label="' . esc_attr( $rating . ' trên 5 sao' ) . '">';
			for ( $star = 1; $star <= 5; $star++ ) echo '<span class="' . ( $star <= $rating ? 'is-on' : '' ) . '" aria-hidden="true">★</span>';
			echo '</div>';
		}
		echo '<blockquote>“' . esc_html( $quote ) . '”</blockquote>';
		echo '<footer><strong>' . esc_html( $name ) . '</strong>';
		if ( $role ) echo '<span>' . esc_html( $role ) . '</span>';
		echo '</footer></div></article>';
	}

	echo '</div>';
	echo '<div class="eyecare-review__controls" aria-label="Điều khiển đánh giá">';
	echo '<button type="button" class="eyecare-review__button" data-review-prev="true" aria-label="Xem đánh giá trước" aria-controls="' . esc_attr( $slider_id ) . '">‹</button>';
	echo '<button type="button" class="eyecare-review__button eyecare-review__pause" data-review-pause="true" aria-label="Tạm dừng tự động chuyển đánh giá" aria-pressed="false">Ⅱ</button>';
	echo '<button type="button" class="eyecare-review__button" data-review-next="true" aria-label="Xem đánh giá tiếp theo" aria-controls="' . esc_attr( $slider_id ) . '">›</button>';
	echo '</div></div></div></section>';
}

/** Một nhận xét đã duyệt chỉ xuất hiện trong bài được biên tập viên chọn. */
function eyecare_danh_gia_lien_quan_bai_viet_in( $article_id ) {
	$article_id = absint( $article_id );
	if ( ! $article_id || 'post' !== get_post_type( $article_id ) ) {
		return;
	}
	$items = array_values( array_filter( eyecare_du_lieu_danh_gia(), static function ( $item ) use ( $article_id ) {
		return $article_id === (int) ( $item['related_post_id'] ?? 0 );
	} ) );
	if ( ! $items ) {
		return;
	}
	$heading_id = wp_unique_id( 'eyecare-review-related-' );
	echo '<aside class="eyecare-review-related" aria-labelledby="' . esc_attr( $heading_id ) . '">';
	echo '<h2 id="' . esc_attr( $heading_id ) . '">Trải nghiệm người bệnh liên quan</h2>';
	foreach ( array_slice( $items, 0, 2 ) as $item ) {
		echo '<blockquote><p>“' . esc_html( $item['noi_dung'] ) . '”</p><footer><strong>' . esc_html( $item['ten'] ) . '</strong>';
		if ( $item['vai_tro'] ) {
			echo ' · ' . esc_html( $item['vai_tro'] );
		}
		echo '</footer></blockquote>';
	}
	echo '<p class="eyecare-review-related__note">Đây là trải nghiệm cá nhân; kết quả thăm khám hoặc điều trị có thể khác nhau.</p></aside>';
}

/** Các cột gọn trên màn hình danh sách đánh giá. */
function eyecare_cot_danh_sach_danh_gia( $columns ) {
	return array( 'cb' => $columns['cb'], 'title' => 'Bệnh nhân', 'eyecare_review_role' => 'Thông tin ngắn', 'eyecare_review_media' => 'Media', 'eyecare_review_order' => 'Thứ tự', 'date' => $columns['date'] );
}
add_filter( 'manage_' . EYECARE_POST_TYPE_DANH_GIA . '_posts_columns', 'eyecare_cot_danh_sach_danh_gia' );

function eyecare_noi_dung_cot_danh_gia( $column, $post_id ) {
	if ( 'eyecare_review_role' === $column ) echo esc_html( get_post_meta( $post_id, '_eyecare_review_role', true ) );
	if ( 'eyecare_review_media' === $column ) echo esc_html( get_post_meta( $post_id, '_eyecare_review_media_type', true ) ?: 'none' );
	if ( 'eyecare_review_order' === $column ) echo esc_html( (string) get_post_meta( $post_id, '_eyecare_review_order', true ) );
}
add_action( 'manage_' . EYECARE_POST_TYPE_DANH_GIA . '_posts_custom_column', 'eyecare_noi_dung_cot_danh_gia', 10, 2 );
