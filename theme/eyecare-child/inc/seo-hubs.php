<?php
/** Mô tả tìm kiếm cho các trang tổng hợp chưa có bộ xuất metadata riêng. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function eyecare_hub_meta_description() {
	if ( ! is_page() || is_front_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}
	$id = (int) get_queried_object_id();
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		return;
	}

	// Các bộ xuất có sẵn giữ quyền ưu tiên; không tạo thẻ description thứ hai.
	if ( '1' === get_post_meta( $id, '_bvmat_noi_dung_y_khoa', true ) ) {
		return;
	}
	if ( function_exists( 'eyecare_content_plan_seo_post_id' ) && eyecare_content_plan_seo_post_id() &&
		trim( (string) get_post_meta( $id, '_eyecare_meta_description_proposal', true ) ) &&
		! in_array( (string) get_post_meta( $id, '_eyecare_content_plan_published_id', true ), array( '02', '06' ), true ) ) {
		return;
	}

	$descriptions = array(
		'dich-vu' => 'Tìm hiểu các dịch vụ khám, chẩn đoán và điều trị mắt tại Bệnh viện Mắt Hà Nội – Bắc Ninh; xem thông tin tư vấn và cách đặt lịch.',
		'kien-thuc' => 'Kiến thức nhãn khoa dễ hiểu: triệu chứng thường gặp, chăm sóc mắt và những thông tin cần biết trước khi đi khám tại Bệnh viện Mắt Hà Nội – Bắc Ninh.',
		'hoi-dap' => 'Giải đáp các câu hỏi thường gặp về khám mắt, đặt lịch, chi phí và chuẩn bị trước khi đến Bệnh viện Mắt Hà Nội – Bắc Ninh.',
		'dich-vu/tham-my-mat' => 'Thông tin cần hỏi trước khi tư vấn thẩm mỹ mắt: đánh giá trước can thiệp, phạm vi dịch vụ, chi phí và cách liên hệ Bệnh viện Mắt Hà Nội – Bắc Ninh.',
		'chuyen-khoa/mat-tre-em' => 'Tìm hiểu các vấn đề mắt ở trẻ em, dấu hiệu cần chú ý và cách chuẩn bị khi đưa trẻ đến khám tại Bệnh viện Mắt Hà Nội – Bắc Ninh.',
		'chuyen-khoa/mat-nguoi-cao-tuoi' => 'Thông tin khám mắt cho người cao tuổi: thay đổi thị lực, các vấn đề thường gặp và chuẩn bị trước khi đến Bệnh viện Mắt Hà Nội – Bắc Ninh.',
		'chuyen-khoa/glocom' => 'Tìm hiểu glôcôm, dấu hiệu cần chú ý và việc thăm khám, theo dõi theo chỉ định bác sĩ tại Bệnh viện Mắt Hà Nội – Bắc Ninh.',
	);
	$uri = trim( (string) get_page_uri( $id ), '/' );
	if ( ! isset( $descriptions[ $uri ] ) ) {
		return;
	}

	$description = trim( (string) get_post_meta( $id, '_bvmat_seo_description', true ) );
	if ( '' === $description ) {
		$description = trim( (string) get_post_field( 'post_excerpt', $id ) );
	}
	if ( '' === $description ) {
		$description = $descriptions[ $uri ];
	}
	$description = html_entity_decode( $description, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$description = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $description ) ) ) );
	if ( '' !== $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'eyecare_hub_meta_description', 6 );
