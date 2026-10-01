<?php
/**
 * Accessible knowledge index and a compact same-topic list on articles.
 *
 * The index contains ordinary HTML links. A transient avoids querying all
 * posts and categories on every request. One cached set serves the full
 * library directory and the short related-article list on single posts.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return the lightweight, published-only index data. */
function eyecare_tra_cuu_du_lieu() {
	$cache_key = 'eyecare_tra_cuu_bai_viet_v2';
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
			'date'  => (int) get_post_time( 'U', true, $id ),
		);
	}
	set_transient( $cache_key, $rows, HOUR_IN_SECONDS );
	return $rows;
}

/** Remove the index after article/category changes. */
function eyecare_tra_cuu_xoa_cache() {
	delete_transient( 'eyecare_tra_cuu_bai_viet_v1' );
	delete_transient( 'eyecare_tra_cuu_bai_viet_v2' );
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

/** Render the complete directory once on the knowledge-library page. */
function eyecare_tra_cuu_toan_bo_in() {
	$groups = array();
	foreach ( eyecare_tra_cuu_du_lieu() as $row ) {
		$groups[ $row['group'] ][] = $row;
	}
	if ( ! $groups ) {
		return;
	}
	ksort( $groups, SORT_NATURAL | SORT_FLAG_CASE );
	$count = array_sum( array_map( 'count', $groups ) );
	?>
	<section class="eyecare-tra-cuu eyecare-tra-cuu--thu-vien" id="toan-bo-bai-viet" aria-label="Tra cứu toàn bộ bài viết về mắt">
		<h2>Tra cứu toàn bộ bài viết <span>(<?php echo esc_html( (string) $count ); ?> bài)</span></h2>
		<p>Chọn chủ đề để xem các bài đã xuất bản.</p>
		<div class="eyecare-tra-cuu__nhom">
			<?php foreach ( $groups as $name => $articles ) : ?>
				<details class="eyecare-tra-cuu__chu-de">
					<summary><?php echo esc_html( $name ); ?> <span>(<?php echo esc_html( (string) count( $articles ) ); ?>)</span></summary>
				<ul>
						<?php foreach ( array_slice( $articles, 0, 8 ) as $article ) : ?>
							<li><a href="<?php echo esc_url( $article['url'] ); ?>"><?php echo esc_html( $article['title'] ); ?></a></li>
						<?php endforeach; ?>
				</ul>
				<?php if ( count( $articles ) > 8 ) : ?>
					<details class="eyecare-tra-cuu__them">
						<summary>Xem thêm <?php echo esc_html( (string) ( count( $articles ) - 8 ) ); ?> bài</summary>
						<ul>
							<?php foreach ( array_slice( $articles, 8 ) as $article ) : ?>
								<li><a href="<?php echo esc_url( $article['url'] ); ?>"><?php echo esc_html( $article['title'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</details>
				<?php endif; ?>
				</details>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/** Keep only relevant, recent links on each article; the library has the full list. */
function eyecare_tra_cuu_bai_viet_in( $current_id ) {
	$rows = eyecare_tra_cuu_du_lieu();
	$group = '';
	foreach ( $rows as $row ) {
		if ( (int) $current_id === $row['id'] ) {
			$group = $row['group'];
			break;
		}
	}
	$related = array_values( array_filter( $rows, static function ( $row ) use ( $current_id, $group ) {
		return (int) $current_id !== $row['id'] && $group === $row['group'];
	} ) );
	usort( $related, static function ( $a, $b ) {
		return $b['date'] <=> $a['date'];
	} );
	$related = array_slice( $related, 0, 6 );
	?>
	<aside class="eyecare-tra-cuu eyecare-tra-cuu--goi-y" aria-label="Bài viết cùng chủ đề">
		<h2>Bài cùng chủ đề</h2>
		<?php if ( $related ) : ?>
			<ul>
				<?php foreach ( $related as $article ) : ?>
					<li><a href="<?php echo esc_url( $article['url'] ); ?>"><?php echo esc_html( $article['title'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<p><a href="<?php echo esc_url( home_url( '/kien-thuc/#toan-bo-bai-viet' ) ); ?>">Tra cứu toàn bộ <?php echo esc_html( (string) count( $rows ) ); ?> bài viết →</a></p>
	</aside>
	<?php
}
