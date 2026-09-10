/**
 * Chọn ảnh slider trong trang quản trị — mở Thư viện WordPress, kéo sắp thứ tự.
 *
 * Lưu ID ảnh (đã phân tách bằng dấu phẩy) vào ô ẩn #eyecare-slider-anh; PHP
 * đọc ô đó khi lưu. KHÔNG ghi thẳng vào cơ sở dữ liệu ở đây — chỉ dựng giao
 * diện; việc ghi tệp do PHP làm sau khi bấm Lưu.
 *
 * @package eyecare-child
 */
( function ( $ ) {
	'use strict';

	var $ds  = $( '#eyecare-slider-ds' );
	var $luu = $( '#eyecare-slider-anh' );
	var khung; // Khung chọn ảnh wp.media, tạo một lần rồi dùng lại.

	// NGƯỠNG KÍCH THƯỚC ẢNH HERO — khớp đúng bộ lọc dự phòng trong
	// inc/trang-chu.php (eyecare_anh_slider): rộng ≥ 1600px và tỉ lệ ngang
	// ≥ 1.7 (cỡ 16:9 nới nhẹ). Ảnh hẹp hơn sẽ vỡ khi phóng Ken Burns trên màn
	// hình lớn; ảnh dọc/vuông đặt làm nền sẽ bị cắt mất phần giữa — đúng chỗ
	// hay có nội dung. Đây là cảnh báo MỀM: nhắc chứ không chặn, vì người đã
	// chủ động chọn và có thể có lý do riêng.
	var RONG_TOI_THIEU = 1600;
	var TI_LE_TOI_THIEU = 1.7;

	/**
	 * Trả về câu cảnh báo kích thước, hoặc chuỗi rỗng nếu ảnh đạt chuẩn.
	 * rong/cao có thể là 0 khi WordPress chưa lưu metadata — khi đó bỏ qua,
	 * không đoán mò.
	 */
	function canhBaoKichThuoc( rong, cao ) {
		rong = parseInt( rong, 10 ) || 0;
		cao  = parseInt( cao, 10 ) || 0;

		if ( ! rong || ! cao ) {
			return '';
		}

		var loi = [];
		if ( rong < RONG_TOI_THIEU ) {
			loi.push( 'rộng ' + rong + 'px (nên ≥ ' + RONG_TOI_THIEU + 'px)' );
		}
		if ( ( rong / cao ) < TI_LE_TOI_THIEU ) {
			loi.push( 'ảnh hơi đứng, dễ bị cắt (nên là ảnh ngang 16:9)' );
		}

		return loi.length ? loi.join( '; ' ) : '';
	}

	/**
	 * Ghi lại chuỗi ID theo đúng thứ tự thẻ đang hiển thị.
	 */
	function capNhatGiaTri() {
		var ids = $ds.find( '> li' ).map( function () {
			return $( this ).data( 'id' );
		} ).get();
		$luu.val( ids.join( ',' ) );
	}

	/**
	 * Dựng một thẻ ảnh trong danh sách.
	 */
	function dungThe( anh ) {
		var canhBao = '';

		if ( ! anh.alt ) {
			// Cảnh báo mềm: ảnh chưa có văn bản thay thế. Không chặn — người đã
			// chủ động chọn ảnh, nhưng nhắc để bổ sung cho trình đọc màn hình.
			canhBao += '<span class="eyecare-slider-canh-bao" title="Ảnh chưa có văn bản thay thế (alt). '
				+ 'Nên bổ sung trong Thư viện để trình đọc màn hình mô tả được ảnh.">⚠ thiếu alt</span>';
		}

		// Cảnh báo mềm về kích thước: ảnh nhỏ/đứng sẽ vỡ hoặc bị cắt khi làm nền
		// hero. title mang chi tiết để người dùng rê chuột đọc được lý do.
		var loiKichThuoc = canhBaoKichThuoc( anh.rong, anh.cao );
		if ( loiKichThuoc ) {
			canhBao += '<span class="eyecare-slider-canh-bao eyecare-slider-canh-bao--co" '
				+ 'title="Kích thước chưa lý tưởng cho ảnh nền: ' + loiKichThuoc
				+ '. Ảnh vẫn dùng được, nhưng có thể vỡ hoặc bị cắt trên màn hình lớn.">⚠ kích thước</span>';
		}

		return $(
			'<li data-id="' + anh.id + '">' +
				'<img src="' + anh.thumb + '" alt="">' +
				'<span class="eyecare-slider-ten">' + ( anh.ten || ( 'Ảnh #' + anh.id ) ) + '</span>' +
				canhBao +
				'<button type="button" class="eyecare-slider-bo" aria-label="Bỏ ảnh này">×</button>' +
			'</li>'
		);
	}

	/**
	 * Vẽ lại toàn bộ danh sách từ một mảng ảnh.
	 */
	function veDanhSach( ds ) {
		$ds.empty();
		( ds || [] ).forEach( function ( anh ) {
			$ds.append( dungThe( anh ) );
		} );
		capNhatGiaTri();
	}

	// --- Mở Thư viện ------------------------------------------------------
	$( '#eyecare-slider-chon' ).on( 'click', function ( e ) {
		e.preventDefault();

		if ( khung ) {
			khung.open();
			return;
		}

		khung = wp.media( {
			title: 'Chọn ảnh cho slider trang chủ',
			button: { text: 'Dùng ảnh này' },
			library: { type: 'image' },
			multiple: 'add'
		} );

		khung.on( 'select', function () {
			var chon = khung.state().get( 'selection' );

			chon.each( function ( attachment ) {
				var a = attachment.toJSON();

				// Bỏ qua nếu ảnh đã có trong danh sách.
				if ( $ds.find( '> li[data-id="' + a.id + '"]' ).length ) {
					return;
				}

				var thumb = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
				$ds.append( dungThe( {
					id: a.id,
					thumb: thumb,
					alt: a.alt,
					ten: a.title,
					rong: a.width,
					cao: a.height
				} ) );
			} );

			capNhatGiaTri();
		} );

		khung.open();
	} );

	// --- Bỏ một ảnh -------------------------------------------------------
	$ds.on( 'click', '.eyecare-slider-bo', function () {
		$( this ).closest( 'li' ).remove();
		capNhatGiaTri();
	} );

	// --- Bỏ tất cả --------------------------------------------------------
	$( '#eyecare-slider-xoa-het' ).on( 'click', function ( e ) {
		e.preventDefault();
		if ( window.confirm( 'Bỏ tất cả ảnh khỏi slider?' ) ) {
			$ds.empty();
			capNhatGiaTri();
		}
	} );

	// --- Kéo sắp thứ tự ---------------------------------------------------
	if ( $.fn.sortable ) {
		$ds.sortable( {
			placeholder: 'eyecare-slider-cho-trong',
			update: capNhatGiaTri
		} );
	}

	// --- Dựng lại danh sách khi mở trang ----------------------------------
	$( function () {
		if ( window.eyecareSliderData ) {
			veDanhSach( window.eyecareSliderData );
		}
	} );

} )( jQuery );
