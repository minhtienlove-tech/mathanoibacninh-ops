<?php
/**
 * Tài liệu API đặt lịch dùng nội bộ, để gửi link cho lập trình viên hoặc trợ lý AI (Codex, Claude):
 * - /api-dat-lich/                              bản HTML cho người đọc
 * - /api-dat-lich.md                            bản Markdown thô cho AI
 * - /api-dat-lich/sdk/ec-booking.mjs            SDK Node.js
 * - /api-dat-lich/sdk/EcBookingClient.php.txt   SDK PHP (đuôi .txt để máy chủ không chạy file)
 * Không lập chỉ mục (noindex), không gắn menu, không có trong sitemap. Không cần đăng nhập để AI đọc được.
 * Nội dung lấy từ content/api-dat-lich.md và content/sdk/; sửa các file đó là trang tự cập nhật.
 * Tài liệu không chứa khóa bí mật nào.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Danh sách cố định đường dẫn → file; không có đường dẫn nào khác được phục vụ. */
function ec_api_docs_routes() {
	return array(
		'/api-dat-lich'                            => array( 'api-dat-lich.md', 'html' ),
		'/api-dat-lich.md'                         => array( 'api-dat-lich.md', 'text/markdown' ),
		'/api-dat-lich/sdk/ec-booking.mjs'          => array( 'sdk/ec-booking.mjs', 'text/javascript' ),
		'/api-dat-lich/sdk/EcBookingClient.php.txt' => array( 'sdk/EcBookingClient.php.txt', 'text/plain' ),
	);
}

function ec_api_docs_match( $uri ) {
	$path = (string) wp_parse_url( (string) $uri, PHP_URL_PATH );
	$path = '/' === $path ? $path : rtrim( $path, '/' );
	return ec_api_docs_routes()[ $path ] ?? null;
}

/** Chữ trong một dòng: `mã`, **đậm**, [liên kết](https://...). Thoát HTML trước, chỉ nhận liên kết http(s). */
function ec_api_docs_inline( $text ) {
	$codes = array();
	$text  = preg_replace_callback( '/`([^`]+)`/', function ( $m ) use ( &$codes ) {
		$codes[] = '<code>' . esc_html( $m[1] ) . '</code>';
		return "\u{E000}" . ( count( $codes ) - 1 ) . "\u{E001}";
	}, $text );
	$html = esc_html( $text );
	$html = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $html );
	$html = preg_replace_callback( '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', function ( $m ) {
		return '<a href="' . esc_url( html_entity_decode( $m[2], ENT_QUOTES ) ) . '">' . $m[1] . '</a>';
	}, $html );
	return preg_replace_callback( "/\u{E000}(\d+)\u{E001}/u", function ( $m ) use ( $codes ) {
		return $codes[ (int) $m[1] ] ?? '';
	}, $html );
}

/** Markdown đủ cho tài liệu này: tiêu đề, đoạn, danh sách, bảng, khối mã. */
function ec_api_docs_render( $markdown ) {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $markdown );
	$html  = '';
	$para  = array();
	$list  = array();
	$flush = function () use ( &$html, &$para, &$list ) {
		if ( $para ) {
			$html .= '<p>' . ec_api_docs_inline( implode( ' ', $para ) ) . "</p>\n";
			$para  = array();
		}
		if ( $list ) {
			$html .= "<ul>\n<li>" . implode( "</li>\n<li>", array_map( 'ec_api_docs_inline', $list ) ) . "</li>\n</ul>\n";
			$list  = array();
		}
	};
	for ( $i = 0, $n = count( $lines ); $i < $n; $i++ ) {
		$line = $lines[ $i ];
		if ( preg_match( '/^```(\w*)\s*$/', $line, $m ) ) {
			$flush();
			$code = array();
			for ( $i++; $i < $n && ! preg_match( '/^```\s*$/', $lines[ $i ] ); $i++ ) {
				$code[] = $lines[ $i ];
			}
			$html .= '<pre><code' . ( '' !== $m[1] ? ' class="language-' . esc_attr( $m[1] ) . '"' : '' ) . '>' . esc_html( implode( "\n", $code ) ) . "</code></pre>\n";
			continue;
		}
		if ( preg_match( '/^(#{1,4})\s+(.+)$/', $line, $m ) ) {
			$flush();
			$level = strlen( $m[1] );
			$html .= '<h' . $level . '>' . ec_api_docs_inline( trim( $m[2] ) ) . '</h' . $level . ">\n";
			continue;
		}
		if ( preg_match( '/^\|.*\|\s*$/', $line ) && isset( $lines[ $i + 1 ] ) && preg_match( '/^\|[\s:|-]+\|\s*$/', $lines[ $i + 1 ] ) ) {
			$flush();
			$cells = function ( $row ) {
				return array_map( 'trim', explode( '|', trim( trim( $row ), '|' ) ) );
			};
			$html .= "<table>\n<thead><tr><th>" . implode( '</th><th>', array_map( 'ec_api_docs_inline', $cells( $line ) ) ) . "</th></tr></thead>\n<tbody>\n";
			for ( $i += 2; $i < $n && preg_match( '/^\|.*\|\s*$/', $lines[ $i ] ); $i++ ) {
				$html .= '<tr><td>' . implode( '</td><td>', array_map( 'ec_api_docs_inline', $cells( $lines[ $i ] ) ) ) . "</td></tr>\n";
			}
			$i--;
			$html .= "</tbody>\n</table>\n";
			continue;
		}
		if ( preg_match( '/^[-*]\s+(.+)$/', $line, $m ) ) {
			if ( $para ) {
				$flush();
			}
			$list[] = $m[1];
			continue;
		}
		if ( '' === trim( $line ) ) {
			$flush();
			continue;
		}
		if ( $list ) {
			$flush();
		}
		$para[] = trim( $line );
	}
	$flush();
	return $html;
}

