<?php
/**
 * Thông báo đặt lịch công khai: chỉ dùng nhãn đã được đồng ý và duyệt riêng.
 * Hồ sơ lịch hẹn, số điện thoại và ngày khám luôn ở CPT riêng tư.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ec_public_proof_settings() {
	$stored = get_option( 'ec_public_proof_settings', array() );
	$stored = is_array( $stored ) ? $stored : array();
	return array(
		'enabled'  => isset( $stored['enabled'] ) && '1' === (string) $stored['enabled'],
		'delay'    => isset( $stored['delay'] ) ? max( 5, min( 120, (int) $stored['delay'] ) ) : 20,
		'duration' => 5,
		'max'      => 2,
	);
}

function ec_public_proof_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	return array(
		'enabled' => isset( $input['enabled'] ) && '1' === (string) $input['enabled'] ? '1' : '0',
		'delay'   => isset( $input['delay'] ) ? max( 5, min( 120, (int) $input['delay'] ) ) : 20,
	);
}

function ec_public_proof_register_settings() {
	register_setting( 'ec_public_proof', 'ec_public_proof_settings', array(
		'type' => 'array',
		'sanitize_callback' => 'ec_public_proof_sanitize_settings',
		'default' => array( 'enabled' => '0', 'delay' => 20 ),
	) );
}
add_action( 'admin_init', 'ec_public_proof_register_settings' );

/** Cài đặt đổi thì HTML cache cũng phải đổi theo trạng thái nạp asset. */
function ec_public_proof_purge_page_cache() {
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
	do_action( 'litespeed_purge_all' );
}
add_action( 'update_option_ec_public_proof_settings', 'ec_public_proof_purge_page_cache', 10, 0 );
add_action( 'add_option_ec_public_proof_settings', 'ec_public_proof_purge_page_cache', 10, 0 );

function ec_public_proof_settings_menu() {
	add_submenu_page( 'edit.php?post_type=ec_appointment', 'Thông báo đăng ký công khai', 'Thông báo công khai', 'manage_options', 'ec-public-proof', 'ec_public_proof_settings_page' );
}
add_action( 'admin_menu', 'ec_public_proof_settings_menu' );

/** Bản xem thử là dữ liệu giả lập trong admin, không truy vấn hay sửa hồ sơ lịch khám. */
function ec_public_proof_demo_labels() {
	return array_map( static function ( $number ) {
		return sprintf( 'Khách mẫu %03d', $number );
	}, range( 1, 100 ) );
}

function ec_public_proof_admin_preview_authorized() {
	return current_user_can( 'manage_options' )
		&& isset( $_GET['post_type'], $_GET['page'], $_GET['preview'], $_GET['_wpnonce'] )
		&& is_string( $_GET['post_type'] ) && 'ec_appointment' === wp_unslash( $_GET['post_type'] )
		&& is_string( $_GET['page'] ) && 'ec-public-proof' === wp_unslash( $_GET['page'] )
		&& is_string( $_GET['preview'] ) && '1' === wp_unslash( $_GET['preview'] )
		&& is_string( $_GET['_wpnonce'] )
		&& (bool) wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ec_public_proof_admin_preview' );
}

function ec_public_proof_admin_preview_assets() {
	if ( ! ec_public_proof_admin_preview_authorized() ) {
		return;
	}
	$root = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	wp_enqueue_style( 'ec-public-proof-admin-base', $uri . '/assets/lich-kham-cong-khai.css', array(), filemtime( $root . '/assets/lich-kham-cong-khai.css' ) );
	wp_enqueue_style( 'ec-public-proof-admin-demo', $uri . '/assets/lich-kham-cong-khai-demo.css', array( 'ec-public-proof-admin-base' ), filemtime( $root . '/assets/lich-kham-cong-khai-demo.css' ) );
	wp_enqueue_script( 'ec-public-proof-admin-demo', $uri . '/assets/lich-kham-cong-khai-demo.js', array(), filemtime( $root . '/assets/lich-kham-cong-khai-demo.js' ), true );
	wp_localize_script( 'ec-public-proof-admin-demo', 'ecPublicProofDemo', array( 'labels' => ec_public_proof_demo_labels() ) );
}
add_action( 'admin_enqueue_scripts', 'ec_public_proof_admin_preview_assets' );

