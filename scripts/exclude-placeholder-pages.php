<?php
/** Exclude six verified placeholder/utility pages from the OBS sitemap option. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'EYECARE_EXCLUDE_PLACEHOLDERS' ) ) {
	throw new RuntimeException( 'Explicit WP-CLI gate required.' );
}
$ids = array( 7, 10, 53, 59, 63, 64 );
$slugs = array( 'tam-nhin-gia-tri', 'ho-so-phap-ly', 'tin-tuc', 'tuyen-dung', 'trang-dang-ky', 'dat-lich-thanh-cong' );
foreach ( $ids as $index => $id ) {
	$post = get_post( $id );
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type || 'publish' !== $post->post_status || $slugs[$index] !== $post->post_name ) {
		throw new RuntimeException( 'Page identity mismatch: ' . $id );
	}
}
$opts = get_option( 'obs_seo_sitemap', array() );
if ( ! is_array( $opts ) ) {
	throw new RuntimeException( 'OBS sitemap option is not an array.' );
}
$existing = array_filter( array_map( 'intval', explode( ',', (string) ( $opts['exclude_ids'] ?? '' ) ) ) );
$merged = array_values( array_unique( array_merge( $existing, $ids ) ) );
sort( $merged, SORT_NUMERIC );
if ( '1' !== getenv( 'EYECARE_EXCLUDE_PLACEHOLDERS_EXECUTE' ) ) {
	WP_CLI::success( 'Six placeholder pages verified; existing sitemap exclusions: ' . count( $existing ) . '; no writes.' );
	return;
}
$backup_dir = realpath( (string) getenv( 'EYECARE_LINK_TRUST_BACKUP' ) );
if ( ! $backup_dir || false === file_put_contents( $backup_dir . '/original-obs-sitemap-option.json', wp_json_encode( $opts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) ) {
	throw new RuntimeException( 'Cannot back up existing sitemap option.' );
}
$opts['exclude_ids'] = implode( ',', $merged );
update_option( 'obs_seo_sitemap', $opts );
WP_CLI::success( 'Excluded six placeholder/utility pages from OBS sitemap; preserved prior exclusions.' );
