<?php
/**
 * Trang Giới thiệu bệnh viện.
 *
 * Nội dung định danh, liên hệ và đội ngũ được lấy từ nguồn dữ liệu dùng chung
 * của theme. Các thông tin chưa xác minh như số giấy phép hoặc tên thiết bị
 * cụ thể không được tự suy diễn trong template này.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'eyecare_gioi_thieu_icon' ) ) {
	/**
	 * Bộ icon SVG tĩnh cho trang Giới thiệu.
	 *
	 * @param string $key Tên icon.
	 * @return string
	 */
	function eyecare_gioi_thieu_icon( $key ) {
		$icons = array(
			'building' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M8 42h32M12 42V14l12-7 12 7v28M18 20h4v4h-4zM26 20h4v4h-4zM18 29h4v4h-4zM26 29h4v4h-4zM22 42v-6h4v6"/></svg>',
			'eye'      => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M5 24s7-11 19-11 19 11 19 11-7 11-19 11S5 24 5 24Z"/><circle cx="24" cy="24" r="6"/><path d="M24 18c4 2.5 4 9.5 0 12"/></svg>',
			'values'   => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M24 42S7 33 7 19a9 9 0 0 1 17-4 9 9 0 0 1 17 4c0 14-17 23-17 23Z"/><path d="m16 24 5 5 11-12"/></svg>',
			'legal'    => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M13 6h17l7 7v29H13z"/><path d="M30 6v8h7M19 22h12M19 29h12M19 36h8"/><circle cx="34" cy="35" r="6"/><path d="m31.5 35 1.7 1.7 3.4-4"/></svg>',
			'phone'    => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M13 8h8l4 11-5 3c3 6 6 9 12 12l3-5 9 4v6c0 3-2 5-5 5C20 44 4 28 4 9c0-3 2-5 5-5h4Z"/></svg>',
			'clock'    => '<svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="18"/><path d="M24 13v12l8 5"/></svg>',
			'location' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M24 44s14-14 14-25a14 14 0 1 0-28 0c0 11 14 25 14 25Z"/><circle cx="24" cy="19" r="5"/></svg>',
			'check'    => '<svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="18"/><path d="m15 24 6 6 13-14"/></svg>',
		);

		return isset( $icons[ $key ] ) ? $icons[ $key ] : $icons['eye'];
	}
}

get_header();

