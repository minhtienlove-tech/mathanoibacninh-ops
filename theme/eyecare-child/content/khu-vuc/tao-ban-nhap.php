<?php
/**
 * WP-CLI: tạo 99 bản nháp, rồi xuất bản toàn bộ khi được duyệt.
 *
 * Chạy thử (chỉ đọc): wp eval-file wp-content/themes/eyecare-child/content/khu-vuc/tao-ban-nhap.php
 * Sau khi đã backup DB: EYECARE_KV_APPLY_DRAFTS=1 wp eval-file ...
 * Sau khi xác nhận đủ 99 bản nháp và tệp nội dung riêng:
 * EYECARE_KV_PUBLISH=1 wp eval-file ...
 *
 * Không xóa trang hoặc sửa nội dung WordPress do người quản trị nhập.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || PHP_SAPI !== 'cli' ) {
	exit;
}

$tep = __DIR__ . '/areas.json';
$doc = json_decode( file_get_contents( $tep ), true );
if ( ! is_array( $doc ) || ! isset( $doc['don_vi'] ) || count( $doc['don_vi'] ) !== 99 ) {
	WP_CLI::error( 'Danh mục hành chính phải có chính xác 99 đơn vị.' );
}

$cha = array(
	'bac-ninh' => get_page_by_path( 'khu-vuc/kham-mat-bac-ninh', OBJECT, 'page' ),
	'bac-giang' => get_page_by_path( 'khu-vuc/kham-mat-bac-giang', OBJECT, 'page' ),
);
foreach ( $cha as $vung => $bai ) {
	if ( ! $bai instanceof WP_Post || 'publish' !== $bai->post_status ) {
		WP_CLI::error( 'Thiếu trang cha đã công bố: ' . $vung );
	}
}

$ghi = '1' === getenv( 'EYECARE_KV_APPLY_DRAFTS' );
$xuat_ban = '1' === getenv( 'EYECARE_KV_PUBLISH' );
if ( $ghi && $xuat_ban ) {
	WP_CLI::error( 'Chỉ chọn một thao tác mỗi lần: tạo bản nháp hoặc xuất bản.' );
}
$moi = 0;
$co = 0;
$can_xuat_ban = array();
$da_xuat_ban = 0;
$bam_noi_dung = array();
foreach ( $doc['don_vi'] as $dia_ban ) {
	$vung = $dia_ban['vung_lich_su'];
	if ( ! isset( $cha[ $vung ] ) || ! preg_match( '/^(xa|phuong)-[a-z0-9-]+$/', $dia_ban['slug'] ) ) {
		WP_CLI::error( 'Dữ liệu xã/phường hoặc slug không hợp lệ.' );
	}
	$duong_dan = 'khu-vuc/kham-mat-' . $vung . '/' . $dia_ban['slug'];
	$tep_noi_dung = __DIR__ . '/dia-ban/' . $dia_ban['slug'] . '.html';
	if ( ! is_readable( $tep_noi_dung ) || filesize( $tep_noi_dung ) < 1500 ) {
		WP_CLI::error( 'Thiếu nội dung riêng hoặc nội dung quá ngắn: ' . $duong_dan );
	}
	$bam = hash_file( 'sha256', $tep_noi_dung );
	if ( isset( $bam_noi_dung[ $bam ] ) ) {
		WP_CLI::error( 'Hai địa bàn dùng cùng nội dung: ' . $duong_dan . ' và ' . $bam_noi_dung[ $bam ] );
	}
	$bam_noi_dung[ $bam ] = $duong_dan;
	$cu = get_page_by_path( $duong_dan, OBJECT, 'page' );
	if ( $cu instanceof WP_Post ) {
		++$co;
		if ( $xuat_ban ) {
			if ( ! in_array( $cu->post_status, array( 'draft', 'publish' ), true ) || (int) $cu->post_parent !== (int) $cha[ $vung ]->ID || '' !== trim( wp_strip_all_tags( $cu->post_content ) ) ) {
				WP_CLI::error( 'Trang đã được sửa hoặc không thuộc lô xuất bản: ' . $duong_dan );
			}
			if ( 'draft' === $cu->post_status ) {
				$can_xuat_ban[] = (int) $cu->ID;
			} else {
				++$da_xuat_ban;
			}
		}
		continue;
	}
	if ( $xuat_ban ) {
		WP_CLI::error( 'Chưa tạo đủ bản nháp: ' . $duong_dan );
	}
	++$moi;
	if ( ! $ghi ) {
		continue;
	}
	$tieu_de = 'Khám mắt tại ' . $dia_ban['loai'] . ' ' . $dia_ban['ten'];
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'draft',
			'post_parent'  => (int) $cha[ $vung ]->ID,
			'post_title'   => $tieu_de,
			'post_name'    => $dia_ban['slug'],
			'post_content' => '<!-- Nội dung bản thảo lấy từ child theme, cần bác sĩ duyệt trước công bố. -->',
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $tieu_de . ': ' . $id->get_error_message() );
	}
	WP_CLI::log( 'Draft ' . $id . ' ' . $tieu_de );
}
if ( $xuat_ban ) {
	if ( count( $can_xuat_ban ) + $da_xuat_ban !== 99 ) {
		WP_CLI::error( 'Phải có đúng 99 trang tự tạo trước khi xuất bản.' );
	}
	foreach ( $can_xuat_ban as $id ) {
		$ket_qua = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ), true );
		if ( is_wp_error( $ket_qua ) ) {
			WP_CLI::error( 'Không thể xuất bản trang ' . $id . ': ' . $ket_qua->get_error_message() );
		}
	}
	WP_CLI::success( 'Đã xuất bản 99 trang xã/phường có nội dung riêng.' );
} else {
	WP_CLI::success( ( $ghi ? 'Đã tạo ' : 'Có thể tạo ' ) . $moi . ' bản nháp; ' . $co . ' trang đã tồn tại.' );
}
