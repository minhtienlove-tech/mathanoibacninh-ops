<?php
/**
 * Trang chủ — slider đầu trang và các hàm phụ trợ.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Biểu tượng SVG nội tuyến cho thẻ lĩnh vực khám.
 *
 * VÌ SAO SVG NỘI TUYẾN, KHÔNG NẠP PHÔNG ICON:
 * Phông icon (Font Awesome…) là ~70KB tải thêm cho vài hình. SVG vẽ thẳng vào
 * HTML thì 0 byte tải thêm, tô màu theme bằng currentColor, và trình đọc màn
 * hình bỏ qua nhờ aria-hidden — đúng vì đây là hình trang trí cạnh chữ đã có.
 *
 * Nét vẽ giữ tối giản một nét (stroke, không tô đặc) cho đồng bộ với "vạch đọc"
 * xanh của thiết kế. Không có khoá khớp thì trả về hình con mắt mặc định.
 *
 * @param string $khoa Khoá biểu tượng: mat | khuc-xa | thuy-tinh-the | glocom | vong-mac | tre-em.
 * @return string Thẻ <svg> đã sẵn aria-hidden.
 */
function eyecare_bieu_tuong( $khoa ) {

	$paths = array(
		// Con mắt tổng quát.
		'mat'           => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
		// Tật khúc xạ — cặp kính.
		'khuc-xa'       => '<circle cx="6" cy="15" r="3.5"/><circle cx="18" cy="15" r="3.5"/><path d="M9.5 14.5c1-1.5 4-1.5 5 0M2.5 12l2-4M21.5 12l-2-4"/>',
		// Đục thuỷ tinh thể — mắt có màn mờ.
		'thuy-tinh-the' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/><path d="M8 8l8 8"/>',
		// Glôcôm — mắt với mũi tên áp lực.
		'glocom'        => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="2.5"/><path d="M12 4v2M12 18v2M4 12h2M18 12h2"/>',
		// Dịch kính – võng mạc — đáy mắt.
		'vong-mac'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/>',
		// Mắt trẻ em — trái tim nhỏ trong mắt.
		'tre-em'        => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><path d="M12 14.5c-1.4-1.2-2.6-2-2.6-3.2A1.4 1.4 0 0 1 12 10.4a1.4 1.4 0 0 1 2.6.9c0 1.2-1.2 2-2.6 3.2z"/>',
	);

	$noi_dung = isset( $paths[ $khoa ] ) ? $paths[ $khoa ] : $paths['mat'];

	return '<svg class="eyecare-bieu-tuong" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
		. 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. $noi_dung . '</svg>';
}

/**
 * Lấy các ảnh đủ điều kiện làm slider đầu trang.
 *
 * 🔴 ĐIỀU KIỆN LỌC LÀ "CÓ CHỮ ALT", VÀ ĐÂY LÀ CỬA KIỂM CHỨ KHÔNG PHẢI
 * CHUYỆN TRỢ NĂNG THUẦN.
 *
 * Ảnh trong thư viện đến từ site cũ. Agent xem được khổ ảnh và bố cục nhưng
 * KHÔNG đọc chắc được chữ nhỏ in trong ảnh, mà chữ nhỏ mới là chỗ nguy hiểm:
 *   - số tổng đài cũ 098 842 88 68 / 0988 428 868 (đã bị QĐ-02 loại)
 *   - cụm bị cấm theo Luật Quảng cáo: "bậc nhất", "hàng đầu", "số 1"
 *   - logo hoặc tên đối thủ Hikari Eye Care TP HCM
 *
 * Người xem ảnh rồi mới điền alt. Nên "có alt" = "đã có người kiểm ảnh này".
 * Ảnh chưa ai kiểm thì không lên trang chủ, và trang tự rơi về hero chữ —
 * mất phần hình, không mất phần đúng.
 *
 * Cách điền alt: Thư viện > chọn ảnh > ô "Văn bản thay thế". Hoặc điền vào
 * scripts/nhap-anh-slider.php rồi chạy lại.
 *
 * @return array Danh sách mảng: id, src, srcset, alt, rong, cao.
 */
