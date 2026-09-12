<?php
/** All credentials and SMTP interactions below are fictional/in memory. */
namespace PHPMailer\PHPMailer {
	class SMTP { public $Timelimit; }
	#[\AllowDynamicProperties]
	class PHPMailer {
		public static $sent = array();
		public static $fail = false;
		public static $last;
		public function __construct( $exceptions ) { self::$last = $this; }
		public function isSMTP() { $this->Mailer = 'smtp'; }
		public function getSMTPInstance() { return new SMTP(); }
		public function setFrom( $email, $name ) { $this->From = $email; }
		public function addAddress( $email ) { $this->To = $email; }
		public function isHTML( $html ) { $this->Html = $html; }
		public function smtpClose() { $this->closed = true; }
		public function send() {
			self::$sent[] = clone $this;
			if ( self::$fail ) { throw new \Exception( 'SMTP error with secret: ' . $this->Password ); }
			return true;
		}
	}
}
namespace {
require __DIR__ . '/booking-zalo-test.php';
require dirname( __DIR__ ) . '/inc/gmail-dat-lich.php';
function is_email( $email ) { return is_string( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ); }
function current_user_can( $cap ) { return false; }
function wp_die( ...$args ) { throw new RuntimeException( 'denied' ); }
function check_admin_referer( ...$args ) { throw new RuntimeException( 'unexpected nonce access' ); }
use PHPMailer\PHPMailer\PHPMailer;
$GLOBALS['zalo_events'] = array();
$GLOBALS['zalo_schedule_fail'] = false;
$defaults = ec_gmail_settings();
ec_expect( !$defaults['enabled'] && !ec_gmail_ready($defaults), 'Gmail defaults disabled and incomplete' );
ec_expect_error(ec_gmail_send('test','test'),'config','Incomplete settings never contact SMTP');
$input = array('username'=>'hospital@gmail.com','recipient'=>'staff@example.org','app_password'=>'abcd efgh ijkl mnop');
$s = ec_gmail_prepare_settings($input,$defaults);
ec_expect(!is_wp_error($s) && ec_gmail_password($s)==='abcdefghijklmnop' && !$s['enabled'], 'Spaced app password normalized and encrypted, initially disabled');
ec_expect(strpos($s['cipher'],'abcdefghijklmnop')===false, 'App password absent from stored ciphertext');
$blank=$input;$blank['app_password']='';
$same=ec_gmail_prepare_settings($blank,$s);
ec_expect($same['cipher']===$s['cipher'] && $same['version']===$s['version'], 'Blank password keeps existing credential and queue version');
$changed=$blank;$changed['username']='new@gmail.com';
ec_expect_error(ec_gmail_prepare_settings($changed,$s),'password','Different account requires corresponding password');
foreach (array('bad','a@example.org,b@example.org',"a@example.org\r\nBcc: leak@example.org") as $bad) {
	$test=$input;$test['recipient']=$bad;ec_expect_error(ec_gmail_prepare_settings($test,$s),'email','Invalid or injected recipient rejected');
}
$test=$input;$test['app_password']='short';ec_expect_error(ec_gmail_prepare_settings($test,$s),'password','Malformed app password rejected');
$test=$input;$test['username']=array('attack');ec_expect_error(ec_gmail_prepare_settings($test,$s),'input','Array input rejected');
foreach(array('ec_gmail_admin_action','ec_gmail_retry') as $handler) {
	try {$handler();ec_expect(false,'Unauthorized handler did not stop');} catch(RuntimeException $e){ec_expect($e->getMessage()==='denied','Capability checked before any mail or mutation');}
}
update_option('ec_booking_gmail',$s);ec_gmail_queue(1);
ec_expect(empty($GLOBALS['zalo_events']),'Disabled Gmail never queues a booking');
ec_expect(ec_gmail_send('Kiểm tra','Nội dung')===true,'Explicit setup test can send while automatic notifications disabled');
$sent=PHPMailer::$sent[0];
ec_expect($sent->Host==='smtp.gmail.com' && $sent->Port===587 && $sent->SMTPSecure==='tls' && $sent->SMTPAuth===true,'Fixed Gmail STARTTLS authentication');
ec_expect($sent->SMTPOptions['ssl']['verify_peer'] && $sent->SMTPOptions['ssl']['verify_peer_name'] && !$sent->SMTPOptions['ssl']['allow_self_signed'] && $sent->SMTPDebug===0,'TLS verified and secret-bearing debug disabled');
ec_expect($sent->From===$s['username'] && $sent->To===$s['recipient'] && !$sent->Html && $sent->CharSet==='UTF-8','Correct sender, single recipient and Vietnamese plain text');
ec_expect(PHPMailer::$last->Password==='' && PHPMailer::$last->closed,'SMTP password cleared and connection closed after send');
$input['enabled']='1';$s=ec_gmail_prepare_settings($input,$s);update_option('ec_booking_gmail',$s);
ec_gmail_queue(1);ec_gmail_queue(1);$before=count(PHPMailer::$sent);
ec_gmail_deliver(1);ec_gmail_deliver(1);
ec_expect(get_post_meta(1,'_ec_gmail_state')==='sent' && count(PHPMailer::$sent)===$before+1,'Worker sends once despite duplicate queue/run');
$text=end(PHPMailer::$sent)->Body;$data=ec_booking_read(get_post(1));
ec_expect(strpos($text,$data['name'])===false && strpos($text,$data['phone'])===false && strpos($text,'post=1&action=edit')!==false,'Message uses admin link without patient name or phone');
ec_gmail_queue(2);$s['version']='changed';update_option('ec_booking_gmail',$s);ec_gmail_deliver(2);
ec_expect(get_post_meta(2,'_ec_gmail_state')==='skipped','Queued mail cannot silently switch recipient after config change');
update_post_meta(2,'_ec_gmail_state','retry');ec_gmail_queue(2);PHPMailer::$fail=true;
ec_gmail_deliver(2);$before=count(PHPMailer::$sent);ec_gmail_deliver(2);
ec_expect(get_post_meta(2,'_ec_gmail_state')==='unknown' && count(PHPMailer::$sent)===$before,'Uncertain SMTP failure does not auto resend');
ec_expect(strpos(get_post_meta(2,'_ec_gmail_error'),'abcdefghijklmnop')===false && PHPMailer::$last->Password==='', 'SMTP exceptions do not disclose stored password');
$GLOBALS['zalo_events']=array();$GLOBALS['zalo_schedule_fail']=true;update_post_meta(2,'_ec_gmail_state','retry');ec_gmail_queue(2);
ec_expect(get_post_meta(2,'_ec_gmail_state')==='failed' && count($GLOBALS['ec_test_posts'])===2,'Queue failure visible; appointments retained');
echo 'PASS: '.$assertions." combined booking/Zalo/Gmail checks; no real email sent.\n";
}
