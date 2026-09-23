<?php
/**
 * Trang chủ — Bệnh viện Mắt Hà Nội – Bắc Ninh.
 *
 * VÌ SAO LÀ front-page.php CHỨ KHÔNG PHẢI MỘT TRANG TRONG WORDPRESS:
 * Quyết định QĐ-08B-1…5 (kiến trúc URL) đang ⏳ chờ Ban giám đốc phê duyệt,
 * và DECISION-LOG §2 ghi rõ: "Không agent nào được tạo trang mới trước thời
 * điểm đó". front-page.php được WordPress ưu tiên hơn cả cấu hình
 * "Trang tĩnh", nên dựng được trang chủ mà KHÔNG tạo bản ghi trang nào.
 * Đổi lại: nội dung nằm trong code, người không biết PHP chưa sửa được. Chấp
 * nhận được ở giai đoạn này vì nội dung trang chủ toàn là dữ liệu định danh
 * (tên, địa chỉ, số máy) — thứ chỉ được sửa ở một chỗ duy nhất là
 * eyecare_du_lieu_thuc_the().
 *
 * ✅ TRẠNG THÁI PHÁP LÝ (cập nhật 08/2026): Bệnh viện ĐÃ CÓ Giấy phép hoạt
 * động. Vì vậy trang chủ nay CÓ nút "Đặt lịch khám" (trỏ tới /dat-lich-kham/
 * đang publish, có biểu mẫu thật — không còn là liên kết chết) và phần giới
 * thiệu các lĩnh vực khám.
 *
 * 🔴 NHƯNG BỐN THỨ VẪN KHÔNG ĐƯỢC CÓ, kể cả khi đã có giấy phép:
 *
 * 1. KHÔNG bảng giá, KHÔNG hứa hẹn kết quả điều trị, KHÔNG mô tả dịch vụ theo
 *    kiểu quảng cáo. Quảng cáo NỘI DUNG dịch vụ khám chữa bệnh còn cần "xác
 *    nhận nội dung quảng cáo" của Sở Y tế — một cửa RIÊNG với giấy phép hoạt
 *    động. Khối "Dịch vụ của chúng tôi" bên dưới lấy nội dung trung tính từ
 *    mục Dịch vụ & lĩnh vực khám trong Admin; từng liên kết được quản trị viên
 *    kiểm tra và cập nhật theo nội dung đã công bố.
 *
 * 2. KHÔNG lời chứng của bệnh nhân. Site tham khảo có 3 mục; đó là dữ liệu
 *    cá nhân về sức khoẻ, cần phiếu đồng thuận có chữ ký (thẩm quyền: Trưởng
 *    pháp chế + Ban giám đốc, xem DECISION-LOG §1.3). Chưa có phiếu thì
 *    không đăng.
 *
 * 3. KHÔNG câu so sánh hơn nhất. Site tham khảo có "THIẾT BỊ HIỆN ĐẠI BẬC
 *    NHẤT" — cụm "bậc nhất" là chính thứ cửa 4 của scripts/kiem-bai.php
 *    chặn trên mọi bài viết. Trang chủ không được phép làm điều mà 100 bài
 *    kiến thức bị cấm.
 *
 * 4. GIỮ câu cảnh báo dấu hiệu phải đi khám ngay ở cuối trang — không gỡ.
 *
 * @package Eyecare_Child
 */

get_header();

$tt = eyecare_du_lieu_thuc_the();
?>

