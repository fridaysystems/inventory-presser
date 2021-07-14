( function( wp ) {
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar = wp.editPost.PluginSidebar;
	var el = wp.element.createElement;
	var Text = wp.components.TextControl;
	var Select = wp.components.SelectControl;
	var withSelect = wp.data.withSelect;
	var withDispatch = wp.data.withDispatch;
	var compose = wp.compose.compose;

	function getLabel( meta_key )
	{
		if( 19 > meta_key.length )
		{
			return meta_key;
		}

		var key = meta_key.substring( 18 );
		switch( key )
		{
			case 'book_value_kbb':
				return 'Book Value KBB';
			case 'book_value_nada':
				return 'Book Value NADA';

			case 'msrp':
			case 'vin':
				return key.toUpperCase();

			case 'odometer':
				return 'Odometer (' + invp.miles_word + ')';

			case 'youtube':
				return 'YouTube Video ID';

			default:
				//Replace underscores with spaces and capitalize the first letter
				const pattern = /_/g;
				return ucwords( key.replace( pattern, ' ' ) );
		}
	}

	function hullMaterialOptions()
	{
		var options = [{label:'',value:''}];
		for( var l=0; l<invp.hull_materials.length; l++ )
		{
			options.push({
				label: invp.hull_materials[l],
				value: invp.hull_materials[l].toLowerCase().replace( ' ', '-' )
			});
		}
		return options;
	}

	function paymentFrequencyOptions()
	{
		var options = [{label:'',value:''}];
		for( var label in invp.payment_frequencies )
		{
			options.push({
				label: label,
				value: invp.payment_frequencies[label]
			});
		}
		return options;
	}

	function yearOptions()
	{
		var options = [];
		for(var y=new Date().getFullYear(); y>1919; y-- )
		{
			options.push({
				label: y,
				value: y
			});
		}
		return options;
	}

	function findTermID( name, taxonomy )
	{
		let terms = new wp.api.collections[ucFirst( taxonomy )];
		terms.fetch().done( function( t ){
			let target = t.find( x => name === x.name );
			if( ! target ) { return; }
			console.log( target.id );
			return target.id;
		});
	}

	function ucFirst( str )
	{
		return (str + '').charAt(0).toUpperCase() + str.substr(1);
	}

	function ucwords( str )
	{
		return (str + '').split(' ').map( x => x.charAt(0).toUpperCase() + x.substr(1) ).join(' ');
	}

	var MetaBlockField = compose(
		withDispatch( function( dispatch, props ) {
			return {
				setMetaFieldValue: function( value ) {
					dispatch( 'core/editor' ).editPost(
						{ meta: { [ props.fieldName ]: value } }
					);
				}
			}
		} ),
		withSelect( function( select, props ) {
			return {
				metaFieldValue: select( 'core/editor' )
					.getEditedPostAttribute( 'meta' )
					[ props.fieldName ],
			}
		} )
	)( function( props ) {
		return el( Text, {
			label: getLabel( props.fieldName ),
			value: props.metaFieldValue,
			id: props.id,
			onChange: function( content ) {
				props.setMetaFieldValue( content );
			},
		} );
	} );

	//Drop down for model year
	var MetaBlockFieldSelect = compose(
		withDispatch( function( dispatch, props ) {
			return {
				setMetaFieldValue: function( value ) {
					dispatch( 'core/editor' ).editPost(
						{ meta: { [ props.fieldName ]: value },
						 model_year: findTermID( value, 'model_year' ) }
					);
				}
			}
		} ),
		withSelect( function( select, props ) {
			return {
				metaFieldValue: select( 'core/editor' )
					.getEditedPostAttribute( 'meta' )
					[ props.fieldName ],
			}
		} )
	)( function( props ) {
		return el( Select, {
			label: getLabel( props.fieldName ),
			value: props.metaFieldValue,
			options: props.optionArray,
			onChange: function( content ) {
				props.setMetaFieldValue( content );
			},
		} );
	} );

	registerPlugin( 'invp-plugin-sidebar', {
		render: function() {
			return el( PluginSidebar,
				{
					name: 'invp-plugin-sidebar',
					icon: 'admin-network',
					title: 'Inventory Presser'
				},
				el( 'div',
					{ className: 'invp-editor-sidebar' },
					el(
						'h2',
						{},
						'Attributes'
					),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_vin',
						id:        'inventory_presser_vin'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_stock_number',
						id:        'inventory_presser_stock_number'
					} ),
					el( MetaBlockFieldSelect, {
						fieldName: 'inventory_presser_year',
						id:        'inventory_presser_year',
						optionArray: yearOptions(),
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_make',
						id:        'inventory_presser_make',
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_model',
						id:        'inventory_presser_model'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_trim',
						id:        'inventory_presser_trim',
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_engine',
						id:        'inventory_presser_engine'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_body_style',
						id:        'inventory_presser_body_style',
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_doors',
						id:        'inventory_presser_doors',
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_color',
						id:        'inventory_presser_color'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_interior_color',
						id:        'inventory_presser_interior_color'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_odometer',
						id:        'inventory_presser_odometer'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_youtube',
						id:        'inventory_presser_youtube'
					} ),
					el(
						'h2',
						{},
						'Prices'
					),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_price',
						id:        'inventory_presser_price'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_msrp',
						id:        'inventory_presser_msrp'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_down_payment',
						id:        'inventory_presser_down_payment'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_payment',
						id:        'inventory_presser_payment'
					} ),
					el( MetaBlockFieldSelect, {
						fieldName: 'inventory_presser_payment_frequency',
						id:        'inventory_presser_payment_frequency',
						optionArray: paymentFrequencyOptions()
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_book_value_kbb',
						id:        'inventory_presser_book_value_kbb'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_book_value_nada',
						id:        'inventory_presser_book_value_nada'
					} ),
					el(
						'h2',
						{},
						'Boat Attributes'
					),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_beam',
						id:        'inventory_presser_beam'
					} ),
					el( MetaBlockField, {
						fieldName: 'inventory_presser_length',
						id:        'inventory_presser_length'
					} ),
					el( MetaBlockFieldSelect, {
						fieldName: 'inventory_presser_hull_material',
						id:        'inventory_presser_hull_material',
						optionArray: hullMaterialOptions()
					} ),
				),
			);
		}
	} );
} )( window.wp );