<?php
/**
 * Bố cục bài con của nhóm Tật khúc xạ.
 *
 * Các trang Cận thị, Viễn thị và Loạn thị là page để quản trị viên chỉnh
 * sửa trực tiếp trong WordPress. Template này chỉ chịu trách nhiệm bố cục:
 * bài chính ở giữa, chuyên khoa liên quan bên trái và nội dung mới bên phải.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$trang_hien_tai = get_the_ID();
	$trang_cha      = get_post_parent();
	$trang_lien_quan = array();

	if ( $trang_cha instanceof WP_Post ) {
		$trang_lien_quan = get_children(
			array(
				'post_parent'    => $trang_cha->ID,
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post__not_in'   => array( $trang_hien_tai ),
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
				'number'         => 6,
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

	$dich_vu = function_exists( 'eyecare_trang_con_hub' ) ? eyecare_trang_con_hub( 'dich-vu', 8 ) : array();
	$tt      = function_exists( 'eyecare_du_lieu_thuc_the' ) ? eyecare_du_lieu_thuc_the() : array( 'dien_thoai' => '', 'dien_thoai_hien' => '' );
	$ten_tac_gia = function_exists( 'eyecare_bac_si_ten_day_du' ) ? eyecare_bac_si_ten_day_du() : 'Ths.BS Lê Như Tùng';
	$link_tac_gia = function_exists( 'eyecare_bac_si_duong_dan' ) ? eyecare_bac_si_duong_dan() : home_url( '/doi-ngu-bac-si/' );

	$chu = wp_strip_all_tags( strip_shortcodes( get_the_content() ) );
	$so_tu = count( preg_split( '/\s+/u', trim( $chu ), -1, PREG_SPLIT_NO_EMPTY ) );
	$so_phut = max( 1, (int) round( $so_tu / 200 ) );
	?>

<article id="bai-<?php the_ID(); ?>" <?php post_class( 'eyecare-bai eyecare-bai--khuc-xa' ); ?>>
	<div class="eyecare-bai__bo-cuc">
		<div class="eyecare-bai__noi-dung">
			<header class="eyecare-bai__dau">
				<div class="eyecare-bai__duong-dan"><?php eyecare_duong_dan_in(); ?></div>
				<p class="eyecare-khuc-xa__nhan"><span aria-hidden="true"></span> Cẩm nang tật khúc xạ</p>
				<h1 class="eyecare-bai__tieu-de"><?php the_title(); ?></h1>
				<p class="eyecare-bai__ngay">
					<span class="eyecare-bai__nhan-ngay">Cập nhật</span>
					<time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date( 'j/n/Y' ) ); ?></time>
					<span class="eyecare-bai__tach">·</span><span><?php echo esc_html( $so_phut ); ?> phút đọc</span>
				</p>
			</header>

			<div class="eyecare-bai__than">
				<?php the_content(); ?>

				<section class="eyecare-khuc-xa__eeat" aria-label="Thông tin người biên soạn">
					<div class="eyecare-khuc-xa__eeat-mark" aria-hidden="true">✓</div>
					<div>
						<p class="eyecare-khuc-xa__eeat-label">Nội dung y khoa được rà soát</p>
						<p><strong><?php echo esc_html( $ten_tac_gia ); ?></strong> · Bác sĩ chuyên khoa Mắt</p>
						<p class="eyecare-khuc-xa__eeat-note">Thông tin mang tính tham khảo, không thay thế chẩn đoán và chỉ định trực tiếp. Nếu thị lực giảm đột ngột, đau mắt dữ dội hoặc có chấn thương, hãy đến cơ sở y tế gần nhất.</p>
						<a href="<?php echo esc_url( $link_tac_gia ); ?>">Xem đội ngũ bác sĩ <span aria-hidden="true">→</span></a>
					</div>
				</section>
			</div>
		</div>

		<aside class="eyecare-bai__cot-ben eyecare-bai__cot-ben--lien-quan" aria-labelledby="khuc-xa-lien-quan">
			<div class="eyecare-bai__ben-trong">
				<header class="eyecare-bai__dau-cot">
					<span class="eyecare-bai__icon-cot" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M5 16s4.2-7.5 11-7.5S27 16 27 16s-4.2 7.5-11 7.5S5 16 5 16Z"/><circle cx="16" cy="16" r="3.5"/></svg></span>
					<div><span>Cùng nhóm khúc xạ</span><h2 id="khuc-xa-lien-quan">Tật khúc xạ khác</h2></div>
				</header>
				<ul class="eyecare-bai__danh-sach">
					<?php foreach ( $trang_lien_quan as $trang_khac ) : ?>
						<li><a class="eyecare-bai__bai-ben eyecare-khuc-xa__lien-ket" href="<?php echo esc_url( get_permalink( $trang_khac->ID ) ); ?>"><span class="eyecare-khuc-xa__so" aria-hidden="true">→</span><span class="eyecare-bai__copy-ben"><strong><?php echo esc_html( get_the_title( $trang_khac->ID ) ); ?></strong><small>Xem thông tin chuyên sâu</small></span></a></li>
					<?php endforeach; ?>
				</ul>
				<div class="eyecare-khuc-xa__side-links">
					<a href="<?php echo esc_url( home_url( '/chuyen-khoa/' ) ); ?>">Danh mục chuyên khoa <span>→</span></a>
					<a href="<?php echo esc_url( home_url( '/hoi-dap/truoc-khi-di-kham/' ) ); ?>">Chuẩn bị trước khi khám <span>→</span></a>
					<a href="<?php echo esc_url( home_url( '/doi-ngu-bac-si/' ) ); ?>">Đội ngũ bác sĩ <span>→</span></a>
				</div>
			</div>
		</aside>

		<aside class="eyecare-bai__cot-ben eyecare-bai__cot-ben--moi" aria-labelledby="khuc-xa-bai-moi">
			<div class="eyecare-bai__ben-trong">
				<header class="eyecare-bai__dau-cot">
					<span class="eyecare-bai__icon-cot" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M16 5v11l7 4"/><circle cx="16" cy="16" r="11"/></svg></span>
					<div><span>Vừa cập nhật</span><h2 id="khuc-xa-bai-moi">Bài viết mới nhất</h2></div>
				</header>
				<?php if ( $bai_moi_nhat ) : ?>
					<ol class="eyecare-bai__danh-sach eyecare-bai__danh-sach--moi">
						<?php foreach ( $bai_moi_nhat as $thu_tu => $id_bai ) : ?>
							<li><a class="eyecare-bai__bai-ben" href="<?php echo esc_url( get_permalink( $id_bai ) ); ?>"><span class="eyecare-bai__anh-ben"><span class="eyecare-bai__anh-thay" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M6 24s7-10 18-10 18 10 18 10-7 10-18 10S6 24 6 24Z"/><circle cx="24" cy="24" r="5"/></svg></span><span class="eyecare-bai__so-thu-tu" aria-hidden="true"><?php echo esc_html( (string) ( $thu_tu + 1 ) ); ?></span></span><span class="eyecare-bai__copy-ben"><span class="eyecare-bai__muc-ben">Kiến thức nhãn khoa</span><strong><?php echo esc_html( get_the_title( $id_bai ) ); ?></strong><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $id_bai ) ); ?>"><?php echo esc_html( get_the_date( 'd/m/Y', $id_bai ) ); ?></time></span></a></li>
						<?php endforeach; ?>
					</ol>
				<?php else : ?>
					<p class="eyecare-khuc-xa__empty">Bài viết mới sẽ được cập nhật tại đây.</p>
				<?php endif; ?>
				<div class="eyecare-khuc-xa__side-links eyecare-khuc-xa__side-links--vang">
					<a href="<?php echo esc_url( home_url( '/dich-vu/' ) ); ?>">Dịch vụ nhãn khoa <span>→</span></a>
					<a href="<?php echo esc_url( home_url( '/bang-gia/' ) ); ?>">Bảng giá tham khảo <span>→</span></a>
					<a href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">Liên hệ bệnh viện <span>→</span></a>
				</div>
			</div>
		</aside>
	</div>
</article>

	<?php
endwhile;

get_footer();
