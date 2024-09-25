/**
 * Vehicles > Options > Manage Taxonomies
 */
// Disable the other checkboxes when a whole taxonomy is disabled.
window.addEventListener(
	'load',
	function () {
		invp_page_settings.taxonomies.forEach(
			function (taxonomy) {
				var active = this.document.querySelector( 'input[type="checkbox"][name="inventory_presser[taxonomies][' + taxonomy + '][active]"]' );
				if ( null !== active ) {
						active.addEventListener(
							'change',
							function (ev) {
								// Toggle the rest of this taxonomy's settings.
								document.querySelectorAll( 'input[type="checkbox"][name^="inventory_presser[taxonomies][' + taxonomy + ']"' ).forEach(
									function (el) {
										// Is this the active?
										if (el.name === 'inventory_presser[taxonomies][' + taxonomy + '][active]') {
												return;
										}
										// Toggle the disabled attribute.
										el.disabled = ! ev.target.checked;
									}
								);
							}
						);
						// Trigger the change event to set the initial state.
						active.dispatchEvent( new Event( 'change' ) );
				}
			}
		);
	}
);

/**
 * Listings pages
 */
(function ($) {
	jQuery( document ).ready(
		function () {
			// add handler on the operator dropdowns so we can toggle the value text box.
			$( 'select.operator' ).on( 'change', toggle_value_input );
			// toggle the textboxes right now when the page is ready.
			$( 'select.operator' ).each( toggle_value_input );

			// Add a handler when the filter field is changed. We might have to enable or disable other inputs.
			$( 'select.filter-key' ).on( 'change', toggle_filter_inputs ).each( toggle_filter_inputs );

			// bind this button to add a new row to the table of additional listings pages.
			$( 'button#add_additional_listings_page' ).on(
				'click',
				function () {
					var row_html = $( '#additional_listings_pages_settings table.invp-settings tbody tr:last' )[0].outerHTML;
					$( '#additional_listings_pages_settings table.invp-settings tbody' ).append( repeat_row( row_html ) );
					// rebind the operator selects.
					$( 'select.operator' ).off( 'change' );
					$( 'select.operator' ).on( 'change', toggle_value_input );
					return false;
				}
			);

			// bind the delete buttons.
			$( 'button.delete-button' ).on( 'click', delete_listings_page );
		}
	);

	function delete_listings_page()
	{
		// id contains the iterator.
		var id       = $( this ).attr( 'id' );
		var pieces   = id.split( '_' );
		var iterator = pieces[pieces.length - 1];
		$( '#row_' + iterator ).remove();
	}

	function repeat_row( tr_html )
	{
		// find the iterator number.
		let re        = new RegExp( 'additional_listings_pages_slug_([0-9]+)' );
		var match_arr = re.exec( tr_html );
		var iterator  = parseInt( match_arr[match_arr.length - 1] );

		// replace it everywhere.
		re      = new RegExp( '_' + iterator, 'g' );
		tr_html = tr_html.replace( re, '_' + (iterator + 1) );

		re      = new RegExp( '\\[' + iterator + '\\]' , 'g' );
		tr_html = tr_html.replace( re, '[' + (iterator + 1) + ']' );

		// reset control values.
		re      = new RegExp( ' selected="selected"' );
		tr_html = tr_html.replace( re, '' );

		re      = new RegExp( 'value="[^"]+"' );
		tr_html = tr_html.replace( re, 'value=""' );

		// remove the view link, this page doesn't exist until saved.
		var mset = jQuery( tr_html );
		mset.find( 'a.button.action,button.delete-button' ).remove();
		return mset[0].outerHTML;
	}

	function toggle_value_input()
	{
		// toggle a text box that is not relevant if exists or does not exist is the operator.
		var val         = $( this ).val();
		var is_disabled = ( 'exists' == val || 'does_not_exist' == val );
		var id          = $( this ).attr( 'id' ); // looks like additional_listings_pages_operator_0.
		var el          = $( '#' + id.replace( 'operator', 'value' ) );
		if (is_disabled ) {
			el.val( '' );
		}
		el.attr( 'disabled', is_disabled );
	}

	function toggle_filter_inputs()
	{
		var key = $( this ).val();
		// Maybe disable operator and value.
		var id = $( this ).attr( 'id' ); // looks like additional_listings_pages_key_2.
		// additional_listings_pages_operator_2.
		// additional_listings_pages_value_2.
		$( '#' + id.replace( '_key_', '_operator_' ) + ',#' + id.replace( '_key_', '_value_' ) ).attr( 'disabled', '' === key );
	}
})( jQuery );
