( function() {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;

	var config = window.verdixListConfig || { schemaTypes: [] };

	wp.blocks.registerBlockType( 'verdix/review-list', {
		edit: function( props ) {
			var attributes = props.attributes;
			var set = props.setAttributes;

			var typeOptions = [ { label: __( 'Any type', 'verdix-reviews' ), value: '' } ].concat( config.schemaTypes );

			return el( 'div', useBlockProps(),
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'List settings', 'verdix-reviews' ) },
						el( SelectControl, {
							label: __( 'Show', 'verdix-reviews' ),
							value: attributes.type,
							options: [
								{ label: __( 'Top rated', 'verdix-reviews' ), value: 'top-rated' },
								{ label: __( 'Latest reviews', 'verdix-reviews' ), value: 'latest' }
							],
							onChange: function( val ) { set( { type: val } ); }
						} ),
						el( RangeControl, {
							label: __( 'Number of reviews', 'verdix-reviews' ),
							value: attributes.count,
							min: 1,
							max: 20,
							onChange: function( val ) { set( { count: val } ); }
						} ),
						el( SelectControl, {
							label: __( 'Schema type', 'verdix-reviews' ),
							value: attributes.schema_type,
							options: typeOptions,
							onChange: function( val ) { set( { schema_type: val } ); }
						} ),
						el( TextControl, {
							label: __( 'Category slug', 'verdix-reviews' ),
							value: attributes.category,
							onChange: function( val ) { set( { category: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show featured image', 'verdix-reviews' ),
							checked: attributes.image,
							onChange: function( val ) { set( { image: val } ); }
						} )
					)
				),
				el( ServerSideRender, { block: 'verdix/review-list', attributes: attributes } )
			);
		},
		save: function() { return null; }
	} );
} )();
