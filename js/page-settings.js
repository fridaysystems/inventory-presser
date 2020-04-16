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
			//rebind the operator selects
			$('select.operator').off('change');
			$('select.operator').on('change', toggle_value_input );
			return false;
		});

		//bind the delete buttons
		$('button.delete-button').on('click', delete_listings_page);

		//bind a checkbox to the visibility of a div to show and hide settings
		$('#additional_listings_page').on('change', toggle_listings_pages_settings );
		toggle_listings_pages_settings();
	});

	function toggle_listings_pages_settings()
	{
		$('#additional_listings_pages_settings').toggle( $('#additional_listings_page').prop('checked') );
	}

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

		//remove the view link, this page doesn't exist until saved
		var mset = jQuery(tr_html);
		mset.find('a.button.action,button.delete-button').remove();
		return mset[0].outerHTML;
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