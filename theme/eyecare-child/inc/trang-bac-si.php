<?php
/**
 * Trang cá nhân từng bác sĩ — /bac-si/<slug>/.
 *
 * Nội dung lấy từ bản ghi trong mục Đội ngũ bác sĩ (Admin): ảnh, học vị,
 * chức danh, giới thiệu, Facebook cá nhân/phòng khám, nguồn khác và các bài
 * viết đã chọn bác sĩ ở hộp “Bác sĩ được giới thiệu trong bài”.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Phiên bản quy tắc đường dẫn; đổi khi đổi slug để làm mới rewrite một lần. */
const EYECARE_BAC_SI_REWRITE_VERSION = '1';

/** Làm mới rewrite một lần sau khi post type bác sĩ có đường dẫn công khai. */
function eyecare_bac_si_lam_moi_rewrite() {
	if ( EYECARE_BAC_SI_REWRITE_VERSION !== get_option( 'eyecare_bac_si_rewrite_version' ) ) {
		flush_rewrite_rules( false );
		update_option( 'eyecare_bac_si_rewrite_version', EYECARE_BAC_SI_REWRITE_VERSION, false );
	}
}
add_action( 'init', 'eyecare_bac_si_lam_moi_rewrite', 99 );

/** Đang xem trang cá nhân bác sĩ. */
function eyecare_la_trang_bac_si() {
	return is_singular( EYECARE_POST_TYPE_BAC_SI );
}

/** Mô tả ngắn của trang bác sĩ: tóm tắt nhập tay, rồi đến đoạn đầu giới thiệu. */
function eyecare_bac_si_mo_ta( $doctor_id ) {
	$doctor_id = absint( $doctor_id );
	$mo_ta     = trim( (string) get_post_field( 'post_excerpt', $doctor_id, 'raw' ) );
	if ( '' === $mo_ta ) {
		$mo_ta = wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $doctor_id, 'raw' ) ) ), 32, '…' );
	}
	if ( '' === trim( $mo_ta ) ) {
		$d     = function_exists( 'eyecare_bac_si_du_lieu_theo_id' ) ? eyecare_bac_si_du_lieu_theo_id( $doctor_id ) : array();
		$ten   = $d ? trim( $d['hoc_vi'] . ' ' . $d['ho_ten'] ) : get_the_title( $doctor_id );
		$mo_ta = $ten . ' – ' . ( ! empty( $d['chuc_danh'] ) ? $d['chuc_danh'] . ', ' : '' ) . 'Bệnh viện Mắt Hà Nội – Bắc Ninh. Hồ sơ chuyên môn và bài viết liên quan.';
	}
	return trim( preg_replace( '/\s+/u', ' ', $mo_ta ) );
}

/**
 * Gỡ đầu ra OBS không phù hợp với trang hồ sơ: Article schema (sai loại),
 * Open Graph trùng, breadcrumb/TOC/khối AI chèn vào phần giới thiệu.
 */
function eyecare_bac_si_go_obs() {
	if ( ! eyecare_la_trang_bac_si() || ! class_exists( 'OBS_Loader' ) ) {
		return;
	}
	$go_head = array(
		'opengraph'  => array( 'render', 5 ),
		'schema'     => array( 'render', 10 ),
		'breadcrumb' => array( 'render_schema', 15 ),
		'author_bio' => array( 'render_schema', 20 ),
	);
	foreach ( $go_head as $module => $hook ) {
		$obj = OBS_Loader::get( $module );
		if ( $obj ) {
			remove_action( 'wp_head', array( $obj, $hook[0] ), $hook[1] );
		}
	}
	$go_content = array(
		'breadcrumb'         => array( array( 'auto_insert', 5 ) ),
		'reading_time'       => array( array( 'prepend_to_content', 4 ), array( 'append_to_content', 5 ) ),
		'toc'                => array( array( 'process_content', 100 ) ),
		'author_bio'         => array( array( 'append_box', 99 ), array( 'prepend_box', 99 ), array( 'both_box', 99 ) ),
		'ai_citation_bridge' => array( array( 'maybe_inject_quick_answer', 7 ), array( 'maybe_inject', 8 ), array( 'maybe_inject_eeat', 11 ), array( 'maybe_inject_sources', 12 ) ),
		'faq_discovery'      => array( array( 'maybe_append_faq', 20 ) ),
	);
	foreach ( $go_content as $module => $hooks ) {
		$obj = OBS_Loader::get( $module );
		if ( ! $obj ) {
			continue;
		}
		foreach ( $hooks as $hook ) {
			remove_filter( 'the_content', array( $obj, $hook[0] ), $hook[1] );
		}
	}
}
add_action( 'wp', 'eyecare_bac_si_go_obs', 20 );

/** Tiêu đề tài liệu: “ThS.BS Lê Như Tùng – Cố vấn chuyên môn cao cấp”. */
function eyecare_bac_si_tieu_de( $title ) {
	if ( ! eyecare_la_trang_bac_si() ) {
		return $title;
	}
	$d = eyecare_bac_si_du_lieu_theo_id( get_queried_object_id() );
	if ( ! $d ) {
		return $title;
	}
	return trim( $d['hoc_vi'] . ' ' . $d['ho_ten'] ) . ( $d['chuc_danh'] ? ' – ' . $d['chuc_danh'] : '' ) . ' | Bệnh viện Mắt Hà Nội – Bắc Ninh';
}
add_filter( 'pre_get_document_title', 'eyecare_bac_si_tieu_de', 110 );