function eyecare_anh_slider() {

	/* ---- Nguồn 1: ảnh đã CHỌN ở trang cài đặt (Giao diện → Slider trang chủ)
	   Đây là nguồn chính từ 08/2026. Người chọn ảnh trong trang cài đặt đã tự
	   nhìn và kiểm ảnh, nên KHÔNG lọc lại theo alt hay theo khổ ở đây — giữ đúng
	   thứ tự người dùng đã kéo. Chỉ bỏ ảnh đã bị xoá khỏi Thư viện. */
	if ( function_exists( 'eyecare_slider_doc_cau_hinh' ) ) {

		$ch = eyecare_slider_doc_cau_hinh();

		if ( ! empty( $ch['anh'] ) ) {

			$ra = array();

			foreach ( $ch['anh'] as $id ) {

				$src = wp_get_attachment_image_src( $id, 'full' );
				if ( ! $src ) {
					continue; // Ảnh đã bị xoá khỏi Thư viện.
				}

				$meta = wp_get_attachment_metadata( $id );

				$ra[] = array(
					'id'     => $id,
					'src'    => $src[0],
					'srcset' => wp_get_attachment_image_srcset( $id, 'full' ),
					'alt'    => get_post_meta( $id, '_wp_attachment_image_alt', true ),
					'rong'   => ! empty( $meta['width'] ) ? $meta['width'] : $src[1],
					'cao'    => ! empty( $meta['height'] ) ? $meta['height'] : $src[2],
				);
			}

			if ( ! empty( $ra ) ) {
				return $ra;
			}
		}
	}

	/* ---- Nguồn 2 (dự phòng): tự dò ảnh có alt trong Thư viện ---------------
	   Giữ lại cách cũ cho trường hợp chưa ai vào trang cài đặt chọn ảnh. "Có
	   alt" vẫn là dấu cho biết ảnh đã được người xem kiểm (số tổng đài cũ, cụm
	   so sánh cấm, logo đối thủ — xem chú thích đầu tệp). */
	$anh = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => 8,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '!=',
				),
			),
		)
	);

	$ra = array();

	foreach ( $anh as $a ) {

		$meta = wp_get_attachment_metadata( $a->ID );

		/* Chỉ nhận ảnh khổ ngang rộng. Ảnh dọc hay ảnh vuông đặt làm nền
		   slider thì bị cắt mất phần giữa — đúng chỗ có nội dung. Ngưỡng 1.7
		   là tỉ lệ 16:9 nới xuống một chút. */
		if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
			continue;
		}

		if ( $meta['width'] < 1600 || ( $meta['width'] / $meta['height'] ) < 1.7 ) {
			continue;
		}

		$src = wp_get_attachment_image_src( $a->ID, 'full' );
		if ( ! $src ) {
			continue;
		}

		$ra[] = array(
			'id'     => $a->ID,
			'src'    => $src[0],
			'srcset' => wp_get_attachment_image_srcset( $a->ID, 'full' ),
			'alt'    => get_post_meta( $a->ID, '_wp_attachment_image_alt', true ),
			'rong'   => $meta['width'],
			'cao'    => $meta['height'],
		);
	}

	return $ra;
}

/**
 * In slider đầu trang chủ.
 *
 * Chạy bằng CSS scroll-snap, KHÔNG dùng thư viện JavaScript nào:
 *   - Flatsome có sẵn slider nhưng nó gắn với trình dựng trang UX Builder,
 *     mà trang chủ này không phải bản ghi trang nên không dùng được.
 *   - Nạp thêm một thư viện slider là thêm ~40KB JS cho thứ trượt ảnh.
 *     Người đọc chính của site dùng 3G ở Bắc Ninh và Bắc Giang.
 *
 * 🔴 KHÔNG TỰ ĐỘNG CHẠY. Ảnh tự trượt gây khó cho người đọc chậm và người
 * dùng phím Tab, và WCAG 2.2.2 yêu cầu có cách dừng thứ đang tự động chuyển
 * động. Không tự chạy thì không cần nút dừng, và người đọc giữ quyền điều
 * khiển. Trang chủ bệnh viện không cần hiệu ứng.
 */

