<?php
$base = __DIR__;
$content = file_get_contents($base . '/content-template.html');
foreach ([338,11,150,158,285,57,58] as $id) {
    if ('publish' !== get_post_status($id)) WP_CLI::error('Target not public: ' . $id);
    $content = str_replace('{{'.$id.'}}', esc_url(get_permalink($id)), $content);
}
$slug = 'gioi-thieu-bscki-bui-van-canh';
if (get_posts(['post_type'=>'post','post_status'=>'any','name'=>$slug])) WP_CLI::error('Post already exists');
if (!has_term('goc-bac-si', 'category', 1736)) WP_CLI::error('Category mismatch');
$image = (int)get_post_thumbnail_id(338);
if (!$image || get_post_type($image) !== 'attachment') WP_CLI::error('Missing portrait');
$id = wp_insert_post(wp_slash([
 'post_type'=>'post','post_status'=>'draft','post_author'=>0,'post_category'=>[24],
 'post_name'=>$slug,'post_title'=>'BSCKI. Bùi Văn Cảnh – Phó Giám đốc chuyên môn Bệnh viện Mắt Hà Nội – Bắc Ninh',
 'post_excerpt'=>'Gặp gỡ BSCKI. Bùi Văn Cảnh: gần 15 năm kinh nghiệm nhãn khoa, chuyên môn Phaco, mộng, quặm, thẩm mỹ mắt và cách đặt lịch tư vấn.',
 'post_content'=>$content,'comment_status'=>'closed','ping_status'=>'closed'
]),true);
if (is_wp_error($id)) WP_CLI::error($id->get_error_message());
file_put_contents($base.'/new-post-id.txt', (string)$id);
update_post_meta($id,'_eyecare_bac_si_lien_quan',338);
set_post_thumbnail($id,$image);
update_post_meta($id,'_bvmat_seo_description','Tìm hiểu BSCKI. Bùi Văn Cảnh, Phó Giám đốc chuyên môn Bệnh viện Mắt Hà Nội – Bắc Ninh: đào tạo, kinh nghiệm, chuyên môn và cách đặt lịch.');
if (get_post_field('post_content',$id) !== $content) WP_CLI::error('Content mismatch; draft retained');
$result = wp_update_post(['ID'=>$id,'post_status'=>'publish'],true);
if (is_wp_error($result)) WP_CLI::error($result->get_error_message());
WP_CLI::success('Published '.$id.' '.get_permalink($id));
WP_CLI::line('Doctor profile: '.get_permalink(338));
