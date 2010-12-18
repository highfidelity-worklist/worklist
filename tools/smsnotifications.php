<?php

if(php_sapi_name() != 'cli'){
    die('Can only be called from command line!');
}

$application_path = dirname(dirname(__FILE__)) . '/';

require_once($application_path . 'config.php');
require_once($application_path . 'lib/Sms.php');
require_once($application_path . 'classes/Notification.class.php');

// we should have at least 3 scrit arguments - subject, message and at least one
// receiver
$args = $_SERVER['argv'];
if(count($args) < 4){
    die('Not enough arguments');
}

$subject = $args[1];
$message = $args[2];

$receivers = array();
$count = count($args);
for($i = 3; $i < $count; $i++){
    $receivers[] = (int) $args[$i];
}

foreach($receivers as $receiver){
    $sms_user = new User();
    $sms_user->findUserById($receiver);
    Notification::sendSMS($sms_user, $subject, $message);
}