/**
 * In hàng nút hành động ở hero: "Đặt lịch khám" + "Gọi tổng đài".
 *
 * Dùng chung cho cả hai nhánh hero (hero-chữ và hero-ảnh) để nút không lệch
 * nhau giữa hai trạng thái.
 *
 * 🔴 VÌ SAO NÚT ĐẶT LỊCH GIỜ HỢP LỆ:
 * Trước đây trang chủ CỐ Ý không có nút đặt lịch vì bệnh viện chưa có Giấy
 * phép hoạt động (B-03). Nay đã có GPHĐ, nút được phép xuất hiện. Nút trỏ tới
 * /dat-lich-kham/ — trang ĐANG PUBLISH và có form thật (đã kiểm), nên không
 * phải liên kết chết.
 *
 * Nút vẫn KHÔNG kèm giá, KHÔNG hứa kết quả, KHÔNG cụm "bậc nhất/hàng đầu":
 * quảng cáo NỘI DUNG dịch vụ khám chữa bệnh còn cần "xác nhận nội dung quảng
 * cáo" của Sở Y tế — cửa riêng với GPHĐ.
 *
 * @param array $tt Dữ liệu thực thể (eyecare_du_lieu_thuc_the()).
 */
function eyecare_hero_hanh_dong( $tt ) {

	$trang_dat_lich = get_page_by_path( 'dat-lich-kham' );

	echo '<div class="eyecare-hero__nut">';

	/* Nút chính chỉ hiện khi trang đặt lịch thật sự publish — tránh dẫn vào
	   liên kết chết nếu đồng bộ đổi trạng thái trang. */
	if ( $trang_dat_lich && 'publish' === get_post_status( $trang_dat_lich ) ) {
		printf(
			'<a class="eyecare-nut eyecare-nut--chinh" href="%s">Đặt lịch khám</a>',
			esc_url( get_permalink( $trang_dat_lich->ID ) )
		);
	}

	printf(
		'<a class="eyecare-nut eyecare-nut--phu" href="tel:%s">Gọi %s</a>',
		esc_attr( $tt['dien_thoai'] ),
		esc_html( $tt['dien_thoai_hien'] )
	);

	echo '</div>';
}

