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

	function replaceFlag( source ) {
		var box = document.querySelector( '.verdix-migrate-replace[data-source="' + source + '"]' );
		return box && box.checked ? '1' : '';
	}

	document.querySelectorAll( '.verdix-stop-schema' ).forEach( function( box ) {
		box.addEventListener( 'change', function() {
			var data = new FormData();
			data.append( 'action', 'verdix_migration_stop_schema' );
			data.append( 'nonce', nonce );
			data.append( 'source', box.getAttribute( 'data-source' ) );
			data.append( 'enabled', box.checked ? '1' : '' );
			box.disabled = true;
			fetch( ajaxUrl, { method: 'POST', body: data } )
				.then( function( r ) {
					return r.json();
				} )
				.then( function( resp ) {
					if ( ! resp.success ) {
						box.checked = ! box.checked;
					}
				} )
				.catch( function() {
					box.checked = ! box.checked;
				} )
				.then( function() {
					box.disabled = false;
				} );
		} );
	} );

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
			data.append( 'replace', replaceFlag( source ) );

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

	// Bulk migration: fetch the ids, then migrate them in batches so large sites don't time out.
	function post( action, fields ) {
		var data = new FormData();
		data.append( 'action', action );
		data.append( 'nonce', nonce );
		Object.keys( fields ).forEach( function( key ) {
			[].concat( fields[ key ] ).forEach( function( value ) {
				data.append( Array.isArray( fields[ key ] ) ? key + '[]' : key, value );
			} );
		} );
		return fetch( ajaxUrl, { method: 'POST', body: data } ).then( function( r ) {
			return r.json();
		} );
	}

	document.querySelectorAll( '.verdix-migrate-all-btn' ).forEach( function( button ) {
		button.addEventListener( 'click', function() {
			var source = this.getAttribute( 'data-source' ) || 'wp_review_pro';
			var replace = replaceFlag( source );
			var confirmText = replace ? i18n.confirmReplace : i18n.confirmAll;
			if ( ! window.confirm( confirmText || 'Migrate all posts?' ) ) {
				return;
			}

			var statusDiv = document.getElementById( 'verdix-migration-status' );
			var msgEl = document.getElementById( 'verdix-migration-message' );
			var migrated = 0;
			var errors = [];

			function show( cls, text ) {
				statusDiv.style.display = 'block';
				statusDiv.className = 'notice ' + cls;
				msgEl.textContent = text;
			}

			function fail( text ) {
				show( 'notice-error', text );
				button.disabled = false;
				button.textContent = i18n.retry || 'Retry migration';
			}

			button.disabled = true;
			button.textContent = i18n.migrating || 'Migrating...';

			post( 'verdix_migration_ids', { source: source } ).then( function( resp ) {
				if ( ! resp.success ) {
					fail( resp.data.message );
					return;
				}
				var ids = resp.data.ids;
				var total = ids.length;

				function next() {
					if ( ! ids.length ) {
						var text = ( i18n.done || '%s' ).replace( '%s', migrated );
						show( errors.length ? 'notice-warning' : 'notice-success', text + ( errors.length ? ' ' + errors.join( '; ' ) : '' ) );
						setTimeout( function() {
							window.location.reload();
						}, 2500 );
						return;
					}
					post( 'verdix_migrate_batch', { source: source, replace: replace, ids: ids.splice( 0, 20 ) } ).then( function( batch ) {
						if ( ! batch.success ) {
							fail( batch.data.message );
							return;
						}
						migrated += batch.data.migrated;
						errors = errors.concat( batch.data.errors );
						show( 'notice-info', ( i18n.progress || '%1$s / %2$s' ).replace( '%1$s', migrated ).replace( '%2$s', total ) );
						next();
					} ).catch( function() {
						fail( i18n.error || 'Error' );
					} );
				}

				next();
			} ).catch( function() {
				fail( i18n.error || 'Error' );
			} );
		} );
	} );
} )();
