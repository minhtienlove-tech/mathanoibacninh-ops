<?php
/**
 * Chân trang và bản đồ — khung trang tự dựng của eyecare-child.
 *
 * Dữ liệu tên, địa chỉ, hotline và giờ mở cửa lấy từ thực thể y tế dùng chung
 * của theme để tránh mỗi nơi hiển thị một phiên bản khác nhau.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyecare_tt                = eyecare_du_lieu_thuc_the();
$eyecare_dia_chi           = eyecare_dia_chi_day_du();
$eyecare_maps_embed        = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1105.3745839551725!2d106.20423332026398!3d21.270592741388416!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x313509eacd610853%3A0x354f69b583ef6a07!2zQuG7h25oIFZp4buHViBN4bqvdCBIw6AgTuG7mWkgLSBC4bqvYyBOaW5o!5e0!3m2!1svi!2s!4v1786318023452!5m2!1svi!2s';
$eyecare_maps_query        = rawurlencode( $eyecare_tt['ten'] . ' ' . $eyecare_dia_chi );
$eyecare_maps_search       = 'https://www.google.com/maps/search/?api=1&query=' . $eyecare_maps_query;
$eyecare_maps_directions   = 'https://www.google.com/maps/dir/?api=1&destination=' . $eyecare_maps_query;
$eyecare_privacy_url       = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
$eyecare_facebook_url      = ! empty( $eyecare_tt['same_as'][0] ) ? $eyecare_tt['same_as'][0] : '';
$eyecare_mang_xa_hoi       = array(
	array(
		'ten'    => 'Facebook',
		'url'    => $eyecare_facebook_url,
		'icon'   => 'facebook',
		'lop'    => 'facebook',
	),
	array(
		'ten'    => 'X',
		'url'    => 'https://x.com/BenhVienMatHNBN',
		'icon'   => 'twitter-x',
		'lop'    => 'x',
	),
	array(
		'ten'    => 'Pinterest',
		'url'    => 'https://www.pinterest.com/mathanoibacninh/',
		'icon'   => 'pinterest',
		'lop'    => 'pinterest',
	),
	array(
		'ten'    => 'YouTube',
		'url'    => 'https://www.youtube.com/@BenhVienMatHNBN',
		'icon'   => 'youtube',
		'lop'    => 'youtube',
	),
	array(
		'ten'    => 'Instagram',
		'url'    => 'https://www.instagram.com/mathanoibacninh/',
		'icon'   => 'instagram',
		'lop'    => 'instagram',
	),
	array(
		'ten'    => 'TikTok',
		'url'    => 'https://www.tiktok.com/@bnh.vin.mt.h.ni.b',
		'icon'   => 'tiktok',
		'lop'    => 'tiktok',
	),
);
$eyecare_anh_bac_si_tung   = function_exists( 'eyecare_bac_si_anh_tac_gia' ) ? eyecare_bac_si_anh_tac_gia() : '';
?>

</main><?php /* Mở ở header.php */ ?>

