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
	$description = trim( (string) get_post_meta( $id, '_eyecare_meta_description_proposal', true ) );
	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}, 100 );
