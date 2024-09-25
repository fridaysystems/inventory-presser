var maps = document.getElementsByClassName( 'invp-map' );
Array.prototype.map.call(
	maps,
	function (map) {
		var mymap = L.map( map.id );
		// load map box tile
		L.tileLayer(
			'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=' + invp_maps.mapbox_public_token,
			{
				attribution: 'Map data &copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a> contributors, Imagery © <a href=\"https://www.mapbox.com/\">Mapbox</a>',
				id: 'mapbox/streets-v11',
				tileSize: 512,
				zoomOffset: -1,
				accessToken: invp_maps.mapbox_public_token
			}
		).addTo( mymap );

		// add markers and popups for each location
		var markers = [];
		for ( var p = 0; p < invp_maps.popups.length; p++ ) {
			if ( invp_maps.popups[p].widget_id != map.id.replace( '-inner', '' ) ) {
				// there must be more than one Map widget on this page
				continue;
			}
			var lat = invp_maps.popups[p].coords.lat;
			var lon = invp_maps.popups[p].coords.lon;
			// center the map
			mymap.setView( [lat, lon], 13 );

			// create a marker at the dealership
			var marker = L.marker( [lat, lon] ).addTo( mymap );
			// and a popup
			marker.bindPopup( '<b>' + invp_maps.popups[p].name + '</b><br />' + invp_maps.popups[p].address ).openPopup();
			markers.push( marker );
		}

		if ( 1 < markers.length ) {
			// make sure all markers are visible at once
			var group = new L.featureGroup( markers );
			mymap.fitBounds( group.getBounds() );
		}
	}
);
