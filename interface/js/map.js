var map;

function command_url(command) {
    var this_location = document.location.toString();
    var base_url = this_location.replace(/\/interface.*/,'');
    return base_url + '/server/?command=' + command;
}

function update_map(data) {
    var latlng = new google.maps.LatLng(data.latitude, data.longitude);
    map.panTo(latlng);
    if ("bearing" in data) {
      jQuery("#map-canvas").css("transform","rotate(" + (-data.bearing).toString() + "deg)");
    }
}

function init_maps() {

  var mapOptions = {
    zoom: 17,
    disableDefaultUI: true,
    styles: [
      {
	stylers: [
	  { hue: '#00ffff' },
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
	  { color: '#00ffff' }
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

jQuery(document).ready(function () {
    init_maps();
    jQuery("i").click(function(sender) {
	var command = jQuery(sender.delegateTarget).attr('id');
        var action_url = command_url(command);
        jQuery.get(action_url);
    });
});

