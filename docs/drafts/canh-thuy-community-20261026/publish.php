<?php
/** WP-CLI only; two phases, private backup/state, no theme/core/plugin edits. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
$base=(string)getenv('EYECARE_BACKUP');
$mode=(string)getenv('EYECARE_MODE');
if (!is_dir($base) || !is_file($base.'/database.sql') || !in_array($mode,['draft','publish'],true)) WP_CLI::error('Missing backup or explicit phase');
foreach (file($base.'/expected.sha256',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    [$hash,$relative]=explode('  ',$line,2);
    if (!preg_match('~^[a-zA-Z0-9/._-]+$~D',$relative) || str_contains($relative,'..') || hash_file('sha256',$base.'/'.$relative)!==$hash) WP_CLI::error('Source mismatch');
}
$m=json_decode(file_get_contents($base.'/post.json'),true);
$assets=json_decode(file_get_contents($base.'/media-manifest.json'),true);
$template=file_get_contents($base.'/content-template.html');
$expected=home_url('/tin-tuc/hoat-dong-cong-dong/'.$m['slug'].'/');
$term=get_term((int)$m['category_id'],'category');
$plain=wp_strip_all_tags(str_replace(['[faq]','[/faq]'],'',$template));
$faq=explode('[/faq]',explode('[faq]',$template)[1]??'')[0];
if (!$term || is_wp_error($term) || $term->slug!=='hoat-dong-cong-dong' || count($assets)!==5 || count(preg_split('/\s+/u',trim($plain),-1,PREG_SPLIT_NO_EMPTY))<2500 || str_contains($template,'<h1') || count(eyecare_tach_faq($faq))!==6) WP_CLI::error('Editorial preflight failed');
$statefile=$base.'/publication.json';
$state=is_file($statefile)?json_decode(file_get_contents($statefile),true):['post_id'=>0,'media'=>[],'draft_ready'=>false,'complete'=>false];
$save=function()use(&$state,$statefile){file_put_contents($statefile,wp_json_encode($state,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));};
$same=get_posts(['post_type'=>'post','post_status'=>'any','name'=>$m['slug'],'numberposts'=>1]);
if ($same && (int)$state['post_id']!==(int)$same[0]->ID) WP_CLI::error('Existing unrelated post; inspect before another write');
if ($state['complete']) {WP_CLI::success('Already published: '.$state['url']);return;}
$backlinks=[
1686=>'<p>Ngày 25/10/2026, Bệnh viện Mắt Hà Nội – Bắc Ninh tham gia <a href="'.$expected.'">khám mắt cộng đồng cho người dân tại Trạm Y tế Cảnh Thụy</a>. Bài viết ghi lại hoạt động và gợi ý cách lưu kết quả, chuẩn bị câu hỏi sau buổi khám.</p>',
1901=>'<p>Tiếp nối sự quan tâm đến thị lực tại địa phương, bạn đọc có thể xem <a href="'.$expected.'">hoạt động khám mắt cho người dân tại Cảnh Thụy ngày 25/10/2026</a>, một hoạt động riêng của Bệnh viện Mắt Hà Nội – Bắc Ninh tại trạm y tế.</p>'
];
foreach ($backlinks as $bid=>$paragraph) {
    if (get_post_status($bid)!=='publish') WP_CLI::error('Backlink destination is not published');
    $snapshot=$base.'/post-'.$bid.'-before.html';
    if (!is_file($snapshot)) file_put_contents($snapshot,get_post_field('post_content',$bid));
}
foreach ($assets as $i=>$a) {
    $path=$base.'/images/'.$a['filename'];
    $size=is_file($path)?getimagesize($path):false;
    if (!$size || $size[2]!==IMAGETYPE_WEBP || $size[0]!==1280 || $size[1]!==720 || hash_file('sha256',$path)!==$a['sha256'] || substr_count($template,'{{'.$a['token'].'}}')!==($i===0?0:1)) WP_CLI::error('Image preflight failed');
}
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$id=(int)$state['post_id'];
if ($mode==='draft') {
    if ($id && get_post_status($id)!=='draft') WP_CLI::error('Draft phase cannot overwrite published content');
    if (!$id) {
        $id=wp_insert_post(wp_slash(['post_type'=>'post','post_status'=>'draft','post_author'=>0,'post_name'=>$m['slug'],'post_title'=>$m['title'],'post_excerpt'=>$m['description'],'post_category'=>[(int)$m['category_id']],'comment_status'=>'closed','ping_status'=>'closed']),true);
        if (is_wp_error($id)) WP_CLI::error($id->get_error_message());
        $state['post_id']=$id;$save();
    }
    $content=$template;
    foreach ([153,285,1901,1686] as $target) {
        if (get_post_status($target)!=='publish') WP_CLI::error('Internal destination unavailable');
        $content=str_replace('{{LINK:'.$target.'}}',esc_url(get_permalink($target)),$content);
    }
    foreach ($assets as $index=>$a) {
        $mid=(int)($state['media'][$a['token']]??0);
        if (!$mid) {
            // Do not import a second attachment after an uncertain previous attempt.
            $children=get_children(['post_parent'=>$id,'post_type'=>'attachment','post_status'=>'inherit']);
            foreach ($children as $child) if (basename(get_attached_file($child->ID))===$a['filename']) $mid=(int)$child->ID;
            if (!$mid) {
                $tmp=wp_tempnam($a['filename']);
                if (!$tmp || !copy($base.'/images/'.$a['filename'],$tmp)) WP_CLI::error('Image staging failed');
                $mid=media_handle_sideload(['name'=>$a['filename'],'tmp_name'=>$tmp,'error'=>0,'size'=>filesize($tmp)],$id,$a['alt']);
                if (is_wp_error($mid)) WP_CLI::error($mid->get_error_message());
            }
            $state['media'][$a['token']]=$mid;$save();
        }
        update_post_meta($mid,'_wp_attachment_image_alt',$a['alt']);
        $r=wp_update_post(wp_slash(['ID'=>$mid,'post_excerpt'=>$a['caption']]),true);
        if (is_wp_error($r)) WP_CLI::error('Caption write failed');
        if ($index===0) set_post_thumbnail($id,$mid);
        $figure='<figure class="eyecare-school-photo">'.wp_get_attachment_image($mid,'full',false,['loading'=>'lazy','decoding'=>'async']).'<figcaption>'.esc_html($a['caption']).'</figcaption></figure>';
        $content=str_replace('{{'.$a['token'].'}}',$figure,$content);
    }
    $content=wp_kses_post($content);
    if (str_contains($content,'{{') || substr_count($content,'<img ')!==4) WP_CLI::error('Content/image placeholders incomplete');
    $r=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$content]),true);
    if (is_wp_error($r) || get_post_field('post_content',$id)!==$content) WP_CLI::error('Draft readback mismatch');
    update_post_meta($id,'_bvmat_seo_description',$m['description']);
    update_post_meta($id,'_bvmat_tu_khoa_chinh',$m['focus_keyword']);
    update_post_meta($id,'_bvmat_tu_khoa_semantic',implode(', ',$m['semantic_keywords']));
    update_post_meta($id,'_eyecare_semantic_keywords',$m['semantic_keywords']);
    update_post_meta($id,'_eyecare_editorial_source',$m['source'].' Event date2026-10-25 confirmed directly. NEI sources linked for general context; no clinician reviewer, attendance, diagnoses, free-service terms, or whole-program organizer inferred.');
    $state['content_sha256']=hash('sha256',$content);
    $state['draft_ready']=true;
    $state['url']=$expected;$save();
    global $post;
    $post=get_post($id);setup_postdata($post);
    file_put_contents($base.'/preview.json',wp_json_encode(['title'=>$m['title'],'description'=>$m['description'],'hero'=>'<figure class="eyecare-news__article-image">'.get_the_post_thumbnail($id,'full').'<figcaption>'.esc_html($assets[0]['caption']).'</figcaption></figure>','body'=>apply_filters('the_content',$content),'expected_url'=>$expected],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    wp_reset_postdata();
    WP_CLI::success('Draft ready: '.$id.'; media='.count($state['media']));return;
}
if (!$state['draft_ready'] || !is_file($base.'/preview-reviewed.json') || hash('sha256',get_post_field('post_content',$id))!==$state['content_sha256']) WP_CLI::error('Reviewed draft missing or changed');
foreach ($backlinks as $bid=>$paragraph) {
    $current=get_post_field('post_content',$bid);
    if (!str_contains($current,$paragraph) && $current!==file_get_contents($base.'/post-'.$bid.'-before.html')) WP_CLI::error('Related article changed since backup; re-review required');
}
$r=wp_update_post(['ID'=>$id,'post_status'=>'publish'],true);
if (is_wp_error($r) || get_post_status($id)!=='publish' || get_permalink($id)!==$expected) WP_CLI::error('Publication readback mismatch');
foreach ($backlinks as $bid=>$paragraph) {
    $before=get_post_field('post_content',$bid);
    if (str_contains($before,$paragraph)) continue;
    $after=$before."\n".$paragraph;
    $r=wp_update_post(wp_slash(['ID'=>$bid,'post_content'=>$after]),true);
    if (is_wp_error($r) || get_post_field('post_content',$bid)!==$after) WP_CLI::error('Reciprocal link readback mismatch');
}
$state['complete']=true;$state['url']=get_permalink($id);$save();
WP_CLI::success(wp_json_encode(['post_id'=>$id,'url'=>$state['url'],'media_count'=>count($state['media']),'backlink_post_ids'=>array_keys($backlinks)],JSON_UNESCAPED_SLASHES));