function ec_public_proof_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = ec_public_proof_settings();
	$preview_url = wp_nonce_url( admin_url( 'edit.php?post_type=ec_appointment&page=ec-public-proof&preview=1' ), 'ec_public_proof_admin_preview' );
	?>
	<div class="wrap"><h1>Thông báo đăng ký công khai</h1>
	<p>Chỉ hồ sơ đã xác nhận hoặc đã khám, có sự đồng ý công khai riêng và được quản trị viên duyệt mới có thể hiện tên gọi ngắn. Không dùng hồ sơ cũ chỉ có đồng ý liên hệ.</p>
	<p><a class="button button-secondary" href="<?php echo esc_url( $preview_url ); ?>">Xem thử popup với 100 dữ liệu mẫu</a> <span class="description">Chỉ quản trị viên xem được. Dữ liệu mẫu không xuất hiện trên website công khai.</span></p>
	<?php if ( ec_public_proof_admin_preview_authorized() ) : ?>
	<section class="ec-public-proof-demo" aria-label="Xem thử thông báo với dữ liệu giả lập">
		<h2>Bản xem thử trong quản trị</h2>
		<p><strong>DỮ LIỆU MẪU:</strong> 100 nhãn được tạo giả lập để kiểm tra giao diện. Không phải người bệnh hoặc lịch hẹn thật; không ghi dữ liệu vào hệ thống đặt lịch.</p>
		<p class="ec-public-proof-demo__controls"><button class="button" type="button" id="ec-public-proof-demo-toggle">Tạm dừng</button> <button class="button" type="button" id="ec-public-proof-demo-next">Mẫu tiếp theo</button> <span id="ec-public-proof-demo-counter" aria-live="polite"></span></p>
		<div class="ec-public-proof-demo__canvas">
			<aside class="ec-public-proof ec-public-proof--demo" id="ec-public-proof-demo-card" aria-label="Thông báo mô phỏng">
				<span class="ec-public-proof__icon" aria-hidden="true">✓</span>
				<p><span class="ec-public-proof-demo__badge">DỮ LIỆU MẪU</span><br><strong class="ec-public-proof__name"></strong> đã từng đăng ký lịch khám tại Bệnh viện Mắt Hà Nội – Bắc Ninh <em>(mô phỏng).</em></p>
			</aside>
		</div>
	</section>
	<?php endif; ?>
	<form action="options.php" method="post">
		<?php settings_fields( 'ec_public_proof' ); ?>
		<table class="form-table" role="presentation"><tbody>
		<tr><th scope="row">Bật thông báo</th><td><label><input type="checkbox" name="ec_public_proof_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>> Hiển thị trên website</label><p class="description">Mặc định tắt. Tắt ở đây sẽ chặn cả API công khai, kể cả khi trang đang được lưu cache.</p></td></tr>
		<tr><th scope="row"><label for="ec-proof-delay">Chờ trước lần hiện đầu</label></th><td><input id="ec-proof-delay" type="number" min="5" max="120" name="ec_public_proof_settings[delay]" value="<?php echo esc_attr( $settings['delay'] ); ?>"> giây</td></tr>
		</tbody></table>
		<?php submit_button( 'Lưu cài đặt' ); ?>
	</form>
	<p>Mỗi phiên chỉ hiện tối đa 2 lần, mỗi lần 5 giây. Người xem có thể đóng để ẩn cả phiên. Không hiển thị thời điểm đăng ký giả.</p>
	</div>
	<?php
}

/** Chỉ chấp nhận đại từ và một tên gọi ngắn; không công khai họ tên đầy đủ. */
function ec_public_proof_clean_label( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$value = trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( $value ) ) ?? '' );
	return preg_match( '/^(?:Anh|Chị|Cô|Chú|Bác) [\p{L}\p{M}]{1,16}$/uD', $value ) ? $value : '';
}

/** Không cho nhân viên nhập tên gọi không khớp với tên trên yêu cầu thật. */
function ec_public_proof_label_matches_booking( $label, $data ) {
	if ( ! is_array( $data ) || empty( $data['name'] ) || ! is_string( $data['name'] ) || ! preg_match( '/^[^ ]+ ([\p{L}\p{M}]+)$/uD', $label, $matches ) ) {
		return false;
	}
	return (bool) preg_match( '/(?:^|\s)' . preg_quote( $matches[1], '/' ) . '$/iuD', trim( $data['name'] ) );
}

function ec_public_proof_valid_date( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value ) ) {
		return false;
	}
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
	return $date && $date->format( 'Y-m-d' ) === $value && $value <= wp_date( 'Y-m-d' );
}

