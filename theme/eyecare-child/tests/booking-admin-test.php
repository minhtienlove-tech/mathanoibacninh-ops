<?php
/** Admin regression tests use memory only; never connect to production. */
require __DIR__ . '/booking-validation-test.php';
require dirname( __DIR__ ) . '/inc/quan-ly-dat-lich.php';

$GLOBALS['ec_admin_allowed'] = true;
$GLOBALS['ec_admin_screen'] = true;
$GLOBALS['ec_admin_post_type'] = 'ec_appointment';
$GLOBALS['ec_admin_meta'] = array();
function current_user_can( $cap ) { return $GLOBALS['ec_admin_allowed']; }
function is_admin() { return $GLOBALS['ec_admin_screen']; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) ); }
function sanitize_textarea_field( $value ) { return strip_tags( $value ); }
function get_post_type( $id ) { return $GLOBALS['ec_admin_post_type']; }
function wp_is_post_autosave( $id ) { return false; }
function wp_is_post_revision( $id ) { return false; }
function get_current_user_id() { return 17; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['ec_admin_meta'][$id][$key] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['ec_admin_meta'][$id][$key] = wp_unslash( $value ); return true; }
function wp_die( ...$args ) { throw new RuntimeException( 'wp_die' ); }
class EcAdminQuery {
	public $args;
	public $main = true;
	public function __construct( $args ) { $this->args = $args; }
	public function get( $key ) { return $this->args[$key] ?? ''; }
	public function set( $key, $value ) { $this->args[$key] = $value; }
	public function is_main_query() { return $this->main; }
}
class EcAdminTestDb {
	public $posts = 'test_posts';
	public function esc_like( $value ) { return addcslashes( $value, '_%\\' ); }
	public function prepare( $sql, $value ) { return str_replace( '%s', "'" . addslashes( $value ) . "'", $sql ); }
}

