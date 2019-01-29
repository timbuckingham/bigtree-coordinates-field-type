<?php
	// Get API key
	$api_key = $cms->getSetting("com.fastspot.coordinates-field-type*api-key");

	if (!$api_key) {
?>
<p class="error_message">You must enter your Google Maps API key in the <a href="<?=ADMIN_ROOT?>settings/edit/com.fastspot.coordinates-field-type*api-key/">Coordinates Field Type - API Key</a> setting.</p>
<?php
	} else {
		// Get default zoom level
		$zoom_level = $cms->getSetting("default-zoom") ?: 15;
		
		// Bring in defaults from field options and settings
		$latitude = $field["options"]["default_latitude"] ?: $cms->getSetting("default-latitude");
		$latitude = $latitude ?: "39.2904";
		
		$longitude = $field["options"]["default_longitude"] ?: $cms->getSetting("default-longitude");
		$longitude = $longitude ?: "-76.6122";
		
		// Bring in current values
		if (is_array($field["value"])) {
			$latitude = $field["value"]["latitude"] ?: $latitude;
			$longitude = $field["value"]["longitude"] ?: $longitude;
		}
?>
<input type="text" id="<?=$field["id"]?>_address" placeholder="Type to search for an address, city, state, etc." tabindex="<?=$field["tabindex"]?>">
<input type="hidden" id="<?=$field["id"]?>_value" value="<?=htmlspecialchars(json_encode($field["value"]))?>" name="<?=$field["key"]?>">

<div id="<?=$field["id"]?>_map" style="width: 100%; height: 300px; margin: 10px 0 0 0;"></div>

<script>
	(function() {
		var Geocoder;
		var Map;
		var Marker;
		var SearchTimer;
		
		function createMarker(latitude, longitude) {
			// Updater or create a marker
			if (Marker) {
				Marker.setPosition(new google.maps.LatLng(latitude, longitude));						
			} else {
				Marker = new google.maps.Marker({
					map: Map,
					draggable: true,
					animation: google.maps.Animation.DROP,
					position: new google.maps.LatLng(latitude, longitude)
				});
				
				google.maps.event.addListener(Marker, "dragend", updateValue);
			}
			
			// Update hidden value
			updateValue();
		}
		
		function geocodeAddress() {
			Geocoder.geocode({
				address: $("#<?=$field["id"]?>_address").val()
			}, function(results, status) {
				if (status === google.maps.GeocoderStatus.OK) {
					// Recenter Map
					Map.setCenter(results[0].geometry.location);
					Map.setZoom(<?=$zoom_level?>);
					
					// Create / update marker
					createMarker(results[0].geometry.location.lat(), results[0].geometry.location.lng());
				}
			});
		}
		
		function initMap() {
			Map = new google.maps.Map(document.getElementById('<?=$field["id"]?>_map'), {
				streetViewControl: false,
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				center: { lat: <?=$latitude?>, lng: <?=$longitude?> },
				zoom: <?=$zoom_level?>
			});
			
			google.maps.event.addListener(Map, "click", function(event) {
				createMarker(event.latLng.lat(), event.latLng.lng());
			});

			Geocoder = new google.maps.Geocoder();
			
			<?php if (!empty($field["value"])) { ?>
			createMarker(<?=$latitude?>, <?=$longitude?>);
			<?php } ?>
		}
		
		function updateValue(latitude, longitude) {
			var value = {
				"latitude": Marker.getPosition().lat(),
				"longitude": Marker.getPosition().lng()
			};
			
			$("#<?=$field["id"]?>_value").val(JSON.stringify(value));
		}
		
		// Include the main script if we don't have it yet
		if (typeof google == "undefined" || typeof google.maps == "undefined") {
			$.getScript("https://maps.googleapis.com/maps/api/js?key=<?=$api_key?>", initMap);
		} else {
			initMap();
		}
		
		$("#<?=$field["id"]?>_address").keyup(function() {
			clearTimeout(SearchTimer);
			SearchTimer = setTimeout(geocodeAddress, 300);
		});
		
	})();
</script>
<?php
	}
