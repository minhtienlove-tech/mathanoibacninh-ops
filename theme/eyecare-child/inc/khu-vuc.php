<?php
/**
 * Nội dung địa bàn khám mắt lưu trong child theme; chỉ hiện khi
 * trang WordPress tương ứng vẫn rỗng. Không tự tạo trang và không sửa DB.
 *
 * Danh mục hành chính: Nghị quyết 1658/NQ-UBTVQH15 (99 xã/phường, 2025).
 * Nội dung y khoa mang tính giáo dục, không thay thế thăm khám cá nhân.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function eyecare_khu_vuc_du_lieu() {
	static $du_lieu = null;
	if ( null === $du_lieu ) {
		$tep = get_stylesheet_directory() . '/content/khu-vuc/areas.json';
		$doc = is_readable( $tep ) ? json_decode( file_get_contents( $tep ), true ) : null;
		$du_lieu = is_array( $doc ) && isset( $doc['don_vi'] ) && is_array( $doc['don_vi'] )
			? $doc['don_vi'] : array();
	}
	return $du_lieu;
}

/** Trả về mã nội dung cho ba trang trụ cột hoặc dữ liệu của trang xã/phường. */
function eyecare_khu_vuc_nguon( $bai ) {
	if ( ! $bai instanceof WP_Post || 'page' !== $bai->post_type ) {
		return null;
	}
	$duong_dan = trim( get_page_uri( $bai ), '/' );
	$ba_trang = array(
		'khu-vuc'                     => 'hub',
		'khu-vuc/kham-mat-bac-giang' => 'bac-giang',
		'khu-vuc/kham-mat-bac-ninh'  => 'bac-ninh',
	);
	if ( isset( $ba_trang[ $duong_dan ] ) ) {
		return array( 'loai' => 'tru-cot', 'ma' => $ba_trang[ $duong_dan ] );
	}
	if ( ! preg_match( '#^khu-vuc/(kham-mat-bac-giang|kham-mat-bac-ninh)/([^/]+)$#', $duong_dan, $m ) ) {
		return null;
	}
	$vung = 'kham-mat-bac-giang' === $m[1] ? 'bac-giang' : 'bac-ninh';
	foreach ( eyecare_khu_vuc_du_lieu() as $dia_ban ) {
		if ( $dia_ban['slug'] === $m[2] && $dia_ban['vung_lich_su'] === $vung ) {
			return array( 'loai' => 'dia-ban', 'dia_ban' => $dia_ban );
		}
	}
	return null;
}

function eyecare_khu_vuc_tep( $ma ) {
	$cho_phep = array( 'hub', 'bac-giang', 'bac-ninh' );
	if ( ! in_array( $ma, $cho_phep, true ) ) {
		return '';
	}
	return get_stylesheet_directory() . '/content/khu-vuc/' . $ma . '.html';
}

function eyecare_khu_vuc_tep_dia_ban( $dia_ban ) {
	$slug = isset( $dia_ban['slug'] ) ? $dia_ban['slug'] : '';
	if ( ! preg_match( '/^(xa|phuong)-[a-z0-9-]+$/', $slug ) ) {
		return '';
	}
	return get_stylesheet_directory() . '/content/khu-vuc/dia-ban/' . $slug . '.html';
}

function eyecare_khu_vuc_co_noi_dung( $bai ) {
	$nguon = eyecare_khu_vuc_nguon( $bai );
	if ( ! $nguon ) {
		return false;
	}
	return 'dia-ban' === $nguon['loai']
		? is_readable( eyecare_khu_vuc_tep_dia_ban( $nguon['dia_ban'] ) )
		: is_readable( eyecare_khu_vuc_tep( $nguon['ma'] ) );
}

function eyecare_khu_vuc_h1( $bai ) {
	$nguon = eyecare_khu_vuc_nguon( $bai );
	return $nguon && 'tru-cot' === $nguon['loai'] && 'hub' === $nguon['ma']
		? 'Khu vực khám mắt' : get_the_title( $bai );
}

/** Chỉ dùng nội dung từ Git nếu trang DB còn trống; sửa tay trong WP thắng. */
function eyecare_khu_vuc_noi_dung( $noi_dung ) {
	if ( ! is_page() || ! is_main_query() || ! in_the_loop() ) {
		return $noi_dung;
	}
	$bai = get_post( get_the_ID() );
	if ( ! eyecare_khu_vuc_co_noi_dung( $bai ) || '' !== trim( wp_strip_all_tags( strip_shortcodes( $bai->post_content ) ) ) ) {
		return $noi_dung;
	}
	$nguon = eyecare_khu_vuc_nguon( $bai );
	if ( 'tru-cot' === $nguon['loai'] ) {
		$van_ban = file_get_contents( eyecare_khu_vuc_tep( $nguon['ma'] ) );
		return do_shortcode( $van_ban );
	}
	return do_shortcode( file_get_contents( eyecare_khu_vuc_tep_dia_ban( $nguon['dia_ban'] ) ) );
}
add_filter( 'the_content', 'eyecare_khu_vuc_noi_dung', 12 );

