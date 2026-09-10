<?php
/**
 * ĐẦU TRANG — khung trang tự dựng, KHÔNG dùng header của Flatsome.
 *
 * VÌ SAO CÓ TỆP NÀY:
 * WordPress ưu tiên tệp cùng tên ở theme con hơn theme mẹ. Tệp này tồn tại là
 * header.php của Flatsome không còn được gọi — theme làm chủ hoàn toàn khung
 * trang, thay vì chèn logo/menu vào bộ render của Flatsome bằng filter.
 *
 * Cách chèn bằng filter trước đây có lỗi không sửa được từ ngoài: menu 6 mục
 * tràn xuống dòng thứ hai, mục "Liên hệ" rớt xuống đè ô tìm kiếm. Tự dựng thì
 * kiểm soát được cả bố cục lẫn thứ tự thẻ.
 *
 * KHÔNG GHI CƠ SỞ DỮ LIỆU:
 * Bản localhost này đồng bộ định kỳ từ nguồn khác, mọi thứ ghi vào DB
 * (theme_mod, bản ghi menu) sẽ bị đè mất. Tệp theme KHÔNG nằm trong phạm vi
 * đồng bộ. Cũng vì thế theme KHÔNG được kích hoạt lại: style.css vẫn khai
 * `Template: flatsome` nên bản ghi theme đang dùng không đổi.
 *
 * GIỮ NGUYÊN wp_head() VÀ wp_body_open():
 * Đó là chỗ WordPress và plugin gắn thẻ meta, CSS, JS, và là chỗ schema y tế
 * (inc/schema-y-te.php) in khối JSON-LD. Bỏ đi là mất cả schema lẫn thanh
 * quản trị.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eyecare_tt = eyecare_du_lieu_thuc_the();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="eyecare-bo-qua" href="#noi-dung">Bỏ qua, tới nội dung chính</a>

<?php
/* ==========================================================================
   THANH TRÊN — tổng đài và giờ mở cửa
   --------------------------------------------------------------------------
   Người tìm một bệnh viện mắt thường cần đúng hai thứ trước tiên: gọi được
   cho ai, và mấy giờ mở cửa. Đặt sẵn trên cùng để không phải đi tìm.

   Số tổng đài lấy từ eyecare_du_lieu_thuc_the() — nguồn sự thật duy nhất.
   Đổi số thì sửa ở đó, KHÔNG viết số trực tiếp vào đây.
   ========================================================================== */
?>
<div class="eyecare-thanh-tren">
	<div class="eyecare-khung-trang eyecare-thanh-tren__trong">

		<span class="eyecare-thanh-tren__muc">
			Mở cửa <?php echo esc_html( $eyecare_tt['gio_mo'] . ' – ' . $eyecare_tt['gio_dong'] ); ?>, cả tuần
		</span>

		<span class="eyecare-thanh-tren__muc">
			Tổng đài
			<a href="tel:<?php echo esc_attr( $eyecare_tt['dien_thoai'] ); ?>">
				<?php echo esc_html( $eyecare_tt['dien_thoai_hien'] ); ?>
			</a>
		</span>

	</div>
</div>

<header class="eyecare-dau-trang">
	<div class="eyecare-khung-trang eyecare-dau-trang__trong">

		<?php
		/* Logo thương hiệu THẬT — khối xếp dọc (mắt xanh + tên bệnh viện + sao
		   vàng + slogan). Tệp nằm trong theme con để sống sót qua đồng bộ.

		   🔴 Kho ảnh có nhiều tệp tên gây nhầm, KHÔNG dùng:
		     - cropped-logo-duong-ban-ngang: favicon vuông, không có tên.
		     - Thanh-ngang-*: dải icon trang trí, không phải logo.
		     - logo-duong-ban-ngang: logo "BỆNH VIỆN MẮT SÀI GÒN" — ĐƠN VỊ KHÁC.
		   Bản đúng thương hiệu duy nhất là logo xếp dọc, lưu tại
		   assets/logo-benhvien.webp.

		   ALT để rỗng có chủ đích: logo nằm ngay cạnh tên bệnh viện dạng chữ ở
		   thanh trên và chân trang, đọc lại lần nữa là lặp cho người dùng trình
		   đọc màn hình. Thẻ <a> đã có aria-label mang tên đầy đủ. */
		?>
		<a class="eyecare-hieu"
			href="<?php echo esc_url( home_url( '/' ) ); ?>"
			aria-label="<?php echo esc_attr( $eyecare_tt['ten'] ) ; ?> — về trang chủ"
			rel="home">
			<img class="eyecare-hieu__anh"
				src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/logo-benhvien.webp' ); ?>"
				width="1024" height="765" alt=""
				decoding="async" fetchpriority="high">
		</a>

		<?php
		/* Nút mở menu — chỉ hiện trên màn hình hẹp (CSS). aria-expanded và
		   aria-controls do JS nhỏ ở footer.php cập nhật; để sẵn giá trị đóng
		   để trạng thái đúng ngay cả khi JS chưa chạy. */
		?>
		<button class="eyecare-menu-nut"
			type="button"
			aria-expanded="false"
			aria-controls="menu-chinh">
			<span class="eyecare-menu-nut__vach" aria-hidden="true"></span>
			Menu
		</button>

		<?php
		/* ==================================================================
		   MENU CHÍNH
		   Dựng từ code, KHÔNG đọc menu trong DB — vừa tránh bị đồng bộ xoá,
		   vừa kiểm soát được đích đến: chỉ trỏ tới trang ĐÃ PUBLISH.

		   🔴 KHÔNG có "Đặt lịch khám": bệnh viện chưa có Giấy phép hoạt động
		   (B-03), chưa được quảng cáo dịch vụ khám chữa bệnh. Cùng một cửa đã
		   giữ nút đặt lịch khỏi trang chủ.

		   Danh sách mục ở eyecare_menu_muc() — inc/dau-trang.php.
		   ================================================================== */
		eyecare_menu_chinh_in();
		?>

	</div>
</header>

<?php
/* Một thẻ <main> DUY NHẤT cho cả site, mở ở đây và đóng ở footer.php.
   Bản cũ có hai thẻ <main> lồng nhau (header Flatsome mở <main id="main">,
   front-page.php lại mở <main id="trang-chu">) — sai cấu trúc HTML. */
?>
<main id="noi-dung">
