<?php
/**
 * Create the public licence page at /giay-phep-hoat-dong/.
 *
 * The child theme ships page-giay-phep-hoat-dong.php, which WordPress applies
 * automatically to a page whose slug is giay-phep-hoat-dong. All visible text
 * lives in the template, so the page record only needs a title and slug.
 *
 * Dry run by default. Apply with EYECARE_GPHD_APPLY=yes after a DB backup.
 * Run through wp eval-file under the deployment lock.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$slug  = 'giay-phep-hoat-dong';
$title = 'Giấy phép hoạt động';
$apply = 'yes' === getenv( 'EYECARE_GPHD_APPLY' );
$state = getenv( 'EYECARE_GPHD_STATE' ) ?: '/home/jwhxtzru/backups/gphd-page-created.json';

/* The theme must already carry the licence number, otherwise the page would
   publish as an empty shell saying nothing is published yet. */
if ( ! function_exists( 'eyecare_co_gphd' ) || ! eyecare_co_gphd() ) {
	WP_CLI::error( 'Theme has no licence number yet; deploy the theme files first.' );
}

if ( ! locate_template( 'page-giay-phep-hoat-dong.php' ) ) {
	WP_CLI::error( 'Template page-giay-phep-hoat-dong.php not found in the active theme.' );
}

$existing = get_page_by_path( $slug );

if ( $existing instanceof WP_Post ) {
	WP_CLI::log( sprintf( 'Page already exists: ID %d, status %s.', $existing->ID, $existing->post_status ) );

	if ( 'publish' === $existing->post_status ) {
		WP_CLI::success( 'Nothing to do.' );
		exit( 0 );
	}

	if ( ! $apply ) {
		WP_CLI::log( 'Dry run: would publish the existing page. No WordPress changes.' );
		exit( 0 );
	}

	$before = $existing->post_status;
	$ok = wp_update_post(
		array(
			'ID'          => $existing->ID,
			'post_status' => 'publish',
		),
		true
	);

	if ( is_wp_error( $ok ) ) {
		WP_CLI::error( 'Publish failed: ' . $ok->get_error_message() );
	}

	file_put_contents(
		$state,
		wp_json_encode(
			array( 'id' => $existing->ID, 'status_before' => $before, 'created' => false ),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		)
	);

	WP_CLI::success( sprintf( 'Published existing page ID %d (was %s).', $existing->ID, $before ) );
	exit( 0 );
}

/* Guard against a slug collision with any other post type before inserting. */
$clash = get_posts(
	array(
		'name'        => $slug,
		'post_type'   => 'any',
		'post_status' => 'any',
		'numberposts' => 1,
		'fields'      => 'ids',
	)
);

if ( $clash ) {
	WP_CLI::error( sprintf( 'Slug %s already used by post ID %d. Stop and inspect.', $slug, (int) $clash[0] ) );
}

if ( ! $apply ) {
	WP_CLI::log( sprintf( 'Dry run: would create page "%s" at /%s/ using the theme template.', $title, $slug ) );
	WP_CLI::log( 'Dry run only. No WordPress changes.' );
	exit( 0 );
}

$id = wp_insert_post(
	array(
		'post_type'      => 'page',
		'post_title'     => $title,
		'post_name'      => $slug,
		'post_status'    => 'publish',
		/* Text lives in the template, so the record body stays empty on
		   purpose. inc/giay-phep-seo.php supplies title, description and OG
		   tags, which an empty body would otherwise leave blank. */
		'post_content'   => '',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	),
	true
);

if ( is_wp_error( $id ) ) {
	WP_CLI::error( 'Insert failed: ' . $id->get_error_message() );
}

file_put_contents(
	$state,
	wp_json_encode(
		array( 'id' => (int) $id, 'status_before' => null, 'created' => true ),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	)
);

WP_CLI::success( sprintf( 'Created and published page ID %d at %s', (int) $id, get_permalink( (int) $id ) ) );
