<?php
/**
 * Bài hướng dẫn SEO/GEO/E-E-A-T trên trang Liên hệ.
 *
 * @package eyecare-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tt = isset( $args['thuc_the'] ) && is_array( $args['thuc_the'] ) ? $args['thuc_the'] : eyecare_du_lieu_thuc_the();
$faq = function_exists( 'eyecare_lien_he_faq_data' ) ? eyecare_lien_he_faq_data() : array();

$dia_chi_day_du = $tt['dia_chi'] . ', ' . $tt['phuong'] . ', ' . $tt['tinh'];
$url             = static function ( $path ) {
	return home_url( $path );
};
?>

<section class="eyecare-lien-he-seo" aria-labelledby="lien-he-seo-tieu-de">
	<div class="eyecare-noi-dung__khung">
		<div class="eyecare-lien-he-seo__intro">
			<div>
				<p class="eyecare-lien-he-seo__eyebrow">Cẩm nang người bệnh · cập nhật <?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></p>
				<h2 id="lien-he-seo-tieu-de">Bệnh viện mắt Bắc Ninh: địa chỉ, giờ khám và hướng dẫn liên hệ</h2>
			</div>
			<p class="eyecare-lien-he-seo__lead">Một cuộc gọi đúng lúc giúp buổi khám mắt bớt chờ đợi và bớt lo lắng. Nội dung dưới đây gom những điều người bệnh thường cần biết trước khi đến <strong><?php echo esc_html( $tt['ten'] ); ?></strong>: tìm đường, chọn chuyên khoa, chuẩn bị hồ sơ, trao đổi chi phí và nhận biết tình huống cần khám sớm.</p>
		</div>

		<?php
		$anh_tac_gia  = function_exists( 'eyecare_bac_si_anh_tac_gia' ) ? eyecare_bac_si_anh_tac_gia() : '';
		$ten_tac_gia  = function_exists( 'eyecare_bac_si_ten_day_du' ) ? eyecare_bac_si_ten_day_du() : 'Ths.BS Lê Như Tùng';
		$link_tac_gia = function_exists( 'eyecare_bac_si_duong_dan' ) ? eyecare_bac_si_duong_dan() : home_url( '/doi-ngu-bac-si/' );
		?>
		<div class="eyecare-lien-he-seo__byline" aria-label="Thông tin người biên soạn">
			<?php if ( $anh_tac_gia ) : ?><img src="<?php echo esc_url( $anh_tac_gia ); ?>" alt="<?php echo esc_attr( $ten_tac_gia ); ?>" width="54" height="54" loading="lazy" decoding="async"><?php endif; ?>
			<div><span>Người biên soạn nội dung</span><strong><a href="<?php echo esc_url( $link_tac_gia ); ?>"><?php echo esc_html( $ten_tac_gia ); ?></a></strong><small>Cập nhật lần cuối <?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?> · Thông tin tham khảo, không thay thế thăm khám trực tiếp.</small></div>
		</div>

		<div class="eyecare-lien-he-seo__answer" role="note">
			<strong>Câu trả lời nhanh:</strong> Bệnh viện Mắt Hà Nội – Bắc Ninh hiện ở <strong><?php echo esc_html( $dia_chi_day_du ); ?></strong>, mở cửa <strong><?php echo esc_html( $tt['gio_mo'] . ' – ' . $tt['gio_dong'] ); ?></strong> tất cả các ngày trong tuần. Gọi <a href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>"><?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a> để xác nhận lịch tiếp nhận, bác sĩ và hướng đi trước khi khởi hành.
		</div>

		<nav class="eyecare-lien-he-seo__toc" aria-label="Mục lục bài hướng dẫn">
			<strong>Trong bài có gì?</strong>
			<a href="#thong-tin-dia-chi">Địa chỉ và giờ làm việc</a>
			<a href="#khi-nao-kham">Khi nào nên khám mắt?</a>
			<a href="#nhom-benh">Nhóm bệnh thường gặp</a>
			<a href="#quy-trinh">Quy trình khám</a>
			<a href="#chuan-bi-kham">Chuẩn bị trước khi đến</a>
			<a href="#huong-di">Hướng dẫn tìm đường</a>
			<a href="#faq-lien-he">10 câu hỏi thường gặp</a>
		</nav>

		<div class="eyecare-lien-he-seo__layout">
			<article class="eyecare-lien-he-seo__body">
				<h2 id="thong-tin-dia-chi">Bệnh viện mắt Hà Nội – Bắc Ninh: thông tin cần biết trước khi đến</h2>
				<p>Khi tìm kiếm <strong>bệnh viện mắt Bắc Ninh</strong>, người bệnh thường cần một câu trả lời thực tế hơn danh sách địa chỉ: cơ sở nằm ở đâu, có thể gọi cho ai, giờ tiếp nhận có phù hợp với lịch cá nhân không và nên bắt đầu từ chuyên khoa nào. Trang Liên hệ này được xây dựng để trả lời các câu hỏi đó bằng thông tin có thể kiểm tra ngay. Địa chỉ hiện hành của <?php echo esc_html( $tt['ten'] ); ?> là <strong><?php echo esc_html( $dia_chi_day_du ); ?></strong>. Bản đồ nhúng ở phía trên cho phép phóng to, xem tuyến đường và nhận diện vị trí trước khi đi.</p>
				<p>Người dân ở khu vực Bắc Ninh, Bắc Giang và các địa bàn lân cận có thể dùng địa chỉ này làm điểm đến khi cần <strong>khám mắt Bắc Ninh</strong>, kiểm tra thị lực định kỳ hoặc tư vấn một vấn đề nhãn khoa cụ thể. Vì tên địa giới và tuyến đường có thể được cập nhật theo từng giai đoạn, cách an toàn nhất là đối chiếu bản đồ với thông tin trên website rồi gọi tổng đài. Nhân viên có thể xác nhận cổng tiếp nhận, khu đăng ký và những giấy tờ cần mang theo.</p>
				<p>Thời gian công bố hiện tại là <strong><?php echo esc_html( $tt['gio_mo'] . ' – ' . $tt['gio_dong'] ); ?></strong>, tất cả các ngày trong tuần. “Mở cửa” không đồng nghĩa mọi phòng chức năng đều thực hiện cùng một kỹ thuật trong cả ngày. Lịch phẫu thuật, lịch bác sĩ và thời gian làm xét nghiệm chuyên sâu có thể được sắp xếp riêng. Nếu bạn đến để đo khúc xạ, khám đáy mắt, tư vấn Phaco hay tái khám sau mổ, hãy nói rõ mục đích ngay khi gọi để được hướng dẫn đúng.</p>

				<h2 id="khi-nao-kham">Khi nào nên đi khám mắt?</h2>
				<p>Mắt có thể thay đổi âm thầm trong thời gian dài. Nhiều người chỉ nhận ra vấn đề khi nhìn xa mờ, đọc chữ phải nheo mắt hoặc không còn thấy rõ vào buổi tối. Khám định kỳ giúp phát hiện tật khúc xạ, khô mắt, bệnh giác mạc, thay đổi thủy tinh thể và dấu hiệu liên quan đến võng mạc trước khi ảnh hưởng lớn đến sinh hoạt. Một lần kiểm tra cơ bản thường gồm hỏi bệnh, đo thị lực, đo khúc xạ và khám phần trước của mắt; bác sĩ sẽ chỉ định thêm khi cần.</p>
				<p>Người đang có bệnh toàn thân cũng nên đưa việc kiểm tra mắt vào lịch chăm sóc sức khỏe. Đái tháo đường có thể ảnh hưởng mạch máu võng mạc; tăng huyết áp làm tăng nguy cơ biến đổi đáy mắt; một số thuốc dùng dài ngày có thể cần theo dõi nhãn khoa. Người làm việc với màn hình nhiều giờ nên kiểm tra khi có khô rát, cộm, đau đầu hoặc nhìn dao động dù đã nghỉ ngơi. Trẻ em cần được đánh giá nếu ngồi quá gần tivi, nheo mắt, nghiêng đầu, viết sai dòng hoặc giảm kết quả học tập.</p>
				<p>Có những dấu hiệu không nên chờ đến lịch hẹn thông thường: thị lực giảm đột ngột, đau nhức dữ dội, mắt đỏ kèm buồn nôn, chấn thương do dị vật hoặc hóa chất, thấy màn đen che tầm nhìn, chớp sáng và ruồi bay tăng nhanh. Đây là các tình huống cần được đánh giá trực tiếp càng sớm càng tốt. Trong lúc di chuyển, không tự nhỏ thuốc có corticoid, không dụi mắt và không cố lấy dị vật cắm sâu. Nếu hóa chất bắn vào mắt, rửa liên tục bằng nước sạch và tìm trợ giúp y tế ngay.</p>

				<h2 id="nhom-benh">Các nhóm vấn đề nhãn khoa thường gặp</h2>
				<p><strong>Khúc xạ và kiểm soát cận thị.</strong> Cận thị, viễn thị, loạn thị và lão thị được đánh giá bằng đo thị lực và khúc xạ. Trẻ đang tăng độ nhanh cần một kế hoạch theo dõi riêng, kết hợp thời gian nhìn xa, ánh sáng phù hợp và hướng dẫn của bác sĩ. Không nên tự mua kính theo đơn cũ khi trẻ đã lớn hoặc có biểu hiện nhìn lệch.</p>
				<p><strong>Dịch kính – võng mạc.</strong> Ruồi bay, chớp sáng, méo hình hoặc vùng nhìn bị khuyết có thể liên quan đến dịch kính và võng mạc. Bác sĩ có thể cần nhỏ giãn đồng tử, soi đáy mắt, chụp OCT hoặc siêu âm tùy tình huống. Bạn có thể đọc thêm về <a href="<?php echo esc_url( $url( '/chuyen-khoa/dich-kinh-vong-mac/' ) ); ?>">chuyên khoa dịch kính – võng mạc</a> để biết các dấu hiệu cần chuẩn bị khi đi khám.</p>
				<p><strong>Giác mạc và kết mạc.</strong> Đỏ mắt, ngứa, chảy nước mắt, cộm như có cát hoặc sợ ánh sáng có nhiều nguyên nhân: dị ứng, viêm nhiễm, khô mắt, trầy xước giác mạc hay vấn đề do kính áp tròng. Không phải mắt đỏ nào cũng dùng chung một loại thuốc. Nội dung về <a href="<?php echo esc_url( $url( '/chuyen-khoa/giac-mac-ket-mac/' ) ); ?>">giác mạc – kết mạc</a> giúp bạn nhận diện thông tin cần trao đổi với bác sĩ.</p>
				<p><strong>Glôcôm – cườm nước.</strong> Glôcôm có thể tiến triển âm thầm do tổn thương thần kinh thị giác. Đo nhãn áp chỉ là một phần của đánh giá; bác sĩ còn xem góc tiền phòng, thị trường, gai thị và các yếu tố nguy cơ. Người có người thân từng mắc glôcôm, cận thị nặng hoặc đang dùng thuốc có nguy cơ nên hỏi về lịch theo dõi tại <a href="<?php echo esc_url( $url( '/chuyen-khoa/glocom/' ) ); ?>">chuyên khoa glôcôm</a>.</p>
				<p><strong>Thủy tinh thể và Phaco.</strong> Khi thủy tinh thể đục, người bệnh có thể nhìn mờ, chói đèn, giảm tương phản hoặc thay kính liên tục mà không cải thiện. Khám không chỉ xác định có đục thủy tinh thể hay không mà còn đánh giá giác mạc, võng mạc, nhãn áp và bệnh nền trước khi tư vấn. Quyết định phẫu thuật cần dựa trên mức ảnh hưởng đến cuộc sống và chỉ định cá nhân, không chỉ dựa vào một con số thị lực.</p>
				<p><strong>Mắt trẻ em và mắt người cao tuổi.</strong> Trẻ cần môi trường học tập và thói quen thị giác phù hợp; người cao tuổi cần chú ý cườm khô, glôcôm, bệnh võng mạc do đái tháo đường và thoái hóa điểm vàng. Bạn có thể xem hướng dẫn riêng về <a href="<?php echo esc_url( $url( '/chuyen-khoa/mat-tre-em/' ) ); ?>">mắt trẻ em</a> và <a href="<?php echo esc_url( $url( '/chuyen-khoa/mat-nguoi-cao-tuoi/' ) ); ?>">mắt người cao tuổi</a> trước khi đặt lịch.</p>

				<h2 id="quy-trinh">Quy trình khám mắt cơ bản tại bệnh viện</h2>
				<p>Quy trình có thể thay đổi theo triệu chứng, tuổi và hồ sơ y tế, nhưng người bệnh thường đi qua các bước sau. Bước đầu là đăng ký và khai thông tin: triệu chứng bắt đầu từ khi nào, một hay hai mắt, có đau hoặc chấn thương không, đang dùng thuốc gì, có bệnh toàn thân nào và lần khám gần nhất ở đâu. Mô tả càng cụ thể, bác sĩ càng dễ chọn hướng kiểm tra phù hợp.</p>
				<ol>
					<li><strong>Đo thị lực và khúc xạ:</strong> kiểm tra khả năng nhìn xa, nhìn gần, đo kính cũ và thử kính khi cần.</li>
					<li><strong>Khám phần trước:</strong> đánh giá mi, kết mạc, giác mạc, tiền phòng và thủy tinh thể bằng đèn khe.</li>
					<li><strong>Đo nhãn áp hoặc khám đáy mắt:</strong> thực hiện khi có chỉ định, đặc biệt ở người lớn tuổi hoặc có triệu chứng bất thường.</li>
					<li><strong>Cận lâm sàng:</strong> chụp OCT, siêu âm, thị trường hoặc xét nghiệm khác chỉ được đề nghị khi giúp trả lời câu hỏi lâm sàng cụ thể.</li>
					<li><strong>Tư vấn và kế hoạch:</strong> bác sĩ giải thích nhận định, hướng điều trị, lịch tái khám, dấu hiệu cần quay lại sớm và khoản chi phí dự kiến.</li>
				</ol>
				<p>Để xem các dịch vụ đang được công bố, hãy mở <a href="<?php echo esc_url( $url( '/dich-vu/' ) ); ?>">trang dịch vụ nhãn khoa</a>. Nếu cần chuẩn bị ngân sách, tham khảo <a href="<?php echo esc_url( $url( '/bang-gia/' ) ); ?>">bảng giá dịch vụ</a> trước khi gọi. Bảng giá giúp định hướng nhưng không thay thế báo giá theo chỉ định thực tế; vật tư, mức độ bệnh và số lần kiểm tra có thể làm chi phí thay đổi.</p>

				<h2 id="chuan-bi-kham">Chuẩn bị gì trước khi đến khám?</h2>
				<p>Một túi hồ sơ gọn gồm căn cước hoặc giấy tờ cần thiết, thẻ bảo hiểm nếu sử dụng, kính đang đeo, đơn kính cũ, thuốc nhỏ mắt và kết quả chụp trước đây. Nếu bạn từng mổ mắt, hãy ghi lại thời điểm mổ, loại phẫu thuật và nơi thực hiện. Người có bệnh nền nên mang danh sách thuốc đang dùng; điều này quan trọng khi bác sĩ cân nhắc nhỏ giãn đồng tử hoặc lên kế hoạch can thiệp.</p>
				<p>Trước khi đo khúc xạ, hãy nói rõ bạn có đang đeo kính áp tròng hay không. Kính áp tròng có thể ảnh hưởng bề mặt giác mạc và làm kết quả đo chưa phản ánh đúng. Nhân viên sẽ hướng dẫn thời gian tháo kính phù hợp từng loại. Không tự ngưng thuốc điều trị bệnh toàn thân chỉ vì sắp khám mắt.</p>
				<p>Nếu có thể phải nhỏ thuốc giãn đồng tử, nên sắp xếp người đi cùng, mang kính râm và tránh tự lái xe sau khám. Trẻ nhỏ thường hợp tác tốt hơn khi được ngủ đủ, ăn nhẹ và giải thích trước rằng bác sĩ chỉ nhìn vào mắt bằng một dụng cụ có đèn. Người cao tuổi hoặc người khó đi lại nên báo trước để được hướng dẫn lối tiếp nhận thuận tiện.</p>
				<p>Trang <a href="<?php echo esc_url( $url( '/hoi-dap/truoc-khi-di-kham/' ) ); ?>">Chuẩn bị trước khi đi khám mắt</a> có thêm danh sách kiểm tra nhanh. Nếu chưa biết nên đăng ký chuyên khoa nào, hãy mô tả triệu chứng qua tổng đài thay vì tự chọn một dịch vụ phẫu thuật.</p>

				<h2 id="vi-sao-chon">Vì sao nên chọn cơ sở nhãn khoa có quy trình rõ ràng?</h2>
				<p>Một cơ sở khám mắt đáng tin không chỉ nằm ở máy móc. Người bệnh cần biết ai tư vấn, kết quả được giải thích thế nào, khi nào phải tái khám và chi phí nào có thể phát sinh. Quy trình rõ ràng giúp giảm việc làm xét nghiệm trùng lặp, tránh bỏ sót tiền sử bệnh và tạo điều kiện để người bệnh đặt câu hỏi. Đây cũng là nền tảng của trải nghiệm an toàn: thông tin được ghi nhận, chỉ định có lý do và người bệnh được quyền đồng ý sau khi hiểu.</p>
				<p>Tại <?php echo esc_html( $tt['ten'] ); ?>, phần <a href="<?php echo esc_url( $url( '/doi-ngu-bac-si/' ) ); ?>">đội ngũ bác sĩ</a> giúp bạn xem các chuyên môn đang được giới thiệu công khai. Khi đặt lịch, bạn có thể nói rõ muốn gặp bác sĩ nào hoặc cần tư vấn nhóm bệnh nào; nhân viên sẽ kiểm tra lịch thực tế. Không nên chọn cơ sở chỉ vì một lời quảng cáo tuyệt đối như “chữa khỏi 100%”. Kết quả điều trị phụ thuộc chẩn đoán, bệnh nền, mức độ bệnh, tuân thủ và nhiều yếu tố khác.</p>
				<p>Độ tin cậy còn đến từ việc nội dung có người đứng tên, ghi ngày cập nhật và dẫn người đọc đến nguồn liên quan. Bạn có thể mở <a href="<?php echo esc_url( $url( '/kien-thuc/' ) ); ?>">thư viện kiến thức nhãn khoa</a> để đọc thêm, sau đó mang câu hỏi cụ thể đến buổi khám. Nội dung trực tuyến chỉ nhằm giúp chuẩn bị tốt hơn; chẩn đoán và kê đơn luôn cần khám trực tiếp.</p>

				<h2 id="huong-di">Hướng dẫn tìm đường từ các khu vực lân cận</h2>
				<p>Nếu bạn tìm “<strong>bệnh viện mắt gần thành phố Bắc Giang</strong>”, “<strong>bệnh viện mắt ở Bắc Ninh địa chỉ nào</strong>” hoặc “<strong>khám mắt ở Bắc Giang cũ hiện nay ở đâu</strong>”, hãy bắt đầu bằng địa chỉ chuẩn trên bản đồ: <strong><?php echo esc_html( $dia_chi_day_du ); ?></strong>. Đây cũng là cách xác nhận <strong>địa chỉ khám mắt trên đường Hùng Vương</strong> trước khi khởi hành. Nhập đúng tên đường và kiểm tra điểm ghim trước khi đi. Với người ở xa, nên gọi tổng đài để hỏi cổng vào, nơi gửi xe và thời gian tiếp nhận hồ sơ trong ngày.</p>
				<p>Từ khu vực trung tâm, hãy dự trù thêm thời gian nếu đi vào giờ cao điểm. Người đưa trẻ đi khám nên mang theo nước và một vật quen thuộc để trẻ bớt căng thẳng. Người cao tuổi nên chọn phương tiện có thể dừng gần lối vào, mang theo thuốc đang dùng và tránh tự đi một mình nếu thị lực đang giảm.</p>
				<p>Website có thể được cập nhật khi bệnh viện thay đổi số điện thoại, giờ làm việc hoặc hướng dẫn tiếp nhận. Vì vậy, đừng chỉ lưu ảnh chụp màn hình cũ. Trước mỗi chuyến đi, kiểm tra lại trang Liên hệ, bản đồ và thời gian hiển thị; nếu có điểm chưa rõ, gọi <a href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>"><?php echo esc_html( $tt['dien_thoai_hien'] ); ?></a>. Cách này đặc biệt hữu ích khi bạn đi từ Bắc Giang, Bắc Ninh, Hà Nội hoặc các huyện lân cận.</p>

				<h2 id="chi-phi">Chi phí minh bạch và cách hỏi đúng trước khi thực hiện</h2>
				<p>Người bệnh có quyền được biết dịch vụ nào đang được đề nghị, mục đích của bước kiểm tra, khoản phí dự kiến và trường hợp nào có thể phát sinh. Khi gọi, hãy hỏi theo tình huống: “Tôi cần khám vì nhìn mờ một mắt”, “Tôi muốn kiểm tra cận thị cho trẻ” hoặc “Tôi cần tái khám sau phẫu thuật”. Câu hỏi cụ thể giúp nhân viên tra đúng nhóm dịch vụ hơn là chỉ hỏi một con số chung.</p>
				<p>Trước khi đồng ý một thủ thuật, hãy xác nhận lại tên dịch vụ, thời gian thực hiện, có cần người đi cùng không và có phải nhịn ăn hay ngừng kính áp tròng không. Nếu bác sĩ chỉ định thêm chụp chiếu, bạn có thể hỏi lý do và thời điểm nhận kết quả. Minh bạch không có nghĩa mọi người sẽ có cùng một mức phí; đó là việc thông tin được giải thích trước, không tạo khoản thu bất ngờ.</p>

				<h2 id="cham-soc">Chăm sóc thị giác sau buổi khám</h2>
				<p>Hãy đọc lại đơn thuốc và lịch tái khám trước khi rời bệnh viện. Đặt báo thức cho thuốc nhỏ mắt, rửa tay trước khi nhỏ và không dùng chung lọ thuốc. Nếu được dặn hạn chế màn hình, hãy chia thời gian làm việc thành các khoảng ngắn, nhìn xa thường xuyên và điều chỉnh ánh sáng. Kính mới cần thời gian thích nghi; nếu đau đầu kéo dài, nhìn đôi hoặc không thể sử dụng, liên hệ lại thay vì tự mài hoặc đổi kính.</p>
				<p>Sau can thiệp hoặc phẫu thuật, các dấu hiệu như đau tăng, đỏ nhiều, tiết dịch bất thường, nhìn giảm nhanh hay buồn nôn cần được báo ngay. Không tự lái xe khi còn nhìn mờ và không tự ý bỏ thuốc chống viêm, kháng sinh hoặc thuốc hạ nhãn áp. Lưu số tổng đài trong điện thoại để có thể hỏi đúng nơi khi cần.</p>

				<h2 id="faq-lien-he">10 câu hỏi thường gặp về liên hệ và khám mắt</h2>
				<div class="eyecare-lien-he-seo__faq">
					<?php foreach ( $faq as $index => $muc ) : ?>
						<details<?php echo 0 === $index ? ' open' : ''; ?>><summary><?php echo esc_html( $muc['question'] ); ?></summary><div><?php echo wpautop( esc_html( $muc['answer'] ) ); ?></div></details>
					<?php endforeach; ?>
				</div>

				<div class="eyecare-lien-he-seo__closing">
					<strong>Thông tin có thể thay đổi theo lịch vận hành.</strong> Trước khi đến, bạn nên xem lại trang này, bản đồ và gọi tổng đài <?php echo esc_html( $tt['dien_thoai_hien'] ); ?>. Khi cần đọc thêm về bệnh viện, mở trang <a href="<?php echo esc_url( $url( '/gioi-thieu/' ) ); ?>">giới thiệu</a> hoặc quay lại <a href="<?php echo esc_url( $url( '/' ) ); ?>">trang chủ</a> để chọn nhanh chuyên khoa và dịch vụ phù hợp.
				</div>
			</article>

			<aside class="eyecare-lien-he-seo__aside" aria-label="Liên kết hữu ích">
				<div class="eyecare-lien-he-seo__aside-card eyecare-lien-he-seo__aside-card--contact">
					<span class="eyecare-lien-he-seo__aside-label">Cần xác nhận trước khi đi?</span>
					<strong><?php echo esc_html( $tt['dien_thoai_hien'] ); ?></strong>
					<p><?php echo esc_html( $tt['gio_mo'] . ' – ' . $tt['gio_dong'] ); ?> · tất cả các ngày</p>
					<a href="tel:<?php echo esc_attr( $tt['dien_thoai'] ); ?>">Gọi tổng đài <span aria-hidden="true">↗</span></a>
				</div>
				<div class="eyecare-lien-he-seo__aside-card">
					<span class="eyecare-lien-he-seo__aside-label">Đi nhanh đến</span>
					<ul>
						<li><a href="<?php echo esc_url( $url( '/chuyen-khoa/' ) ); ?>">Tất cả chuyên khoa <span>→</span></a></li>
						<li><a href="<?php echo esc_url( $url( '/dich-vu/' ) ); ?>">Dịch vụ nhãn khoa <span>→</span></a></li>
						<li><a href="<?php echo esc_url( $url( '/bang-gia/' ) ); ?>">Bảng giá dịch vụ <span>→</span></a></li>
						<li><a href="<?php echo esc_url( $url( '/doi-ngu-bac-si/' ) ); ?>">Đội ngũ bác sĩ <span>→</span></a></li>
						<li><a href="<?php echo esc_url( $url( '/kien-thuc/' ) ); ?>">Kiến thức nhãn khoa <span>→</span></a></li>
					</ul>
				</div>
				<div class="eyecare-lien-he-seo__aside-card eyecare-lien-he-seo__aside-card--trust">
					<span class="eyecare-lien-he-seo__aside-label">Nhắc nhỏ an toàn</span>
					<p>Nội dung mang tính tham khảo, không thay thế chẩn đoán, kê đơn hoặc chỉ định trực tiếp của bác sĩ.</p>
					<a href="<?php echo esc_url( $url( '/chinh-sach/mien-tru-trach-nhiem-y-khoa/' ) ); ?>">Đọc chính sách y khoa <span>↗</span></a>
				</div>
			</aside>
		</div>
	</div>
</section>
