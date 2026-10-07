<?php
/**
 * Khối đội ngũ chuyên môn dùng chung cho các trang giới thiệu bệnh viện.
 *
 * Ảnh chân dung lưu trong theme để khối giới thiệu không phụ thuộc dữ liệu
 * đồng bộ của WordPress. Hồ sơ chi tiết vẫn lấy từ bản ghi bác sĩ trong Admin.
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
			'slug'  => 'le-nhu-tung',
			'name'  => 'ThS.BS Lê Như Tùng',
			'role'  => 'Cố vấn chuyên môn cao cấp',
			'note'  => 'Đồng hành cùng đội ngũ trong khám và điều trị nhãn khoa',
			'image' => 'doctor-le-nhu-tung.png',
		),
		'members'  => array(
			array(
				'slug'  => 'dang-cong-hai',
				'name'  => 'BSCKI. Đặng Công Hải',
				'role'  => 'Giám đốc bệnh viện',
				'image' => 'doctor-dang-cong-hai.png',
			),
			array(
				'slug'  => 'bui-van-canh',
				'name'  => 'BSCKI. Bùi Văn Cảnh',
				'role'  => 'Phó giám đốc chuyên môn',
				'image' => 'doctor-bui-van-canh.png',
			),
			array(
				'slug'  => 'tran-khanh-thang',
				'name'  => 'BSCK. Trần Khánh Thắng',
				'role'  => 'Trưởng khoa khúc xạ',
				'image' => 'doctor-tran-khanh-thang.jpg',
			),
			array(
				'slug'  => 'tran-duc-thinh',
				'name'  => 'Cử nhân khúc xạ Trần Đức Thịnh',
				'role'  => 'Chuyên gia khúc xạ',
				'image' => 'doctor-tran-duc-thinh.jpg',
			),
			array(
				'slug'  => 'nguyen-dang-dat',
				'name'  => 'Cử nhân Nguyễn Đăng Đạt',
				'role'  => 'Trưởng khoa cận lâm sàng',
				'image' => 'doctor-nguyen-dang-dat.jpg',
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
	foreach ( array( '/assets/doctor-posters/', '/assets/' ) as $directory ) {
		$path = get_stylesheet_directory() . $directory . $filename;
		if ( is_file( $path ) ) {
			return get_stylesheet_directory_uri() . $directory . rawurlencode( $filename );
		}
	}
	return '';
}

/** Responsive WebP variants retain the original as the high-density fallback. */
function eyecare_doi_ngu_trang_chu_anh_srcset( $filename ) {
	$filename = sanitize_file_name( $filename );
	if ( 'webp' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
		return '';
	}
	$base     = pathinfo( $filename, PATHINFO_FILENAME );
	$urls     = array();
	foreach ( array( 400, 800 ) as $width ) {
		$variant = $base . '-' . $width . '.webp';
		$url     = eyecare_doi_ngu_trang_chu_anh_url( $variant );
		if ( $url ) {
			$urls[] = esc_url( $url ) . ' ' . $width . 'w';
		}
	}
	$original = eyecare_doi_ngu_trang_chu_anh_url( $filename );
	if ( $original ) {
		$urls[] = esc_url( $original ) . ' 1200w';
	}
	return implode( ', ', $urls );
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
	if ( function_exists( 'eyecare_du_lieu_doi_ngu' ) ) {
		foreach ( eyecare_du_lieu_doi_ngu() as $doctor ) {
			$slug = function_exists( 'eyecare_bac_si_slug' ) ? eyecare_bac_si_slug( $doctor ) : '';
			if ( 'le-nhu-tung' === $slug ) {
				$featured['name'] = eyecare_doi_ngu_ten_day_du( $doctor );
				$featured['role'] = ! empty( $doctor['chuc_danh'] ) ? $doctor['chuc_danh'] : $featured['role'];
				$featured['doctor_id'] = ! empty( $doctor['post_id'] ) ? (int) $doctor['post_id'] : 0;
			}
			foreach ( $members as &$member ) {
				if ( $slug === $member['slug'] ) {
					$member['name'] = eyecare_doi_ngu_ten_day_du( $doctor );
					$member['role'] = ! empty( $doctor['chuc_danh'] ) ? $doctor['chuc_danh'] : $member['role'];
					$member['doctor_id'] = ! empty( $doctor['post_id'] ) ? (int) $doctor['post_id'] : 0;
				}
			}
			unset( $member );
		}
	}
	$featured_link = function_exists( 'eyecare_bac_si_trang_ca_nhan_url' ) ? eyecare_bac_si_trang_ca_nhan_url( ! empty( $featured['doctor_id'] ) ? $featured['doctor_id'] : $featured['name'] ) : home_url( '/doi-ngu-bac-si/' );
	$featured_external = false !== strpos( $featured_link, 'facebook.com' );
	$hero_url    = eyecare_doi_ngu_trang_chu_anh_url( $featured['image'] );
	$hero_small  = eyecare_doi_ngu_trang_chu_anh_url( pathinfo( $featured['image'], PATHINFO_FILENAME ) . '-400.webp' );
	$hero_srcset = eyecare_doi_ngu_trang_chu_anh_srcset( $featured['image'] );
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
					<span><a href="<?php echo esc_url( $featured_link ); ?>"<?php echo $featured_external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $featured['name'] ); ?></a></span>
				</div>
				<figure class="eyecare-home-team__featured-media">
					<a href="<?php echo esc_url( $featured_link ); ?>"<?php echo $featured_external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> aria-label="Xem hồ sơ <?php echo esc_attr( $featured['name'] ); ?>"><img src="<?php echo esc_url( $hero_small ?: $hero_url ); ?>" srcset="<?php echo esc_attr( $hero_srcset ); ?>" sizes="(min-width: 1025px) 340px, (min-width: 701px) 310px, 330px" width="400" height="600" loading="lazy" decoding="async" alt="Hồ sơ chuyên môn <?php echo esc_attr( $featured['name'] ); ?>"></a>
				</figure>
			</article>

			<div class="eyecare-home-team__grid" role="group" aria-label="Danh sách đội ngũ chuyên môn" tabindex="0">
				<?php foreach ( $members as $member ) : ?>
					<?php $image_url = eyecare_doi_ngu_trang_chu_anh_url( $member['image'] ); ?>
					<?php $image_small = eyecare_doi_ngu_trang_chu_anh_url( pathinfo( $member['image'], PATHINFO_FILENAME ) . '-400.webp' ); ?>
					<?php $member_link = function_exists( 'eyecare_bac_si_trang_ca_nhan_url' ) ? eyecare_bac_si_trang_ca_nhan_url( ! empty( $member['doctor_id'] ) ? $member['doctor_id'] : $member['name'] ) : home_url( '/doi-ngu-bac-si/' ); ?>
					<?php $member_external = false !== strpos( $member_link, 'facebook.com' ); ?>
					<?php if ( '' === $image_url ) { continue; } ?>
					<article class="eyecare-home-team__card">
						<figure class="eyecare-home-team__card-media">
							<a href="<?php echo esc_url( $member_link ); ?>"<?php echo $member_external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> aria-label="Xem hồ sơ <?php echo esc_attr( $member['name'] ); ?>"><img src="<?php echo esc_url( $image_small ?: $image_url ); ?>" srcset="<?php echo esc_attr( eyecare_doi_ngu_trang_chu_anh_srcset( $member['image'] ) ); ?>" sizes="(min-width: 1025px) 200px, (min-width: 701px) 30vw, 45vw" width="400" height="600" loading="lazy" decoding="async" alt="Hồ sơ chuyên môn <?php echo esc_attr( $member['name'] ); ?>"></a>
						</figure>
						<div class="eyecare-home-team__caption">
							<h3><a href="<?php echo esc_url( $member_link ); ?>"<?php echo $member_external ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $member['name'] ); ?></a></h3>
							<p><?php echo esc_html( $member['role'] ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}
