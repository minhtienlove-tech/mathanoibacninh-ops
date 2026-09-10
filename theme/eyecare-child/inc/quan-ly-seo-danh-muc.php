<?php
/**
 * Nội dung dài, metadata SEO và schema cho category/tag.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Danh sách trường term meta được quản lý.
 *
 * @return array<string,string>
 */
function eyecare_truong_seo_danh_muc() {
	return array(
		'_eyecare_seo_title'        => 'Tiêu đề SEO',
		'_eyecare_seo_description'  => 'Mô tả SEO',
		'_eyecare_tu_khoa_chinh'    => 'Từ khóa chính',
		'_eyecare_tu_khoa_phu'      => 'Từ khóa phụ',
		'_eyecare_tu_khoa_semantic' => 'Từ khóa ngữ nghĩa',
		'_eyecare_tu_khoa_dai'      => 'Từ khóa dài',
	);
}

/**
 * Đăng ký term meta để WordPress quản lý kiểu dữ liệu và REST an toàn.
 */
function eyecare_dang_ky_meta_danh_muc() {
	foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
		register_term_meta(
			$taxonomy,
			'_eyecare_noi_dung_dai',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => static function () {
					return current_user_can( 'manage_categories' );
				},
			)
		);

		foreach ( array_keys( eyecare_truong_seo_danh_muc() ) as $key ) {
			register_term_meta(
				$taxonomy,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => static function () {
						return current_user_can( 'manage_categories' );
					},
				)
			);
		}
	}
}
add_action( 'init', 'eyecare_dang_ky_meta_danh_muc' );

/**
 * Trường thêm mới category/tag.
 *
 * @param string $taxonomy Taxonomy hiện tại.
 */
function eyecare_them_truong_danh_muc( $taxonomy ) {
	wp_nonce_field( 'eyecare_luu_seo_danh_muc', 'eyecare_seo_danh_muc_nonce' );
	?>
	<div class="form-field">
		<label for="eyecare_noi_dung_dai">Nội dung hướng dẫn dài</label>
		<textarea name="eyecare_noi_dung_dai" id="eyecare_noi_dung_dai" rows="12"></textarea>
		<p>Hỗ trợ HTML an toàn và shortcode [faq]. Nội dung hiển thị sau danh sách bài viết.</p>
	</div>
	<?php foreach ( eyecare_truong_seo_danh_muc() as $key => $label ) : ?>
		<div class="form-field">
			<label for="<?php echo esc_attr( ltrim( $key, '_' ) ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="text" name="<?php echo esc_attr( ltrim( $key, '_' ) ); ?>" id="<?php echo esc_attr( ltrim( $key, '_' ) ); ?>" value="">
		</div>
	<?php endforeach; ?>
	<?php
}
add_action( 'category_add_form_fields', 'eyecare_them_truong_danh_muc' );
add_action( 'post_tag_add_form_fields', 'eyecare_them_truong_danh_muc' );

/**
 * Trường chỉnh sửa category/tag.
 *
 * @param WP_Term $term Term hiện tại.
 */
