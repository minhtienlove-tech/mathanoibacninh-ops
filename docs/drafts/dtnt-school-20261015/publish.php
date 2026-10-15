<?php
if (!defined('WP_CLI') || !WP_CLI) exit(1);
$base = (string) getenv('EYECARE_BACKUP');
if (!is_dir($base)) WP_CLI::error('Missing backup directory');
$meta = json_decode(file_get_contents($base . '/post.json'), true);
$assets = json_decode(file_get_contents($base . '/media-manifest.json'), true);
$content = file_get_contents($base . '/content-template.html');
$slug = $meta['slug'];
if (get_posts(['post_type'=>'post', 'post_status'=>'any', 'name'=>$slug])) WP_CLI::error('Article already exists');
$term = get_term(26, 'category');
if (!$term || is_wp_error($term) || $term->slug !== 'hoat-dong-cong-dong') WP_CLI::error('Category mismatch');
if (count($assets)!==5 || substr_count($content, '{{PHOTO:')!==4 || count(preg_split('/\s+/u', wp_strip_all_tags($content))) < 2400 || substr_count($content, 'H: ')!==7) WP_CLI::error('Content preflight failed');
foreach ([11,144,285,58,57,1853] as $id) {
    if (get_post_status($id)!=='publish') WP_CLI::error('Target not public ' . $id);
    $content = str_replace('{{'.$id.'}}', esc_url(get_permalink($id)), $content);
}
$content = str_replace('{{CATEGORY}}', esc_url(get_term_link($term)), $content);
foreach ($assets as $asset) {
    $path = $base.'/images/'.$asset['filename'];
    if (!is_file($path) || hash_file('sha256',$path)!==$asset['sha256'] || getimagesize($path)[2]!==IMAGETYPE_WEBP) WP_CLI::error('Image mismatch');
}
$originals = [];
foreach ([1686,1853] as $id) {
    if (get_post_status($id)!=='publish') WP_CLI::error('Backlink target missing');
    $originals[$id] = get_post_field('post_content',$id);
    file_put_contents($base.'/post-'.$id.'-before.html', $originals[$id]);
}
$post_id = wp_insert_post(wp_slash(['post_type'=>'post','post_status'=>'draft','post_author'=>0,'post_name'=>$slug,'post_title'=>$meta['title'],'post_excerpt'=>$meta['description'],'post_content'=>'','post_category'=>[26],'comment_status'=>'closed','ping_status'=>'closed']), true);
if (is_wp_error($post_id)) WP_CLI::error($post_id->get_error_message());
file_put_contents($base.'/post-id', (string)$post_id);
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
foreach ($assets as $index=>$asset) {
    $tmp = wp_tempnam($asset['filename']);
    if (!$tmp || !copy($base.'/images/'.$asset['filename'],$tmp)) WP_CLI::error('Temporary image failed');
    $id = media_handle_sideload(['name'=>$asset['filename'],'tmp_name'=>$tmp,'type'=>'image/webp','error'=>0,'size'=>filesize($tmp)], $post_id, $asset['alt']);
    if (is_wp_error($id)) WP_CLI::error($id->get_error_message());
    file_put_contents($base.'/media-ids.tsv', $asset['source']."\t".$id."\n", FILE_APPEND);
    update_post_meta($id,'_wp_attachment_image_alt',$asset['alt']);
    $r = wp_update_post(wp_slash(['ID'=>$id,'post_excerpt'=>$asset['caption']]),true);
    if (is_wp_error($r)) WP_CLI::error($r->get_error_message());
    if ($index===0) { set_post_thumbnail($post_id,$id); continue; }
    $figure = '<figure class="eyecare-school-photo">'.wp_get_attachment_image($id,'large',false,['loading'=>'lazy','decoding'=>'async']).'<figcaption>'.esc_html($asset['caption']).'</figcaption></figure>';
    $content = str_replace('{{PHOTO:'.$asset['source'].'}}',$figure,$content);
}
$content = wp_kses_post($content);
if (str_contains($content,'{{') || substr_count($content,'<img ')!==4) WP_CLI::error('Unresolved template');
$result = wp_update_post(wp_slash(['ID'=>$post_id,'post_content'=>$content]),true);
if (is_wp_error($result) || get_post_field('post_content',$post_id)!==$content) WP_CLI::error('Content mismatch; draft retained');
update_post_meta($post_id,'_bvmat_seo_description',$meta['description']);
update_post_meta($post_id,'_eyecare_event_date',$meta['event_date']);
update_post_meta($post_id,'_eyecare_editorial_source','School event report published 2026-10-13; hospital-provided DTNT workbook aggregates and five photographs. Verified 2026-10-15. No individual medical reviewer asserted.');
update_post_meta($post_id,'_eyecare_semantic_keywords',$meta['semantic_keywords']);
update_post_meta($post_id,'_eyecare_primary_keyword',$meta['primary_keyword']);
file_put_contents($base.'/published-content.html',$content);
$result = wp_update_post(['ID'=>$post_id,'post_status'=>'publish'],true);
if (is_wp_error($result)) WP_CLI::error($result->get_error_message());
$url = get_permalink($post_id);
foreach ($originals as $id=>$before) {
    if (get_post_field('post_content',$id)!==$before) WP_CLI::error('Backlink target changed concurrently');
    $addition = $id===1686
        ? '<p>Xem hoạt động ngày 23/09/2026: <a href="'.esc_url($url).'">Khám mắt học đường tại Trường PTDTNT tỉnh Bắc Ninh</a>, với hình ảnh và số liệu tổng hợp của chương trình.</p>'
        : '<p><strong>Hoạt động liên quan:</strong> <a href="'.esc_url($url).'">Khám mắt học đường tại Trường PTDTNT tỉnh Bắc Ninh</a> – hình ảnh chương trình và hướng dẫn theo dõi dành cho học sinh nội trú.</p>';
    $result = wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$before."\n".$addition]),true);
    if (is_wp_error($result)) WP_CLI::error($result->get_error_message());
    file_put_contents($base.'/backlinks-updated.tsv', $id."\n",FILE_APPEND);
}
WP_CLI::success('Published '.$post_id.' '.$url);
