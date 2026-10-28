<?php
/** Scoped WordPress content publication; run through publish.sh under the shared lock. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
$base = (string) getenv('EYECARE_BACKUP');
if (!is_dir($base) || !is_file($base . '/database.sql')) WP_CLI::error('Required backup missing');
if (is_file($base . '/publication.json')) WP_CLI::error('A publication record exists; inspect it before resuming');
foreach (file($base . '/expected.sha256', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    [$hash, $relative] = explode('  ', $line, 2);
    if (!preg_match('~^[a-zA-Z0-9/._-]+$~D', $relative) || str_contains($relative, '..') || hash_file('sha256', $base . '/' . $relative) !== $hash) WP_CLI::error('Source hash mismatch');
}
$meta = json_decode(file_get_contents($base . '/post.json'), true);
$expected = json_decode(file_get_contents($base . '/preflight.json'), true);
$content = file_get_contents($base . '/content-template.html');
if (!is_array($meta) || !is_array($expected) || get_posts(['post_type' => 'post', 'post_status' => 'any', 'name' => $meta['slug'], 'numberposts' => 1])) WP_CLI::error('Invalid input or existing article');
if (substr(current_time('mysql'), 0, 10) !== '2026-10-28') WP_CLI::error('Publication date changed; review the dated policy text');
$term = get_term($meta['category_id'], 'category');
if (!$term || is_wp_error($term) || $term->slug !== $meta['category_slug']) WP_CLI::error('Category mismatch');
$image = get_post((int) $meta['featured_media_id']);
if (!$image || $image->post_type !== 'attachment' || !wp_attachment_is_image($image->ID) || $image->ID !== 1889) WP_CLI::error('Featured image mismatch');
$plain = wp_strip_all_tags(str_replace(['[faq]', '[/faq]'], '', $content));
if (str_contains($content, '<h1') || str_contains($content, '【') || count(preg_split('/\s+/u', trim($plain), -1, PREG_SPLIT_NO_EMPTY)) < 1000 || substr_count($content, 'H: ') !== 6) WP_CLI::error('Content preflight failed');
$faq = explode('[/faq]', explode('[faq]', $content)[1] ?? '')[0];
if (!function_exists('eyecare_tach_faq') || count(eyecare_tach_faq($faq)) !== 6) WP_CLI::error('FAQ mismatch');
foreach (['đo độ cận bằng máy', 'công khám bác sĩ', 'soi đáy mắt', 'không giới hạn thời gian', 'https://zalo.me/2368653938911696310'] as $required) {
    if (!str_contains($content, $required)) WP_CLI::error('Missing policy fact');
}
$before = [];
foreach ($expected['backlinks'] as $record) {
    $id = (int) $record['id'];
    if (!in_array($id, $meta['backlink_ids'], true) || get_post_status($id) !== 'publish') WP_CLI::error('Backlink target mismatch');
    $before[$id] = get_post_field('post_content', $id);
    if (hash('sha256', $before[$id]) !== $record['sha256'] || get_permalink($id) !== $record['url']) WP_CLI::error('Backlink target changed since preflight');
    $content = str_replace('{{' . $id . '}}', esc_url(get_permalink($id)), $content);
}
if (count($before) !== 3 || str_contains($content, '{{')) WP_CLI::error('Unresolved internal links');
$content = wp_kses_post($content);
$state = ['post_id' => 0, 'backlinks' => [], 'complete' => false];
$save = function () use (&$state, $base) { file_put_contents($base . '/publication.json', wp_json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); };
$save();
try {
    foreach ($before as $id => $html) file_put_contents($base . '/post-' . $id . '-before.html', $html);
    $post_id = wp_insert_post(wp_slash([
        'post_type' => 'post', 'post_status' => 'draft', 'post_author' => 0,
        'post_name' => $meta['slug'], 'post_title' => $meta['title'],
        'post_excerpt' => $meta['description'], 'post_content' => $content,
        'post_category' => [$meta['category_id']], 'comment_status' => 'closed', 'ping_status' => 'closed',
    ]), true);
    if (is_wp_error($post_id)) throw new RuntimeException($post_id->get_error_message());
    $state['post_id'] = (int) $post_id; $save();
    if (get_post_field('post_content', $post_id) !== $content) throw new RuntimeException('Article readback mismatch');
    set_post_thumbnail($post_id, $meta['featured_media_id']);
    update_post_meta($post_id, '_bvmat_seo_description', $meta['description']);
    update_post_meta($post_id, '_bvmat_tu_khoa_chinh', $meta['focus_keyword']);
    update_post_meta($post_id, '_bvmat_tu_khoa_semantic', implode(', ', $meta['semantic_keywords']));
    update_post_meta($post_id, '_eyecare_primary_keyword', $meta['focus_keyword']);
    update_post_meta($post_id, '_eyecare_semantic_keywords', $meta['semantic_keywords']);
    update_post_meta($post_id, '_eyecare_editorial_source', 'Hospital school-programme policy provided and confirmed by the user on 2026-10-28. Eligibility: school examination records and school list. No signed document, ZBS approval, new expiry date, use limit or named medical reviewer asserted.');
    update_post_meta($post_id, '_eyecare_policy_verified_date', $meta['source_verified_date']);
    if ((int) get_post_thumbnail_id($post_id) !== $meta['featured_media_id'] || get_post_meta($post_id, '_bvmat_seo_description', true) !== $meta['description']) throw new RuntimeException('Article metadata mismatch');
    $result = wp_update_post(['ID' => $post_id, 'post_status' => 'publish'], true);
    $url = get_permalink($post_id);
    $expected_url = home_url('/tin-tuc/hoat-dong-cong-dong/' . $meta['slug'] . '/');
    if (is_wp_error($result) || get_post_status($post_id) !== 'publish' || $url !== $expected_url) throw new RuntimeException('Publication or URL mismatch');
    $state['url'] = $url; $save();
    foreach ($before as $id => $old) {
        if (get_post_field('post_content', $id) !== $old) throw new RuntimeException('Backlink concurrent change');
        $addition = '<p><strong>Chính sách học đường:</strong> Phụ huynh có thể xem <a href="' . esc_url($url) . '">quyền lợi chương trình Mắt Sáng Học Đường năm học 2026–2027</a>, gồm ba hạng mục khám miễn phí và giảm 20% toàn đơn kính cho học sinh tham gia; hai quyền lợi không giới hạn thời gian. Việc tiếp nhận được đối chiếu theo hồ sơ khám tại trường và danh sách nhà trường cung cấp.</p>';
        $after = $old . "\n" . $addition;
        $state['backlinks'][$id] = ['before_sha256' => hash('sha256', $old), 'after_sha256' => hash('sha256', $after)];
        $save();
        $updated = wp_update_post(wp_slash(['ID' => $id, 'post_content' => $after]), true);
        if (is_wp_error($updated) || get_post_field('post_content', $id) !== $after) throw new RuntimeException('Backlink readback mismatch');
    }
    file_put_contents($base . '/content-published.html', $content);
    $state['complete'] = true; $save();
    foreach (array_merge([$url, home_url('/'), home_url('/tin-tuc/'), get_term_link($term)], array_column($expected['backlinks'], 'url')) as $purge) do_action('litespeed_purge_url', $purge);
    WP_CLI::success(wp_json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
} catch (Throwable $e) {
    foreach ($state['backlinks'] as $id => $hashes) {
        if (hash('sha256', get_post_field('post_content', (int) $id)) === $hashes['after_sha256']) wp_update_post(wp_slash(['ID' => (int) $id, 'post_content' => $before[$id]]));
    }
    if ($state['post_id']) wp_update_post(['ID' => $state['post_id'], 'post_status' => 'draft']);
    $state['failure'] = $e->getMessage(); $save();
    WP_CLI::error('Publication retained as draft; selective rollback attempted: ' . $e->getMessage());
}
