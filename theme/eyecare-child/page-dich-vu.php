<?php
/**
 * Trang danh mục dịch vụ.
 *
 * Danh sách lấy từ các trang con đã công bố của /dich-vu/. Người quản trị có
 * thể thêm, sửa, xoá hoặc đổi thứ tự trang trong WordPress mà không sửa khuôn.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icon SVG theo nhóm dịch vụ.
 *
 * @param string $slug Slug trang dịch vụ.
 * @return string
 */
function eyecare_dich_vu_icon( $slug ) {
	$icons = array(
		'kham-mat-tong-quat' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M5 24s7-11 19-11 19 11 19 11-7 11-19 11S5 24 5 24Z"/><circle cx="24" cy="24" r="6"/><path d="M24 6v5M24 37v5M6 24h5M37 24h5"/></svg>',
		'chan-doan-nhan-khoa' => '<svg viewBox="0 0 48 48" aria-hidden="true"><rect x="7" y="8" width="34" height="25" rx="4"/><path d="M13 26l6-6 5 5 5-8 6 9M18 40h12M24 33v7"/></svg>',
		'phau-thuat-phaco' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M6 24s7-11 18-11 18 11 18 11-7 11-18 11S6 24 6 24Z"/><circle cx="24" cy="24" r="8"/><path d="M24 16v16M20 19c5 2 5 8 0 10"/></svg>',
		'phau-thuat-khuc-xa' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M6 25s7-10 18-10 18 10 18 10-7 10-18 10S6 25 6 25Z"/><circle cx="24" cy="25" r="5"/><path d="m34 8 1.5 3.5L39 13l-3.5 1.5L34 18l-1.5-3.5L29 13l3.5-1.5L34 8Z"/></svg>',
		'phau-thuat-mong' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M5 24s7-11 19-11 19 11 19 11-7 11-19 11S5 24 5 24Z"/><path d="M11 24h16l-6-6M27 24l-6 6"/><circle cx="31" cy="24" r="4"/></svg>',
		'phau-thuat-quem' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M7 20c6-7 28-7 34 0M9 29c7 6 23 6 30 0"/><path d="M13 18l-2-5M19 16l-1-6M35 18l2-5M29 16l1-6"/><circle cx="24" cy="24" r="5"/></svg>',
		'mi-mat-le-dao' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M6 22s7-9 18-9 18 9 18 9-7 9-18 9S6 22 6 22Z"/><circle cx="24" cy="22" r="5"/><path d="M36 27c0 6-4 9-8 12M38 31c3 3 3 7 0 10"/></svg>',
		'xet-nghiem-truoc-phau-thuat' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M18 6h12M21 6v12L11 37a4 4 0 0 0 3.5 5h19a4 4 0 0 0 3.5-5L27 18V6"/><path d="M16 32h16M19 27h10"/></svg>',
	);

	return isset( $icons[ $slug ] )
		? $icons[ $slug ]
		: '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M6 24s7-10 18-10 18 10 18 10-7 10-18 10S6 24 6 24Z"/><circle cx="24" cy="24" r="5"/></svg>';
}

get_header();

while ( have_posts() ) :
	the_post();

	$tt       = eyecare_du_lieu_thuc_the();
	$dich_vu  = eyecare_trang_con_hub( 'dich-vu', 30 );
	$noi_dung = trim( get_the_content() );
	$co_chu   = '' !== trim( wp_strip_all_tags( strip_shortcodes( $noi_dung ) ) );
	$bang_gia = get_page_by_path( 'bang-gia', OBJECT, 'page' );

	$mo_ta = array(
		'kham-mat-tong-quat' => 'Khám mắt thường và khám theo yêu cầu, kết hợp chỉ định kiểm tra phù hợp với từng trường hợp.',
		'chan-doan-nhan-khoa' => 'Đo khúc xạ, nhãn áp, thị trường, siêu âm, chụp OCT và các kỹ thuật hỗ trợ chẩn đoán nhãn khoa.',
		'phau-thuat-phaco' => 'Thông tin về phẫu thuật thay thể thủy tinh, Phaco và các nhóm thể thủy tinh nhân tạo.',
		'phau-thuat-khuc-xa' => 'Thông tin thăm khám và lựa chọn can thiệp tật khúc xạ đang được bệnh viện cung cấp.',
		'phau-thuat-mong' => 'Các phương pháp phẫu thuật mộng đơn, mộng kép và ghép kết mạc theo chỉ định.',
		'phau-thuat-quem' => 'Thăm khám và phẫu thuật điều chỉnh quặm mi, bao gồm quặm bẩm sinh và quặm mắc phải.',
		'mi-mat-le-dao' => 'Các thủ thuật và phẫu thuật liên quan đến mi mắt, sụp mi, hở mi và đường lệ.',
		'xet-nghiem-truoc-phau-thuat' => 'Danh mục xét nghiệm được bác sĩ chỉ định khi cần đánh giá trước phẫu thuật.',
	);
	$slot_anh = array(
		'kham-mat-tong-quat' => 'dich_vu_kham_mat_tong_quat',
		'chan-doan-nhan-khoa' => 'dich_vu_chan_doan_nhan_khoa',
		'phau-thuat-phaco' => 'dich_vu_phau_thuat_phaco',
		'phau-thuat-khuc-xa' => 'dich_vu_phau_thuat_khuc_xa',
		'phau-thuat-mong' => 'dich_vu_phau_thuat_mong',
		'phau-thuat-quem' => 'dich_vu_phau_thuat_quem',
		'mi-mat-le-dao' => 'dich_vu_mi_mat_le_dao',
		'xet-nghiem-truoc-phau-thuat' => 'dich_vu_xet_nghiem_truoc_phau_thuat',
	);
	?>

