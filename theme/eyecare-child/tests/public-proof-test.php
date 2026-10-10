<?php
/** In-memory privacy regression checks. Run with PHP CLI; never touches WordPress. */
define( 'ABSPATH', __DIR__ );
$GLOBALS['proof_posts'] = array();
$GLOBALS['proof_meta'] = array();
$GLOBALS['proof_allowed'] = true;
$GLOBALS['proof_settings'] = array( 'enabled' => '1', 'delay' => 20 );
$GLOBALS['proof_cache_flushes'] = 0;
$GLOBALS['proof_litespeed_purges'] = 0;
$GLOBALS['proof_admin_assets'] = array();
$assertions = 0;

function expect_proof( $condition, $message ) {
	global $assertions;
	$assertions++;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function do_action( $hook ) { if ( 'litespeed_purge_all' === $hook ) { $GLOBALS['proof_litespeed_purges']++; } }
function wp_cache_flush() { $GLOBALS['proof_cache_flushes']++; }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function wp_unslash( $value ) { return is_string( $value ) ? stripslashes( $value ) : $value; }
function wp_verify_nonce( $nonce, $action ) { return 'valid' === $nonce; }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function home_url( $path ) { return 'https://example.test' . $path; }
function is_admin() { return false; }
function is_feed() { return false; }
function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function settings_fields( $group ) {}
function checked( $value, $expected = true, $echo = true ) { return $value === $expected ? 'checked' : ''; }
function submit_button( $text, ...$args ) { echo '<button>' . esc_html( $text ) . '</button>'; }
function wp_nonce_field( ...$args ) {}
function get_stylesheet_directory() { return dirname( __DIR__ ); }
function get_stylesheet_directory_uri() { return 'https://example.test/wp-content/themes/eyecare-child'; }
function wp_enqueue_style( $handle, ...$args ) { $GLOBALS['proof_admin_assets'][] = $handle; }
function wp_enqueue_script( $handle, ...$args ) { $GLOBALS['proof_admin_assets'][] = $handle; }
function wp_localize_script( $handle, $name, $data ) { $GLOBALS['proof_admin_assets'][] = array( $handle, $name, $data ); }
function wp_date( $format, $timestamp = null ) { return gmdate( $format, strtotime( '2026-10-10 12:00:00 UTC' ) ); }
function current_user_can( $cap ) { return $GLOBALS['proof_allowed']; }
function get_current_user_id() { return 7; }
function get_post_type( $id ) { return isset( $GLOBALS['proof_posts'][ $id ] ) ? 'ec_appointment' : ''; }
function wp_is_post_autosave( $id ) { return false; }
function wp_is_post_revision( $id ) { return false; }
function get_post( $id ) { return $GLOBALS['proof_posts'][ $id ] ?? null; }
function ec_booking_read( $post ) { return $post->data; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['proof_meta'][ $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['proof_meta'][ $id ][ $key ] = $value; }
function delete_post_meta( $id, $key ) { unset( $GLOBALS['proof_meta'][ $id ][ $key ] ); }
function get_option( $key, $default = false ) { return $GLOBALS['proof_settings']; }
function ec_booking_statuses() { return array( 'pending' => 'Chờ', 'confirmed' => 'Xác nhận', 'completed' => 'Đã khám', 'cancelled' => 'Hủy' ); }

require dirname( __DIR__ ) . '/inc/lich-kham-cong-khai.php';

ob_start();
ec_public_proof_settings_page();
$plain_settings = ob_get_clean();
expect_proof( false === strpos( $plain_settings, 'ec-public-proof-demo' ) && false === strpos( $plain_settings, 'Xem thử popup' ), 'Settings page contains no synthetic preview' );
expect_proof( false !== strpos( $plain_settings, 'lời mời đặt lịch' ), 'Settings page explains the truthful fallback' );
ob_start();
ec_public_proof_markup();
$public_markup = ob_get_clean();
expect_proof( false !== strpos( $public_markup, 'https://example.test/dat-lich-kham/' ), 'Public fallback links to booking page' );
expect_proof( false === strpos( $public_markup, 'ec-public-proof-demo' ) && false === strpos( $public_markup, '(mẫu)' ), 'Public markup contains no synthetic booking labels' );

ec_public_proof_purge_page_cache();
expect_proof( 1 === $GLOBALS['proof_cache_flushes'] && 1 === $GLOBALS['proof_litespeed_purges'], 'Settings change purges WordPress and LiteSpeed caches' );

expect_proof( 'Anh Tú' === ec_public_proof_clean_label( ' Anh  Tú ' ), 'Short display name accepted' );
foreach ( array( 'Nguyễn Văn Tú', 'Anh Tú 0868899396', 'Chị Ninh khám Phaco', '<script>x</script>', 'Anh Tú Trần' ) as $unsafe ) {
	expect_proof( '' === ec_public_proof_clean_label( $unsafe ), 'Full or sensitive label rejected' );
}
expect_proof( ec_public_proof_label_matches_booking( 'Anh Tú', array( 'name' => 'Nguyễn Văn Tú' ) ), 'Public given name must match booking' );
expect_proof( ! ec_public_proof_label_matches_booking( 'Anh Hải', array( 'name' => 'Nguyễn Văn Tú' ) ), 'Invented given name rejected' );
$GLOBALS['proof_posts'][1] = (object) array( 'ID' => 1, 'post_status' => 'private', 'data' => array( 'name' => 'Nguyễn Văn Tú', 'phone' => '0868899396', 'consent' => true ) );
$GLOBALS['proof_meta'][1] = array( '_ec_booking_status' => 'confirmed' );
expect_proof( ! ec_public_proof_has_consent( 1 ), 'Old contact consent never authorizes publication' );
ob_start();
ec_public_proof_meta_box( $GLOBALS['proof_posts'][1] );
$admin_box = ob_get_clean();
expect_proof( false !== strpos( $admin_box, 'Lưu và kiểm tra hiển thị' ) && false !== strpos( $admin_box, 'Điều kiện còn thiếu' ), 'Approval box has its own save button and explains missing requirements' );
$_POST = array( 'ec_public_proof_nonce' => 'valid', 'ec_proof_label' => 'Anh Tú', 'ec_proof_state' => 'approved' );
ec_public_proof_save_meta( 1 );
expect_proof( ! get_post_meta( 1, '_ec_public_proof_approved' ), 'Approval rejected without separate consent' );
expect_proof( in_array( 'consent', $GLOBALS['ec_public_proof_feedback'], true ), 'Admin receives the missing-consent reason after a rejected approval' );

$_POST['ec_proof_record_legacy'] = '1';
$_POST['ec_proof_legacy_date'] = '2026-10-10';
$_POST['ec_proof_legacy_evidence'] = 'Sổ xác nhận nội bộ 1010';
ec_public_proof_save_meta( 1 );
expect_proof( '1' === get_post_meta( 1, '_ec_public_proof_approved' ), 'Historical record requires documented new consent and approval' );
expect_proof( array( 'approved' ) === $GLOBALS['ec_public_proof_feedback'], 'Admin receives a saved-approval confirmation' );
$_POST['ec_proof_state'] = 'revoked';
ec_public_proof_save_meta( 1 );
expect_proof( ! get_post_meta( 1, '_ec_public_proof_approved' ) && get_post_meta( 1, '_ec_public_proof_revoked_at' ), 'Withdrawal removes approval' );
unset( $_POST['ec_proof_record_legacy'] );
$_POST['ec_proof_state'] = 'approved';
ec_public_proof_save_meta( 1 );
expect_proof( ! get_post_meta( 1, '_ec_public_proof_approved' ), 'Withdrawal cannot be reversed without new consent evidence' );
expect_proof( in_array( 'revoked', $GLOBALS['ec_public_proof_feedback'], true ), 'Admin receives the fresh-consent requirement after withdrawal' );
$_POST['ec_proof_record_legacy'] = '1';
ec_public_proof_save_meta( 1 );
expect_proof( ! get_post_meta( 1, '_ec_public_proof_approved' ), 'Rechecking old evidence cannot reverse withdrawal' );
$_POST['ec_proof_legacy_evidence'] = 'Sổ xác nhận bổ sung 1010';
ec_public_proof_save_meta( 1 );
expect_proof( '1' === get_post_meta( 1, '_ec_public_proof_approved' ), 'Fresh distinct consent evidence can restore approval' );

$GLOBALS['proof_posts'][2] = (object) array( 'ID' => 2, 'post_status' => 'private', 'data' => array( 'name' => 'Trần Thị Ninh', 'phone' => '0123456789', 'public_share_consent' => true, 'public_share_consented_at' => '2026-10-10T10:00:00+07:00' ) );
$GLOBALS['proof_meta'][2] = array( '_ec_booking_status' => 'cancelled' );
$_POST = array( 'ec_public_proof_nonce' => 'valid', 'ec_proof_label' => 'Chị Ninh', 'ec_proof_state' => 'approved' );
ec_public_proof_save_meta( 2 );
expect_proof( ! get_post_meta( 2, '_ec_public_proof_approved' ), 'Cancelled booking cannot be approved' );
expect_proof( in_array( 'status', $GLOBALS['ec_public_proof_feedback'], true ), 'Admin receives the invalid-status reason' );
$GLOBALS['proof_meta'][2]['_ec_booking_status'] = 'completed';
ec_public_proof_save_meta( 2 );
expect_proof( '1' === get_post_meta( 2, '_ec_public_proof_approved' ), 'Completed opt-in can be approved' );
$GLOBALS['proof_allowed'] = false;
$_POST['ec_proof_state'] = 'revoked';
ec_public_proof_save_meta( 2 );
expect_proof( '1' === get_post_meta( 2, '_ec_public_proof_approved' ), 'Unauthorized edit cannot change approval' );

$GLOBALS['proof_posts'][3] = (object) array( 'ID' => 3, 'post_status' => 'private', 'data' => $GLOBALS['proof_posts'][2]->data );
$GLOBALS['proof_meta'][3] = array( '_ec_booking_status' => 'completed', '_ec_public_proof_approved' => '1', '_ec_public_proof_label' => 'Chị Ninh' );
class ProofTestDb {
	public $posts = 'posts';
	public $postmeta = 'postmeta';
	function prepare( $sql, ...$args ) { return $sql; }
	function get_col( $sql ) { return array( 1, 2, 3 ); }
}
$wpdb = new ProofTestDb();
$labels = ec_public_proof_eligible_labels();
expect_proof( $labels === array( 'Anh Tú', 'Chị Ninh' ), 'Repeat bookings with the same public label show once in rotation' );

echo 'PASS: ' . $assertions . " privacy checks.\n";
