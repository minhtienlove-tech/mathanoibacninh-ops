<?php
/**
 * Nội dung địa bàn khám mắt. Bản thảo nguồn trong child theme; chỉ hiện khi
 * trang WordPress tương ứng vẫn rỗng. Không tự tạo trang và không sửa DB.
 *
 * Danh mục hành chính: Nghị quyết 1658/NQ-UBTVQH15 (99 xã/phường, 2025).
 * Nội dung y khoa: cần bác sĩ duyệt trước khi triển khai lên website công khai.
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

function eyecare_khu_vuc_co_noi_dung( $bai ) {
	$nguon = eyecare_khu_vuc_nguon( $bai );
	if ( ! $nguon ) {
		return false;
	}
	return 'dia-ban' === $nguon['loai'] || is_readable( eyecare_khu_vuc_tep( $nguon['ma'] ) );
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
	return eyecare_khu_vuc_trang_dia_ban( $nguon['dia_ban'] );
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

/** Trang xã/phường: thông tin hành chính chính xác, y khoa mang tính giáo dục. */
function eyecare_khu_vuc_trang_dia_ban( $dia_ban ) {
	$ten = ucfirst( $dia_ban['loai'] ) . ' ' . $dia_ban['ten'];
	$vung = 'bac-ninh' === $dia_ban['vung_lich_su'] ? 'Bắc Ninh' : 'Bắc Giang';
	$nen = 'bac-ninh' === $dia_ban['vung_lich_su'] ? '/khu-vuc/kham-mat-bac-ninh/' : '/khu-vuc/kham-mat-bac-giang/';
	$nguon_goc = $dia_ban['nguon_goc'];
	$mo_ta_nguon = $dia_ban['stt_nghi_quyet']
		? $ten . ' được hình thành từ ' . $nguon_goc . ' theo mục ' . $dia_ban['stt_nghi_quyet'] . ' Điều 1 Nghị quyết 1658/NQ-UBTVQH15.'
		: $ten . ' là xã không thực hiện sắp xếp theo Nghị quyết 1658/NQ-UBTVQH15.';
	$chuyen_de = ( (int) $dia_ban['stt_nghi_quyet'] % 3 );
	$chu_de = array(
		array( 'Trẻ học đường và tật khúc xạ', 'Trẻ nhìn sát bảng, nheo mắt hoặc nghiêng đầu không nhất thiết chỉ bị cận thị. Khám mắt có thể đánh giá thị lực từng mắt, khúc xạ và những vấn đề như lác, nhược thị theo độ tuổi. Gia đình nên mang kính cũ, kết quả kiểm tra ở trường và ghi lại biểu hiện cụ thể.', '/kien-thuc/kham-mat-cho-tre-truoc-tuoi-di-hoc/', 'Đọc hướng dẫn khám mắt cho trẻ' ),
		array( 'Người lớn tuổi, đục thủy tinh thể và đáy mắt', 'Nhìn mờ dần, chói hoặc khó đọc có thể do nhiều nguyên nhân. Đục thủy tinh thể, glôcôm và bệnh võng mạc cần cách đánh giá khác nhau; đổi kính không phải lúc nào cũng giải quyết được. Người có đái tháo đường, tăng huyết áp hoặc tiền sử gia đình mắc glôcôm nên hỏi bác sĩ về lịch theo dõi.', '/kien-thuc/dau-hieu-glocom-giai-doan-som/', 'Đọc về phát hiện glôcôm sớm' ),
		array( 'Mắt trong công việc và sinh hoạt', 'Bụi, hóa chất, tia hàn hoặc nhìn màn hình kéo dài có thể làm mắt khó chịu và tạo nguy cơ chấn thương. Dùng kính bảo hộ đúng nguy cơ; nếu mắt đỏ đau, sợ sáng, giảm thị lực hoặc bị hóa chất bắn vào mắt, cần đánh giá sớm thay vì tự nhỏ thuốc không rõ thành phần.', '/kien-thuc/mat-va-cong-viec-nha-may/', 'Đọc về mắt và công việc' ),
	);
	$m = $chu_de[ $chuyen_de ];
	$ra  = '<p class="kv-intro">Hướng dẫn cho người tìm thông tin khám mắt tại ' . esc_html( $ten ) . ', tỉnh Bắc Ninh. Đây là trang thông tin theo nơi cư trú, không phải thông báo bệnh viện có cơ sở tại ' . esc_html( $ten ) . '. Bệnh viện Mắt Hà Nội – Bắc Ninh công bố địa chỉ tại Lô 4, đường Hùng Vương, phường Bắc Giang, tỉnh Bắc Ninh.</p>';
	$ra .= '<h2>Tên địa bàn hiện hành</h2><p>' . esc_html( $mo_ta_nguon ) . ' Vì vậy nếu giấy tờ cũ dùng tên xã, phường hoặc thị trấn trước sắp xếp, bạn nên đối chiếu tên hiện hành trước khi ghi địa chỉ. Tên tỉnh hiện nay là Bắc Ninh; cụm “địa bàn ' . esc_html( $vung ) . ' cũ” trên website chỉ để giúp người đọc nhận ra vùng lịch sử.</p>';
	$ra .= '<h2>Khi nào cần khám mắt?</h2><p>Nếu thị lực mờ dần, kính đang dùng không còn phù hợp, mắt thường xuyên đỏ, cộm hoặc người thân nhận ra trẻ nhìn khác trước, hãy trao đổi với nhân viên y tế để chọn hình thức khám phù hợp. Mô tả rõ mắt nào bị ảnh hưởng, triệu chứng bắt đầu từ khi nào và đã dùng thuốc gì. Đo kính có thể hữu ích, nhưng khám mắt có thể cần đánh giá thêm giác mạc, thủy tinh thể, nhãn áp, võng mạc hoặc thần kinh thị giác theo chỉ định.</p>';
	$ra .= '<h3>' . esc_html( $m[0] ) . '</h3><p>' . esc_html( $m[1] ) . ' <a href="' . esc_url( home_url( $m[2] ) ) . '">' . esc_html( $m[3] ) . '</a>.</p>';
	$ra .= '<h2>Dấu hiệu cần đi cấp cứu</h2><p>Mất thị lực đột ngột, đau mắt dữ dội kèm buồn nôn, chớp sáng cùng nhiều chấm bay mới, tấm màn tối che tầm nhìn, chấn thương vật sắc hoặc hóa chất vào mắt không nên chờ lịch khám thông thường. Hãy đến cơ sở cấp cứu phù hợp gần nhất, không cố đi xa chỉ vì đã chọn một địa chỉ khám từ trước. Với hóa chất, bắt đầu rửa mắt bằng nước sạch ngay và tìm hỗ trợ y tế. <a href="' . esc_url( home_url( '/kien-thuc/bong-vong-mac-dau-hieu-canh-bao/' ) ) . '">Xem dấu hiệu bong võng mạc</a>.</p>';
	$ra .= '<h2>Chuẩn bị nếu đến Bệnh viện Mắt Hà Nội – Bắc Ninh</h2><p>Trước khi di chuyển từ ' . esc_html( $ten ) . ', kiểm tra <a href="' . esc_url( home_url( '/lien-he/' ) ) . '">địa chỉ và giờ tiếp nhận</a>. Mang kính đang đeo, đơn kính cũ, kết quả khám hoặc ảnh chụp mắt trước đây, danh sách thuốc, hồ sơ bệnh nền và giấy tờ thanh toán cần thiết. Hỏi trước liệu có thể phải nhỏ giãn đồng tử; nếu có, bạn có thể cần người đưa về vì nhìn mờ hoặc chói tạm thời. Chi phí, bảo hiểm và chỉ định kiểm tra phụ thuộc từng trường hợp, không được xác định chỉ từ địa chỉ cư trú.</p>';
	$ra .= '<h2>Đọc thêm và nguồn</h2><p>Xem <a href="' . esc_url( home_url( $nen ) ) . '">hướng dẫn cho địa bàn ' . esc_html( $vung ) . '</a>, <a href="' . esc_url( home_url( '/khu-vuc/' ) ) . '">danh sách toàn tỉnh</a> và <a href="' . esc_url( home_url( '/kien-thuc/' ) ) . '">thư viện kiến thức nhãn khoa</a>. Địa danh dựa trên <a href="https://xaydungchinhsach.chinhphu.vn/toan-van-nghi-quyet-so-1658-nq-ubtvqh15-sap-xep-cac-dvhc-cap-xa-cua-tinh-bac-ninh-nam-2025-119250616193651987.htm">Nghị quyết 1658/NQ-UBTVQH15</a>; nguyên tắc khám và triệu chứng tham khảo <a href="https://www.nei.nih.gov/eye-health-information/healthy-vision/finding-eye-doctor/get-dilated-eye-exam">National Eye Institute</a>. Bản thảo ngày 24/09/2026 chờ bác sĩ chuyên khoa duyệt trước công bố. Trang không chẩn đoán, kê đơn hoặc xác nhận cơ sở khám tại địa phương.</p>';
	$ra .= '<div class="eyecare-faq"><h2>Câu hỏi thường gặp</h2>';
	foreach ( eyecare_khu_vuc_faq_dia_ban( $dia_ban ) as $cap ) {
		$ra .= '<h3>' . esc_html( $cap['hoi'] ) . '</h3><p>' . esc_html( $cap['dap'] ) . '</p>';
	}
	$ra .= '</div>';
	return $ra;
}

function eyecare_khu_vuc_faq_dia_ban( $dia_ban ) {
	$ten = $dia_ban['loai'] . ' ' . $dia_ban['ten'];
	return array(
		array( 'hoi' => 'Đây có phải chi nhánh bệnh viện tại ' . $ten . '?', 'dap' => 'Không. Đây là nội dung hướng dẫn theo địa bàn cư trú; địa chỉ bệnh viện đã công bố ở phường Bắc Giang.' ),
		array( 'hoi' => 'Người ở ' . $ten . ' bị mất thị lực đột ngột nên làm gì?', 'dap' => 'Hãy đến cơ sở cấp cứu phù hợp gần nhất để được đánh giá ngay, không chờ lịch khám thông thường.' ),
	);
}

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
	if ( 'dia-ban' === $nguon['loai'] ) {
		global $eyecare_faq_da_gom;
		$eyecare_faq_da_gom = eyecare_khu_vuc_faq_dia_ban( $nguon['dia_ban'] );
		return;
	}
	$tep = eyecare_khu_vuc_tep( $nguon['ma'] );
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
