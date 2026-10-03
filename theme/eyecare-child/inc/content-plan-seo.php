<?php
/** SEO title and description for the reviewed 20-item content plan. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function eyecare_content_plan_seo_post_id() {
	if ( ! is_singular( array( 'post', 'page' ) ) ) {
		return 0;
	}
	$id = get_queried_object_id();
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		return 0;
	}
	return ( get_post_meta( $id, '_eyecare_content_plan_id', true ) ||
		get_post_meta( $id, '_eyecare_content_plan_published_id', true ) ) ? $id : 0;
}

add_filter( 'pre_get_document_title', static function ( $title ) {
	$id = eyecare_content_plan_seo_post_id();
	if ( ! $id ) {
		return $title;
	}
	$planned = trim( (string) get_post_meta( $id, '_eyecare_seo_title_proposal', true ) );
	return $planned ?: $title;
}, 100 );

add_action( 'wp_head', static function () {
	$id = eyecare_content_plan_seo_post_id();
	if ( ! $id ) {
		return;
	}
	// These pages already emit one managed description from the child theme.
	if ( in_array( (string) get_post_meta( $id, '_eyecare_content_plan_published_id', true ), array( '02', '06' ), true ) ) {
		return;
	}
	$description = trim( (string) get_post_meta( $id, '_eyecare_meta_description_proposal', true ) );
	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}, 100 );

/**
 * OBS publishes Open Graph descriptions but does not emit a normal meta
 * description for many posts. Reuse each post's own editorial summary rather
 * than a site-wide or keyword-stuffed template.
 */
function eyecare_post_meta_description_fallback() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$id = (int) get_queried_object_id();
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		return;
	}

	// The content plan already prints its hand-written description at priority 100.
	if ( eyecare_content_plan_seo_post_id() &&
		trim( (string) get_post_meta( $id, '_eyecare_meta_description_proposal', true ) ) ) {
		return;
	}

	$from_excerpt = false;
	$from_title = false;
	$description = trim( (string) get_post_meta( $id, '_bvmat_seo_description', true ) );
	if ( '' === $description ) {
		$description = trim( (string) get_post_field( 'post_excerpt', $id ) );
		$from_excerpt = '' !== $description;
	}
	if ( '' === $description ) {
		// Unreviewed body copy can contain medical claims that should not be
		// promoted into search snippets automatically. The title is the safe,
		// post-specific fallback until an editor writes an excerpt.
		$from_title = true;
		$title = trim( preg_replace( '/[.!?…]+$/u', '', get_the_title( $id ) ) );
		$description = sprintf(
			'Bài viết về %s. Xem dấu hiệu cần chú ý và thời điểm nên khám tại Bệnh viện Mắt Hà Nội – Bắc Ninh.',
			$title
		);
	}

	$description = html_entity_decode( wp_strip_all_tags( strip_shortcodes( $description ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$description = trim( preg_replace( '/\s+/u', ' ', $description ) );
	if ( ! $from_title ) {
		$description = wp_trim_words( $description, 38, '…' );
	}
	if ( $from_excerpt && mb_strlen( $description, 'UTF-8' ) < 75 ) {
		$description = preg_replace( '/[\s.!?…]+$/u', '', $description );
		$description .= '. Bệnh viện Mắt Hà Nội – Bắc Ninh giải thích khi nào nên khám và cách chuẩn bị.';
	}
	if ( '' !== $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'eyecare_post_meta_description_fallback', 90 );
