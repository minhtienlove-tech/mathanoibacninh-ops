<?php
/**
 * Accessible, expandable index of every other published knowledge article.
 *
 * The index contains ordinary HTML links. A transient avoids querying all
 * posts and categories on every single-post request; the current post is
 * excluded only at render time so one cached index serves every article.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return the lightweight, published-only index data. */
function eyecare_tra_cuu_du_lieu() {
	$cache_key = 'eyecare_tra_cuu_bai_viet_v1';
	$rows      = get_transient( $cache_key );
	if ( is_array( $rows ) ) {
		return $rows;
	}

	$ids = get_posts(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => true,
		)
	);
	$rows = array();
	foreach ( $ids as $id ) {
		$terms     = get_the_category( $id );
		$group     = 'Kiến thức nhãn khoa';
		$best_depth = -1;
		foreach ( $terms as $term ) {
			if ( 'kien-thuc' === $term->slug ) {
				continue;
			}
			$depth = count( get_ancestors( $term->term_id, 'category' ) );
			if ( $depth > $best_depth ) {
				$group      = $term->name;
				$best_depth = $depth;
			}
		}
		$rows[] = array(
			'id'    => (int) $id,
			'title' => get_the_title( $id ),
			'url'   => get_permalink( $id ),
			'group' => $group,
		);
	}
	set_transient( $cache_key, $rows, HOUR_IN_SECONDS );
	return $rows;
}

/** Remove the index after article/category changes. */
function eyecare_tra_cuu_xoa_cache() {
	delete_transient( 'eyecare_tra_cuu_bai_viet_v1' );
}
add_action( 'save_post_post', 'eyecare_tra_cuu_xoa_cache' );
add_action( 'deleted_post', 'eyecare_tra_cuu_xoa_cache' );
add_action( 'edited_category', 'eyecare_tra_cuu_xoa_cache' );
add_action( 'created_category', 'eyecare_tra_cuu_xoa_cache' );
add_action( 'delete_category', 'eyecare_tra_cuu_xoa_cache' );
add_action( 'set_object_terms', function ( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( 'category' === $taxonomy && 'post' === get_post_type( $object_id ) ) {
		eyecare_tra_cuu_xoa_cache();
	}
}, 10, 4 );

/** Render links in category groups, skipping the current article. */
function eyecare_tra_cuu_bai_viet_in( $current_id ) {
	$groups = array();
	foreach ( eyecare_tra_cuu_du_lieu() as $row ) {
		if ( (int) $current_id === $row['id'] ) {
			continue;
		}
		$groups[ $row['group'] ][] = $row;
	}
	if ( ! $groups ) {
		return;
	}
	ksort( $groups, SORT_NATURAL | SORT_FLAG_CASE );
	$count = array_sum( array_map( 'count', $groups ) );
	?>
	<aside class="eyecare-tra-cuu" aria-label="Tra cứu toàn bộ bài viết về mắt">
		<details>
			<summary>Tra cứu toàn bộ bài viết <span>(<?php echo esc_html( (string) $count ); ?> bài khác)</span></summary>
			<p>Chọn chủ đề để đọc các bài đã xuất bản. <a href="<?php echo esc_url( home_url( '/kien-thuc/' ) ); ?>">Mở thư viện kiến thức mắt</a>.</p>
			<div class="eyecare-tra-cuu__nhom">
				<?php foreach ( $groups as $name => $articles ) : ?>
					<section aria-label="<?php echo esc_attr( $name ); ?>">
						<h3><?php echo esc_html( $name ); ?> <small>(<?php echo esc_html( (string) count( $articles ) ); ?>)</small></h3>
						<ul>
							<?php foreach ( $articles as $article ) : ?>
								<li><a href="<?php echo esc_url( $article['url'] ); ?>"><?php echo esc_html( $article['title'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endforeach; ?>
			</div>
		</details>
	</aside>
	<?php
}
