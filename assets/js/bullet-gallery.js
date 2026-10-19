/**
 * Bullet Gallery - front end behaviour.
 *
 * Handles the horizontal scrollers and the lightbox. No dependencies.
 */
( function () {
	'use strict';

	var l10n = window.bulletGalleryL10n || {};
	var lightbox = null;
	var items = [];
	var current = 0;
	var lastFocus = null;

	function text( key, fallback ) {
		return l10n[ key ] || fallback;
	}

	/* ---------------- scrollers ---------------- */

	function setupTrack( section ) {
		var track = section.querySelector( '[data-bgal-track]' );
		var prev = section.querySelector( '[data-bgal-prev]' );
		var next = section.querySelector( '[data-bgal-next]' );
		var fill = section.querySelector( '[data-bgal-fill]' );
		var bar = section.querySelector( '.bgal-bar' );

		if ( ! track ) {
			return;
		}

		function gap() {
			var styles = window.getComputedStyle( track );
			var value = parseFloat( styles.columnGap || styles.gap );

			return isNaN( value ) ? 14 : value;
		}

		function update() {
			var max = track.scrollWidth - track.clientWidth;

			if ( fill && bar ) {
				var ratio = track.clientWidth / track.scrollWidth;
				var width = Math.max( 10, Math.min( 100, ratio * 100 ) );

				fill.style.width = width + '%';

				// Travel is measured inside the bar, not the track, or the
				// thumb overshoots the right edge on wide screens.
				var travel = bar.clientWidth - ( bar.clientWidth * width / 100 );
				var progress = max > 0 ? track.scrollLeft / max : 0;

				fill.style.transform = 'translateX(' + ( progress * travel ) + 'px)';
			}

			if ( prev ) {
				prev.disabled = track.scrollLeft <= 2;
			}

			if ( next ) {
				next.disabled = track.scrollLeft >= max - 2;
			}
		}

		function step( direction ) {
			var card = track.querySelector( '.bgal-item' );
			var width = card ? card.getBoundingClientRect().width + gap() : track.clientWidth * 0.8;
			var pages = Math.max( 1, Math.floor( track.clientWidth / width ) );

			track.scrollBy( { left: direction * width * pages, behavior: 'smooth' } );
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				step( -1 );
			} );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				step( 1 );
			} );
		}

		/* ---- click and drag with a mouse ---- */

		var dragging = false;
		var moved = false;
		var startX = 0;
		var startScroll = 0;

		track.addEventListener( 'pointerdown', function ( event ) {
			if ( 'mouse' !== event.pointerType || 0 !== event.button ) {
				return;
			}

			dragging = true;
			moved = false;
			startX = event.clientX;
			startScroll = track.scrollLeft;

			track.classList.add( 'is-grabbing' );
		} );

		window.addEventListener( 'pointermove', function ( event ) {
			if ( ! dragging ) {
				return;
			}

			var distance = event.clientX - startX;

			if ( ! moved && Math.abs( distance ) > 4 ) {
				moved = true;
			}

			if ( moved ) {
				event.preventDefault();
				track.scrollLeft = startScroll - distance;
			}
		}, { passive: false } );

		function endDrag() {
			if ( ! dragging ) {
				return;
			}

			dragging = false;
			track.classList.remove( 'is-grabbing' );

			// Let the click handler below see the flag, then clear it.
			window.setTimeout( function () {
				moved = false;
			}, 0 );
		}

		window.addEventListener( 'pointerup', endDrag );
		window.addEventListener( 'pointercancel', endDrag );

		// Swallow the click that ends a drag so the lightbox does not open.
		track.addEventListener( 'click', function ( event ) {
			if ( moved ) {
				event.preventDefault();
				event.stopPropagation();
			}
		}, true );

		track.addEventListener( 'dragstart', function ( event ) {
			event.preventDefault();
		} );

		track.addEventListener( 'scroll', function () {
			window.requestAnimationFrame( update );
		}, { passive: true } );

		window.addEventListener( 'resize', function () {
			window.requestAnimationFrame( update );
		} );

		if ( window.ResizeObserver ) {
			new window.ResizeObserver( update ).observe( track );
		}

		window.setTimeout( update, 60 );
		window.setTimeout( update, 400 );
		update();
	}

	/* ---------------- lightbox ---------------- */

	function buildLightbox() {
		var box = document.createElement( 'div' );

		box.className = 'bgal-lb';
		box.setAttribute( 'role', 'dialog' );
		box.setAttribute( 'aria-modal', 'true' );
		box.setAttribute( 'aria-label', text( 'close', 'Photo' ) );

		box.innerHTML =
			'<button type="button" class="bgal-lb__btn bgal-lb__close" data-lb-close aria-label="' + text( 'close', 'Close' ) + '">&times;</button>' +
			'<button type="button" class="bgal-lb__btn bgal-lb__prev" data-lb-prev aria-label="' + text( 'previous', 'Previous photo' ) + '">&#8592;</button>' +
			'<img class="bgal-lb__img" data-lb-img alt="" />' +
			'<button type="button" class="bgal-lb__btn bgal-lb__next" data-lb-next aria-label="' + text( 'next', 'Next photo' ) + '">&#8594;</button>' +
			'<p class="bgal-lb__caption" data-lb-caption></p>' +
			'<span class="bgal-lb__counter" data-lb-counter></span>';

		document.body.appendChild( box );

		box.addEventListener( 'click', function ( event ) {
			if ( event.target === box || event.target.hasAttribute( 'data-lb-close' ) ) {
				close();
			}
		} );

		box.querySelector( '[data-lb-prev]' ).addEventListener( 'click', function () {
			show( current - 1 );
		} );

		box.querySelector( '[data-lb-next]' ).addEventListener( 'click', function () {
			show( current + 1 );
		} );

		return box;
	}

	function show( index ) {
		if ( ! items.length ) {
			return;
		}

		current = ( index + items.length ) % items.length;

		var item = items[ current ];
		var img = lightbox.querySelector( '[data-lb-img]' );
		var caption = lightbox.querySelector( '[data-lb-caption]' );
		var counter = lightbox.querySelector( '[data-lb-counter]' );
		var inner = item.querySelector( 'img' );

		img.src = item.getAttribute( 'data-bgal-full' );
		img.alt = inner ? inner.getAttribute( 'alt' ) || '' : '';

		var text_caption = item.getAttribute( 'data-bgal-caption' ) || '';

		caption.textContent = text_caption;
		caption.style.display = text_caption ? '' : 'none';

		counter.textContent = text( 'counter', '%1$s of %2$s' )
			.replace( '%1$s', current + 1 )
			.replace( '%2$s', items.length );

		var single = items.length < 2;

		lightbox.querySelector( '[data-lb-prev]' ).style.display = single ? 'none' : '';
		lightbox.querySelector( '[data-lb-next]' ).style.display = single ? 'none' : '';
	}

	function open( index ) {
		lastFocus = document.activeElement;

		show( index );

		lightbox.classList.add( 'is-open' );
		document.body.classList.add( 'bgal-lb-open' );
		lightbox.querySelector( '[data-lb-close]' ).focus();
	}

	function close() {
		lightbox.classList.remove( 'is-open' );
		document.body.classList.remove( 'bgal-lb-open' );

		if ( lastFocus && typeof lastFocus.focus === 'function' ) {
			lastFocus.focus();
		}
	}

	function onKey( event ) {
		if ( ! lightbox || ! lightbox.classList.contains( 'is-open' ) ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			close();
		} else if ( 'ArrowLeft' === event.key ) {
			show( current - 1 );
		} else if ( 'ArrowRight' === event.key ) {
			show( current + 1 );
		}
	}

	/* ---------------- boot ---------------- */

	function boot() {
		var sections = document.querySelectorAll( '.bgal-section' );
		var i;

		for ( i = 0; i < sections.length; i++ ) {
			setupTrack( sections[ i ] );
		}

		var links = document.querySelectorAll( 'a.bgal-item[data-bgal-full]' );

		if ( ! links.length ) {
			return;
		}

		items = Array.prototype.slice.call( links );
		lightbox = buildLightbox();

		items.forEach( function ( link, index ) {
			link.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				open( index );
			} );
		} );

		document.addEventListener( 'keydown', onKey );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