function eyecare_slider_dau_trang() {

	$anh = eyecare_anh_slider();
	$tt  = eyecare_du_lieu_thuc_the();

	/* ---- Hero chữ: dùng khi chưa có ảnh nào được kiểm ------------------ */
	if ( empty( $anh ) ) {

		echo '<section class="eyecare-hero eyecare-hero--chu" aria-label="Giới thiệu">';
		echo '<div class="eyecare-chu__khung">';

		echo '<p class="eyecare-hero__nhan">Nâng niu đôi mắt Việt</p>';
		echo '<h1 class="eyecare-hero__td">' . esc_html( $tt['ten'] ) . '</h1>';

		echo '<p class="eyecare-hero__mt">Khám và điều trị các bệnh về mắt tại '
			. esc_html( $tt['tinh'] ) . '. Mở cửa từ '
			. esc_html( $tt['gio_mo'] ) . ' đến ' . esc_html( $tt['gio_dong'] )
			. ', tất cả các ngày trong tuần.</p>';

		/* Hàng nút hành động: Đặt lịch (trang có form thật) + Gọi. Hợp lệ từ
		   khi có Giấy phép hoạt động. */
		eyecare_hero_hanh_dong( $tt );

		/* Chỉ hiện với người có quyền sửa bài — người đọc thường không thấy. */
		if ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="eyecare-canh-bao"><strong>Cảnh báo cho người biên tập:</strong> '
				. 'slider đang không có ảnh nào. Ảnh chỉ lên trang khi đã điền '
				. '<em>Văn bản thay thế</em> trong Thư viện — đó là dấu cho biết đã có '
				. 'người xem ảnh và kiểm ba thứ: không còn số tổng đài cũ, không có cụm '
				. 'so sánh hơn nhất in trong ảnh, không có logo đối thủ.</p>';
		}

		echo '</div></section>';
		return;
	}

	/* ---- Slider ảnh ---------------------------------------------------- */

	$nhieu = count( $anh ) > 1;

	/* Cấu hình hiệu ứng/tự chạy lấy từ trang cài đặt. Có nhiều hơn 1 ảnh mới
	   bật tự chạy — một ảnh thì không có gì để chuyển. */
	$cfg      = function_exists( 'eyecare_slider_doc_cau_hinh' ) ? eyecare_slider_doc_cau_hinh() : eyecare_slider_mac_dinh();
	$hieu_ung = $nhieu ? $cfg['hieu_ung'] : 'khong';
	$tu_chay  = ( $nhieu && ! empty( $cfg['tu_chay'] ) ) ? 1 : 0;
	$nhip     = max( 4, min( 12, (int) $cfg['nhip'] ) );

	/* Kiểu hero và Ken Burns lấy từ trang cài đặt.
	   - kieu 'tach': chữ trên dải nền xanh riêng phía trên ảnh (mặc định, tương
	     phản đo được chắc chắn).
	   - kieu 'phu': chữ đè lên ảnh, có lớp phủ gradient tối để chữ trắng luôn
	     đọc được bất kể ảnh sáng tối (cảm giác "điện ảnh" như LayerSlider).
	   Ken Burns (ảnh phóng/trôi chậm) là hiệu ứng CSS thuần, tự tắt khi người
	   dùng bật "giảm chuyển động" — xem @media trong style.css. */
	$kieu      = ( 'phu' === $cfg['kieu'] ) ? 'phu' : 'tach';
	$ken_burns = ! empty( $cfg['ken_burns'] ) ? 1 : 0;

	/* Data-* mang cấu hình xuống JS đầu trang. JS là lớp NÂNG CẤP: không có JS
	   thì slider vẫn trượt được bằng scroll-snap + chấm neo (đã chạy từ trước).
	   Có JS thì thêm hiệu ứng mờ/trượt và tự chạy. class--hu-* để CSS chọn kiểu
	   chuyển ảnh; --kieu-* chọn bố cục chữ; --kb bật Ken Burns. */
	printf(
		'<section class="eyecare-hero eyecare-hero--anh eyecare-hero--hu-%s eyecare-hero--kieu-%s%s" aria-label="Hình ảnh bệnh viện" data-hieu-ung="%s" data-tu-chay="%d" data-nhip="%d" data-ken-burns="%d">',
		esc_attr( $hieu_ung ),
		esc_attr( $kieu ),
		$ken_burns ? ' eyecare-hero--kb' : '',
		esc_attr( $hieu_ung ),
		(int) $tu_chay,
		(int) $nhip,
		(int) $ken_burns
	);

	/* Tên bệnh viện phải là <h1> thật, không phải chữ nằm trong ảnh: máy tìm
	   kiếm và trình đọc màn hình đều không đọc được chữ vẽ trong ảnh. */
	echo '<div class="eyecare-hero__loi"><div class="eyecare-chu__khung">';
	echo '<p class="eyecare-hero__nhan">Nâng niu đôi mắt Việt</p>';
	echo '<h1 class="eyecare-hero__td">' . esc_html( $tt['ten'] ) . '</h1>';
	echo '<p class="eyecare-hero__mt">Mở cửa ' . esc_html( $tt['gio_mo'] )
		. ' – ' . esc_html( $tt['gio_dong'] ) . ', tất cả các ngày trong tuần.</p>';
	eyecare_hero_hanh_dong( $tt );
	echo '</div></div>';

	/* Ảnh, chấm chỉ mục và nút tạm dừng cùng nằm trong một khung media để
	   các điều khiển có thể nổi trực tiếp trên ảnh mà không sinh dải rỗng. */
	echo '<div class="eyecare-hero__media">';

	printf(
		'<div class="eyecare-hero__bang"%s>',
		/* Vùng trượt được bằng chuột thì cũng phải trượt được bằng phím. Cho
		   nó nhận tiêu điểm và khai là nhóm, kèm nhãn hướng dẫn. */
		$nhieu ? ' tabindex="0" role="group" aria-label="Trượt ngang để xem thêm ảnh"' : ''
	);

	foreach ( $anh as $i => $a ) {

		echo '<figure class="eyecare-hero__khung" id="anh-' . (int) $a['id'] . '">';

		/* srcset chỉ in khi thật sự có. Ảnh nhập bằng script khi PHP thiếu
		   phần mở rộng GD thì không sinh được cỡ phái sinh nào, và
		   wp_get_attachment_image_srcset() trả về false — in ra srcset=""
		   là HTML sai, một số trình duyệt hiểu thành "không có ảnh nào". */
		$srcset = $a['srcset']
			? sprintf( ' srcset="%s" sizes="(min-width: 1440px) 1360px, 94vw"', esc_attr( $a['srcset'] ) )
			: '';

		printf(
			'<img class="eyecare-hero__anh" src="%s"%s'
				. ' width="%d" height="%d" alt="%s" %s decoding="async">',
			esc_url( $a['src'] ),
			$srcset,
			(int) $a['rong'],
			(int) $a['cao'],
			esc_attr( $a['alt'] ),
			/* Ảnh đầu nạp ngay vì nó nằm trong khung nhìn đầu tiên; ảnh sau
			   nạp lười. Đặt loading="lazy" cho ảnh đầu làm chậm chính chỉ số
			   LCP mà nó đang chiếm. */
			0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'
		);

		echo '</figure>';
	}

	echo '</div>';

	/* Nút trước/sau là lớp nâng cấp bằng JavaScript. Khi JS không chạy, chúng
	   được ẩn và chấm neo bên dưới vẫn điều khiển slider bình thường. */
	if ( $nhieu ) {
		echo '<div class="eyecare-hero__mui-ten" aria-label="Điều khiển ảnh">';
		echo '<button type="button" class="eyecare-hero__nut-chuyen eyecare-hero__truoc" aria-label="Ảnh trước" title="Ảnh trước">'
			. '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m14.5 5-7 7 7 7"/></svg></button>';
		echo '<button type="button" class="eyecare-hero__nut-chuyen eyecare-hero__sau" aria-label="Ảnh tiếp theo" title="Ảnh tiếp theo">'
			. '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9.5 5 7 7-7 7"/></svg></button>';
		echo '</div>';
	}

	/* Chấm chỉ mục: là liên kết neo, không phải nút JavaScript. Bấm thì trình
	   duyệt tự cuộn tới ảnh — chạy được cả khi JS bị lỗi hoặc bị chặn. */
	if ( $nhieu ) {
		echo '<ol class="eyecare-hero__cham" aria-label="Chuyển tới ảnh">';
		foreach ( $anh as $i => $a ) {
			printf(
				'<li><a href="#anh-%d" aria-label="Ảnh %d">%d</a></li>',
				(int) $a['id'],
				(int) $i + 1,
				(int) $i + 1
			);
		}
		echo '</ol>';
	}

	/* Nút tạm dừng: CHỈ in khi bật tự chạy. WCAG 2.2.2 đòi có cách dừng thứ
	   đang tự động chuyển động. Nút ẩn mặc định bằng CSS và chỉ hiện khi JS
	   đã bật (class .eyecare-hero--js trên <section>), vì không có JS thì
	   slider không tự chạy nên cũng không cần nút dừng. */
	if ( $tu_chay ) {
		echo '<div class="eyecare-hero__dieu-khien">';
		echo '<button type="button" class="eyecare-hero__tam-dung" aria-pressed="false" aria-label="Tạm dừng" title="Tạm dừng">'
			. '<span class="eyecare-hero__tam-dung-chu" aria-hidden="true">⏸</span></button>';
		echo '</div>';
	}

	echo '</div>';
	echo '</section>';
}

