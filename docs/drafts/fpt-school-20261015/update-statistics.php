<?php
$base=getenv('EYECARE_BACKUP');
$p=get_post(1853);
if(!$p || $p->post_name!=='kham-mat-hoc-duong-fpt-bac-giang' || $p->post_status!=='publish') WP_CLI::error('Unexpected target');
$c=$p->post_content;
if(str_contains($c,'id="so-lieu"')) WP_CLI::error('Statistics already added');
file_put_contents($base.'/content-before.html',$c);
$section=file_get_contents(__DIR__.'/statistics.html');
$c=str_replace('<li><a href="#hinh-anh">','<li><a href="#so-lieu">Số liệu tổng hợp hồ sơ</a></li><li><a href="#hinh-anh">',$c,$toc);
$c=str_replace('<h2 id="hinh-anh">',$section."\n".'<h2 id="hinh-anh">',$c,$body);
$c=str_replace('H: Bài viết có công bố số học sinh bị cận thị không?',"H: Có phải toàn bộ 1.074 học sinh trong danh sách đều đã khám?\nĐ: Danh sách có 1.074 hồ sơ, trong đó 668 hồ sơ có ít nhất một mục kết quả được nhập. Dữ liệu hiện có chưa đủ để xác nhận tổng số học sinh thực tế đã khám; hồ sơ chưa có kết quả không đồng nghĩa học sinh vắng mặt.\n\nH: Bài viết có công bố số học sinh bị cận thị không?",$c,$faq);
$c=str_replace('<ul><li>Tư liệu hoạt động:','<ul><li>Nguồn số liệu: dữ liệu tháng 9 do bệnh viện cung cấp, sheet “TRƯỜNG LIÊN CẤP FPT”, 1.074 dòng hồ sơ; rà soát ngày 15/10/2026. Bản dữ liệu cá nhân không được đính kèm công khai.</li><li>Tư liệu hoạt động:',$c,$source);
if($toc!==1 || $body!==1 || $faq!==1 || $source!==1) WP_CLI::error('Unexpected anchors');
$r=wp_update_post(wp_slash(['ID'=>1853,'post_content'=>$c]),true);
if(is_wp_error($r) || get_post_field('post_content',1853)!==$c) WP_CLI::error('Update failed');
file_put_contents($base.'/content-after.html',$c);
WP_CLI::success('Statistics updated on post 1853');
