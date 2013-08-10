<?php

const meters_to_move = 50;
const latitude_meters = 100000;
const longitude_meters = 70000;
const default_latitude = 53.80134;
const default_longitude = -1.53687;
const location_file = 'location.json';
$bearing = 0;

if (file_exists(location_file)) {
    $contents = file_get_contents(location_file);
    $location = json_decode($contents,true);
} else {
    $location = array('latitude'=>default_latitude,'longitude'=>default_longitude);
}
header('Content-type: text/javascript');
header('X-latitude: '.$location['latitude']);
header('X-longitude: '.$location['longitude']);

$command = null;
$movement_bearing = null;
if (isset($_REQUEST['command'])) {
    switch ($_REQUEST['command']) {
        case 'move_forward' :
            $command = 'forward';
            $movement_bearing = $bearing;
            break;
        case 'move_backward' :
            $command = 'backward';
            $movement_bearing = $bearing - pi();
            break;
        case 'move_left' :
            $command = 'left';
            $movement_bearing = $bearing - (pi()/2);
            break;
        case 'move_right' :
            $command = 'right';
            $movement_bearing = $bearing + (pi()/2);
            break;
    }
}
$latitude_move = 0;
$longitude_move = 0;
if (!is_null($movement_bearing) && !is_null($command)) {
    $latitude_move = meters_to_move * sin($movement_bearing) / latitude_meters;
    $longitude_move = meters_to_move * cos($movement_bearing) / longitude_meters;
}
$location['latitude'] = (float)$location['latitude']+(float)$latitude_move;
$location['longitude'] = (float)$location['longitude']+(float)$longitude_move;
file_put_contents(location_file,json_encode($location));
echo json_encode($location);
