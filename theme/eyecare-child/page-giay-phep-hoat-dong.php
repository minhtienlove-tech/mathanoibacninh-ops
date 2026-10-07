<?php
/**
 * Trang công bố Giấy phép hoạt động khám bệnh, chữa bệnh.
 *
 * Mọi thông tin trên trang này đọc từ eyecare_du_lieu_thuc_the() — nguồn sự
 * thật duy nhất của theme — nên không có chỗ nào gõ lại số giấy phép bằng tay.
 *
 * NGUYÊN TẮC: KHÔNG BỊA, KHÔNG SUY DIỄN.
 * - Chưa có số giấy phép (eyecare_co_gphd() === false) thì trang in thông báo
 *   chưa công bố thay vì dựng một bảng thông tin rỗng.
 * - Giấy phép cấp 06/10/2026 ghi “tỉnh Bắc Ninh”, còn địa danh hành chính
 *   hiện hành là “Thành phố Bắc Ninh” (Nghị quyết 202/2025/QH15 và
 *   39/2026/QH16). Trang hiển thị địa chỉ hiện hành ở bảng thông tin và
 *   trích NGUYÊN VĂN bản giấy trong khối riêng, có ghi chú lý do khác nhau.
 * - Giờ hoạt động trên giấy phép là “24/24 giờ” nhưng bệnh viện CHƯA xác nhận
 *   có trực đêm tiếp nhận người bệnh, nên trường gphd_gio_tren_giay để rỗng và
 *   trang chỉ công bố giờ tiếp nhận khám theo lịch. Không mời người bệnh đến
 *   vào giờ chưa chắc có người tiếp.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$eyecare_gp_tt        = eyecare_du_lieu_thuc_the();
$eyecare_gp_co        = eyecare_co_gphd();
$eyecare_gp_ngay      = eyecare_gphd_ngay_hien();
$eyecare_gp_anh       = eyecare_gphd_anh_url();
$eyecare_gp_cap_nhat  = wp_date( 'd/m/Y', max( (int) get_post_modified_time( 'U', true ), (int) filemtime( __FILE__ ) ) );

/* Số thứ tự mục: đếm theo mục thật sự được in, vì vài mục có thể bị ẩn khi
   dữ liệu tương ứng để rỗng. Tính tay dễ ra hai mục cùng số. */
$eyecare_gp_stt = 0;
$eyecare_gp_so  = static function () use ( &$eyecare_gp_stt ) {
	$eyecare_gp_stt++;
	return str_pad( (string) $eyecare_gp_stt, 2, '0', STR_PAD_LEFT );
};

while ( have_posts() ) :
	the_post();
	?>

