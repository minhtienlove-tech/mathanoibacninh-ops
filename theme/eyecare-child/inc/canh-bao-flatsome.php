<?php
/**
 * Gỡ cảnh báo "outdated template files" của Flatsome — kèm chốt an toàn.
 *
 * VÌ SAO CẢNH BÁO ĐÓ HIỆN RA:
 * Flatsome quét mọi tệp .php của theme con trùng tên với tệp của theme mẹ,
 * đọc thẻ `@flatsome-version` trong đầu tệp, rồi so với bản của theme mẹ.
 * Không có thẻ, hoặc thẻ nhỏ hơn, thì bị coi là "bản sao lỗi thời".
 *
 * VÌ SAO Ở ĐÂY NÓ BÁO OAN:
 * page.php và single.php của theme con KHÔNG phải bản sao của Flatsome. Chúng
 * được viết mới từ đầu cho bài kiến thức nhãn khoa (bề rộng dòng, tầng tiêu
 * đề, không có nút chia sẻ chen giữa nội dung y khoa) và chỉ trùng tên vì
 * WordPress quy định tên khuôn. Không có gì để "cập nhật theo bản mới" cả —
 * dán mã Flatsome vào sẽ phá đúng những thứ chúng được viết ra để làm.
 *
 * 🔴 VÌ SAO KHÔNG TẮT HẲN:
 * Nếu sau này có người sao chép một khuôn Flatsome thật (header.php,
 * footer.php, woocommerce/...) rồi sửa, cảnh báo đó là cảnh báo ĐÚNG và cần
 * thấy. Nên ở đây chỉ bỏ qua đúng các tệp đã biết là viết riêng, ghi trong
 * $tep_viet_rieng. Tệp nào ngoài danh sách mà lỗi thời thì vẫn hiện cảnh báo,
 * bằng thông báo tiếng Việt nói rõ tệp nào.
 *
 * Cách thêm tệp vào danh sách: chỉ thêm khi tệp đó thực sự viết mới từ đầu,
 * không phải khi muốn cho cảnh báo im đi.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Các khuôn viết mới từ đầu, chỉ trùng tên với Flatsome.
 *
 * Đường dẫn tính từ thư mục theme con.
 *
 * @return string[]
 */
function eyecare_cb_tep_viet_rieng() {
	return array(
		'page.php',
		'single.php',
	);
}

/**
 * Gỡ cảnh báo mặc định của Flatsome.
 *
 * Phải gỡ ở admin_init chứ không gỡ trong functions.php: WordPress nạp
 * functions.php của theme con TRƯỚC theme mẹ, lúc đó Flatsome chưa kịp
 * add_action nên remove_action không có gì để gỡ.
 */
function eyecare_cb_go_canh_bao_flatsome() {
	remove_action( 'admin_notices', 'flatsome_status_check_admin_notice' );
}
add_action( 'admin_init', 'eyecare_cb_go_canh_bao_flatsome' );

/**
 * Đọc thẻ @flatsome-version trong đầu tệp.
 *
 * Chỉ đọc 8 KB đầu như WordPress làm với header tệp — không nạp cả tệp lớn.
 *
 * @param string $duong_dan Đường dẫn tuyệt đối tới tệp.
 * @return string Số bản, hoặc chuỗi rỗng nếu không có thẻ.
 */
function eyecare_cb_doc_ban( $duong_dan ) {
	if ( ! is_readable( $duong_dan ) ) {
		return '';
	}

	$tay = fopen( $duong_dan, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! $tay ) {
		return '';
	}

	$dau_tep = fread( $tay, 8192 ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fclose( $tay ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	if ( ! $dau_tep ) {
		return '';
	}

	$dau_tep = str_replace( "\r", "\n", $dau_tep );

	if ( preg_match( '/^[ \t\/*#@]*@flatsome-version\s*(.*)$/mi', $dau_tep, $khop ) ) {
		return trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $khop[1] ) );
	}

	return '';
}

/**
 * Tìm các khuôn theo Flatsome đã cũ, BỎ QUA các tệp viết riêng.
 *
 * @return array[] Mỗi phần tử: array( tep, ban_con, ban_me ).
 */
