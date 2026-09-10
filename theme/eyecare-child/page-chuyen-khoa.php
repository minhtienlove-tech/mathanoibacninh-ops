<?php
/**
 * Trang danh mục Chuyên khoa.
 *
 * Danh sách chuyên khoa lấy từ các page con đang xuất bản. Bài hướng dẫn dài
 * lấy từ nội dung page và hiển thị sau danh mục để người quản trị có thể cập
 * nhật bằng WordPress mà không phải sửa template.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'eyecare_chuyen_khoa_icon' ) ) {
	/**
	 * Trả về icon SVG tĩnh theo slug chuyên khoa.
	 *
	 * @param string $slug Slug trang chuyên khoa.
	 * @return string
	 */
	function eyecare_chuyen_khoa_icon( $slug ) {
		$icons = array(
			'dich-kinh-vong-mac' => '<svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="14"/><circle cx="24" cy="24" r="7"/><path d="M24 10v7M24 31v7M10 24h7M31 24h7M14.2 14.2l5 5M28.8 28.8l5 5M33.8 14.2l-5 5M19.2 28.8l-5 5"/></svg>',
			'giac-mac-ket-mac'   => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M5 24s7-11 19-11 19 11 19 11-7 11-19 11S5 24 5 24Z"/><circle cx="24" cy="24" r="6"/><path d="M24 18c4 2.5 4 9.5 0 12"/></svg>',
			'glocom'              => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M7 25s6.5-10 17-10 17 10 17 10-6.5 10-17 10S7 25 7 25Z"/><circle cx="24" cy="25" r="5"/><path d="M32 7v8M28 11h8M12 11l4 4M36 37l-4-4"/></svg>',
			'mat-nguoi-cao-tuoi' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M10 22h10l2 3h4l2-3h10"/><circle cx="15" cy="23" r="7"/><circle cx="33" cy="23" r="7"/><path d="M12 35c3 2 7 3 12 3s9-1 12-3M17 11c2-1 4-2 7-2s5 1 7 2"/></svg>',
			'mat-tre-em'         => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M6 25s7-10 18-10 18 10 18 10-7 10-18 10S6 25 6 25Z"/><circle cx="24" cy="25" r="5"/><path d="m36 8 1.4 3.2L41 12.5l-3.6 1.3L36 17l-1.4-3.2-3.6-1.3 3.6-1.3L36 8Z"/></svg>',
		);

		return isset( $icons[ $slug ] )
			? $icons[ $slug ]
			: '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M5 24s7-11 19-11 19 11 19 11-7 11-19 11S5 24 5 24Z"/><circle cx="24" cy="24" r="6"/></svg>';
	}
}

get_header();

