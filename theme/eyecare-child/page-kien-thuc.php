<?php
/**
 * Trang tổng hợp kiến thức nhãn khoa.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$tt            = eyecare_du_lieu_thuc_the();
	$paged         = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$thu_muc_cha   = get_category_by_slug( 'kien-thuc' );
	$thu_muc       = array();
	$thu_muc_chon  = null;
	$slug_dang_xem = isset( $_GET['chu-de'] ) ? sanitize_title( wp_unslash( $_GET['chu-de'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $thu_muc_cha ) {
		$thu_muc = get_categories(
			array(
				'taxonomy'   => 'category',
				'parent'     => (int) $thu_muc_cha->term_id,
				'hide_empty' => false,
			)
		);

		usort(
			$thu_muc,
			static function ( $a, $b ) {
				$thu_tu_a = (int) get_term_meta( $a->term_id, '_eyecare_thu_tu', true );
				$thu_tu_b = (int) get_term_meta( $b->term_id, '_eyecare_thu_tu', true );
				return $thu_tu_a <=> $thu_tu_b;
			}
		);

		foreach ( $thu_muc as $muc ) {
			if ( $slug_dang_xem === $muc->slug ) {
				$thu_muc_chon = $muc;
				break;
			}
		}
	}

	$tham_so = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 10,
		'paged'               => $paged,
		'ignore_sticky_posts' => false,
	);
	if ( $thu_muc_chon ) {
		$tham_so['cat'] = (int) $thu_muc_chon->term_id;
	}

	$bai_viet    = new WP_Query( $tham_so );
	$tong_bai    = (int) wp_count_posts( 'post' )->publish;
	$link_thu_vien = get_permalink();
	?>

<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-kien-thuc' ); ?>>
	<section class="eyecare-page-hero eyecare-page-hero--kien-thuc" aria-labelledby="kien-thuc-tieu-de">
		<div class="eyecare-page-hero__khung">
			<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?>
				<div class="eyecare-page-hero__duong-dan"><?php eyecare_duong_dan_in(); ?></div>
			<?php endif; ?>
			<div class="eyecare-page-hero__grid">
				<div>
					<p class="eyecare-page-hero__nhan">Thư viện sức khỏe mắt</p>
					<h1 id="kien-thuc-tieu-de"><?php the_title(); ?></h1>
					<p class="eyecare-page-hero__dan">Tổng hợp bài viết giúp người đọc hiểu rõ hơn về dấu hiệu thường gặp, cách chăm sóc mắt và những điều cần chuẩn bị trước khi đi khám.</p>
					<form class="eyecare-kien-thuc__tim" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label class="screen-reader-text" for="tim-kien-thuc">Tìm bài viết</label>
						<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/></svg>
						<input id="tim-kien-thuc" type="search" name="s" placeholder="Tìm bài viết về mắt...">
						<input type="hidden" name="post_type" value="post">
						<button type="submit">Tìm kiếm</button>
					</form>
				</div>
				<div class="eyecare-page-hero__art" aria-hidden="true">
					<svg viewBox="0 0 260 230"><path d="M43 40h142a22 22 0 0 1 22 22v129H65a22 22 0 0 1-22-22V40Z"/><path d="M74 78h102M74 105h72M74 132h90"/><path d="M171 159c19-28 58-28 77 0-19 28-58 28-77 0Z"/><circle cx="209.5" cy="159" r="11"/></svg>
					<span><strong><?php echo esc_html( (string) $tong_bai ); ?></strong> bài viết đang xuất bản</span>
				</div>
			</div>
		</div>
	</section>

	<?php if ( $thu_muc ) : ?>
	<section class="eyecare-kien-thuc__thu-muc" aria-labelledby="thu-muc-kien-thuc-tieu-de">
		<div class="eyecare-noi-dung__khung">
			<header class="eyecare-section-heading">
				<div><p>Thư viện theo chủ đề</p><h2 id="thu-muc-kien-thuc-tieu-de">Chọn thư mục bài viết</h2></div>
				<span>Mỗi bài được xếp vào một nhóm chuyên môn để người đọc và quản trị viên tìm nhanh hơn.</span>
			</header>

			<nav class="eyecare-kien-thuc__thu-muc-grid" aria-label="Thư mục bài viết kiến thức">
				<a class="eyecare-kien-thuc__folder<?php echo $thu_muc_chon ? '' : ' is-active'; ?>" href="<?php echo esc_url( $link_thu_vien ); ?>"<?php echo $thu_muc_chon ? '' : ' aria-current="page"'; ?>>
					<span class="eyecare-kien-thuc__folder-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M3.5 8.5h9l2.5 3h13.5v14H3.5z"/><path d="M3.5 11.5h25"/></svg></span>
					<span><small>Tất cả</small><strong>Toàn bộ bài viết</strong><em><?php echo esc_html( (string) $tong_bai ); ?> bài</em></span>
				</a>

				<?php foreach ( $thu_muc as $muc ) :
					$ma_cum  = (string) get_term_meta( $muc->term_id, '_eyecare_ma_cum', true );
					$dang_xem = $thu_muc_chon && (int) $thu_muc_chon->term_id === (int) $muc->term_id;
					$link_muc = get_term_link( $muc );
					if ( is_wp_error( $link_muc ) ) {
						$link_muc = $link_thu_vien;
					}
					?>
					<a class="eyecare-kien-thuc__folder<?php echo $dang_xem ? ' is-active' : ''; ?>" href="<?php echo esc_url( $link_muc ); ?>"<?php echo $dang_xem ? ' aria-current="page"' : ''; ?>>
						<span class="eyecare-kien-thuc__folder-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M3.5 8.5h9l2.5 3h13.5v14H3.5z"/><path d="M3.5 11.5h25"/></svg></span>
						<span><small><?php echo esc_html( $ma_cum ); ?></small><strong><?php echo esc_html( $muc->name ); ?></strong><em><?php echo esc_html( (string) $muc->count ); ?> bài</em></span>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
	</section>
	<?php endif; ?>

	<section class="eyecare-kien-thuc__noi-dung" aria-labelledby="bai-viet-moi-tieu-de">
		<div class="eyecare-noi-dung__khung">
			<header class="eyecare-section-heading">
				<div><p><?php echo $thu_muc_chon ? 'Đang xem thư mục' : 'Cập nhật mới'; ?></p><h2 id="bai-viet-moi-tieu-de"><?php echo esc_html( $thu_muc_chon ? $thu_muc_chon->name : 'Bài viết kiến thức nhãn khoa' ); ?></h2></div>
				<span>Thông tin trên website có mục đích tham khảo và không thay thế việc khám, chẩn đoán trực tiếp.</span>
			</header>

			<?php if ( $bai_viet->have_posts() ) : ?>
				<div class="eyecare-kien-thuc__luoi">
					<?php $i = 0; while ( $bai_viet->have_posts() ) : $bai_viet->the_post();
						$anh = get_the_post_thumbnail_url( get_the_ID(), 'large' );
						$tom_tat = eyecare_tom_tat_bai_viet_sach( get_the_ID(), 25 );
						$thu_muc_bai = null;
						if ( $thu_muc_cha ) {
							$cac_muc_bai = wp_get_post_terms( get_the_ID(), 'category' );
							foreach ( $cac_muc_bai as $muc_bai ) {
								if ( (int) $muc_bai->parent === (int) $thu_muc_cha->term_id ) {
									$thu_muc_bai = $muc_bai;
									break;
								}
							}
						}
						$link_danh_muc_bai = $thu_muc_bai ? get_term_link( $thu_muc_bai ) : '';
						if ( is_wp_error( $link_danh_muc_bai ) ) {
							$link_danh_muc_bai = '';
						}
						?>
						<article class="eyecare-kien-thuc__the<?php echo 0 === $i ? ' eyecare-kien-thuc__the--noi-bat' : ''; ?>">
							<a class="eyecare-kien-thuc__anh" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
								<?php if ( $anh ) : ?>
									<img src="<?php echo esc_url( $anh ); ?>" alt="" loading="lazy" decoding="async">
								<?php else : ?>
									<?php echo eyecare_anh_bai_du_phong( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG nội bộ, dữ liệu động đã escape trong hàm. ?>
								<?php endif; ?>
							</a>
							<div class="eyecare-kien-thuc__the-copy">
								<p class="eyecare-kien-thuc__meta">
									<?php if ( $thu_muc_bai && $link_danh_muc_bai ) : ?><a href="<?php echo esc_url( $link_danh_muc_bai ); ?>"><?php echo esc_html( $thu_muc_bai->name ); ?></a><?php endif; ?>
									<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></time>
								</p>
								<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<?php if ( $tom_tat ) : ?><p><?php echo esc_html( $tom_tat ); ?></p><?php endif; ?>
								<a class="eyecare-kien-thuc__doc" href="<?php the_permalink(); ?>">Đọc bài viết <span aria-hidden="true">→</span></a>
							</div>
						</article>
						<?php $i++; endwhile; ?>
				</div>

				<?php
				$phan_trang = paginate_links(
					array(
						'total'   => $bai_viet->max_num_pages,
						'current' => $paged,
						'type'    => 'list',
						'prev_text' => '← Trước',
						'next_text' => 'Sau →',
						'add_args'  => $thu_muc_chon ? array( 'chu-de' => $thu_muc_chon->slug ) : array(),
					)
				);
				if ( $phan_trang ) {
					echo '<nav class="eyecare-kien-thuc__phan-trang" aria-label="Phân trang bài viết">' . wp_kses_post( $phan_trang ) . '</nav>';
				}
				?>
			<?php else : ?>
				<div class="eyecare-kien-thuc__rong">
					<strong>Thư mục này chưa có bài viết công khai.</strong>
					<p>Quay lại toàn bộ thư viện hoặc chọn một chủ đề khác.</p>
					<a href="<?php echo esc_url( $link_thu_vien ); ?>">Xem tất cả bài viết</a>
				</div>
			<?php endif; wp_reset_postdata(); ?>
		</div>
	</section>

	<section class="eyecare-page-cta">
		<div class="eyecare-noi-dung__khung eyecare-page-cta__trong">
			<div><p>Cần được hướng dẫn thêm?</p><h2>Liên hệ bệnh viện trước khi đến</h2></div>
			<a class="eyecare-nut eyecare-nut--chinh" href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">Gọi <?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a>
		</div>
	</section>
</article>

	<?php
endwhile;

get_footer();
