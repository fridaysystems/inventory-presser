// Returns an associative array.
function extract_wp_query_args_from_url() {
	var url = window.location.href;
	// get just the query parameters.
	if ( -1 === url.indexOf( '?' ) ) {
		return {}; }
	// trim any fragment if we find one.
	if ( -1 !== url.indexOf( '#' ) ) {
		url = url.substring( 0, url.indexOf( '#' ) );
	}
	var chunks = url.split( '?' );
	if ( 2 == chunks.length ) {
		return query_string_to_associative_array( chunks[1] );
	}
	return {};
}


function html_form_launch( url, method, params ) {
	// build a form and submit it, found @ https://stackoverflow.com/a/133997/338432.
	var form = document.createElement( 'form' );
	form.setAttribute( 'method', method );
	if ( '' !== url ) {
		form.setAttribute( 'action', url ); }
	if ( null !== params ) {
		for ( var key in params ) {
			if ( params.hasOwnProperty( key ) ) {
				var field = document.createElement( 'input' );
				field.setAttribute( 'type', 'hidden' );
				field.setAttribute( 'name', key );
				field.setAttribute( 'value', params[key] );
				form.appendChild( field );
			}
		}
	}
	document.body.appendChild( form );
	form.submit();
}

function order_by_post_meta( key ) {
	// Submit to the same page with an orderby param.
	var params        = extract_wp_query_args_from_url();
	params['orderby'] = key;
	// sorting on same field? reverse the sort order.
	params['order'] = ( ( undefined !== params['orderby'] && key == params['orderby'] ) && ( 'ASC' == params['order'] || '' === params['order'] ) ? 'DESC' : 'ASC' );
	html_form_launch( '', 'GET', params );
}

function query_string_to_associative_array( str ) {
	var arr    = str.replace( '?', '' ).split( '&' );
	var params = {};
	var count  = arr.length;
	for ( var q = 0; q < count; q++ ) {
		var pieces        = arr[q].split( '=' );
		params[pieces[0]] = pieces[1];
	}
	return params;
}
