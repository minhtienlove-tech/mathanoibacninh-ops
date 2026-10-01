<?php
/** Read-only WP-CLI export of published post/page metadata for editorial planning. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$rows = array();
$posts = get_posts( array(
	'post_type'              => array( 'post', 'page' ),
	'post_status'            => 'publish',
	'posts_per_page'         => -1,
	'orderby'                => 'ID',
	'order'                  => 'ASC',
	'suppress_filters'       => true,
	'update_post_meta_cache' => false,
) );

foreach ( $posts as $post ) {
	$links = array();
	if ( preg_match_all( '/<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\']/iu', $post->post_content, $matches ) ) {
		foreach ( $matches[1] as $href ) {
			$url = html_entity_decode( $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( str_starts_with( $url, '/' ) ) {
				$url = home_url( $url );
			}
			if ( wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
				$links[] = $url;
			}
		}
	}

	$author = get_userdata( (int) $post->post_author );
	$rows[] = array(
		'id'             => (int) $post->ID,
		'title'          => get_the_title( $post ),
		'url'            => get_permalink( $post ),
		'type'           => $post->post_type,
		'status'         => $post->post_status,
		'categories'     => $post->post_type === 'post' ? wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'names' ) ) : array(),
		'author_id'      => (int) $post->post_author,
		'author_display' => $author ? $author->display_name : '',
		'published'      => $post->post_date,
		'modified'       => $post->post_modified,
		'word_count'     => preg_match_all( '/[\p{L}\p{N}]+/u', wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ),
		'internal_links' => array_values( array_unique( $links ) ),
	);
}

echo wp_json_encode( $rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
