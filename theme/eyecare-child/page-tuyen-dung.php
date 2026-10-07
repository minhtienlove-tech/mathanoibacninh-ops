<?php
/** Trang tuyển dụng: chỉ hiển thị tin thực sự đã xuất bản. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
while ( have_posts() ) :
	the_post();
	$category = get_category_by_slug( 'tuyen-dung' );
	$paged    = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$args     = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 9,
		'paged'               => $paged,
		'ignore_sticky_posts' => true,
		'meta_query'          => array(
			array( 'key' => '_eyecare_category_intro', 'compare' => 'NOT EXISTS' ),
		),
	);
	if ( $category ) {
		$args['cat'] = (int) $category->term_id;
	} else {
		$args['post__in'] = array( 0 );
	}
	$recruitment_posts = new WP_Query( $args );
	?>
	<main id="tuyen-dung" class="eyecare-news eyecare-news--recruitment">
		<section class="eyecare-news__hero" aria-labelledby="eyecare-recruitment-title">
			<div class="eyecare-news__container">
				<nav class="eyecare-news__breadcrumb" aria-label="Đường dẫn">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a><span aria-hidden="true">/</span>
					<a href="<?php echo esc_url( home_url( '/tin-tuc/' ) ); ?>">Tin bệnh viện</a><span aria-hidden="true">/</span>
					<span aria-current="page"><?php the_title(); ?></span>
				</nav>
				<p class="eyecare-news__eyebrow">Cơ hội làm việc</p>
				<h1 id="eyecare-recruitment-title"><?php the_title(); ?></h1>
				<p class="eyecare-news__lead">Thông tin về môi trường làm việc và các cơ hội tuyển dụng khi được Bệnh viện Mắt Hà Nội – Bắc Ninh công bố. Mỗi thông báo vị trí cụ thể sẽ nêu điều kiện, hạn nộp và cách ứng tuyển.</p>
			</div>
		</section>
		<?php if ( '' !== trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) ) ) : ?>
			<section class="eyecare-news__intro" aria-label="Giới thiệu tuyển dụng"><div class="eyecare-news__container eyecare-news__richtext"><?php the_content(); ?></div></section>
		<?php endif; ?>
		<?php if ( $category && ! is_wp_error( get_term_link( $category ) ) ) : ?>
			<div class="eyecare-news__container"><p class="eyecare-news__back"><a href="<?php echo esc_url( get_term_link( $category ) ); ?>">Đọc bài giới thiệu chuyên mục tuyển dụng →</a></p></div>
		<?php endif; ?>
		<section class="eyecare-news__latest" aria-labelledby="eyecare-recruitment-latest">
			<div class="eyecare-news__container">
				<div class="eyecare-news__section-heading"><p>Cơ hội nghề nghiệp</p><h2 id="eyecare-recruitment-latest">Tin tuyển dụng</h2></div>
				<?php if ( $recruitment_posts->have_posts() ) : ?>
					<div class="eyecare-news__post-grid">
						<?php while ( $recruitment_posts->have_posts() ) : $recruitment_posts->the_post(); ?>
							<article <?php post_class( 'eyecare-news__post-card' ); ?>>
								<div class="eyecare-news__post-copy">
									<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></time>
									<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
									<p><?php echo esc_html( eyecare_tom_tat_bai_viet_sach( get_the_ID(), 25 ) ); ?></p>
									<a class="eyecare-news__read-more" href="<?php the_permalink(); ?>">Xem chi tiết <span aria-hidden="true">→</span></a>
								</div>
							</article>
						<?php endwhile; ?>
					</div>
					<?php wp_reset_postdata(); ?>
					<?php echo wp_kses_post( paginate_links( array( 'total' => $recruitment_posts->max_num_pages, 'current' => $paged ) ) ); ?>
				<?php else : ?>
					<div class="eyecare-news__empty"><p>Hiện chưa có vị trí tuyển dụng cụ thể được công bố. Vui lòng theo dõi trang này để xem thông báo mới.</p></div>
				<?php endif; ?>
			</div>
		</section>
	</main>
	<?php
endwhile;
get_footer();
