<?php
/**
 * Chính sách quyền riêng tư.
 *
 * @package Eyecare_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$tt            = eyecare_du_lieu_thuc_the();
	$cap_nhat_moc  = max( (int) get_post_modified_time( 'U', true ), (int) filemtime( __FILE__ ) );
	$cap_nhat      = wp_date( 'd/m/Y', $cap_nhat_moc );
	?>

<article id="trang-<?php the_ID(); ?>" <?php post_class( 'eyecare-chinh-sach' ); ?>>
	<section class="eyecare-page-hero eyecare-page-hero--chinh-sach" aria-labelledby="chinh-sach-tieu-de">
		<div class="eyecare-page-hero__khung">
			<?php if ( function_exists( 'eyecare_duong_dan_in' ) ) : ?><div class="eyecare-page-hero__duong-dan"><?php eyecare_duong_dan_in(); ?></div><?php endif; ?>
			<div class="eyecare-page-hero__grid">
				<div>
					<p class="eyecare-page-hero__nhan">Bảo vệ thông tin cá nhân</p>
					<h1 id="chinh-sach-tieu-de"><?php the_title(); ?></h1>
					<p class="eyecare-page-hero__dan">Chính sách này giải thích loại thông tin website có thể tiếp nhận, mục đích sử dụng, cách bảo vệ và quyền của người dùng đối với dữ liệu cá nhân.</p>
					<p class="eyecare-chinh-sach__ngay">Cập nhật lần cuối: <strong><?php echo esc_html( $cap_nhat ); ?></strong></p>
				</div>
				<div class="eyecare-page-hero__art" aria-hidden="true"><svg viewBox="0 0 260 230"><path d="M130 20 45 52v58c0 53 35 84 85 105 50-21 85-52 85-105V52l-85-32Z"/><rect x="91" y="94" width="78" height="62" rx="12"/><path d="M106 94V76a24 24 0 0 1 48 0v18M130 119v15"/></svg><span><strong>12</strong> nội dung chính</span></div>
			</div>
		</div>
	</section>

	<div class="eyecare-chinh-sach__tin-cay eyecare-noi-dung__khung" aria-label="Cam kết xử lý dữ liệu">
		<div>
			<span aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M7 16.5 13 22l12-13"/></svg></span>
			<p><strong>Không bán dữ liệu</strong><small>Thông tin cá nhân không được sử dụng để mua bán.</small></p>
		</div>
		<div>
			<span aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M16 4 7 8v7c0 6 3.8 10.2 9 13 5.2-2.8 9-7 9-13V8l-9-4Z"/><path d="m12 16 2.5 2.5L20 13"/></svg></span>
			<p><strong>Thu thập có giới hạn</strong><small>Chỉ tiếp nhận dữ liệu cần thiết cho mục đích đã nêu.</small></p>
		</div>
		<div>
			<span aria-hidden="true"><svg viewBox="0 0 32 32"><circle cx="16" cy="16" r="11"/><path d="M16 10v7l4 3"/></svg></span>
			<p><strong>Quyền yêu cầu rõ ràng</strong><small>Người dùng có thể đề nghị xem, sửa hoặc hạn chế xử lý.</small></p>
		</div>
	</div>

	<div class="eyecare-chinh-sach__bo-cuc eyecare-noi-dung__khung">
		<aside class="eyecare-chinh-sach__muc-luc" aria-label="Mục lục chính sách">
			<p>Nội dung</p>
			<ol>
				<li><a href="#pham-vi">Phạm vi áp dụng</a></li>
				<li><a href="#du-lieu">Dữ liệu có thể tiếp nhận</a></li>
				<li><a href="#muc-dich">Mục đích sử dụng</a></li>
				<li><a href="#du-lieu-suc-khoe">Dữ liệu sức khỏe</a></li>
				<li><a href="#cookie">Cookie và dịch vụ ngoài</a></li>
				<li><a href="#chia-se">Chia sẻ dữ liệu</a></li>
				<li><a href="#luu-tru">Thời gian lưu trữ</a></li>
				<li><a href="#bao-mat">Biện pháp bảo vệ</a></li>
				<li><a href="#quyen">Quyền của người dùng</a></li>
				<li><a href="#tre-em">Thông tin của trẻ em</a></li>
				<li><a href="#thay-doi">Thay đổi chính sách</a></li>
				<li><a href="#lien-he-rieng-tu">Liên hệ</a></li>
			</ol>
			<div class="eyecare-chinh-sach__ghi-chu">
				<span aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M16 4 6 8v7c0 6.5 4.3 10.8 10 13 5.7-2.2 10-6.5 10-13V8L16 4Z"/><path d="M12 16h8M16 12v8"/></svg></span>
				<p><strong>Lưu ý về dữ liệu sức khỏe</strong>Không gửi bệnh án, kết quả xét nghiệm hoặc giấy tờ cá nhân qua bình luận và kênh công khai.</p>
			</div>
		</aside>

		<div class="eyecare-chinh-sach__noi-dung">
			<header class="eyecare-chinh-sach__mo-dau">
				<p>Quyền riêng tư tại bệnh viện</p>
				<h2>Cách website tiếp nhận và bảo vệ thông tin</h2>
				<span>Nội dung được chia thành 12 phần để người đọc có thể tra cứu nhanh theo nhu cầu, từ loại dữ liệu được tiếp nhận đến quyền yêu cầu chỉnh sửa hoặc xóa thông tin.</span>
			</header>
			<section id="pham-vi"><span>01</span><h2>Phạm vi áp dụng</h2><p>Chính sách này áp dụng đối với việc truy cập và sử dụng website chính thức của <?php echo esc_html( $tt['ten'] ); ?>. Chính sách không tự động áp dụng cho website, ứng dụng hoặc dịch vụ của bên thứ ba được liên kết từ website này.</p></section>

			<section id="du-lieu"><span>02</span><h2>Dữ liệu website có thể tiếp nhận</h2><p>Tùy vào cách bạn sử dụng website, hệ thống có thể tiếp nhận các nhóm thông tin sau:</p><ul><li><strong>Thông tin bạn chủ động cung cấp:</strong> họ tên, số điện thoại và nội dung yêu cầu khi bạn liên hệ qua kênh được công bố.</li><li><strong>Thông tin kỹ thuật:</strong> địa chỉ IP, loại trình duyệt, thiết bị, thời điểm truy cập, trang đã xem và nhật ký lỗi do máy chủ ghi nhận để vận hành và bảo vệ website.</li><li><strong>Thông tin tương tác:</strong> lựa chọn cookie, thao tác với bản đồ nhúng, video hoặc liên kết tới dịch vụ bên ngoài.</li></ul></section>

			<section id="muc-dich"><span>03</span><h2>Mục đích sử dụng thông tin</h2><p>Thông tin được sử dụng trong phạm vi cần thiết để:</p><ul><li>Phản hồi yêu cầu liên hệ và hướng dẫn thông tin trước khi người dùng đến bệnh viện.</li><li>Vận hành, bảo trì, khắc phục lỗi và cải thiện khả năng sử dụng của website.</li><li>Phòng ngừa truy cập trái phép, thư rác, gian lận và các rủi ro an toàn thông tin.</li><li>Thực hiện nghĩa vụ theo yêu cầu hợp pháp của cơ quan có thẩm quyền.</li></ul></section>

			<section id="du-lieu-suc-khoe"><span>04</span><h2>Dữ liệu sức khỏe và thông tin nhạy cảm</h2><p>Website hiện không yêu cầu người dùng gửi hồ sơ bệnh án, kết quả xét nghiệm, hình ảnh y tế hoặc thông tin sức khỏe chi tiết qua biểu mẫu công khai. Bạn không nên gửi dữ liệu sức khỏe nhạy cảm qua bình luận, kênh không được mã hóa hoặc nền tảng mạng xã hội công khai.</p><p>Khi cần trao đổi về tình trạng cụ thể, hãy gọi tổng đài để được hướng dẫn kênh tiếp nhận phù hợp. Việc cung cấp thông tin trên website không thay thế khám và chẩn đoán trực tiếp.</p></section>

			<section id="cookie"><span>05</span><h2>Cookie, nhật ký truy cập và dịch vụ bên ngoài</h2><p>Website có thể sử dụng cookie cần thiết để duy trì phiên làm việc, ghi nhớ lựa chọn và bảo vệ chức năng quản trị. Nhật ký máy chủ có thể được lưu để theo dõi lỗi và an ninh.</p><p>Trang liên hệ có thể nhúng Google Maps; bài viết hoặc đánh giá có thể nhúng nội dung từ YouTube, Vimeo hoặc nền tảng khác. Khi bạn tương tác với nội dung nhúng, bên cung cấp dịch vụ có thể nhận dữ liệu kỹ thuật và áp dụng chính sách riêng tư của họ. Bạn nên xem chính sách của dịch vụ tương ứng trước khi sử dụng.</p></section>

			<section id="chia-se"><span>06</span><h2>Chia sẻ và chuyển giao dữ liệu</h2><p><?php echo esc_html( $tt['ten'] ); ?> không bán thông tin cá nhân của người dùng. Dữ liệu chỉ có thể được chia sẻ trong phạm vi cần thiết với nhà cung cấp hạ tầng, đơn vị hỗ trợ kỹ thuật chịu nghĩa vụ bảo mật, hoặc cơ quan nhà nước khi có yêu cầu hợp pháp.</p><p>Trong trường hợp cần sử dụng nhà cung cấp ngoài lãnh thổ Việt Nam cho hạ tầng hoặc nội dung nhúng, dữ liệu kỹ thuật có thể được xử lý theo cơ chế của nhà cung cấp đó và quy định pháp luật áp dụng.</p></section>

			<section id="luu-tru"><span>07</span><h2>Thời gian lưu trữ</h2><p>Dữ liệu được lưu trong thời gian cần thiết để hoàn thành mục đích tiếp nhận, giải quyết yêu cầu, bảo đảm an ninh và tuân thủ nghĩa vụ pháp lý. Khi không còn cần thiết, dữ liệu sẽ được xóa, ẩn danh hoặc hạn chế truy cập theo quy trình phù hợp.</p></section>

			<section id="bao-mat"><span>08</span><h2>Biện pháp bảo vệ thông tin</h2><p>Website áp dụng các biện pháp hợp lý như kiểm soát quyền truy cập, xác thực tài khoản quản trị, cập nhật phần mềm, sao lưu, ghi nhật ký và giới hạn dữ liệu được thu thập. Tuy vậy, không phương thức truyền hoặc lưu trữ điện tử nào bảo đảm an toàn tuyệt đối; người dùng cũng cần bảo vệ thiết bị và không chia sẻ mật khẩu, mã xác thực.</p></section>

			<section id="quyen"><span>09</span><h2>Quyền của người dùng</h2><p>Trong phạm vi pháp luật áp dụng và khả năng xác minh danh tính, bạn có thể yêu cầu được biết, xem, chỉnh sửa, rút lại sự đồng ý, hạn chế xử lý hoặc xóa thông tin cá nhân do mình cung cấp. Một số dữ liệu có thể cần được tiếp tục lưu giữ để tuân thủ nghĩa vụ pháp lý, giải quyết tranh chấp hoặc bảo vệ an toàn hệ thống.</p><p>Để gửi yêu cầu, hãy sử dụng thông tin liên hệ ở cuối chính sách và mô tả rõ dữ liệu hoặc tương tác liên quan để bệnh viện có thể xác minh, xử lý chính xác.</p></section>

			<section id="tre-em"><span>10</span><h2>Thông tin liên quan đến trẻ em</h2><p>Cha mẹ hoặc người giám hộ nên trực tiếp thực hiện việc liên hệ và quyết định cung cấp thông tin của trẻ. Không đăng công khai họ tên đầy đủ, địa chỉ, trường học, hồ sơ y tế hoặc hình ảnh nhận diện của trẻ nếu chưa đánh giá sự cần thiết và quyền riêng tư.</p></section>

			<section id="thay-doi"><span>11</span><h2>Thay đổi chính sách</h2><p>Chính sách có thể được cập nhật khi chức năng website, quy trình xử lý dữ liệu hoặc quy định pháp luật thay đổi. Phiên bản mới được công bố tại trang này và ngày cập nhật được ghi ở phần đầu. Việc tiếp tục sử dụng website sau ngày cập nhật được hiểu là bạn đã có cơ hội đọc phiên bản mới.</p></section>

			<section id="lien-he-rieng-tu" class="eyecare-chinh-sach__lien-he"><span>12</span><h2>Liên hệ về quyền riêng tư</h2><p><strong>Đơn vị tiếp nhận:</strong> <?php echo esc_html( $tt['phap_nhan'] ); ?></p><p><strong>Địa chỉ:</strong> <?php echo esc_html( eyecare_dia_chi_day_du() ); ?></p><p><strong>Điện thoại:</strong> <a href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>"><?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a></p><p>Khi liên hệ, vui lòng nêu rõ yêu cầu liên quan đến dữ liệu cá nhân và thông tin cần thiết để xác minh quyền yêu cầu.</p></section>
		</div>
	</div>
</article>

	<?php
endwhile;

get_footer();