function ec_api_docs_page( $body ) {
	return '<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">'
		. '<title>API đặt lịch khám – tài liệu nội bộ</title><style>'
		. 'body{margin:0;background:#f6faf7;color:#193b30;font:16px/1.65 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}'
		. 'main{box-sizing:border-box;max-width:920px;margin:0 auto;padding:28px 20px 60px}'
		. 'h1{font-size:1.9rem;line-height:1.25}h2{margin-top:2.2em;padding-top:.6em;border-top:1px solid #d9eadf}h3{margin-top:1.6em}'
		. 'code{background:#e8f2ec;padding:.1em .35em;border-radius:4px;font-size:.9em}'
		. 'pre{overflow:auto;background:#10261d;color:#e6f2ea;padding:14px 16px;border-radius:8px}pre code{background:none;padding:0;color:inherit}'
		. 'table{width:100%;border-collapse:collapse;display:block;overflow-x:auto}th,td{border:1px solid #d9eadf;padding:6px 10px;text-align:left;vertical-align:top}th{background:#ecf5ef}'
		. 'a{color:#0b5f32}'
		. '</style></head><body><main>' . $body . '</main></body></html>';
}

/** Phản hồi cho đường dẫn tài liệu, hoặc null nếu không phải đường dẫn tài liệu. */
function ec_api_docs_response( $uri, $method ) {
	$route = ec_api_docs_match( $uri );
	if ( ! $route || ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
		return null;
	}
	$file = get_stylesheet_directory() . '/content/' . $route[0];
	if ( ! is_readable( $file ) ) {
		return null;
	}
	$source = (string) file_get_contents( $file );
	$html   = 'html' === $route[1];
	return array(
		'status'  => 200,
		'headers' => array(
			'Content-Type'           => ( $html ? 'text/html' : $route[1] ) . '; charset=UTF-8',
			'X-Robots-Tag'           => 'noindex, nofollow',
			'X-Content-Type-Options' => 'nosniff',
			'Cache-Control'          => 'no-cache, must-revalidate, max-age=0',
		),
		'body'    => $html ? ec_api_docs_page( ec_api_docs_render( $source ) ) : $source,
	);
}

function ec_api_docs_serve() {
	$response = ec_api_docs_response( $_SERVER['REQUEST_URI'] ?? '', $_SERVER['REQUEST_METHOD'] ?? 'GET' );
	if ( ! $response ) {
		return;
	}
	status_header( $response['status'] );
	header( 'X-LiteSpeed-Cache-Control: no-cache' );
	do_action( 'litespeed_control_set_nocache', 'internal API docs' );
	foreach ( $response['headers'] as $name => $value ) {
		header( $name . ': ' . $value );
	}
	if ( 'HEAD' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
		echo $response['body']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã thoát trong ec_api_docs_render; bản thô là text/markdown, text/plain.
	}
	exit;
}
add_action( 'template_redirect', 'ec_api_docs_serve', -1 );
