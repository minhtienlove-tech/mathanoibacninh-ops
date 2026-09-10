<?php
/**
 * Thumbnail vector dự phòng cho bài viết chưa có ảnh đại diện.
 *
 * SVG chỉ xuất hiện ở giao diện danh sách. Khi quản trị viên đặt ảnh đại diện
 * thật, WordPress tự dùng ảnh thật và không cần xoá dữ liệu dự phòng.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trả về thumbnail SVG có màu theo chuyên mục chính của bài.
 *
 * @param int    $post_id  ID bài viết.
 * @param string $them_lop Lớp CSS bổ sung từ khuôn nội bộ.
 * @return string
 */
function eyecare_anh_bai_du_phong( $post_id = 0, $them_lop = '' ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	$cac_muc = get_the_category( $post_id );
	$muc     = null;

	foreach ( $cac_muc as $muc_ung_vien ) {
		if ( $muc_ung_vien instanceof WP_Term && $muc_ung_vien->parent ) {
			$muc = $muc_ung_vien;
			break;
		}
	}

	if ( ! $muc && $cac_muc ) {
		$muc = $cac_muc[0];
	}

	$slug = $muc instanceof WP_Term ? $muc->slug : 'kien-thuc-nhan-khoa';
	$nhan = $muc instanceof WP_Term ? $muc->name : 'Kiến thức nhãn khoa';

	$bang_mau = array(
		'can-thi-tre-em'             => array( '#e9f1ff', '#f8fbff', '#3867d6' ),
		'chuan-bi-kham-bao-hiem'     => array( '#e8f7f1', '#fffaf0', '#0c7a5a' ),
		'dau-hieu-can-kham-ngay'     => array( '#fff0f1', '#fff9f4', '#b42333' ),
		'dich-kinh-vong-mac'         => array( '#f0ecff', '#fbfaff', '#6847bd' ),
		'giac-mac-ket-mac-kho-mat'  => array( '#e6f8f7', '#f7fcfb', '#087f78' ),
		'glocom-cuom-nuoc'           => array( '#e5f5e9', '#f8fcf9', '#087b42' ),
		'mat-nguoi-cao-tuoi'         => array( '#fff4d8', '#fffaf0', '#9a6700' ),
		'mat-va-moi-truong-lam-viec' => array( '#e8f5fb', '#f7fbfd', '#176d91' ),
		'nhuoc-thi-lac-tre-em'       => array( '#faeaf4', '#fff8fc', '#a53f78' ),
		'tat-khuc-xa-nguoi-lon'      => array( '#e7f2ff', '#f8fbff', '#1768ac' ),
	);
	$mau = isset( $bang_mau[ $slug ] ) ? $bang_mau[ $slug ] : array( '#e7f5eb', '#f8fcf9', '#087b42' );

	$cac_lop = array( 'eyecare-anh-bai-du-phong' );
	foreach ( preg_split( '/\s+/', trim( $them_lop ) ) as $lop ) {
		$lop = sanitize_html_class( $lop );
		if ( '' !== $lop ) {
			$cac_lop[] = $lop;
		}
	}
	$cac_lop[] = 'eyecare-anh-bai-du-phong--' . ( $post_id % 3 );

	/* Uu tien anh JPG that trong theme de bai moi/ban nhap khong bi khoang
	 * trang neu may chu chua bat GD de tao anh bia tu dong. */
	$anh_mac_dinh_tap = get_stylesheet_directory() . '/assets/anh-bai-mac-dinh.jpg';
	$anh_mac_dinh_url = get_stylesheet_directory_uri() . '/assets/anh-bai-mac-dinh.jpg';
	if ( is_readable( $anh_mac_dinh_tap ) ) {
		return sprintf(
			'<span class="%1$s" aria-hidden="true"><img src="%2$s" alt="" loading="lazy" decoding="async"><span class="eyecare-anh-bai-du-phong__nhan">%3$s</span></span>',
			esc_attr( implode( ' ', array_unique( $cac_lop ) ) ),
			esc_url( $anh_mac_dinh_url ),
			esc_html( $nhan )
		);
	}

	return sprintf(
		'<span class="%1$s" style="--eyecare-thumb-a:%2$s;--eyecare-thumb-b:%3$s;--eyecare-thumb-nhan:%4$s" aria-hidden="true">'
		. '<svg viewBox="0 0 320 240" preserveAspectRatio="xMidYMid slice">'
		. '<circle class="eyecare-anh-bai-du-phong__quy-dao eyecare-anh-bai-du-phong__quy-dao--mot" cx="275" cy="28" r="94"/>'
		. '<circle class="eyecare-anh-bai-du-phong__quy-dao eyecare-anh-bai-du-phong__quy-dao--hai" cx="34" cy="220" r="72"/>'
		. '<path class="eyecare-anh-bai-du-phong__song" d="M-22 63C48 24 108 91 174 54s118-12 174 22"/>'
		. '<g class="eyecare-anh-bai-du-phong__mat"><path d="M48 119s42-61 112-61 112 61 112 61-42 61-112 61S48 119 48 119Z"/><circle cx="160" cy="119" r="37"/><circle class="eyecare-anh-bai-du-phong__dong-tu" cx="160" cy="119" r="16"/><circle class="eyecare-anh-bai-du-phong__sang" cx="171" cy="108" r="5"/></g>'
		. '<path class="eyecare-anh-bai-du-phong__lap-lanh" d="m54 43 4 10 10 4-10 4-4 10-4-10-10-4 10-4 4-10Zm221 130 3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7Z"/>'
		. '</svg><span class="eyecare-anh-bai-du-phong__nhan">%5$s</span></span>',
		esc_attr( implode( ' ', array_unique( $cac_lop ) ) ),
		esc_attr( $mau[0] ),
		esc_attr( $mau[1] ),
		esc_attr( $mau[2] ),
		esc_html( $nhan )
	);
}