/** Hồ sơ mới có đồng ý trên form; hồ sơ cũ cần chứng cứ đồng ý bổ sung. */
function ec_public_proof_has_consent( $post_id, $data = null ) {
	if ( null === $data ) {
		$post = get_post( $post_id );
		$data = $post ? ec_booking_read( $post ) : array();
	}
	$new_consent = isset( $data['public_share_consent'], $data['public_share_consented_at'] )
		&& true === $data['public_share_consent']
		&& is_string( $data['public_share_consented_at'] )
		&& '' !== $data['public_share_consented_at'];
	$legacy_date = get_post_meta( $post_id, '_ec_public_proof_legacy_consent_at', true );
	$legacy_evidence = get_post_meta( $post_id, '_ec_public_proof_legacy_evidence', true );
	$legacy_consent = ec_public_proof_valid_date( $legacy_date ) && is_string( $legacy_evidence ) && '' !== trim( $legacy_evidence );
	return $new_consent || $legacy_consent;
}

function ec_public_proof_add_meta_box() {
	add_meta_box( 'ec-public-proof', 'Thông báo đăng ký công khai', 'ec_public_proof_meta_box', 'ec_appointment', 'side', 'default' );
}
add_action( 'add_meta_boxes_ec_appointment', 'ec_public_proof_add_meta_box' );

function ec_public_proof_meta_box( $post ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$data = ec_booking_read( $post );
	$status = get_post_meta( $post->ID, '_ec_booking_status', true );
	$has_new = isset( $data['public_share_consent'] ) && true === $data['public_share_consent'];
	$legacy_date = get_post_meta( $post->ID, '_ec_public_proof_legacy_consent_at', true );
	$legacy_evidence = get_post_meta( $post->ID, '_ec_public_proof_legacy_evidence', true );
	$revoked = (bool) get_post_meta( $post->ID, '_ec_public_proof_revoked_at', true );
	$approved = '1' === get_post_meta( $post->ID, '_ec_public_proof_approved', true );
	$state = $revoked ? 'revoked' : ( $approved ? 'approved' : 'hidden' );
	wp_nonce_field( 'ec_public_proof_save', 'ec_public_proof_nonce' );
	echo '<p><strong>Sự đồng ý công khai:</strong> ' . ( $has_new ? 'Đã chọn riêng trên form' : ( ec_public_proof_valid_date( $legacy_date ) && $legacy_evidence ? 'Đã lưu bằng chứng bổ sung' : 'Chưa có' ) ) . '</p>';
	echo '<p><strong>Trạng thái lịch:</strong> ' . esc_html( isset( ec_booking_statuses()[ $status ] ) ? ec_booking_statuses()[ $status ] : 'Chờ xác nhận' ) . '</p>';
	{
		echo '<p>Với lịch cũ hoặc khi cần mở lại sau thu hồi, chỉ ghi nhận nếu người đăng ký đã đồng ý riêng việc hiển thị tên gọi trên website. Không dùng ô đồng ý liên hệ ban đầu.</p>';
		echo '<p><label for="ec-proof-legacy-date">Ngày đồng ý riêng</label><br><input id="ec-proof-legacy-date" type="date" name="ec_proof_legacy_date" value="' . esc_attr( $legacy_date ) . '" max="' . esc_attr( wp_date( 'Y-m-d' ) ) . '"></p>';
		echo '<p><label for="ec-proof-legacy-evidence">Mã hồ sơ/chứng cứ lưu nội bộ</label><br><input id="ec-proof-legacy-evidence" type="text" maxlength="200" class="widefat" name="ec_proof_legacy_evidence" value="' . esc_attr( $legacy_evidence ) . '"></p>';
		echo '<p><label><input type="checkbox" name="ec_proof_record_legacy" value="1"> Xác nhận đã đối chiếu sự đồng ý riêng</label></p>';
	}
	echo '<p><label for="ec-proof-label">Tên gọi công khai (ví dụ: Anh Tú)</label><br><input id="ec-proof-label" type="text" maxlength="30" class="widefat" name="ec_proof_label" value="' . esc_attr( get_post_meta( $post->ID, '_ec_public_proof_label', true ) ) . '"></p>';
	echo '<p class="description">Chỉ đại từ và một tên riêng; không nhập họ tên đầy đủ, số điện thoại hoặc tình trạng sức khỏe.</p>';
	echo '<p><label><input type="radio" name="ec_proof_state" value="hidden" ' . checked( $state, 'hidden', false ) . '> Ẩn</label><br>';
	echo '<label><input type="radio" name="ec_proof_state" value="approved" ' . checked( $state, 'approved', false ) . '> Duyệt hiển thị</label><br>';
	echo '<label><input type="radio" name="ec_proof_state" value="revoked" ' . checked( $state, 'revoked', false ) . '> Thu hồi/không dùng nữa</label></p>';
	echo '<p class="description">Sau khi thu hồi, muốn hiện lại phải có sự đồng ý mới và lưu bằng chứng mới. Chỉ trạng thái Đã xác nhận hoặc Đã khám mới đủ điều kiện.</p>';
}

