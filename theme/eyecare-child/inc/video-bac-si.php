<?php
/**
 * Video bác sĩ do biên tập viên chọn, chỉ hiển thị sau khi xác nhận quyền đăng.
 *
 * Cách tích hợp: require_once file này từ functions.php, rồi gọi một trong ba
 * hàm eyecare_video_bs_home_html(), eyecare_video_bs_profile_html( $doctor_id )
 * hoặc eyecare_video_bs_article_html( $article_id ) tại vị trí cần hiển thị.
 * Các hàm trả chuỗi rỗng khi chưa có video đủ điều kiện.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const EYECARE_VIDEO_BS_POST_TYPE = 'eyecare_video_bs';
const EYECARE_VIDEO_BS_URL = '_eyecare_video_bs_url';
const EYECARE_VIDEO_BS_DOCTOR = '_eyecare_video_bs_doctor';
const EYECARE_VIDEO_BS_ARTICLE = '_eyecare_video_bs_article';
const EYECARE_VIDEO_BS_HOME = '_eyecare_video_bs_home';
const EYECARE_VIDEO_BS_APPROVED = '_eyecare_video_bs_approved';

/** Admin-only CPT: individual video pages and public archives are not needed. */
function eyecare_video_bs_register_type() {
	register_post_type(
		EYECARE_VIDEO_BS_POST_TYPE,
		array(
			'labels' => array(
				'name'               => 'Video bác sĩ',
				'singular_name'      => 'Video bác sĩ',
				'menu_name'          => 'Video bác sĩ',
				'add_new_item'       => 'Thêm video bác sĩ',
				'edit_item'          => 'Sửa video bác sĩ',
				'all_items'          => 'Tất cả video bác sĩ',
				'featured_image'     => 'Ảnh poster video',
				'set_featured_image' => 'Chọn ảnh poster',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_position'       => 22,
			'menu_icon'           => 'dashicons-video-alt3',
			'supports'            => array( 'title', 'thumbnail', 'revisions' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'eyecare_video_bs_register_type', 6 );

/**
 * Validate a source and produce a fixed player URL. Arbitrary oEmbed HTML is
 * never accepted. MP4 must resolve to an actual WordPress Media attachment.
 *
 * @param string $raw_url Entered source URL.
 * @return array{kind:string,player_url:string,source_url:string}|array Empty on failure.
 */
function eyecare_video_bs_source( $raw_url ) {
	$url = trim( (string) $raw_url );
	if ( '' === $url || strlen( $url ) > 2048 ) {
		return array();
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) || 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) )
		|| empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] )
		|| isset( $parts['port'] ) || isset( $parts['fragment'] ) ) {
		return array();
	}

	$host = strtolower( $parts['host'] );
	$path = $parts['path'] ?? '/';
	$query = array();
	if ( ! empty( $parts['query'] ) ) {
		parse_str( $parts['query'], $query );
	}

	$youtube_hosts = array( 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'www.youtube-nocookie.com', 'youtube-nocookie.com' );
	if ( in_array( $host, $youtube_hosts, true ) ) {
		$id = '';
		if ( 'youtu.be' === $host || 'www.youtu.be' === $host ) {
			if ( preg_match( '~^/([A-Za-z0-9_-]{11})/?$~D', $path, $match ) ) {
				$id = $match[1];
			}
		} elseif ( '/watch' === $path && isset( $query['v'] ) && is_string( $query['v'] ) ) {
			$id = $query['v'];
		} elseif ( preg_match( '~^/(?:embed|shorts|live)/([A-Za-z0-9_-]{11})/?$~D', $path, $match ) ) {
			$id = $match[1];
		}
		if ( preg_match( '/^[A-Za-z0-9_-]{11}$/D', $id ) ) {
			return array(
				'kind'       => 'embed',
				'portrait'   => 0 === strpos( $path, '/shorts/' ),
				'player_url' => 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0',
				'source_url' => 'https://www.youtube.com/watch?v=' . $id,
			);
		}
		return array();
	}

	if ( in_array( $host, array( 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com' ), true ) ) {
		$pattern = 'player.vimeo.com' === $host ? '~^/video/([0-9]+)/?$~D' : '~^/([0-9]+)(?:/([a-fA-F0-9]{8,64}))?/?$~D';
		if ( preg_match( $pattern, $path, $match ) ) {
			$hash = $match[2] ?? '';
			if ( $query ) {
				if ( $hash || 1 !== count( $query ) || ! isset( $query['h'] ) || ! is_string( $query['h'] ) || ! preg_match( '/^[a-fA-F0-9]{8,64}$/D', $query['h'] ) ) {
					return array();
				}
				$hash = $query['h'];
			}
			return array(
				'kind'       => 'embed',
				'player_url' => 'https://player.vimeo.com/video/' . $match[1] . ( $hash ? '?h=' . $hash : '' ),
				'source_url' => 'https://vimeo.com/' . $match[1] . ( $hash ? '/' . $hash : '' ),
			);
		}
		return array();
	}

	// A media-library URL may use a configured CDN, so validate its attachment
	// identity and MIME instead of assuming the site's hostname.
	if ( ! preg_match( '/\.mp4$/iD', $path ) ) {
		return array();
	}
	$attachment_id = attachment_url_to_postid( $url );
	$media_url     = $attachment_id ? wp_get_attachment_url( $attachment_id ) : false;
	if ( ! $media_url || 'video/mp4' !== get_post_mime_type( $attachment_id ) || $url !== $media_url ) {
		return array();
	}
	return array(
		'kind'       => 'mp4',
		'player_url' => $media_url,
		'source_url' => $media_url,
	);
}

/** Required relationships and poster must still exist and be public. */
function eyecare_video_bs_post_is( $id, $type ) {
	return $id > 0 && $type === get_post_type( $id ) && 'publish' === get_post_status( $id );
}

/** Read submitted fields when possible, otherwise inspect persisted metadata. */
function eyecare_video_bs_fields( $post_id, $submitted = false ) {
	if ( $submitted ) {
		return array(
			'url'      => isset( $_POST['eyecare_video_bs_url'] ) ? trim( (string) wp_unslash( $_POST['eyecare_video_bs_url'] ) ) : '',
			'doctor'   => isset( $_POST['eyecare_video_bs_doctor'] ) ? absint( wp_unslash( $_POST['eyecare_video_bs_doctor'] ) ) : 0,
			'article'  => isset( $_POST['eyecare_video_bs_article'] ) ? absint( wp_unslash( $_POST['eyecare_video_bs_article'] ) ) : 0,
			'home'     => isset( $_POST['eyecare_video_bs_home'] ) ? 1 : 0,
			'approved' => isset( $_POST['eyecare_video_bs_approved'] ) ? 1 : 0,
			'poster'   => isset( $_POST['_thumbnail_id'] ) ? max( 0, (int) wp_unslash( $_POST['_thumbnail_id'] ) ) : get_post_thumbnail_id( $post_id ),
		);
	}
	return array(
		'url'      => (string) get_post_meta( $post_id, EYECARE_VIDEO_BS_URL, true ),
		'doctor'   => (int) get_post_meta( $post_id, EYECARE_VIDEO_BS_DOCTOR, true ),
		'article'  => (int) get_post_meta( $post_id, EYECARE_VIDEO_BS_ARTICLE, true ),
		'home'     => (int) get_post_meta( $post_id, EYECARE_VIDEO_BS_HOME, true ),
		'approved' => (int) get_post_meta( $post_id, EYECARE_VIDEO_BS_APPROVED, true ),
		'poster'   => (int) get_post_thumbnail_id( $post_id ),
	);
}

/** All conditions are checked both before publishing and before rendering. */
function eyecare_video_bs_publishable( $title, $fields ) {
	return '' !== trim( wp_strip_all_tags( (string) $title ) )
		&& 1 === (int) $fields['approved']
		&& ! empty( eyecare_video_bs_source( $fields['url'] ) )
		&& eyecare_video_bs_post_is( (int) $fields['doctor'], 'eyecare_bac_si' )
		&& ( 0 === (int) $fields['article'] || eyecare_video_bs_post_is( (int) $fields['article'], 'post' ) )
		&& ! empty( $fields['poster'] )
		&& wp_attachment_is_image( (int) $fields['poster'] );
}

/** Refuse Publish/Schedule when a required field is absent or becomes invalid. */
function eyecare_video_bs_guard_publish( $data, $postarr ) {
	if ( EYECARE_VIDEO_BS_POST_TYPE !== ( $data['post_type'] ?? '' )
		|| ! in_array( $data['post_status'] ?? '', array( 'publish', 'future' ), true ) ) {
		return $data;
	}
	$post_id   = absint( $postarr['ID'] ?? 0 );
	$submitted = isset( $_POST['eyecare_video_bs_nonce'] )
		&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eyecare_video_bs_nonce'] ) ), 'eyecare_video_bs_save' );
	$fields = eyecare_video_bs_fields( $post_id, $submitted );
	if ( ! eyecare_video_bs_publishable( $data['post_title'] ?? '', $fields ) ) {
		$data['post_status'] = 'draft';
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'eyecare_video_bs_guard_publish', 10, 2 );

/** Explain a rejected Publish action in the editor after WordPress redirects. */
function eyecare_video_bs_redirect_notice( $location, $post_id ) {
	if ( EYECARE_VIDEO_BS_POST_TYPE !== get_post_type( $post_id ) || 'draft' !== get_post_status( $post_id ) ) {
		return $location;
	}
	$requested = isset( $_POST['publish'] )
		|| ( isset( $_POST['post_status'] ) && in_array( sanitize_key( wp_unslash( $_POST['post_status'] ) ), array( 'publish', 'future' ), true ) );
	return $requested ? add_query_arg( 'eyecare_video_bs_blocked', '1', $location ) : $location;
}
add_filter( 'redirect_post_location', 'eyecare_video_bs_redirect_notice', 10, 2 );

function eyecare_video_bs_admin_notice() {
	$screen = get_current_screen();
	if ( ! $screen || EYECARE_VIDEO_BS_POST_TYPE !== $screen->post_type
		|| ! isset( $_GET['eyecare_video_bs_blocked'] ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>Video vẫn là bản nháp. Cần tiêu đề, URL YouTube/Vimeo hoặc MP4 trong Thư viện Media, bác sĩ đang công khai, ảnh poster và xác nhận quyền đăng. Bài viết liên quan nếu được chọn cũng phải đang công khai.</p></div>';
}
add_action( 'admin_notices', 'eyecare_video_bs_admin_notice' );

/** Editor controls: associations are explicit; no article or doctor is inferred. */
function eyecare_video_bs_add_box() {
	add_meta_box( 'eyecare-video-bs-details', 'Nguồn video và quyền đăng', 'eyecare_video_bs_box', EYECARE_VIDEO_BS_POST_TYPE, 'normal', 'high' );
}
add_action( 'add_meta_boxes_' . EYECARE_VIDEO_BS_POST_TYPE, 'eyecare_video_bs_add_box' );

function eyecare_video_bs_box( $post ) {
	wp_nonce_field( 'eyecare_video_bs_save', 'eyecare_video_bs_nonce' );
	$fields = eyecare_video_bs_fields( $post->ID );
	$doctors = get_posts( array( 'post_type' => 'eyecare_bac_si', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$articles = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
	?>
	<p>Chỉ dùng video và ảnh poster đã có quyền đăng. Video có người bệnh nhận diện được phải có đồng thuận phù hợp trước khi chọn ô xác nhận. Chọn <strong>Ảnh poster video</strong> ở cột bên phải.</p>
	<p><label for="eyecare-video-bs-url"><strong>URL video *</strong></label><br>
	<input id="eyecare-video-bs-url" name="eyecare_video_bs_url" type="url" inputmode="url" style="width:100%" value="<?php echo esc_attr( $fields['url'] ); ?>" placeholder="https://www.youtube.com/watch?v=…" required>
	<small>YouTube, Vimeo hoặc URL MP4 từ Thư viện Media của WordPress; dùng HTTPS. Trình phát chỉ tải khi người xem bấm.</small></p>
	<p><label for="eyecare-video-bs-doctor"><strong>Bác sĩ trong video *</strong></label><br>
	<select id="eyecare-video-bs-doctor" name="eyecare_video_bs_doctor" required>
	<option value="">— Chọn bác sĩ —</option>
	<?php foreach ( $doctors as $doctor ) : ?>
	<option value="<?php echo esc_attr( $doctor->ID ); ?>" <?php selected( $fields['doctor'], $doctor->ID ); ?>><?php echo esc_html( get_the_title( $doctor ) ); ?></option>
	<?php endforeach; ?>
	</select></p>
	<p><label for="eyecare-video-bs-article"><strong>Bài viết liên quan</strong></label><br>
	<select id="eyecare-video-bs-article" name="eyecare_video_bs_article">
	<option value="0">— Không gắn bài viết —</option>
	<?php foreach ( $articles as $article ) : ?>
	<option value="<?php echo esc_attr( $article->ID ); ?>" <?php selected( $fields['article'], $article->ID ); ?>><?php echo esc_html( get_the_title( $article ) ); ?> (#<?php echo esc_html( $article->ID ); ?>)</option>
	<?php endforeach; ?>
	</select><br><small>Chỉ hiện ở bài được chọn; không tự gắn theo từ khóa.</small></p>
	<p><label><input type="checkbox" name="eyecare_video_bs_home" value="1" <?php checked( $fields['home'], 1 ); ?>> Giới thiệu trên trang chủ</label></p>
	<p><label><input type="checkbox" name="eyecare_video_bs_approved" value="1"> <strong>Tôi xác nhận đã kiểm tra quyền đăng video, hình ảnh poster, hình/giọng của bác sĩ và đồng thuận của mọi người bệnh có thể nhận diện.</strong></label></p>
	<p><small>Cần xác nhận lại mỗi lần cập nhật để quyền đăng không tự chuyển sang video hoặc poster khác. Thiếu tiêu đề, URL hợp lệ, bác sĩ đang công khai, poster hoặc xác nhận trên sẽ giữ video ở trạng thái Bản nháp.</small></p>
	<?php
}

function eyecare_video_bs_save( $post_id ) {
	if ( ! isset( $_POST['eyecare_video_bs_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['eyecare_video_bs_nonce'] ) ), 'eyecare_video_bs_save' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = eyecare_video_bs_fields( $post_id, true );
	$url    = esc_url_raw( $fields['url'], array( 'https' ) );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_URL, $url );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_DOCTOR, $fields['doctor'] );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_ARTICLE, $fields['article'] );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_HOME, $fields['home'] );
	update_post_meta( $post_id, EYECARE_VIDEO_BS_APPROVED, $fields['approved'] );
}
add_action( 'save_post_' . EYECARE_VIDEO_BS_POST_TYPE, 'eyecare_video_bs_save' );

/** Load only on the three intended frontend contexts. */
function eyecare_video_bs_enqueue() {
	if ( ! is_front_page() && ! is_singular( array( 'eyecare_bac_si', 'post' ) ) ) {
		return;
	}
	$base = get_stylesheet_directory();
	$uri  = get_stylesheet_directory_uri();
	wp_enqueue_style( 'eyecare-video-bs', $uri . '/assets/video-bac-si.css', array(), filemtime( $base . '/assets/video-bac-si.css' ) );
	wp_enqueue_script( 'eyecare-video-bs', $uri . '/assets/video-bac-si.js', array(), filemtime( $base . '/assets/video-bac-si.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'eyecare_video_bs_enqueue', 109 );

/** Return curated, currently valid videos for one placement. */
function eyecare_video_bs_items( $placement, $related_id = 0, $limit = 3 ) {
	$limit = min( 6, max( 1, absint( $limit ) ) );
	$filter = array( 'key' => EYECARE_VIDEO_BS_APPROVED, 'value' => '1' );
	if ( 'home' === $placement ) {
		$filter = array( 'key' => EYECARE_VIDEO_BS_HOME, 'value' => '1' );
	} elseif ( 'profile' === $placement ) {
		if ( ! eyecare_video_bs_post_is( absint( $related_id ), 'eyecare_bac_si' ) ) {
			return array();
		}
		$filter = array( 'key' => EYECARE_VIDEO_BS_DOCTOR, 'value' => (string) absint( $related_id ) );
	} elseif ( 'article' === $placement ) {
		if ( ! eyecare_video_bs_post_is( absint( $related_id ), 'post' ) ) {
			return array();
		}
		$filter = array( 'key' => EYECARE_VIDEO_BS_ARTICLE, 'value' => (string) absint( $related_id ) );
	} else {
		return array();
	}
	$ids = get_posts( array(
		'post_type'           => EYECARE_VIDEO_BS_POST_TYPE,
		'post_status'         => 'publish',
		'posts_per_page'      => 100,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'meta_query'          => array(
			'relation' => 'AND',
			array( 'key' => EYECARE_VIDEO_BS_APPROVED, 'value' => '1' ),
			$filter,
		),
	) );
	$items = array();
	foreach ( $ids as $id ) {
		$fields = eyecare_video_bs_fields( $id );
		if ( ! eyecare_video_bs_publishable( get_the_title( $id ), $fields ) ) {
			continue;
		}
		$source = eyecare_video_bs_source( $fields['url'] );
		$items[] = array( 'id' => $id, 'fields' => $fields, 'source' => $source );
		if ( count( $items ) >= $limit ) {
			break;
		}
	}
	return $items;
}

/** Build compact cards. The poster link is a real URL without JavaScript. */
function eyecare_video_bs_markup( $items, $heading, $carousel = false ) {
	if ( ! $items ) {
		return '';
	}
	$carousel = $carousel && count( $items ) > 1;
	$heading_id = wp_unique_id( 'eyecare-video-bs-' );
	$html = '<section class="eyecare-video-bs' . ( $carousel ? ' eyecare-video-bs--carousel' : '' ) . '" aria-labelledby="' . esc_attr( $heading_id ) . '"' . ( $carousel ? ' aria-roledescription="carousel"' : '' ) . '>';
	$html .= '<div class="eyecare-video-bs__inner"><h2 id="' . esc_attr( $heading_id ) . '">' . esc_html( $heading ) . '</h2>';
	if ( $carousel ) {
		$html .= '<div class="eyecare-video-bs__viewport">';
	}
	$html .= '<div class="eyecare-video-bs__grid">';
	foreach ( $items as $position => $item ) {
		$id = $item['id'];
		$title = get_the_title( $id );
		$doctor_id = $item['fields']['doctor'];
		$doctor_name = get_the_title( $doctor_id );
		$doctor_url = get_permalink( $doctor_id );
		$article_id = (int) $item['fields']['article'];
		$source = $item['source'];
		$html .= '<article class="eyecare-video-bs__card"' . ( $carousel ? ' role="group" aria-roledescription="slide" aria-label="Video ' . esc_attr( (string) ( $position + 1 ) ) . ' / ' . esc_attr( (string) count( $items ) ) . '"' : '' ) . '>';
		$html .= '<div class="eyecare-video-bs__stage' . ( ! empty( $source['portrait'] ) ? ' is-portrait' : '' ) . '">';
		$html .= '<a class="eyecare-video-bs__load" href="' . esc_url( $source['source_url'] ) . '" target="_blank" rel="noopener noreferrer" data-eyecare-video-kind="' . esc_attr( $source['kind'] ) . '" data-eyecare-video-portrait="' . ( ! empty( $source['portrait'] ) ? '1' : '0' ) . '" data-eyecare-video-src="' . esc_url( $source['player_url'] ) . '" data-eyecare-video-title="' . esc_attr( $title ) . '" aria-label="Mở trình phát video: ' . esc_attr( $title ) . '">';
		$html .= get_the_post_thumbnail( $id, 'medium_large', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) );
		$html .= '<span class="eyecare-video-bs__play" aria-hidden="true">▶</span><span class="eyecare-video-bs__load-label">Mở trình phát</span></a>';
		$html .= '</div><div class="eyecare-video-bs__copy"><h3>' . esc_html( $title ) . '</h3>';
		$html .= '<p>Bác sĩ: <a href="' . esc_url( $doctor_url ) . '">' . esc_html( $doctor_name ) . '</a></p>';
		if ( $article_id && eyecare_video_bs_post_is( $article_id, 'post' ) ) {
			$html .= '<p><a href="' . esc_url( get_permalink( $article_id ) ) . '">Đọc bài liên quan: ' . esc_html( get_the_title( $article_id ) ) . '</a></p>';
		}
		$html .= '</div></article>';
	}
	$html .= '</div>';
	if ( $carousel ) {
		$html .= '</div><div class="eyecare-video-bs__controls" aria-label="Điều khiển video bác sĩ">';
		$html .= '<button class="eyecare-video-bs__previous" type="button" aria-label="Video trước">←</button>';
		$html .= '<div class="eyecare-video-bs__dots">';
		foreach ( $items as $position => $item ) {
			$html .= '<button type="button" class="eyecare-video-bs__dot" data-eyecare-slide="' . esc_attr( (string) $position ) . '" aria-label="Xem video ' . esc_attr( (string) ( $position + 1 ) ) . ' / ' . esc_attr( (string) count( $items ) ) . '"></button>';
		}
		$html .= '</div><button class="eyecare-video-bs__next" type="button" aria-label="Video tiếp theo">→</button>';
		$html .= '<button class="eyecare-video-bs__pause" type="button" aria-label="Tạm dừng tự chuyển video">Tạm dừng</button>';
		$html .= '<span class="eyecare-video-bs__announcement" aria-live="polite" aria-atomic="true"></span></div>';
	}
	return $html . '</div></section>';
}

function eyecare_video_bs_home_html( $limit = 3 ) {
	return eyecare_video_bs_markup( eyecare_video_bs_items( 'home', 0, $limit ), 'Video bác sĩ', true );
}

function eyecare_video_bs_profile_html( $doctor_id, $limit = 3 ) {
	return eyecare_video_bs_markup( eyecare_video_bs_items( 'profile', $doctor_id, $limit ), 'Video của bác sĩ' );
}

function eyecare_video_bs_article_html( $article_id, $limit = 2 ) {
	return eyecare_video_bs_markup( eyecare_video_bs_items( 'article', $article_id, $limit ), 'Video liên quan' );
}