while ( have_posts() ) :
	the_post();

	$tt          = eyecare_du_lieu_thuc_the();
	$doi_ngu      = function_exists( 'eyecare_du_lieu_doi_ngu' ) ? eyecare_du_lieu_doi_ngu() : array();
	$doi_ngu_hero = array_slice( $doi_ngu, 0, 3 );
	$noi_dung    = trim( get_the_content() );
	$co_noi_dung = '' !== trim( wp_strip_all_tags( strip_shortcodes( $noi_dung ) ) );
	$anh_tam_nhin = function_exists( 'eyecare_anh_giao_dien_lay' ) ? eyecare_anh_giao_dien_lay( 'gioi_thieu_tam_nhin', 'full' ) : null;
	$cac_muc     = array(
		array(
			'slug'  => 'tam-nhin-gia-tri',
			'icon'  => 'values',
			'nhan'  => 'Định hướng',
			'ten'   => 'Tầm nhìn và giá trị',
			'mo_ta' => 'Đặt người bệnh ở trung tâm, trình bày thông tin dễ hiểu và duy trì sự nhất quán trong từng điểm chạm trước, trong và sau buổi khám.',
		),
		array(
			'slug'  => 'co-so-vat-chat',
			'icon'  => 'building',
			'nhan'  => 'Không gian',
			'ten'   => 'Cơ sở vật chất',
			'mo_ta' => 'Không gian tiếp nhận và thăm khám được tổ chức theo hành trình của người bệnh, giúp việc tìm khu vực cần đến rõ ràng và thuận tiện hơn.',
		),
		array(
			'slug'  => 'trang-thiet-bi',
			'icon'  => 'eye',
			'nhan'  => 'Thăm khám',
			'ten'   => 'Trang thiết bị',
			'mo_ta' => 'Thông tin thiết bị chỉ được công bố theo hồ sơ đã xác minh. Người bệnh có thể liên hệ tổng đài để hỏi về phương tiện thăm khám phù hợp với nhu cầu.',
		),
		array(
			'slug'  => 'ho-so-phap-ly',
			'icon'  => 'legal',
			'nhan'  => 'Minh bạch',
			'ten'   => 'Hồ sơ pháp lý',
			'mo_ta' => 'Khu vực tập hợp thông tin pháp nhân và các hồ sơ được phép công khai, giúp người đọc đối chiếu đúng tên đơn vị khi cần.',
		),
	);
	?>

	<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-gioi-thieu' ); ?>>
		<section class="eyecare-page-hero eyecare-page-hero--gioi-thieu" aria-labelledby="gioi-thieu-tieu-de">
			<div class="eyecare-page-hero__khung">
				<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?>
					<div class="eyecare-page-hero__duong-dan"><?php eyecare_duong_dan_in(); ?></div>
				<?php endif; ?>

				<div class="eyecare-page-hero__grid">
					<div class="eyecare-gioi-thieu__hero-copy">
						<p class="eyecare-page-hero__nhan">Về bệnh viện</p>
						<h1 id="gioi-thieu-tieu-de">Chăm sóc thị lực gần hơn, thông tin rõ ràng hơn</h1>
						<p class="eyecare-page-hero__dan"><?php echo esc_html( $tt['ten'] ); ?> cung cấp thông tin và hoạt động thăm khám chuyên khoa Mắt cho người dân tại Bắc Ninh, Bắc Giang và khu vực lân cận. Trang này giúp bạn hiểu nhanh về bệnh viện trước khi đến.</p>
						<div class="eyecare-gioi-thieu__hero-actions">
							<a class="eyecare-gioi-thieu__nut eyecare-gioi-thieu__nut--vang" href="#tong-quan-benh-vien">Khám phá bệnh viện <span aria-hidden="true">↓</span></a>
							<a class="eyecare-gioi-thieu__nut eyecare-gioi-thieu__nut--kinh" href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">Gọi <?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a>
						</div>
					</div>

					<div class="eyecare-gioi-thieu__hero-visual" aria-label="Đại diện đội ngũ bác sĩ chuyên khoa Mắt">
						<div class="eyecare-gioi-thieu__orb" aria-hidden="true"></div>
						<?php if ( $doi_ngu_hero ) : ?>
							<div class="eyecare-gioi-thieu__portraits">
								<?php foreach ( $doi_ngu_hero as $i => $bac_si ) :
									$ten_bac_si = function_exists( 'eyecare_doi_ngu_ten_day_du' ) ? eyecare_doi_ngu_ten_day_du( $bac_si ) : trim( ( $bac_si['hoc_vi'] ?? '' ) . ' ' . ( $bac_si['ho_ten'] ?? '' ) );
									?>
									<figure class="eyecare-gioi-thieu__portrait eyecare-gioi-thieu__portrait--<?php echo esc_attr( (string) ( $i + 1 ) ); ?>">
										<?php if ( ! empty( $bac_si['anh'] ) && wp_get_attachment_image_src( (int) $bac_si['anh'] ) ) : ?>
											<?php echo wp_get_attachment_image( (int) $bac_si['anh'], 'medium', false, array( 'alt' => $ten_bac_si, 'loading' => 0 === $i ? 'eager' : 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php elseif ( ! empty( $bac_si['anh_url'] ) ) : ?>
											<img src="<?php echo esc_url( $bac_si['anh_url'] ); ?>" alt="<?php echo esc_attr( $ten_bac_si ); ?>" <?php echo 0 === $i ? 'loading="eager"' : 'loading="lazy"'; ?> decoding="async">
										<?php endif; ?>
									</figure>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<div class="eyecare-gioi-thieu__eye-art" aria-hidden="true"><?php echo eyecare_gioi_thieu_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<?php endif; ?>
						<div class="eyecare-gioi-thieu__team-note">
							<span aria-hidden="true"><?php echo eyecare_gioi_thieu_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<strong>Đội ngũ chuyên khoa Mắt</strong>
							<small>Hồ sơ được cập nhật từ trang quản trị</small>
						</div>
					</div>
				</div>

				<div class="eyecare-gioi-thieu__quick-info" aria-label="Thông tin nhanh về bệnh viện">
					<a href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">
						<span><?php echo eyecare_gioi_thieu_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<small>Tổng đài</small>
						<strong><?php echo esc_html( $tt['dien_thoai_hien'] ); ?></strong>
					</a>
					<div>
						<span><?php echo eyecare_gioi_thieu_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<small>Giờ mở cửa</small>
						<strong><?php echo esc_html( $tt['gio_mo'] . ' – ' . $tt['gio_dong'] ); ?></strong>
					</div>
					<div>
						<span><?php echo eyecare_gioi_thieu_icon( 'location' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<small>Địa chỉ</small>
						<strong><?php echo esc_html( $tt['phuong'] . ', ' . $tt['tinh'] ); ?></strong>
					</div>
				</div>
			</div>
		</section>

		<section id="tong-quan-benh-vien" class="eyecare-gioi-thieu__tong-quan" aria-labelledby="tong-quan-tieu-de">
			<div class="eyecare-noi-dung__khung eyecare-gioi-thieu__tong-quan-grid">
				<div class="eyecare-gioi-thieu__tong-quan-copy">
					<p class="eyecare-gioi-thieu__eyebrow">Tổng quan</p>
					<h2 id="tong-quan-tieu-de">Một địa chỉ nhãn khoa được tổ chức quanh nhu cầu của người bệnh</h2>
					<p>Bệnh viện tập trung vào chuyên khoa Mắt, từ thăm khám ban đầu, theo dõi thị lực đến hướng dẫn chuẩn bị cho các chỉ định chuyên môn. Thông tin trên website được sắp xếp theo chuyên khoa, dịch vụ, đội ngũ bác sĩ, hỏi đáp và kiến thức nhãn khoa để người đọc tìm đúng nội dung nhanh hơn.</p>
					<p>Trước khi đến, bạn có thể kiểm tra giờ làm việc, địa chỉ, bảng giá đang được công khai và gọi tổng đài để hỏi những giấy tờ cần mang theo. Với từng trường hợp, bác sĩ sẽ đánh giá trực tiếp và giải thích hướng xử trí phù hợp; nội dung trực tuyến không thay thế chẩn đoán tại cơ sở y tế.</p>
				</div>

				<div class="eyecare-gioi-thieu__cam-ket" aria-label="Những điều bệnh viện hướng tới">
					<div><span>01</span><strong>Thông tin dễ hiểu</strong><p>Nội dung được trình bày theo nhu cầu thường gặp, hạn chế thuật ngữ khó hiểu khi không cần thiết.</p></div>
					<div><span>02</span><strong>Chi phí minh bạch</strong><p>Mức giá công khai được quản lý theo danh mục; người bệnh nên xác nhận lại dịch vụ dự kiến trước khi thực hiện.</p></div>
					<div><span>03</span><strong>Thời gian thuận tiện</strong><p>Thông tin chuẩn bị và nơi tiếp nhận được làm rõ để giảm thời gian tìm kiếm khi đến bệnh viện.</p></div>
				</div>
			</div>
		</section>

		<section class="eyecare-gioi-thieu__hanh-trinh" aria-labelledby="hanh-trinh-tieu-de">
			<div class="eyecare-noi-dung__khung">
				<header class="eyecare-gioi-thieu__section-heading">
					<div><p>Hành trình thăm khám</p><h2 id="hanh-trinh-tieu-de">Biết trước từng bước để chủ động hơn</h2></div>
					<span>Quy trình thực tế có thể thay đổi theo tình trạng mắt và chỉ định trực tiếp của nhân viên y tế.</span>
				</header>
				<ol class="eyecare-gioi-thieu__steps">
					<li><span>01</span><div><strong>Tìm thông tin</strong><p>Xem chuyên khoa, giờ làm việc, địa chỉ và nội dung chuẩn bị phù hợp với nhu cầu.</p></div></li>
					<li><span>02</span><div><strong>Liên hệ trước</strong><p>Gọi tổng đài khi cần hỏi về lịch tiếp nhận, giấy tờ hoặc dịch vụ dự kiến.</p></div></li>
					<li><span>03</span><div><strong>Thăm khám trực tiếp</strong><p>Bác sĩ đánh giá tình trạng mắt và giải thích các bước tiếp theo dựa trên kết quả thực tế.</p></div></li>
					<li><span>04</span><div><strong>Theo dõi hướng dẫn</strong><p>Giữ lại kết quả, đơn thuốc và lịch hẹn để thuận tiện cho lần tái khám sau.</p></div></li>
				</ol>
			</div>
		</section>

		<section class="eyecare-gioi-thieu__kham-pha" aria-labelledby="kham-pha-tieu-de">
			<div class="eyecare-noi-dung__khung">
				<header class="eyecare-gioi-thieu__section-heading eyecare-gioi-thieu__section-heading--sang">
					<div><p>Tìm hiểu thêm</p><h2 id="kham-pha-tieu-de">Bốn góc nhìn về bệnh viện</h2></div>
					<span>Chọn nội dung bạn quan tâm. Các khối dưới đây vẫn cung cấp thông tin ngay cả khi trang chi tiết đang được cập nhật.</span>
				</header>

				<div class="eyecare-gioi-thieu__bento">
					<?php foreach ( $cac_muc as $i => $muc ) :
						$trang_con = get_page_by_path( 'gioi-thieu/' . $muc['slug'] );
						$co_trang_con = $trang_con instanceof WP_Post && 'publish' === $trang_con->post_status && '' !== trim( wp_strip_all_tags( strip_shortcodes( $trang_con->post_content ) ) );
						?>
						<article id="gt-<?php echo esc_attr( $muc['slug'] ); ?>" class="eyecare-gioi-thieu__bento-card eyecare-gioi-thieu__bento-card--<?php echo esc_attr( (string) ( $i + 1 ) ); ?>">
							<?php if ( 0 === $i && $anh_tam_nhin ) : ?>
								<img class="eyecare-gioi-thieu__bento-bg" src="<?php echo esc_url( $anh_tam_nhin['url'] ); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
							<?php endif; ?>
							<span class="eyecare-gioi-thieu__bento-icon"><?php echo eyecare_gioi_thieu_icon( $muc['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<p><?php echo esc_html( $muc['nhan'] ); ?></p>
							<h3><?php echo esc_html( $muc['ten'] ); ?></h3>
							<div><?php echo esc_html( $muc['mo_ta'] ); ?></div>
							<?php if ( $co_trang_con ) : ?>
								<a href="<?php echo esc_url( get_permalink( $trang_con->ID ) ); ?>">Xem nội dung chi tiết <span aria-hidden="true">→</span></a>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<?php
		if ( function_exists( 'eyecare_doi_ngu_trang_chu_in' ) ) {
			eyecare_doi_ngu_trang_chu_in();
		}
		?>

		<?php if ( $co_noi_dung ) : ?>
			<section class="eyecare-gioi-thieu__noi-dung" aria-label="Nội dung giới thiệu bổ sung">
				<div class="eyecare-noi-dung__khung eyecare-trang__than"><?php the_content(); ?></div>
			</section>
		<?php endif; ?>

		<section class="eyecare-gioi-thieu__cta" aria-labelledby="gioi-thieu-cta-tieu-de">
			<div class="eyecare-noi-dung__khung eyecare-gioi-thieu__cta-inner">
				<div>
					<p>Chuẩn bị trước khi đến</p>
					<h2 id="gioi-thieu-cta-tieu-de">Cần hỏi thêm thông tin về bệnh viện?</h2>
					<span>Liên hệ tổng đài để xác nhận giờ tiếp nhận, địa chỉ và nội dung cần chuẩn bị phù hợp với nhu cầu của bạn.</span>
				</div>
				<div class="eyecare-gioi-thieu__cta-actions">
					<a class="eyecare-gioi-thieu__nut eyecare-gioi-thieu__nut--vang" href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">Gọi <?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a>
					<a class="eyecare-gioi-thieu__nut eyecare-gioi-thieu__nut--trang" href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">Xem đường đến bệnh viện</a>
				</div>
			</div>
		</section>
	</article>

	<?php
endwhile;

get_footer();
