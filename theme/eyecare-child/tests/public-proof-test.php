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
function do_action( $hook ) { if ( 'litespeed_purge_all' === $hook ) { $GLOBALS['proof_litespeed_purges']++; } }
function wp_cache_flush() { $GLOBALS['proof_cache_flushes']++; }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function wp_unslash( $value ) { return is_string( $value ) ? stripslashes( $value ) : $value; }
function wp_verify_nonce( $nonce, $action ) { return 'valid' === $nonce; }
function wp_nonce_url( $url, $action ) { return $url . '&_wpnonce=valid'; }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function settings_fields( $group ) {}
function checked( $value, $expected = true, $echo = true ) { return $value === $expected ? 'checked' : ''; }
function submit_button( $text ) {}
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

$samples = ec_public_proof_demo_labels();
expect_proof( count( $samples ) === 100 && count( array_unique( $samples ) ) === 100, 'Admin demo contains 100 unique synthetic labels' );
expect_proof( 'Anh Tú (mẫu)' === $samples[0] && 'Chị Ninh (mẫu)' === $samples[1] && 'Chị Thủy (mẫu)' === $samples[99], 'Demo labels resemble short Vietnamese names and remain marked as samples' );
foreach ( $samples as $sample ) {
	expect_proof( 1 === preg_match( '/^(?:Anh|Chị) [\p{L}\p{M}]+ \(mẫu\)$/uD', $sample ), 'Every sample has a realistic short-name format and explicit sample suffix' );
	expect_proof( '' === ec_public_proof_clean_label( $sample ), 'No demo label can pass the public booking label validator' );
}
$_GET = array( 'post_type' => 'ec_appointment', 'page' => 'ec-public-proof', 'preview' => '1', '_wpnonce' => 'valid' );
$GLOBALS['proof_allowed'] = false;
expect_proof( ! ec_public_proof_admin_preview_authorized(), 'Non-admin cannot see demo even with a valid nonce' );
$GLOBALS['proof_allowed'] = true;
$_GET['_wpnonce'] = 'invalid';
expect_proof( ! ec_public_proof_admin_preview_authorized(), 'Preview rejects an invalid nonce' );
ec_public_proof_admin_preview_assets();
expect_proof( array() === $GLOBALS['proof_admin_assets'], 'Demo assets are not enqueued without valid preview authorization' );
ob_start();
ec_public_proof_settings_page();
$plain_settings = ob_get_clean();
expect_proof( false === strpos( $plain_settings, 'id="ec-public-proof-demo-card"' ), 'Settings page does not render a demo card without valid nonce' );
$_GET['_wpnonce'] = 'valid';
expect_proof( ec_public_proof_admin_preview_authorized(), 'Admin can open nonce-protected preview' );
ec_public_proof_admin_preview_assets();
expect_proof( count( $GLOBALS['proof_admin_assets'] ) === 4 && count( $GLOBALS['proof_admin_assets'][3][2]['labels'] ) === 100, 'Only authorized admin receives demo assets and synthetic labels' );
ob_start();
ec_public_proof_settings_page();
$preview_settings = ob_get_clean();
expect_proof( false !== strpos( $preview_settings, 'id="ec-public-proof-demo-card"' ) && false !== strpos( $preview_settings, 'DỮ LIỆU MẪU' ), 'Admin preview permanently identifies the card as sample data' );
$_GET = array();

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
$_POST = array( 'ec_public_proof_nonce' => 'valid', 'ec_proof_label' => 'Anh Tú', 'ec_proof_state' => 'approved' );
ec_public_proof_save_meta( 1 );
expect_proof( ! get_post_meta( 1, '_ec_public_proof_approved' ), 'Approval rejected without separate consent' );

$_POST['ec_proof_record_legacy'] = '1';
$_POST['ec_proof_legacy_date'] = '2026-10-10';
$_POST['ec_proof_legacy_evidence'] = 'Sổ xác nhận nội bộ 1010';
ec_public_proof_save_meta( 1 );
expect_proof( '1' === get_post_meta( 1, '_ec_public_proof_approved' ), 'Historical record requires documented new consent and approval' );
$_POST['ec_proof_state'] = 'revoked';
ec_public_proof_save_meta( 1 );
expect_proof( ! get_post_meta( 1, '_ec_public_proof_approved' ) && get_post_meta( 1, '_ec_public_proof_revoked_at' ), 'Withdrawal removes approval' );
unset( $_POST['ec_proof_record_legacy'] );
$_POST['ec_proof_state'] = 'approved';
ec_public_proof_save_meta( 1 );
expect_proof( ! get_post_meta( 1, '_ec_public_proof_approved' ), 'Withdrawal cannot be reversed without new consent evidence' );
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
