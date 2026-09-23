<?php
/** Standalone regression tests: fake WordPress state; no database/production files. */
define( 'ABSPATH', __DIR__ ); define( 'MB_IN_BYTES', 1048576 ); define( 'HOUR_IN_SECONDS', 3600 );
$root = sys_get_temp_dir() . '/ec-media-test-' . bin2hex( random_bytes( 6 ) ); mkdir( $root );
$meta = array(); $posts = array(); $allow = true; $fail_update = false; $large = false;
$home_slides = array();
function eyecare_slider_doc_cau_hinh() { global $home_slides; return array( 'anh' => $home_slides ); }
class WP_Error { function __construct( public $code, public $message ) {} function get_error_message() { return $this->message; } }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function add_action( ...$x ) {} function add_filter( ...$x ) {}
function wp_upload_dir() { global $root; return array( 'basedir' => $root, 'baseurl' => 'https://hospital.test/uploads' ); }
function wp_normalize_path( $s ) { return str_replace( '\\', '/', $s ); }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function absint( $s ) { return abs( (int) $s ); }
function remove_accents( $s ) { return strtr( $s, array( 'ắ'=>'a', 'đ'=>'d', 'ề'=>'e', 'ị'=>'i', 'ệ'=>'e', 'ý'=>'y', 'ủ'=>'u', 'Đ'=>'D' ) ); }
function sanitize_title( $s ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $s ) ), '-' ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $s ) ); }
function current_user_can( ...$x ) { global $allow; return $allow; }
function get_post( $id ) { global $posts; return $posts[ $id ] ?? null; }
function get_post_type_object( $type ) { return (object) array( 'public' => 'private_tool' !== $type ); }
function get_the_title( $p ) { return is_object( $p ) ? $p->post_title : get_post( $p )->post_title; }
function get_post_meta( $id, $key, $single = true ) { global $meta; return $meta[ $id ][ $key ] ?? ''; }
function add_post_meta( $id, $key, $value, $unique = false ) { global $meta; if ( isset( $meta[$id][$key] ) ) { return false; } $meta[$id][$key] = $value; return true; }
function delete_post_meta( $id, $key ) { global $meta; unset( $meta[$id][$key] ); }
function get_attached_file( $id, $unfiltered = false ) { global $root; return "$root/" . get_post_meta( $id, '_wp_attached_file' ); }
function update_attached_file( $id, $file ) { global $root, $meta; $meta[$id]['_wp_attached_file'] = substr( $file, strlen( $root ) + 1 ); }
function wp_update_attachment_metadata( $id, $value ) { global $meta; $meta[$id]['_wp_attachment_metadata'] = $value; }
function wp_update_post( $value, $error = false ) { global $posts, $fail_update; if ( $fail_update ) { $fail_update = false; throw new RuntimeException( 'Injected failure' ); } $posts[$value['ID']]->post_mime_type = $value['post_mime_type']; return $value['ID']; }
function wp_generate_uuid4() { return bin2hex( random_bytes( 8 ) ); }
function wp_mkdir_p( $path ) { return mkdir( $path, 0777, true ); }
function wp_image_editor_supports( $x ) { return true; }
function wp_getimagesize( $s ) { return str_contains( $s, 'crop' ) ? array( 600, 400 ) : array( 2400, 1600 ); }
function wp_get_image_mime( $s ) { return 'image/png'; }
function size_format( $n ) { return "$n B"; }
function delete_transient( $x ) {}
class TestEditor {
  public $width; public $height;
  function __construct( $file ) { [$this->width,$this->height] = wp_getimagesize( $file ); }
  function set_quality( $n ) { return true; }
  function resize( $w, $h, $crop ) { $scale = min( $w / $this->width, $h / $this->height, 1 ); $this->width = (int) ($this->width * $scale); $this->height = (int) ($this->height * $scale); return true; }
  function save( $file, $mime ) { global $large; file_put_contents( $file, str_repeat( 'w', $large ? 10000 : 100 ) ); return array( 'width'=>$this->width, 'height'=>$this->height, 'file'=>basename($file) ); }
}
function wp_get_image_editor( $file ) { return new TestEditor( $file ); }
require $argv[1] ?? dirname( __DIR__ ) . '/inc/toi-uu-anh.php';
$count = 0;
function check( $value, $label ) { global $count; if ( ! $value ) { throw new RuntimeException( 'FAIL: ' . $label ); } $count++; echo "PASS $label\n"; }
$posts[7] = (object) array( 'ID'=>7, 'post_type'=>'attachment', 'post_mime_type'=>'image/png' );
$posts[8] = (object) array( 'ID'=>8, 'post_type'=>'post', 'post_status'=>'publish', 'post_title'=>'Điều trị bệnh lý mắt' );
file_put_contents( "$root/source.png", str_repeat( 'p', 5000 ) );
file_put_contents( "$root/crop.png", str_repeat( 'c', 2000 ) );
$original = array( '_wp_attached_file'=>'source.png', '_wp_attachment_metadata'=>array( 'file'=>'source.png', 'width'=>2400, 'height'=>1600, 'sizes'=>array( 'medium'=>array('file'=>'crop.png','width'=>600,'height'=>400), 'duplicate'=>array('file'=>'crop.png','width'=>600,'height'=>400) ) ) );
$meta[7] = $original;
check( ec_media_local_file( __FILE__ ) === false, 'paths outside upload directory rejected' );
check( ec_media_slug( 'Điều trị bệnh lý mắt', 7 ) === 'dieu-tri-benh-ly-mat-anh-7', 'Vietnamese filename and unique ID' );
$allow = false;
check( ec_media_optimize(7,8)->code === 'permission', 'permission denied before changes' ); $allow = true;
check( ec_media_optimize(7,7)->code === 'invalid', 'attachment cannot be article owner' );
check( !ec_media_valid_owner((object)array('post_type'=>'private_tool','post_status'=>'publish')), 'internal post type not eligible for public filenames' );
check( ec_media_valid_owner((object)array('post_type'=>'eyecare_bac_si','post_status'=>'publish')), 'doctor profile may own its photo' );
check( ec_media_valid_owner((object)array('post_type'=>'eyecare_danh_gia','post_status'=>'publish')), 'patient review may own its photo' );
$large = true;
check( ec_media_optimize(7,8)->code === 'not_smaller' && $meta[7] === $original, 'larger result does not activate' ); $large = false;
$result = ec_media_optimize(7,8,1600,82);
check( !is_wp_error($result) && $result['before'] === 7000 && $result['after'] === 200, 'optimization deduplicates crop byte totals' );
check( $meta[7]['_wp_attachment_metadata']['width'] === 1600 && $meta[7]['_wp_attachment_metadata']['sizes']['medium']['width'] === 600, 'resize and existing crop preserved' );
check( $posts[7]->post_mime_type === 'image/webp' && file_exists("$root/source.png"), 'new mime activated and original kept' );
check( ec_media_optimize(7,8)->code === 'already', 'repeat optimization blocked' );
check( !is_wp_error(ec_media_restore(7)) && $meta[7] === $original && $posts[7]->post_mime_type === 'image/png', 'complete metadata and mime restored' );
$fail_update = true;
check( ec_media_optimize(7,8)->code === 'save' && $meta[7] === $original, 'activation exception rolls back' );
$result = ec_media_optimize(7,8,800,65,true);
check( !is_wp_error($result) && $result['before'] === $result['after'] && hash_file('sha256',get_attached_file(7))===hash_file('sha256',"$root/source.png"), 'rename-only preserves exact bytes and original format' );
check( !is_wp_error(ec_media_restore(7)) && $meta[7] === $original, 'rename-only is reversible' );
$home_slides = array( 7 );
check( ec_media_is_home_slide(7) && !ec_media_is_home_slide(8), 'only configured homepage slide eligible for homepage owner' );
$result = ec_media_optimize(7,0,1600,82);
check( !is_wp_error($result) && str_contains(get_post_meta(7,'_wp_attached_file'),'trang-chu-slider-anh-7.webp'), 'home slider gets homepage filename' );
check( !is_wp_error(ec_media_restore(7)), 'home slider optimization restores' );
$home_slides = array();
$meta[7]['_wp_attachment_metadata']['sizes']['missing'] = array('file'=>'absent.png');
check( ec_media_optimize(7,8)->code === 'crop' && $meta[7]['_wp_attached_file'] === 'source.png', 'missing crop aborts before activation' );
echo "$count assertions passed; isolated fixture directory: $root\n";
