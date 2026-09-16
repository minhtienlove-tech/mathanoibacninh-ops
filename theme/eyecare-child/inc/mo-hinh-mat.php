<?php
/** Isolated educational eye model, rendered only by the knowledge page template. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function eyecare_eye_model_assets() {
	if ( ! is_page( 'kien-thuc' ) ) { return; }
	foreach ( array( 'css', 'js' ) as $type ) {
		$file = '/assets/eye-embed.' . $type;
		$url = get_stylesheet_directory_uri() . $file;
		$version = filemtime( get_stylesheet_directory() . $file );
		if ( 'css' === $type ) {
			wp_enqueue_style( 'eyecare-eye-embed', $url, array( 'eyecare-child-style' ), $version );
		} else {
			wp_enqueue_script( 'eyecare-eye-embed', $url, array(), $version, true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'eyecare_eye_model_assets', 120 );

function eyecare_eye_model_render() {
	$file = '/assets/eye-anatomy/index.html';
	$url = add_query_arg( 'v', filemtime( get_stylesheet_directory() . $file ), get_stylesheet_directory_uri() . $file );
	?>
	<section class="eyecare-eye" id="cau-tao-mat-3d" aria-labelledby="eyecare-eye-title">
		<div class="eyecare-eye__inner">
			<header class="eyecare-eye__heading">
				<div><p class="eyecare-eye__eyebrow">HIỂU HƠN VỀ ĐÔI MẮT</p><h2 id="eyecare-eye-title">Khám phá cấu tạo mắt qua mô hình 3D</h2></div>
				<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">Mở mô hình trong tab riêng <span aria-hidden="true">↗</span></a>
			</header>
			<p class="eyecare-eye__intro" id="eyecare-eye-help">Xoay, phóng to hoặc mở mặt cắt. Chọn một trong 12 bộ phận để đọc giải thích bằng tiếng Việt.</p>
			<iframe class="eyecare-eye__frame" data-eye-frame src="<?php echo esc_url( $url ); ?>" title="Mô hình cấu tạo mắt 3D tương tác — Bệnh viện Mắt Hà Nội – Bắc Ninh" aria-describedby="eyecare-eye-help" loading="lazy" width="1200" height="1400" referrerpolicy="same-origin"></iframe>
		</div>
	</section>
	<?php
}
