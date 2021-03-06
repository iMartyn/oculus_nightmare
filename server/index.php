<?php

$meters_to_move = 50;
$latitude_meters = 100000;
$longitude_meters = 70000;
$default_latitude = 53.80134;
$default_longitude = -1.53687;
$data_dir = 'data';
$lock_dir = $data_dir.'/lockfile.json';
$location_file = $data_dir.'/location.json';
$users_file = $data_dir.'/users.json';
$places_file = $data_dir.'/places.json';
$games_file = $data_dir.'/games.json';
$users_regex = '/^[a-zA-Z_\- ]{3,14}$/i';
$bearing = 0;
$dm_commands = array('forward','backward','left','right');
ini_set('show_errors',0);
ini_set('log_errors',1);

function float_array_valid($array,$keys) {
    $all_valid = true;
    foreach ($keys as $key) {
        if (!isset($array[$key]) || !is_numeric($array[$key])) {
            $all_valid = false;
        }
    }
    return $all_valid;
}

function process_winners(&$games,$users) {
    global $lock_dir;
    foreach ($games as $id=>$game) {
        if ($game['won'] && !array_key_exists('winner',$game) && $id > 0 && isset($game['uservotes'])) {
            // Array search is 0 based!
            $correct_id = array_search($game['correct'],$game['places']) +1;
            foreach ($game['uservotes'] as $userid=>$uservote) {
                if ($correct_id == $uservote) {
                    $winners[] = $userid;
                }
            }
            if (count($winners)) {
                $games[$id]['winners'] = $winners;
                $games[$id]['final_winner'] = array_rand($winners);
            }
        }
    }
}

function valid_name($username) {
    global $users_regex;
    return preg_match($users_regex,$username);
}

function some_random_places($places, $howMany = 4, $includeKey = null) {
    $return = array();
    if (isset($includeKey) && !is_null($includeKey)) {
        $return[] = $includeKey;
    }
    while (count($return) < $howMany) {
        $tryadding = array_rand($places);
        if (!in_array($tryadding,$return)) {
            $return[] = $tryadding;
        }
    }
    shuffle($return);
    return $return;
}

if (file_exists($location_file)) {
    $contents = file_get_contents($location_file);
    $location = json_decode($contents,true);
    if (array_key_exists('bearing', $location)) {
        $bearing = deg2rad($location['bearing']);
    }
} else {
    $location = array('latitude'=>$default_latitude,'longitude'=>$default_longitude);
}

if (!is_dir($data_dir)) {
    // Attempt to create it
    if (!mkdir($data_dir)){
        // Oh shit.
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'Could not make a home :(')));
    }
}