/** Lấy các trang con đã công bố bằng một truy vấn thay vì 99 lần tra đường dẫn. */
function eyecare_khu_vuc_trang_con_xuat_ban() {
	static $ban_do = null;
	if ( null !== $ban_do ) {
		return $ban_do;
	}
	$ban_do = array();
	$cha_ids = array();
	foreach ( array( 'bac-ninh', 'bac-giang' ) as $vung ) {
		$cha = get_page_by_path( 'khu-vuc/kham-mat-' . $vung, OBJECT, 'page' );
		if ( $cha instanceof WP_Post ) {
			$cha_ids[ (int) $cha->ID ] = $vung;
		}
	}
	if ( ! $cha_ids ) {
		return $ban_do;
	}
	$con = get_posts( array(
		'post_type'              => 'page',
		'post_status'            => 'publish',
		'post_parent__in'        => array_keys( $cha_ids ),
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
	) );
	foreach ( $con as $trang ) {
		$ban_do[ $cha_ids[ (int) $trang->post_parent ] . '/' . $trang->post_name ] = $trang;
	}
	return $ban_do;
}

/** Mục lục có link tới trang đã publish, còn bản nháp hiện tên rõ ràng. */
function eyecare_khu_vuc_danh_sach( $thuoc_tinh ) {
	$thuoc_tinh = shortcode_atts( array( 'vung' => '' ), $thuoc_tinh, 'eyecare_khu_vuc_danh_sach' );
	$trang_con = eyecare_khu_vuc_trang_con_xuat_ban();
	$nhom = array( 'bac-ninh' => array(), 'bac-giang' => array() );
	foreach ( eyecare_khu_vuc_du_lieu() as $dia_ban ) {
		$nhom[ $dia_ban['vung_lich_su'] ][] = $dia_ban;
	}
	$ra = '<nav class="kv-directory" aria-label="Danh sách xã phường tỉnh Bắc Ninh">';
	foreach ( $nhom as $vung => $danh_sach ) {
		if ( $thuoc_tinh['vung'] && $thuoc_tinh['vung'] !== $vung ) {
			continue;
		}
		$ra .= '<section class="kv-directory__group"><h3>' . ( 'bac-ninh' === $vung ? 'Địa bàn Bắc Ninh trước sắp xếp' : 'Địa bàn Bắc Giang trước sắp xếp' ) . ' (' . count( $danh_sach ) . ' xã/phường)</h3><ul>';
		foreach ( $danh_sach as $dia_ban ) {
			$ten = ucfirst( $dia_ban['loai'] ) . ' ' . $dia_ban['ten'];
			$trang = $trang_con[ $vung . '/' . $dia_ban['slug'] ] ?? null;
			$ra .= '<li>';
			$ra .= $trang instanceof WP_Post && 'publish' === $trang->post_status
				? '<a href="' . esc_url( get_permalink( $trang ) ) . '">' . esc_html( $ten ) . '</a>'
				: '<span>' . esc_html( $ten ) . '</span>';
			$ra .= '</li>';
		}
		$ra .= '</ul></section>';
	}
	return $ra . '</nav>';
}
add_shortcode( 'eyecare_khu_vuc_danh_sach', 'eyecare_khu_vuc_danh_sach' );

/** Gom FAQ nguồn Git trước wp_head để schema dùng cùng câu trả lời đang hiển thị. */
function eyecare_khu_vuc_gom_faq() {
	if ( ! is_page() ) {
		return;
	}
	$bai = get_queried_object();
	$nguon = eyecare_khu_vuc_nguon( $bai );
	if ( ! $nguon || '' !== trim( wp_strip_all_tags( strip_shortcodes( $bai->post_content ) ) ) ) {
		return;
	}
	$tep = 'dia-ban' === $nguon['loai']
		? eyecare_khu_vuc_tep_dia_ban( $nguon['dia_ban'] )
		: eyecare_khu_vuc_tep( $nguon['ma'] );
	if ( ! is_readable( $tep ) || ! function_exists( 'eyecare_tach_faq' ) ) {
		return;
	}
	$noi_dung = file_get_contents( $tep );
	if ( preg_match( '#\[faq\](.*?)\[/faq\]#s', $noi_dung, $khop ) ) {
		global $eyecare_faq_da_gom;
		$eyecare_faq_da_gom = eyecare_tach_faq( $khop[1] );
	}
}
add_action( 'wp', 'eyecare_khu_vuc_gom_faq' );