while ( have_posts() ) :
	the_post();

	$tt           = eyecare_du_lieu_thuc_the();
	$chuyen_khoa  = eyecare_trang_con_hub( 'chuyen-khoa' );
	$noi_dung     = trim( get_the_content() );
	$co_noi_dung  = '' !== trim( wp_strip_all_tags( strip_shortcodes( $noi_dung ) ) );
	$anh_tac_gia  = function_exists( 'eyecare_bac_si_anh_tac_gia' ) ? eyecare_bac_si_anh_tac_gia() : '';
	$ten_tac_gia  = function_exists( 'eyecare_bac_si_ten_day_du' ) ? eyecare_bac_si_ten_day_du() : 'Ths.BS Lê Như Tùng';
	$link_tac_gia = function_exists( 'eyecare_bac_si_duong_dan' ) ? eyecare_bac_si_duong_dan() : home_url( '/doi-ngu-bac-si/' );
	$so_tu        = absint( get_post_meta( get_the_ID(), '_bvmat_so_tu', true ) );
	$anh_nen      = function_exists( 'eyecare_anh_giao_dien_lay' ) ? eyecare_anh_giao_dien_lay( 'chuyen_khoa_nen', 'full' ) : null;
	$mo_ta        = array(
		'dich-kinh-vong-mac' => 'Đánh giá những thay đổi liên quan đến dịch kính, võng mạc và vùng nhìn.',
		'giac-mac-ket-mac'   => 'Thăm khám giác mạc, kết mạc và các biểu hiện thường gặp ở bề mặt mắt.',
		'glocom'              => 'Theo dõi nhãn áp, thần kinh thị giác và những yếu tố liên quan đến glôcôm.',
		'mat-nguoi-cao-tuoi' => 'Chăm sóc thị lực theo các thay đổi thường gặp ở người lớn tuổi.',
		'mat-tre-em'         => 'Theo dõi thị lực, tật khúc xạ và quá trình phát triển thị giác của trẻ.',
	);
	$slug_hien_tai = get_post_field( 'post_name', get_the_ID() );
	$chuyen_khoa_khac = array();
	foreach ( $chuyen_khoa as $muc_khoa ) {
		if ( ! empty( $muc_khoa['trang'] ) && $slug_hien_tai !== get_post_field( 'post_name', $muc_khoa['trang']->ID ) ) {
			$chuyen_khoa_khac[] = $muc_khoa['trang'];
		}
	}
	$bai_moi_nhat = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 5,
			'orderby'              => 'date',
			'order'                => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	?>

	<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-chuyen-khoa' ); ?>>
		<section class="eyecare-chuyen-khoa__hero" aria-labelledby="chuyen-khoa-tieu-de">
			<div class="eyecare-chuyen-khoa__khung">
				<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?>
					<div class="eyecare-chuyen-khoa__duong-dan">
						<?php eyecare_duong_dan_in(); ?>
					</div>
				<?php endif; ?>

				<div class="eyecare-chuyen-khoa__hero-grid">
					<div class="eyecare-chuyen-khoa__hero-copy">
						<p class="eyecare-chuyen-khoa__nhan"><span aria-hidden="true"></span> Chuyên khoa nhãn khoa</p>
						<h1 id="chuyen-khoa-tieu-de"><?php the_title(); ?></h1>
						<p>Chọn nhóm thông tin theo dấu hiệu và độ tuổi. Mỗi chuyên khoa được trình bày ngắn gọn để người bệnh dễ chuẩn bị trước khi đến khám.</p>
						<div class="eyecare-chuyen-khoa__hero-actions">
							<a class="eyecare-chuyen-khoa__kham-pha" href="#danh-sach-chuyen-khoa">Xem danh mục <span aria-hidden="true">↓</span></a>
							<a class="eyecare-chuyen-khoa__hero-link" href="#cam-nang-chuyen-khoa">Đọc cẩm nang chọn chuyên khoa</a>
						</div>
					</div>

					<div class="eyecare-chuyen-khoa__hero-visual" aria-hidden="true">
						<div class="eyecare-chuyen-khoa__quy-dao eyecare-chuyen-khoa__quy-dao--mot"></div>
						<div class="eyecare-chuyen-khoa__quy-dao eyecare-chuyen-khoa__quy-dao--hai"></div>
						<div class="eyecare-chuyen-khoa__mat">
							<svg viewBox="0 0 260 180"><path d="M18 90s42-62 112-62 112 62 112 62-42 62-112 62S18 90 18 90Z"/><circle cx="130" cy="90" r="42"/><circle cx="130" cy="90" r="18"/><path d="M130 48c22 13 22 71 0 84"/></svg>
						</div>
						<div class="eyecare-chuyen-khoa__so-luong">
							<strong><?php echo esc_html( str_pad( (string) count( $chuyen_khoa ), 2, '0', STR_PAD_LEFT ) ); ?></strong>
							<span>nhóm chuyên khoa</span>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section id="danh-sach-chuyen-khoa" class="eyecare-chuyen-khoa__danh-sach<?php echo $anh_nen ? ' eyecare-chuyen-khoa__danh-sach--co-anh' : ''; ?>" aria-labelledby="danh-sach-tieu-de">
			<?php if ( $anh_nen ) : ?><img class="eyecare-chuyen-khoa__danh-sach-bg" src="<?php echo esc_url( $anh_nen['url'] ); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async"><?php endif; ?>
			<div class="eyecare-chuyen-khoa__khung">
				<header class="eyecare-chuyen-khoa__muc-dau">
					<div>
						<p>Danh mục thăm khám</p>
						<h2 id="danh-sach-tieu-de">Tìm nhóm chuyên khoa phù hợp</h2>
					</div>
					<span>Danh sách tự cập nhật theo các trang chuyên khoa đang được xuất bản trong WordPress.</span>
				</header>

				<?php if ( $chuyen_khoa ) : ?>
					<ul class="eyecare-chuyen-khoa__luoi">
						<?php foreach ( $chuyen_khoa as $i => $muc ) :
							$trang = $muc['trang'];
							$slug  = get_post_field( 'post_name', $trang->ID );
							$ten   = get_the_title( $trang->ID );
							$dan   = isset( $mo_ta[ $slug ] ) ? $mo_ta[ $slug ] : 'Tìm hiểu phạm vi thăm khám và thông tin cần chuẩn bị cho nhóm chuyên khoa này.';
							?>
							<li>
								<a class="eyecare-chuyen-khoa__the" href="<?php echo esc_url( get_permalink( $trang->ID ) ); ?>">
									<span class="eyecare-chuyen-khoa__so"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
									<span class="eyecare-chuyen-khoa__icon"><?php echo eyecare_chuyen_khoa_icon( $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="eyecare-chuyen-khoa__the-copy">
										<strong><?php echo esc_html( $ten ); ?></strong>
										<span><?php echo esc_html( $dan ); ?></span>
									</span>
									<span class="eyecare-chuyen-khoa__the-link">Xem chuyên khoa <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</section>

		<?php if ( $co_noi_dung ) : ?>
			<section id="cam-nang-chuyen-khoa" class="eyecare-chuyen-khoa__noi-dung" aria-label="Cẩm nang chuyên khoa mắt">
				<div class="eyecare-chuyen-khoa__khung eyecare-chuyen-khoa__noi-dung-grid eyecare-chuyen-khoa__noi-dung-grid--ba-cot">
					<aside class="eyecare-chuyen-khoa__sidebar eyecare-chuyen-khoa__sidebar--lien-quan" aria-labelledby="chuyen-khoa-lien-quan">
						<div class="eyecare-chuyen-khoa__sidebar-card">
							<p class="eyecare-chuyen-khoa__sidebar-kicker">Khám theo nhóm</p>
							<h2 id="chuyen-khoa-lien-quan">Chuyên khoa khác</h2>
							<ul class="eyecare-chuyen-khoa__sidebar-list">
								<?php foreach ( $chuyen_khoa_khac as $trang_khac ) : ?>
									<li><a href="<?php echo esc_url( get_permalink( $trang_khac->ID ) ); ?>"><span><?php echo esc_html( get_the_title( $trang_khac->ID ) ); ?></span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6"/></svg></a></li>
								<?php endforeach; ?>
							</ul>
							<div class="eyecare-chuyen-khoa__sidebar-links">
								<a href="<?php echo esc_url( home_url( '/hoi-dap/truoc-khi-di-kham/' ) ); ?>">Chuẩn bị trước khi khám <span>→</span></a>
								<a href="<?php echo esc_url( home_url( '/kien-thuc/' ) ); ?>">Thư viện kiến thức <span>→</span></a>
								<a href="<?php echo esc_url( home_url( '/doi-ngu-bac-si/' ) ); ?>">Đội ngũ bác sĩ <span>→</span></a>
							</div>
						</div>
					</aside>

					<div class="eyecare-chuyen-khoa__bai eyecare-trang__than">
						<?php the_content(); ?>
						<div class="eyecare-chuyen-khoa__eeat-inline">
							<div class="eyecare-chuyen-khoa__eeat-card">
								<p class="eyecare-chuyen-khoa__eeat-label">Nội dung y khoa</p>
								<?php if ( $anh_tac_gia ) : ?>
									<img src="<?php echo esc_url( $anh_tac_gia ); ?>" alt="<?php echo esc_attr( $ten_tac_gia ); ?>" width="120" height="120" loading="lazy">
								<?php endif; ?>
								<p class="eyecare-chuyen-khoa__eeat-kicker">Người đứng tên nội dung</p>
								<h2><a href="<?php echo esc_url( $link_tac_gia ); ?>"><?php echo esc_html( $ten_tac_gia ); ?></a></h2>
								<p>Bác sĩ chuyên khoa Mắt<br><?php echo esc_html( $tt['ten'] ); ?></p>
								<?php if ( function_exists( 'eyecare_bac_si_chi_so_in' ) ) { eyecare_bac_si_chi_so_in(); } ?>
								<dl>
									<div><dt>Cập nhật</dt><dd><?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></dd></div>
									<?php if ( $so_tu ) : ?><div><dt>Độ dài</dt><dd><?php echo esc_html( number_format_i18n( $so_tu ) ); ?> từ</dd></div><?php endif; ?>
								</dl>
								<p class="eyecare-chuyen-khoa__eeat-note">Nội dung tham khảo, không thay thế khám và chỉ định trực tiếp.</p>
							</div>
						</div>
					</div>

					<aside class="eyecare-chuyen-khoa__sidebar eyecare-chuyen-khoa__sidebar--moi" aria-labelledby="bai-moi-nhat">
						<div class="eyecare-chuyen-khoa__sidebar-card">
							<p class="eyecare-chuyen-khoa__sidebar-kicker">Cập nhật mới</p>
							<h2 id="bai-moi-nhat">Bài viết mới nhất</h2>
							<?php if ( $bai_moi_nhat ) : ?>
								<ol class="eyecare-chuyen-khoa__bai-moi-list">
									<?php foreach ( $bai_moi_nhat as $i => $bai ) : ?>
										<li><a href="<?php echo esc_url( get_permalink( $bai->ID ) ); ?>"><span class="eyecare-chuyen-khoa__bai-moi-so"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><span><strong><?php echo esc_html( get_the_title( $bai->ID ) ); ?></strong><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $bai->ID ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y', $bai->ID ) ); ?></time></span></a></li>
									<?php endforeach; ?>
								</ol>
							<?php else : ?>
								<p class="eyecare-chuyen-khoa__sidebar-empty">Các bài viết mới sẽ được cập nhật tại đây.</p>
							<?php endif; ?>
							<a class="eyecare-chuyen-khoa__sidebar-cta" href="<?php echo esc_url( home_url( '/dich-vu/' ) ); ?>">Xem dịch vụ nhãn khoa <span>→</span></a>
							<a class="eyecare-chuyen-khoa__sidebar-cta eyecare-chuyen-khoa__sidebar-cta--vang" href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">Liên hệ bệnh viện <span>→</span></a>
						</div>
					</aside>
				</div>
			</section>
		<?php endif; ?>

		<section class="eyecare-chuyen-khoa__cta" aria-labelledby="chuyen-khoa-cta-tieu-de">
			<div class="eyecare-chuyen-khoa__khung eyecare-chuyen-khoa__cta-trong">
				<div>
					<p>Cần hướng dẫn trước khi đến?</p>
					<h2 id="chuyen-khoa-cta-tieu-de">Chưa xác định chuyên khoa phù hợp</h2>
					<span>Liên hệ tổng đài để được hướng dẫn thông tin cần chuẩn bị và nơi tiếp nhận phù hợp với dấu hiệu đang gặp.</span>
				</div>
				<div class="eyecare-chuyen-khoa__cta-nut">
					<a class="eyecare-nut eyecare-nut--chinh" href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">Gọi <?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a>
					<a class="eyecare-nut eyecare-nut--phu" href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">Xem thông tin liên hệ</a>
				</div>
			</div>
		</section>
	</article>

	<?php
endwhile;

get_footer();
