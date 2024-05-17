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

function onEventMarkerClicked(eventObject, marker) {
	window.lastEvent = eventObject;

	showInfobox(eventObject, marker);
}

function showInfobox(eventObject, marker) {
	if (window.infoBox != null) {
		window.infoBox.close();
	}

	contentHtml = "";
	contentHtml += '<img class = "bannerSmall" src = "' + eventObject.bannerUrl +'" />';
	contentHtml += "<h2>" + eventObject.organizerTitle + " - " + eventObject.eventTitle + "</h2>";
	contentHtml += '<p>';
	contentHtml += 'Starts: ' + eventObject.dateStart + ', finishes: ' + eventObject.dateFinish + '<br />';
	contentHtml += 'Seats: ' + eventObject.numberOfSeats + '<br /><br />';
	contentHtml += '<a href = "viewEvent.php?id=' + eventObject.id + '">More info...</a>'
	contentHtml += '</p>';

	window.infoBox = new google.maps.InfoWindow({
		content: contentHtml
	});

	window.infoBox.open(window.map, marker);
}

async function renderMap() {
    const { Map } = await google.maps.importLibrary("maps");

    window.mapCenter = { lat: 55.729639, lng: 4.603271 }
    
		window.map = new Map(document.getElementById('map'), {
      center: mapCenter,
      zoom: 5,
      mapId: 'lanlist.org'
    })
}

function focusOnMarker(marker) {
	
}

async function addMarker(lat, lng, icon, focus, eventTitle) {
	var position = { lat: lat, lng: lng }

  const { AdvancedMarkerElement } = await google.maps.importLibrary("marker")

  const img = document.createElement('img')
  img.src = icon

	const marker = new AdvancedMarkerElement({
		position: position,
		map: window.map,
    title: eventTitle,
    content: img,
    gmpClickable: true
//		icon: (icon == null) ? null : icon,
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

	addMarker(eventObject.lat, eventObject.lng, icon, eventObject.eventTitle).then(marker => {
    marker.addListener('click', function() {
      onEventMarkerClicked(eventObject, marker);
    });
  })
}
