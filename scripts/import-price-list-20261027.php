<?php
/** A single, backed-up price-list replacement through WP-CLI; never delete records. */
if (!defined('WP_CLI') || !WP_CLI) exit(1);
$base=(string)getenv('EYECARE_PRICE_BACKUP');
$plan_hash=(string)getenv('EYECARE_PRICE_PLAN_SHA256');
if (!$base || !is_file($base.'/database.sql') || filesize($base.'/database.sql')===0 || !preg_match('/^[a-f0-9]{64}$/D',$plan_hash) || hash_file('sha256',$base.'/import-plan.json')!==$plan_hash) WP_CLI::error('Backup or input hash missing/mismatched');
$plan=json_decode(file_get_contents($base.'/import-plan.json'),true);
if ($plan['source_sheet']!=='DMDV-BH (2)' || count($plan['rows'])!==215 || count($plan['expected_rows'])!==184) WP_CLI::error('Unexpected source scope');
$type='eyecare_bang_gia';
if (!post_type_exists($type)) WP_CLI::error('Price post type missing');
$state_file=$base.'/import-result.json';
if (is_file($state_file)) WP_CLI::error('Import result already exists; inspect it before any rerun');
$meta=['group'=>'_eyecare_gia_nhom','unit'=>'_eyecare_gia_don_vi','price'=>'_eyecare_gia_muc','group_order'=>'_eyecare_gia_nhom_thu_tu','order'=>'_eyecare_gia_thu_tu'];
$snapshot=function($id)use($meta){
    $post=get_post($id);$r=['ID'=>$id,'title'=>$post->post_title,'status'=>$post->post_status];
    foreach ($meta as $k=>$mk) $r[$k]=(string)get_post_meta($id,$mk,true);
    return $r;
};
$ids=get_posts(['post_type'=>$type,'post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC']);
$expected_ids=array_column($plan['expected_rows'],'ID');sort($expected_ids,SORT_NUMERIC);sort($ids,SORT_NUMERIC);
if ($ids!==$expected_ids || get_post_status(42)!=='publish' || get_post_field('post_name',42)!=='bang-gia') WP_CLI::error('Production price/page scope changed');
$before=[];
foreach ($plan['expected_rows'] as $old) {
    $id=(int)$old['ID'];$actual=$snapshot($id);
    if ($actual!==$old || get_post_type($id)!==$type) WP_CLI::error('Production row changed: '.$id);
    $before[]=['post'=>get_post($id,ARRAY_A),'meta'=>get_post_meta($id)];
}
$used=[];$keys=[];$groups=[];
foreach ($plan['rows'] as $r) {
    $id=(int)$r['existing_id'];
    if ($id && (!in_array($id,$ids,true) || isset($used[$id]))) WP_CLI::error('Unsafe ID reuse');
    if ($id) $used[$id]=true;
    if (!is_int($r['price']) || $r['price']<=0 || !$r['title'] || !$r['unit'] || !$r['group']) WP_CLI::error('Incomplete price row');
    $key=$r['title']."\0".$r['unit'];
    if (isset($keys[$key])) WP_CLI::error('Duplicate source service/unit');
    $keys[$key]=true;$groups[$r['group']]=true;
}
$retire=array_values(array_diff($ids,array_keys($used)));sort($retire,SORT_NUMERIC);
$planned_retire=$plan['retire_ids'];sort($planned_retire,SORT_NUMERIC);
if ($retire!==$planned_retire || count($groups)!==10) WP_CLI::error('Replacement coverage mismatch');
$options=[];
foreach (['eyecare_bang_gia_hien_gia','eyecare_bang_gia_ghi_chu'] as $key) $options[$key]=get_option($key);
if (file_put_contents($base.'/price-records-before.json',wp_json_encode(['rows'=>$before,'options'=>$options,'page'=>get_post(42,ARRAY_A)],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))===false) WP_CLI::error('Cannot save price-record backup');
global $wpdb;
foreach ([$wpdb->posts,$wpdb->postmeta,$wpdb->options] as $table) {
    $engine=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));
    if (strtoupper((string)$engine)!=='INNODB') WP_CLI::error('Transactional tables required');
}
$result=['source_file_sha256'=>$plan['source_file_sha256'],'source_sheet'=>$plan['source_sheet'],'plan_sha256'=>$plan_hash,'updated_ids'=>[],'created_ids'=>[],'retired_to_draft'=>$retire,'rows'=>[],'complete'=>false];
if ($wpdb->query('START TRANSACTION')===false) WP_CLI::error('Cannot start transaction');
try {
    foreach ($plan['rows'] as $r) {
        $id=(int)$r['existing_id'];
        if ($id) {
            $res=wp_update_post(wp_slash(['ID'=>$id,'post_title'=>$r['title'],'post_status'=>'publish']),true);
            if (is_wp_error($res)) throw new RuntimeException($res->get_error_message());
            $result['updated_ids'][]=$id;
        } else {
            $id=wp_insert_post(wp_slash(['post_type'=>$type,'post_status'=>'publish','post_title'=>$r['title'],'comment_status'=>'closed','ping_status'=>'closed']),true);
            if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
            $result['created_ids'][]=$id;
        }
        $values=['group'=>$r['group'],'unit'=>$r['unit'],'price'=>$r['price'],'group_order'=>$r['group_order'],'order'=>$r['order']];
        foreach ($values as $key=>$value) update_post_meta($id,$meta[$key],wp_slash((string)$value));
        update_post_meta($id,'_eyecare_gia_nguon_sheet',$plan['source_sheet']);
        update_post_meta($id,'_eyecare_gia_nguon_hang',(int)$r['row']);
        update_post_meta($id,'_eyecare_gia_nguon_sha256',$plan['source_file_sha256']);
        $actual=$snapshot($id);
        foreach ($values as $key=>$value) if ($actual[$key] !== (string)$value) throw new RuntimeException('Value write mismatch: '.$id.'/'.$key);
        if ($actual['title']!==$r['title']) throw new RuntimeException('Title write mismatch: '.$id);
        $result['rows'][]=['ID'=>$id,'source_row'=>$r['row']];
    }
    foreach ($retire as $id) {
        $res=wp_update_post(['ID'=>$id,'post_status'=>'draft'],true);
        if (is_wp_error($res) || get_post_status($id)!=='draft') throw new RuntimeException('Draft transition failed');
    }
    update_option('eyecare_bang_gia_hien_gia','1',false);
    $note='Cập nhật ngày 27/10/2026. Mức giá tính bằng đồng Việt Nam, theo đơn vị niêm yết tại từng dịch vụ. Chi phí thực tế có thể thay đổi theo chỉ định, vật tư sử dụng và quyền lợi bảo hiểm y tế của từng người bệnh.';
    update_option('eyecare_bang_gia_ghi_chu',$note,false);
    if (get_option('eyecare_bang_gia_hien_gia')!=='1' || get_option('eyecare_bang_gia_ghi_chu')!==$note) throw new RuntimeException('Display option write mismatch');
    $final=get_posts(['post_type'=>$type,'post_status'=>'publish','numberposts'=>-1,'fields'=>'ids']);
    if (count($final)!==215) throw new RuntimeException('Final row count mismatch');
    if ($wpdb->query('COMMIT')===false) throw new RuntimeException('Commit failed');
} catch (Throwable $e) {
    $rolled_back=$wpdb->query('ROLLBACK')!==false;wp_cache_flush();
    file_put_contents($base.'/import-failure.json',wp_json_encode(['error'=>$e->getMessage(),'attempt'=>$result],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    WP_CLI::error(($rolled_back?'Transaction rolled back: ':'Rollback unconfirmed; inspect backup: ').$e->getMessage());
}
$result['complete']=true;
if (file_put_contents($state_file,wp_json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))===false) WP_CLI::error('Database committed, but result log could not be saved. Inspect production; do not rerun.');
WP_CLI::success(wp_json_encode(['updated'=>count($result['updated_ids']),'created'=>count($result['created_ids']),'retired_to_draft'=>count($retire),'published'=>215,'groups'=>count($groups)],JSON_UNESCAPED_SLASHES));
