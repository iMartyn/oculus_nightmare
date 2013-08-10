<?php

$meters_to_move = 50;
$latitude_meters = 100000;
$longitude_meters = 70000;
$default_latitude = 53.80134;
$default_longitude = -1.53687;
$location_file = 'location.json';
$bearing = 0;
$dm_commands = array('forward','backward','left','right');

if (file_exists($location_file)) {
    $contents = file_get_contents($location_file);
    $location = json_decode($contents,true);
    if (array_key_exists('bearing', $_REQUEST)) {
        $bearing = $location['bearing'];
    }
} else {
    $location = array('latitude'=>$default_latitude,'longitude'=>$default_longitude);
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
        case 'view' :
            $command = 'view';
            if (!isset($_REQUEST['heading']) || !isset($_REQUEST['lat']) || !isset($_REQUEST['lng']) ||
                !is_float($_REQUEST['heading']) || !is_float($_REQUEST['lat']) || !is_float($_REQUEST['lng'])) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'Expected three floating-point options!')));
            }
            $location['bearing'] = (float)$_REQUEST['heading'];
            $location['latitude'] = (float)$_REQUEST['lat'];
            $location['longitude'] = (float)$_REQUEST['lng'];
            break;
    }
}
if (!isset($_REQUEST['command'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'I have no idea what you want me to do!')));
}
$latitude_move = 0;
$longitude_move = 0;
if (!is_null($movement_bearing) && !is_null($command) && in_array($command,$dm_commands,true)) {
    /* This is here because it's a movement command and so we don't want copy and paste. */
    $latitude_move = $meters_to_move * sin($movement_bearing) / $latitude_meters;
    $longitude_move = $meters_to_move * cos($movement_bearing) / $longitude_meters;
    $location['latitude'] = (float)$location['latitude']+(float)$latitude_move;
    $location['longitude'] = (float)$location['longitude']+(float)$longitude_move;
}
file_put_contents($location_file,json_encode($location));
echo json_encode($location);
