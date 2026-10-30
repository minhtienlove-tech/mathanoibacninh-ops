<?php
/**
 * Trang form đặt lịch để website đối tác nhúng qua iframe: /?ec_booking_embed=pk_...
 * Chỉ domain đã khai báo cho khóa đó được nhúng (CSP frame-ancestors); khóa sai hoặc tạm dừng trả 404.
 * Website đối tác dùng assets/dat-lich-nhung.js để chèn iframe và tự chỉnh chiều cao.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ec_booking_embed_render() {
	if ( ! isset( $_GET['ec_booking_embed'] ) || ! is_string( $_GET['ec_booking_embed'] ) ) {
		return;
	}
	$client = ec_booking_api_client( wp_unslash( $_GET['ec_booking_embed'] ) );
	// Danh sách domain có thể đổi bất kỳ lúc nào nên trang này không được lưu bộ nhớ đệm.
	nocache_headers();
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	do_action( 'litespeed_control_set_nocache', 'booking embed' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header_remove( 'X-Frame-Options' );
	if ( ! $client || ! $client['origins'] ) {
		header( 'Content-Security-Policy: ' . ec_booking_api_frame_policy( null ) );
		status_header( 404 );
		echo '<!doctype html><meta charset="utf-8"><title>Không tìm thấy</title><p>Form đặt lịch này chưa được kích hoạt.</p>';
		exit;
	}
	header( 'Content-Security-Policy: ' . ec_booking_api_frame_policy( $client ) );
	$theme = get_stylesheet_directory();
	$uri   = get_stylesheet_directory_uri();
	$info  = eyecare_du_lieu_thuc_the();
	?>
<!doctype html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title>Đặt lịch khám – <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( $uri . '/assets/lien-he-noi.css?ver=' . filemtime( $theme . '/assets/lien-he-noi.css' ) ); ?>">
	<style>
		body { margin: 0; padding: 16px; background: #fff; color: #193b30; font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
		.ec-booking-embed__title { margin: 0 0 4px; font-size: 1.35rem; }
		.ec-booking-embed__by { margin: 0 0 12px; color: #576b62; font-size: .9rem; }
	</style>
</head>
<body class="ec-booking-page ec-booking-embed">
	<h1 class="ec-booking-embed__title">Đặt lịch khám</h1>
	<p class="ec-booking-embed__by"><?php echo esc_html( $info['ten'] ?? get_bloginfo( 'name' ) ); ?></p>
	<?php eyecare_booking_form( 'embed', $client['public_key'] ); ?>
	<script src="<?php echo esc_url( $uri . '/assets/lien-he-noi.js?ver=' . filemtime( $theme . '/assets/lien-he-noi.js' ) ); ?>"></script>
	<script>
	// Báo chiều cao cho trang đối tác; chỉ gửi một con số, không gửi dữ liệu form.
	(function () {
		if (window.parent === window) return;
		var last = 0;
		function send() {
			var height = Math.ceil(document.documentElement.scrollHeight);
			if (height !== last) { last = height; window.parent.postMessage({type: 'ec-booking-height', height: height}, '*'); }
		}
		if (window.ResizeObserver) new ResizeObserver(send).observe(document.body);
		window.addEventListener('load', send);
		document.addEventListener('toggle', send, true);
		send();
	}());
	</script>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'ec_booking_embed_render', 0 );
