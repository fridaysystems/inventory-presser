(function($) {
	jQuery(document).ready(function() {
		//add handler on the operator dropdowns so we can toggle the value text box
		$('select.operator').on('change', toggle_value_input );
		//toggle the textboxes right now when the page is ready
		$('select.operator').each(toggle_value_input);

		//bind this button to add a new row to the table of additional listings pages
		$('button#add_additional_listings_page').on('click', function(){
			var row_html = $('table.additional_listings_pages tbody tr:last')[0].outerHTML;
			$('table.additional_listings_pages tbody').append( repeat_row( row_html ) );
			return false;
		});

		//bind the delete buttons
		$('button.delete-button').on('click', delete_listings_page);
	});

	function delete_listings_page()
	{
		//id contains the iterator
		var id = $(this).attr('id');
		var pieces = id.split('_');
		var iterator = pieces[pieces.length-1];
		$('#row_' + iterator).remove();
	}

	function repeat_row( tr_html )
	{
		//find the iterator number
		let re = new RegExp('additional_listings_pages_slug_([0-9]+)');
		var match_arr = re.exec( tr_html );
		var iterator = parseInt( match_arr[match_arr.length-1] );

		//replace it everywhere
		re = new RegExp( '_' + iterator, 'g' );
		tr_html = tr_html.replace( re, '_' + (iterator+1) );

		re = new RegExp( '\\[' + iterator + '\\]' , 'g' );
		tr_html = tr_html.replace( re, '[' + (iterator+1) + ']' );

		//reset control values
		re = new RegExp( ' selected="selected"' );
		tr_html = tr_html.replace( re, '' );

		re = new RegExp( 'value="[^"]+"' );
		tr_html = tr_html.replace( re, 'value=""' );

		return tr_html;

		/*
			<tr>
				<td style="width: 100%;">http://localhost:8080/sandbox/<input type="text" id="additional_listings_pages_slug_0" name="inventory_presser[additional_listings_pages][0][url_path]" value="cash-deals"></td>
				<td><select id="additional_listings_pages_key_0" name="inventory_presser[additional_listings_pages][0][key]"><option value="beam">Beam</option><option value="body_style">Body style</option><option value="car_id">Car id</option><option value="carfax_have_report">Carfax have report</option><option value="carfax_one_owner">Carfax one owner</option><option value="carfax_url_icon">Carfax url icon</option><option value="carfax_url_report">Carfax url report</option><option value="color">Color</option><option value="dealer_id">Dealer id</option><option value="down_payment" selected="selected">Down payment</option><option value="edmunds_style_id">Edmunds style id</option><option value="engine">Engine</option><option value="featured">Featured</option><option value="hull_material">Hull material</option><option value="interior_color">Interior color</option><option value="last_modified">Last modified</option><option value="leads_id">Leads id</option><option value="length">Length</option><option value="make">Make</option><option value="model">Model</option><option value="msrp">Msrp</option><option value="odometer">Odometer</option><option value="payment">Payment</option><option value="payment_frequency">Payment frequency</option><option value="price">Price</option><option value="stock_number">Stock number</option><option value="title_status">Title status</option><option value="transmission_speeds">Transmission speeds</option><option value="trim">Trim</option><option value="vin">Vin</option><option value="year">Year</option><option value="youtube">Youtube</option></select></td>
				<td><select id="additional_listings_pages_operator_0" name="inventory_presser[additional_listings_pages][0][operator]" class="operator"><option value="exists">exists</option><option value="does_not_exist">does not exist</option><option value="greater_than">greater than</option><option value="less_than">less than</option><option value="equal_to">equal to</option><option value="not_equal_to">not equal to</option></select></td>
				<td><input type="text" id="additional_listings_pages_value_0" name="inventory_presser[additional_listings_pages][0][value]" value=""></td>
			</tr>
		*/
	}

	function toggle_value_input()
	{
		//toggle a text box that is not relevant if exists or does not exist is the operator
		var val = $(this).val();
		var is_disabled = ( 'exists' == val || 'does_not_exist' == val );
		var id = $(this).attr('id'); //looks like additional_listings_pages_operator_0
		var el = $('#'+id.replace( 'operator', 'value' ));
		if( is_disabled )
		{
			el.val('');
		}
		el.attr('disabled', is_disabled );
	}
})( jQuery );