function ec_public_proof_save_meta( $post_id ) {
	if ( ! current_user_can( 'manage_options' ) || 'ec_appointment' !== get_post_type( $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['ec_public_proof_nonce'] ) || ! is_string( $_POST['ec_public_proof_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ec_public_proof_nonce'] ) ), 'ec_public_proof_save' ) ) {
		return;
	}
	$post = get_post( $post_id );
	if ( ! $post || 'private' !== $post->post_status ) {
		return;
	}
	$data = ec_booking_read( $post );
	$has_new = isset( $data['public_share_consent'] ) && true === $data['public_share_consent'];
	$recorded_fresh_consent = false;
	if ( isset( $_POST['ec_proof_record_legacy'], $_POST['ec_proof_legacy_date'], $_POST['ec_proof_legacy_evidence'] ) && is_string( $_POST['ec_proof_record_legacy'] ) && '1' === wp_unslash( $_POST['ec_proof_record_legacy'] ) && is_string( $_POST['ec_proof_legacy_date'] ) && is_string( $_POST['ec_proof_legacy_evidence'] ) ) {
		$date = sanitize_text_field( wp_unslash( $_POST['ec_proof_legacy_date'] ) );
		$evidence = sanitize_text_field( wp_unslash( $_POST['ec_proof_legacy_evidence'] ) );
		$revoked_at = (int) get_post_meta( $post_id, '_ec_public_proof_revoked_at', true );
		$after_revocation = ! $revoked_at || $date >= wp_date( 'Y-m-d', $revoked_at );
		$previous_evidence = (string) get_post_meta( $post_id, '_ec_public_proof_legacy_evidence', true );
		$new_evidence = ! $revoked_at || ( '' !== $evidence && ! hash_equals( $previous_evidence, $evidence ) );
		if ( ec_public_proof_valid_date( $date ) && $after_revocation && $new_evidence && '' !== trim( $evidence ) && strlen( $evidence ) <= 200 ) {
			update_post_meta( $post_id, '_ec_public_proof_legacy_consent_at', $date );
			update_post_meta( $post_id, '_ec_public_proof_legacy_evidence', $evidence );
			update_post_meta( $post_id, '_ec_public_proof_legacy_recorded_by', get_current_user_id() );
			update_post_meta( $post_id, '_ec_public_proof_legacy_recorded_at', time() );
			$recorded_fresh_consent = true;
		}
	}
	$label = isset( $_POST['ec_proof_label'] ) ? ec_public_proof_clean_label( wp_unslash( $_POST['ec_proof_label'] ) ) : '';
	if ( '' !== $label ) {
		update_post_meta( $post_id, '_ec_public_proof_label', $label );
	} else {
		delete_post_meta( $post_id, '_ec_public_proof_label' );
	}
	$state = isset( $_POST['ec_proof_state'] ) && is_string( $_POST['ec_proof_state'] ) ? sanitize_key( wp_unslash( $_POST['ec_proof_state'] ) ) : 'hidden';
	if ( 'revoked' === $state ) {
		update_post_meta( $post_id, '_ec_public_proof_revoked_at', time() );
		delete_post_meta( $post_id, '_ec_public_proof_approved' );
		return;
	}
	if ( 'approved' === $state && '' !== $label && ec_public_proof_label_matches_booking( $label, $data ) && in_array( get_post_meta( $post_id, '_ec_booking_status', true ), array( 'confirmed', 'completed' ), true ) && ec_public_proof_has_consent( $post_id, $data ) ) {
		$revoked_at = (int) get_post_meta( $post_id, '_ec_public_proof_revoked_at', true );
		if ( ! $revoked_at || $recorded_fresh_consent ) {
			if ( $revoked_at ) {
				delete_post_meta( $post_id, '_ec_public_proof_revoked_at' );
			}
			update_post_meta( $post_id, '_ec_public_proof_approved', '1' );
			return;
		}
	}
	delete_post_meta( $post_id, '_ec_public_proof_approved' );
}
add_action( 'save_post_ec_appointment', 'ec_public_proof_save_meta', 20 );

