( function( wp ) {
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar  = wp.editPost.PluginSidebar;
	var el             = wp.element.createElement;
	var Text           = wp.components.TextControl;
	var Select         = wp.components.SelectControl;
	var Checkbox       = wp.components.CheckboxControl;
	var withSelect     = wp.data.withSelect;
	var withDispatch   = wp.data.withDispatch;
	var compose        = wp.compose.compose;
	var metaPrefix     = 'inventory_presser_'; // TODO localize this so it's dynamic.

	function getLabel( meta_key ) {
		if ( metaPrefix !== meta_key.substr(0, metaPrefix.length ) ) {
			return meta_key;
		}

		var key = meta_key.substring( metaPrefix.length );
		switch ( key ) {
			case 'msrp':
			case 'vin':
				return key.toUpperCase();

			case 'odometer':
				return 'Odometer (' + invp.miles_word + ')';

			case 'youtube':
				return 'YouTube Video ID';

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
		return (str + '').split( ' ' ).map( x => x.charAt( 0 ).toUpperCase() + x.substr( 1 ) ).join( ' ' );
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
							fieldName: 'inventory_presser_vin',
							id:        'inventory_presser_vin',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_stock_number',
							id:        'inventory_presser_stock_number',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_trim',
							id:        'inventory_presser_trim',
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_engine',
							id:        'inventory_presser_engine',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_doors',
							id:        'inventory_presser_doors',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_color',
							id:        'inventory_presser_color',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_interior_color',
							id:        'inventory_presser_interior_color',
							isNumeric: false,
						}
					),
					el(
						MetaBlockDigitsField,
						{
							fieldName: 'inventory_presser_odometer',
							id:        'inventory_presser_odometer',
							isNumeric: false,
						}
					),
					el(
						MetaBlockFieldSelect,
						{
							fieldName: 'inventory_presser_title_status',
							id:        'inventory_presser_title_status',
							isNumeric: false,
							optionArray: titleStatusOptions(),
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_car_id',
							id:        'inventory_presser_car_id',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_dealer_id',
							id:        'inventory_presser_dealer_id',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_leads_id',
							id:        'inventory_presser_leads_id',
							isNumeric: true,
						}
					),
					el(
						MetaBlockCheckboxField,
						{
							fieldName: 'inventory_presser_wholesale',
							id:        'inventory_presser_wholesale',
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
							fieldName: 'inventory_presser_price',
							id:        'inventory_presser_price',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_msrp',
							id:        'inventory_presser_msrp',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_down_payment',
							id:        'inventory_presser_down_payment',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_payment',
							id:        'inventory_presser_payment',
							isNumeric: true,
						}
					),
					el(
						MetaBlockFieldSelect,
						{
							fieldName: 'inventory_presser_payment_frequency',
							id:        'inventory_presser_payment_frequency',
							isNumeric: false,
							optionArray: paymentFrequencyOptions(),
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_book_value_kbb',
							id:        'inventory_presser_book_value_kbb',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_book_value_nada',
							id:        'inventory_presser_book_value_nada',
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
							fieldName: 'inventory_presser_edmunds_style_id',
							id:        'inventory_presser_edmunds_style_id',
							isNumeric: true,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_nextgear_inspection_url',
							id:        'inventory_presser_nextgear_inspection_url',
							isNumeric: false,
						}
					),
					el(
						MetaBlockField,
						{
							fieldName: 'inventory_presser_youtube',
							id:        'inventory_presser_youtube',
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
									fieldName: 'inventory_presser_beam',
									id:        'inventory_presser_beam',
									isNumeric: true,
								}
							),
							el(
								MetaBlockField,
								{
									fieldName: 'inventory_presser_length',
									id:        'inventory_presser_length',
									isNumeric: true,
								}
							),
							el(
								MetaBlockFieldSelect,
								{
									fieldName: 'inventory_presser_hull_material',
									id:        'inventory_presser_hull_material',
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
