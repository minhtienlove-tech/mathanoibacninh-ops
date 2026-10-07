<?php
/**
 * Trang Tin bệnh viện: điều hướng theo chuyên mục và các bài đã xuất bản.
 *
 * Danh mục và bài viết được lấy từ WordPress. Trang không tự tạo thông tin về
 * chương trình, người bệnh, ưu đãi hoặc nhân sự khi chưa có bài được biên tập.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$news_root     = get_category_by_slug( 'tin-tuc' );
	$news_terms    = array();
	$news_posts    = null;

	if ( $news_root instanceof WP_Term ) {
		$news_terms = get_categories(
			array(
				'parent'     => (int) $news_root->term_id,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$news_posts = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 7,
				'ignore_sticky_posts' => true,
				'tax_query'           => array(
					array(
						'taxonomy'         => 'category',
						'field'            => 'term_id',
						'terms'            => array( (int) $news_root->term_id ),
						'include_children' => true,
					),
				),
			)
		);
	}
	?>
	<main id="tin-benh-vien" class="eyecare-news eyecare-news--hub">
		<section class="eyecare-news__hero" aria-labelledby="eyecare-news-title">
			<div class="eyecare-news__container">
				<nav class="eyecare-news__breadcrumb" aria-label="Đường dẫn">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
					<span aria-hidden="true">/</span>
					<span aria-current="page"><?php the_title(); ?></span>
				</nav>
				<p class="eyecare-news__eyebrow">Hoạt động và thông tin từ bệnh viện</p>
				<h1 id="eyecare-news-title"><?php the_title(); ?></h1>
				<p class="eyecare-news__lead">Theo dõi câu chuyện người bệnh, đội ngũ, chương trình khám cộng đồng và các thông báo chính thức của Bệnh viện Mắt Hà Nội – Bắc Ninh.</p>
			</div>
		</section>

		<?php if ( '' !== trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) ) ) : ?>
			<section class="eyecare-news__intro" aria-label="Giới thiệu tin bệnh viện">
				<div class="eyecare-news__container eyecare-news__richtext"><?php the_content(); ?></div>
			</section>
		<?php endif; ?>

		<?php if ( $news_terms ) : ?>
			<section class="eyecare-news__topics" aria-labelledby="eyecare-news-topics-heading">
				<div class="eyecare-news__container">
					<div class="eyecare-news__section-heading">
						<p>Khám phá theo chủ đề</p>
						<h2 id="eyecare-news-topics-heading">Thông tin bạn muốn theo dõi</h2>
						<span>Chọn một chuyên mục để xem các bài đã được công bố.</span>
					</div>
					<div class="eyecare-news__topic-grid">
						<?php foreach ( $news_terms as $news_term ) :
							$term_url = get_term_link( $news_term );
							if ( is_wp_error( $term_url ) ) {
								continue;
							}
							$term_description = trim( wp_strip_all_tags( term_description( $news_term->term_id, 'category' ) ) );
							$term_mark        = preg_match( '/^./us', $news_term->name, $mark_match ) ? $mark_match[0] : '•';
							?>
							<a class="eyecare-news__topic" href="<?php echo esc_url( $term_url ); ?>">
								<span class="eyecare-news__topic-mark" aria-hidden="true"><?php echo esc_html( $term_mark ); ?></span>
								<strong><?php echo esc_html( $news_term->name ); ?></strong>
								<?php if ( $term_description ) : ?><span><?php echo esc_html( wp_trim_words( $term_description, 18, '…' ) ); ?></span><?php endif; ?>
								<small>Xem chuyên mục <span aria-hidden="true">↗</span></small>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="eyecare-news__latest" aria-labelledby="eyecare-news-latest-heading">
			<div class="eyecare-news__container">
				<div class="eyecare-news__section-heading">
					<p>Mới đăng</p>
					<h2 id="eyecare-news-latest-heading">Tin từ bệnh viện</h2>
				</div>
				<?php if ( $news_posts instanceof WP_Query && $news_posts->have_posts() ) : ?>
					<div class="eyecare-news__post-grid">
						<?php while ( $news_posts->have_posts() ) : $news_posts->the_post(); ?>
							<article <?php post_class( 'eyecare-news__post-card' ); ?>>
								<a class="eyecare-news__post-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
									<?php if ( has_post_thumbnail() ) : ?>
										<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?>
									<?php else : ?>
										<span class="eyecare-news__image-fallback" aria-hidden="true">Bệnh viện Mắt<br>Hà Nội – Bắc Ninh</span>
									<?php endif; ?>
								</a>
								<div class="eyecare-news__post-copy">
									<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></time>
									<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
									<p><?php echo esc_html( eyecare_tom_tat_bai_viet_sach( get_the_ID(), 25 ) ); ?></p>
									<a class="eyecare-news__read-more" href="<?php the_permalink(); ?>">Đọc bài viết <span aria-hidden="true">→</span></a>
								</div>
							</article>
						<?php endwhile; ?>
					</div>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<div class="eyecare-news__empty"><p>Các bài tin đang được biên tập. Bạn có thể chọn chuyên mục ở trên để tìm hiểu phạm vi nội dung.</p></div>
				<?php endif; ?>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();
