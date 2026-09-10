<?php
/**
 * Khuôn trang Đội ngũ bác sĩ — /doi-ngu-bac-si/.
 *
 * Khối đội ngũ dùng chung component poster đã được duyệt trên trang chủ.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$eyecare_co_doi_ngu  = function_exists( 'eyecare_doi_ngu_trang_chu_in' );
	$eyecare_noi_dung     = trim( get_the_content() );
	$eyecare_co_noi_dung  = '' !== trim( wp_strip_all_tags( strip_shortcodes( $eyecare_noi_dung ) ) );
	$eyecare_thuc_the     = function_exists( 'eyecare_du_lieu_thuc_the' ) ? eyecare_du_lieu_thuc_the() : array( 'ten' => get_bloginfo( 'name' ) );
	$eyecare_anh_tac_gia  = function_exists( 'eyecare_bac_si_anh_tac_gia' ) ? eyecare_bac_si_anh_tac_gia() : '';
	$eyecare_ten_tac_gia  = function_exists( 'eyecare_bac_si_ten_day_du' ) ? eyecare_bac_si_ten_day_du() : 'Ths.BS Lê Như Tùng';
	$eyecare_link_tac_gia = function_exists( 'eyecare_bac_si_duong_dan' ) ? eyecare_bac_si_duong_dan() : get_permalink();
	$eyecare_so_tu        = absint( get_post_meta( get_the_ID(), '_bvmat_so_tu', true ) );
	?>

<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-doi-ngu-page' ); ?>>
	<?php if ( $eyecare_co_doi_ngu ) : ?>
		<?php eyecare_doi_ngu_trang_chu_in( 1 ); ?>

	<?php else : ?>
		<section class="eyecare-doi-ngu-page__empty">
			<div class="eyecare-khung-trang">
				<h1><?php the_title(); ?></h1>
				<p>Danh sách bác sĩ đang được cập nhật. Vui lòng quay lại sau.</p>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $eyecare_co_noi_dung ) : ?>
		<section id="cam-nang-doi-ngu" class="eyecare-chuyen-khoa__noi-dung eyecare-doi-ngu-page__noi-dung" aria-label="Cẩm nang chọn bác sĩ mắt phù hợp">
			<div class="eyecare-chuyen-khoa__khung eyecare-chuyen-khoa__noi-dung-grid">
				<div class="eyecare-chuyen-khoa__bai eyecare-trang__than">
					<?php the_content(); ?>
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
						<dl>
							<div><dt>Cập nhật</dt><dd><?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></dd></div>
							<?php if ( $eyecare_so_tu ) : ?>
								<div><dt>Độ dài</dt><dd><?php echo esc_html( number_format_i18n( $eyecare_so_tu ) ); ?> từ</dd></div>
							<?php endif; ?>
						</dl>
						<p class="eyecare-chuyen-khoa__eeat-note">Nội dung giúp người đọc chuẩn bị trước khi khám, không thay thế chẩn đoán và chỉ định trực tiếp.</p>
					</div>
				</aside>
			</div>
		</section>
	<?php endif; ?>
</article>

	<?php
endwhile;

get_footer();
