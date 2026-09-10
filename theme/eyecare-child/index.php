<?php
/**
 * KHUÔN DỰ PHÒNG — index.php
 *
 * WordPress BẮT BUỘC mỗi theme có tệp này: khi không tìm được khuôn nào khớp
 * hơn (trang lưu trữ, kết quả tìm kiếm, danh mục, thẻ, trang 404) thì rơi về
 * đây. Trước kia theme con không có nên rơi về index.php của Flatsome; nay
 * theme tự dựng khung nên phải có bản riêng, nếu không các trang đó sẽ hiện
 * khung Flatsome lẫn với header tự viết.
 *
 * Cố ý viết mỏng: mọi loại trang có nội dung thật đều đã có khuôn riêng
 * (front-page.php, page.php, single.php, page-lien-he.php,
 * page-doi-ngu-bac-si.php). Tệp này chỉ cần không bao giờ để người đọc gặp
 * trang trắng.
 *
 * @package eyecare-child
 */

get_header();
?>

<div class="eyecare-trang">

	<header class="eyecare-trang__dau">
		<h1 class="eyecare-trang__tieu-de">
			<?php
			if ( is_search() ) {
				printf(
					/* translators: %s: từ khoá người đọc vừa tìm */
					esc_html__( 'Kết quả tìm cho “%s”', 'eyecare-child' ),
					esc_html( get_search_query() )
				);
			} elseif ( is_archive() ) {
				echo esc_html( get_the_archive_title() );
			} elseif ( is_404() ) {
				esc_html_e( 'Không tìm thấy trang này', 'eyecare-child' );
			} else {
				esc_html_e( 'Bài viết', 'eyecare-child' );
			}
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>

		<ul class="eyecare-chu__bai-ds">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<li class="eyecare-chu__bai-muc">
					<a class="eyecare-chu__bai-anh" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( 'medium_large', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
						<?php else : ?>
							<?php echo eyecare_anh_bai_du_phong( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG nội bộ. ?>
						<?php endif; ?>
					</a>
					<h2 class="eyecare-chu__bai-td">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h2>

					<p class="eyecare-chu__bai-ngay">
						<time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
							<?php echo esc_html( get_the_modified_date( 'j/n/Y' ) ); ?>
						</time>
					</p>
				</li>
				<?php
			endwhile;
			?>
		</ul>

		<?php
		/* Phân trang. Danh sách bài dài hơn một trang thì người đọc cần lối đi
		   tiếp; không có thì các bài từ trang 2 trở đi thành không ai tới được. */
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => esc_html__( 'Trang trước', 'eyecare-child' ),
				'next_text' => esc_html__( 'Trang sau', 'eyecare-child' ),
			)
		);
		?>

	<?php else : ?>

		<?php
		/* Không có bài nào khớp. Đừng để người đọc đứng ở ngõ cụt — đưa sẵn số
		   tổng đài, vì phần lớn người vào site này đang cần hỏi một việc cụ thể. */
		?>
		<div class="eyecare-trang__than">
			<p>
				Không có nội dung nào khớp. Bạn thử tìm lại bằng từ khác, hoặc gọi
				<a href="tel:<?php echo esc_attr( eyecare_hotline_goi() ); ?>"><?php echo esc_html( eyecare_hotline_hien() ); ?></a>
				để được hỗ trợ trực tiếp.
			</p>
		</div>

	<?php endif; ?>

</div>

<?php
get_footer();
