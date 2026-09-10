<?php
/**
 * Dữ liệu schema cho bài hướng dẫn trên trang Liên hệ.
 *
 * Phần chữ dài nằm trong template-parts/noi-dung-lien-he-seo.php để dễ biên
 * tập. FAQ được khai báo ở đây để phần hiển thị và JSON-LD dùng cùng một nguồn.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Câu hỏi thường gặp hiển thị ở cuối bài hướng dẫn Liên hệ.
 *
 * @return array[]
 */
function eyecare_lien_he_faq_data() {
	$d = eyecare_du_lieu_thuc_the();

	return array(
		array(
			'question' => 'Bệnh viện Mắt Hà Nội – Bắc Ninh ở địa chỉ nào?',
			'answer'   => 'Bệnh viện hiện tiếp nhận người bệnh tại ' . $d['dia_chi'] . ', ' . $d['phuong'] . ', ' . $d['tinh'] . '. Người bệnh nên mở bản đồ trên trang Liên hệ và gọi tổng đài ' . $d['dien_thoai_hien'] . ' nếu cần xác nhận hướng đi hoặc thông tin tiếp nhận trong ngày.',
		),
		array(
			'question' => 'Giờ khám mắt tại bệnh viện là khi nào?',
			'answer'   => 'Khung giờ đang được công bố là ' . $d['gio_mo'] . ' – ' . $d['gio_dong'] . ', tất cả các ngày trong tuần. Lịch của từng bác sĩ, ca phẫu thuật hoặc dịch vụ chuyên sâu có thể khác, vì vậy nên gọi trước khi đến để được hướng dẫn chính xác.',
		),
		array(
			'question' => 'Tôi có cần đặt lịch trước khi khám mắt không?',
			'answer'   => 'Đặt lịch trước không phải lúc nào cũng bắt buộc, nhưng giúp bệnh viện chủ động sắp xếp thời gian, bác sĩ và các bước đo kiểm cần thiết. Người bệnh có thể gọi tổng đài hoặc dùng biểu mẫu đặt lịch trên website.',
		),
		array(
			'question' => 'Nên mang theo gì khi đi khám mắt?',
			'answer'   => 'Hãy mang kính đang sử dụng, đơn kính cũ, thuốc nhỏ mắt, kết quả chụp hoặc hồ sơ khám trước đây. Nếu đang đeo kính áp tròng, nên tháo theo hướng dẫn của nhân viên y tế trước khi đo khúc xạ.',
		),
		array(
			'question' => 'Bệnh viện có khám mắt cho trẻ em không?',
			'answer'   => 'Có thể đăng ký khám các vấn đề thị giác ở trẻ như tật khúc xạ, lé, nhược thị hoặc theo dõi cận thị. Phụ huynh nên cho trẻ mang kính đang đeo và ghi lại thời gian sử dụng thiết bị để bác sĩ có thêm thông tin khi tư vấn.',
		),
		array(
			'question' => 'Người cao tuổi nên kiểm tra mắt với tần suất thế nào?',
			'answer'   => 'Tần suất phụ thuộc tuổi, bệnh nền và kết quả lần khám trước. Người có đái tháo đường, tăng huyết áp, glaucoma hoặc từng phẫu thuật mắt nên hỏi bác sĩ về lịch tái khám riêng thay vì tự ước lượng.',
		),
		array(
			'question' => 'Khi nào cần đi khám mắt khẩn cấp?',
			'answer'   => 'Mất thị lực đột ngột, đau mắt dữ dội, chấn thương, nhìn thấy màn đen, nhiều chớp sáng hoặc ruồi bay xuất hiện dày đặc cần được đánh giá trực tiếp càng sớm càng tốt. Không nên chờ tư vấn qua mạng nếu triệu chứng đang tăng nhanh.',
		),
		array(
			'question' => 'Tôi có thể xem trước chi phí dịch vụ ở đâu?',
			'answer'   => 'Trang Bảng giá trên website tổng hợp các nhóm dịch vụ và mức phí đang được công bố. Một số khoản phụ thuộc chỉ định, vật tư hoặc tình trạng thực tế; bệnh viện sẽ tư vấn rõ trước khi thực hiện.',
		),
		array(
			'question' => 'Khám mắt có làm nhìn mờ sau khi nhỏ thuốc không?',
			'answer'   => 'Một số kiểm tra đáy mắt cần nhỏ thuốc giãn đồng tử, có thể gây chói và nhìn gần mờ tạm thời. Nếu có khả năng thực hiện bước này, người bệnh nên đi cùng người thân và không tự lái xe khi thị lực chưa trở lại bình thường.',
		),
		array(
			'question' => 'Tôi cần liên hệ ai nếu thông tin trên website thay đổi?',
			'answer'   => 'Thông tin liên hệ được quản lý tập trung. Hãy gọi ' . $d['dien_thoai_hien'] . ' hoặc gửi yêu cầu qua trang Liên hệ để nhân viên xác nhận địa chỉ, giờ tiếp nhận và hướng dẫn mới nhất trước khi bạn di chuyển.',
		),
	);
}

