// Hide taxonomy meta boxes in the Block Editor.
wp.api.loadPromise.done( function() {
	var post = new wp.api.models.Inventory( { id: wp.media.view.settings.post.id } );
	post.fetch().then( ( post ) => {
		var inventoryTypes = new wp.api.collections.Inventory_type();
		inventoryTypes.fetch().then( ( types ) => {
			types.forEach( ( type ) => {
				if ( -1 !== post.inventory_type.indexOf( type.id ) ) {
					invp_block_editor_hide_taxonomies( type.slug );
				}
			} );
		} );
	} );
} );
function invp_block_editor_hide_taxonomies( typeSlug ) {
	for ( var taxonomy in invp.taxonomies ) {
		// If the user has disabled this taxonomy, remove its meta box.
		if ( invp.taxonomies[taxonomy].active === false ) {
			wp.data.dispatch( 'core/editor').removeEditorPanel( 'taxonomy-panel-' + taxonomy.replace( '-', '_' ) );
			continue;
		}

		// If this taxonomy is not active for this vehicle type, remove its meta box.
		if ( 'undefined' === typeof invp.taxonomies[taxonomy][typeSlug]
			|| false === invp.taxonomies[taxonomy][typeSlug] ) {

			wp.data.dispatch( 'core/editor').removeEditorPanel( 'taxonomy-panel-' + taxonomy.replace( '-', '_' ) );
		}
	}
}

/**
 * Editor sidebar spin up.
 */