/** Một bộ meta description, Open Graph và Twitter cho trang bác sĩ. */
function eyecare_bac_si_seo_meta() {
	if ( ! eyecare_la_trang_bac_si() ) {
		return;
	}
	$id    = get_queried_object_id();
	$mo_ta = eyecare_bac_si_mo_ta( $id );
	$anh   = get_the_post_thumbnail_url( $id, 'large' );
	if ( ! $anh ) {
		$anh = (string) get_post_meta( $id, '_eyecare_anh_url', true );
	}
	echo '<meta name="description" content="' . esc_attr( $mo_ta ) . '" />' . "\n";
	$og = array(
		'og:type'        => 'profile',
		'og:site_name'   => 'Bệnh viện Mắt Hà Nội – Bắc Ninh',
		'og:locale'      => 'vi_VN',
		'og:title'       => wp_get_document_title(),
		'og:description' => $mo_ta,
		'og:url'         => get_permalink( $id ),
	);
	if ( $anh ) {
		$og['og:image'] = $anh;
	}
	foreach ( $og as $k => $v ) {
		echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '" />' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $og['og:title'] ) . '" />' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $mo_ta ) . '" />' . "\n";
}
add_action( 'wp_head', 'eyecare_bac_si_seo_meta', 3 );

/**
 * Các nút JSON-LD cho trang bác sĩ: ProfilePage + Physician + BreadcrumbList.
 * Được eyecare_in_schema() gộp vào @graph chung.
 *
 * @return array[]
 */
function eyecare_schema_trang_bac_si() {
	if ( ! eyecare_la_trang_bac_si() || ! function_exists( 'eyecare_schema_bac_si' ) ) {
		return array();
	}
	$id     = (int) get_queried_object_id();
	$link   = get_permalink( $id );
	$person = eyecare_schema_bac_si( $id );
	if ( ! $person ) {
		return array();
	}
	$person['description'] = eyecare_bac_si_mo_ta( $id );
	$person['worksFor']    = array( '@id' => home_url( '/' ) . '#to-chuc' );
	$anh                   = get_the_post_thumbnail_url( $id, 'large' );
	if ( $anh ) {
		$person['image'] = $anh;
	}
	$same_as = isset( $person['sameAs'] ) ? $person['sameAs'] : array();
	$phong   = eyecare_bac_si_phong_kham_url( $id );
	if ( $phong ) {
		$same_as[] = $phong;
	}
	if ( $same_as ) {
		$person['sameAs'] = array_values( array_unique( $same_as ) );
	}
	return array(
		array(
			'@type'        => 'ProfilePage',
			'@id'          => $link . '#trang',
			'url'          => $link,
			'name'         => wp_get_document_title(),
			'inLanguage'   => 'vi-VN',
			'isPartOf'     => array( '@id' => home_url( '/' ) . '#website' ),
			'mainEntity'   => array( '@id' => $person['@id'] ),
			'dateModified' => get_post_modified_time( DATE_W3C, false, $id ),
			'breadcrumb'   => array( '@id' => $link . '#duong-dan' ),
		),
		$person,
		array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $link . '#duong-dan',
			'itemListElement' => array(
				array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => home_url( '/' ) ),
				array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Đội ngũ bác sĩ', 'item' => home_url( '/doi-ngu-bac-si/' ) ),
				array( '@type' => 'ListItem', 'position' => 3, 'name' => get_the_title( $id ) ),
			),
		),
	);
}

/** CSS trang bác sĩ, dùng lại thẻ bài của trang tin. */
function eyecare_nap_css_trang_bac_si() {
	if ( ! eyecare_la_trang_bac_si() ) {
		return;
	}
	foreach ( array( 'eyecare-news' => 'news.css', 'eyecare-trang-bac-si' => 'doctor-page.css' ) as $handle => $file ) {
		$path = get_stylesheet_directory() . '/assets/' . $file;
		if ( file_exists( $path ) ) {
			wp_enqueue_style( $handle, get_stylesheet_directory_uri() . '/assets/' . $file, array( 'eyecare-child-style' ), filemtime( $path ) );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'eyecare_nap_css_trang_bac_si', 106 );

/** Cột Admin: đường dẫn trang cá nhân và số bài gắn với bác sĩ. */
function eyecare_bac_si_hang_thao_tac( $actions, $post ) {
	if ( EYECARE_POST_TYPE_BAC_SI === $post->post_type && 'publish' === $post->post_status ) {
		$so = count( eyecare_bac_si_bai_viet_ids( $post->ID ) );
		$actions['eyecare_bai'] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=post&eyecare_bac_si=' . $post->ID ) ) . '">' . esc_html( sprintf( 'Bài viết (%d)', $so ) ) . '</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'eyecare_bac_si_hang_thao_tac', 10, 2 );

/** Lọc danh sách Bài viết theo bác sĩ: edit.php?eyecare_bac_si=<id>. */
function eyecare_bac_si_loc_bai_admin( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || empty( $_GET['eyecare_bac_si'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- bộ lọc chỉ đọc.
		return;
	}
	$doctor_id = absint( wp_unslash( $_GET['eyecare_bac_si'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$query->set( 'post__in', eyecare_bac_si_bai_viet_ids( $doctor_id ) ?: array( 0 ) );
}
add_action( 'pre_get_posts', 'eyecare_bac_si_loc_bai_admin' );