/**
 * Lấy các trang con ĐANG PUBLISH và CÓ NỘI DUNG THẬT của một trang cha.
 *
 * Vì sao lọc nội dung rỗng: 47 trong 59 trang hiện tại chỉ là vỏ chưa có chữ.
 * Liên kết từ trang chủ tới một trang trắng làm người đọc mất niềm tin nhanh
 * hơn là không có liên kết đó — và với máy tìm kiếm thì đó là nội dung mỏng
 * (cảnh báo S-05).
 *
 * @param string $slug_cha Slug trang cha, ví dụ 'chuyen-khoa'.
 * @param int    $so_toi_da Số trang tối đa trả về.
 * @return WP_Post[]
 */
function eyecare_trang_con_co_noi_dung( $slug_cha, $so_toi_da = 8 ) {

	$cha = get_page_by_path( $slug_cha );
	if ( ! $cha ) {
		return array();
	}

	$con = get_pages(
		array(
			'child_of'    => $cha->ID,
			'parent'      => $cha->ID,
			'post_status' => 'publish',
			'sort_column' => 'menu_order,post_title',
		)
	);

	$ra = array();

	foreach ( $con as $t ) {

		/* Bỏ mã ngắn trước khi đếm: một trang chỉ có [hotline] không phải là
		   trang có nội dung. */
		$chu = trim( wp_strip_all_tags( strip_shortcodes( $t->post_content ) ) );

		if ( '' === $chu ) {
			continue;
		}

		$so_tu = count( preg_split( '/\s+/u', $chu, -1, PREG_SPLIT_NO_EMPTY ) );

		/* Ngưỡng 150 từ: dưới mức đó thì trang chưa trả lời được câu hỏi nào
		   trọn vẹn, kể cả khi đã có chữ. */
		if ( $so_tu < 150 ) {
			continue;
		}

		$ra[] = $t;

		if ( count( $ra ) >= $so_toi_da ) {
			break;
		}
	}

	return $ra;
}

