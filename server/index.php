<?php

$GLOBALS['latitude'] = 53.80134;
$GLOBALS['longitude'] = -1.53687;
header('Content-type: text/javascript');
header('X-latitude: '.$GLOBALS['latitude']);
header('X-longitude: '.$GLOBALS['longitude']);

echo json_encode(array('latitude'=>$GLOBALS['latitude'],'longitude'=>$GLOBALS['longitude']));