/** Chỉ trả một tên gọi đã duyệt cho mỗi lần yêu cầu; không xuất hồ sơ gốc. */
function ec_public_proof_eligible_labels() {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT p.ID FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->postmeta} a ON a.post_id = p.ID
		 INNER JOIN {$wpdb->postmeta} s ON s.post_id = p.ID
		 WHERE p.post_type = %s AND p.post_status = %s
		 AND a.meta_key = %s AND a.meta_value = %s
		 AND s.meta_key = %s AND s.meta_value IN (%s, %s)
		 ORDER BY p.ID ASC",
		'ec_appointment', 'private', '_ec_public_proof_approved', '1', '_ec_booking_status', 'confirmed', 'completed'
	) );
	$labels = array();
	foreach ( $ids as $id ) {
		$id = (int) $id;
		if ( get_post_meta( $id, '_ec_public_proof_revoked_at', true ) || ! ec_public_proof_has_consent( $id ) ) {
			continue;
		}
		$post = get_post( $id );
		$data = $post ? ec_booking_read( $post ) : array();
		$label = ec_public_proof_clean_label( get_post_meta( $id, '_ec_public_proof_label', true ) );
		if ( '' !== $label && ec_public_proof_label_matches_booking( $label, $data ) ) {
			$labels[] = $label;
		}
	}
	// Một tên gọi có thể xuất hiện trên nhiều yêu cầu khám; chỉ hiện một lần trong vòng xoay.
	return array_values( array_unique( $labels ) );
}

function ec_public_proof_ajax_next() {
	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
	if ( ! ec_public_proof_settings()['enabled'] ) {
		wp_send_json_success( array( 'label' => '', 'next_cursor' => 0, 'has_multiple' => false ) );
	}
	$labels = ec_public_proof_eligible_labels();
	$count = count( $labels );
	if ( ! $count ) {
		wp_send_json_success( array( 'label' => '', 'next_cursor' => 0, 'has_multiple' => false ) );
	}
	$cursor = isset( $_GET['cursor'] ) && is_scalar( $_GET['cursor'] ) ? absint( wp_unslash( $_GET['cursor'] ) ) : 0;
	$index = $cursor % $count;
	wp_send_json_success( array(
		'label' => $labels[ $index ],
		'next_cursor' => ( $index + 1 ) % $count,
		'has_multiple' => $count > 1,
	) );
}
add_action( 'wp_ajax_ec_public_proof_next', 'ec_public_proof_ajax_next' );
add_action( 'wp_ajax_nopriv_ec_public_proof_next', 'ec_public_proof_ajax_next' );

function ec_public_proof_enqueue() {
	$settings = ec_public_proof_settings();
	if ( ! $settings['enabled'] || is_admin() || is_feed() ) {
		return;
	}
	$root = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	wp_enqueue_style( 'ec-public-proof', $uri . '/assets/lich-kham-cong-khai.css', array(), filemtime( $root . '/assets/lich-kham-cong-khai.css' ) );
	wp_enqueue_script( 'ec-public-proof', $uri . '/assets/lich-kham-cong-khai.js', array(), filemtime( $root . '/assets/lich-kham-cong-khai.js' ), true );
	wp_localize_script( 'ec-public-proof', 'ecPublicProof', array(
		'endpoint' => admin_url( 'admin-ajax.php' ),
		'delayMs' => $settings['delay'] * 1000,
		'durationMs' => $settings['duration'] * 1000,
		'maxPerSession' => $settings['max'],
	) );
}
add_action( 'wp_enqueue_scripts', 'ec_public_proof_enqueue' );

function ec_public_proof_markup() {
	if ( ! ec_public_proof_settings()['enabled'] || is_admin() || is_feed() ) {
		return;
	}
	echo '<aside class="ec-public-proof" id="ec-public-proof" hidden aria-label="Thông tin đăng ký lịch khám"><button class="ec-public-proof__close" type="button" aria-label="Đóng thông báo đăng ký">×</button><span class="ec-public-proof__icon" aria-hidden="true">✓</span><p><strong class="ec-public-proof__name"></strong> đã từng đăng ký lịch khám tại Bệnh viện Mắt Hà Nội – Bắc Ninh.</p></aside>';
}
// WordPress in footer in script ở priority 20; markup phải có trước khi JS chạy.
add_action( 'wp_footer', 'ec_public_proof_markup', 10 );