/**
 * Schema riêng cho phần hướng dẫn y khoa trên trang Liên hệ.
 *
 * @return array[]
 */
function eyecare_schema_lien_he_noi_dung() {
	if ( ! is_page( 'lien-he' ) ) {
		return array();
	}

	$d   = eyecare_du_lieu_thuc_the();
	$id  = get_queried_object_id();
	$url = get_permalink( $id );
	$faq = array();

	foreach ( eyecare_lien_he_faq_data() as $muc ) {
		$faq[] = array(
			'@type'          => 'Question',
			'name'           => $muc['question'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $muc['answer'],
			),
		);
	}

	$do_thi = array(
		array(
			'@type'            => array( 'MedicalWebPage', 'Article' ),
			'@id'              => $url . '#noi-dung-lien-he-seo',
			'url'              => $url,
			'name'             => 'Hướng dẫn liên hệ và khám mắt tại Bệnh viện Mắt Hà Nội – Bắc Ninh',
			'headline'         => 'Bệnh viện mắt Bắc Ninh: địa chỉ, giờ khám và hướng dẫn đi khám',
			'description'      => 'Thông tin thực tế về địa chỉ, giờ làm việc, quy trình liên hệ và các lưu ý trước khi khám mắt tại Bệnh viện Mắt Hà Nội – Bắc Ninh.',
			'inLanguage'       => 'vi-VN',
			'datePublished'    => get_the_date( 'c', $id ),
			'dateModified'     => get_the_modified_date( 'c', $id ),
			'author'           => array( '@id' => home_url( '/' ) . '#bac-si-le-nhu-tung' ),
			'publisher'        => array( '@id' => home_url( '/' ) . '#to-chuc' ),
			'isPartOf'         => array( '@id' => home_url( '/' ) . '#website' ),
			'mainEntityOfPage' => array( '@id' => $url ),
			'medicalAudience'  => 'Patient',
			'about'            => array(
				'@type' => 'MedicalClinic',
				'name'  => $d['ten'],
			),
			'keywords'         => 'bệnh viện mắt Bắc Ninh, bệnh viện mắt Hà Nội Bắc Ninh, khám mắt Bắc Ninh, địa chỉ bệnh viện mắt Bắc Ninh, bác sĩ nhãn khoa Bắc Ninh, giờ khám mắt Bắc Ninh, khám và điều trị bệnh mắt, bệnh viện mắt gần thành phố Bắc Giang',
		),
		array(
			'@type'      => 'FAQPage',
			'@id'        => $url . '#faq-lien-he',
			'url'        => $url . '#cau-hoi-thuong-gap',
			'mainEntity' => $faq,
		),
	);

	// Chỉ khai reviewedBy sau khi có ngày bác sĩ duyệt trong hồ sơ page.
	$ngay_duyet = get_post_meta( $id, '_bvmat_bac_si_duyet', true );
	if ( $ngay_duyet ) {
		$do_thi[0]['reviewedBy']    = array( '@id' => home_url( '/' ) . '#bac-si-le-nhu-tung' );
		$do_thi[0]['lastReviewed']  = $ngay_duyet;
	}

	// Bổ sung thực thể Physician mà MedicalWebPage đang trỏ tới, để máy đọc
	// được đầy đủ người biên soạn ngay cả khi page chưa bật meta y khoa chung.
	if ( function_exists( 'eyecare_schema_bac_si' ) ) {
		$do_thi[] = eyecare_schema_bac_si();
	}

	return $do_thi;
}

/**
 * Meta description cho trang Liên hệ — chỉ thêm khi trang chưa có mô tả từ
 * plugin SEO. Nội dung dùng dữ liệu Admin để không bị cũ khi đổi hotline.
 */
function eyecare_lien_he_seo_meta() {
	if ( ! is_page( 'lien-he' ) ) {
		return;
	}

	$d = eyecare_du_lieu_thuc_the();
	$mo_ta = sprintf(
		'Bệnh viện mắt Bắc Ninh tại %1$s. Xem giờ khám %2$s – %3$s, bản đồ, dịch vụ nhãn khoa và gọi %4$s để được hướng dẫn trước khi đến.',
		$d['dia_chi'] . ', ' . $d['phuong'] . ', ' . $d['tinh'],
		$d['gio_mo'],
		$d['gio_dong'],
		$d['dien_thoai_hien']
	);

	echo '<meta name="description" content="' . esc_attr( $mo_ta ) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $mo_ta ) . '" />' . "\n";
}
add_action( 'wp_head', 'eyecare_lien_he_seo_meta', 3 );
