var map;

function command_url(command) {
    var this_location = document.location.toString();
    var base_url = this_location.replace(/\/interface.*/,'');
    return base_url + '/server/?command=' + command;
}

var displaying_map = false;

var last_lat = 0;
var last_lng = 0;

function update_map(data) {
    var map_canvas = jQuery("#map-canvas");

    if (!displaying_map) {
	displaying_map = true;
	map_canvas.show();
	init_map(data.latitude, data.longitude);
    }
    if (data.latitude != last_lat || data.longitude != last_lng) {
	var latlng = new google.maps.LatLng(data.latitude, data.longitude);
	last_lat = data.latitude;
	last_lng = data.longitude;
	map.panTo(latlng);
    }
    if ("bearing" in data) {
	map_canvas.css("transform","rotate(" + (-data.bearing).toString() + "deg)");
    }
}

var map_active = false;

function start_game(data) {
    var places = data.places;

    jQuery(".inter_answer").each(function (n) {
	this.innerHTML = places[n];
	jQuery(this).show();
    });

    if (!map_active) {
	map_active = true;
	setInterval(function() {
	    jQuery.getJSON(command_url("status"), update_map);
	}, 1000);
    }
}

function request_new_game() {
    jQuery.getJSON(command_url("new_game"), start_game);
}

function init_map(start_lat, start_lng) {

  var start_loc = new google.maps.LatLng(start_lat, start_lng);
  var mapOptions = {
    zoom: 17,
    disableDefaultUI: true,
    center: start_loc,
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

  last_lat = start_lat;
  last_lng = start_lng;
  map = new google.maps.Map(document.getElementById('map-canvas'),
      mapOptions);
}

jQuery(document).ready(function () {
    jQuery("i").click(function(sender) {
	var command = jQuery(sender.delegateTarget).attr('id');
        var action_url = command_url(command);
        jQuery.get(action_url);
    });
    jQuery("#new-game").click(function(sender) {
	request_new_game();
	return false;
    });
});

