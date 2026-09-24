<?php
/**
 * Khuôn trang tĩnh chung — Bệnh viện Mắt Hà Nội – Bắc Ninh.
 *
 * WordPress dùng khuôn này cho MỌI trang (post_type=page) không có khuôn
 * riêng page-{slug}.php. Hiện có 59 trang, phần lớn còn rỗng.
 *
 * BA TRẠNG THÁI TRANG, XỬ LÝ KHÁC NHAU:
 *
 *   1. Trang CÓ NỘI DUNG  → in bình thường: đường dẫn, H1, thân bài.
 *
 *   2. Trang RỖNG nhưng LÀ TRANG CHA và có trang con đã publish + có nội dung
 *      → tự liệt kê trang con (biến trang hub thành có ích mà không cần viết
 *      khuôn riêng). Đây là cấu trúc điều hướng, KHÔNG phải nội dung bịa ra.
 *
 *   3. Trang RỖNG hẳn, không con nào → chỉ hiện đường dẫn + H1, kèm cảnh báo
 *      cho người biên tập (người đọc thường không thấy). KHÔNG bịa nội dung
 *      để trang "trông đầy": trang bệnh học chờ bác sĩ ký (B-01b), trang dịch
 *      vụ chờ Giấy phép hoạt động (B-03).
 *
 * 🔴 KHÔNG đặt nút đặt lịch / bảng giá ở khuôn dùng chung — xem lý do trong
 * front-page.php và single.php. Khuôn này phủ cả nhánh /dich-vu/ và /khu-vuc/.
 *
 * @package Eyecare_Child
 */

/* Các trang con của nhóm Tật khúc xạ có bố cục bài chuyên khoa riêng.
 * Giữ việc phân nhánh ở đây để những trang mới tạo dưới nhóm này cũng tự
 * nhận đúng giao diện, thay vì rơi về khối hỗ trợ dấu hỏi của trang rỗng. */
$eyecare_trang_khuc_xa = get_page_by_path( 'chuyen-khoa/tat-khuc-xa' );
if ( $eyecare_trang_khuc_xa instanceof WP_Post && is_page() ) {
	$eyecare_cha_ids = get_post_ancestors( get_queried_object_id() );
	if ( in_array( (int) $eyecare_trang_khuc_xa->ID, array_map( 'intval', $eyecare_cha_ids ), true ) ) {
		get_template_part( 'template-parts/trang-khuc-xa' );
		return;
	}
}

get_header();

