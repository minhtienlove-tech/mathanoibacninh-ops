<?php
/**
 * Khuôn trang Đội ngũ bác sĩ — /doi-ngu-bac-si/.
 *
 * Khối đội ngũ dùng chung component poster đã được duyệt trên trang chủ.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$eyecare_co_doi_ngu  = function_exists( 'eyecare_doi_ngu_trang_chu_in' );
	$eyecare_noi_dung     = trim( get_the_content() );
	$eyecare_co_noi_dung  = '' !== trim( wp_strip_all_tags( strip_shortcodes( $eyecare_noi_dung ) ) );
	$eyecare_ho_so        = function_exists( 'eyecare_du_lieu_doi_ngu' ) ? eyecare_du_lieu_doi_ngu() : array();
	?>

<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-doi-ngu-page' ); ?>>
	<?php if ( $eyecare_co_doi_ngu ) : ?>
		<?php eyecare_doi_ngu_trang_chu_in( 1 ); ?>

	<?php else : ?>
		<section class="eyecare-doi-ngu-page__empty">
			<div class="eyecare-khung-trang">
				<h1><?php the_title(); ?></h1>
				<p>Danh sách bác sĩ đang được cập nhật. Vui lòng quay lại sau.</p>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $eyecare_ho_so ) : ?>
		<section class="eyecare-doctor-profiles" aria-labelledby="eyecare-doctor-profiles-title">
			<div class="eyecare-doctor-profiles__inner">
				<header class="eyecare-doctor-profiles__header">
					<p>Hồ sơ chuyên môn</p>
					<h2 id="eyecare-doctor-profiles-title">Tìm hiểu từng thành viên</h2>
					<p>Thông tin do bệnh viện quản lý và cập nhật theo hồ sơ của từng người. Các mốc thành tích chỉ hiển thị sau khi có nguồn đối chiếu.</p>
				</header>
				<?php foreach ( $eyecare_ho_so as $eyecare_bac_si ) : ?>
					<?php
					$eyecare_slug = function_exists( 'eyecare_bac_si_slug' ) ? eyecare_bac_si_slug( $eyecare_bac_si ) : sanitize_title( $eyecare_bac_si['ho_ten'] );
					$eyecare_fb   = function_exists( 'eyecare_bac_si_facebook_url' ) ? eyecare_bac_si_facebook_url( $eyecare_bac_si ) : '';
					$eyecare_link = $eyecare_fb ?: ( ! empty( $eyecare_bac_si['profile'] ) ? $eyecare_bac_si['profile'] : home_url( '/doi-ngu-bac-si/#bac-si-' . $eyecare_slug ) );
					$eyecare_bio  = isset( $eyecare_bac_si['gioi_thieu'] ) ? trim( (string) $eyecare_bac_si['gioi_thieu'] ) : '';
					$eyecare_name = function_exists( 'eyecare_doi_ngu_ten_day_du' ) ? eyecare_doi_ngu_ten_day_du( $eyecare_bac_si ) : $eyecare_bac_si['ho_ten'];
					?>
					<article id="bac-si-<?php echo esc_attr( $eyecare_slug ); ?>" class="eyecare-doctor-profiles__item">
						<div class="eyecare-doctor-profiles__summary">
							<h3><a href="<?php echo esc_url( $eyecare_link ); ?>"<?php echo $eyecare_fb ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $eyecare_name ); ?></a></h3>
							<?php if ( ! empty( $eyecare_bac_si['chuc_danh'] ) ) : ?><p><?php echo esc_html( $eyecare_bac_si['chuc_danh'] ); ?></p><?php endif; ?>
							<?php if ( ! empty( $eyecare_bac_si['chuyen_khoa'] ) ) : ?><p><?php echo esc_html( $eyecare_bac_si['chuyen_khoa'] ); ?></p><?php endif; ?>
							<?php if ( $eyecare_fb ) : ?><a href="<?php echo esc_url( $eyecare_fb ); ?>" target="_blank" rel="noopener noreferrer">Trang cá nhân Facebook ↗</a><?php endif; ?>
						</div>
						<div class="eyecare-doctor-profiles__bio">
							<?php if ( $eyecare_bio ) : ?>
								<?php echo wp_kses_post( apply_filters( 'the_content', $eyecare_bio ) ); ?>
							<?php elseif ( 'le-nhu-tung' === $eyecare_slug ) : ?>
								<p><a href="<?php echo esc_url( $eyecare_link ); ?>" target="_blank" rel="noopener noreferrer">ThS.BS Lê Như Tùng</a> đồng hành cùng Bệnh viện Mắt Hà Nội – Bắc Ninh với vai trò cố vấn chuyên môn cao cấp. Bác sĩ tham gia tư vấn chuyên môn nhãn khoa, đặc biệt trong đánh giá và điều trị đục thủy tinh thể bằng phẫu thuật Phaco.</p>
								<p>Trước khi chỉ định phẫu thuật, người bệnh cần được thăm khám để đánh giá thủy tinh thể, giác mạc, võng mạc, thần kinh thị giác, nhãn áp và các bệnh lý mắt đi kèm. Phương án điều trị phải phù hợp với tình trạng và nhu cầu thị giác của từng người.</p>
							<?php else : ?>
								<p><?php echo esc_html( $eyecare_name ); ?> là thành viên trong đội ngũ chuyên môn của Bệnh viện Mắt Hà Nội – Bắc Ninh<?php echo ! empty( $eyecare_bac_si['chuc_danh'] ) ? ', hiện được giới thiệu với vai trò ' . esc_html( $eyecare_bac_si['chuc_danh'] ) : ''; ?>.<?php echo ! empty( $eyecare_bac_si['chuyen_khoa'] ) ? ' Lĩnh vực được giới thiệu: ' . esc_html( $eyecare_bac_si['chuyen_khoa'] ) . '.' : ''; ?> Thông tin đào tạo và quá trình công tác chi tiết đang được bệnh viện bổ sung từ hồ sơ đã đối chiếu.</p>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $eyecare_co_noi_dung ) : ?>
		<section id="cam-nang-doi-ngu" class="eyecare-chuyen-khoa__noi-dung eyecare-doi-ngu-page__noi-dung" aria-label="Cẩm nang chọn bác sĩ mắt phù hợp">
			<div class="eyecare-chuyen-khoa__khung eyecare-chuyen-khoa__noi-dung-grid">
				<div class="eyecare-chuyen-khoa__bai eyecare-trang__than">
					<?php the_content(); ?>
				</div>

				<aside class="eyecare-chuyen-khoa__eeat" aria-label="Thông tin cập nhật nội dung">
					<div class="eyecare-chuyen-khoa__eeat-card">
						<p class="eyecare-chuyen-khoa__eeat-label">Cập nhật hồ sơ</p>
						<h2>Đội ngũ bác sĩ</h2>
						<p>Hồ sơ và liên kết cá nhân được quản lý tại mục Đội ngũ bác sĩ trong trang quản trị.</p>
						<dl>
							<div><dt>Cập nhật</dt><dd><?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></dd></div>
						</dl>
						<p class="eyecare-chuyen-khoa__eeat-note">Nội dung giúp người đọc chuẩn bị trước khi khám, không thay thế chẩn đoán và chỉ định trực tiếp.</p>
					</div>
				</aside>
			</div>
		</section>
	<?php endif; ?>
</article>

	<?php
endwhile;

get_footer();