<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-gphd' ); ?>>
	<section class="eyecare-page-hero eyecare-page-hero--gphd" aria-labelledby="gphd-tieu-de">
		<div class="eyecare-page-hero__khung">
			<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?><div class="eyecare-page-hero__duong-dan"><?php eyecare_duong_dan_in(); ?></div><?php endif; ?>
			<div class="eyecare-page-hero__grid">
				<div>
					<p class="eyecare-page-hero__nhan">Hồ sơ pháp lý công khai</p>
					<h1 id="gphd-tieu-de"><?php the_title(); ?></h1>
					<p class="eyecare-page-hero__dan">Người bệnh có quyền biết cơ sở mình đến khám đã được cấp phép hay chưa, do cơ quan nào cấp và phạm vi hoạt động là gì. Trang này công bố nguyên văn thông tin trên giấy phép hoạt động khám bệnh, chữa bệnh của bệnh viện.</p>
					<p class="eyecare-gphd__ngay">Cập nhật lần cuối: <strong><?php echo esc_html( $eyecare_gp_cap_nhat ); ?></strong></p>
				</div>
				<div class="eyecare-page-hero__art" aria-hidden="true"><svg viewBox="0 0 260 230"><path d="M70 24h85l45 44v138H70Z"/><path d="M155 24v44h45M95 104h110M95 134h110M95 164h70"/><circle cx="188" cy="168" r="30"/><path d="m174 168 10 10 19-22"/></svg><span><strong>Bộ Y tế</strong>cơ quan cấp phép</span></div>
			</div>
		</div>
	</section>

	<?php if ( ! $eyecare_gp_co ) : ?>
	<div class="eyecare-noi-dung__khung eyecare-gphd__chua-co">
		<p><strong>Thông tin giấy phép chưa được công bố trên trang này.</strong> Để đối chiếu hồ sơ pháp lý của bệnh viện, vui lòng gọi tổng đài <a href="tel:<?php echo esc_attr( $eyecare_gp_tt['dien_thoai'] ); ?>"><?php echo esc_html( $eyecare_gp_tt['dien_thoai_hien'] ); ?></a> hoặc đề nghị xem bản gốc tại quầy tiếp nhận.</p>
	</div>
	<?php else : ?>

	<div class="eyecare-gphd__noi-bat eyecare-noi-dung__khung" aria-label="Thông tin chính của giấy phép">
		<div>
			<span aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M8 4h11l5 5v19H8Z"/><path d="M19 4v5h5"/><path d="m12 18 2.5 2.5L21 14"/></svg></span>
			<p><strong>Số <?php echo esc_html( $eyecare_gp_tt['so_gphd'] ); ?></strong><small>Giấy phép hoạt động khám bệnh, chữa bệnh</small></p>
		</div>
		<div>
			<span aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M16 4 6 9v8c0 7 4.3 11.4 10 13 5.7-1.6 10-6 10-13V9L16 4Z"/><path d="m12 16 3 3 6-7"/></svg></span>
			<p><strong><?php echo esc_html( $eyecare_gp_tt['gphd_co_quan'] ); ?></strong><small>Cơ quan cấp phép</small></p>
		</div>
		<?php if ( '' !== $eyecare_gp_ngay ) : ?>
		<div>
			<span aria-hidden="true"><svg viewBox="0 0 32 32"><rect x="5" y="8" width="22" height="19" rx="3"/><path d="M5 14h22M11 5v5M21 5v5"/></svg></span>
			<p><strong><?php echo esc_html( $eyecare_gp_ngay ); ?></strong><small>Ngày cấp</small></p>
		</div>
		<?php endif; ?>
	</div>

	<div class="eyecare-gphd__bo-cuc eyecare-noi-dung__khung">
		<div class="eyecare-gphd__noi-dung">
			<header class="eyecare-gphd__mo-dau">
				<p>Vì sao trang này tồn tại</p>
				<h2>Kiểm tra trước khi đi khám</h2>
				<span>Theo Luật Khám bệnh, chữa bệnh, một cơ sở chỉ được khám chữa bệnh khi đã có giấy phép hoạt động do cơ quan có thẩm quyền cấp. Thông tin dưới đây giúp bạn đối chiếu với bản gốc được niêm yết tại bệnh viện, hoặc tra cứu trên hệ thống của Bộ Y tế.</span>
			</header>

			<section id="thong-tin-giay-phep">
				<span><?php echo esc_html( $eyecare_gp_so() ); ?></span>
				<h2>Thông tin trên giấy phép</h2>
				<dl class="eyecare-gphd__bang">
					<div><dt>Tên cơ sở</dt><dd><?php echo esc_html( $eyecare_gp_tt['ten'] ); ?></dd></div>
					<div><dt>Hình thức tổ chức</dt><dd><?php echo esc_html( $eyecare_gp_tt['gphd_hinh_thuc'] ); ?></dd></div>
					<div><dt>Số giấy phép</dt><dd><?php echo esc_html( $eyecare_gp_tt['so_gphd'] ); ?></dd></div>
					<?php if ( '' !== $eyecare_gp_ngay ) : ?>
					<div><dt>Ngày cấp</dt><dd><?php echo esc_html( $eyecare_gp_ngay ); ?></dd></div>
					<?php endif; ?>
					<div><dt>Cơ quan cấp</dt><dd><?php echo esc_html( $eyecare_gp_tt['gphd_co_quan'] ); ?></dd></div>
					<?php if ( ! empty( $eyecare_gp_tt['gphd_nguoi_ky'] ) ) : ?>
					<div><dt>Người ký</dt><dd><?php echo esc_html( $eyecare_gp_tt['gphd_nguoi_ky'] ); ?></dd></div>
					<?php endif; ?>
					<div><dt>Chủ sở hữu</dt><dd><?php echo esc_html( $eyecare_gp_tt['phap_nhan'] ); ?></dd></div>
					<div><dt>Mã số thuế</dt><dd><?php echo esc_html( $eyecare_gp_tt['mst'] ); ?></dd></div>
					<div><dt>Địa chỉ</dt><dd><?php echo esc_html( eyecare_dia_chi_day_du() ); ?></dd></div>
					<div><dt>Phạm vi chuyên môn</dt><dd>Chuyên khoa mắt</dd></div>
				</dl>
			</section>

			<?php if ( ! empty( $eyecare_gp_tt['gphd_dia_chi_nguyen_van'] ) ) : ?>
			<section id="dia-chi-nguyen-van">
				<span><?php echo esc_html( $eyecare_gp_so() ); ?></span>
				<h2>Địa chỉ ghi trên bản giấy</h2>
				<blockquote class="eyecare-gphd__trich">
					<p><?php echo esc_html( $eyecare_gp_tt['gphd_dia_chi_nguyen_van'] ); ?></p>
					<cite>Trích nguyên văn giấy phép số <?php echo esc_html( $eyecare_gp_tt['so_gphd'] ); ?></cite>
				</blockquote>
				<p>Giấy phép ghi “tỉnh Bắc Ninh” theo thời điểm cấp. Từ ngày 20/09/2026, theo Nghị quyết 202/2025/QH15 và Nghị quyết 39/2026/QH16, địa bàn này thuộc <strong>Thành phố Bắc Ninh</strong>. Vì vậy website dùng địa danh hành chính hiện hành khi chỉ đường và ghi địa chỉ, còn khối trích dẫn trên giữ đúng chữ trên bản giấy. Đây là cùng một địa điểm, không phải hai cơ sở khác nhau.</p>
			</section>
			<?php endif; ?>

			<section id="gio-tiep-nhan">
				<span><?php echo esc_html( $eyecare_gp_so() ); ?></span>
				<h2>Giờ tiếp nhận người bệnh</h2>
				<p>Bệnh viện tiếp nhận khám theo lịch từ <strong><?php echo esc_html( $eyecare_gp_tt['gio_mo'] ); ?> đến <?php echo esc_html( $eyecare_gp_tt['gio_dong'] ); ?></strong>, tất cả các ngày trong tuần. Nếu bạn cần đến ngoài khung giờ này, hãy gọi tổng đài <a href="tel:<?php echo esc_attr( $eyecare_gp_tt['dien_thoai'] ); ?>"><?php echo esc_html( $eyecare_gp_tt['dien_thoai_hien'] ); ?></a> trước để được hướng dẫn, thay vì đến mà chưa hẹn.</p>
				<?php if ( ! empty( $eyecare_gp_tt['gphd_gio_tren_giay'] ) ) : ?>
				<p>Giờ hoạt động ghi trên giấy phép: <strong><?php echo esc_html( $eyecare_gp_tt['gphd_gio_tren_giay'] ); ?></strong>.</p>
				<?php endif; ?>
				<div class="eyecare-gphd__luu-y">
					<span aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M13.7 4.9 2.4 23.9A2 2 0 0 0 4.1 27h23.8a2 2 0 0 0 1.7-3.1L18.3 4.9a2 2 0 0 0-4.6 0Z"/><path d="M16 12v6M16 22h.01"/></svg></span>
					<p>Nếu bạn <strong>đột ngột mất thị lực, đau mắt dữ dội, bị hóa chất hoặc dị vật bắn vào mắt</strong>, hãy đến cơ sở cấp cứu gần nhất ngay, không chờ giờ hành chính và không chờ đặt lịch.</p>
				</div>
			</section>

			<?php if ( '' !== $eyecare_gp_anh ) : ?>
			<section id="ban-chup">
				<span><?php echo esc_html( $eyecare_gp_so() ); ?></span>
				<h2>Bản chụp giấy phép</h2>
				<p>Ảnh dưới đây là bản chụp giấy phép do bệnh viện cung cấp, đăng để người bệnh đối chiếu nhanh. Bản gốc được lưu tại bệnh viện; bạn có thể đề nghị xem trực tiếp tại quầy tiếp nhận.</p>
				<figure class="eyecare-gphd__anh">
					<a href="<?php echo esc_url( $eyecare_gp_anh ); ?>" target="_blank" rel="noopener noreferrer">
						<img
							src="<?php echo esc_url( $eyecare_gp_anh ); ?>"
							<?php if ( ! empty( $eyecare_gp_tt['gphd_anh_rong'] ) && ! empty( $eyecare_gp_tt['gphd_anh_cao'] ) ) : ?>
							width="<?php echo esc_attr( (int) $eyecare_gp_tt['gphd_anh_rong'] ); ?>"
							height="<?php echo esc_attr( (int) $eyecare_gp_tt['gphd_anh_cao'] ); ?>"
							<?php endif; ?>
							loading="lazy"
							decoding="async"
							alt="Bản chụp Giấy phép hoạt động khám bệnh, chữa bệnh số <?php echo esc_attr( $eyecare_gp_tt['so_gphd'] ); ?> do <?php echo esc_attr( $eyecare_gp_tt['gphd_co_quan'] ); ?> cấp cho <?php echo esc_attr( $eyecare_gp_tt['ten'] ); ?>">
					</a>
					<figcaption>Bấm vào ảnh để xem ở kích thước lớn. Thông tin dạng chữ ở mục 01 là bản gõ lại để máy đọc màn hình và công cụ tìm kiếm đọc được.</figcaption>
				</figure>
			</section>
			<?php endif; ?>

			<section id="doi-chieu" class="eyecare-gphd__lien-he">
				<span><?php echo esc_html( $eyecare_gp_so() ); ?></span>
				<h2>Đối chiếu và khiếu nại</h2>
				<p>Nếu thông tin trên trang này khác với bản gốc bạn thấy tại bệnh viện, hoặc bạn cần xác minh phạm vi hoạt động trước khi quyết định điều trị, hãy liên hệ để được cung cấp bản đối chiếu:</p>
				<p><strong>Đơn vị chịu trách nhiệm:</strong> <?php echo esc_html( $eyecare_gp_tt['phap_nhan'] ); ?></p>
				<p><strong>Địa chỉ:</strong> <?php echo esc_html( eyecare_dia_chi_day_du() ); ?></p>
				<p><strong>Điện thoại:</strong> <a href="tel:<?php echo esc_attr( $eyecare_gp_tt['dien_thoai'] ); ?>"><?php echo esc_html( $eyecare_gp_tt['dien_thoai_hien'] ); ?></a></p>
				<p>Giấy phép hoạt động của cơ sở và chứng chỉ hành nghề của từng bác sĩ là hai loại giấy tờ khác nhau. Thông tin đội ngũ bác sĩ được công bố tại trang <a href="<?php echo esc_url( home_url( '/doi-ngu-bac-si/' ) ); ?>">Đội ngũ bác sĩ</a>.</p>
			</section>
		</div>
	</div>
	<?php endif; ?>
</article>

	<?php
endwhile;

get_footer();
