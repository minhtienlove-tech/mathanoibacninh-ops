<?php
/**
 * Bài Tin bệnh viện. Tách khỏi khuôn bài kiến thức và không tự gán tác giả y khoa.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$news_root = get_category_by_slug( 'tin-tuc' );
$news_page = get_page_by_path( 'tin-tuc' );
$news_home = $news_page instanceof WP_Post ? get_permalink( $news_page ) : home_url( '/tin-tuc/' );

get_header();

while ( have_posts() ) :
	the_post();

	$news_id        = get_the_ID();
	$news_category  = null;
	$news_terms     = get_the_category( $news_id );
	$news_related   = array();
	$news_byline    = function_exists( 'eyecare_bai_bac_si_byline' ) ? eyecare_bai_bac_si_byline( $news_id ) : array();
	$news_published = (int) get_the_time( 'U' );
	$news_modified  = (int) get_the_modified_time( 'U' );

	if ( $news_root instanceof WP_Term ) {
		foreach ( $news_terms as $news_term ) {
			if ( (int) $news_term->term_id !== (int) $news_root->term_id && term_is_ancestor_of( $news_root->term_id, $news_term->term_id, 'category' ) ) {
				$news_category = $news_term;
				break;
			}
		}
	}

	$news_category_url = $news_category ? get_term_link( $news_category ) : '';
	if ( is_wp_error( $news_category_url ) ) {
		$news_category_url = '';
	}

	if ( $news_root instanceof WP_Term ) {
		$news_related = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 3,
				'post__not_in'        => array( $news_id ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'tax_query'           => array(
					array(
						'taxonomy'         => 'category',
						'field'            => 'term_id',
						'terms'            => array( $news_category ? (int) $news_category->term_id : (int) $news_root->term_id ),
						'include_children' => true,
					),
				),
			)
		);
	}
	?>
	<main class="eyecare-news eyecare-news--single">
		<article id="bai-<?php the_ID(); ?>" <?php post_class( 'eyecare-news__article' ); ?>>
			<header class="eyecare-news__hero eyecare-news__hero--article">
				<div class="eyecare-news__container eyecare-news__container--reading">
					<nav class="eyecare-news__breadcrumb" aria-label="Đường dẫn">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a>
						<span aria-hidden="true">/</span>
						<a href="<?php echo esc_url( $news_home ); ?>">Tin bệnh viện</a>
						<?php if ( $news_category && $news_category_url ) : ?>
							<span aria-hidden="true">/</span>
							<a href="<?php echo esc_url( $news_category_url ); ?>"><?php echo esc_html( $news_category->name ); ?></a>
						<?php endif; ?>
						<span aria-hidden="true">/</span>
						<span aria-current="page"><?php the_title(); ?></span>
					</nav>
					<?php if ( $news_category && $news_category_url ) : ?>
						<a class="eyecare-news__eyebrow eyecare-news__eyebrow--link" href="<?php echo esc_url( $news_category_url ); ?>"><?php echo esc_html( $news_category->name ); ?></a>
					<?php else : ?>
						<p class="eyecare-news__eyebrow">Tin bệnh viện</p>
					<?php endif; ?>
					<h1><?php the_title(); ?></h1>
					<p class="eyecare-news__article-meta">
						<span>Đăng ngày <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></time></span>
						<?php if ( $news_byline ) : ?>
							<span>Người viết: <a href="<?php echo esc_url( $news_byline['facebook'] ? $news_byline['facebook'] : $news_byline['url'] ); ?>"<?php echo $news_byline['facebook'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $news_byline['name'] ); ?></a></span>
						<?php endif; ?>
						<?php if ( $news_modified > $news_published + DAY_IN_SECONDS ) : ?>
							<span>Cập nhật <time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></time></span>
						<?php endif; ?>
					</p>
				</div>
			</header>

			<div class="eyecare-news__container eyecare-news__container--reading">
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="eyecare-news__article-image">
						<?php the_post_thumbnail( 'large', array( 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?>
						<?php if ( get_the_post_thumbnail_caption() ) : ?><figcaption><?php echo esc_html( get_the_post_thumbnail_caption() ); ?></figcaption><?php endif; ?>
					</figure>
				<?php endif; ?>

				<div class="eyecare-news__article-content"><?php the_content(); ?></div>

				<p class="eyecare-news__back"><a href="<?php echo esc_url( $news_category_url ? $news_category_url : $news_home ); ?>">← Xem thêm tin trong <?php echo esc_html( $news_category ? $news_category->name : 'Tin bệnh viện' ); ?></a></p>
			</div>
		</article>

		<?php if ( $news_related ) : ?>
			<section class="eyecare-news__latest eyecare-news__related" aria-labelledby="eyecare-news-related-heading">
				<div class="eyecare-news__container">
					<div class="eyecare-news__section-heading">
						<p>Đọc tiếp</p>
						<h2 id="eyecare-news-related-heading">Tin liên quan</h2>
					</div>
					<div class="eyecare-news__post-grid">
						<?php foreach ( $news_related as $news_post ) : ?>
							<article class="eyecare-news__post-card">
								<a class="eyecare-news__post-image<?php echo eyecare_anh_dai_dien_dang_doc( $news_post->ID ) ? ' eyecare-news__post-image--doc' : ''; ?>" href="<?php echo esc_url( get_permalink( $news_post ) ); ?>" tabindex="-1" aria-hidden="true">
									<?php if ( has_post_thumbnail( $news_post ) ) : ?>
										<?php echo wp_kses_post( get_the_post_thumbnail( $news_post, 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ) ); ?>
									<?php else : ?>
										<span class="eyecare-news__image-fallback" aria-hidden="true">Bệnh viện Mắt<br>Hà Nội – Bắc Ninh</span>
									<?php endif; ?>
								</a>
								<div class="eyecare-news__post-copy">
									<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $news_post ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y', $news_post ) ); ?></time>
									<h3><a href="<?php echo esc_url( get_permalink( $news_post ) ); ?>"><?php echo esc_html( get_the_title( $news_post ) ); ?></a></h3>
									<p><?php echo esc_html( eyecare_tom_tat_bai_viet_sach( $news_post->ID, 23 ) ); ?></p>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer();
