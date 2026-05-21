/* Site Preview Dashboard — Admin JS */
/* global spdAdmin */
(function () {
	'use strict';

	var ajaxurl = spdAdmin.ajaxurl;
	var nonce   = spdAdmin.nonce;

	// ── Helpers ────────────────────────────────────────────────────────────────

	function post( action, extra ) {
		var params = new URLSearchParams( Object.assign( { action: action, nonce: nonce }, extra ) );
		return fetch( ajaxurl, {
			method:  'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:    params.toString(),
		} ).then( function ( r ) { return r.json(); } );
	}

	// ── Drag-and-Drop Reorder ──────────────────────────────────────────────────

	var tbody   = document.getElementById( 'spd-sites-tbody' );
	var dragSrc = null;

	if ( tbody ) {
		tbody.addEventListener( 'dragstart', function ( e ) {
			dragSrc = e.target.closest( 'tr' );
			if ( ! dragSrc ) return;
			dragSrc.classList.add( 'spd-dragging' );
			e.dataTransfer.effectAllowed = 'move';
		} );

		tbody.addEventListener( 'dragover', function ( e ) {
			e.preventDefault();
			var target = e.target.closest( 'tr' );
			if ( ! target || target === dragSrc ) return;

			tbody.querySelectorAll( 'tr' ).forEach( function ( row ) {
				row.classList.remove( 'spd-drag-over' );
			} );
			target.classList.add( 'spd-drag-over' );

			var rect = target.getBoundingClientRect();
			if ( e.clientY < rect.top + rect.height / 2 ) {
				tbody.insertBefore( dragSrc, target );
			} else {
				tbody.insertBefore( dragSrc, target.nextSibling );
			}
		} );

		tbody.addEventListener( 'dragend', function () {
			tbody.querySelectorAll( 'tr' ).forEach( function ( row ) {
				row.classList.remove( 'spd-dragging', 'spd-drag-over' );
			} );
			dragSrc = null;
			saveOrder();
		} );
	}

	function saveOrder() {
		var rows   = tbody.querySelectorAll( 'tr[data-site-id]' );
		var params = new URLSearchParams();
		params.append( 'action', 'spd_reorder_sites' );
		params.append( 'nonce', nonce );
		rows.forEach( function ( row ) {
			params.append( 'order[]', row.getAttribute( 'data-site-id' ) );
		} );

		fetch( ajaxurl, {
			method:  'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:    params.toString(),
		} ).catch( function () {} );
	}

	// ── Single Refresh ─────────────────────────────────────────────────────────

	document.querySelectorAll( '.spd-btn-refresh' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var siteId   = btn.getAttribute( 'data-site-id' );
			var row      = btn.closest( 'tr' );
			var feedback = row.querySelector( '.spd-action-feedback' );

			btn.disabled        = true;
			feedback.textContent = '⏳ Bezig…';

			post( 'spd_refresh_site', { site_id: siteId } )
				.then( function ( data ) {
					btn.disabled = false;
					if ( data.success ) {
						feedback.textContent = '✓ Klaar';
						row.querySelector( '.spd-last-updated' ).textContent = data.data.last_updated;
					} else {
						feedback.textContent = '✗ ' + ( data.data || 'Fout' );
					}
				} )
				.catch( function () {
					btn.disabled        = false;
					feedback.textContent = '✗ Netwerkfout';
				} )
				.finally( function () {
					setTimeout( function () { feedback.textContent = ''; }, 5000 );
				} );
		} );
	} );

	// ── Toggle Active ──────────────────────────────────────────────────────────

	document.querySelectorAll( '.spd-btn-toggle' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var siteId    = btn.getAttribute( 'data-site-id' );
			var row       = btn.closest( 'tr' );
			var feedback  = row.querySelector( '.spd-action-feedback' );
			var statusDot = row.querySelector( '.spd-status-dot' );
			var statusTxt = row.querySelector( '.spd-status-text' );

			btn.disabled        = true;
			feedback.textContent = '⏳';

			post( 'spd_toggle_site', { site_id: siteId } )
				.then( function ( data ) {
					btn.disabled        = false;
					feedback.textContent = '';
					if ( data.success ) {
						var isActive = data.data.active;
						btn.textContent = isActive ? 'Uitzetten' : 'Aanzetten';
						btn.setAttribute( 'data-active', isActive ? '1' : '0' );
						statusDot.className = 'spd-status-dot ' + ( isActive ? 'spd-active' : 'spd-inactive' );
						statusDot.title     = isActive ? 'Actief' : 'Uitgeschakeld';
						statusTxt.textContent = isActive ? 'Actief' : 'Uitgeschakeld';
					}
				} )
				.catch( function () {
					btn.disabled        = false;
					feedback.textContent = '✗';
					setTimeout( function () { feedback.textContent = ''; }, 3000 );
				} );
		} );
	} );

	// ── Delete ─────────────────────────────────────────────────────────────────

	document.querySelectorAll( '.spd-btn-delete' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			if ( ! window.confirm( 'Weet je zeker dat je deze site wilt verwijderen?' ) ) return;

			var siteId = btn.getAttribute( 'data-site-id' );
			var row    = btn.closest( 'tr' );

			btn.disabled = true;

			post( 'spd_delete_site', { site_id: siteId } )
				.then( function ( data ) {
					if ( data.success ) {
						row.style.transition = 'opacity 0.3s ease';
						row.style.opacity    = '0';
						setTimeout( function () { row.remove(); }, 320 );
					} else {
						btn.disabled = false;
					}
				} )
				.catch( function () {
					btn.disabled = false;
				} );
		} );
	} );

	// ── Refresh All (AJAX loop) ────────────────────────────────────────────────

	var refreshAllBtn  = document.getElementById( 'spd-refresh-all' );
	var progressText   = document.getElementById( 'spd-refresh-progress' );

	if ( refreshAllBtn ) {
		refreshAllBtn.addEventListener( 'click', function () {
			var activeSiteIds = Array.from(
				document.querySelectorAll( '.spd-btn-refresh' )
			).filter( function ( btn ) {
				return btn.closest( 'tr' ).querySelector( '.spd-status-dot.spd-active' ) !== null;
			} ).map( function ( btn ) {
				return btn.getAttribute( 'data-site-id' );
			} );

			if ( activeSiteIds.length === 0 ) {
				progressText.textContent = 'Geen actieve sites gevonden.';
				setTimeout( function () { progressText.textContent = ''; }, 4000 );
				return;
			}

			refreshAllBtn.disabled = true;
			var done  = 0;
			var total = activeSiteIds.length;
			progressText.textContent = '0 / ' + total + ' vernieuwd…';

			function refreshNext( index ) {
				if ( index >= activeSiteIds.length ) {
					progressText.textContent = total + ' / ' + total + ' vernieuwd ✓';
					refreshAllBtn.disabled   = false;
					setTimeout( function () { progressText.textContent = ''; }, 6000 );
					return;
				}

				var siteId   = activeSiteIds[ index ];
				var row      = document.querySelector( 'tr[data-site-id="' + siteId + '"]' );
				var feedback = row ? row.querySelector( '.spd-action-feedback' ) : null;

				if ( feedback ) feedback.textContent = '⏳';

				post( 'spd_refresh_site', { site_id: siteId } )
					.then( function ( data ) {
						done++;
						progressText.textContent = done + ' / ' + total + ' vernieuwd…';
						if ( feedback ) {
							feedback.textContent = data.success ? '✓' : '✗';
							if ( data.success && row ) {
								row.querySelector( '.spd-last-updated' ).textContent = data.data.last_updated;
							}
							setTimeout( function () {
								if ( feedback ) feedback.textContent = '';
							}, 4000 );
						}
					} )
					.catch( function () {
						done++;
						progressText.textContent = done + ' / ' + total + ' vernieuwd…';
						if ( feedback ) {
							feedback.textContent = '✗';
							setTimeout( function () {
								if ( feedback ) feedback.textContent = '';
							}, 4000 );
						}
					} )
					.finally( function () {
						refreshNext( index + 1 );
					} );
			}

			refreshNext( 0 );
		} );
	}
} )();
