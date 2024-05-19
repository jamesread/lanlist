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
	contentHtml += "<h2><a href = \"viewEvent.php?id=" + eventObject.id + "\">" + eventObject.organizerTitle + " - " + eventObject.eventTitle + "</a></h2>";
	contentHtml += '<p>';
	contentHtml += '<strong>Starts:</strong> ' + eventObject.dateStartHuman + '<br />';
  contentHtml += '<strong>Finishes:</strong> ' + eventObject.dateFinishHuman + '<br />';
	contentHtml += '<strong>Seats:</strong> ' + eventObject.numberOfSeats + '<br /><br />';
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
      mapId: 'lanlist'
    })
}

function focusOnMarker(marker) {
	
}

async function addMarker(lat, lng, icon, eventObject, focus) {
	var position = { lat: lat, lng: lng }

  const { AdvancedMarkerElement } = await google.maps.importLibrary("marker")

  const img = document.createElement('img')
  img.setAttribute('title', eventObject.eventTitle)
  img.setAttribute('alt', eventObject.eventTitle)
  img.setAttribute('width', '16')
  img.setAttribute('height', '16')
  img.src = icon

  if (eventObject.useFavicon) {
    img.classList.add('favicon')
  }

	const marker = new AdvancedMarkerElement({
		position: position,
		map: window.map,
    title: eventObject.eventTitle,
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
  if (eventObject.useFavicon) {
    var icon = 'resources/images/organizer-favicons/' + eventObject.organizerId + '.png'
  } else {
    if (new Date(eventObject.dateStart) < window.now) {
      var icon = "resources/images/eventMarkerGray.png";
    } else {
      var icon = "resources/images/eventMarkerLogo.png";
    }
  }

	addMarker(eventObject.lat, eventObject.lng, icon, eventObject).then(marker => {
    marker.addListener('click', function() {
      onEventMarkerClicked(eventObject, marker);
    });
  })
}
