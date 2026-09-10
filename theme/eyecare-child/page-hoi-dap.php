<?php
/**
 * Trang Hỏi đáp.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$tt  = eyecare_du_lieu_thuc_the();
	$con = eyecare_trang_con_hub( 'hoi-dap' );
	$mo_ta = array(
		'hoi-dap-mat-tre-em' => 'Các câu hỏi thường gặp về thị lực học đường và chăm sóc mắt cho trẻ.',
		'mo-mat-va-hoi-phuc' => 'Thông tin cần lưu ý trước và sau các can thiệp, phẫu thuật mắt.',
		'truoc-khi-di-kham'  => 'Những giấy tờ, kính và thông tin nên chuẩn bị trước khi đến bệnh viện.',
	);
	?>

<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-hoi-dap-page' ); ?>>
	<section class="eyecare-page-hero eyecare-page-hero--hoi-dap" aria-labelledby="hoi-dap-tieu-de">
		<div class="eyecare-page-hero__khung">
			<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?><div class="eyecare-page-hero__duong-dan"><?php eyecare_duong_dan_in(); ?></div><?php endif; ?>
			<div class="eyecare-page-hero__grid">
				<div>
					<p class="eyecare-page-hero__nhan">Giải đáp dễ hiểu</p>
					<h1 id="hoi-dap-tieu-de"><?php the_title(); ?></h1>
					<p class="eyecare-page-hero__dan">Tìm nhanh các thông tin thường được người bệnh và gia đình quan tâm trước khi khám, chăm sóc mắt cho trẻ hoặc trong thời gian hồi phục.</p>
				</div>
				<div class="eyecare-page-hero__art" aria-hidden="true">
					<svg viewBox="0 0 260 230"><path d="M38 45h140a22 22 0 0 1 22 22v82a22 22 0 0 1-22 22H98l-38 28 9-28H38a22 22 0 0 1-22-22V67a22 22 0 0 1 22-22Z"/><path d="M92 91c2-19 34-21 38-2 4 18-20 18-20 34M110 145h.1"/><circle cx="214" cy="70" r="31"/><path d="M202 70h24M214 58v24"/></svg>
					<span><strong><?php echo esc_html( str_pad( (string) count( $con ), 2, '0', STR_PAD_LEFT ) ); ?></strong> nhóm câu hỏi</span>
				</div>
			</div>
		</div>
	</section>

	<section class="eyecare-hoi-dap__nhom" aria-labelledby="nhom-hoi-dap-tieu-de">
		<div class="eyecare-noi-dung__khung">
			<header class="eyecare-section-heading"><div><p>Chọn chủ đề</p><h2 id="nhom-hoi-dap-tieu-de">Bạn đang quan tâm điều gì?</h2></div><span>Mỗi nhóm tập trung vào một giai đoạn hoặc đối tượng cụ thể để bạn dễ theo dõi.</span></header>
			<ul class="eyecare-hoi-dap__luoi">
				<?php foreach ( $con as $i => $muc ) :
					$trang = $muc['trang'];
					$slug  = get_post_field( 'post_name', $trang->ID );
					?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $trang->ID ) ); ?>">
							<span class="eyecare-hoi-dap__icon" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<strong><?php echo esc_html( get_the_title( $trang->ID ) ); ?></strong>
							<p><?php echo esc_html( $mo_ta[ $slug ] ?? 'Tổng hợp những câu hỏi thường gặp và thông tin cần biết.' ); ?></p>
							<span class="eyecare-hoi-dap__xem">Xem chủ đề <b aria-hidden="true">→</b></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section class="eyecare-hoi-dap__pho-bien" aria-labelledby="cau-hoi-pho-bien-tieu-de">
		<div class="eyecare-noi-dung__khung eyecare-hoi-dap__pho-bien-grid">
			<header><p>Câu hỏi phổ biến</p><h2 id="cau-hoi-pho-bien-tieu-de">Chuẩn bị trước khi đi khám</h2><span>Thông tin chung giúp buổi khám thuận tiện hơn. Hướng dẫn riêng sẽ phụ thuộc vào tình trạng của từng người.</span></header>
			<div class="eyecare-hoi-dap__accordion">
				<details open><summary>Khi đi khám mắt nên mang theo những gì?</summary><p>Mang kính đang đeo, đơn kính cũ, đơn thuốc, giấy ra viện hoặc kết quả khám mắt trước đây nếu có.</p></details>
				<details><summary>Bệnh viện mở cửa vào thời gian nào?</summary><p>Bệnh viện mở cửa từ <?php echo esc_html( $tt['gio_mo'] ); ?> đến <?php echo esc_html( $tt['gio_dong'] ); ?>, tất cả các ngày trong tuần.</p></details>
				<details><summary>Người đeo kính áp tròng cần lưu ý gì?</summary><p>Nên tháo kính áp tròng trước khi đến. Khi gọi tổng đài, bạn có thể hỏi thêm về thời gian cần tháo phù hợp với loại kính đang sử dụng.</p></details>
				<details><summary>Khi nào nên có người thân đi cùng?</summary><p>Nếu buổi khám có thể cần nhỏ thuốc giãn đồng tử, thị lực có thể mờ và chói tạm thời. Có người đi cùng sẽ thuận tiện và an toàn hơn khi về.</p></details>
				<details><summary>Dấu hiệu nào cần đi khám ngay?</summary><p>Đột ngột mất thị lực, đau mắt dữ dội, thấy chớp sáng hoặc màn đen che một phần tầm nhìn là những dấu hiệu cần đến cơ sở khám mắt gần nhất sớm, không chờ tư vấn trực tuyến.</p></details>
			</div>
		</div>
	</section>

	<section class="eyecare-page-cta"><div class="eyecare-noi-dung__khung eyecare-page-cta__trong"><div><p>Chưa tìm thấy câu trả lời?</p><h2>Gọi tổng đài để được hướng dẫn</h2></div><a class="eyecare-nut eyecare-nut--chinh" href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">Gọi <?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a></div></section>
</article>

	<?php
endwhile;

get_footer();

