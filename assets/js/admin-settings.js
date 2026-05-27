/**
 * Admin settings page JS — color picker + live preview refresh.
 *
 * @package Verdix Reviews
 */

( function( $ ) {
	'use strict';

	var refreshTimer;

	function scheduleRefresh() {
		clearTimeout( refreshTimer );
		refreshTimer = setTimeout( refreshPreview, 150 );
	}

	function refreshPreview() {
		var $container = $( '#verdix-settings-preview' );
		if ( ! $container.length || typeof verdixPreview === 'undefined' ) {
			return;
		}

		$container.css( 'opacity', 0.6 );

		$.post( verdixPreview.ajaxUrl, {
			action:       'verdix_render_preview',
			nonce:        verdixPreview.nonce,
			style:        $( '#verdix_default_style' ).val() || 'default',
			accent_color: $( '#verdix_accent_color' ).val(),
			bg_color:     $( '#verdix_bg_color' ).val(),
			border_color: $( '#verdix_border_color' ).val()
		} ).done( function( resp ) {
			if ( resp && resp.success && resp.data && resp.data.html ) {
				$container.html( resp.data.html );
			}
		} ).always( function() {
			$container.css( 'opacity', 1 );
		} );
	}

	$( function() {
		$( '.verdix-color-picker' ).wpColorPicker( {
			change: scheduleRefresh,
			clear:  scheduleRefresh
		} );

		$( '#verdix_default_style' ).on( 'change', refreshPreview );
	} );
} )( jQuery );
