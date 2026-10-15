<?php
if (!defined('WP_CLI') || !WP_CLI) exit(1);
$base = (string)getenv('EYECARE_BACKUP');
if (!is_dir($base)) WP_CLI::error('Missing backup directory');
$slug = 'kham-mat-hoc-duong-fpt-bac-giang';
if (get_posts(['post_type'=>'post','post_status'=>'any','name'=>$slug])) WP_CLI::error('Article already exists');
$term = get_term(26,'category');
if (!$term || is_wp_error($term) || $term->slug !== 'hoat-dong-cong-dong') WP_CLI::error('Category mismatch');
$assets = json_decode(file_get_contents($base.'/media-manifest.json'),true);
$meta = json_decode(file_get_contents($base.'/post.json'),true);
$content = file_get_contents($base.'/content-template.html');
if (count($assets)!==4 || substr_count($content,'{{PHOTO:')!==3 || count(preg_split('/\s+/u',wp_strip_all_tags($content))) < 2400) WP_CLI::error('Content preflight failed');
foreach ([11,144,285,58,57] as $id) {
 if (get_post_status($id)!=='publish') WP_CLI::error('Target not public '.$id);
 $content=str_replace('{{'.$id.'}}',esc_url(get_permalink($id)),$content);
}
$content=str_replace('{{CATEGORY}}',esc_url(get_term_link($term)),$content);
foreach($assets as $asset) {
 $path=$base.'/images/'.$asset['filename'];
 if (!is_file($path) || hash_file('sha256',$path)!==$asset['sha256'] || getimagesize($path)[2]!==IMAGETYPE_WEBP) WP_CLI::error('Image mismatch');
}
if (get_post_status(1686)!=='publish') WP_CLI::error('Community intro missing');
file_put_contents($base.'/community-intro-before.html',get_post_field('post_content',1686));
$post_id=wp_insert_post(wp_slash(['post_type'=>'post','post_status'=>'draft','post_author'=>0,'post_name'=>$slug,'post_title'=>$meta['title'],'post_excerpt'=>$meta['description'],'post_content'=>'','post_category'=>[26],'comment_status'=>'closed','ping_status'=>'closed']),true);
if (is_wp_error($post_id)) WP_CLI::error($post_id->get_error_message());
file_put_contents($base.'/post-id',(string)$post_id);
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
foreach($assets as $index=>$asset) {
 $tmp=wp_tempnam($asset['filename']);
 if (!$tmp || !copy($base.'/images/'.$asset['filename'],$tmp)) WP_CLI::error('Temporary image failed');
 $id=media_handle_sideload(['name'=>$asset['filename'],'tmp_name'=>$tmp,'type'=>'image/webp','error'=>0,'size'=>filesize($tmp)],$post_id,$asset['alt']);
 if(is_wp_error($id)) WP_CLI::error($id->get_error_message());
 file_put_contents($base.'/media-ids.tsv',$asset['source']."\t".$id."\n",FILE_APPEND);
 update_post_meta($id,'_wp_attachment_image_alt',$asset['alt']);
 wp_update_post(wp_slash(['ID'=>$id,'post_excerpt'=>$asset['caption']]));
 if($index===0) {set_post_thumbnail($post_id,$id);continue;}
 $figure='<figure class="eyecare-school-photo">'.wp_get_attachment_image($id,'large',false,['loading'=>'lazy','decoding'=>'async']).'<figcaption>'.esc_html($asset['caption']).'</figcaption></figure>';
 $content=str_replace('{{PHOTO:'.$asset['source'].'}}',$figure,$content);
}
$content=wp_kses_post($content);
if(str_contains($content,'{{') || substr_count($content,'<img ')!==3) WP_CLI::error('Unresolved template');
$result=wp_update_post(wp_slash(['ID'=>$post_id,'post_content'=>$content]),true);
if(is_wp_error($result) || get_post_field('post_content',$post_id)!==$content) WP_CLI::error('Content mismatch; draft retained');
update_post_meta($post_id,'_bvmat_seo_description',$meta['description']);
update_post_meta($post_id,'_eyecare_event_date','2026-09-08');
update_post_meta($post_id,'_eyecare_editorial_source','Hospital-provided photos and FPT Schools event report; verified 2026-10-15. No individual medical reviewer asserted.');
update_post_meta($post_id,'_eyecare_semantic_keywords',$meta['semantic_keywords']);
$result=wp_update_post(['ID'=>$post_id,'post_status'=>'publish'],true);
if(is_wp_error($result)) WP_CLI::error($result->get_error_message());
$url=get_permalink($post_id);
$intro=get_post_field('post_content',1686);
$intro.='\n<p>Xem hoạt động thực tế: <a href="'.esc_url($url).'">Bệnh viện Mắt Hà Nội – Bắc Ninh tham gia khám mắt học đường tại FPT Bắc Giang</a>, cùng hình ảnh và hướng dẫn dành cho gia đình sau buổi kiểm tra.</p>';
$intro=str_replace('\\n',"\n",$intro);
$result=wp_update_post(wp_slash(['ID'=>1686,'post_content'=>$intro]),true);
if(is_wp_error($result)) WP_CLI::error($result->get_error_message());
WP_CLI::success('Published '.$post_id.' '.$url);
