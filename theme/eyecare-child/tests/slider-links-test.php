<?php
/** Read-only checks for optional homepage slider links. Run with wp eval-file. */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$errors = 0;
$check  = static function ( $label, $passed ) use ( &$errors ) {
	echo ( $passed ? 'PASS  ' : 'FAIL  ' ) . $label . "\n";
	if ( ! $passed ) {
		++$errors;
	}
};

$check( 'blank URL stays unlinked', '' === eyecare_slider_chuan_hoa_lien_ket( '' ) );
$check( 'unsafe scheme rejected', '' === eyecare_slider_chuan_hoa_lien_ket( 'javascript:alert(1)' ) );
$check( 'relative URL rejected', '' === eyecare_slider_chuan_hoa_lien_ket( '/kien-thuc/' ) );
$valid = 'https://mathanoibacninh.com/kien-thuc/dau-hieu-can-kham-ngay/?ref=slider';
$check( 'HTTP(S) URL with query retained', $valid === eyecare_slider_chuan_hoa_lien_ket( $valid ) );

$slide = array(
	'id'       => 991,
	'src'      => 'https://mathanoibacninh.com/wp-content/uploads/example.webp',
	'srcset'   => false,
	'alt'      => 'Minh họa khám mắt',
	'rong'     => 1600,
	'cao'      => 609,
	'lien_ket' => $valid,
);
$linked = eyecare_slider_html_anh( $slide, 0 );
$check( 'linked image has anchor and descriptive accessible name', str_contains( $linked, 'href="' . $valid . '"' ) && str_contains( $linked, 'aria-label="Xem nội dung liên quan: Minh họa khám mắt"' ) );
$check( 'first image keeps priority', str_contains( $linked, 'fetchpriority="high"' ) && ! str_contains( $linked, 'srcset=""' ) );

$slide['lien_ket'] = '';
$plain = eyecare_slider_html_anh( $slide, 1 );
$check( 'empty URL outputs image without anchor', ! str_contains( $plain, '<a ' ) && str_contains( $plain, 'loading="lazy"' ) );

$slide['lien_ket'] = 'javascript:alert(1)';
$check( 'unsafe renderer URL cannot create anchor', ! str_contains( eyecare_slider_html_anh( $slide, 1 ), '<a ' ) );

$config = eyecare_slider_doc_cau_hinh();
$check( 'existing config normalized with link map', isset( $config['lien_ket'] ) && is_array( $config['lien_ket'] ) );

echo $errors ? "FAILURES: $errors\n" : "All slider link checks passed.\n";
exit( $errors ? 1 : 0 );
