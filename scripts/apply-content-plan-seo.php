<?php
/** Attach already-approved SEO copy to the eight retained WordPress URLs. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_APPLY_CONTENT_PLAN_SEO' ) ) {
	throw new RuntimeException( 'Explicit SEO update gate required.' );
}
$manifest_path = (string) getenv( 'EYECARE_CONTENT_MANIFEST' );
$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );
if ( ! is_array( $manifest ) || 20 !== count( $manifest ) ) {
	throw new RuntimeException( 'Expected the complete 20-item manifest.' );
}
$ids = array( '01' => 6, '02' => 58, '03' => 57, '04' => 42, '05' => 285, '06' => 11, '10' => 120, '14' => 140 );
$rows = array();
foreach ( $manifest as $item ) {
	$number = (string) ( $item['id'] ?? '' );
	if ( ! isset( $ids[ $number ] ) ) {
		continue;
	}
	$post_id = $ids[ $number ];
	if ( $number !== get_post_meta( $post_id, '_eyecare_content_plan_published_id', true ) ||
		'publish' !== get_post_status( $post_id ) ||
		! ( $item['seo_title'] ?? '' ) || ! ( $item['meta_description'] ?? '' ) ) {
		throw new RuntimeException( "SEO preflight failed for {$number}." );
	}
	$rows[ $post_id ] = $item;
}
if ( 8 !== count( $rows ) ) {
	throw new RuntimeException( 'Missing existing SEO entries.' );
}
foreach ( $rows as $post_id => $item ) {
	update_post_meta( $post_id, '_eyecare_seo_title_proposal', (string) $item['seo_title'] );
	update_post_meta( $post_id, '_eyecare_meta_description_proposal', (string) $item['meta_description'] );
	WP_CLI::log( 'SEO updated for post=' . $post_id );
}
WP_CLI::success( 'Eight existing URLs received the approved SEO copy.' );
