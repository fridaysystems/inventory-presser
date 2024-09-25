var parseQueryString = function () {
	var str    = window.location.search;
	var objURL = {};
	str.replace(
		new RegExp( "([^?=&]+)(=([^&]*))?", "g" ),
		function ( $0, $1, $2, $3 ) {
			objURL[ $1 ] = $3;
		}
	);
	return objURL;
};
window.addEventListener(
	'load',
	function () {
		var el = document.getElementById( 'sort_by' );
		if ( 'undefined' === typeof el ) {
			return;
		}
		el.addEventListener(
			'change',
			function () {
				var new_location = window.location.protocol + '//'
				+ window.location.hostname
				+ ( window.location.port ? ':' + window.location.port : '' )
				+ window.location.pathname + '?';

				// remove any paging so the user ends up back on page 1
				new_location = new_location.replace( new RegExp( "\/page\/[0-9]+","gm" ), "" );

				// get all the querystring args into an object
				var params = parseQueryString();
				// replace with our new orderby values, while still preserving anything current
				params["orderby"] = el.value;
				params["order"]   = el.options[el.selectedIndex].dataset.order; // jQuery(this).find(':selected').data('order');
				var qstringarr    = [];
				for ( var key in params ) {
					if ( params.hasOwnProperty( key ) ) {
						qstringarr.push( key + '=' + params[key] );
					}
				}
				window.location.href = new_location + qstringarr.join( '&' );
			}
		);
	}
);
