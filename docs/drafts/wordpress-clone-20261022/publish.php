<?php
/** Run only under WP-CLI after backup, with the existing deployment lock held. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
$base=(string)getenv('EYECARE_BACKUP');
if (!is_dir($base) || !is_file($base.'/database.sql')) WP_CLI::error('Backup missing');
foreach (file($base.'/expected.sha256',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    [$hash,$relative]=explode('  ',$line,2);
    if (!preg_match('~^[a-zA-Z0-9/._-]+$~D',$relative) || str_contains($relative,'..') || hash_file('sha256',$base.'/'.$relative)!==$hash) WP_CLI::error('Source hash mismatch');
}
$keys=['optical-space','glasses-questions','women-day'];
$expected=[
 'optical-space'=>home_url('/tin-tuc/doi-song-benh-vien/khong-gian-kinh-mat-benh-vien-mat-ha-noi-bac-ninh/'),
 'glasses-questions'=>home_url('/kien-thuc/tat-khuc-xa-nguoi-lon/tu-van-kinh-mat-cau-hoi-truoc-khi-dat-kinh-moi/'),
 'women-day'=>home_url('/tin-tuc/doi-song-benh-vien/tri-an-nu-can-bo-nhan-vien-y-te-nhan-dip-20-10/')
];
$assets=json_decode(file_get_contents($base.'/media-manifest.json'),true);
if (count($assets)!==9) WP_CLI::error('Media manifest mismatch');
$progress_file=$base.'/publication.json';
$state=is_file($progress_file)?json_decode(file_get_contents($progress_file),true):['posts'=>[],'media'=>[],'complete'=>false];
if ($state['complete']) {WP_CLI::success('Already completed: '.$progress_file);return;}
$save=function()use(&$state,$progress_file){file_put_contents($progress_file,wp_json_encode($state,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));};
$articles=[];
foreach ($keys as $key) {
    $m=json_decode(file_get_contents($base.'/'.$key.'/post.json'),true);
    $content=file_get_contents($base.'/'.$key.'/content-template.html');
    $term=get_term($m['category_id'],'category');
    if (!$term || is_wp_error($term) || !in_array($term->slug,['doi-song-benh-vien','tat-khuc-xa-nguoi-lon'],true)) WP_CLI::error('Category mismatch');
    $plain=wp_strip_all_tags(str_replace(['[faq]','[/faq]'],'',$content));
    if (count(preg_split('/\s+/u',trim($plain),-1,PREG_SPLIT_NO_EMPTY))<2500 || str_contains($content,'<h1')) WP_CLI::error('Article length/structure mismatch');
    $faq=explode('[/faq]',explode('[faq]',$content)[1]??'')[0];
    if (count(eyecare_tach_faq($faq))!==6) WP_CLI::error('FAQ mismatch');
    $existing=get_posts(['post_type'=>'post','post_status'=>'any','name'=>$m['slug'],'numberposts'=>1]);
    if ($existing && (int)($state['posts'][$key]??0)!==(int)$existing[0]->ID) WP_CLI::error('Existing unrelated post; do not clone twice');
    $articles[$key]=['meta'=>$m,'content'=>$content];
}
foreach ([1447,1575,1685,1847] as $id) {
    if (get_post_status($id)!=='publish') WP_CLI::error('Backlink target missing');
    $path=$base.'/post-'.$id.'-before.html';
    if (!is_file($path)) file_put_contents($path,get_post_field('post_content',$id));
}
foreach ($assets as $a) {
    $path=$base.'/images/'.$a['filename'];
    if (!is_file($path) || hash_file('sha256',$path)!==$a['sha256'] || getimagesize($path)[2]!==IMAGETYPE_WEBP) WP_CLI::error('Media mismatch');
}
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
foreach ($articles as $key=>$a) {
    $m=$a['meta'];
    $id=(int)($state['posts'][$key]??0);
    if (!$id) {
        $id=wp_insert_post(wp_slash(['post_type'=>'post','post_status'=>'draft','post_author'=>0,'post_name'=>$m['slug'],'post_title'=>$m['title'],'post_excerpt'=>$m['description'],'post_category'=>[$m['category_id']],'comment_status'=>'closed','ping_status'=>'closed']),true);
        if (is_wp_error($id)) WP_CLI::error($id->get_error_message());
        $state['posts'][$key]=$id;$save();
    }
    $content=$a['content'];$first=true;
    foreach ($assets as $asset) {
        if ($asset['article']!==$key) continue;
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
        if ($first) {set_post_thumbnail($id,$mid);$first=false;}
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
    // Existing theme gate suppresses plugin-inferred doctor credit and duplicate Article output.
    if ((int)$m['category_id']===7) update_post_meta($id,'_eyecare_content_review_status','pending-author-and-medical-review');
    update_post_meta($id,'_eyecare_editorial_source',$m['source_url'].' | Hospital-owned source article and photos, reworked with user authorization; no invented event date, participant count, diagnosis, price, warranty or medical reviewer. Medical context references NEI/MedlinePlus where applicable.');
    file_put_contents($base.'/'.$key.'/content-published.html',$content);
}
foreach ($keys as $key) {
    $id=$state['posts'][$key];
    $r=wp_update_post(['ID'=>$id,'post_status'=>'publish'],true);
    if (is_wp_error($r) || get_permalink($id)!==$expected[$key]) WP_CLI::error('Publication/permalink mismatch: '.$key);
    $state['urls'][$key]=get_permalink($id);$save();
}
$backlinks=[
1447=>['<h2 id="nhan-kinh-15">','<p>Bạn có thể xem <a href="'.$expected['optical-space'].'">không gian kính mắt tại Bệnh viện Mắt Hà Nội – Bắc Ninh</a> để hình dung khu vực xem gọng và chuẩn bị cho buổi trao đổi trước khi đến.</p>'],
1575=>['<h2>Câu hỏi thường gặp</h2>','<p>Trước lần làm kính tiếp theo, xem <a href="'.$expected['glasses-questions'].'">những câu hỏi cần làm rõ khi tư vấn kính mới</a> để trao đổi đầy đủ về đơn kính, cảm giác đeo, báo giá và hỗ trợ sau khi nhận.</p>'],
1685=>['<h2>Mời độc giả theo dõi và góp ý</h2>','<p>Những câu chuyện về con người còn được tiếp nối trong <a href="'.$expected['women-day'].'">hoạt động tri ân nữ cán bộ, nhân viên y tế nhân dịp 20/10</a>, với bộ ảnh gặp gỡ, hoa và lời chúc giữa đồng nghiệp.</p>'],
1847=>['<aside aria-label="Nguồn tư liệu và đơn vị biên soạn">','<p>Sau bộ ảnh ngày 15/10, bạn đọc có thể xem thêm <a href="'.$expected['women-day'].'">những khoảnh khắc chúc mừng Ngày Phụ nữ Việt Nam 20/10</a> để theo dõi đời sống của đội ngũ bệnh viện.</p>']
];
foreach ($backlinks as $id=>[$marker,$paragraph]) {
    $before=get_post_field('post_content',$id);
    if (str_contains($before,$paragraph)) continue;
    if (substr_count($before,$marker)!==1) WP_CLI::error('Backlink marker mismatch: '.$id);
    $after=str_replace($marker,$paragraph."\n".$marker,$before);
    $r=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$after]),true);
    if (is_wp_error($r) || get_post_field('post_content',$id)!==$after) WP_CLI::error('Backlink write failed');
}
$state['complete']=true;$save();
WP_CLI::success(wp_json_encode(['posts'=>$state['posts'],'urls'=>$state['urls'],'media_count'=>count($state['media'])],JSON_UNESCAPED_SLASHES));
