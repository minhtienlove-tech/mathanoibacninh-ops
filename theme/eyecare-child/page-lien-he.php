<?php
/**
 * Trang liên hệ.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$tt       = eyecare_du_lieu_thuc_the();
	$noi_dung = trim( wp_strip_all_tags( strip_shortcodes( get_the_content() ) ) );
	$anh_lien_he = function_exists( 'eyecare_anh_giao_dien_lay_nhieu' )
		? eyecare_anh_giao_dien_lay_nhieu( array( 'lien_he_kinh_1', 'lien_he_kinh_2', 'lien_he_kinh_3', 'lien_he_kinh_4' ), 'large' )
		: array();
	$nhan_anh_lien_he = array(
		'lien_he_kinh_1' => array( 'eyebrow' => 'Mặt tiền bệnh viện', 'label' => 'Nhận diện đúng địa chỉ trước khi khởi hành' ),
		'lien_he_kinh_2' => array( 'eyebrow' => 'Không gian tiếp đón', 'label' => 'Người bệnh được hướng dẫn ngay từ cửa vào' ),
		'lien_he_kinh_3' => array( 'eyebrow' => 'Khu chờ và kính mắt', 'label' => 'Không gian rõ ràng, thuận tiện khi thăm khám' ),
		'lien_he_kinh_4' => array( 'eyebrow' => 'Không gian nội thất', 'label' => 'Trải nghiệm thăm khám gọn gàng và dễ định hướng' ),
	);

	$anh_dau_key      = isset( $anh_lien_he[0]['key'] ) ? (string) $anh_lien_he[0]['key'] : '';
	$nhan_dau_lien_he = isset( $nhan_anh_lien_he[ $anh_dau_key ] )
		? $nhan_anh_lien_he[ $anh_dau_key ]
		: array( 'eyebrow' => 'Không gian bệnh viện', 'label' => 'Rõ ràng trước khi đến bệnh viện' );
	?>

<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-lien-he-page' ); ?>>
	<section class="eyecare-page-hero eyecare-page-hero--lien-he" aria-labelledby="lien-he-tieu-de">
		<div class="eyecare-page-hero__khung">
			<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?><div class="eyecare-page-hero__duong-dan"><?php eyecare_duong_dan_in(); ?></div><?php endif; ?>
			<div class="eyecare-page-hero__grid">
				<div>
					<p class="eyecare-page-hero__nhan">Kết nối chăm sóc thị giác</p>
					<h1 id="lien-he-tieu-de">Nâng niu đôi mắt.<br><em>Sáng một tương lai.</em></h1>
					<p class="eyecare-page-hero__dan">Gọi tổng đài, xem giờ làm việc và tìm đường đến bệnh viện trước khi khởi hành. Chúng tôi luôn sẵn sàng lắng nghe và hướng dẫn bước tiếp theo.</p>
					<div class="eyecare-contact-actions">
						<a class="eyecare-lien-he-page__goi-ngay" href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">
							<span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h3l2 5-2 1.5a15 15 0 0 0 4.5 4.5L16 12l5 2v3c0 2.2-1.8 4-4 4C9.3 21 3 14.7 3 7c0-2.2 1.8-4 4-4Z"/></svg></span>
							<b><small>Tổng đài</small><?php echo esc_html( $tt['dien_thoai_hien'] ); ?></b>
						</a>
						<a class="eyecare-lien-he-page__xem-thong-tin" href="#thong-tin-lien-he-tieu-de"><span aria-hidden="true">↘</span> Thông tin bệnh viện</a>
					</div>
				</div>
					<div class="eyecare-lien-he-eye" aria-label="Không gian bệnh viện trong hiệu ứng kính mắt" data-eyecare-contact-eye>
					<div class="eyecare-lien-he-eye__halo" aria-hidden="true"></div>
					<div class="eyecare-lien-he-eye__orbit eyecare-lien-he-eye__orbit--outer" aria-hidden="true"><i></i><i></i><i></i></div>
					<div class="eyecare-lien-he-eye__orbit eyecare-lien-he-eye__orbit--inner" aria-hidden="true"></div>
					<div class="eyecare-lien-he-eye__frame">
						<?php if ( empty( $anh_lien_he ) ) : ?>
							<div class="eyecare-lien-he-eye__placeholder">
								<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M6 32s9.5-16 26-16 26 16 26 16-9.5 16-26 16S6 32 6 32Z"/><circle cx="32" cy="32" r="9"/><path d="M32 23c6 3.5 6 14.5 0 18"/></svg>
								<span>Chọn ảnh tại Giao diện → Ảnh giao diện</span>
							</div>
						<?php endif; ?>
						<?php foreach ( $anh_lien_he as $index => $anh ) : ?>
							<?php
							$anh_key = isset( $anh['key'] ) ? (string) $anh['key'] : '';
							$copy    = isset( $nhan_anh_lien_he[ $anh_key ] ) ? $nhan_anh_lien_he[ $anh_key ] : array( 'eyebrow' => 'Không gian bệnh viện', 'label' => 'Rõ ràng trước khi đến bệnh viện' );
							?>
							<img
								class="eyecare-lien-he-eye__slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
								src="<?php echo esc_url( $anh['url'] ); ?>"
								alt="<?php echo esc_attr( $anh['alt'] ); ?>"
								width="<?php echo esc_attr( (string) $anh['width'] ); ?>"
								height="<?php echo esc_attr( (string) $anh['height'] ); ?>"
								<?php echo 0 === $index ? 'loading="eager"' : 'loading="lazy"'; ?>
								decoding="async"
								data-eyecare-contact-eye-eyebrow="<?php echo esc_attr( $copy['eyebrow'] ); ?>"
								data-eyecare-contact-eye-label-text="<?php echo esc_attr( $copy['label'] ); ?>"
								data-eyecare-contact-eye-slide>
						<?php endforeach; ?>
						<span class="eyecare-lien-he-eye__lid eyecare-lien-he-eye__lid--top" aria-hidden="true"></span>
						<span class="eyecare-lien-he-eye__lid eyecare-lien-he-eye__lid--bottom" aria-hidden="true"></span>
						<span class="eyecare-lien-he-eye__scan" aria-hidden="true"></span>
					</div>
					<div class="eyecare-lien-he-eye__pupil" aria-hidden="true"></div>
					<div class="eyecare-lien-he-eye__label" aria-live="polite">
						<small data-eyecare-contact-eye-eyebrow><?php echo esc_html( $nhan_dau_lien_he['eyebrow'] ); ?></small>
						<strong data-eyecare-contact-eye-label><?php echo esc_html( $nhan_dau_lien_he['label'] ); ?></strong>
					</div>
					<span class="eyecare-lien-he-eye__badge"><strong><?php echo esc_html( $tt['gio_mo'] ); ?></strong> mở cửa mỗi ngày</span>
					<button class="eyecare-lien-he-eye__toggle" type="button" aria-pressed="false" aria-label="Tạm dừng hoặc tiếp tục hiệu ứng kính mắt" data-eyecare-contact-eye-toggle><span>Tạm dừng chuyển động</span></button>
				</div>
			</div>
			<div class="eyecare-contact-quick" aria-label="Thông tin nhanh">
				<div><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 21s7-7 7-12a7 7 0 1 0-14 0c0 5 7 12 7 12Z"/><circle cx="12" cy="9" r="2"/></svg></span><p><small>Địa chỉ</small><strong><?php echo esc_html( $tt['dia_chi'] . ', ' . $tt['phuong'] ); ?></strong></p></div>
				<div><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><p><small>Giờ làm việc</small><strong><?php echo esc_html( $tt['gio_mo'] . ' – ' . $tt['gio_dong'] ); ?>, tất cả các ngày</strong></p></div>
				<div><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg></span><p><small>Website</small><strong><?php echo esc_html( wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ); ?></strong></p></div>
			</div>
		</div>
	</section>

	<section class="eyecare-lien-he-page__thong-tin" aria-labelledby="thong-tin-lien-he-tieu-de">
		<div class="eyecare-noi-dung__khung">
			<header class="eyecare-section-heading"><div><p>Thông tin chính thức</p><h2 id="thong-tin-lien-he-tieu-de">Liên hệ và đường đến bệnh viện</h2></div><span>Dữ liệu này được quản lý tập trung và cập nhật đồng thời trên toàn website.</span></header>

			<div class="eyecare-lien-he-page__cards">
				<a href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h3l2 5-2 1.5a15 15 0 0 0 4.5 4.5L16 12l5 2v3c0 2.2-1.8 4-4 4C9.3 21 3 14.7 3 7c0-2.2 1.8-4 4-4Z"/></svg></span><small>Tổng đài</small><strong><?php echo esc_html( $tt['dien_thoai_hien'] ); ?></strong><em>Bấm để gọi ngay</em></a>
				<div><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><small>Giờ làm việc</small><strong><?php echo esc_html( $tt['gio_mo'] . ' – ' . $tt['gio_dong'] ); ?></strong><em>Tất cả các ngày trong tuần</em></div>
				<div><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 21s7-7 7-12a7 7 0 1 0-14 0c0 5 7 12 7 12Z"/><circle cx="12" cy="9" r="2"/></svg></span><small>Địa chỉ</small><strong><?php echo esc_html( $tt['dia_chi'] ); ?></strong><em><?php echo esc_html( $tt['phuong'] . ', ' . $tt['tinh'] ); ?></em></div>
			</div>

			<div class="eyecare-lien-he-page__ban-do-grid">
				<div class="eyecare-lien-he-page__dia-chi">
					<p class="eyecare-lien-he-page__eyebrow">Địa điểm tiếp nhận</p>
					<h2><?php echo esc_html( $tt['ten'] ); ?></h2>
					<address><?php echo esc_html( $tt['dia_chi'] ); ?><br><?php echo esc_html( $tt['phuong'] . ', ' . $tt['tinh'] ); ?></address>
					<p>Mở cửa <?php echo esc_html( $tt['gio_mo'] . ' – ' . $tt['gio_dong'] ); ?>, tất cả các ngày trong tuần.</p>
					<a class="eyecare-nut eyecare-nut--chinh" href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">Gọi trước khi đến</a>
				</div>
				<?php if ( ! empty( $tt['map_embed'] ) ) : ?>
					<div class="eyecare-lien-he-page__ban-do">
						<iframe src="<?php echo esc_url( $tt['map_embed'] ); ?>" title="Bản đồ <?php echo esc_attr( $tt['ten'] ); ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( '' !== $noi_dung ) : ?><section class="eyecare-lien-he-page__noi-dung"><div class="eyecare-noi-dung__khung eyecare-trang__than"><?php the_content(); ?></div></section><?php endif; ?>

	<section class="eyecare-lien-he-page__chuan-bi" aria-labelledby="chuan-bi-tieu-de">
		<div class="eyecare-noi-dung__khung">
			<header class="eyecare-section-heading"><div><p>Trước khi đến</p><h2 id="chuan-bi-tieu-de">Chuẩn bị để buổi khám thuận tiện hơn</h2></div></header>
			<ul>
				<li><span>01</span><div><strong>Mang kính đang đeo</strong><p>Kèm đơn kính cũ nếu có.</p></div></li>
				<li><span>02</span><div><strong>Mang hồ sơ khám cũ</strong><p>Đơn thuốc, giấy ra viện hoặc kết quả khám mắt trước đây.</p></div></li>
				<li><span>03</span><div><strong>Thông báo kính áp tròng</strong><p>Nên tháo trước khi đến và hỏi tổng đài nếu cần hướng dẫn riêng.</p></div></li>
				<li><span>04</span><div><strong>Chủ động người đi cùng</strong><p>Hữu ích khi có chỉ định nhỏ thuốc giãn đồng tử làm nhìn mờ, chói tạm thời.</p></div></li>
			</ul>
			<p class="eyecare-lien-he-page__khan"><strong>Cần khám sớm:</strong> Đột ngột mất thị lực, đau mắt dữ dội, thấy chớp sáng hoặc màn đen che một phần tầm nhìn cần được đánh giá trực tiếp tại cơ sở khám mắt gần nhất, không chờ tư vấn trực tuyến.</p>
		</div>
	</section>

	<section class="eyecare-lien-he-page__phap-nhan"><div class="eyecare-noi-dung__khung"><p><span>Tên pháp nhân</span><strong><?php echo esc_html( $tt['phap_nhan'] ); ?></strong></p><p><span>Mã số thuế</span><strong><?php echo esc_html( $tt['mst'] ); ?></strong></p></div></section>

	<?php get_template_part( 'template-parts/noi-dung-lien-he-seo', null, array( 'thuc_the' => $tt ) ); ?>
</article>

	<?php
endwhile;

get_footer();
