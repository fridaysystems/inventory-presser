window.addEventListener('load', function()
{
	if( 0 < invp_google_maps_v3.locations.length )
	{
		var map = new google.maps.Map(document.getElementById("map_canvas"), {
			zoom: 13,
			center: new google.maps.LatLng(39.833333, -98.583333), //middle USA
			mapTypeId: google.maps.MapTypeId.ROADMAP
		});

		//Use a LatLngBounds to zoom the map to show all markers
		var bounds = new google.maps.LatLngBounds();

		for( var l=0; l<invp_google_maps_v3.locations.length; l++ )
		{
			//If we do not have latitude and longitude, we can't map the address
			if( null == invp_google_maps_v3.locations[l].coords )
			{
				continue;
			}

			// Data is already escaped in PHP via esc_html/wp_kses_post
			var marker = new google.maps.Marker({
				position: new google.maps.LatLng( invp_google_maps_v3.locations[l].coords.lat, invp_google_maps_v3.locations[l].coords.lon ),
				map: map,
				title: invp_google_maps_v3.locations[l].name
			});

			bounds.extend( marker.position );

			var infoWindow = new google.maps.InfoWindow({
				content: "<div class=\"mapBubble\" style=\"color: #000;\">" 
					+ "<b>" + invp_google_maps_v3.locations[l].name + "</b>"
					+ "<br />" + invp_google_maps_v3.locations[l].address
					+ "</div>",
				disableAutoPan: false,
				anchor: marker
			});

			//Add a click handler to open the infoWindow after it's been closed
			with({thisMarker: marker, thisInfoWindow: infoWindow})
			{
				google.maps.event.addListener( thisMarker, 'click', function(){
					thisInfoWindow.open( map, thisMarker );
				});
			}
		}

		//Make sure all the markers are visible when the page loads
		map.fitBounds(bounds);
	}
});