<?php
/**
 * Lưu trữ chuyên mục Tin bệnh viện, không dùng nhãn nội dung y khoa.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$news_term = get_queried_object();
if ( ! $news_term instanceof WP_Term ) {
	get_template_part( 'index' );
	return;
}

$news_root      = get_category_by_slug( 'tin-tuc' );
$news_page      = get_page_by_path( 'tin-tuc' );
$news_page_url  = $news_page instanceof WP_Post ? get_permalink( $news_page ) : home_url( '/tin-tuc/' );
$news_siblings  = $news_root instanceof WP_Term ? get_categories( array( 'parent' => (int) $news_root->term_id, 'hide_empty' => false, 'orderby' => 'name' ) ) : array();
$news_term_desc = trim( wp_strip_all_tags( term_description( $news_term->term_id, 'category' ) ) );

get_header();
?>
<main class="eyecare-news eyecare-news--archive">
	<section class="eyecare-news__hero" aria-labelledby="eyecare-news-title">
		<div class="eyecare-news__container">
			<nav class="eyecare-news__breadcrumb" aria-label="Đường dẫn">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( $news_page_url ); ?>">Tin bệnh viện</a>
				<span aria-hidden="true">/</span>
				<span aria-current="page"><?php echo esc_html( single_term_title( '', false ) ); ?></span>
			</nav>
			<p class="eyecare-news__eyebrow">Chuyên mục tin bệnh viện</p>
			<h1 id="eyecare-news-title"><?php echo esc_html( single_term_title( '', false ) ); ?></h1>
			<?php if ( $news_term_desc ) : ?>
				<p class="eyecare-news__lead"><?php echo esc_html( $news_term_desc ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $news_siblings ) : ?>
		<nav class="eyecare-news__topic-nav eyecare-news__container" aria-label="Các chuyên mục tin bệnh viện">
			<a href="<?php echo esc_url( $news_page_url ); ?>">Tất cả tin</a>
			<?php foreach ( $news_siblings as $news_sibling ) :
				$sibling_url = get_term_link( $news_sibling );
				if ( is_wp_error( $sibling_url ) ) {
					continue;
				}
				?>
				<a href="<?php echo esc_url( $sibling_url ); ?>"<?php echo (int) $news_sibling->term_id === (int) $news_term->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $news_sibling->name ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<section class="eyecare-news__latest" aria-labelledby="eyecare-news-posts-heading">
		<div class="eyecare-news__container">
			<div class="eyecare-news__section-heading">
				<p>Bài đã xuất bản</p>
				<h2 id="eyecare-news-posts-heading">Trong chuyên mục này</h2>
			</div>
			<?php if ( have_posts() ) : ?>
				<div class="eyecare-news__post-grid">
					<?php while ( have_posts() ) : the_post(); ?>
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
				<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => 'Trang trước', 'next_text' => 'Trang sau' ) ); ?>
			<?php else : ?>
				<div class="eyecare-news__empty"><p>Bài viết trong chuyên mục đang được biên tập.</p></div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer();
