/* Site Preview Dashboard — Frontend JS */
(function () {
	'use strict';

	var overlay  = document.getElementById( 'spd-popup' );
	var iframe   = document.getElementById( 'spd-popup-iframe' );
	var titleEl  = document.getElementById( 'spd-popup-title' );
	var visitBtn = document.getElementById( 'spd-popup-visit' );
	var closeBtn = document.getElementById( 'spd-popup-close' );

	if ( ! overlay ) return;

	// ── Open ───────────────────────────────────────────────────────────────────

	function openPopup( card ) {
		var siteUrl  = card.getAttribute( 'data-site-url' );
		var siteName = card.getAttribute( 'data-site-name' );

		titleEl.textContent = siteName;
		visitBtn.href       = siteUrl;
		iframe.src          = siteUrl; // lazy-load: set src only on open

		overlay.setAttribute( 'aria-hidden', 'false' );
		overlay.classList.add( 'spd-open' );
		document.body.style.overflow = 'hidden';

		// Focus close button for keyboard accessibility.
		closeBtn.focus();
	}

	// ── Close ──────────────────────────────────────────────────────────────────

	function closePopup() {
		overlay.classList.remove( 'spd-open' );
		overlay.setAttribute( 'aria-hidden', 'true' );
		document.body.style.overflow = '';
		iframe.src = ''; // stop all loading / media
	}

	// ── Card clicks ────────────────────────────────────────────────────────────

	document.querySelectorAll( '.spd-card' ).forEach( function ( card ) {
		card.addEventListener( 'click', function () {
			openPopup( card );
		} );
		card.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				openPopup( card );
			}
		} );
	} );

	// ── Close button ───────────────────────────────────────────────────────────

	closeBtn.addEventListener( 'click', closePopup );

	// ── Click outside popup window ─────────────────────────────────────────────
	// Overlay click always closes; popup window stops propagation so inner clicks don't bubble up.

	overlay.addEventListener( 'click', closePopup );

	var popupWindow = overlay.querySelector( '.spd-popup-window' );
	if ( popupWindow ) {
		popupWindow.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
		} );
	}

	// ── ESC key ────────────────────────────────────────────────────────────────

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && overlay.classList.contains( 'spd-open' ) ) {
			closePopup();
		}
	} );
} )();
