<?php
if (!defined('WP_CLI') || !WP_CLI) exit(1);
$base=(string)getenv('EYECARE_BACKUP');
if(!is_dir($base)) WP_CLI::error('Missing backup');
$id=1861;
if(get_post_field('post_name',$id)!=='kham-mat-hoc-duong-truong-dtnt-bac-ninh') WP_CLI::error('Post mismatch');
$before=get_post_field('post_content',$id);
if(substr_count($before,'<table>')!==2) WP_CLI::error('Table mismatch');
file_put_contents($base.'/content-before.html',$before);
$changes=[
    '<table>'=>'<table style="width:100%;border-collapse:collapse;margin:1.5rem 0;">',
    '<caption>'=>'<caption style="text-align:left;font-weight:600;padding:0 0 .75rem;">',
    '<th scope="col">'=>'<th scope="col" style="text-align:left;padding:.7rem;border-bottom:2px solid #c8ddd5;background:#edf7f2;">',
    '<td>'=>'<td style="padding:.7rem;border-bottom:1px solid #dbe8e3;">'
];
$after=wp_kses_post(str_replace(array_keys($changes),array_values($changes),$before));
$r=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$after]),true);
if(is_wp_error($r) || get_post_field('post_content',$id)!==$after) WP_CLI::error('Update failed');
file_put_contents($base.'/content-after.html',$after);
WP_CLI::success('Improved table readability for post 1861');
