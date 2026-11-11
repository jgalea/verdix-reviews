( function() {
	'use strict';

	function ready( fn ) {
		if ( document.readyState !== 'loading' ) { fn(); return; }
		document.addEventListener( 'DOMContentLoaded', fn );
	}

	function buildRow( field, container ) {
		var row = document.createElement( 'div' );
		row.className = 'verdix-repeater__row';

		var input = document.createElement( 'input' );
		input.type = 'text';
		input.name = 'verdix_review_' + field + '[]';
		input.className = 'regular-text';
		input.placeholder = container.getAttribute( 'data-placeholder' ) || '';
		input.setAttribute( 'aria-label', container.getAttribute( 'data-label' ) || '' );
		row.appendChild( input );

		var remove = document.createElement( 'button' );
		remove.type = 'button';
		remove.className = 'button verdix-repeater__remove';
		remove.setAttribute( 'aria-label', 'Remove' );
		remove.textContent = '×';
		row.appendChild( remove );

		return row;
	}

	function initSchemaTypeToggle() {
		var select = document.getElementById( 'verdix_review_schema_type' );
		if ( ! select ) { return; }

		var groups = document.querySelectorAll( '.verdix-type-fields' );
		var empty  = document.querySelector( '.verdix-type-fields-empty' );
		var note   = document.querySelector( '.verdix-schema-type-note' );

		select.addEventListener( 'change', function( e ) {
			var val   = e.target.value;
			if ( note ) {
				var message = select.options[ select.selectedIndex ].getAttribute( 'data-note' ) || '';
				note.textContent = message;
				note.style.display = message ? '' : 'none';
			}
			var found = false;
			groups.forEach( function( g ) {
				var match = g.getAttribute( 'data-type' ) === val;
				g.style.display = match ? '' : 'none';
				if ( match ) { found = true; }
			} );
			if ( empty ) { empty.style.display = found ? 'none' : ''; }
		} );
	}

	ready( function() {
		var box = document.querySelector( '.verdix-meta-box' );
		if ( ! box ) { return; }

		initSchemaTypeToggle();

		box.addEventListener( 'click', function( e ) {
			var target = e.target;

			if ( target.classList.contains( 'verdix-repeater__add' ) ) {
				e.preventDefault();
				var field = target.getAttribute( 'data-target' );
				var container = box.querySelector( '.verdix-repeater[data-field="' + field + '"]' );
				if ( container ) {
					var row = buildRow( field, container );
					container.appendChild( row );
					var firstInput = row.querySelector( 'input' );
					if ( firstInput ) { firstInput.focus(); }
				}
			}

			if ( target.classList.contains( 'verdix-offers__add' ) ) {
				e.preventDefault();
				var offers = box.querySelector( '.verdix-offers' );
				var template = document.getElementById( 'verdix-offer-template' );
				if ( offers && template ) {
					var index = parseInt( offers.getAttribute( 'data-next' ), 10 ) || 0;
					offers.setAttribute( 'data-next', index + 1 );
					var holder = document.createElement( 'div' );
					holder.innerHTML = template.innerHTML.replace( /__INDEX__/g, index );
					offers.appendChild( holder.firstElementChild );
				}
			}

			if ( target.classList.contains( 'verdix-offer__remove' ) ) {
				e.preventDefault();
				var offerRow = target.closest( '.verdix-offer' );
				if ( offerRow ) { offerRow.remove(); }
			}

			if ( target.classList.contains( 'verdix-repeater__remove' ) ) {
				e.preventDefault();
				var row = target.closest( '.verdix-repeater__row' );
				if ( row && row.parentElement ) {
					var siblings = row.parentElement.querySelectorAll( '.verdix-repeater__row' );
					if ( siblings.length > 1 ) {
						row.parentElement.removeChild( row );
					} else {
						row.querySelectorAll( 'input' ).forEach( function( inp ) {
							inp.value = inp.type === 'number' ? '0' : '';
						} );
					}
				}
			}
		} );
	} );
} )();