<section class="eyecare-ban-do" aria-labelledby="eyecare-ban-do-tieu-de">
	<div class="eyecare-ban-do__hoa-tiet" aria-hidden="true"></div>
	<div class="eyecare-khung-trang eyecare-ban-do__trong">
		<header class="eyecare-ban-do__dau">
			<div>
				<p class="eyecare-ban-do__nhan"><span aria-hidden="true">•</span> Tìm đường</p>
				<h2 id="eyecare-ban-do-tieu-de">Đến bệnh viện thuận tiện hơn</h2>
				<p class="eyecare-ban-do__mo-ta">Xem vị trí, giờ mở cửa và nhận chỉ đường đến Bệnh viện Mắt Hà Nội – Bắc Ninh.</p>
			</div>
			<div class="eyecare-ban-do__hanh-dong">
				<a class="eyecare-nut eyecare-nut--phu" href="<?php echo esc_url( $eyecare_maps_search ); ?>" target="_blank" rel="noopener noreferrer">
					<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
					Mở Google Maps
				</a>
				<a class="eyecare-nut eyecare-nut--chinh" href="<?php echo esc_url( $eyecare_maps_directions ); ?>" target="_blank" rel="noopener noreferrer">
					<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg>
					Chỉ đường
				</a>
			</div>
		</header>

		<div class="eyecare-ban-do__ban-do">
			<div class="eyecare-ban-do__khung-iframe">
				<iframe
					src="<?php echo esc_url( $eyecare_maps_embed ); ?>"
					width="600"
					height="450"
					style="border:0;"
					allowfullscreen=""
					loading="lazy"
					referrerpolicy="strict-origin-when-cross-origin"
					title="Bản đồ Bệnh viện Mắt Hà Nội – Bắc Ninh"></iframe>
			</div>

			<aside class="eyecare-ban-do__the-thong-tin" aria-label="Thông tin bệnh viện">
				<div class="eyecare-ban-do__the-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
				</div>
				<div class="eyecare-ban-do__the-noi-dung">
					<h3><?php echo esc_html( $eyecare_tt['ten'] ); ?></h3>
					<div class="eyecare-ban-do__dong">
						<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M12 22s7-7 7-12a7 7 0 1 0-14 0c0 5 7 12 7 12z"></path><circle cx="12" cy="10" r="3"></circle></svg>
						<address><?php echo esc_html( $eyecare_dia_chi ); ?></address>
					</div>
					<div class="eyecare-ban-do__dong">
						<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
						<span><?php echo esc_html( $eyecare_tt['gio_mo'] . ' – ' . $eyecare_tt['gio_dong'] ); ?> · Tất cả các ngày</span>
					</div>
					<a class="eyecare-nut eyecare-nut--vang" href="tel:<?php echo esc_attr( $eyecare_tt['dien_thoai'] ); ?>">
						<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 3.1 6.2 2 2 0 0 1 5 4h3a2 2 0 0 1 2 1.7l.5 3a2 2 0 0 1-.6 1.7l-1.4 1.4a16 16 0 0 0 6 6l1.4-1.4a2 2 0 0 1 1.7-.6l3 .5A2 2 0 0 1 22 16.9z"></path></svg>
						Gọi <?php echo esc_html( $eyecare_tt['dien_thoai_hien'] ); ?>
					</a>
				</div>
			</aside>
		</div>

		<div class="eyecare-ban-do__tien-ich" aria-label="Thông tin nhanh">
			<div class="eyecare-ban-do__tien-ich-item">
				<span class="eyecare-ban-do__tien-ich-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4l3 3"></path></svg></span>
				<span><small>Giờ hoạt động</small><strong><?php echo esc_html( $eyecare_tt['gio_mo'] . ' – ' . $eyecare_tt['gio_dong'] ); ?></strong></span>
			</div>
			<div class="eyecare-ban-do__tien-ich-item">
				<span class="eyecare-ban-do__tien-ich-icon eyecare-ban-do__tien-ich-icon--vang" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 22s7-7 7-12a7 7 0 1 0-14 0c0 5 7 12 7 12z"></path><circle cx="12" cy="10" r="3"></circle></svg></span>
				<span><small>Địa chỉ hiện hành</small><strong>Phường Bắc Giang, Tỉnh Bắc Ninh</strong></span>
			</div>
			<div class="eyecare-ban-do__tien-ich-item">
				<span class="eyecare-ban-do__tien-ich-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 12h14M12 5l7 7-7 7"></path></svg></span>
				<span><small>Đi nhanh hơn</small><strong>Nhấn “Chỉ đường” để bắt đầu</strong></span>
			</div>
		</div>
	</div>
</section>

