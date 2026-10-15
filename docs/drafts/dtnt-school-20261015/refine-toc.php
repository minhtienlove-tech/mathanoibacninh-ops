<?php
if (!defined('WP_CLI') || !WP_CLI) exit(1);
$base = (string) getenv('EYECARE_BACKUP');
if (!is_dir($base)) WP_CLI::error('Backup missing');
$id = 1861;
if (get_post_field('post_name',$id)!=='kham-mat-hoc-duong-truong-dtnt-bac-ninh') WP_CLI::error('Post mismatch');
$before = get_post_field('post_content',$id);
file_put_contents($base.'/content-before.html',$before);
$after = preg_replace('~<nav aria-label="Mục lục bài khám mắt tại trường DTNT">.*?</nav>\s*~su','',$before,-1,$count);
if ($count!==1 || strlen($after)<10000) WP_CLI::error('Unexpected TOC match');
$result = wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$after]),true);
if (is_wp_error($result) || get_post_field('post_content',$id)!==$after) WP_CLI::error('Update failed');
file_put_contents($base.'/content-after.html',$after);
WP_CLI::success('Removed duplicate manual TOC from '.$id.'; automatic TOC retained');
