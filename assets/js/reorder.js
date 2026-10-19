/**
 * Bullet Gallery - drag and drop section ordering.
 */
( function ( $ ) {
	'use strict';

	var data = window.bulletGalleryReorder || {};

	$( function () {
		var $list = $( '#bgal-reorder-list' );
		var $status = $( '#bgal-reorder-status' );

		if ( ! $list.length ) {
			return;
		}

		function save() {
			var order = [];

			$list.children( '[data-id]' ).each( function () {
				order.push( $( this ).attr( 'data-id' ) );
			} );

			$status.removeClass( 'is-error' ).text( data.saving || 'Saving…' );

			$.post( data.ajaxUrl, {
				action: 'bullet_gallery_reorder',
				nonce: data.nonce,
				order: order
			} )
				.done( function ( response ) {
					if ( response && response.success ) {
						$status.text( data.saved || 'Saved.' );
					} else {
						$status.addClass( 'is-error' ).text( data.failed || 'Could not save.' );
					}
				} )
				.fail( function () {
					$status.addClass( 'is-error' ).text( data.failed || 'Could not save.' );
				} );
		}

		$list.sortable( {
			items: '> li',
			handle: '.bgal-reorder__handle',
			axis: 'y',
			cursor: 'move',
			tolerance: 'pointer',
			placeholder: 'bgal-reorder__item bgal-reorder__item--placeholder',
			forcePlaceholderSize: true,
			update: save
		} );
	} );
}( jQuery ) );
