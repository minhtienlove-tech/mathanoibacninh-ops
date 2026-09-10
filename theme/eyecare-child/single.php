<?php
/**
 * Khuôn bài đơn — bài kiến thức nhãn khoa.
 *
 * Vì sao theme con cần template riêng thay vì dùng của Flatsome: bài kiến
 * thức dài 2.500 từ, người đọc chủ yếu là người đang lo về mắt mình, tra cứu
 * bằng điện thoại, và một phần đáng kể có thị lực đã giảm. Khuôn mặc định
 * của Flatsome dựng cho blog thương mại — bề rộng dòng quá dài, không có
 * mục lục, và có phần chia sẻ mạng xã hội chen giữa nội dung y khoa.
 *
 * 🔴 KHÔNG đặt nút đặt lịch trong khuôn này. Bài cụm C8 (dấu hiệu cảnh báo)
 * dùng chung khuôn, mà KIẾN TRÚC §6.3 quy tắc 2 cấm nút đặt lịch trong bài
 * dấu hiệu cảnh báo — người thấy chớp sáng cần đến cơ sở gần nhất hôm nay,
 * không phải đặt lịch tuần sau. Đặt nút ở khuôn dùng chung là vi phạm ở cả
 * 100 bài cùng lúc.
 *
 * @package Eyecare_Child
 */

get_header();

while ( have_posts() ) :
	the_post();

	$bai_hien_tai     = get_the_ID();
	$danh_muc_hien_tai = wp_get_post_categories( $bai_hien_tai );
	$bai_lien_quan    = array();

	if ( $danh_muc_hien_tai ) {
		$bai_lien_quan = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 5,
				'post__not_in'        => array( $bai_hien_tai ),
				'category__in'        => $danh_muc_hien_tai,
				'orderby'              => 'date',
				'order'                => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
			)
		);
	}

	// Một chuyên mục mới có thể chưa đủ năm bài. Bổ sung bài kiến thức gần
	// nhất để cột trái không bị trống, nhưng không lặp lại bài đã chọn.
	if ( count( $bai_lien_quan ) < 5 ) {
		$bai_bo_sung = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 5 - count( $bai_lien_quan ),
				'post__not_in'        => array_merge( array( $bai_hien_tai ), $bai_lien_quan ),
				'orderby'              => 'date',
				'order'                => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
			)
		);

		$bai_lien_quan = array_merge( $bai_lien_quan, $bai_bo_sung );
	}

	$bai_moi_nhat = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 5,
			'post__not_in'        => array( $bai_hien_tai ),
			'orderby'              => 'date',
			'order'                => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
		)
	);
	?>

