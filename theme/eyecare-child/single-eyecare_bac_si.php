<?php
/**
 * Trang cá nhân một bác sĩ — /bac-si/<slug>/.
 *
 * Ảnh, học vị, chức danh, giới thiệu, liên kết và bài viết đều quản lý trong
 * Admin → Đội ngũ bác sĩ. Bài viết hiện ở đây khi bài chọn bác sĩ ở hộp
 * “Bác sĩ được giới thiệu trong bài”, hoặc chọn bác sĩ là người viết/duyệt.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$bs_id       = get_the_ID();
	$bs          = function_exists( 'eyecare_bac_si_du_lieu_theo_id' ) ? eyecare_bac_si_du_lieu_theo_id( $bs_id ) : array();
	$bs_ten      = $bs ? trim( $bs['hoc_vi'] . ' ' . $bs['ho_ten'] ) : get_the_title();
	$bs_doi_ngu  = eyecare_du_lieu_doi_ngu();
	$bs_the      = array();
	foreach ( $bs_doi_ngu as $bs_muc ) {
		if ( (int) $bs_muc['post_id'] === (int) $bs_id ) {
			$bs_the = $bs_muc;
			break;
		}
	}
	$bs_facebook = $bs ? $bs['facebook'] : '';
	$bs_phong    = eyecare_bac_si_phong_kham_url( $bs_id );
	$bs_khac     = eyecare_bac_si_lien_ket_khac( $bs_id );
	$bs_bai      = eyecare_bac_si_bai_viet_ids( $bs_id, 12 );
	$bs_tom_tat  = trim( (string) get_post_field( 'post_excerpt', $bs_id, 'raw' ) );
	$bs_co_bio   = '' !== trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) );
	$bs_dat_lich = get_page_by_path( 'dat-lich-kham' );
	?>
	<div class="eyecare-news eyecare-bac-si-page">
		<article id="bac-si-<?php the_ID(); ?>" <?php post_class( 'eyecare-bac-si-page__article' ); ?>>
			<header class="eyecare-news__hero eyecare-bac-si-page__hero">
				<div class="eyecare-news__container">
					<nav class="eyecare-news__breadcrumb" aria-label="Đường dẫn">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
						<span aria-hidden="true">/</span>
						<a href="<?php echo esc_url( home_url( '/doi-ngu-bac-si/' ) ); ?>">Đội ngũ bác sĩ</a>
						<span aria-hidden="true">/</span>
						<span aria-current="page"><?php echo esc_html( $bs_ten ); ?></span>
					</nav>
					<div class="eyecare-bac-si-page__intro">
						<figure class="eyecare-bac-si-page__photo">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'large', array( 'alt' => $bs_ten, 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => '(min-width: 900px) 320px, 70vw' ) ); ?>
							<?php elseif ( ! empty( $bs_the['anh_url'] ) ) : ?>
								<img src="<?php echo esc_url( $bs_the['anh_url'] ); ?>" alt="<?php echo esc_attr( $bs_ten ); ?>" decoding="async">
							<?php else : ?>
								<span class="eyecare-bac-si-page__initial" aria-hidden="true"><?php echo esc_html( mb_substr( (string) ( $bs ? $bs['ho_ten'] : get_the_title() ), 0, 1, 'UTF-8' ) ); ?></span>
							<?php endif; ?>
						</figure>
						<div class="eyecare-bac-si-page__heading">
							<p class="eyecare-news__eyebrow">Hồ sơ bác sĩ</p>
							<h1><?php echo esc_html( $bs_ten ); ?></h1>
							<?php if ( ! empty( $bs['chuc_danh'] ) ) : ?>
								<p class="eyecare-bac-si-page__role"><?php echo esc_html( $bs['chuc_danh'] ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $bs['chuyen_mon'] ) ) : ?>
								<p class="eyecare-news__lead"><?php echo esc_html( $bs['chuyen_mon'] ); ?></p>
							<?php endif; ?>
							<?php if ( $bs_tom_tat ) : ?>
								<p class="eyecare-news__lead"><?php echo esc_html( $bs_tom_tat ); ?></p>
							<?php endif; ?>
							<div class="eyecare-bac-si-page__actions">
								<?php if ( $bs_dat_lich instanceof WP_Post ) : ?>
									<a class="eyecare-bac-si-page__btn eyecare-bac-si-page__btn--primary" href="<?php echo esc_url( get_permalink( $bs_dat_lich ) ); ?>">Đặt lịch khám</a>
								<?php endif; ?>
								<?php if ( $bs_facebook ) : ?>
									<a class="eyecare-bac-si-page__btn" href="<?php echo esc_url( $bs_facebook ); ?>" target="_blank" rel="noopener noreferrer">Facebook cá nhân <span aria-hidden="true">↗</span><span class="screen-reader-text">(mở tab mới)</span></a>
								<?php endif; ?>
								<?php if ( $bs_phong ) : ?>
									<a class="eyecare-bac-si-page__btn" href="<?php echo esc_url( $bs_phong ); ?>" target="_blank" rel="noopener noreferrer">Facebook phòng khám <span aria-hidden="true">↗</span><span class="screen-reader-text">(mở tab mới)</span></a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</header>

			<div class="eyecare-news__container eyecare-bac-si-page__body">
				<div class="eyecare-bac-si-page__main">
					<?php if ( ! empty( $bs_the['highlights'] ) ) : ?>
						<section class="eyecare-bac-si-page__card" aria-labelledby="eyecare-bs-chuyen-mon">
							<h2 id="eyecare-bs-chuyen-mon">Chuyên môn và kinh nghiệm</h2>
							<ul class="eyecare-bac-si-page__checks">
								<?php foreach ( $bs_the['highlights'] as $bs_dong ) : ?>
									<li><?php echo esc_html( $bs_dong ); ?></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<section class="eyecare-news__article-content eyecare-bac-si-page__bio" aria-label="Giới thiệu">
						<?php if ( $bs_co_bio ) : ?>
							<?php the_content(); ?>
						<?php else : ?>
							<p><?php echo esc_html( $bs_ten ); ?> là thành viên đội ngũ chuyên môn của Bệnh viện Mắt Hà Nội – Bắc Ninh. Thông tin đào tạo và quá trình công tác chi tiết đang được bệnh viện cập nhật.</p>
						<?php endif; ?>
					</section>
				</div>

				<?php if ( $bs_khac ) : ?>
					<aside class="eyecare-bac-si-page__card eyecare-bac-si-page__sources" aria-labelledby="eyecare-bs-nguon">
						<h2 id="eyecare-bs-nguon">Liên kết và nguồn khác</h2>
						<ul>
							<?php foreach ( $bs_khac as $bs_lk ) : ?>
								<li><a href="<?php echo esc_url( $bs_lk['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $bs_lk['ten'] ); ?> <span aria-hidden="true">↗</span><span class="screen-reader-text">(mở tab mới)</span></a></li>
							<?php endforeach; ?>
						</ul>
					</aside>
				<?php endif; ?>
			</div>
		</article>

		<?php if ( $bs_bai ) : ?>
			<section class="eyecare-news__latest" aria-labelledby="eyecare-bs-bai-viet">
				<div class="eyecare-news__container">
					<div class="eyecare-news__section-heading">
						<p>Bài viết</p>
						<h2 id="eyecare-bs-bai-viet">Bài viết về <?php echo esc_html( $bs_ten ); ?></h2>
					</div>
					<div class="eyecare-news__post-grid">
						<?php foreach ( $bs_bai as $bs_bai_id ) : ?>
							<article class="eyecare-news__post-card">
								<a class="eyecare-news__post-image" href="<?php echo esc_url( get_permalink( $bs_bai_id ) ); ?>" tabindex="-1" aria-hidden="true">
									<?php if ( has_post_thumbnail( $bs_bai_id ) ) : ?>
										<?php echo wp_kses_post( get_the_post_thumbnail( $bs_bai_id, 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ) ); ?>
									<?php else : ?>
										<span class="eyecare-news__image-fallback" aria-hidden="true">Bệnh viện Mắt<br>Hà Nội – Bắc Ninh</span>
									<?php endif; ?>
								</a>
								<div class="eyecare-news__post-copy">
									<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $bs_bai_id ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y', $bs_bai_id ) ); ?></time>
									<h3><a href="<?php echo esc_url( get_permalink( $bs_bai_id ) ); ?>"><?php echo esc_html( get_the_title( $bs_bai_id ) ); ?></a></h3>
									<p><?php echo esc_html( function_exists( 'eyecare_tom_tat_bai_viet_sach' ) ? eyecare_tom_tat_bai_viet_sach( $bs_bai_id, 23 ) : get_the_excerpt( $bs_bai_id ) ); ?></p>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		$bs_khac_bs = array_filter( $bs_doi_ngu, static function ( $m ) use ( $bs_id ) {
			return ! empty( $m['post_id'] ) && (int) $m['post_id'] !== (int) $bs_id;
		} );
		?>
		<?php if ( $bs_khac_bs ) : ?>
			<nav class="eyecare-news__container eyecare-bac-si-page__team" aria-label="Bác sĩ khác">
				<h2>Đội ngũ bác sĩ</h2>
				<ul>
					<?php foreach ( $bs_khac_bs as $bs_m ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $bs_m['post_id'] ) ); ?>"><?php echo esc_html( eyecare_doi_ngu_ten_day_du( $bs_m ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<p class="eyecare-news__back"><a href="<?php echo esc_url( home_url( '/doi-ngu-bac-si/' ) ); ?>">← Xem toàn bộ đội ngũ bác sĩ</a></p>
			</nav>
		<?php endif; ?>
	</div>
	<?php
endwhile;

get_footer();