ec_expect( ec_booking_admin_day( '2026-09-12' ) === '2026-09-12', 'Valid filter date accepted' );
foreach ( array( '2026-02-30', '2026-09-12 OR 1=1', "2026-09-12\0", array( '2026-09-12' ) ) as $bad_day ) {
	ec_expect( ec_booking_admin_day( $bad_day ) === '', 'Malformed filter date cannot reach SQL' );
}
ec_expect( ec_booking_admin_date_label( '2026-09-12' ) === '12/09/2026', 'Dates shown in Vietnamese order' );
ec_expect( ec_booking_admin_filters( array( 'ec_status' => array( 'pending' ) ) )['status'] === '', 'Array status safe' );
ec_expect( ec_booking_admin_filters( array( 'ec_status' => 'completed' ) )['status'] === 'completed', 'Completed status filter recognized' );
ec_expect( ec_booking_admin_meta_query( 'pending' )['relation'] === 'OR', 'Unprocessed legacy records included in pending filter' );
ec_expect( ec_booking_admin_meta_query( 'bad' ) === array(), 'Unknown status not sent to query' );
$_GET = array( 'ec_day' => '2026-09-12', 'ec_status' => 'confirmed' );
$query = new EcAdminQuery( array( 'post_type' => 'ec_appointment', 's' => '+84 868 899 396' ) );
ec_booking_admin_query( $query );
ec_expect( $query->get( 's' ) === '0868899396', 'Phone search normalized to stored phone' );
ec_expect( $query->get( 'ec_booking_day' ) === '2026-09-12' && $query->get( 'meta_query' )[0]['value'] === 'confirmed', 'Main appointment list receives day and status filters' );
$wpdb = new EcAdminTestDb();
$where = ec_booking_admin_where( 'WHERE 1=1', $query );
ec_expect( strpos( $where, 'test_posts.post_content LIKE' ) !== false && strpos( $where, '2026-09-12' ) !== false, 'Scoped day filter searches saved appointment date' );
$GLOBALS['ec_admin_screen'] = false;
$public_query = new EcAdminQuery( array( 'post_type' => 'ec_appointment' ) );
ec_booking_admin_query( $public_query );
ec_expect( ! $public_query->get( 'ec_booking_admin_scope' ) && ec_booking_admin_where( 'WHERE original', $query ) === 'WHERE original', 'Public queries never altered by admin filters' );
$GLOBALS['ec_admin_screen'] = true;
$other = new EcAdminQuery( array( 'post_type' => 'post' ) );
ec_booking_admin_query( $other );
ec_expect( ! $other->get( 'ec_booking_admin_scope' ), 'Unrelated admin post lists unchanged' );
$GLOBALS['ec_admin_allowed'] = false;
ec_expect( ec_booking_admin_count() === 0, 'Unauthorized caller cannot query appointment counts' );
$_POST = array( 'ec_booking_admin_nonce' => 'test-nonce', 'ec_booking_status' => 'completed', 'ec_booking_note' => 'Đã khám' );
ec_booking_admin_save( 1 );
ec_expect( empty( $GLOBALS['ec_admin_meta'] ), 'Unauthorized status/note write rejected' );
$GLOBALS['ec_admin_allowed'] = true;
$_POST['ec_booking_admin_nonce'] = 'bad';
ec_booking_admin_save( 1 );
ec_expect( empty( $GLOBALS['ec_admin_meta'] ), 'Bad nonce cannot change record' );
$_POST['ec_booking_admin_nonce'] = 'test-nonce';
$GLOBALS['ec_admin_post_type'] = 'post';
ec_booking_admin_save( 1 );
ec_expect( empty( $GLOBALS['ec_admin_meta'] ), 'Cannot update unrelated post type' );
$GLOBALS['ec_admin_post_type'] = 'ec_appointment';
$_POST['ec_booking_note'] = array( 'bad' );
try { ec_booking_admin_save( 1 ); ec_expect( false, 'Array note must be rejected' ); } catch ( RuntimeException $error ) {}
ec_expect( empty( $GLOBALS['ec_admin_meta'] ), 'Bad note rejected before changing status' );
$_POST['ec_booking_note'] = str_repeat( 'a', 8001 );
try { ec_booking_admin_save( 1 ); ec_expect( false, 'Oversized note must be rejected' ); } catch ( RuntimeException $error ) {}
ec_expect( empty( $GLOBALS['ec_admin_meta'] ), 'Oversized note never persisted' );
$_POST['ec_booking_status'] = 'unknown';
$_POST['ec_booking_note'] = 'ignored';
ec_booking_admin_save( 1 );
ec_expect( empty( $GLOBALS['ec_admin_meta'] ), 'Unknown status rejected' );
$_POST['ec_booking_status'] = 'completed';
$_POST['ec_booking_note'] = wp_slash( "Đã gọi O'Connor\n<b>Đã khám</b>" );
ec_booking_admin_save( 1 );
ec_expect( get_post_meta( 1, '_ec_booking_status' ) === 'completed', 'Authorized staff can mark completed' );
ec_expect( get_post_meta( 1, '_ec_booking_note' ) === "Đã gọi O'Connor\nĐã khám", 'Notes safely sanitized, preserve apostrophes and line breaks' );
ec_expect( get_post_meta( 1, '_ec_booking_handled_by' ) === 17 && get_post_meta( 1, '_ec_booking_handled_at' ) > 0, 'Handling user/time tracked' );
unset( $_POST['ec_booking_note'] );
$_POST['ec_booking_status'] = 'confirmed';
ec_booking_admin_save( 1 );
ec_expect( get_post_meta( 1, '_ec_booking_note' ) === "Đã gọi O'Connor\nĐã khám", 'Older open forms do not erase existing notes' );
ec_expect( ! isset( ec_booking_admin_bulk_actions( array( 'edit' => 'Edit', 'trash' => 'Trash' ) )['edit'] ), 'Generic bulk edit cannot overwrite appointment fields' );
echo 'PASS: ' . $assertions . " combined booking and admin checks.\n";
