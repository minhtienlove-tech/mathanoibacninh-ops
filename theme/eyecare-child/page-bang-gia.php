<?php
/**
 * Trang tra cứu bảng giá dịch vụ.
 *
 * Dữ liệu lấy từ mục Bảng giá dịch vụ trong WordPress Admin. Công tắc hiển thị
 * số tiền nằm tại Bảng giá dịch vụ → Cài đặt hiển thị.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$tt        = eyecare_du_lieu_thuc_the();
	$posts_gia = function_exists( 'eyecare_bang_gia_lay_danh_sach' ) ? eyecare_bang_gia_lay_danh_sach() : array();
	$hien_gia  = '1' === get_option( 'eyecare_bang_gia_hien_gia', '0' );
	$ghi_chu   = trim( (string) get_option( 'eyecare_bang_gia_ghi_chu', '' ) );
	$nhom      = array();

	foreach ( $posts_gia as $dich_vu ) {
		$ten_nhom = trim( (string) get_post_meta( $dich_vu->ID, '_eyecare_gia_nhom', true ) );
		if ( '' === $ten_nhom ) {
			$ten_nhom = 'Dịch vụ khác';
		}

		$nhom_thu_tu = max( 1, absint( get_post_meta( $dich_vu->ID, '_eyecare_gia_nhom_thu_tu', true ) ) );
		$thu_tu      = max( 1, absint( get_post_meta( $dich_vu->ID, '_eyecare_gia_thu_tu', true ) ) );

		if ( ! isset( $nhom[ $ten_nhom ] ) ) {
			$nhom[ $ten_nhom ] = array( 'thu_tu' => $nhom_thu_tu, 'dich_vu' => array() );
		}

		$nhom[ $ten_nhom ]['dich_vu'][] = array(
			'post'    => $dich_vu,
			'don_vi'  => (string) get_post_meta( $dich_vu->ID, '_eyecare_gia_don_vi', true ),
			'gia'     => absint( get_post_meta( $dich_vu->ID, '_eyecare_gia_muc', true ) ),
			'thu_tu'  => $thu_tu,
		);
	}

	uasort(
		$nhom,
		static function ( $a, $b ) {
			return $a['thu_tu'] <=> $b['thu_tu'];
		}
	);

	foreach ( $nhom as &$du_lieu_nhom ) {
		usort(
			$du_lieu_nhom['dich_vu'],
			static function ( $a, $b ) {
				if ( $a['thu_tu'] === $b['thu_tu'] ) {
					return strnatcasecmp( $a['post']->post_title, $b['post']->post_title );
				}
				return $a['thu_tu'] <=> $b['thu_tu'];
			}
		);
	}
	unset( $du_lieu_nhom );
	?>

<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-bang-gia' ); ?>>
	<section class="eyecare-bang-gia__hero" aria-labelledby="bang-gia-tieu-de">
		<div class="eyecare-bang-gia__khung">
			<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?><div class="eyecare-bang-gia__duong-dan"><?php eyecare_duong_dan_in(); ?></div><?php endif; ?>
			<div class="eyecare-bang-gia__hero-grid">
				<div><p class="eyecare-bang-gia__nhan"><span aria-hidden="true"></span> Tra cứu minh bạch</p><h1 id="bang-gia-tieu-de"><?php the_title(); ?></h1><p>Tìm nhanh dịch vụ theo tên hoặc xem theo từng nhóm. Dữ liệu được quản lý trực tiếp trong WordPress Admin.</p></div>
				<div class="eyecare-bang-gia__tong"><strong><?php echo esc_html( number_format_i18n( count( $posts_gia ) ) ); ?></strong><span>dịch vụ đang niêm yết</span><small><?php echo esc_html( number_format_i18n( count( $nhom ) ) ); ?> nhóm danh mục</small></div>
			</div>
		</div>
	</section>

	<section class="eyecare-bang-gia__noi-dung">
		<div class="eyecare-bang-gia__khung">
			<div class="eyecare-bang-gia__cong-cu">
				<label for="eyecare-tim-gia"><span>Tìm dịch vụ</span><input id="eyecare-tim-gia" type="search" placeholder="Ví dụ: khám mắt, OCT, Phaco…" autocomplete="off"></label>
				<p id="eyecare-ket-qua-gia" aria-live="polite"></p>
			</div>

			<?php if ( $ghi_chu ) : ?><p class="eyecare-bang-gia__ghi-chu"><?php echo esc_html( $ghi_chu ); ?></p><?php endif; ?>

			<nav class="eyecare-bang-gia__nhom-nav" aria-label="Nhóm bảng giá">
				<?php foreach ( $nhom as $ten_nhom => $du_lieu_nhom ) :
					$nhom_id = 'nhom-gia-' . sanitize_title( $ten_nhom );
					$nhan_nhom = preg_replace( '/^[IVXLCDM]+\s*[-–]\s*/u', '', $ten_nhom );
					?><a href="#<?php echo esc_attr( $nhom_id ); ?>"><?php echo esc_html( $nhan_nhom ); ?></a><?php endforeach; ?>
			</nav>

			<div class="eyecare-bang-gia__cac-nhom" data-bang-gia>
				<?php foreach ( $nhom as $ten_nhom => $du_lieu_nhom ) :
					$nhom_id = 'nhom-gia-' . sanitize_title( $ten_nhom );
					$nhan_nhom = preg_replace( '/^[IVXLCDM]+\s*[-–]\s*/u', '', $ten_nhom );
					?>
					<section id="<?php echo esc_attr( $nhom_id ); ?>" class="eyecare-bang-gia__nhom" data-nhom-gia>
						<header><div><span><?php echo esc_html( str_pad( (string) $du_lieu_nhom['thu_tu'], 2, '0', STR_PAD_LEFT ) ); ?></span><h2><?php echo esc_html( $nhan_nhom ); ?></h2></div><em><?php echo esc_html( number_format_i18n( count( $du_lieu_nhom['dich_vu'] ) ) ); ?> dịch vụ</em></header>
						<div class="eyecare-bang-gia__bang-wrap">
							<table><thead><tr><th scope="col">Dịch vụ</th><th scope="col">Đơn vị</th><th scope="col"><?php echo $hien_gia ? 'Mức giá' : 'Thông tin giá'; ?></th></tr></thead><tbody>
							<?php foreach ( $du_lieu_nhom['dich_vu'] as $dong ) :
								$ten = get_the_title( $dong['post']->ID );
								?>
								<tr data-dich-vu-gia data-ten="<?php echo esc_attr( remove_accents( strtolower( $ten ) ) ); ?>"><td data-label="Dịch vụ"><strong><?php echo esc_html( $ten ); ?></strong></td><td data-label="Đơn vị"><?php echo esc_html( $dong['don_vi'] ?: '—' ); ?></td><td data-label="Mức giá" class="eyecare-bang-gia__muc"><?php echo $hien_gia && $dong['gia'] > 0 ? esc_html( number_format_i18n( $dong['gia'] ) . ' đ' ) : '<a href="tel:' . esc_attr( $tt['dien_thoai'] ) . '">Liên hệ</a>'; ?></td></tr>
							<?php endforeach; ?>
							</tbody></table>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
</article>

<script>
( function () {
	var input = document.getElementById( 'eyecare-tim-gia' );
	var status = document.getElementById( 'eyecare-ket-qua-gia' );
	var rows = Array.prototype.slice.call( document.querySelectorAll( '[data-dich-vu-gia]' ) );
	var groups = Array.prototype.slice.call( document.querySelectorAll( '[data-nhom-gia]' ) );
	if ( ! input || ! rows.length ) { return; }
	function normalizeText( value ) {
		return value.toLowerCase().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' ).replace( /đ/g, 'd' ).trim();
	}
	function filterPrices() {
		var query = normalizeText( input.value );
		var visible = 0;
		rows.forEach( function ( row ) {
			var match = ! query || normalizeText( row.getAttribute( 'data-ten' ) || row.textContent ).indexOf( query ) !== -1;
			row.hidden = ! match;
			if ( match ) { visible += 1; }
		} );
		groups.forEach( function ( group ) {
			group.hidden = ! group.querySelector( '[data-dich-vu-gia]:not([hidden])' );
		} );
		status.textContent = query ? visible + ' dịch vụ phù hợp' : '';
	}
	input.addEventListener( 'input', filterPrices );
}() );
</script>

	<?php
endwhile;

get_footer();

