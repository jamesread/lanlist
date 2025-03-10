function showSetLocationForm()
{
    $("#formSetLocationContainer").show();
    $("#linkShowSetLocationForm").hide();
}

function getCookieContent(cookieSearch)
{
    var cookieList = document.cookie.split(';');
    var theContent = null;

    $(cookieList).each(function (index, item) {
        cookie = item.split('=');

        if (cookie[0].trim() == cookieSearch) {
            theContent = cookie[1];
        }
    });

    return theContent;
}

function onEventMarkerClicked(eventObject, marker)
{
    window.lastEvent = eventObject;

    showInfobox(eventObject, marker);
}

function showInfobox(eventObject, marker)
{
    if (window.infoBox != null) {
        window.infoBox.close();
    }

    contentHtml = "";
    contentHtml += '<img class = "bannerSmall" src = "' + eventObject.bannerUrl + '" />';
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

async function renderMap(geoipGuess)
{
    const { Map } = await google.maps.importLibrary("maps");

    window.map = new Map(document.getElementById('map'), {
        zoom: 5,
        mapId: 'lanlist'
    })

    try { 
      //const geoipGuess = 'United Kingdom'
      countryZoom(geoipGuess)
    } catch (e) {
      console.error("Could not get location", e)
      window.map.setCenter({ lat: 55.729639, lng: 4.603271 })
    }
}


function countryZoom(country) {
  const geocoder = new google.maps.Geocoder()

  geocoder.geocode({address: country}, (results, status) => {
    if (status == google.maps.GeocoderStatus.OK) {
      window.map.setCenter(results[0].geometry.location);
      window.map.fitBounds(results[0].geometry.viewport);
    }
  })

}

function focusOnMarker(marker)
{

}

async function addMarker(lat, lng, title, iconUrl, clickable, focus)
{
    const { AdvancedMarkerElement } = await google.maps.importLibrary("marker")

    let markerSettings = {
        position: { lat: lat, lng: lng},
        map: window.map,
        title: title,
        gmpClickable: clickable
    }

    if (iconUrl != null) {
        const img = document.createElement('img')
        img.setAttribute('title', title)
        img.setAttribute('alt', title)
        img.setAttribute('width', '16')
        img.setAttribute('height', '16')
        img.src = iconUrl
        img.classList.add('favicon')

        markerSettings.content = img
    }

    const marker = new AdvancedMarkerElement(markerSettings);

    if (focus) {
        window.map.setZoom(8);
        window.map.setCenter(markerSettings.position);
    }

    return marker
}

function addMarkerEvent(evt, focus)
{
    addMarker(evt.venueLat, evt.venueLng, evt.eventTitle, getEventIcon(evt), true, focus).then(marker => {
        marker.addListener('click', function () {
            onEventMarkerClicked(evt, marker);
        });
    })
}

function getEventIcon(evt)
{
    if (evt.useFavicon) {
        return 'resources/images/organizer-favicons/' + evt.organizerId + '.png'
    } else {
        if (new Date(evt.dateStart) < window.now) {
            return "resources/images/eventMarkerGray.png";
        } else {
            return "resources/images/eventMarkerLogo.png";
        }
    }
}

window.now = new Date();
