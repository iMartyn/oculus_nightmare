var map;
var brooklyn = new google.maps.LatLng(53.80134, -1.53687) // really not

var MY_MAPTYPE_ID = 'custom_style';

function update_map(data) {
    var latlng = new google.maps.LatLng(data.latitude, data.longitude);
    map.panTo(latlng);
    if ("bearing" in data) {
      jQuery("#map-canvas").css("transform","rotate(" + (-data.bearing).toString() + "deg)");
    }
}

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

  setInterval(function() {
      jQuery.getJSON(command_url("status"), update_map);
  }, 1000);
}

google.maps.event.addDomListener(window, 'load', initialize);

