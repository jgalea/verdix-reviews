/**
 * Migration page JavaScript.
 *
 * Handles single-post and bulk migration AJAX requests.
 * Supports multiple migration sources (wp_review_pro, wp_product_review, legacy).
 *
 * @package Verdix Reviews
 */

( function() {
	'use strict';

	var config = window.verdixMigration || {};
	var nonce = config.nonce || '';
	var ajaxUrl = config.ajaxUrl || '';
	var i18n = config.i18n || {};

	function setStatus( cell, text, color ) {
		cell.textContent = '';
		var span = document.createElement( 'span' );
		span.style.color = color;
		span.textContent = text;
		cell.appendChild( span );
	}

	// Single post migration.
	document.querySelectorAll( '.verdix-migrate-single' ).forEach( function( btn ) {
		btn.addEventListener( 'click', function() {
			var postId = this.getAttribute( 'data-post-id' );
			var source = this.getAttribute( 'data-source' ) || 'wp_review_pro';
			var row = document.getElementById( 'verdix-migrate-row-' + postId );
			var statusCell = row.querySelector( '.verdix-migrate-status' );

			this.disabled = true;
			setStatus( statusCell, i18n.migrating || 'Migrating...', '#856404' );

			var data = new FormData();
			data.append( 'action', 'verdix_migrate' );
			data.append( 'nonce', nonce );
			data.append( 'post_id', postId );
			data.append( 'source', source );

			fetch( ajaxUrl, { method: 'POST', body: data } )
				.then( function( r ) {
					return r.json();
				} )
				.then( function( resp ) {
					if ( resp.success ) {
						setStatus( statusCell, i18n.migrated || 'Migrated', '#28a745' );
						btn.remove();
					} else {
						setStatus( statusCell, resp.data.message, '#dc3545' );
						btn.disabled = false;
					}
				} )
				.catch( function() {
					setStatus( statusCell, i18n.error || 'Error', '#dc3545' );
					btn.disabled = false;
				} );
		} );
	} );

	// Bulk migration — one handler for all "Migrate All" buttons.
	document.querySelectorAll( '.verdix-migrate-all-btn' ).forEach( function( migrateAllBtn ) {
		migrateAllBtn.addEventListener( 'click', function() {
			if ( ! confirm( i18n.confirmAll || 'Migrate all posts? This cannot be undone.' ) ) {
				return;
			}

			var source = this.getAttribute( 'data-source' ) || 'wp_review_pro';

			this.disabled = true;
			this.textContent = i18n.migrating || 'Migrating...';

			var data = new FormData();
			data.append( 'action', 'verdix_migrate_all' );
			data.append( 'nonce', nonce );
			data.append( 'source', source );

			var currentBtn = this;

			fetch( ajaxUrl, { method: 'POST', body: data } )
				.then( function( r ) {
					return r.json();
				} )
				.then( function( resp ) {
					var statusDiv = document.getElementById( 'verdix-migration-status' );
					var msgEl = document.getElementById( 'verdix-migration-message' );

					statusDiv.style.display = 'block';

					if ( resp.success ) {
						statusDiv.className = 'notice notice-success';
						var msg = resp.data.message;
						if ( resp.data.errors && resp.data.errors.length > 0 ) {
							msg += ' Errors: ' + resp.data.errors.join( '; ' );
						}
						msgEl.textContent = msg;
						// Reload to refresh statuses.
						setTimeout( function() {
							window.location.reload();
						}, 1500 );
					} else {
						statusDiv.className = 'notice notice-error';
						msgEl.textContent = resp.data.message;
						currentBtn.disabled = false;
						currentBtn.textContent = i18n.retry || 'Retry Migration';
					}
				} )
				.catch( function() {
					currentBtn.disabled = false;
					currentBtn.textContent = i18n.retry || 'Retry Migration';
				} );
		} );
	} );
} )();