header('Content-type: text/javascript');
while (!mkdir($lock_dir)) {
    usleep(350);
}
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
        case 'bodge' :
            $command = 'bodge';
            if (!float_array_valid($_REQUEST,array('lat','lng'))) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'Expected two floating-point options!')));
            }
            $location['latitude'] = (float)$_REQUEST['lat'];
            $location['longitude'] = (float)$_REQUEST['lng'];
            $location['moved_recently'] = true;
            break;
        case 'view' :
            $command = 'view';
            if (!float_array_valid($_REQUEST,array('heading','lat','lng'))) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'Expected three floating-point options!')));
            }
            $location['bearing'] = (float)$_REQUEST['heading'];
            if (array_key_exists('moved_recently',$location) && !$location['moved_recently']) {
                $location['latitude'] = (float)$_REQUEST['lat'];
                $location['longitude'] = (float)$_REQUEST['lng'];
            } else {
                $location['moved_recently'] = false;
            }
            break;
        case 'client_register' :
            $command = 'register';
            if (!valid_name($_REQUEST['name'])) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'Name contains invalid characters')));
            }
            if (!file_exists($users_file)) {
                file_put_contents($users_file,json_encode(array('0'=>'')));
            }
            $users = json_decode(file_get_contents($users_file),true);
            $index = max(array_keys($users))+1;
            $users[$index] = $_REQUEST['name'];
            file_put_contents($users_file,json_encode($users));
            rmdir($lock_dir);
            die(json_encode(array('id'=>$index,'name'=>$users[$index])));
            break;
        case 'client_vote' :
            $command = 'vote';
            if (!file_exists($users_file)) {
                file_put_contents($users_file,json_encode(array('-1'=>'')));
            }
            $users = json_decode(file_get_contents($users_file),true);
            if (!isset($_REQUEST['user_id']) || !array_key_exists($_REQUEST['user_id'],$users)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'User not registered')));
            }
            $userid = $_REQUEST['user_id'];
            if (!file_exists($games_file)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 503 Internal Server Error', true, 503);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'No games in progress, come back later.')));
            }
            $games = json_decode(file_get_contents($games_file),true);
            if (empty($games)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 503 Internal Server Error', true, 503);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'No games in progress, come back later.')));
            }
            if (!isset($_REQUEST['game_id']) || !array_key_exists($_REQUEST['game_id'],$games) ||
                ($games[$_REQUEST['game_id']]['won'])) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 503 Internal Server Error', true, 503);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'Game not in progress')));
            }
            $gameid = $_REQUEST['game_id'];
            if (!isset($_REQUEST['vote']) || !is_numeric($_REQUEST['vote'])) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'Expected a number to vote for.')));
            }
            if (!in_array('uservotes',$games[$gameid])) {
                $games[$gameid]['uservotes'] = array();
            }
            $games[$gameid]['uservotes'][$userid] = $_REQUEST['vote'];
            file_put_contents($games_file,json_encode($games));
            rmdir($lock_dir);
            die(json_encode(array($userid=>$_REQUEST['vote'])));
            break;
        case 'place_register' :
            $command = 'place_register';
            if (!array_key_exists('desc',$_REQUEST) || !array_key_exists('lat',$_REQUEST) || !array_key_exists('lng',$_REQUEST) ||
                !is_numeric($_REQUEST['lat']) || !is_numeric($_REQUEST['lng'])) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'Expected description, lat, lng')));
            }
            if (!file_exists($places_file)) {
                file_put_contents($places_file,json_encode(array()));
            }
            $places = json_decode(file_get_contents($places_file),true);
            $places[$_REQUEST['desc']] = array('lat'=>$_REQUEST['lat'],'lng'=>$_REQUEST['lng']);
            file_put_contents($places_file,json_encode($places));
            rmdir($lock_dir);
            die(json_encode($places));
            break;
        case 'new_game' :
            $command = 'new_game';
            if (!file_exists($games_file)) {
                file_put_contents($games_file,json_encode(array(0=>null)));
            }
            $games = json_decode(file_get_contents($games_file),true);
            if (empty($games)) {
                $next_id = 1;
            } else {
                $next_id = max(array_keys($games))+1;
            }
            if (!file_exists($places_file)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'With what place data, huh?')));
            }
            $places = json_decode(file_get_contents($places_file),true);
            $key = array_rand($places);
            $placeList = some_random_places($places,4,$key);
            $place = $places[$key];
            foreach ($games as $id=>$game) {
                $games[$id]['won'] = true;
            }
            process_winners($games,$users);
            $games[$next_id] = array('id'=>$next_id,'won'=>false,
                'places'=>$placeList,
                'lat'=>$place['lat'], 'lng'=>$place['lng'],
                'correct'=>$key);
            file_put_contents($games_file,json_encode($games));
            $location['latitude'] = (float)$place['lat'];
            $location['longitude'] = (float)$place['lng'];
            $location['moved_recently'] = true;
            file_put_contents($location_file,json_encode($location));
            rmdir($lock_dir);
            die(json_encode($games[$next_id]));
            break;
        case 'game_status' :
            $command = 'game_status';
            if (!file_exists($users_file)) {
                file_put_contents($users_file,json_encode(array('0'=>'')));
            }
            $users = json_decode(file_get_contents($users_file),true);
            if (isset($_REQUEST['user_id']) && array_key_exists($_REQUEST['user_id'],$users)) {
                $userid = $_REQUEST['user_id'];
            } else {
                $userid = null;
            }
            if (!file_exists($games_file)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 503 Internal Server Error', true, 503);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'No games in progress, come back later.')));
            }
            $games = json_decode(file_get_contents($games_file),true);
            if (empty($games)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 503 Internal Server Error', true, 503);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'No games in progress, come back later.')));
            }
            if (!isset($_REQUEST['game_id'])) {
                $_REQUEST['game_id'] = max(array_keys($games));
            }
            if (!array_key_exists($_REQUEST['game_id'],$games)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 503 Internal Server Error', true, 503);
                rmdir($lock_dir);
                die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'Game not in progress or complete')));
            }
            $gameid = $_REQUEST['game_id'];
            $game = $games[$gameid];
            if (!in_array('uservotes',$games[$gameid])) {
                $games[$gameid]['uservotes'] = array();
            }
            $data = array();
            $data['choices'] = $game['places'];
            $data['id'] = $gameid;
            if (!is_null($userid)) {
                $userdata = $game['uservotes'][$userid];
                $data['userdata'] = $userdata;
            }
            if ($game['won']) {
                $resultdata = array('voted'=>count($game['uservotes']),'correctanswer'=>$game['correct']);
                $data['results'] = $resultdata;
            }
            $data['complete'] = $game['won'];
            if (isset($game['winners'])) {
                $data['winners'] = $game['winners'];
            }
            rmdir($lock_dir);
            die(json_encode($data));
            break;
        case 'status' :
            break;
        default:
            unset($_REQUEST['command']);
            break;
    }
}
if (!isset($_REQUEST['command'])) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    rmdir($lock_dir);
    die(json_encode(array('latitude'=>-1,'longitude'=>-1,'failure'=>'I have no idea what you want me to do!')));
}
$latitude_move = 0;
$longitude_move = 0;
if (!is_null($movement_bearing) && !is_null($command) && in_array($command,$dm_commands,true)) {
    /* This is here because it's a movement command and so we don't want copy and paste. */
    $latitude_move = $meters_to_move * cos($movement_bearing) / $latitude_meters;
    $longitude_move = $meters_to_move * sin($movement_bearing) / $longitude_meters;
    $location['latitude'] = (float)$location['latitude']+(float)$latitude_move;
    $location['longitude'] = (float)$location['longitude']+(float)$longitude_move;
    $location['moved_recently'] = true;
}
file_put_contents($location_file,json_encode($location));
echo json_encode($location);
rmdir($lock_dir);