( function() {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useEntityProp = wp.coreData.useEntityProp;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var Button = wp.components.Button;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;

	var STAR_PATH = 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z';

	var CONFIG = window.verdixEditorConfig || {
		ratingTypes: [ { value: 'star', label: 'Star (0-5)', max: 5, step: 0.5, format: '%s/5' } ],
		styles: [ { value: 'default', label: 'Default' } ],
		schemaTypes: [],
		typeFields: {}
	};

	var DEFAULTS = window.verdixDefaults || {
		rating: 0, rating_type: 'star', style: '', position: '', heading: '', summary: '', pros: [], cons: [],
		schema_type: 'Product', product_name: '', price: '', currency: 'USD',
		cta_text: '', cta_url: '', author_name: '', type_fields: {}
	};

	var starIdCounter = 0;

	function Star( props ) {
		var index = props.index;
		var rating = props.rating;
		var onRate = props.onRate;
		var filled = rating >= index + 1;
		var halfFilled = ! filled && rating >= index + 0.5;
		var idRef = useState( function() { return 'sb-star-grad-' + ( ++starIdCounter ); } );
		var gradientId = idRef[ 0 ];

		function handleClick( e ) {
			var rect = e.currentTarget.getBoundingClientRect();
			var x = e.clientX - rect.left;
			onRate( x < rect.width / 2 ? index + 0.5 : index + 1 );
		}

		var children = [];
		if ( halfFilled ) {
			children.push(
				el( 'defs', { key: 'defs' },
					el( 'linearGradient', { id: gradientId },
						el( 'stop', { offset: '50%', stopColor: 'var(--sb-star-color, #1e73be)' } ),
						el( 'stop', { offset: '50%', stopColor: '#d0d0d0' } )
					)
				)
			);
		}
		children.push(
			el( 'path', {
				key: 'path',
				d: STAR_PATH,
				fill: filled ? 'var(--sb-star-color, #1e73be)' : halfFilled ? 'url(#' + gradientId + ')' : '#d0d0d0'
			} )
		);

		return el( 'svg', {
			className: 'verdix-star-editor',
			width: 28, height: 28, viewBox: '0 0 24 24',
			onClick: handleClick,
			style: { cursor: 'pointer', display: 'inline-block' },
			role: 'button', tabIndex: 0,
			'aria-label': ( index + 1 ) + ' star',
			onKeyDown: function( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); onRate( index + 1 ); }
			}
		}, children );
	}

	function StarRating( props ) {
		var stars = [];
		for ( var i = 0; i < 5; i++ ) {
			stars.push( el( Star, { key: i, index: i, rating: props.value, onRate: props.onChange } ) );
		}
		stars.push( el( 'span', { key: 'val', className: 'verdix-star-rating__value' }, props.value + '/5' ) );
		return el( 'div', { className: 'verdix-star-rating', role: 'group', 'aria-label': 'Rating' }, stars );
	}

	function ListEditor( props ) {
		var items = props.items || [];
		var onChange = props.onChange;
		var placeholder = props.placeholder;

		function updateItem( index, value ) {
			var updated = items.slice();
			updated[ index ] = value;
			onChange( updated );
		}
		function removeItem( index ) {
			onChange( items.filter( function( _, i ) { return i !== index; } ) );
		}
		function addItem() {
			onChange( items.concat( [ '' ] ) );
		}

		var children = items.map( function( item, index ) {
			return el( 'div', { key: index, className: 'verdix-list-editor__item' },
				el( TextControl, {
					value: item,
					onChange: function( val ) { updateItem( index, val ); },
					placeholder: placeholder
				} ),
				el( Button, {
					icon: 'no-alt',
					label: __( 'Remove', 'verdix-reviews' ),
					onClick: function() { removeItem( index ); },
					isDestructive: true,
					size: 'small'
				} )
			);
		} );

		children.push(
			el( Button, {
				key: 'add',
				variant: 'secondary',
				onClick: addItem,
				size: 'small',
				className: 'verdix-list-editor__add'
			}, __( '+ Add', 'verdix-reviews' ) )
		);

		return el( 'div', { className: 'verdix-list-editor' }, children );
	}

	function RatingInput( props ) {
		var ratingType = props.ratingType;
		var value = props.value;
		var onChange = props.onChange;

		var typeConfig = null;
		for ( var i = 0; i < CONFIG.ratingTypes.length; i++ ) {
			if ( CONFIG.ratingTypes[ i ].value === ratingType ) {
				typeConfig = CONFIG.ratingTypes[ i ];
				break;
			}
		}
		if ( ! typeConfig ) {
			typeConfig = CONFIG.ratingTypes[ 0 ];
		}

		if ( typeConfig.value === 'star' ) {
			return el( 'div', { className: 'verdix-box-editor__rating' },
				el( StarRating, { value: value, onChange: onChange } ),
				el( RangeControl, {
					value: value,
					onChange: onChange,
					min: 0, max: typeConfig.max, step: typeConfig.step, withInputField: true
				} )
			);
		}

		return el( 'div', { className: 'verdix-box-editor__rating' },
			el( RangeControl, {
				label: typeConfig.label,
				value: value,
				onChange: onChange,
				min: 0, max: typeConfig.max, step: typeConfig.step, withInputField: true
			} )
		);
	}

	function Edit( props ) {
		var blockProps = useBlockProps();
		var context = props.context || {};
		var postType = context.postType || 'post';

		var entityProp = useEntityProp( 'postType', postType, 'meta' );
		var rawMeta = entityProp[ 0 ];
		var setMeta = entityProp[ 1 ];

		var rawReview = '';
		if ( rawMeta ) {
			rawReview = rawMeta._verdix_review || '';
		}

		function parseReview( raw ) {
			if ( ! raw ) return Object.assign( {}, DEFAULTS );
			try {
				return Object.assign( {}, DEFAULTS, JSON.parse( raw ) );
			} catch ( e ) {
				return Object.assign( {}, DEFAULTS );
			}
		}

		var stateHook = useState( function() { return parseReview( rawReview ); } );
		var review = stateHook[ 0 ];
		var setReviewState = stateHook[ 1 ];

		useEffect( function() {
			setReviewState( parseReview( rawReview ) );
		}, [ rawReview ] );

		function updateReview( changes ) {
			var updated = Object.assign( {}, review, changes );
			setReviewState( updated );
			var newMeta = Object.assign( {}, rawMeta, { _verdix_review: JSON.stringify( updated ) } );
			setMeta( newMeta );
		}

		var ratingTypeOptions = CONFIG.ratingTypes;
		var hasMultipleRatingTypes = ratingTypeOptions.length > 1;

		var inspectorPanels = [];

		var styleChildren = [];
		var styleOptions = [ { label: __( 'Use global default', 'verdix-reviews' ), value: '' } ].concat(
			CONFIG.styles.map( function( s ) {
				return { label: s.label, value: s.value };
			} )
		);

		if ( CONFIG.styles.length > 1 ) {
			styleChildren.push(
				el( SelectControl, {
					key: 'style-select',
					label: __( 'Review Box Style', 'verdix-reviews' ),
					value: review.style || '',
					options: styleOptions,
					onChange: function( val ) { updateReview( { style: val } ); }
				} )
			);
		} else {
			styleChildren.push(
				el( 'p', { key: 'style-label', style: { marginBottom: '8px' } },
					CONFIG.styles[ 0 ] ? CONFIG.styles[ 0 ].label : __( 'Default', 'verdix-reviews' )
				)
			);
		}

		inspectorPanels.push(
			el( PanelBody, { key: 'style', title: __( 'Style', 'verdix-reviews' ), initialOpen: false },
				styleChildren
			)
		);

		var ratingTypeChildren = [];
		if ( hasMultipleRatingTypes ) {
			ratingTypeChildren.push(
				el( SelectControl, {
					key: 'type-select',
					label: __( 'Rating Type', 'verdix-reviews' ),
					value: review.rating_type || 'star',
					options: ratingTypeOptions.map( function( t ) {
						return { label: t.label, value: t.value };
					} ),
					onChange: function( val ) {
						updateReview( { rating_type: val, rating: 0 } );
					}
				} )
			);
		} else {
			ratingTypeChildren.push(
				el( 'p', { key: 'type-label', style: { marginBottom: '8px' } },
					ratingTypeOptions[ 0 ] ? ratingTypeOptions[ 0 ].label : __( 'Star (0-5)', 'verdix-reviews' )
				)
			);
		}

		inspectorPanels.push(
			el( PanelBody, { key: 'rating-type', title: __( 'Rating Type', 'verdix-reviews' ), initialOpen: false },
				ratingTypeChildren
			)
		);

		inspectorPanels.push(
			el( PanelBody, { key: 'position', title: __( 'Position', 'verdix-reviews' ), initialOpen: false },
				el( SelectControl, {
					label: __( 'Review Box Position', 'verdix-reviews' ),
					value: review.position || '',
					options: [
						{ label: __( 'Use global default', 'verdix-reviews' ), value: '' },
						{ label: __( 'After content', 'verdix-reviews' ), value: 'bottom' },
						{ label: __( 'Before content', 'verdix-reviews' ), value: 'top' },
						{ label: __( 'Before and after content', 'verdix-reviews' ), value: 'both' },
						{ label: __( 'Manual (block/shortcode only)', 'verdix-reviews' ), value: 'manual' }
					],
					onChange: function( val ) { updateReview( { position: val } ); }
				} )
			)
		);

		var schemaTypeOptions = CONFIG.schemaTypes && CONFIG.schemaTypes.length
			? CONFIG.schemaTypes
			: [
				{ label: 'Product', value: 'Product' },
				{ label: 'Software Application', value: 'SoftwareApplication' },
				{ label: 'Thing', value: 'Thing' }
			];

		function updateTypeField( typeKey, fieldKey, value ) {
			var tf = Object.assign( {}, review.type_fields || {} );
			var typeObj = Object.assign( {}, tf[ typeKey ] || {} );
			if ( value === '' || value === null || value === undefined ) {
				delete typeObj[ fieldKey ];
			} else {
				typeObj[ fieldKey ] = value;
			}
			if ( Object.keys( typeObj ).length === 0 ) {
				delete tf[ typeKey ];
			} else {
				tf[ typeKey ] = typeObj;
			}
			updateReview( { type_fields: tf } );
		}

		var typeFieldDefs = ( CONFIG.typeFields && CONFIG.typeFields[ review.schema_type ] ) || [];
		var typeFieldControls = typeFieldDefs.map( function ( f ) {
			var currentType = review.type_fields && review.type_fields[ review.schema_type ];
			var val = currentType && currentType[ f.key ] !== undefined ? currentType[ f.key ] : '';
			var onChange = function ( v ) { updateTypeField( review.schema_type, f.key, v ); };
			if ( f.type === 'textarea' ) {
				return el( TextareaControl, { key: 'tf-' + f.key, label: f.label, value: val, onChange: onChange } );
			}
			return el( TextControl, {
				key:      'tf-' + f.key,
				label:    f.label,
				type:     ( f.type === 'number' || f.type === 'date' ) ? f.type : 'text',
				value:    val,
				onChange: onChange
			} );
		} );

		inspectorPanels.push(
			el( PanelBody, { key: 'schema', title: __( 'Schema Settings', 'verdix-reviews' ), initialOpen: false },
				el( SelectControl, {
					label: __( 'Schema Type', 'verdix-reviews' ),
					value: review.schema_type,
					options: schemaTypeOptions,
					onChange: function( val ) { updateReview( { schema_type: val } ); }
				} ),
				typeFieldControls,
				el( TextControl, {
					label: __( 'Product / Item Name', 'verdix-reviews' ),
					value: review.product_name,
					onChange: function( val ) { updateReview( { product_name: val } ); },
					placeholder: __( 'Defaults to post title', 'verdix-reviews' )
				} ),
				el( TextControl, {
					label: __( 'Price', 'verdix-reviews' ),
					value: review.price,
					onChange: function( val ) { updateReview( { price: val } ); },
					placeholder: '49.99'
				} ),
				el( SelectControl, {
					label: __( 'Currency', 'verdix-reviews' ),
					value: review.currency,
					options: [
						{ label: 'USD', value: 'USD' },
						{ label: 'EUR', value: 'EUR' },
						{ label: 'GBP', value: 'GBP' }
					],
					onChange: function( val ) { updateReview( { currency: val } ); }
				} ),
				el( TextControl, {
					label: __( 'Author Name Override', 'verdix-reviews' ),
					value: review.author_name,
					onChange: function( val ) { updateReview( { author_name: val } ); },
					placeholder: __( 'Uses default from settings', 'verdix-reviews' )
				} )
			)
		);

		inspectorPanels.push(
			el( PanelBody, { key: 'cta', title: __( 'CTA Button', 'verdix-reviews' ), initialOpen: false },
				el( TextControl, {
					label: __( 'Button Text', 'verdix-reviews' ),
					value: review.cta_text,
					onChange: function( val ) { updateReview( { cta_text: val } ); },
					placeholder: __( 'e.g., Visit Website', 'verdix-reviews' )
				} ),
				el( TextControl, {
					label: __( 'Button URL', 'verdix-reviews' ),
					value: review.cta_url,
					onChange: function( val ) { updateReview( { cta_url: val } ); },
					placeholder: 'https://',
					type: 'url'
				} )
			)
		);

		var inspector = el( InspectorControls, {}, inspectorPanels );

		var contentChildren = [
			el( TextControl, {
				key: 'heading',
				className: 'verdix-box-editor__heading',
				value: review.heading,
				onChange: function( val ) { updateReview( { heading: val } ); },
				placeholder: __( 'Review heading (e.g., Our Verdict)', 'verdix-reviews' )
			} )
		];

		var currentRatingType = review.rating_type || 'star';

		contentChildren.push(
			el( RatingInput, {
				key: 'rating',
				ratingType: currentRatingType,
				value: review.rating,
				onChange: function( val ) { updateReview( { rating: val } ); }
			} )
		);

		contentChildren.push(
			el( TextareaControl, {
				key: 'summary',
				className: 'verdix-box-editor__summary',
				value: review.summary,
				onChange: function( val ) { updateReview( { summary: val } ); },
				placeholder: __( 'Write a brief review summary...', 'verdix-reviews' ),
				rows: 3
			} ),
			el( 'div', { key: 'pros-cons', className: 'verdix-box-editor__pros-cons' },
				el( 'div', { className: 'verdix-box-editor__pros' },
					el( 'h4', { className: 'verdix-box-editor__list-heading verdix-box-editor__list-heading--pros' }, __( 'Pros', 'verdix-reviews' ) ),
					el( ListEditor, { items: review.pros, onChange: function( val ) { updateReview( { pros: val } ); }, placeholder: __( 'Add a pro...', 'verdix-reviews' ) } )
				),
				el( 'div', { className: 'verdix-box-editor__cons' },
					el( 'h4', { className: 'verdix-box-editor__list-heading verdix-box-editor__list-heading--cons' }, __( 'Cons', 'verdix-reviews' ) ),
					el( ListEditor, { items: review.cons, onChange: function( val ) { updateReview( { cons: val } ); }, placeholder: __( 'Add a con...', 'verdix-reviews' ) } )
				)
			)
		);

		if ( review.cta_text ) {
			contentChildren.push(
				el( 'div', { key: 'cta', className: 'verdix-box-editor__cta' },
					el( 'span', { className: 'verdix-box-editor__cta-button' }, review.cta_text )
				)
			);
		}

		var content = el( 'div', blockProps,
			el( 'div', { className: 'verdix-box-editor' }, contentChildren )
		);

		return el( Fragment, {}, inspector, content );
	}

	wp.blocks.registerBlockType( 'verdix/review-box', {
		edit: Edit,
		save: function() { return null; }
	} );
} )();