function eyecare_sua_truong_danh_muc( $term ) {
	wp_nonce_field( 'eyecare_luu_seo_danh_muc', 'eyecare_seo_danh_muc_nonce' );
	$noi_dung = get_term_meta( $term->term_id, '_eyecare_noi_dung_dai', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="eyecare_noi_dung_dai">Nội dung hướng dẫn dài</label></th>
		<td>
			<?php
			wp_editor(
				$noi_dung,
				'eyecare_noi_dung_dai',
				array(
					'textarea_name' => 'eyecare_noi_dung_dai',
					'textarea_rows' => 18,
					'media_buttons' => false,
				)
			);
			?>
			<p class="description">Nội dung hiển thị sau danh sách bài; dùng [faq] để hiển thị FAQ và sinh schema khớp nội dung thật.</p>
		</td>
	</tr>
	<?php foreach ( eyecare_truong_seo_danh_muc() as $key => $label ) : ?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( ltrim( $key, '_' ) ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input type="text" class="regular-text" name="<?php echo esc_attr( ltrim( $key, '_' ) ); ?>" id="<?php echo esc_attr( ltrim( $key, '_' ) ); ?>" value="<?php echo esc_attr( get_term_meta( $term->term_id, $key, true ) ); ?>"></td>
		</tr>
	<?php endforeach; ?>
	<?php
}
add_action( 'category_edit_form_fields', 'eyecare_sua_truong_danh_muc' );
add_action( 'post_tag_edit_form_fields', 'eyecare_sua_truong_danh_muc' );

/**
 * Lưu trường category/tag.
 *
 * @param int $term_id ID term.
 */
function eyecare_luu_truong_danh_muc( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	$nonce = isset( $_POST['eyecare_seo_danh_muc_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['eyecare_seo_danh_muc_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'eyecare_luu_seo_danh_muc' ) ) {
		return;
	}

	if ( isset( $_POST['eyecare_noi_dung_dai'] ) ) {
		$noi_dung = wp_kses_post( wp_unslash( $_POST['eyecare_noi_dung_dai'] ) );
		update_term_meta( $term_id, '_eyecare_noi_dung_dai', $noi_dung );
		update_term_meta( $term_id, '_eyecare_so_tu', (string) str_word_count( wp_strip_all_tags( strip_shortcodes( $noi_dung ) ) ) );
	}

	foreach ( array_keys( eyecare_truong_seo_danh_muc() ) as $key ) {
		$form_key = ltrim( $key, '_' );
		if ( isset( $_POST[ $form_key ] ) ) {
			update_term_meta( $term_id, $key, sanitize_text_field( wp_unslash( $_POST[ $form_key ] ) ) );
		}
	}
}
add_action( 'created_category', 'eyecare_luu_truong_danh_muc' );
add_action( 'edited_category', 'eyecare_luu_truong_danh_muc' );
add_action( 'created_post_tag', 'eyecare_luu_truong_danh_muc' );
add_action( 'edited_post_tag', 'eyecare_luu_truong_danh_muc' );

/**
 * Tiêu đề SEO riêng của category/tag.
 *
 * @param string $title Tiêu đề hiện tại.
 * @return string
 */
function eyecare_tieu_de_seo_danh_muc( $title ) {
	if ( ! is_category() && ! is_tag() && ! is_tax() ) {
		return $title;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return $title;
	}

	$seo_title = trim( wp_strip_all_tags( get_term_meta( $term->term_id, '_eyecare_seo_title', true ) ) );
	return '' !== $seo_title ? $seo_title : $title;
}
add_filter( 'pre_get_document_title', 'eyecare_tieu_de_seo_danh_muc', 25 );

/**
 * Meta description và noindex cho archive rỗng.
 */
function eyecare_meta_danh_muc() {
	if ( ! is_category() && ! is_tag() && ! is_tax() ) {
		return;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$mo_ta = trim( wp_strip_all_tags( get_term_meta( $term->term_id, '_eyecare_seo_description', true ) ) );
	if ( '' === $mo_ta ) {
		$mo_ta = trim( wp_strip_all_tags( term_description( $term->term_id, $term->taxonomy ) ) );
	}
	if ( '' !== $mo_ta ) {
		echo '<meta name="description" content="' . esc_attr( $mo_ta ) . '">' . "\n";
	}

	if ( 0 === (int) $term->count || 'chua-phan-loai' === $term->slug ) {
		echo '<meta name="robots" content="noindex, follow">' . "\n";
	}
}
add_action( 'wp_head', 'eyecare_meta_danh_muc', 4 );

/**
 * Schema CollectionPage và FAQPage cho nội dung đang hiển thị ở archive.
 */
function eyecare_schema_danh_muc() {
	if ( ! is_category() && ! is_tag() && ! is_tax() ) {
		return;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$url     = get_term_link( $term );
	$mo_ta   = get_term_meta( $term->term_id, '_eyecare_seo_description', true );
	$noi_dung = get_term_meta( $term->term_id, '_eyecare_noi_dung_dai', true );
	if ( is_wp_error( $url ) ) {
		return;
	}

	$do_thi = array(
		array(
			'@type'       => 'CollectionPage',
			'@id'         => $url . '#collection',
			'url'         => $url,
			'name'        => single_term_title( '', false ),
			'description' => $mo_ta ? wp_strip_all_tags( $mo_ta ) : wp_strip_all_tags( term_description( $term->term_id, $term->taxonomy ) ),
			'inLanguage'  => 'vi-VN',
		),
	);

	if ( $noi_dung && function_exists( 'eyecare_tach_faq' ) && preg_match_all( '#\[faq\](.*?)\[/faq\]#s', $noi_dung, $khop ) ) {
		$cap = array();
		foreach ( $khop[1] as $trong ) {
			$cap = array_merge( $cap, eyecare_tach_faq( $trong ) );
		}

		if ( $cap ) {
			$main_entity = array();
			foreach ( $cap as $c ) {
				$main_entity[] = array(
					'@type'          => 'Question',
					'name'           => $c['hoi'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $c['dap'] ),
					),
				);
			}
			$do_thi[] = array(
				'@type'      => 'FAQPage',
				'@id'        => $url . '#hoi-dap',
				'mainEntity' => $main_entity,
			);
		}
	}

	echo "\n<script type=\"application/ld+json\">\n";
	echo wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $do_thi,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
	echo "\n</script>\n";
}
add_action( 'wp_head', 'eyecare_schema_danh_muc', 7 );
