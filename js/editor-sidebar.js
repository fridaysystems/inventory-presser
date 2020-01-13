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
		switch( meta_key.substring( 18 ) )
		{
			case 'year':
				return 'Year';

			case 'stock_number':
				return 'Stock Number';

			case 'vin':
				return 'VIN';
		}
		return meta_key;
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
		return el( Select, {
			label: getLabel( props.fieldName ),
			value: props.metaFieldValue,
			options: yearOptions(),
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
					title: 'Inventory Presser',
				},
				el( 'div',
					{ className: 'invp-editor-sidebar' },
					el( MetaBlockField,
						{ fieldName: 'inventory_presser_vin' }
					),
					el( MetaBlockField,
						{ fieldName: 'inventory_presser_stock_number' }
					),
					el( MetaBlockFieldSelect,
						{ fieldName: 'inventory_presser_year' }
					)
				),
			);
		}
	} );
} )( window.wp );