function eyecare_khu_vuc_css() {
	if ( ! is_page() || ! eyecare_khu_vuc_nguon( get_queried_object() ) ) {
		return;
	}
	$tep = get_stylesheet_directory() . '/assets/khu-vuc.css';
	wp_enqueue_style( 'eyecare-khu-vuc', get_stylesheet_directory_uri() . '/assets/khu-vuc.css', array(), filemtime( $tep ) );
}
add_action( 'wp_enqueue_scripts', 'eyecare_khu_vuc_css' );

/** Schema trang điều hướng, không tự gắn reviewedBy hoặc bác sĩ chưa duyệt. */
function eyecare_schema_khu_vuc() {
	if ( ! is_page() ) {
		return null;
	}
	$bai = get_queried_object();
	$nguon = eyecare_khu_vuc_nguon( $bai );
	if ( ! $nguon ) {
		return null;
	}
	$url = get_permalink( $bai );
	return array(
		'@type'       => 'tru-cot' === $nguon['loai'] && 'hub' === $nguon['ma'] ? 'CollectionPage' : 'WebPage',
		'@id'         => $url . '#khu-vuc',
		'url'         => $url,
		'name'        => eyecare_khu_vuc_h1( $bai ),
		'inLanguage'  => 'vi-VN',
		'isPartOf'    => array( '@id' => home_url( '/' ) . '#website' ),
		'publisher'   => array( '@id' => home_url( '/' ) . '#to-chuc' ),
		'about'       => array( '@type' => 'Thing', 'name' => 'Thông tin khám mắt tại các địa bàn tỉnh Bắc Ninh' ),
	);
}

function eyecare_khu_vuc_title( $tieu_de ) {
	if ( ! is_page() ) {
		return $tieu_de;
	}
	$nguon = eyecare_khu_vuc_nguon( get_queried_object() );
	if ( ! $nguon ) {
		return $tieu_de;
	}
	if ( 'dia-ban' === $nguon['loai'] ) {
		return 'Khám mắt tại ' . $nguon['dia_ban']['loai'] . ' ' . $nguon['dia_ban']['ten'] . ' | Bệnh viện Mắt Hà Nội – Bắc Ninh';
	}
	$ten = array( 'hub' => 'Khu vực khám mắt tại 99 xã, phường Bắc Ninh', 'bac-giang' => 'Khám mắt địa bàn Bắc Giang – Hướng dẫn và danh sách xã, phường', 'bac-ninh' => 'Khám mắt Bắc Ninh – Hướng dẫn và danh sách xã, phường' );
	return $ten[ $nguon['ma'] ] . ' | Bệnh viện Mắt Hà Nội – Bắc Ninh';
}
add_filter( 'pre_get_document_title', 'eyecare_khu_vuc_title', 30 );

function eyecare_khu_vuc_meta() {
	if ( ! is_page() ) {
		return;
	}
	$nguon = eyecare_khu_vuc_nguon( get_queried_object() );
	if ( ! $nguon ) {
		return;
	}
	if ( 'dia-ban' === $nguon['loai'] ) {
		$mo_ta = 'Thông tin khám mắt cho người dân ' . $nguon['dia_ban']['loai'] . ' ' . $nguon['dia_ban']['ten'] . ', tỉnh Bắc Ninh: tên địa bàn hiện hành, dấu hiệu cần khám, chuẩn bị hồ sơ và địa chỉ bệnh viện.';
	} else {
		$mo_ta = array(
			'hub' => 'Tra cứu 99 xã, phường tỉnh Bắc Ninh hiện hành, dấu hiệu cần khám mắt, cách chuẩn bị và bài kiến thức nhãn khoa của Bệnh viện Mắt Hà Nội – Bắc Ninh.',
			'bac-giang' => 'Hướng dẫn khám mắt cho địa bàn Bắc Giang cũ, nay thuộc tỉnh Bắc Ninh: 57 xã, phường, các triệu chứng cần chú ý và thông tin chuẩn bị.',
			'bac-ninh' => 'Hướng dẫn khám mắt tại địa bàn Bắc Ninh cũ: 42 xã, phường hiện hành, triệu chứng, chuẩn bị và bài đọc về nhãn khoa.',
		)[ $nguon['ma'] ];
	}
	echo '<meta name="description" content="' . esc_attr( $mo_ta ) . '">' . "\n";
}
add_action( 'wp_head', 'eyecare_khu_vuc_meta', 4 );
