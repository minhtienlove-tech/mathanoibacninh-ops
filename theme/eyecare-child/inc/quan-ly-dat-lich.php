<?php
/** Appointment administration: private WordPress list, filters and dashboard. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function ec_booking_admin_day( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $value ) ) { return ''; }
	$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
	return $day && $day->format( 'Y-m-d' ) === $value ? $value : '';
}

function ec_booking_admin_date_label( $value ) {
	return ec_booking_admin_day( $value ) ? DateTimeImmutable::createFromFormat( '!Y-m-d', $value )->format( 'd/m/Y' ) : ( is_string( $value ) ? $value : '' );
}

function ec_booking_admin_filters( $input ) {
	$input = is_array( $input ) ? $input : array();
	$status = isset( $input['ec_status'] ) && is_string( $input['ec_status'] ) ? sanitize_key( $input['ec_status'] ) : '';
	return array(
		'day' => ec_booking_admin_day( $input['ec_day'] ?? '' ),
		'status' => array_key_exists( $status, ec_booking_statuses() ) ? $status : '',
	);
}

function ec_booking_admin_meta_query( $status ) {
	if ( ! array_key_exists( $status, ec_booking_statuses() ) ) { return array(); }
	$match = array( 'key' => '_ec_booking_status', 'value' => $status );
	return 'pending' === $status ? array( 'relation' => 'OR', $match, array( 'key' => '_ec_booking_status', 'compare' => 'NOT EXISTS' ), array( 'key' => '_ec_booking_status', 'value' => '' ) ) : array( $match );
}

/** Scope to the actual appointment admin list; never alter public queries. */
function ec_booking_admin_query( $query ) {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! $query->is_main_query() || 'ec_appointment' !== $query->get( 'post_type' ) ) { return; }
	$filters = ec_booking_admin_filters( wp_unslash( $_GET ) ); // Read-only list filters.
	$query->set( 'ec_booking_day', $filters['day'] );
	$query->set( 'ec_booking_admin_scope', true );
	if ( $filters['status'] ) { $query->set( 'meta_query', ec_booking_admin_meta_query( $filters['status'] ) ); }
	if ( ! $query->get( 'orderby' ) ) {
		// New requests first; within a selected appointment day, sort by appointment time.
		$query->set( 'orderby', $filters['day'] ? array( 'title' => 'ASC', 'ID' => 'DESC' ) : array( 'date' => 'DESC', 'ID' => 'DESC' ) );
	}
	$search = $query->get( 's' );
	if ( is_string( $search ) && preg_match( '/^[+0-9\s().-]{7,30}$/D', $search ) ) {
		$phone = preg_replace( '/[\s().-]/', '', $search );
		if ( 0 === strpos( $phone, '+84' ) ) { $phone = '0' . substr( $phone, 3 ); }
		$query->set( 's', $phone );
	}
}
add_action( 'pre_get_posts', 'ec_booking_admin_query' );

/** Existing records store canonical compact JSON; no migration or patient-data copy. */
function ec_booking_admin_where( $where, $query ) {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! $query->get( 'ec_booking_admin_scope' ) || 'ec_appointment' !== $query->get( 'post_type' ) ) { return $where; }
	$day = ec_booking_admin_day( $query->get( 'ec_booking_day' ) );
	if ( $day ) {
		global $wpdb;
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_content LIKE %s", '%' . $wpdb->esc_like( '"date":"' . $day . '"' ) . '%' );
	}
	return $where;
}
add_filter( 'posts_where', 'ec_booking_admin_where', 10, 2 );

