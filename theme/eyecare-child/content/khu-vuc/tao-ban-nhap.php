<?php
/**
 * WP-CLI: chuẩn bị 99 trang xã/phường dưới dạng draft, KHÔNG xuất bản.
 *
 * Chạy thử (chỉ đọc): wp eval-file wp-content/themes/eyecare-child/content/khu-vuc/tao-ban-nhap.php
 * Sau khi đã backup DB: EYECARE_KV_APPLY_DRAFTS=1 wp eval-file ...
 *
 * Không sửa trang đã tồn tại, không xóa và không đổi trạng thái xuất bản.
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
$moi = 0;
$co = 0;
foreach ( $doc['don_vi'] as $dia_ban ) {
	$vung = $dia_ban['vung_lich_su'];
	if ( ! isset( $cha[ $vung ] ) || ! preg_match( '/^(xa|phuong)-[a-z0-9-]+$/', $dia_ban['slug'] ) ) {
		WP_CLI::error( 'Dữ liệu xã/phường hoặc slug không hợp lệ.' );
	}
	$duong_dan = 'khu-vuc/kham-mat-' . $vung . '/' . $dia_ban['slug'];
	$cu = get_page_by_path( $duong_dan, OBJECT, 'page' );
	if ( $cu instanceof WP_Post ) {
		++$co;
		continue;
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
WP_CLI::success( ( $ghi ? 'Đã tạo ' : 'Có thể tạo ' ) . $moi . ' bản nháp; ' . $co . ' trang đã tồn tại. Không trang nào được publish.' );
