function showSetLocationForm() {
	$("#formSetLocationContainer").show();
	$("#linkShowSetLocationForm").hide();
}

function getCookieContent(cookieSearch) {
	var cookieList = document.cookie.split(';');
	var theContent = null;

	$(cookieList).each(function(index, item) {
		cookie = item.split('=');

		if (cookie[0].trim() == cookieSearch) {
			theContent = cookie[1];
		}
	});

	return theContent;
}

function getDirections(eventObject) {
		var myLocation = getCookieContent('mylocation');

		if (!myLocation) {
			return;
		}

		var req = {
			origin: myLocation,
			destination: (new google.maps.LatLng(eventObject.lat, eventObject.lng)),
			travelMode: google.maps.DirectionsTravelMode.DRIVING
		};

		var directionsService = new google.maps.DirectionsService();
		directionsService.route(req, function(result, status) {
			if (status == google.maps.DirectionsStatus.OK) {
				dd = new google.maps.DirectionsRenderer();
				dd.setMap(map);
				dd.setDirections(result);
			} else {
				window.alert("I'm sorry, I'm afraid I can't do that dave.");
			}
		});
}

function onEventMarkerClicked(eventObject) {
	var content = '';
	content += '<div class = "infoPopup">';
	content += '<h2>' + eventObject.organizerTitle + ' - ' + eventObject.eventTitle + '</h2>';
	content += '<strong>Start:</strong> ' + eventObject.dateStart + '<br />';
	content += '<strong>Finish:</strong> ' + eventObject.dateFinish + '<br /><br />';
	content += '<strong>Seats:</strong> ' + eventObject.numberOfSeats + '<br />';
	content += '<a href = "viewEvent.php?id=' + eventObject.id + '">more details...</a>';
	content += '</div>';
	content = $(content);

	$('.infoPopup').remove();
	$('#eventInfo').append(content)
	content.show('clip')
	content.effect('highlight');
//	document.getElementById('eventInfo').innerHTML = content;

	if (getCookieContent('mylocation') === null) {
		$('#btnDirections').attr('disabled', 'disabled');
	} else {
		$('#btnDirections').removeAttr('disabled');
		$('#btnDirections').click(function () {getDirections(eventObject); });
	}
}

function renderMap() {
		window.map = new google.maps.Map(document.getElementById("map"));
		window.map.setCenter(new google.maps.LatLng(55.729639,4.603271));
		window.map.setZoom(5);
		window.map.setMapTypeId(google.maps.MapTypeId.ROADMAP);

		$('#btnDirections').attr('disabled', 'disabled');
}

function focusOnMarker(marker) {
	
}

function addMarker(lat, lng, icon, focus) {
	var position = new google.maps.LatLng(lat, lng);

	var marker = new google.maps.Marker({
		map: window.map,
		position: position,
		icon: (icon == null) ? null : icon,
	});

	if (focus) {
		window.map.setCenter(position);
	}

	return marker;
}

window.now = new Date();

function addEvent(eventObject) {
	if (new Date(eventObject.dateStart) < window.now) {
		var icon = "resources/images/eventMarkerGray.png";
	} else {
		var icon = "resources/images/eventMarkerLogo.png";
	}

	var marker = addMarker(eventObject.lat, eventObject.lng, icon);

	google.maps.event.addListener(marker, 'click', function() {
		onEventMarkerClicked(eventObject);
	});
}