<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-dich-vu' ); ?>>
	<section class="eyecare-dich-vu__hero" aria-labelledby="dich-vu-tieu-de">
		<div class="eyecare-dich-vu__nen" aria-hidden="true"></div>
		<div class="eyecare-dich-vu__khung">
			<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?>
				<div class="eyecare-dich-vu__duong-dan"><?php eyecare_duong_dan_in(); ?></div>
			<?php endif; ?>

			<div class="eyecare-dich-vu__hero-grid">
				<div>
					<p class="eyecare-dich-vu__nhan"><span aria-hidden="true"></span> Danh mục dịch vụ nhãn khoa</p>
					<h1 id="dich-vu-tieu-de"><?php the_title(); ?></h1>
					<p>Tra cứu các nhóm khám, chẩn đoán, thủ thuật và phẫu thuật đang được công bố trên website của bệnh viện.</p>
					<div class="eyecare-dich-vu__hero-nut">
						<a class="eyecare-nut eyecare-nut--vang" href="#danh-sach-dich-vu">Xem danh mục</a>
						<?php if ( $bang_gia && 'publish' === $bang_gia->post_status ) : ?>
							<a class="eyecare-nut eyecare-nut--vien-sang" href="<?php echo esc_url( get_permalink( $bang_gia->ID ) ); ?>">Tra cứu bảng giá</a>
						<?php endif; ?>
					</div>
				</div>
				<div class="eyecare-dich-vu__hero-so" aria-label="Số nhóm dịch vụ đang công khai">
					<strong><?php echo esc_html( str_pad( (string) count( $dich_vu ), 2, '0', STR_PAD_LEFT ) ); ?></strong>
					<span>nhóm dịch vụ</span>
					<svg viewBox="0 0 160 100" aria-hidden="true"><path d="M7 50s27-37 73-37 73 37 73 37-27 37-73 37S7 50 7 50Z"/><circle cx="80" cy="50" r="23"/><circle cx="80" cy="50" r="8"/></svg>
				</div>
			</div>
		</div>
	</section>

	<?php if ( $co_chu ) : ?>
		<section class="eyecare-dich-vu__gioi-thieu">
			<div class="eyecare-dich-vu__khung eyecare-trang__than"><?php the_content(); ?></div>
		</section>
	<?php endif; ?>

	<section id="danh-sach-dich-vu" class="eyecare-dich-vu__danh-sach" aria-labelledby="dich-vu-danh-sach-tieu-de">
		<div class="eyecare-dich-vu__khung">
			<header class="eyecare-dich-vu__muc-dau">
				<div>
					<p>Khám và điều trị</p>
					<h2 id="dich-vu-danh-sach-tieu-de">Chọn dịch vụ bạn đang quan tâm</h2>
				</div>
				<span>Danh mục được cập nhật trực tiếp từ các trang dịch vụ trong WordPress Admin.</span>
			</header>

			<?php if ( $dich_vu ) : ?>
				<ul class="eyecare-dich-vu__luoi">
					<?php foreach ( $dich_vu as $i => $muc ) :
						$trang = $muc['trang'];
						$slug  = get_post_field( 'post_name', $trang->ID );
						$ten   = get_the_title( $trang->ID );
						$dan   = isset( $mo_ta[ $slug ] ) ? $mo_ta[ $slug ] : wp_trim_words( wp_strip_all_tags( $trang->post_content ), 24 );
						if ( '' === trim( $dan ) ) {
							$dan = 'Xem thông tin phạm vi dịch vụ và hướng thăm khám phù hợp.';
						}
						$anh_the = isset( $slot_anh[ $slug ] ) && function_exists( 'eyecare_anh_giao_dien_lay' )
							? eyecare_anh_giao_dien_lay( $slot_anh[ $slug ], 'large' )
							: null;
						?>
						<li style="--eyecare-dv-i: <?php echo (int) $i; ?>;">
							<a class="eyecare-dich-vu__the" href="<?php echo esc_url( get_permalink( $trang->ID ) ); ?>">
								<?php if ( $anh_the ) : ?><img class="eyecare-dich-vu__the-bg" src="<?php echo esc_url( $anh_the['url'] ); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async"><?php endif; ?>
								<span class="eyecare-dich-vu__media">
									<?php if ( $anh_the ) : ?><img src="<?php echo esc_url( $anh_the['url'] ); ?>" alt="<?php echo esc_attr( $anh_the['alt'] ); ?>" loading="lazy" decoding="async"><?php endif; ?>
									<span class="eyecare-dich-vu__so"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
									<span class="eyecare-dich-vu__icon"><?php echo eyecare_dich_vu_icon( $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG nội bộ tĩnh. ?></span>
								</span>
								<span class="eyecare-dich-vu__the-copy"><strong><?php echo esc_html( $ten ); ?></strong><span><?php echo esc_html( $dan ); ?></span></span>
								<span class="eyecare-dich-vu__xem">Xem chi tiết <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<section class="eyecare-dich-vu__cta" aria-labelledby="dich-vu-cta-tieu-de">
		<div class="eyecare-dich-vu__khung eyecare-dich-vu__cta-trong">
			<div><p>Cần hướng dẫn trước khi đến?</p><h2 id="dich-vu-cta-tieu-de">Chưa biết nên chọn dịch vụ nào</h2><span>Liên hệ tổng đài để được hướng dẫn nơi tiếp nhận và thông tin cần chuẩn bị.</span></div>
			<div class="eyecare-dich-vu__cta-nut"><a class="eyecare-nut eyecare-nut--vang" href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">Gọi <?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a><a class="eyecare-nut eyecare-nut--vien-sang" href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">Thông tin liên hệ</a></div>
		</div>
	</section>
</article>

	<?php
endwhile;

get_footer();
