<?php
/**
 * Khối đội ngũ chuyên môn dùng chung cho các trang giới thiệu bệnh viện.
 *
 * Ảnh poster được tối ưu từ bộ ảnh đã duyệt và lưu trong theme để không phụ
 * thuộc dữ liệu đồng bộ của WordPress. Trang /doi-ngu-bac-si/ vẫn dùng bộ
 * hồ sơ động hiện có trong inc/tac-gia-bac-si.php.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dữ liệu hiển thị theo đúng thứ tự của bản thiết kế được duyệt.
 *
 * @return array{featured:array<string,string>,members:array<int,array<string,string>>}
 */
function eyecare_doi_ngu_trang_chu_du_lieu() {
	return array(
		'featured' => array(
			'name'  => 'ThS.BS Lê Như Tùng',
			'role'  => 'Cố vấn chuyên môn cao cấp và Chủ tịch HĐQT',
			'note'  => 'Hơn 100.000 ca mổ Phaco trên toàn quốc và nước ngoài',
			'image' => 'le-nhu-tung.webp',
		),
		'members'  => array(
			array(
				'name'  => 'BSCKI. Đặng Công Hải',
				'role'  => 'Giám đốc bệnh viện',
				'image' => 'dang-cong-hai.webp',
			),
			array(
				'name'  => 'BSCKI. Bùi Văn Cảnh',
				'role'  => 'Phó giám đốc chuyên môn',
				'image' => 'bui-van-canh.webp',
			),
			array(
				'name'  => 'BSCK. Trần Khánh Thắng',
				'role'  => 'Trưởng khoa khúc xạ',
				'image' => 'tran-khanh-thang.webp',
			),
			array(
				'name'  => 'Cử nhân khúc xạ Trần Đức Thịnh',
				'role'  => 'Chuyên gia khúc xạ',
				'image' => 'tran-duc-thinh.webp',
			),
			array(
				'name'  => 'Cử nhân Nguyễn Đăng Đạt',
				'role'  => 'Trưởng khoa cận lâm sàng',
				'image' => 'nguyen-dang-dat.webp',
			),
		),
	);
}

/**
 * Tạo URL an toàn tới một poster bác sĩ trong theme.
 *
 * @param string $filename Tên tệp đã định nghĩa nội bộ.
 * @return string
 */
function eyecare_doi_ngu_trang_chu_anh_url( $filename ) {
	$filename = sanitize_file_name( $filename );
	$path     = get_stylesheet_directory() . '/assets/doctor-posters/' . $filename;

	if ( ! is_file( $path ) ) {
		return '';
	}

	return get_stylesheet_directory_uri() . '/assets/doctor-posters/' . rawurlencode( $filename );
}

/**
 * In khối đội ngũ theo bố cục 1 cố vấn nổi bật + 5 thành viên.
 *
 * @param int $heading_level Cấp tiêu đề chính, chỉ nhận 1 hoặc 2.
 */
function eyecare_doi_ngu_trang_chu_in( $heading_level = 2 ) {
	$data        = eyecare_doi_ngu_trang_chu_du_lieu();
	$featured    = $data['featured'];
	$members     = $data['members'];
	$hero_url    = eyecare_doi_ngu_trang_chu_anh_url( $featured['image'] );
	$heading_tag = 1 === absint( $heading_level ) ? 'h1' : 'h2';

	if ( '' === $hero_url || empty( $members ) ) {
		return;
	}
	?>
	<section id="doi-ngu-bac-si" class="eyecare-home-team" aria-labelledby="eyecare-home-team-title">
		<div class="eyecare-home-team__inner">
			<header class="eyecare-home-team__header">
				<p class="eyecare-home-team__eyebrow"><span>Chuyên môn tận tâm</span></p>
				<div class="eyecare-home-team__intro">
					<<?php echo esc_html( $heading_tag ); ?> id="eyecare-home-team-title">Đội ngũ chuyên môn đồng hành cùng người bệnh</<?php echo esc_html( $heading_tag ); ?>>
					<p>Mỗi thành viên có nền tảng đào tạo và kinh nghiệm riêng, cùng phối hợp để thăm khám, tư vấn và chăm sóc mắt rõ ràng hơn cho từng người bệnh.</p>
				</div>
			</header>

			<article class="eyecare-home-team__featured">
				<div class="eyecare-home-team__featured-copy">
					<p><?php echo esc_html( $featured['note'] ); ?></p>
					<h3><?php echo esc_html( $featured['role'] ); ?></h3>
					<span><?php echo esc_html( $featured['name'] ); ?></span>
				</div>
				<figure class="eyecare-home-team__featured-media">
					<img src="<?php echo esc_url( $hero_url ); ?>" width="1200" height="1800" loading="lazy" decoding="async" alt="Hồ sơ chuyên môn <?php echo esc_attr( $featured['name'] ); ?>">
				</figure>
			</article>

			<div class="eyecare-home-team__grid" role="list" aria-label="Danh sách đội ngũ chuyên môn" tabindex="0">
				<?php foreach ( $members as $member ) : ?>
					<?php $image_url = eyecare_doi_ngu_trang_chu_anh_url( $member['image'] ); ?>
					<?php if ( '' === $image_url ) { continue; } ?>
					<article class="eyecare-home-team__card" role="listitem">
						<figure class="eyecare-home-team__card-media">
							<img src="<?php echo esc_url( $image_url ); ?>" width="1200" height="1800" loading="lazy" decoding="async" alt="Hồ sơ chuyên môn <?php echo esc_attr( $member['name'] ); ?>">
						</figure>
						<div class="eyecare-home-team__caption">
							<h3><?php echo esc_html( $member['name'] ); ?></h3>
							<p><?php echo esc_html( $member['role'] ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}
