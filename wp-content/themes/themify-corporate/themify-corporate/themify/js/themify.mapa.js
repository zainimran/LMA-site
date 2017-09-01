var ThemifyMap = {};

(function(){

	'use strict';

	ThemifyMap = {

		request: 0,

		/**
		 * Function to initialize a Google Maps instance
		 * @param address Map address to display
		 * @param num CSS ID
		 * @param zoom 0 - 15
		 * @param type ROADMAP SATELLITE HYBRID TERRAIN
		 * @param scroll
		 * @param drag
		 */
		initialize: function( address, num, zoom, type, scroll, drag ) {
			var delay = this.request++ * 500;

			if ( 'desktop' == drag ) {
				if ( 'undefined' === typeof themifyScript || 'undefined' === themifyScript.isTouch || 'true' != themifyScript.isTouch ) {
					drag = 'yes';
				}
			}

			setTimeout( function(){
				var geo = new google.maps.Geocoder(),
					latlng = new google.maps.LatLng(-34.397, 150.644),
					mapOptions = {
						'zoom': zoom,
						center: latlng,
						mapTypeId: google.maps.MapTypeId.ROADMAP,
						scrollwheel: scroll == 'yes',
						draggable: drag == 'yes'
					};
				switch( type.toUpperCase() ) {
					case 'ROADMAP':
						mapOptions.mapTypeId = google.maps.MapTypeId.ROADMAP;
						break;
					case 'SATELLITE':
						mapOptions.mapTypeId = google.maps.MapTypeId.SATELLITE;
						break;
					case 'HYBRID':
						mapOptions.mapTypeId = google.maps.MapTypeId.HYBRID;
						break;
					case 'TERRAIN':
						mapOptions.mapTypeId = google.maps.MapTypeId.TERRAIN;
						break;
				}
				var	map = new google.maps.Map( document.getElementById( 'themify_map_canvas_' + num ), mapOptions );
				geo.geocode( { 'address': address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						map.setCenter(results[0].geometry.location);
						var marker = new google.maps.Marker({
							map: map,
							position: results[0].geometry.location	});
					}
				});
				jQuery('#' + 'themify_map_canvas_' + num ).data('map', map);
			}, delay );
		}
	};
})();