function ec_booking_admin_filter_controls( $post_type, $which ) {
	if ( 'ec_appointment' !== $post_type || 'top' !== $which || ! current_user_can( 'manage_options' ) ) { return; }
	$filters = ec_booking_admin_filters( wp_unslash( $_GET ) );
	?>
	<label class="screen-reader-text" for="ec-booking-filter-day">Ngày khám</label>
	<input type="date" id="ec-booking-filter-day" name="ec_day" value="<?php echo esc_attr( $filters['day'] ); ?>" aria-label="Lọc theo ngày khám">
	<label class="screen-reader-text" for="ec-booking-filter-status">Trạng thái xử lý</label>
	<select id="ec-booking-filter-status" name="ec_status"><option value="">Tất cả trạng thái</option><?php foreach ( ec_booking_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
	<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=ec_appointment' ) ); ?>">Bỏ lọc</a>
	<?php
}
add_action( 'restrict_manage_posts', 'ec_booking_admin_filter_controls', 10, 2 );

/** Hide the native filter by submission month; staff filter by appointment day. */
function ec_booking_admin_disable_months( $disable, $type ) { return 'ec_appointment' === $type ? true : $disable; }
add_filter( 'disable_months_dropdown', 'ec_booking_admin_disable_months', 10, 2 );

function ec_booking_admin_count( $status = '', $day = '' ) {
	if ( ! current_user_can( 'manage_options' ) ) { return 0; }
	$query = new WP_Query( array( 'post_type' => 'ec_appointment', 'post_status' => 'private', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => false, 'ec_booking_admin_scope' => true, 'ec_booking_day' => ec_booking_admin_day( $day ), 'meta_query' => ec_booking_admin_meta_query( $status ) ) );
	return (int) $query->found_posts;
}

function ec_booking_admin_summary() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-ec_appointment' !== $screen->id || ! current_user_can( 'manage_options' ) ) { return; }
	$base = admin_url( 'edit.php?post_type=ec_appointment' );
	$today = ec_booking_now()->format( 'Y-m-d' );
	$cards = array(
		array( 'Tổng yêu cầu', ec_booking_admin_count(), $base, 'all' ),
		array( 'Chờ xác nhận', ec_booking_admin_count( 'pending' ), add_query_arg( 'ec_status', 'pending', $base ), 'pending' ),
		array( 'Có lịch hôm nay', ec_booking_admin_count( '', $today ), add_query_arg( 'ec_day', $today, $base ), 'today' ),
	);
	echo '<div class="ec-admin-intro"><p>Tiếp nhận và xử lý lịch đăng ký từ website. Tìm theo họ tên hoặc số điện thoại; bấm vào yêu cầu để xem chi tiết, cập nhật trạng thái và ghi chú.</p></div><div class="ec-admin-summary">';
	foreach ( $cards as $card ) {
		echo '<a class="ec-admin-summary__card ec-admin-summary__card--' . esc_attr( $card[3] ) . '" href="' . esc_url( $card[2] ) . '"><span>' . esc_html( $card[0] ) . '</span><strong>' . esc_html( number_format_i18n( $card[1] ) ) . '</strong></a>';
	}
	echo '</div>';
}
add_action( 'all_admin_notices', 'ec_booking_admin_summary' );

function ec_booking_admin_assets() {
	$screen = get_current_screen();
	if ( ! $screen || 'ec_appointment' !== $screen->post_type ) { return; }
	$file = '/assets/dat-lich-admin.css';
	wp_enqueue_style( 'ec-booking-admin', get_stylesheet_directory_uri() . $file, array(), filemtime( get_stylesheet_directory() . $file ) );
}
add_action( 'admin_enqueue_scripts', 'ec_booking_admin_assets' );

function ec_booking_admin_bulk_actions( $actions ) {
	unset( $actions['edit'] );
	return $actions;
}
add_filter( 'bulk_actions-edit-ec_appointment', 'ec_booking_admin_bulk_actions' );

function ec_booking_dashboard_widget() {
	if ( current_user_can( 'manage_options' ) ) {
		wp_add_dashboard_widget( 'ec-booking-overview', 'Đặt lịch khám', 'ec_booking_dashboard_content' );
	}
}
add_action( 'wp_dashboard_setup', 'ec_booking_dashboard_widget', 1000 );

function ec_booking_dashboard_content() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	echo '<p><strong>' . esc_html( number_format_i18n( ec_booking_admin_count( 'pending' ) ) ) . '</strong> yêu cầu đang chờ xác nhận.</p><p>Xem lịch đăng ký, gọi xác nhận và cập nhật kết quả xử lý.</p><p><a class="button button-primary" href="' . esc_url( admin_url( 'edit.php?post_type=ec_appointment' ) ) . '">Quản lý đặt lịch khám</a></p>';
}