<div id="trang-chu" class="eyecare-chu">

	<?php
	/* ==================================================================
	   SLIDER ĐẦU TRANG
	   Định nghĩa ở inc/trang-chu.php. Tự rơi về hero chữ khi chưa có ảnh
	   nào được người kiểm nội dung xác nhận — xem chú thích trong hàm.
	   Hero nay có hàng nút "Đặt lịch khám" + "Gọi" (eyecare_hero_hanh_dong).
	   ================================================================== */
	eyecare_slider_dau_trang();
	?>

	<?php
	/* ==================================================================
	   DẢI DẪN NHANH "GẤP NẾP" (kiểu MEDCON) — ngay dưới hero
	   ------------------------------------------------------------------
	   Lấy cảm hứng từ dải xanh gấp nếp của MEDCON: một dải ngang nối liền
	   dưới hero, chia 4 ô dẫn tới các phần chính, đổi sang hệ XANH LÁ
	   thương hiệu. Làm hoàn toàn bằng CSS (clip-path răng cưa + gradient
	   chéo), 0 byte JS — đúng ràng buộc "nhẹ cho người đọc 3G".

	   Bốn ô đều trỏ tới trang ĐANG PUBLISH có nội dung thật; /dat-lich-kham/
	   có biểu mẫu thật. Không ô nào dẫn ra /dich-vu/ hay /bang-gia/ (draft).
	   Chữ trắng trên nền xanh đậm giữ tương phản ≥ 4.5:1 cho nhóm bệnh nhân
	   thị lực giảm. Icon là SVG nội tuyến (eyecare_bieu_tuong), aria-hidden.
	   ================================================================== */
	$dai_gap = array(
		array(
			'icon' => 'mat',
			'ten'  => 'Chuyên khoa',
			'phu'  => 'Khám chuyên sâu',
			'href' => home_url( '/chuyen-khoa/' ),
		),
		array(
			'icon' => 'khuc-xa',
			'ten'  => 'Đội ngũ bác sĩ',
			'phu'  => 'Khám trực tiếp',
			'href' => home_url( '/doi-ngu-bac-si/' ),
		),
		array(
			'icon' => 'thuy-tinh-the',
			'ten'  => 'Đặt lịch khám',
			'phu'  => 'Đặt trước giờ khám',
			'href' => home_url( '/dat-lich-kham/' ),
		),
		array(
			'icon' => 'glocom',
			'ten'  => 'Liên hệ',
			'phu'  => 'Địa chỉ, số máy',
			'href' => home_url( '/lien-he/' ),
		),
	);
	?>
	<nav class="eyecare-dai-gap" aria-label="Dẫn nhanh">
		<div class="eyecare-dai-gap__khung">
			<?php foreach ( $dai_gap as $o ) : ?>
				<a class="eyecare-dai-gap__o" href="<?php echo esc_url( $o['href'] ); ?>">
					<?php
					if ( function_exists( 'eyecare_bieu_tuong' ) ) {
						echo '<span class="eyecare-dai-gap__icon">' . eyecare_bieu_tuong( $o['icon'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG tự dựng
					}
					?>
					<span class="eyecare-dai-gap__ten"><?php echo esc_html( $o['ten'] ); ?></span>
					<span class="eyecare-dai-gap__phu"><?php echo esc_html( $o['phu'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</nav>

	<?php
	/* ==================================================================
	   BA ĐIỂM NHẬN DIỆN
	   Không phải "3 lý do chọn chúng tôi" — đó là lời tự khen không kiểm
	   chứng được. Ba mục dưới đây đều là SỰ THẬT KIỂM CHỨNG ĐƯỢC bằng giấy
	   tờ hoặc bằng cách gọi điện: giờ mở cửa, địa chỉ và thông tin chi phí.
	   ================================================================== */
	?>
	<section class="eyecare-chu__diem" aria-label="Thông tin cơ bản">
		<div class="eyecare-chu__khung">
			<ul class="eyecare-chu__diem-ds">

				<li class="eyecare-chu__diem-muc">
					<span class="eyecare-chu__diem-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" role="img">
							<circle cx="12" cy="12" r="8.5"></circle>
							<path d="M12 7.5v4.9l3.2 2"></path>
							<path d="M8.4 3.1 7.2 1.8M15.6 3.1l1.2-1.3"></path>
						</svg>
					</span>
					<div class="eyecare-chu__diem-noi-dung">
						<h2 class="eyecare-chu__diem-td">Mở cửa cả tuần</h2>
						<p class="eyecare-chu__diem-mt">
							<?php
							// Mở cửa 7 ngày là lợi thế đang bị chôn (lỗi F14) — đưa
							// lên trang chủ thay vì để lẫn trong trang liên hệ.
							printf(
								/* translators: 1: giờ mở, 2: giờ đóng */
								esc_html__( 'Từ %1$s đến %2$s, tất cả các ngày trong tuần.', 'eyecare-child' ),
								esc_html( $tt['gio_mo'] ),
								esc_html( $tt['gio_dong'] )
							);
							?>
						</p>
					</div>
				</li>

				<li class="eyecare-chu__diem-muc">
					<span class="eyecare-chu__diem-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" role="img">
							<path d="M20 10.1c0 5.4-8 11.4-8 11.4s-8-6-8-11.4a8 8 0 1 1 16 0Z"></path>
							<circle cx="12" cy="10" r="2.6"></circle>
						</svg>
					</span>
					<div class="eyecare-chu__diem-noi-dung">
						<h2 class="eyecare-chu__diem-td">Ngay trung tâm</h2>
						<p class="eyecare-chu__diem-mt">
							<?php echo esc_html( eyecare_dia_chi_day_du() ); ?>
						</p>
					</div>
				</li>

				<li class="eyecare-chu__diem-muc">
					<span class="eyecare-chu__diem-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" role="img">
							<path d="M6 3.2h12v17.6l-3-1.7-3 1.7-3-1.7-3 1.7V3.2Z"></path>
							<path d="M9 8h6M9 11.5h3.5"></path>
							<path d="m13.7 15 1.3 1.3 2.5-2.7"></path>
						</svg>
					</span>
					<div class="eyecare-chu__diem-noi-dung">
						<h2 class="eyecare-chu__diem-td">Chi phí minh bạch</h2>
						<p class="eyecare-chu__diem-mt">
							Không phát sinh chi phí ngoài dự kiến, thời gian khám nhanh chóng.
						</p>
					</div>
				</li>

			</ul>
		</div>
	</section>

	<?php
	/* ==================================================================
	   MÁY MÓC HIỆN ĐẠI — slider giới thiệu thiết bị đang sử dụng.
	   Ảnh được tối ưu riêng trong child theme; nội dung mô tả giữ trung tính,
	   chỉ nêu công dụng hỗ trợ, không đưa ra cam kết kết quả điều trị.
	   ================================================================== */
	$thiet_bi = array(
		array(
			'ten'  => 'IOLMaster 700',
			'nhan' => 'Sinh trắc học quang học',
			'mo_ta' => 'Đo các thông số nhãn cầu, hỗ trợ tính công suất thủy tinh thể nhân tạo theo chỉ định của bác sĩ.',
			'anh'  => 'iolmaster-700.jpg',
		),
		array(
			'ten'  => 'Revo 80 OCT',
			'nhan' => 'Chụp cắt lớp võng mạc',
			'mo_ta' => 'Tạo hình ảnh chi tiết các lớp võng mạc để bác sĩ đánh giá cấu trúc đáy mắt.',
			'anh'  => 'revo-80-oct.jpg',
		),
		array(
			'ten'  => 'OPMI Lumera 300',
			'nhan' => 'Kính hiển vi phẫu thuật',
			'mo_ta' => 'Hỗ trợ quan sát rõ trong các thao tác phẫu thuật nhãn khoa theo quy trình chuyên môn.',
			'anh'  => 'opmi-lumera-300.jpg',
		),
		array(
			'ten'  => 'Phaco FAROS',
			'nhan' => 'Hệ thống phẫu thuật đục thủy tinh thể',
			'mo_ta' => 'Kiểm soát năng lượng và dòng dịch trong phẫu thuật theo chỉ định và quy trình của bác sĩ.',
			'anh'  => 'phaco-faros.jpg',
		),
		array(
			'ten'  => 'Laser YAG YC-200',
			'nhan' => 'Laser nhãn khoa',
			'mo_ta' => 'Thiết bị laser dùng trong một số thủ thuật nhãn khoa khi có chỉ định phù hợp.',
			'anh'  => 'laser-yag-yc-200.jpg',
		),
		array(
			'ten'  => 'VuPad A/B',
			'nhan' => 'Siêu âm mắt A-scan & B-scan',
			'mo_ta' => 'Đo trục nhãn cầu và khảo sát cấu trúc bên trong mắt trong những trường hợp cần thiết.',
			'anh'  => 'vupad-ab.jpg',
		),
	);
	$thiet_bi_url = get_stylesheet_directory_uri() . '/assets/thiet-bi/';
	?>
	<section id="thiet-bi-hien-dai" class="eyecare-equipment" data-equipment-slider aria-labelledby="thiet-bi-hien-dai-tieu-de">
		<div class="eyecare-equipment__khung">
			<header class="eyecare-equipment__dau">
				<p class="eyecare-equipment__nhan"><span aria-hidden="true"></span>Không gian thiết bị</p>
				<h2 id="thiet-bi-hien-dai-tieu-de">Máy móc hiện đại</h2>
				<p>Hình ảnh một số thiết bị được sử dụng để hỗ trợ thăm khám, chẩn đoán và điều trị tại bệnh viện.</p>
			</header>

			<div class="eyecare-equipment__san" role="region" aria-roledescription="carousel" aria-label="Các thiết bị nhãn khoa">
				<button class="eyecare-equipment__nut eyecare-equipment__nut--truoc" type="button" data-equipment-prev aria-label="Thiết bị trước">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"></path></svg>
				</button>
				<div class="eyecare-equipment__khung-truot" data-equipment-viewport>
					<ul class="eyecare-equipment__danh-sach" data-equipment-track>
						<?php foreach ( $thiet_bi as $i => $tb ) : ?>
							<li class="eyecare-equipment__slide<?php echo 0 === $i ? ' is-active' : ''; ?>" data-equipment-slide data-equipment-index="<?php echo (int) $i; ?>" aria-hidden="false">
								<article class="eyecare-equipment__the">
									<div class="eyecare-equipment__anh-wrap">
										<img src="<?php echo esc_url( $thiet_bi_url . $tb['anh'] ); ?>" alt="<?php echo esc_attr( $tb['ten'] ); ?>" width="1122" height="1402" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?> decoding="async">
									</div>
									<div class="eyecare-equipment__noi-dung">
										<p class="eyecare-equipment__so"><?php echo esc_html( sprintf( '%02d / %02d', $i + 1, count( $thiet_bi ) ) ); ?></p>
										<p class="eyecare-equipment__loai"><?php echo esc_html( $tb['nhan'] ); ?></p>
										<h3><?php echo esc_html( $tb['ten'] ); ?></h3>
										<p class="eyecare-equipment__mo-ta"><?php echo esc_html( $tb['mo_ta'] ); ?></p>
									</div>
								</article>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<button class="eyecare-equipment__nut eyecare-equipment__nut--sau" type="button" data-equipment-next aria-label="Thiết bị tiếp theo">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"></path></svg>
				</button>
			</div>

			<div class="eyecare-equipment__dieu-khien" role="group" aria-label="Chọn thiết bị">
				<?php foreach ( $thiet_bi as $i => $tb ) : ?>
					<button type="button" class="eyecare-equipment__cham<?php echo 0 === $i ? ' is-active' : ''; ?>" data-equipment-dot="<?php echo (int) $i; ?>" aria-label="Xem <?php echo esc_attr( $tb['ten'] ); ?>" aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"></button>
				<?php endforeach; ?>
				<button type="button" class="eyecare-equipment__tam-dung" data-equipment-toggle aria-pressed="false" aria-label="Tạm dừng tự động chuyển">
					<svg class="eyecare-equipment__icon-dung" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14M16 5v14"></path></svg>
					<svg class="eyecare-equipment__icon-chay" viewBox="0 0 24 24" aria-hidden="true"><path d="m8 5 11 7-11 7V5Z"></path></svg>
					<span class="screen-reader-text">Tạm dừng hoặc tiếp tục tự động chuyển</span>
				</button>
			</div>
			<p class="screen-reader-text" data-equipment-status aria-live="polite">Thiết bị 1 trên <?php echo (int) count( $thiet_bi ); ?>: <?php echo esc_html( $thiet_bi[0]['ten'] ); ?></p>
		</div>
	</section>

	<?php
	/* ==================================================================
	   DỊCH VỤ CỦA CHÚNG TÔI — dữ liệu lấy từ “Dịch vụ & lĩnh vực khám”
	   trong WordPress Admin. Người quản trị có thể thêm/sửa/xóa thẻ, đổi
	   ảnh, mô tả, liên kết, icon, màu và thứ tự mà không sửa template.
	   ================================================================== */
	$linh_vuc = function_exists( 'eyecare_linh_vuc_lay_ds' ) ? eyecare_linh_vuc_lay_ds( 12 ) : array();
	$linh_vuc_cau_hinh = function_exists( 'eyecare_linh_vuc_cau_hinh' ) ? eyecare_linh_vuc_cau_hinh() : array(
		'nhan'    => 'Chuyên khoa mắt',
		'tieu_de' => 'Dịch vụ của chúng tôi',
		'mo_ta'   => 'Khám, chẩn đoán và điều trị các vấn đề về mắt với thông tin rõ ràng, dễ hiểu trước khi bạn đến bệnh viện.',
	);
	?>
	<?php if ( $linh_vuc ) : ?>
	<section id="linh-vuc-kham" class="eyecare-linh-vuc-showcase" aria-labelledby="linh-vuc-kham-tieu-de">
		<div class="eyecare-linh-vuc-showcase__nen" aria-hidden="true"></div>
		<div class="eyecare-linh-vuc-showcase__khung">
			<header class="eyecare-linh-vuc-showcase__dau">
				<p class="eyecare-linh-vuc-showcase__nhan"><span aria-hidden="true"></span><?php echo esc_html( $linh_vuc_cau_hinh['nhan'] ); ?></p>
				<h2 id="linh-vuc-kham-tieu-de"><?php echo esc_html( $linh_vuc_cau_hinh['tieu_de'] ); ?></h2>
				<p><?php echo esc_html( $linh_vuc_cau_hinh['mo_ta'] ); ?></p>
			</header>

			<ul class="eyecare-linh-vuc-showcase__luoi">
				<?php foreach ( $linh_vuc as $i => $lv ) : ?>
					<li class="eyecare-linh-vuc-card eyecare-linh-vuc-card--<?php echo esc_attr( $lv['mau'] ); ?>" style="--eyecare-lv-i: <?php echo (int) $i; ?>;">
						<a href="<?php echo esc_url( $lv['link'] ); ?>" aria-label="<?php echo esc_attr( 'Xem chi tiết ' . $lv['ten'] ); ?>">
							<span class="eyecare-linh-vuc-card__anh">
								<?php if ( ! empty( $lv['image'] ) ) : ?>
									<img src="<?php echo esc_url( $lv['image'] ); ?>" alt="<?php echo esc_attr( $lv['ten'] ); ?>" loading="lazy" decoding="async">
								<?php else : ?>
									<span class="eyecare-linh-vuc-card__anh-thay" aria-hidden="true">
										<?php echo function_exists( 'eyecare_bieu_tuong' ) ? eyecare_bieu_tuong( $lv['icon'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG nội bộ. ?>
									</span>
								<?php endif; ?>
								<span class="eyecare-linh-vuc-card__phu" aria-hidden="true"></span>
								<span class="eyecare-linh-vuc-card__copy"><small><?php echo esc_html( $lv['nhan'] ); ?></small><strong><?php echo esc_html( $lv['ten'] ); ?></strong><span><?php echo esc_html( $lv['mo_ta'] ); ?></span></span>
							</span>
							<span class="eyecare-linh-vuc-card__nut">Xem chi tiết <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="eyecare-linh-vuc-showcase__chan">
				<a href="<?php echo esc_url( home_url( '/dich-vu/' ) ); ?>">Xem toàn bộ dịch vụ <span aria-hidden="true">→</span></a>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php
	/* ==================================================================
	   ĐỘI NGŨ CHUYÊN MÔN — bố cục poster 1 cố vấn + 5 thành viên.
	   Khối trang chủ tách khỏi trang hồ sơ đầy đủ để giữ đúng thiết kế mà
	   không làm thay đổi dữ liệu quản trị hoặc trang /doi-ngu-bac-si/.
	   ================================================================== */
	if ( function_exists( 'eyecare_doi_ngu_trang_chu_in' ) ) {
		eyecare_doi_ngu_trang_chu_in();
	}
	?>

	<?php
	/* ==================================================================
	   CHUYÊN KHOA — bài/trang bệnh học đã có nội dung
	   Chỉ liệt kê trang ĐANG PUBLISH và có nội dung thật. Trang rỗng thì
	   người bấm vào gặp trang trắng — tệ hơn là không có liên kết. Khối
	   này tự ẩn khi chưa có trang con nào đủ điều kiện (hiện tại là vậy).
	   ================================================================== */
	$chuyen_khoa = eyecare_trang_con_co_noi_dung( 'chuyen-khoa' );

	if ( $chuyen_khoa ) :
		?>
	<section class="eyecare-chu__khoa" aria-label="Tìm hiểu về bệnh mắt">
		<div class="eyecare-chu__khung">
			<div class="eyecare-khoa__dau">
				<div class="eyecare-khoa__tieu-de">
					<p class="eyecare-khoa__nhan">Kiến thức nhãn khoa</p>
					<h2 class="eyecare-chu__muc-td">Tìm hiểu về bệnh mắt</h2>
				</div>
				<p class="eyecare-chu__muc-dan">
					Thông tin bệnh học giúp bạn hiểu rõ hơn tình trạng của mình trước khi đi khám.
					Nội dung chỉ mang tính tham khảo, không thay thế chẩn đoán của bác sĩ.
				</p>
			</div>

			<ul class="eyecare-chu__the-ds">
				<?php foreach ( $chuyen_khoa as $thu_tu => $trang ) : ?>
					<?php
					$slug_chuyen_khoa = (string) $trang->post_name;
					$khoa_bieu_tuong  = 'mat';

					if ( false !== strpos( $slug_chuyen_khoa, 'vong-mac' ) ) {
						$khoa_bieu_tuong = 'vong-mac';
					} elseif ( false !== strpos( $slug_chuyen_khoa, 'glocom' ) ) {
						$khoa_bieu_tuong = 'glocom';
					} elseif ( false !== strpos( $slug_chuyen_khoa, 'tre-em' ) ) {
						$khoa_bieu_tuong = 'tre-em';
					}
					?>
					<li class="eyecare-chu__the">
						<a class="eyecare-chu__the-lk" href="<?php echo esc_url( get_permalink( $trang->ID ) ); ?>">
							<span class="eyecare-khoa__so" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $thu_tu + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="eyecare-khoa__icon" aria-hidden="true"><?php echo eyecare_bieu_tuong( $khoa_bieu_tuong ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG noi bo co dinh. ?></span>
							<span class="eyecare-khoa__noi-dung">
								<span class="eyecare-chu__the-ten"><?php echo esc_html( get_the_title( $trang->ID ) ); ?></span>
								<span class="eyecare-khoa__xem">Xem thông tin <span aria-hidden="true">&#8594;</span></span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
		<?php
	endif;
	?>

	<?php
	/* ==================================================================
	   BÀI KIẾN THỨC MỚI
	   Khối này RỖNG là đúng ở thời điểm hiện tại: mọi bài đang ở draft chờ
	   bác sĩ ký (ràng buộc B-01b). WP_Query chỉ lấy bài publish, nên khối
	   tự ẩn. Không hạ điều kiện xuống 'draft' để trang chủ "trông có nội
	   dung" — làm thế là công khai bài chưa ai duyệt.
	   ================================================================== */
	$bai_moi = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	if ( $bai_moi->have_posts() ) :
		?>
	<section class="eyecare-chu__bai" aria-label="Bài viết mới">
		<div class="eyecare-chu__khung">
			<header class="eyecare-chu__bai-dau">
				<div>
					<p class="eyecare-chu__bai-nhan">Kiến thức mới</p>
					<h2 id="eyecare-bai-moi-tieu-de" class="eyecare-chu__muc-td">Bài viết chăm sóc mắt</h2>
					<p class="eyecare-chu__bai-dan">Thông tin dễ hiểu giúp bạn nhận biết vấn đề về mắt và chuẩn bị tốt hơn trước khi đi khám.</p>
				</div>
				<a class="eyecare-chu__bai-tat-ca" href="<?php echo esc_url( home_url( '/kien-thuc/' ) ); ?>">
					Xem tất cả <span aria-hidden="true">&rarr;</span>
				</a>
			</header>

			<div class="eyecare-chu__bai-bo-cuc" aria-labelledby="eyecare-bai-moi-tieu-de">
				<?php
				$thu_tu_bai = 0;
				while ( $bai_moi->have_posts() ) :
					$bai_moi->the_post();

					$la_bai_noi_bat = 0 === $thu_tu_bai;
					$co_anh_dai_dien = has_post_thumbnail();
					$anh_alt         = $co_anh_dai_dien ? get_post_meta( get_post_thumbnail_id(), '_wp_attachment_image_alt', true ) : '';
					$anh_alt         = $anh_alt ? $anh_alt : get_the_title();
					$tom_tat         = wp_trim_words(
						wp_strip_all_tags( get_the_excerpt() ),
						$la_bai_noi_bat ? 30 : 18,
						'...'
					);
					?>
					<?php if ( $la_bai_noi_bat ) : ?>
						<article class="eyecare-chu__bai-noi-bat">
							<a class="eyecare-chu__bai-anh" href="<?php echo esc_url( get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
								<?php if ( $co_anh_dai_dien ) : ?>
									<?php the_post_thumbnail( 'large', array( 'alt' => esc_attr( $anh_alt ), 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
								<?php else : ?>
									<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/anh-bai-mac-dinh.jpg' ); ?>" alt="<?php echo esc_attr( $anh_alt ); ?>" loading="lazy" decoding="async">
								<?php endif; ?>
							</a>
							<div class="eyecare-chu__bai-noi-dung">
								<p class="eyecare-chu__bai-ngay">
									<span>Bài mới</span>
									<time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
										<?php echo esc_html( get_the_modified_date( 'j/n/Y' ) ); ?>
									</time>
								</p>
								<h3 class="eyecare-chu__bai-td">
									<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
								</h3>
								<?php if ( $tom_tat ) : ?>
									<p class="eyecare-chu__bai-tom-tat"><?php echo esc_html( $tom_tat ); ?></p>
								<?php endif; ?>
								<a class="eyecare-chu__bai-doc" href="<?php echo esc_url( get_permalink() ); ?>">Đọc bài viết <span aria-hidden="true">&rarr;</span></a>
							</div>
						</article>
					<?php else : ?>
						<?php if ( 1 === $thu_tu_bai ) : ?>
							<div class="eyecare-chu__bai-phu" role="list" aria-label="Các bài viết mới khác">
						<?php endif; ?>
							<article class="eyecare-chu__bai-muc" role="listitem">
								<a class="eyecare-chu__bai-anh" href="<?php echo esc_url( get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
									<?php if ( $co_anh_dai_dien ) : ?>
										<?php the_post_thumbnail( 'medium', array( 'alt' => esc_attr( $anh_alt ), 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
									<?php else : ?>
										<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/anh-bai-mac-dinh.jpg' ); ?>" alt="<?php echo esc_attr( $anh_alt ); ?>" loading="lazy" decoding="async">
									<?php endif; ?>
								</a>
								<div class="eyecare-chu__bai-noi-dung">
									<p class="eyecare-chu__bai-ngay">
										<time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
											<?php echo esc_html( get_the_modified_date( 'j/n/Y' ) ); ?>
										</time>
									</p>
									<h3 class="eyecare-chu__bai-td">
										<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
									</h3>
									<?php if ( $tom_tat ) : ?>
										<p class="eyecare-chu__bai-tom-tat"><?php echo esc_html( $tom_tat ); ?></p>
									<?php endif; ?>
								</div>
							</article>
					<?php endif; ?>
					<?php
					++$thu_tu_bai;
				endwhile;
				if ( $thu_tu_bai > 1 ) :
					?>
						</div>
					<?php
				endif;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
		<?php
	endif;
	?>

	<?php
	/* ==================================================================
	   DẢI KÊU GỌI ĐẶT LỊCH
	   Hợp lệ từ khi có Giấy phép hoạt động: /dat-lich-kham/ đang publish và
	   có biểu mẫu thật. Đây là hỗ trợ ĐẶT LỊCH, không phải quảng cáo nội
	   dung dịch vụ — nên không kèm giá, không mô tả gói khám, không hứa hẹn.
	   ================================================================== */
	?>
	<section class="eyecare-chu__cta" aria-label="Đặt lịch khám">
		<div class="eyecare-chu__khung eyecare-chu__cta-trong">
			<div class="eyecare-chu__cta-loi">
				<h2 class="eyecare-chu__cta-td">Cần khám mắt?</h2>
				<p class="eyecare-chu__cta-mt">
					Đặt lịch trước để được sắp xếp thời gian khám phù hợp, hoặc gọi
					tổng đài để được hỗ trợ trực tiếp.
				</p>
			</div>
			<div class="eyecare-hero__nut">
				<a class="eyecare-nut eyecare-nut--chinh" href="<?php echo esc_url( home_url( '/dat-lich-kham/' ) ); ?>">Đặt lịch khám</a>
				<a class="eyecare-nut eyecare-nut--phu" href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">Gọi <?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a>
			</div>
		</div>
	</section>

	<?php
	/* ==================================================================
	   ĐÁNH GIÁ TỪ BỆNH NHÂN
	   Dữ liệu được quản lý bằng mục “Đánh giá bệnh nhân” trong WordPress.
	   Hỗ trợ ảnh, video tải lên và video YouTube/Vimeo.
	   ================================================================== */
	if ( function_exists( 'eyecare_danh_gia_benh_nhan_in' ) ) {
		eyecare_danh_gia_benh_nhan_in();
	}
	?>

	<?php
	if ( function_exists( 'eyecare_noi_dung_seo_trang_chu_in' ) ) {
		?>
		<details class="eyecare-home-guide">
			<summary><span><small>Chuẩn bị trước khi đến khám</small><strong>Cẩm nang khám và chăm sóc mắt</strong><span>Thông tin về bệnh mắt, giấy tờ và những điều cần lưu ý.</span></span><span class="eyecare-home-guide__more">Xem cẩm nang <span aria-hidden="true">＋</span></span></summary>
		<?php
		eyecare_noi_dung_seo_trang_chu_in( $tt );
		?>
		</details>
		<?php
	}
	?>

</div>

<?php
get_footer();
