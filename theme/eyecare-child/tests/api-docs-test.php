<?php
/**
 * Tài liệu API nội bộ: chỉ phục vụ đúng các đường dẫn đã khai báo, luôn noindex,
 * bản HTML thoát mọi HTML trong nguồn, chữ ký mẫu trong tài liệu khớp máy chủ.
 * Chạy: php theme/eyecare-child/tests/api-docs-test.php
 */
define( 'ABSPATH', __DIR__ );
function add_action( ...$args ) {}
function do_action( ...$args ) {}
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return preg_match( '#^https?://#i', (string) $url ) ? htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' ) : ''; }
function get_stylesheet_directory() { return dirname( __DIR__ ); }

require dirname( __DIR__ ) . '/inc/tai-lieu-api.php';
require dirname( __DIR__ ) . '/inc/api-dat-lich.php';

$assertions = 0;
function ec_expect( $condition, $message ) {
	global $assertions;
	++$assertions;
	if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $message . "\n" ); exit( 1 ); }
}

// Chỉ đúng các đường dẫn đã khai báo.
foreach ( array( '/api-dat-lich', '/api-dat-lich/', '/api-dat-lich/?utm=x', '/api-dat-lich.md', '/api-dat-lich/sdk/ec-booking.mjs', '/api-dat-lich/sdk/EcBookingClient.php.txt' ) as $uri ) {
	ec_expect( null !== ec_api_docs_match( $uri ), 'Route served: ' . $uri );
}
foreach ( array( '/', '/api-dat-lich/sdk/', '/api-dat-lich/../wp-config.php', '/api-dat-lich/sdk/../../wp-config.php', '/api-dat-lich/sdk/EcBookingClient.php', '/api-dat-lich.md.bak', '/content/api-dat-lich.md', '/API-DAT-LICH.md' ) as $uri ) {
	ec_expect( null === ec_api_docs_match( $uri ), 'Route not served: ' . $uri );
}
ec_expect( null === ec_api_docs_response( '/api-dat-lich/', 'POST' ), 'Only GET/HEAD answered' );

// Bản HTML: 200, noindex ở cả header và thẻ meta, không cache, không còn ký hiệu Markdown thô.
$page = ec_api_docs_response( '/api-dat-lich/', 'GET' );
ec_expect( 200 === $page['status'] && 0 === strpos( $page['headers']['Content-Type'], 'text/html' ), 'HTML page returns 200 text/html' );
ec_expect( 'noindex, nofollow' === $page['headers']['X-Robots-Tag'] && false !== strpos( $page['body'], '<meta name="robots" content="noindex,nofollow">' ), 'HTML page is noindex by header and meta tag' );
ec_expect( false !== strpos( $page['headers']['Cache-Control'], 'no-cache' ), 'HTML page not cached' );
$html = $page['body'];
ec_expect( false !== strpos( $html, '<h1>API đặt lịch khám và form nhúng</h1>' ), 'Title rendered as H1' );
ec_expect( substr_count( $html, '<table>' ) >= 2 && false !== strpos( $html, '<th>HTTP</th>' ), 'Tables rendered' );
ec_expect( false !== strpos( $html, '<pre><code class="language-bash">' ) && false !== strpos( $html, '<pre><code class="language-json">' ), 'Code blocks keep their language' );
ec_expect( false !== strpos( $html, '&lt;div data-ec-booking-key=&quot;pk_...&quot;&gt;&lt;/div&gt;' ) && false === strpos( $html, '<div data-ec-booking-key' ), 'Embed snippet shown as text, not live HTML' );
ec_expect( false === strpos( $html, '<script src=' ), 'Embed script tag is not executed on the docs page' );
ec_expect( false !== strpos( $html, '<strong>gửi lại cùng <code>request_id</code></strong>' ), 'Bold around inline code renders' );
ec_expect( false !== strpos( $html, '<a href="https://mathanoibacninh.com/api-dat-lich.md">' ), 'Markdown link for AI rendered' );
$text = strip_tags( preg_replace( '#<pre>.*?</pre>#s', '', $html ) );
ec_expect( false === strpos( $text, '**' ) && false === strpos( $text, '```' ) && ! preg_match( '/^#{1,4} /m', $text ), 'No raw Markdown left outside code blocks' );
ec_expect( false === strpos( $html, "\u{E000}" ) && false === strpos( $html, "\u{E001}" ), 'No inline-code placeholders leak' );
ec_expect( ! preg_match( '/sk_[a-f0-9]{64}/', $html ), 'No real-looking secret key in the docs' );

// Chống chèn HTML từ nguồn Markdown.
$evil = ec_api_docs_render( "# <img src=x onerror=alert(1)>\n\n[bấm](javascript:alert(1)) và `<script>` **<b>x</b>**\n\n| a | b |\n|---|---|\n| <i>c</i> | d |" );
ec_expect( false === strpos( $evil, '<img' ) && false === strpos( $evil, '<script>' ) && false === strpos( $evil, '<b>' ) && false === strpos( $evil, '<i>' ), 'Raw HTML in the source is escaped' );
ec_expect( false === strpos( $evil, 'href="javascript' ), 'Only http(s) links become anchors' );

// Bản thô cho AI và SDK: nguyên văn file, đúng loại nội dung, noindex.
$md = ec_api_docs_response( '/api-dat-lich.md', 'GET' );
ec_expect( 0 === strpos( $md['headers']['Content-Type'], 'text/markdown' ) && 'noindex, nofollow' === $md['headers']['X-Robots-Tag'], 'Markdown served as text/markdown, noindex' );
ec_expect( file_get_contents( dirname( __DIR__ ) . '/content/api-dat-lich.md' ) === $md['body'], 'Markdown served byte for byte' );
$js  = ec_api_docs_response( '/api-dat-lich/sdk/ec-booking.mjs', 'GET' );
$php = ec_api_docs_response( '/api-dat-lich/sdk/EcBookingClient.php.txt', 'GET' );
ec_expect( 0 === strpos( $js['headers']['Content-Type'], 'text/javascript' ) && false !== strpos( $js['body'], 'export class EcBookingClient' ), 'Node SDK served' );
ec_expect( 0 === strpos( $php['headers']['Content-Type'], 'text/plain' ) && false !== strpos( $php['body'], 'class EcBookingClient' ), 'PHP SDK served as plain text, never executed' );

// Chữ ký mẫu trong tài liệu phải khớp hàm ký của máy chủ.
$source = $md['body'];
ec_expect( preg_match( '/^(\{"name":"[^\n]+\})$/mu', $source, $body ) && preg_match( '/Chữ ký đúng: `([a-f0-9]{64})`/u', $source, $sig ), 'Docs contain the signature example' );
ec_expect( ec_booking_api_sign( 'sk_test_example', '1800000000', 'POST', '/ec-booking/v1/appointments', $body[1] ) === $sig[1], 'Docs signature example matches the server' );
preg_match_all( '#/ec-booking/v1(/[a-z/{}]*)#', $source, $paths );
foreach ( array_unique( $paths[1] ) as $path ) {
	ec_expect( in_array( $path, array( '', '/slots', '/appointments', '/appointments/{id}' ), true ), 'Documented endpoint exists: ' . $path );
}

echo 'PASS: ' . $assertions . " internal API docs checks.\n";