<article id="bai-<?php the_ID(); ?>" <?php post_class( 'eyecare-bai' ); ?>>
	<div class="eyecare-bai__bo-cuc">
		<div class="eyecare-bai__noi-dung">

	<header class="eyecare-bai__dau">
		<?php
		// Đường dẫn phân cấp — người vào từ kết quả tìm kiếm cần biết mình
		// đang ở đâu trong site. Hàm tự dựng (inc/dau-trang.php), khớp thứ tự
		// với breadcrumb schema; bọc trong lớp cũ để giữ nguyên kiểu CSS.
		echo '<div class="eyecare-bai__duong-dan">';
		eyecare_duong_dan_in();
		echo '</div>';
		?>

		<h1 class="eyecare-bai__tieu-de"><?php the_title(); ?></h1>

		<?php
		/* Ngày cập nhật, không phải ngày đăng. Nội dung y khoa cũ là nội dung
		   đáng ngờ — người đọc cần biết bài còn mới hay không. Chỉ hiện khi
		   bài đã thực sự được sửa sau khi đăng. */
		$ngay_dang = get_the_time( 'U' );
		$ngay_sua  = get_the_modified_time( 'U' );
		?>
		<p class="eyecare-bai__ngay">
			<?php if ( $ngay_sua > $ngay_dang + DAY_IN_SECONDS ) : ?>
				<span class="eyecare-bai__nhan-ngay">Cập nhật</span>
				<time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
					<?php echo esc_html( get_the_modified_date( 'j/n/Y' ) ); ?>
				</time>
			<?php else : ?>
				<span class="eyecare-bai__nhan-ngay">Đăng ngày</span>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
					<?php echo esc_html( get_the_date( 'j/n/Y' ) ); ?>
				</time>
			<?php endif; ?>

			<?php
			// Số phút đọc: bài 2.500 từ là cam kết thời gian thật với người
			// đọc. Nói trước thì họ chọn đọc ngay hay lưu lại, thay vì bỏ
			// giữa chừng. ~200 từ/phút cho tiếng Việt phổ thông.
			//
			// Đếm bằng preg_split chứ KHÔNG dùng str_word_count(): hàm đó
			// cắt theo byte nên tiếng Việt có dấu bị đếm thành nhiều từ.
			$chu     = wp_strip_all_tags( strip_shortcodes( get_the_content() ) );
			$so_tu   = count( preg_split( '/\s+/u', trim( $chu ), -1, PREG_SPLIT_NO_EMPTY ) );
			$so_phut = max( 1, (int) round( $so_tu / 200 ) );
			?>
			<span class="eyecare-bai__tach">·</span>
			<span class="eyecare-bai__phut"><?php echo esc_html( $so_phut ); ?> phút đọc</span>
		</p>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="eyecare-bai__anh-dai-dien">
			<?php the_post_thumbnail( 'large', array( 'alt' => get_the_title(), 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?>
		</figure>
	<?php endif; ?>

	<div class="eyecare-bai__than">
		<?php the_content(); ?>
	</div>
		</div>

		<aside class="eyecare-bai__cot-ben eyecare-bai__cot-ben--lien-quan" aria-labelledby="eyecare-bai-lien-quan">
			<div class="eyecare-bai__ben-trong">
				<header class="eyecare-bai__dau-cot">
					<span class="eyecare-bai__icon-cot" aria-hidden="true">
						<svg viewBox="0 0 32 32"><path d="M5 16s4.2-7.5 11-7.5S27 16 27 16s-4.2 7.5-11 7.5S5 16 5 16Z"/><circle cx="16" cy="16" r="3.5"/></svg>
					</span>
					<div><span>Cùng chủ đề</span><h2 id="eyecare-bai-lien-quan">Bài viết liên quan</h2></div>
				</header>

				<?php if ( $bai_lien_quan ) : ?>
					<ul class="eyecare-bai__danh-sach">
						<?php foreach ( $bai_lien_quan as $id_bai ) :
							$tieu_de_bai = get_the_title( $id_bai );
							$cac_danh_muc = get_the_category( $id_bai );
							$ten_danh_muc = $cac_danh_muc ? $cac_danh_muc[0]->name : 'Kiến thức nhãn khoa';
							?>
							<li>
								<a class="eyecare-bai__bai-ben" href="<?php echo esc_url( get_permalink( $id_bai ) ); ?>">
									<span class="eyecare-bai__anh-ben">
										<?php if ( has_post_thumbnail( $id_bai ) ) : ?>
											<?php echo wp_kses_post( get_the_post_thumbnail( $id_bai, 'medium', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) ); ?>
										<?php else : ?>
											<?php echo eyecare_anh_bai_du_phong( $id_bai, 'eyecare-anh-bai-du-phong--mini' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG nội bộ, dữ liệu động đã escape trong hàm. ?>
										<?php endif; ?>
									</span>
									<span class="eyecare-bai__copy-ben">
										<span class="eyecare-bai__muc-ben"><?php echo esc_html( $ten_danh_muc ); ?></span>
										<strong><?php echo esc_html( $tieu_de_bai ); ?></strong>
										<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $id_bai ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y', $id_bai ) ); ?></time>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</aside>

		<aside class="eyecare-bai__cot-ben eyecare-bai__cot-ben--moi" aria-labelledby="eyecare-bai-moi-nhat">
			<div class="eyecare-bai__ben-trong">
				<header class="eyecare-bai__dau-cot">
					<span class="eyecare-bai__icon-cot" aria-hidden="true">
						<svg viewBox="0 0 32 32"><path d="M16 5v11l7 4"/><circle cx="16" cy="16" r="11"/></svg>
					</span>
					<div><span>Vừa cập nhật</span><h2 id="eyecare-bai-moi-nhat">Bài viết mới nhất</h2></div>
				</header>

				<?php if ( $bai_moi_nhat ) : ?>
					<ol class="eyecare-bai__danh-sach eyecare-bai__danh-sach--moi">
						<?php foreach ( $bai_moi_nhat as $thu_tu => $id_bai ) :
							$tieu_de_bai = get_the_title( $id_bai );
							$cac_danh_muc = get_the_category( $id_bai );
							$ten_danh_muc = $cac_danh_muc ? $cac_danh_muc[0]->name : 'Kiến thức nhãn khoa';
							?>
							<li>
								<a class="eyecare-bai__bai-ben" href="<?php echo esc_url( get_permalink( $id_bai ) ); ?>">
									<span class="eyecare-bai__anh-ben">
										<?php if ( has_post_thumbnail( $id_bai ) ) : ?>
											<?php echo wp_kses_post( get_the_post_thumbnail( $id_bai, 'medium', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) ); ?>
										<?php else : ?>
											<?php echo eyecare_anh_bai_du_phong( $id_bai, 'eyecare-anh-bai-du-phong--mini' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG nội bộ, dữ liệu động đã escape trong hàm. ?>
										<?php endif; ?>
										<span class="eyecare-bai__so-thu-tu" aria-hidden="true"><?php echo esc_html( (string) ( $thu_tu + 1 ) ); ?></span>
									</span>
									<span class="eyecare-bai__copy-ben">
										<span class="eyecare-bai__muc-ben"><?php echo esc_html( $ten_danh_muc ); ?></span>
										<strong><?php echo esc_html( $tieu_de_bai ); ?></strong>
										<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $id_bai ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y', $id_bai ) ); ?></time>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</div>
		</aside>
	</div>

</article>

	<?php
endwhile;

get_footer();
