<?php
/**
 * Giao diện dùng chung cho category và tag.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyecare_term = get_queried_object();
if ( ! $eyecare_term instanceof WP_Term ) {
	get_template_part( 'index' );
	return;
}

$eyecare_noi_dung    = get_term_meta( $eyecare_term->term_id, '_eyecare_noi_dung_dai', true );
$eyecare_mo_ta       = trim( wp_strip_all_tags( term_description( $eyecare_term->term_id, $eyecare_term->taxonomy ) ) );
$eyecare_so_tu       = absint( get_term_meta( $eyecare_term->term_id, '_eyecare_so_tu', true ) );
$eyecare_thuc_the    = function_exists( 'eyecare_du_lieu_thuc_the' ) ? eyecare_du_lieu_thuc_the() : array( 'ten' => get_bloginfo( 'name' ) );
$eyecare_anh_tac_gia = function_exists( 'eyecare_bac_si_anh_tac_gia' ) ? eyecare_bac_si_anh_tac_gia() : '';
$eyecare_ten_tac_gia = function_exists( 'eyecare_bac_si_ten_day_du' ) ? eyecare_bac_si_ten_day_du() : 'Ths.BS Lê Như Tùng';
$eyecare_link_tac_gia = function_exists( 'eyecare_bac_si_duong_dan' ) ? eyecare_bac_si_duong_dan() : home_url( '/doi-ngu-bac-si/' );

get_header();
?>

<main class="eyecare-archive-term">
	<section class="eyecare-archive-term__hero" aria-labelledby="eyecare-archive-term-title">
		<div class="eyecare-chuyen-khoa__khung">
			<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?>
				<div class="eyecare-archive-term__duong-dan"><?php eyecare_duong_dan_in(); ?></div>
			<?php endif; ?>

			<p class="eyecare-archive-term__nhan"><span aria-hidden="true"></span> Thư viện kiến thức nhãn khoa</p>
			<h1 id="eyecare-archive-term-title"><?php echo esc_html( single_term_title( '', false ) ); ?></h1>
			<?php if ( $eyecare_mo_ta ) : ?>
				<p class="eyecare-archive-term__mo-ta"><?php echo esc_html( $eyecare_mo_ta ); ?></p>
			<?php else : ?>
				<p class="eyecare-archive-term__mo-ta">Các bài viết được sắp theo chủ đề để người đọc tra cứu dấu hiệu, cách chuẩn bị đi khám và những điều cần trao đổi với bác sĩ.</p>
			<?php endif; ?>
			<p class="eyecare-archive-term__so-bai"><strong><?php echo esc_html( number_format_i18n( (int) $eyecare_term->count ) ); ?></strong> bài viết trong chuyên mục</p>
		</div>
	</section>

	<section class="eyecare-archive-term__danh-sach" aria-labelledby="eyecare-archive-term-posts">
		<div class="eyecare-chuyen-khoa__khung">
			<header class="eyecare-archive-term__muc-dau">
				<div>
					<p>Bài viết theo chủ đề</p>
					<h2 id="eyecare-archive-term-posts">Thông tin mới cập nhật</h2>
				</div>
				<span>Chọn bài phù hợp với dấu hiệu đang gặp. Nội dung không thay thế việc khám trực tiếp.</span>
			</header>

			<?php if ( have_posts() ) : ?>
				<div class="eyecare-archive-term__luoi">
					<?php while ( have_posts() ) : the_post(); ?>
						<article <?php post_class( 'eyecare-archive-term__the' ); ?>>
							<a class="eyecare-archive-term__anh" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?>
								<?php else : ?>
									<?php echo eyecare_anh_bai_du_phong( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG nội bộ, dữ liệu động đã escape trong hàm. ?>
								<?php endif; ?>
							</a>
							<div class="eyecare-archive-term__the-copy">
								<p class="eyecare-archive-term__ngay"><time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></time></p>
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<p><?php echo esc_html( eyecare_tom_tat_bai_viet_sach( get_the_ID(), 28 ) ); ?></p>
								<a class="eyecare-archive-term__doc" href="<?php the_permalink(); ?>">Đọc bài viết <span aria-hidden="true">→</span></a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>

				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 1,
						'prev_text' => 'Trang trước',
						'next_text' => 'Trang sau',
					)
				);
				?>
			<?php else : ?>
				<div class="eyecare-archive-term__rong">
					<h2>Nội dung đang được cập nhật</h2>
					<p>Chuyên mục này chưa có bài viết công khai. Bạn có thể xem thư viện kiến thức hoặc liên hệ tổng đài khi cần chuẩn bị cho buổi khám.</p>
					<a href="<?php echo esc_url( home_url( '/kien-thuc/' ) ); ?>">Xem kiến thức nhãn khoa</a>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( '' !== trim( wp_strip_all_tags( strip_shortcodes( $eyecare_noi_dung ) ) ) ) : ?>
		<section class="eyecare-chuyen-khoa__noi-dung eyecare-archive-term__noi-dung" aria-label="Cẩm nang theo chuyên mục">
			<div class="eyecare-chuyen-khoa__khung eyecare-chuyen-khoa__noi-dung-grid">
				<div class="eyecare-chuyen-khoa__bai eyecare-trang__than">
					<?php echo apply_filters( 'the_content', $eyecare_noi_dung ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<aside class="eyecare-chuyen-khoa__eeat" aria-label="Thông tin người đứng tên nội dung">
					<div class="eyecare-chuyen-khoa__eeat-card">
						<p class="eyecare-chuyen-khoa__eeat-label">Nội dung y khoa</p>
						<?php if ( $eyecare_anh_tac_gia ) : ?>
							<img src="<?php echo esc_url( $eyecare_anh_tac_gia ); ?>" alt="<?php echo esc_attr( $eyecare_ten_tac_gia ); ?>" width="120" height="120" loading="lazy">
						<?php endif; ?>
						<p class="eyecare-chuyen-khoa__eeat-kicker">Người đứng tên nội dung</p>
						<h2><a href="<?php echo esc_url( $eyecare_link_tac_gia ); ?>"><?php echo esc_html( $eyecare_ten_tac_gia ); ?></a></h2>
						<p>Bác sĩ chuyên khoa Mắt<br><?php echo esc_html( $eyecare_thuc_the['ten'] ); ?></p>
						<?php if ( function_exists( 'eyecare_bac_si_chi_so_in' ) ) { eyecare_bac_si_chi_so_in(); } ?>
						<?php if ( $eyecare_so_tu ) : ?><p><?php echo esc_html( number_format_i18n( $eyecare_so_tu ) ); ?> từ trong cẩm nang chuyên mục</p><?php endif; ?>
						<p class="eyecare-chuyen-khoa__eeat-note">Thông tin tham khảo, không dùng để tự chẩn đoán hoặc trì hoãn việc khám khi thị lực thay đổi nhanh.</p>
					</div>
				</aside>
			</div>
		</section>
	<?php endif; ?>
</main>

<?php
get_footer();
