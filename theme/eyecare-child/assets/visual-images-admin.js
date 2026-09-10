/**
 * Chọn ảnh cho trang quản trị "Ảnh giao diện".
 *
 * @package eyecare-child
 */
( function ( $ ) {
	'use strict';

	var config = window.eyecareVisualImagesAdmin || {};
	var frame;
	var $currentSlot;

	function editUrl( id ) {
		return ( config.editUrl || '' ).replace( '__ID__', id );
	}

	function setSlotImage( $slot, attachment ) {
		var thumb = attachment.url;
		if ( attachment.sizes && attachment.sizes.medium ) {
			thumb = attachment.sizes.medium.url;
		} else if ( attachment.sizes && attachment.sizes.thumbnail ) {
			thumb = attachment.sizes.thumbnail.url;
		}

		$slot.find( '[data-visual-input]' ).val( attachment.id );
		$slot.find( '[data-visual-preview]' ).html( '<img src="' + thumb + '" alt="">' );
		$slot.find( '[data-visual-edit]' )
			.attr( 'href', editUrl( attachment.id ) )
			.removeClass( 'is-disabled' );
	}

	function clearSlotImage( $slot ) {
		$slot.find( '[data-visual-input]' ).val( '0' );
		$slot.find( '[data-visual-preview]' ).html( '<span>' + ( config.placeholder || 'Chưa chọn ảnh' ) + '</span>' );
		$slot.find( '[data-visual-edit]' )
			.attr( 'href', '#' )
			.addClass( 'is-disabled' );
	}

	$( document ).on( 'click', '[data-visual-select]', function ( event ) {
		event.preventDefault();
		$currentSlot = $( this ).closest( '[data-visual-slot]' );

		if ( ! frame ) {
			frame = wp.media( {
				title: config.title || 'Chọn ảnh giao diện',
				button: { text: config.button || 'Dùng ảnh này' },
				library: { type: 'image' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first();
				if ( ! attachment || ! $currentSlot ) {
					return;
				}

				setSlotImage( $currentSlot, attachment.toJSON() );
			} );
		}

		frame.open();
	} );

	$( document ).on( 'click', '[data-visual-remove]', function ( event ) {
		event.preventDefault();
		clearSlotImage( $( this ).closest( '[data-visual-slot]' ) );
	} );

	$( document ).on( 'click', '[data-visual-edit].is-disabled', function ( event ) {
		event.preventDefault();
	} );
} )( jQuery );
