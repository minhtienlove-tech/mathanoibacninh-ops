<?php
/** Regression checks for the public patient review data boundary. */

define( 'ABSPATH', __DIR__ );

function add_action() {}
function add_filter() {}
function get_posts() {
	return '1' === ( $GLOBALS['review_meta']['_eyecare_review_public_approved'] ?? '' )
		? array( (object) array( 'ID' => 339 ) )
		: array();
}
function get_the_title() { return 'Tên bệnh nhân'; }
function get_post_thumbnail_id() { return 349; }
function get_post_meta( $post_id, $key ) {
	return $GLOBALS['review_meta'][ $key ] ?? '';
}

require dirname( __DIR__ ) . '/inc/quan-ly-danh-gia.php';

function assert_review( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
}

$GLOBALS['review_meta'] = array(
	'_eyecare_review_public_approved' => '0',
	'_eyecare_review_source_ref' => 'source-1',
	'_eyecare_review_consent_ref' => 'consent-1',
	'_eyecare_review_text' => 'Nội dung đã đối chiếu.',
	'_eyecare_review_rating' => '0',
	'_eyecare_review_media_type' => 'image',
);

assert_review( array() === eyecare_du_lieu_danh_gia(), 'Bài chưa duyệt vẫn hiện công khai.' );
$GLOBALS['review_meta']['_eyecare_review_public_approved'] = '1';
$GLOBALS['review_meta']['_eyecare_review_consent_ref'] = '';
assert_review( array() === eyecare_du_lieu_danh_gia(), 'Bài thiếu tham chiếu đồng ý vẫn hiện công khai.' );
$GLOBALS['review_meta']['_eyecare_review_consent_ref'] = 'consent-1';

$item = eyecare_du_lieu_danh_gia()[0];
assert_review( 'Người bệnh' === $item['ten'], 'Tên riêng bị lộ khi chưa duyệt công khai.' );
assert_review( 0 === $item['image_id'] && 'none' === $item['media_type'], 'Ảnh bị lộ khi chưa duyệt công khai.' );
assert_review( 0 === $item['rating'], 'Tự gán sao khi chưa có điểm đánh giá.' );

$GLOBALS['review_meta']['_eyecare_review_show_name'] = '1';
$GLOBALS['review_meta']['_eyecare_review_show_media'] = '1';
$item = eyecare_du_lieu_danh_gia()[0];
assert_review( 'Tên bệnh nhân' === $item['ten'], 'Tên không hiện dù đã duyệt.' );
assert_review( 349 === $item['image_id'] && 'image' === $item['media_type'], 'Ảnh không hiện dù đã duyệt.' );

echo "Review consent checks passed.\n";
