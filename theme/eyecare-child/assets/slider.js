/**
 * SLIDER TRANG CHỦ — lớp nâng cấp (progressive enhancement).
 *
 * KHÔNG có tệp này thì slider VẪN CHẠY: markup gốc là scroll-snap ngang cộng
 * chấm neo (#anh-123), trượt được bằng chuột, bằng phím, bằng cách bấm chấm —
 * kể cả khi JS bị chặn. Tệp này chỉ THÊM:
 *   - hiệu ứng chuyển ảnh mờ dần / trượt (đọc data-hieu-ung)
 *   - tự chuyển ảnh khi bật (data-tu-chay), nhịp data-nhip giây
 *   - nút Tạm dừng để người đọc giữ quyền điều khiển (WCAG 2.2.2)
 *   - đồng bộ trạng thái chấm chỉ mục đang xem
 *
 * 🔴 TÔN TRỌNG "GIẢM CHUYỂN ĐỘNG": nếu máy người dùng đặt prefers-reduced-motion
 * thì KHÔNG tự chạy và KHÔNG hiệu ứng — chỉ giữ phần trượt tay. Đây là người
 * dễ chóng mặt vì chuyển động; ép hiệu ứng lên họ là hại.
 *
 * @package eyecare-child
 */
( function () {
	'use strict';

	var gtoc = document.querySelector( '.eyecare-hero--anh' );
	if ( ! gtoc ) {
		return;
	}

	var bang = gtoc.querySelector( '.eyecare-hero__bang' );
	var anh  = bang ? Array.prototype.slice.call( bang.querySelectorAll( '.eyecare-hero__khung' ) ) : [];
	if ( ! bang || anh.length < 2 ) {
		return; // Một ảnh (hoặc không có) thì không có gì để nâng cấp.
	}

	// Đánh dấu đã bật JS — CSS dựa vào class này để hiện nút Tạm dừng và bật
	// hiệu ứng. Không có class này thì mọi thứ về đúng trạng thái không-JS.
	gtoc.classList.add( 'eyecare-hero--js' );

	var giamChuyenDong = window.matchMedia
		&& window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var hieuUng = gtoc.getAttribute( 'data-hieu-ung' ) || 'mo';
	var tuChay  = gtoc.getAttribute( 'data-tu-chay' ) === '1' && ! giamChuyenDong;
	var nhip    = parseInt( gtoc.getAttribute( 'data-nhip' ), 10 ) || 6;

	var cham = Array.prototype.slice.call(
		gtoc.querySelectorAll( '.eyecare-hero__cham a' )
	);

	var hienTai = 0;
	var hen     = null;
	var dangChay = tuChay;

	/**
	 * Chuyển tới ảnh thứ i (vòng lại từ đầu khi hết).
	 */
	function toi( i ) {
		hienTai = ( i + anh.length ) % anh.length;

		if ( 'mo' !== hieuUng || giamChuyenDong ) {
			// Chế độ trượt dùng scroll-snap sẵn có. Khi người dùng yêu cầu giảm
			// chuyển động, vẫn chuyển đúng ảnh nhưng bỏ cuộn mượt.
			bang.scrollTo( {
				left: anh[ hienTai ].offsetLeft - bang.offsetLeft,
				behavior: giamChuyenDong ? 'auto' : 'smooth'
			} );
		}

		// Đánh dấu khung ĐANG XEM ở MỌI chế độ — không chỉ chế độ mờ.
		// Chế độ mờ dùng --hien để chồng/hiện ảnh; nhưng Ken Burns (CSS) cũng
		// dựa vào --hien để biết ảnh nào đang xem mà phóng. Ở chế độ trượt,
		// --hien không đổi cách hiển thị (ảnh vẫn xếp hàng ngang) mà chỉ cho
		// Ken Burns cái móc — nên gắn ở đây là an toàn cho cả hai.
		anh.forEach( function ( el, idx ) {
			el.classList.toggle( 'eyecare-hero__khung--hien', idx === hienTai );
		} );

		capNhatCham();
	}

	/**
	 * Tô đậm chấm của ảnh đang xem, và khai cho trình đọc màn hình.
	 */
	function capNhatCham() {
		cham.forEach( function ( a, idx ) {
			var dang = idx === hienTai;
			a.classList.toggle( 'eyecare-hero__cham--dang', dang );
			if ( dang ) {
				a.setAttribute( 'aria-current', 'true' );
			} else {
				a.removeAttribute( 'aria-current' );
			}
		} );
	}

	/**
	 * Đặt lịch chuyển ảnh kế tiếp.
	 */
	function henTiep() {
		if ( ! dangChay ) {
			return;
		}
		huyHen();
		hen = window.setTimeout( function () {
			toi( hienTai + 1 );
			henTiep();
		}, nhip * 1000 );
	}

	function huyHen() {
		if ( hen ) {
			window.clearTimeout( hen );
			hen = null;
		}
	}

	// --- Chế độ mờ dần: cần đặt ảnh chồng lên nhau (class --mo bật lưới chồng
	// trong CSS). Chỉ bật ở chế độ mờ và khi không giảm chuyển động.
	if ( 'mo' === hieuUng && ! giamChuyenDong ) {
		gtoc.classList.add( 'eyecare-hero--mo' );
	}

	// Đánh dấu ảnh đầu ĐANG XEM ở MỌI chế độ. Chế độ mờ cần --hien để hiện ảnh
	// đầu; chế độ trượt thì --hien chỉ là móc cho Ken Burns phóng đúng ảnh đầu
	// ngay khi tải. Không gắn ở đây thì ảnh đầu không phóng cho tới lần chuyển
	// ảnh đầu tiên.
	anh[ 0 ].classList.add( 'eyecare-hero__khung--hien' );

	// --- Chấm chỉ mục: bấm thì dừng tự chạy tạm rồi chuyển ------------------
	cham.forEach( function ( a, idx ) {
		a.addEventListener( 'click', function ( e ) {
			// JS đã hoạt động thì tự điều khiển để không đổi hash URL; khi JS bị
			// chặn, liên kết neo gốc vẫn là phương án dự phòng đầy đủ.
			e.preventDefault();
			toi( idx );
			henTiep(); // đặt lại đồng hồ sau tương tác của người dùng
		} );
	} );

	capNhatCham();

	// --- Nút trước/sau và bàn phím -------------------------------------------
	var nutTruoc = gtoc.querySelector( '.eyecare-hero__truoc' );
	var nutSau   = gtoc.querySelector( '.eyecare-hero__sau' );

	function chuyenBangTay( buoc ) {
		toi( hienTai + buoc );
		henTiep();
	}

	if ( nutTruoc ) {
		nutTruoc.addEventListener( 'click', function () {
			chuyenBangTay( -1 );
		} );
	}

	if ( nutSau ) {
		nutSau.addEventListener( 'click', function () {
			chuyenBangTay( 1 );
		} );
	}

	bang.addEventListener( 'keydown', function ( e ) {
		if ( 'ArrowLeft' === e.key ) {
			e.preventDefault();
			chuyenBangTay( -1 );
		} else if ( 'ArrowRight' === e.key ) {
			e.preventDefault();
			chuyenBangTay( 1 );
		}
	} );

	// Vuốt/kéo trực tiếp cũng phải cập nhật chấm và ảnh hiện tại. Chỉ đọc vị
	// trí ở animation frame kế tiếp để không làm nặng sự kiện scroll.
	var khungCuon = null;
	if ( 'mo' !== hieuUng || giamChuyenDong ) {
		bang.addEventListener( 'scroll', function () {
			if ( khungCuon ) {
				window.cancelAnimationFrame( khungCuon );
			}
			khungCuon = window.requestAnimationFrame( function () {
				var tam = bang.scrollLeft + ( bang.clientWidth / 2 );
				var ganNhat = 0;
				var khoangCach = Infinity;

				anh.forEach( function ( el, idx ) {
					var giua = el.offsetLeft + ( el.offsetWidth / 2 );
					var lech = Math.abs( giua - tam );
					if ( lech < khoangCach ) {
						khoangCach = lech;
						ganNhat = idx;
					}
				} );

				if ( ganNhat !== hienTai ) {
					hienTai = ganNhat;
					anh.forEach( function ( el, idx ) {
						el.classList.toggle( 'eyecare-hero__khung--hien', idx === hienTai );
					} );
					capNhatCham();
				}
			} );
		}, { passive: true } );
	}

	// --- Nút Tạm dừng ------------------------------------------------------
	// Nút chỉ vẽ một biểu tượng (⏸ khi đang chạy, ▶ khi đang dừng) — không có
	// chữ hiển thị. Trình đọc màn hình đọc aria-label; aria-pressed báo trạng
	// thái cho công cụ hỗ trợ; title cho chuột. CSS giấu phần <span> chữ đi
	// bằng .eyecare-hero__tam-dung-chu { font-size: 0 }.
	var nut = gtoc.querySelector( '.eyecare-hero__tam-dung' );
	if ( nut ) {
		var chu      = nut.querySelector( '.eyecare-hero__tam-dung-chu' );

		function veNut() {
			nut.setAttribute( 'aria-pressed', dangChay ? 'false' : 'true' );
			nut.setAttribute( 'aria-label',  dangChay ? 'Tạm dừng' : 'Chạy tiếp' );
			nut.setAttribute( 'title',       dangChay ? 'Tạm dừng' : 'Chạy tiếp' );
			if ( chu ) {
				chu.textContent = dangChay ? '⏸' : '▶';
			}
		}

		nut.addEventListener( 'click', function () {
			dangChay = ! dangChay;
			if ( dangChay ) {
				henTiep();
			} else {
				huyHen();
			}
			veNut();
		} );

		veNut();
	}

	// --- Dừng khi con trỏ ở trên slider hoặc tab mất tiêu điểm -------------
	// Người đang đọc chữ trên ảnh không muốn nó nhảy giữa chừng.
	gtoc.addEventListener( 'mouseenter', huyHen );
	gtoc.addEventListener( 'mouseleave', function () {
		if ( dangChay ) {
			henTiep();
		}
	} );
	gtoc.addEventListener( 'focusin', huyHen );
	gtoc.addEventListener( 'focusout', function () {
		if ( dangChay ) {
			henTiep();
		}
	} );

	document.addEventListener( 'visibilitychange', function () {
		if ( document.hidden ) {
			huyHen();
		} else if ( dangChay ) {
			henTiep();
		}
	} );

	// --- Khởi động ---------------------------------------------------------
	henTiep();

} )();