function eyecare_cb_tim_khuon_cu() {
	$thu_muc_me  = get_template_directory();
	$thu_muc_con = get_stylesheet_directory();

	// Theme con chính là theme mẹ thì không có gì để so.
	if ( $thu_muc_me === $thu_muc_con ) {
		return array();
	}

	$viet_rieng = eyecare_cb_tep_viet_rieng();
	$ket_qua    = array();

	// Ba nơi Flatsome quét: thư mục gốc, template-parts, woocommerce.
	$vung_quet = array( '', 'template-parts', 'woocommerce' );

	foreach ( $vung_quet as $vung ) {
		$duong_me = $thu_muc_me . ( '' === $vung ? '' : '/' . $vung );

		if ( ! is_dir( $duong_me ) ) {
			continue;
		}

		// Thư mục gốc quét KHÔNG đệ quy — đúng như Flatsome làm. Đệ quy ở đây
		// sẽ nuốt luôn template-parts và woocommerce, vốn đã có lượt quét
		// riêng bên dưới, và sinh ra cảnh báo lặp cho cùng một tệp.
		$danh_sach = eyecare_cb_liet_ke_php( $duong_me, '', '' !== $vung );

		foreach ( $danh_sach as $ten ) {
			$tuong_doi = ( '' === $vung ? '' : $vung . '/' ) . $ten;

			// functions.php của theme con luôn tồn tại và không phải khuôn.
			if ( 'functions.php' === $tuong_doi ) {
				continue;
			}

			// Tệp viết mới từ đầu — không so bản.
			if ( in_array( $tuong_doi, $viet_rieng, true ) ) {
				continue;
			}

			$tep_con = $thu_muc_con . '/' . $tuong_doi;

			if ( ! file_exists( $tep_con ) ) {
				continue;
			}

			$ban_me = eyecare_cb_doc_ban( $thu_muc_me . '/' . $tuong_doi );

			// Theme mẹ không đánh số bản thì không có cơ sở để so.
			if ( '' === $ban_me ) {
				continue;
			}

			$ban_con = eyecare_cb_doc_ban( $tep_con );

			if ( '' === $ban_con || version_compare( $ban_con, $ban_me, '<' ) ) {
				$ket_qua[] = array(
					'tep'     => $tuong_doi,
					'ban_con' => $ban_con,
					'ban_me'  => $ban_me,
				);
			}
		}
	}

	return $ket_qua;
}

/**
 * Liệt kê tệp .php trong một thư mục, đệ quy.
 *
 * @param string $duong_dan Thư mục cần quét.
 * @param string $tien_to   Tiền tố đường dẫn tương đối (dùng khi đệ quy).
 * @param bool   $de_quy    Có xuống thư mục con hay không.
 * @return string[]
 */
function eyecare_cb_liet_ke_php( $duong_dan, $tien_to = '', $de_quy = true ) {
	$ket_qua = array();
	$muc     = @scandir( $duong_dan ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

	if ( ! $muc ) {
		return $ket_qua;
	}

	foreach ( $muc as $ten ) {
		if ( '.' === $ten || '..' === $ten ) {
			continue;
		}

		$day_du = $duong_dan . '/' . $ten;

		if ( is_dir( $day_du ) ) {
			if ( $de_quy ) {
				$ket_qua = array_merge( $ket_qua, eyecare_cb_liet_ke_php( $day_du, $tien_to . $ten . '/', true ) );
			}
		} elseif ( '.php' === strtolower( substr( $ten, -4 ) ) ) {
			$ket_qua[] = $tien_to . $ten;
		}
	}

	return $ket_qua;
}

/**
 * Cảnh báo thay thế: chỉ hiện khi có khuôn sao chép thật đã cũ.
 *
 * Khác cảnh báo của Flatsome ở hai chỗ: viết tiếng Việt, và nói rõ tệp nào
 * cũ kèm số bản — người đọc không phải bấm sang trang khác để biết.
 */
function eyecare_cb_canh_bao_khuon_cu() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$man_hinh = get_current_screen();
	$cho_hien = array( 'dashboard', 'themes', 'theme-editor', 'update-core', 'site-health' );

	if ( ! $man_hinh || ! in_array( $man_hinh->id, $cho_hien, true ) ) {
		return;
	}

	$khuon_cu = eyecare_cb_tim_khuon_cu();

	if ( ! $khuon_cu ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>';
	esc_html_e( 'Theme con có khuôn sao chép từ Flatsome đã cũ hơn bản của theme mẹ:', 'eyecare-child' );
	echo '</strong></p><ul style="list-style:disc;margin-left:1.5em">';

	foreach ( $khuon_cu as $muc ) {
		printf(
			'<li><code>%1$s</code> — %2$s</li>',
			esc_html( $muc['tep'] ),
			esc_html(
				sprintf(
					/* translators: %1$s: bản của theme con, %2$s: bản của theme mẹ. */
					__( 'bản trong theme con: %1$s · bản của Flatsome: %2$s', 'eyecare-child' ),
					'' === $muc['ban_con'] ? __( 'không ghi', 'eyecare-child' ) : $muc['ban_con'],
					$muc['ban_me']
				)
			)
		);
	}

	echo '</ul><p>';
	esc_html_e( 'Cần đối chiếu với bản mới của Flatsome rồi áp lại phần đã sửa. Nếu tệp thực sự được viết mới từ đầu chứ không sao chép, thêm tên tệp vào eyecare_cb_tep_viet_rieng() trong inc/canh-bao-flatsome.php.', 'eyecare-child' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'eyecare_cb_canh_bao_khuon_cu' );
