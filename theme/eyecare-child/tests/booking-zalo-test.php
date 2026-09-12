<?php
/** In-memory tests: no real credentials, network calls or Zalo messages. */
require __DIR__ . '/booking-validation-test.php';
require dirname( __DIR__ ) . '/inc/quan-ly-dat-lich.php';
require dirname( __DIR__ ) . '/inc/zalo-dat-lich.php';
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function update_option( $key, $value, $autoload = false ) { $GLOBALS['ec_test_options'][$key] = $value; return true; }
function get_post( $id ) { foreach ( $GLOBALS['ec_test_posts'] as $post ) { if ( $post->ID === $id ) { return $post; } } return null; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['zalo_meta'][$id][$key] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['zalo_meta'][$id][$key] = $value; return true; }
function wp_next_scheduled( $hook, $args ) { return $GLOBALS['zalo_events'][$args[0]] ?? false; }
function wp_schedule_single_event( $time, $hook, $args, $error = false ) { if ( ! empty( $GLOBALS['zalo_schedule_fail'] ) ) { return false; } $GLOBALS['zalo_events'][$args[0]] = $time; return true; }
function admin_url( $path ) { return 'https://example.test/wp-admin/' . $path; }
function wp_remote_post( $url, $args ) { $GLOBALS['zalo_http_calls'][] = array( $url, $args ); return $GLOBALS['zalo_response']; }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function ec_test_zalo_response( $code, $body ) { $GLOBALS['zalo_response'] = array( 'response' => array( 'code' => $code ), 'body' => json_encode( $body ) ); }
$GLOBALS['zalo_http_calls'] = array();
$GLOBALS['zalo_meta'] = array();
$GLOBALS['zalo_events'] = array();
ec_expect( ec_zalo_settings()['enabled'] === false, 'Notifications disabled without configuration' );
$token = '123456789:dummy_test_token';
$cipher = ec_zalo_encrypt( $token );
ec_expect( is_string( $cipher ) && strpos( $cipher, $token ) === false, 'Token encrypted at rest' );
ec_expect( ec_zalo_token( array( 'cipher' => $cipher ) ) === $token, 'Encrypted token round trip' );
ec_expect( ec_zalo_token( array( 'cipher' => 'malformed' ) ) === '', 'Malformed ciphertext fails closed' );
$raw=base64_decode($cipher);$raw[strlen($raw)-1]=chr(ord($raw[strlen($raw)-1])^1);
ec_expect( ec_zalo_token( array( 'cipher' => base64_encode($raw) ) ) === '', 'Tampered ciphertext rejected' );
ec_expect_error( ec_zalo_api( 'deleteWebhook', array(), $token ), 'config', 'Unsupported API method rejected' );
ec_expect_error( ec_zalo_api( 'getMe', array(), '../escape/token' ), 'config', 'Token cannot inject URL path' );
ec_expect( count($GLOBALS['zalo_http_calls'])===0, 'Invalid requests never reach network' );
ec_test_zalo_response(200,array('ok'=>true,'result'=>array('message_id'=>'message-1')));
$settings=array_merge(ec_zalo_settings(),array('enabled'=>true,'cipher'=>$cipher,'chat_id'=>'abc.xyz','version'=>'settings-v1'));
update_option('ec_booking_zalo',$settings,false);
$result=ec_zalo_api('sendMessage',array('chat_id'=>'abc.xyz','text'=>'Test'));
ec_expect( !is_wp_error($result), 'Documented API success recognized' );
$call=end($GLOBALS['zalo_http_calls']);
ec_expect( $call[0]==='https://bot-api.zaloplatforms.com/bot'.$token.'/sendMessage' && $call[1]['redirection']===0 && $call[1]['sslverify']===true, 'Fixed HTTPS endpoint, TLS verification and redirects disabled' );
$GLOBALS['zalo_response']=new WP_Error('http','Failure at https://bot-api.zaloplatforms.com/bot'.$token.'/sendMessage');
$error=ec_zalo_api('sendMessage');
ec_expect( $error->get_error_code()==='uncertain' && strpos($error->get_error_message(),$token)===false, 'Uncertain transport failure never exposes token' );
ec_test_zalo_response(200,array('ok'=>true,'result'=>array()));
ec_expect_error(ec_zalo_api('sendMessage'),'uncertain','Missing message acknowledgement not reported as sent');
ec_test_zalo_response(401,array('ok'=>false,'description'=>'secret '.$token));
ec_expect_error(ec_zalo_api('getMe'),'api','API refusal handled without raw secret-bearing description');
$event=array('message'=>array('text'=>'/nhanlich','from'=>array('display_name'=>'Nhân viên','is_bot'=>false),'chat'=>array('id'=>'abc.xyz')));
ec_expect( ec_zalo_candidates($event)===array('abc.xyz'=>'Nhân viên'), 'Extract recipient from documented event format' );
$other=$event;$other['message']['text']='private unrelated message';
ec_expect( ec_zalo_candidates(array($other,array('message'=>'bad')))===array(), 'Unrelated messages are not stored as recipients' );
$text=ec_zalo_message(get_post(1));$booking_data=ec_booking_read(get_post(1));
ec_expect( strpos($text,$booking_data['name'])===false && strpos($text,$booking_data['phone'])===false && strpos($text,'post=1&action=edit')!==false, 'Notifications contain admin link, not patient name or phone' );
$settings['enabled']=false;update_option('ec_booking_zalo',$settings,false);ec_zalo_queue(1);
ec_expect( empty($GLOBALS['zalo_events']), 'Disabled configuration never queues historical or new notifications' );
$settings['enabled']=true;update_option('ec_booking_zalo',$settings,false);
ec_zalo_queue(1);ec_zalo_queue(1);
ec_expect( count($GLOBALS['zalo_events'])===1 && get_post_meta(1,'_ec_zalo_state')==='pending', 'One queued event per new appointment' );
ec_test_zalo_response(200,array('ok'=>true,'result'=>array('message_id'=>'message-2')));
$before=count($GLOBALS['zalo_http_calls']);ec_zalo_deliver(1);ec_zalo_deliver(1);
ec_expect( get_post_meta(1,'_ec_zalo_state')==='sent' && count($GLOBALS['zalo_http_calls'])===$before+1, 'Repeated worker execution does not duplicate sent notification' );
ec_zalo_queue(2);$settings['version']='new-recipient';update_option('ec_booking_zalo',$settings,false);ec_zalo_deliver(2);
ec_expect( get_post_meta(2,'_ec_zalo_state')==='skipped' && count($GLOBALS['zalo_http_calls'])===$before+1, 'Queued notification never silently switches recipient' );
update_post_meta(2,'_ec_zalo_state','retry');ec_zalo_queue(2);
$GLOBALS['zalo_response']=new WP_Error('timeout','Timed out '.$token);ec_zalo_deliver(2);$calls=count($GLOBALS['zalo_http_calls']);ec_zalo_deliver(2);
ec_expect( get_post_meta(2,'_ec_zalo_state')==='unknown' && count($GLOBALS['zalo_http_calls'])===$calls, 'Ambiguous send does not automatically retry and duplicate' );
ec_expect( strpos(get_post_meta(2,'_ec_zalo_error'),$token)===false, 'Saved delivery error is token-free' );
$GLOBALS['zalo_events']=array();$GLOBALS['zalo_schedule_fail']=true;update_post_meta(2,'_ec_zalo_state','retry');ec_zalo_queue(2);
ec_expect( get_post_meta(2,'_ec_zalo_state')==='failed', 'Queue failure visible without losing saved appointment' );
ec_expect( count($GLOBALS['ec_test_posts'])===2, 'Notification failures do not remove appointments' );
echo 'PASS: '.$assertions." combined booking/Zalo checks; no network messages sent.\n";