while ( have_posts() ) :
	the_post();

	$noi_dung = trim( get_the_content() );
	$co_chu   = '' !== trim( wp_strip_all_tags( strip_shortcodes( $noi_dung ) ) )
		|| ( function_exists( 'eyecare_khu_vuc_co_noi_dung' ) && eyecare_khu_vuc_co_noi_dung( get_post() ) );
	$trang_cha = get_post_parent();
	$la_dich_vu_con = $trang_cha instanceof WP_Post && 'dich-vu' === $trang_cha->post_name;
	$la_trang_co_sidebar = $co_chu && ! $la_dich_vu_con;
	$dich_vu_khac   = array();
	$bai_moi_nhat   = array();
	$trang_lien_quan = array();

	/* Các page có nội dung dài dùng cùng một nhịp đọc với bài kiến thức:
	 * cột giữa là nội dung chính, hai bên là điều hướng hữu ích. Siblings
	 * được lấy từ WordPress để khi admin thêm trang mới, sidebar tự cập nhật. */
	if ( $la_trang_co_sidebar ) {
		if ( $trang_cha instanceof WP_Post ) {
			$trang_lien_quan = get_children(
				array(
					'post_parent' => $trang_cha->ID,
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post__not_in' => array( get_the_ID() ),
					'orderby'     => 'menu_order title',
					'order'       => 'ASC',
					'number'      => 5,
				)
			);
		}

		$bai_moi_nhat = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 5,
				'orderby'              => 'date',
				'order'                => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
			)
		);
	}

	if ( $la_dich_vu_con ) {
		foreach ( eyecare_trang_con_hub( 'dich-vu', 30 ) as $muc_dich_vu ) {
			if ( ! empty( $muc_dich_vu['trang'] ) && (int) $muc_dich_vu['trang']->ID !== get_the_ID() ) {
				$dich_vu_khac[] = $muc_dich_vu['trang'];
			}
		}

		$bai_moi_nhat = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 5,
				'orderby'              => 'date',
				'order'                => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'fields'              => 'ids',
			)
		);
	}
	?>

	<?php
	$lop_trang = $la_dich_vu_con
		? 'eyecare-trang eyecare-trang--dich-vu-con'
		: ( $la_trang_co_sidebar ? 'eyecare-trang eyecare-trang--co-sidebar' : 'eyecare-trang' );
	?>
	<article id="trang-<?php the_ID(); ?>" <?php post_class( $lop_trang ); ?>>
	<?php if ( $la_dich_vu_con ) : ?>
		<div class="eyecare-bai__bo-cuc eyecare-trang-dich-vu__bo-cuc">
			<div class="eyecare-bai__noi-dung eyecare-trang-dich-vu__noi-dung">
	<?php elseif ( $la_trang_co_sidebar ) : ?>
		<div class="eyecare-trang__bo-cuc">
			<div class="eyecare-trang__cot-chinh">
	<?php endif; ?>

	<header class="eyecare-trang__dau">
		<?php
		// Đường dẫn phân cấp tự dựng (thay flatsome_breadcrumb đã gỡ).
		if ( function_exists( 'eyecare_duong_dan_in' ) ) {
			echo '<div class="eyecare-trang__duong-dan">';
			eyecare_duong_dan_in();
			echo '</div>';
		}
		?>
		<h1 class="eyecare-trang__tieu-de"><?php echo esc_html( function_exists( 'eyecare_khu_vuc_h1' ) ? eyecare_khu_vuc_h1( get_post() ) : get_the_title() ); ?></h1>
	</header>

	<?php if ( $co_chu ) : ?>

		<div class="eyecare-trang__than">
			<?php the_content(); ?>
		</div>

	<?php else : ?>

		<?php
		/* Trang rỗng — liệt kê trang con đã publish làm mục lục nhánh.
		   Dùng eyecare_trang_con_hub() (giữ cả trang rỗng, có đánh dấu) chứ
		   KHÔNG dùng eyecare_trang_con_co_noi_dung(): xem lý do đầy đủ ở chú
		   thích của hàm trong inc/trang-chu.php. Ngắn gọn: hub là mục lục của
		   nhánh, ẩn hết trang chưa viết xong thì người đọc kết luận sai rằng
		   bệnh viện không có chuyên khoa đó. */
		$con = eyecare_trang_con_hub( get_post_field( 'post_name', get_the_ID() ) );

		if ( $con ) :
			?>
			<nav class="eyecare-trang__con" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
				<ul class="eyecare-chu__the-ds">
					<?php foreach ( $con as $c ) : ?>
						<li class="eyecare-chu__the">
							<a class="eyecare-chu__the-lk<?php echo $c['co_chu'] ? '' : ' eyecare-chu__the-lk--cho'; ?>"
								href="<?php echo esc_url( get_permalink( $c['trang']->ID ) ); ?>">
								<span class="eyecare-chu__the-ten"><?php echo esc_html( get_the_title( $c['trang']->ID ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
			<?php
		else :
			/* Không nội dung, không con — trang thật sự trống.
			   Người đọc thấy một đoạn trung tính; người biên tập thấy thêm lý do. */
			?>
			<div class="eyecare-trang__than eyecare-trang__than--rong">
				<div class="eyecare-trang__ho-tro">
					<span class="eyecare-trang__ho-tro-icon" aria-hidden="true">?</span>
					<div>
						<h2>Bạn cần hỗ trợ về nội dung này?</h2>
						<p>Liên hệ tổng đài để được hướng dẫn thông tin phù hợp trước khi đến bệnh viện.</p>
						<a href="tel:<?php echo esc_attr( eyecare_du_lieu_thuc_the()['dien_thoai'] ); ?>">Gọi <?php echo esc_html( eyecare_du_lieu_thuc_the()['dien_thoai_hien'] ); ?></a>
					</div>
				</div>
			</div>
			<?php
		endif;
		?>

	<?php endif; ?>

	<?php if ( $la_dich_vu_con ) : ?>
			</div>

			<aside class="eyecare-bai__cot-ben eyecare-bai__cot-ben--lien-quan" aria-labelledby="eyecare-dich-vu-lien-quan-<?php the_ID(); ?>">
				<div class="eyecare-bai__ben-trong">
					<header class="eyecare-bai__dau-cot">
						<span class="eyecare-bai__icon-cot" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M5 16s4.2-7.5 11-7.5S27 16 27 16s-4.2 7.5-11 7.5S5 16 5 16Z"/><circle cx="16" cy="16" r="3.5"/></svg></span>
						<div><span>Cùng danh mục</span><h2 id="eyecare-dich-vu-lien-quan-<?php the_ID(); ?>">Dịch vụ khác</h2></div>
					</header>

					<?php if ( $dich_vu_khac ) : ?>
						<ul class="eyecare-bai__danh-sach">
							<?php foreach ( $dich_vu_khac as $thu_tu => $trang_dich_vu ) : ?>
								<li>
									<a class="eyecare-bai__bai-ben" href="<?php echo esc_url( get_permalink( $trang_dich_vu->ID ) ); ?>">
										<span class="eyecare-bai__anh-ben eyecare-dich-vu-phu__anh" aria-hidden="true">
											<svg viewBox="0 0 64 64"><path d="M7 32s9-15 25-15 25 15 25 15-9 15-25 15S7 32 7 32Z"/><circle cx="32" cy="32" r="8"/></svg>
											<span><?php echo esc_html( str_pad( (string) ( $thu_tu + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
										</span>
										<span class="eyecare-bai__copy-ben">
											<span class="eyecare-bai__muc-ben">Dịch vụ nhãn khoa</span>
											<strong><?php echo esc_html( get_the_title( $trang_dich_vu->ID ) ); ?></strong>
											<small>Xem thông tin chi tiết</small>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</aside>

			<aside class="eyecare-bai__cot-ben eyecare-bai__cot-ben--moi" aria-labelledby="eyecare-bai-moi-dich-vu-<?php the_ID(); ?>">
				<div class="eyecare-bai__ben-trong">
					<header class="eyecare-bai__dau-cot">
						<span class="eyecare-bai__icon-cot" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M16 5v11l7 4"/><circle cx="16" cy="16" r="11"/></svg></span>
						<div><span>Kiến thức hữu ích</span><h2 id="eyecare-bai-moi-dich-vu-<?php the_ID(); ?>">Bài viết mới</h2></div>
					</header>

					<?php if ( $bai_moi_nhat ) : ?>
						<ol class="eyecare-bai__danh-sach eyecare-bai__danh-sach--moi">
							<?php foreach ( $bai_moi_nhat as $thu_tu => $id_bai ) :
								$cac_danh_muc = get_the_category( $id_bai );
								$ten_danh_muc = $cac_danh_muc ? $cac_danh_muc[0]->name : 'Kiến thức nhãn khoa';
								?>
								<li>
									<a class="eyecare-bai__bai-ben" href="<?php echo esc_url( get_permalink( $id_bai ) ); ?>">
										<span class="eyecare-bai__anh-ben">
											<?php if ( has_post_thumbnail( $id_bai ) ) : ?>
												<?php echo wp_kses_post( get_the_post_thumbnail( $id_bai, 'medium', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) ); ?>
											<?php else : ?>
												<?php echo eyecare_anh_bai_du_phong( $id_bai, 'eyecare-anh-bai-du-phong--mini' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG nội bộ. ?>
											<?php endif; ?>
											<span class="eyecare-bai__so-thu-tu" aria-hidden="true"><?php echo esc_html( (string) ( $thu_tu + 1 ) ); ?></span>
										</span>
										<span class="eyecare-bai__copy-ben">
											<span class="eyecare-bai__muc-ben"><?php echo esc_html( $ten_danh_muc ); ?></span>
											<strong><?php echo esc_html( get_the_title( $id_bai ) ); ?></strong>
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
	<?php elseif ( $la_trang_co_sidebar ) : ?>
			</div>

			<aside class="eyecare-trang__cot-ben eyecare-trang__cot-ben--lien-quan" aria-labelledby="eyecare-trang-lien-quan-<?php the_ID(); ?>">
				<div class="eyecare-bai__ben-trong">
					<header class="eyecare-bai__dau-cot">
						<span class="eyecare-bai__icon-cot" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M5 16s4.2-7.5 11-7.5S27 16 27 16s-4.2 7.5-11 7.5S5 16 5 16Z"/><circle cx="16" cy="16" r="3.5"/></svg></span>
						<div><span>Đọc thêm</span><h2 id="eyecare-trang-lien-quan-<?php the_ID(); ?>">Trang liên quan</h2></div>
					</header>
					<?php if ( $trang_lien_quan ) : ?>
						<ul class="eyecare-bai__danh-sach">
							<?php foreach ( $trang_lien_quan as $trang_lien_quan_item ) : ?>
								<li><a class="eyecare-bai__bai-ben eyecare-trang__lien-ket-ben" href="<?php echo esc_url( get_permalink( $trang_lien_quan_item->ID ) ); ?>"><span class="eyecare-trang__so-ben" aria-hidden="true">→</span><span class="eyecare-bai__copy-ben"><strong><?php echo esc_html( get_the_title( $trang_lien_quan_item->ID ) ); ?></strong><small>Xem thông tin chi tiết</small></span></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="eyecare-trang__lien-ket-nhanh">
						<a href="<?php echo esc_url( home_url( '/chuyen-khoa/' ) ); ?>">Chuyên khoa mắt <span>→</span></a>
						<a href="<?php echo esc_url( home_url( '/dich-vu/' ) ); ?>">Dịch vụ nhãn khoa <span>→</span></a>
						<a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">Thông tin liên hệ <span>→</span></a>
					</div>
				</div>
			</aside>

			<aside class="eyecare-trang__cot-ben eyecare-trang__cot-ben--moi" aria-labelledby="eyecare-trang-bai-moi-<?php the_ID(); ?>">
				<div class="eyecare-bai__ben-trong">
					<header class="eyecare-bai__dau-cot">
						<span class="eyecare-bai__icon-cot" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M16 5v11l7 4"/><circle cx="16" cy="16" r="11"/></svg></span>
						<div><span>Vừa cập nhật</span><h2 id="eyecare-trang-bai-moi-<?php the_ID(); ?>">Bài viết mới nhất</h2></div>
					</header>
					<?php if ( $bai_moi_nhat ) : ?>
						<ol class="eyecare-bai__danh-sach eyecare-bai__danh-sach--moi">
							<?php foreach ( $bai_moi_nhat as $thu_tu => $id_bai ) : ?>
								<li><a class="eyecare-bai__bai-ben" href="<?php echo esc_url( get_permalink( $id_bai ) ); ?>"><span class="eyecare-bai__anh-ben"><?php if ( has_post_thumbnail( $id_bai ) ) : ?>
<?php echo wp_kses_post( get_the_post_thumbnail( $id_bai, 'medium', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ) ); ?>
<?php else : ?>
<?php echo eyecare_anh_bai_du_phong( $id_bai, 'eyecare-anh-bai-du-phong--mini' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup nội bộ đã escape. ?>
<?php endif; ?><span class="eyecare-bai__so-thu-tu" aria-hidden="true"><?php echo esc_html( (string) ( $thu_tu + 1 ) ); ?></span></span><span class="eyecare-bai__copy-ben"><span class="eyecare-bai__muc-ben">Kiến thức nhãn khoa</span><strong><?php echo esc_html( get_the_title( $id_bai ) ); ?></strong><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $id_bai ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y', $id_bai ) ); ?></time></span></a></li>
							<?php endforeach; ?>
						</ol>
					<?php else : ?>
						<p class="eyecare-trang__cot-rong">Bài viết mới sẽ được cập nhật tại đây.</p>
					<?php endif; ?>
					<div class="eyecare-trang__lien-ket-nhanh eyecare-trang__lien-ket-nhanh--vang">
						<a href="<?php echo esc_url( home_url( '/kien-thuc/' ) ); ?>">Thư viện kiến thức <span>→</span></a>
						<a href="<?php echo esc_url( home_url( '/hoi-dap/' ) ); ?>">Hỏi đáp nhãn khoa <span>→</span></a>
						<a href="<?php echo esc_url( home_url( '/doi-ngu-bac-si/' ) ); ?>">Đội ngũ bác sĩ <span>→</span></a>
					</div>
				</div>
			</aside>
		</div>
	<?php endif; ?>

</article>

	<?php
endwhile;

get_footer();
