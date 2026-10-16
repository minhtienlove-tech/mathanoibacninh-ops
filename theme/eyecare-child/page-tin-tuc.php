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
	$topic_design = array(
		'hoat-dong-cong-dong' => array( 'Các chương trình khám mắt và hoạt động chăm sóc sức khỏe cùng cộng đồng.', 'M12 21s-8-5-8-11a4 4 0 0 1 8-2 4 4 0 0 1 8 2c0 6-8 11-8 11Z' ),
		'goc-bac-si' => array( 'Gặp gỡ đội ngũ bác sĩ và tìm hiểu những chia sẻ về sức khỏe đôi mắt.', 'M6 3v6a6 6 0 0 0 12 0V3M4 3h4m8 0h4M12 15v2a4 4 0 0 0 8 0v-3M20 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z' ),
		'cau-chuyen-nguoi-benh' => array( 'Những câu chuyện và trải nghiệm được người bệnh đồng ý chia sẻ.', 'M4 4h16v12H9l-5 4V4Zm4 5h8m-8 4h5' ),
		'doi-song-benh-vien' => array( 'Con người, công việc và những khoảnh khắc trong đời sống bệnh viện.', 'M4 21V5h16v16M9 21v-5h6v5M9 9h6m-3-3v6' ),
		'thong-bao-benh-vien' => array( 'Cập nhật lịch hoạt động và các thông tin chính thức từ bệnh viện.', 'M5 17h14l-2-3V9a5 5 0 0 0-10 0v5l-2 3Zm5 3h4M12 2v2' ),
		'tuyen-dung' => array( 'Tìm hiểu vị trí tuyển dụng và cơ hội đồng hành cùng bệnh viện.', 'M3 7h18v13H3V7Zm5 0V4h8v3M3 12h18m-11 0v3h4v-3' ),
		'uu-dai-va-ho-tro-nguoi-benh' => array( 'Thông tin chương trình hỗ trợ, điều kiện và thời gian áp dụng.', 'M3 8h18v4H3V8Zm2 4v9h14v-9M12 8v13M12 8C4 8 5 1 9 3l3 5Zm0 0c8 0 7-7 3-5l-3 5Z' ),
	);
	$topic_order = array_keys( $topic_design );
	usort( $news_terms, static function ( $a, $b ) use ( $topic_order ) {
		$left = array_search( $a->slug, $topic_order, true );
		$right = array_search( $b->slug, $topic_order, true );
		return ( false === $left ? 99 : $left ) <=> ( false === $right ? 99 : $right );
	} );
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
						<h2 id="eyecare-news-topics-heading">Bạn muốn khám phá điều gì?</h2>
						<span>Câu chuyện, con người và những hoạt động mới từ bệnh viện.</span>
					</div>
					<div class="eyecare-news__topic-grid">
						<?php foreach ( $news_terms as $news_term ) :
							$term_url = get_term_link( $news_term );
							if ( is_wp_error( $term_url ) ) {
								continue;
							}
							$term_description = trim( wp_strip_all_tags( term_description( $news_term->term_id, 'category' ) ) );
							$topic = isset( $topic_design[ $news_term->slug ] ) ? $topic_design[ $news_term->slug ] : array( $term_description, 'M4 4h16v16H4V4Zm4 5h8m-8 4h8m-8 4h5' );
							?>
							<a class="eyecare-news__topic<?php echo 'hoat-dong-cong-dong' === $news_term->slug ? ' eyecare-news__topic--featured' : ''; ?>" href="<?php echo esc_url( $term_url ); ?>">
								<span class="eyecare-news__topic-mark" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="<?php echo esc_attr( $topic[1] ); ?>" /></svg></span>
								<strong><?php echo esc_html( $news_term->name ); ?></strong>
								<span><?php echo esc_html( $topic[0] ); ?></span>
								<small>Khám phá <span aria-hidden="true">→</span></small>
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
								<a class="eyecare-news__post-image<?php echo eyecare_anh_dai_dien_dang_doc( get_the_ID() ) ? ' eyecare-news__post-image--doc' : ''; ?>" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
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