/**
 * Lấy MỌI trang con đã publish của một trang cha, kèm cờ cho biết có nội dung.
 *
 * Khác eyecare_trang_con_co_noi_dung() ở chỗ KHÔNG loại trang rỗng, mà đánh
 * dấu chúng. Dùng cho trang hub (/chuyen-khoa/, /gioi-thieu/, /hoi-dap/…).
 *
 * VÌ SAO HUB CẦN LIỆT KÊ CẢ TRANG RỖNG, TRONG KHI TRANG CHỦ THÌ KHÔNG:
 * Hai chỗ này có nghĩa khác nhau với người đọc.
 *   - Trang chủ là nơi GIỚI THIỆU: dẫn người ta tới trang trắng là hứa rồi
 *     không giữ lời, nên trang chủ chỉ dẫn tới trang có nội dung thật.
 *   - Trang hub là MỤC LỤC của một nhánh: người vào /chuyen-khoa/ muốn biết
 *     bệnh viện có những chuyên khoa nào. Ẩn hết trang chưa viết xong thì hub
 *     thành trang trắng và người đọc kết luận sai rằng bệnh viện không có
 *     chuyên khoa đó. Liệt kê kèm ghi chú "đang biên soạn" nói đúng sự thật ở
 *     cả hai phía: chuyên khoa này có, bài viết thì chưa xong.
 *
 * @param string $slug_cha  Slug trang cha.
 * @param int    $so_toi_da Số trang tối đa.
 * @return array Mảng các mảng: trang (WP_Post), co_chu (bool).
 */
function eyecare_trang_con_hub( $slug_cha, $so_toi_da = 14 ) {

	$cha = get_page_by_path( $slug_cha );
	if ( ! $cha ) {
		return array();
	}

	$con = get_pages(
		array(
			'child_of'    => $cha->ID,
			'parent'      => $cha->ID,
			'post_status' => 'publish',
			'sort_column' => 'menu_order,post_title',
			'number'      => $so_toi_da,
		)
	);

	$ra = array();

	foreach ( $con as $t ) {
		$chu   = trim( wp_strip_all_tags( strip_shortcodes( $t->post_content ) ) );
		$so_tu = '' === $chu ? 0 : count( preg_split( '/\s+/u', $chu, -1, PREG_SPLIT_NO_EMPTY ) );

		$ra[] = array(
			'trang'  => $t,
			'co_chu' => $so_tu >= 150,
		);
	}

	return $ra;
}