( function( wp ) {
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar  = wp.editor.PluginSidebar;
	var el             = wp.element.createElement;
	var Text           = wp.components.TextControl;
	var Select         = wp.components.SelectControl;
	var Checkbox       = wp.components.CheckboxControl;
	var withSelect     = wp.data.withSelect;
	var withDispatch   = wp.data.withDispatch;
	var compose        = wp.compose.compose;
	const { __ } = wp.i18n;

	function getLabel( meta_key ) {
		if ( invp.meta_prefix !== meta_key.substr(0, invp.meta_prefix.length ) ) {
			return meta_key;
		}

		var key = meta_key.substring( invp.meta_prefix.length );
		switch ( key ) {
			case 'msrp':
			case 'vin':
				return key.toUpperCase();

			case 'odometer':
				return invp.odometer_label + ' (' + invp.odometer_units + ')';

			case 'youtube':
				return __( 'YouTube Video ID', 'inventory-presser' );

			default:
				/**
				 * Replace underscores with spaces, capitalize the first letter,
				 * and capitalize ID, KBB, NADA, and URL.
				 */
				const pattern = /_/g;
				return ucwords( key.replace( pattern, ' ' ) )
					.replace( 'Id', 'ID' )
					.replace( 'Kbb', 'KBB' )
					.replace( 'Nada', 'NADA' )
					.replace( 'Url', 'URL' );

		}
	}

	function hullMaterialOptions()
	{
		var options = [{label:'',value:''}];
		for ( var l = 0; l < invp.hull_materials.length; l++ ) {
			options.push(
				{
					label: invp.hull_materials[l],
					value: invp.hull_materials[l].toLowerCase().replace( ' ', '-' )
				}
			);
		}
		return options;
	}

	function paymentFrequencyOptions()
	{
		var options = [{label:'',value:''}];
		for ( var label in invp.payment_frequencies ) {
			options.push(
				{
					label: label,
					value: invp.payment_frequencies[label]
				}
			);
		}
		return options;
	}

	function titleStatusOptions()
	{
		var options = [{label:'',value:''}];
		for ( var l = 0; l < invp.title_statuses.length; l++ ) {
			options.push(
				{
					label: invp.title_statuses[l],
					value: invp.title_statuses[l]
				}
			);
		}
		return options;
	}

	function ucwords( str )
	{
		return (str + '').split( ' ' ).map( x => x.charAt( 0 ).toUpperCase() + x.substring( 1 ) ).join( ' ' );
	}

	// Most text fields.
	var MetaBlockField = compose(
		withDispatch(
			function( dispatch, props ) {
				return {
					setMetaFieldValue: function( value ) {
						// Is this a numeric meta field? Can't save an empty string in those.
						if ( props.isNumeric && '' === value ) {
							value = '0';
						}
						dispatch( 'core/editor' ).editPost(
							{ meta: { [ props.fieldName ]: value } }
						);
					}
				}
			}
		),
		withSelect(
			function( select, props ) {
				return {
					metaFieldValue: select( 'core/editor' )
					 .getEditedPostAttribute( 'meta' )
					 [ props.fieldName ],
				}
			}
		)
	)(
		function( props ) {
			return el(
				Text,
				{
					label: getLabel( props.fieldName ),
					value: props.metaFieldValue,
					id: props.id,
					onChange: function( content ) {
						 props.setMetaFieldValue( content );
					},
				}
			);
		}
	);

	// Text field that allows only digits.
	var MetaBlockDigitsField = compose(
		withDispatch(
			function( dispatch, props ) {
				return {
					setMetaFieldValue: function( value ) {
						dispatch( 'core/editor' ).editPost(
							{ meta: { [ props.fieldName ]: value } }
						);
					}
				}
			}
		),
		withSelect(
			function( select, props ) {
				return {
					metaFieldValue: select( 'core/editor' )
					 .getEditedPostAttribute( 'meta' )
					 [ props.fieldName ],
				}
			}
		)
	)(
		function( props ) {
			return el(
				Text,
				{
					label: getLabel( props.fieldName ),
					value: props.metaFieldValue,
					id: props.id,
					onChange: function( content ) {
						 props.setMetaFieldValue( content.replace( /[^0-9]+/g, '' ) );
					},
				}
			);
		}
	);

	// Drop down
	var MetaBlockFieldSelect = compose(
		withDispatch(
			function( dispatch, props ) {
				return {
					setMetaFieldValue: function( value ) {
						dispatch( 'core/editor' ).editPost(
							{ meta: { [ props.fieldName ]: value } }
						);
					}
				}
			}
		),
		withSelect(
			function( select, props ) {
				return {
					metaFieldValue: select( 'core/editor' )
					 .getEditedPostAttribute( 'meta' )
					 [ props.fieldName ],
				}
			}
		)
	)(
		function( props ) {
			return el(
				Select,
				{
					label: getLabel( props.fieldName ),
					value: props.metaFieldValue,
					options: props.optionArray,
					onChange: function( content ) {
						 props.setMetaFieldValue( content );
					},
				}
			);
		}
	);

	// Boolean fields are checkboxes.
	var MetaBlockCheckboxField = compose(
		withDispatch(
			function( dispatch, props ) {
				return {
					setMetaFieldValue: function( value ) {
						dispatch( 'core/editor' ).editPost(
							{ meta: { [ props.fieldName ]: value } }
						);
					}
				}
			}
		),
		withSelect(
			function( select, props ) {
				return {
					metaFieldValue: select( 'core/editor' )
					 .getEditedPostAttribute( 'meta' )
					 [ props.fieldName ],
				}
			}
		)
	)(
		function( props ) {
			return el(
				Checkbox,
				{
					label: getLabel( props.fieldName ),
					checked: props.metaFieldValue,
					onChange: function( value ) {
						props.setMetaFieldValue( value );
					},
				}
			);
		}
	);

	registerPlugin(
		'invp-plugin-sidebar',
		{
			render: function() {
				var fields = el(
					wp.element.Fragment,
					{},
					el(
						'h2',
						{},
						'Attributes'
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'vin',
							id:        invp.meta_prefix + 'vin',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'stock_number',
							id:        invp.meta_prefix + 'stock_number',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'trim',
							id:        invp.meta_prefix + 'trim',
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'engine',
							id:        invp.meta_prefix + 'engine',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'doors',
							id:        invp.meta_prefix + 'doors',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'color',
							id:        invp.meta_prefix + 'color',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'interior_color',
							id:        invp.meta_prefix + 'interior_color',
							isNumeric: false,
						}
					),
					el(
						MetaBlockDigitsField,
						{
							fieldName: invp.meta_prefix + 'odometer',
							id:        invp.meta_prefix + 'odometer',
							isNumeric: false,
						}
					),
					el(
						MetaBlockFieldSelect,
						{
							fieldName: invp.meta_prefix + 'title_status',
							id:        invp.meta_prefix + 'title_status',
							isNumeric: false,
							optionArray: titleStatusOptions(),
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'car_id',
							id:        invp.meta_prefix + 'car_id',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'dealer_id',
							id:        invp.meta_prefix + 'dealer_id',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'leads_id',
							id:        invp.meta_prefix + 'leads_id',
							isNumeric: true,
						}
					),
					el(
						MetaBlockCheckboxField,
						{
							fieldName: invp.meta_prefix + 'wholesale',
							id:        invp.meta_prefix + 'wholesale',
							isNumeric: false,
						}
					),
					el(
						'h2',
						{},
						'Prices'
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'price',
							id:        invp.meta_prefix + 'price',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'msrp',
							id:        invp.meta_prefix + 'msrp',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'down_payment',
							id:        invp.meta_prefix + 'down_payment',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'payment',
							id:        invp.meta_prefix + 'payment',
							isNumeric: true,
						}
					),
					el(
						MetaBlockFieldSelect,
						{
							fieldName: invp.meta_prefix + 'payment_frequency',
							id:        invp.meta_prefix + 'payment_frequency',
							isNumeric: false,
							optionArray: paymentFrequencyOptions(),
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'book_value_kbb',
							id:        invp.meta_prefix + 'book_value_kbb',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'book_value_nada',
							id:        invp.meta_prefix + 'book_value_nada',
							isNumeric: true,
						}
					),
					el(
						'h2',
						{},
						'Third Parties'
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'edmunds_style_id',
							id:        invp.meta_prefix + 'edmunds_style_id',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'nextgear_inspection_url',
							id:        invp.meta_prefix + 'nextgear_inspection_url',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: invp.meta_prefix + 'youtube',
							id:        invp.meta_prefix + 'youtube',
							isNumeric: false,
						}
					),
				);
				// Is this a boat?
				const inventory_presser_type = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'meta' ).inventory_presser_type;
				if ( 'boat' === inventory_presser_type.toLowerCase() ) {
					// Yes, add the boat fields.
					fields = el(
						wp.element.Fragment,
						{},
						fields,
						el(
							wp.element.Fragment,
							{},
							el(
								'h2',
								{},
								'Boat Attributes'
							),
							el(
								MetaBlockField,
								{
									fieldName: invp.meta_prefix + 'beam',
									id:        invp.meta_prefix + 'beam',
									isNumeric: true,
								}
							),
							el(
								MetaBlockField,
								{
									fieldName: invp.meta_prefix + 'length',
									id:        invp.meta_prefix + 'length',
									isNumeric: true,
								}
							),
							el(
								MetaBlockFieldSelect,
								{
									fieldName: invp.meta_prefix + 'hull_material',
									id:        invp.meta_prefix + 'hull_material',
									isNumeric: false,
									optionArray: hullMaterialOptions(),
								}
							),
						),
					);
				}

				// Allow add-ons to change the sidebar fields.
				fields = wp.hooks.applyFilters( 'invp_editor_sidebar_elements', fields, inventory_presser_type );
				return el(
					PluginSidebar,
					{
						name: 'invp-plugin-sidebar',
						icon: 'admin-network',
						title: 'Inventory Presser'
					},
					el(
						'div',
						{ className: 'invp-editor-sidebar' },
						fields
					),
				);
			}
		}
	);
} )( window.wp );
