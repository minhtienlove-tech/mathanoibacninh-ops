<?php
/** WP-CLI only. Hold the shared deploy lock and create database.sql first. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
$base=(string)getenv('EYECARE_BACKUP');
if (!is_dir($base) || !is_file($base.'/database.sql')) WP_CLI::error('Backup missing');
foreach (file($base.'/expected.sha256',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    [$hash,$relative]=explode('  ',$line,2);
    if (!preg_match('~^[a-zA-Z0-9/._-]+$~D',$relative) || str_contains($relative,'..') || hash_file('sha256',$base.'/'.$relative)!==$hash) WP_CLI::error('Source hash mismatch');
}
$m=json_decode(file_get_contents($base.'/post.json'),true);
$template=file_get_contents($base.'/content-template.html');
$assets=json_decode(file_get_contents($base.'/media-manifest.json'),true);
$expected=home_url('/tin-tuc/hoat-dong-cong-dong/kham-mat-hoc-duong-tieu-hoc-canh-thuy/');
$term=get_term((int)$m['category_id'],'category');
if (!$term || is_wp_error($term) || $term->slug!=='hoat-dong-cong-dong' || count($assets)!==4) WP_CLI::error('Category or media mismatch');
$plain=wp_strip_all_tags(str_replace(['[faq]','[/faq]'],'',$template));
if (count(preg_split('/\s+/u',trim($plain),-1,PREG_SPLIT_NO_EMPTY))<2500 || str_contains($template,'<h1')) WP_CLI::error('Article length/structure mismatch');
$faq=explode('[/faq]',explode('[faq]',$template)[1]??'')[0];
if (count(eyecare_tach_faq($faq))!==6) WP_CLI::error('FAQ mismatch');
$progress_file=$base.'/publication.json';
$state=is_file($progress_file)?json_decode(file_get_contents($progress_file),true):['post_id'=>0,'media'=>[],'complete'=>false];
if ($state['complete']) {WP_CLI::success('Already completed: '.$progress_file);return;}
$save=function()use(&$state,$progress_file){file_put_contents($progress_file,wp_json_encode($state,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));};
$existing=get_posts(['post_type'=>'post','post_status'=>'any','name'=>$m['slug'],'numberposts'=>1]);
if ($existing && (int)$state['post_id']!==(int)$existing[0]->ID) WP_CLI::error('Existing unrelated post; do not clone twice');
$backlinks=[
1872=>['<p><em>Nội dung và hình ảnh: Bệnh viện Mắt Hà Nội – Bắc Ninh.</em></p>','<p>Ở bậc tiểu học, sự quan tâm đến thị lực cần được tiếp nối qua việc lắng nghe trẻ và phối hợp với thầy cô. Xem thêm <a href="'.$expected.'">hoạt động khám mắt học đường tại Trường Tiểu học Cảnh Thụy</a> cùng hướng dẫn dành cho phụ huynh sau buổi kiểm tra.</p>'],
1445=>['<h2 id="buoi-kham-12">Chuẩn bị cho buổi khám</h2>','<p>Từ hoạt động tại trường đến bước theo dõi tại nhà, phụ huynh có thể tham khảo <a href="'.$expected.'">chương trình khám mắt tại Trường Tiểu học Cảnh Thụy</a> để hiểu thêm cách gia đình và nhà trường cùng quan tâm đến thị lực của trẻ.</p>']
];
foreach ($backlinks as $id=>[$marker,$paragraph]) {
    $before=get_post_field('post_content',$id);
    if (get_post_status($id)!=='publish' || substr_count($before,$marker)!==1) WP_CLI::error('Backlink preflight failed: '.$id);
    if (!is_file($base.'/post-'.$id.'-before.html')) file_put_contents($base.'/post-'.$id.'-before.html',$before);
}
foreach ($assets as $asset) {
    $path=$base.'/images/'.$asset['filename'];
    if (!is_file($path) || hash_file('sha256',$path)!==$asset['sha256'] || getimagesize($path)[2]!==IMAGETYPE_WEBP) WP_CLI::error('Media mismatch');
    if (substr_count($template,'{{'.$asset['token'].'}}')!==1) WP_CLI::error('Media token mismatch');
}
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$id=(int)$state['post_id'];
if (!$id) {
    $id=wp_insert_post(wp_slash(['post_type'=>'post','post_status'=>'draft','post_author'=>0,'post_name'=>$m['slug'],'post_title'=>$m['title'],'post_excerpt'=>$m['description'],'post_category'=>[(int)$m['category_id']],'comment_status'=>'closed','ping_status'=>'closed']),true);
    if (is_wp_error($id)) WP_CLI::error($id->get_error_message());
    $state['post_id']=$id;$save();
}
$content=$template;
foreach ($assets as $index=>$asset) {
    $mid=(int)($state['media'][$asset['token']]??0);
    if (!$mid) {
        $tmp=wp_tempnam($asset['filename']);
        if (!$tmp || !copy($base.'/images/'.$asset['filename'],$tmp)) WP_CLI::error('Media copy failed');
        $mid=media_handle_sideload(['name'=>$asset['filename'],'tmp_name'=>$tmp,'error'=>0,'size'=>filesize($tmp)],$id,$asset['alt']);
        if (is_wp_error($mid)) WP_CLI::error($mid->get_error_message());
        $state['media'][$asset['token']]=$mid;$save();
        update_post_meta($mid,'_wp_attachment_image_alt',$asset['alt']);
        update_post_meta($mid,'_eyecare_source_url',$asset['source_url']);
        wp_update_post(wp_slash(['ID'=>$mid,'post_excerpt'=>$asset['caption']]));
    }
    if ($index===0) set_post_thumbnail($id,$mid);
    $figure='<figure class="eyecare-school-photo">'.wp_get_attachment_image($mid,'large',false,['loading'=>'lazy','decoding'=>'async']).'<figcaption>'.esc_html($asset['caption']).'</figcaption></figure>';
    $content=str_replace('{{'.$asset['token'].'}}',$figure,$content);
}
$content=wp_kses_post($content);
if (str_contains($content,'{{')) WP_CLI::error('Unresolved placeholder');
$r=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$content]),true);
if (is_wp_error($r) || get_post_field('post_content',$id)!==$content) WP_CLI::error('Content write mismatch');
update_post_meta($id,'_bvmat_seo_description',$m['description']);
update_post_meta($id,'_bvmat_tu_khoa_chinh',$m['focus_keyword']);
update_post_meta($id,'_bvmat_tu_khoa_semantic',implode(', ',$m['semantic_keywords']));
update_post_meta($id,'_eyecare_semantic_keywords',$m['semantic_keywords']);
update_post_meta($id,'_eyecare_editorial_source',$m['source_url'].' | Hospital-owned public Facebook text and four photographs, expanded with user authorization. No invented event date, pupil count, findings, free-service terms, individual doctor or medical reviewer. Educational context links AAPOS, NEI and MedlinePlus.');
file_put_contents($base.'/content-published.html',$content);
$r=wp_update_post(['ID'=>$id,'post_status'=>'publish'],true);
if (is_wp_error($r) || get_permalink($id)!==$expected) WP_CLI::error('Publication/permalink mismatch');
$state['url']=get_permalink($id);$save();
foreach ($backlinks as $bid=>[$marker,$paragraph]) {
    $before=get_post_field('post_content',$bid);
    if (str_contains($before,$paragraph)) continue;
    if (substr_count($before,$marker)!==1) WP_CLI::error('Backlink marker mismatch: '.$bid);
    $after=str_replace($marker,$paragraph."\n".$marker,$before);
    $r=wp_update_post(wp_slash(['ID'=>$bid,'post_content'=>$after]),true);
    if (is_wp_error($r) || get_post_field('post_content',$bid)!==$after) WP_CLI::error('Backlink write failed: '.$bid);
}
$state['complete']=true;$save();
WP_CLI::success(wp_json_encode(['post_id'=>$id,'url'=>$state['url'],'media_count'=>count($state['media']),'backlink_post_ids'=>array_keys($backlinks)],JSON_UNESCAPED_SLASHES));
