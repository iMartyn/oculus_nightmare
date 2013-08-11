var map;
var brooklyn = new google.maps.LatLng(53.80134, -1.53687) // really not

var MY_MAPTYPE_ID = 'custom_style';

function initialize() {

  var mapOptions = {
    zoom: 17,
    center: brooklyn,
    disableDefaultUI: true,
    styles: [
      {
	stylers: [
	  { hue: '#890000' },
	  { visibility: 'simplified' },
	  { gamma: 0.5 },
	  { weight: 0.5 }
	]
      },
      {
	elementType: 'labels',
	stylers: [
	  { visibility: 'off' }
	]
      },
      {
	featureType: 'water',
	stylers: [
	  { color: '#890000' }
	]
      }
    ],
  };

  map = new google.maps.Map(document.getElementById('map-canvas'),
      mapOptions);

}

google.maps.event.addDomListener(window, 'load', initialize);

