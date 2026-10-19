/**
 * Bullet Gallery - admin photo picker.
 */
( function ( $ ) {
	'use strict';

	var l10n = window.bulletGalleryAdmin || {};

	function initColorPickers() {
		if ( $.fn.wpColorPicker ) {
			$( '.bgal-color' ).wpColorPicker();
		}
	}

	function initPicker() {
		var $wrap = $( '[data-bgal-images]' );

		if ( ! $wrap.length ) {
			return;
		}

		var $list = $wrap.find( '[data-bgal-list]' );
		var $input = $wrap.find( '[data-bgal-input]' );
		var $empty = $wrap.find( '[data-bgal-empty]' );
		var $count = $wrap.find( '[data-bgal-count]' );
		var frame = null;

		function sync() {
			var list = [];

			$list.children( '[data-id]' ).each( function () {
				list.push( $( this ).attr( 'data-id' ) );
			} );

			$input.val( list.join( ',' ) );

			$empty.toggle( 0 === list.length );

			if ( 0 === list.length ) {
				$count.text( '' );
			} else if ( 1 === list.length ) {
				$count.text( l10n.countOne || '1 photo' );
			} else {
				$count.text( ( l10n.countMany || '%s photos' ).replace( '%s', list.length ) );
			}
		}

		function addItem( attachment ) {
			if ( $list.find( '[data-id="' + attachment.id + '"]' ).length ) {
				return;
			}

			var url = attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;

			var alt = attachment.alt || '';
			var warn = alt ? '' : '<span class="bgal-thumb__warn" title="' + ( l10n.noAlt || 'No alt text' ) + '">!</span>';

			var $item = $(
				'<li class="bgal-thumb" data-id="' + attachment.id + '">' +
					'<img src="" alt="" />' +
					warn +
					'<button type="button" class="bgal-thumb__remove" aria-label="' + ( l10n.removeLabel || 'Remove' ) + '">&times;</button>' +
				'</li>'
			);

			$item.find( 'img' ).attr( 'src', url );
			$list.append( $item );
		}

		$wrap.on( 'click', '[data-bgal-add]', function ( event ) {
			event.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: l10n.frameTitle || 'Select photos',
				button: { text: l10n.frameButton || 'Add' },
				library: { type: 'image' },
				multiple: 'add'
			} );

			frame.on( 'select', function () {
				frame.state().get( 'selection' ).each( function ( model ) {
					addItem( model.toJSON() );
				} );

				sync();
			} );

			frame.open();
		} );

		$wrap.on( 'click', '.bgal-thumb__remove', function ( event ) {
			event.preventDefault();
			$( this ).closest( '.bgal-thumb' ).remove();
			sync();
		} );

		$wrap.on( 'click', '[data-bgal-clear]', function ( event ) {
			event.preventDefault();

			if ( ! $list.children().length ) {
				return;
			}

			if ( window.confirm( l10n.confirmAll || 'Remove all?' ) ) {
				$list.empty();
				sync();
			}
		} );

		if ( $list.sortable ) {
			$list.sortable( {
				items: '> li',
				cursor: 'move',
				tolerance: 'pointer',
				forcePlaceholderSize: true,
				placeholder: 'bgal-thumb bgal-thumb--placeholder',
				update: sync
			} );
		}

		sync();
	}

	$( function () {
		initColorPickers();
		initPicker();
	} );
}( jQuery ) );