<footer class="eyecare-chan">
	<div class="eyecare-chan__song" aria-hidden="true">
		<svg viewBox="0 0 1440 90" preserveAspectRatio="none" focusable="false"><path d="M0 0h1440v50c-178-59-278-50-470-4-180 43-302 42-488 1C302 7 180 8 0 62Z"></path></svg>
	</div>
	<div class="eyecare-chan__hoa-tiet" aria-hidden="true"></div>
	<div class="eyecare-khung-trang eyecare-chan__trong">
		<div class="eyecare-chan__luoi">
			<div class="eyecare-chan__cot eyecare-chan__cot--thuong-hieu">
				<div class="eyecare-chan__thuong-hieu">
					<div class="eyecare-chan__logo-wrap">
						<img class="eyecare-chan__logo" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/logo-benhvien.webp' ); ?>" width="1024" height="765" alt="<?php echo esc_attr( $eyecare_tt['ten'] ); ?>">
					</div>
					<div>
						<p class="eyecare-chan__ten"><?php echo esc_html( $eyecare_tt['ten'] ); ?></p>
						<p class="eyecare-chan__tagline">Chăm sóc đôi mắt bằng chuyên môn và sự tận tâm.</p>
					</div>
				</div>

				<div class="eyecare-chan__thong-tin">
					<p><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M12 22s7-7 7-12a7 7 0 1 0-14 0c0 5 7 12 7 12z"></path><circle cx="12" cy="10" r="3"></circle></svg><span><?php echo esc_html( $eyecare_dia_chi ); ?></span></p>
					<p><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 3.1 6.2 2 2 0 0 1 5 4h3a2 2 0 0 1 2 1.7l.5 3a2 2 0 0 1-.6 1.7l-1.4 1.4a16 16 0 0 0 6 6l1.4-1.4a2 2 0 0 1 1.7-.6l3 .5A2 2 0 0 1 22 16.9z"></path></svg><a href="tel:<?php echo esc_attr( $eyecare_tt['dien_thoai'] ); ?>"><?php echo esc_html( $eyecare_tt['dien_thoai_hien'] ); ?></a></p>
					<p><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg><span>Mở cửa <?php echo esc_html( $eyecare_tt['gio_mo'] . ' – ' . $eyecare_tt['gio_dong'] ); ?>, tất cả các ngày</span></p>
				</div>

				<div class="eyecare-chan__lien-he">
					<a class="eyecare-nut eyecare-nut--vang" href="tel:<?php echo esc_attr( $eyecare_tt['dien_thoai'] ); ?>"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 3.1 6.2 2 2 0 0 1 5 4h3a2 2 0 0 1 2 1.7l.5 3a2 2 0 0 1-.6 1.7l-1.4 1.4a16 16 0 0 0 6 6l1.4-1.4a2 2 0 0 1 1.7-.6l3 .5A2 2 0 0 1 22 16.9z"></path></svg>Gọi tổng đài</a>
					<div class="eyecare-chan__mang-xa-hoi-list" aria-label="Mạng xã hội của bệnh viện">
						<?php foreach ( $eyecare_mang_xa_hoi as $eyecare_mang ) : ?>
							<?php if ( empty( $eyecare_mang['url'] ) ) : continue; endif; ?>
							<a class="eyecare-chan__mang-xa-hoi eyecare-chan__mang-xa-hoi--<?php echo esc_attr( $eyecare_mang['lop'] ); ?>" href="<?php echo esc_url( $eyecare_mang['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $eyecare_mang['ten'] . ' của bệnh viện' ); ?>" title="<?php echo esc_attr( $eyecare_mang['ten'] ); ?>">
								<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/social-icons/' . $eyecare_mang['icon'] . '.svg' ); ?>" width="22" height="22" alt="" aria-hidden="true" loading="lazy" decoding="async">
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<nav class="eyecare-chan__cot" aria-label="Điều hướng chân trang">
				<h2 class="eyecare-chan__td">Nội dung chính</h2>
				<ul class="eyecare-chan__ds">
					<?php foreach ( eyecare_menu_muc() as $muc ) : ?>
						<li><a href="<?php echo esc_url( home_url( $muc['duong_dan'] ) ); ?>"><span aria-hidden="true">›</span><?php echo esc_html( $muc['nhan'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<div class="eyecare-chan__cot eyecare-chan__cot--noi-dung">
				<h2 class="eyecare-chan__td">Thông tin trên trang</h2>
				<p class="eyecare-chan__mo-ta">Nội dung y khoa được biên soạn để người đọc chuẩn bị tốt hơn trước khi đi khám mắt.</p>
				<div class="eyecare-chan__tac-gia">
					<div class="eyecare-chan__tac-gia-avatar" aria-hidden="true">
						<?php if ( $eyecare_anh_bac_si_tung ) : ?>
							<img src="<?php echo esc_url( $eyecare_anh_bac_si_tung ); ?>" alt="" loading="lazy" decoding="async">
						<?php else : ?>
							LT
						<?php endif; ?>
						<span>✓</span>
					</div>
					<div><strong><?php echo esc_html( eyecare_bac_si_ten_day_du() ); ?></strong><small>Người đứng tên nội dung y khoa</small><em>Đã xác thực chuyên môn</em></div>
				</div>
				<div class="eyecare-chan__luu-y">
					<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h16.9a2 2 0 0 0 1.7-3l-8.5-14.1a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4M12 17h.01"></path></svg>
					<p>Thông tin chỉ mang tính tham khảo, không thay thế chẩn đoán và chỉ định trực tiếp của bác sĩ.</p>
				</div>
			</div>
		</div>

		<p class="eyecare-chan__khan">Nếu bạn <strong>đột ngột mất thị lực, đau mắt dữ dội, thấy chớp sáng hoặc màn đen che một phần tầm nhìn</strong>, hãy đến cơ sở khám mắt gần nhất ngay hôm nay, không chờ đặt lịch.</p>

		<div class="eyecare-chan__day">
			<p><?php printf( esc_html__( '© %1$s %2$s', 'eyecare-child' ), esc_html( gmdate( 'Y' ) ), esc_html( $eyecare_tt['phap_nhan'] ) ); ?> · Mã số thuế <?php echo esc_html( $eyecare_tt['mst'] ); ?></p>
			<nav class="eyecare-chan__phap-ly" aria-label="Liên kết pháp lý">
				<?php if ( $eyecare_privacy_url ) : ?><a href="<?php echo esc_url( $eyecare_privacy_url ); ?>">Chính sách bảo mật</a><span aria-hidden="true"></span><?php endif; ?>
				<a href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>">Sơ đồ trang</a>
			</nav>
		</div>
	</div>
</footer>

<?php
/* Menu mobile vẫn mở được ngay cả khi JavaScript chưa tải xong. */
?>
<script>
( function () {
	var nut = document.querySelector( '.eyecare-menu-nut' );
	var menu = document.getElementById( 'menu-chinh' );

	if ( ! nut || ! menu ) {
		return;
	}

	var man_hinh_hep = window.matchMedia( '(max-width: 1110px)' );

	function dong_theo_be_ngang() {
		menu.hidden = man_hinh_hep.matches;
		nut.setAttribute( 'aria-expanded', String( ! menu.hidden ) );
	}

	nut.addEventListener( 'click', function () {
		menu.hidden = ! menu.hidden;
		nut.setAttribute( 'aria-expanded', String( ! menu.hidden ) );
	} );

	man_hinh_hep.addEventListener( 'change', dong_theo_be_ngang );
	dong_theo_be_ngang();
}() );

( function () {
	var toggles = document.querySelectorAll( '.eyecare-menu__toggle' );
	if ( ! toggles.length ) {
		return;
	}

	function dong_tat_ca(ngoai_tru) {
		toggles.forEach( function (toggle) {
			var muc = toggle.closest( '.eyecare-menu__muc--co-con' );
			var con = document.getElementById( toggle.getAttribute( 'aria-controls' ) );
			if ( ! muc || ! con || muc === ngoai_tru ) {
				return;
			}
			muc.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			toggle.setAttribute( 'aria-label', 'Mở submenu Dịch vụ' );
			con.hidden = true;
		} );
	}

	toggles.forEach( function (toggle) {
		toggle.addEventListener( 'click', function (event) {
			event.preventDefault();
			var muc = toggle.closest( '.eyecare-menu__muc--co-con' );
			var con = document.getElementById( toggle.getAttribute( 'aria-controls' ) );
			if ( ! muc || ! con ) {
				return;
			}
			var dang_mo = 'true' === toggle.getAttribute( 'aria-expanded' );
			dong_tat_ca( muc );
			muc.classList.toggle( 'is-open', ! dang_mo );
			toggle.setAttribute( 'aria-expanded', String( ! dang_mo ) );
			toggle.setAttribute( 'aria-label', ( ! dang_mo ? 'Đóng' : 'Mở' ) + ' submenu Dịch vụ' );
			con.hidden = dang_mo;
		} );
	} );

	document.addEventListener( 'click', function (event) {
		if ( ! event.target.closest( '.eyecare-menu__muc--co-con' ) ) {
			dong_tat_ca( null );
		}
	} );

	document.addEventListener( 'keydown', function (event) {
		if ( 'Escape' === event.key ) {
			dong_tat_ca( null );
		}
	} );
}() );
</script>

<?php wp_footer(); ?>

</body>
</html>
