<?php

if(php_sapi_name() != 'cli'){
    die('Can only be called from command line!');
}

$application_path = dirname(dirname(__FILE__)) . '/';
require_once ($application_path . 'config.php');
require_once ($application_path . "functions.php");
include_once ($application_path . "send_email.php");

$sql = " 
    SELECT `id`, `phone`
    FROM  " . USERS . " 
    WHERE LENGTH(`phone`) > 7 
      AND SUBSTRING(`phone`, 1, 1) REGEXP '[[:digit:]]'";

$res = mysql_query($sql);
$i = 0;
while(($row = mysql_fetch_array($res)) !== false) {
    $user = new User();
    $id = $row['id'];
    $phone = $row['phone'];
    $user->findUserById($id);
    $phone_confirm_string = substr(uniqid(), -4);
    
    echo 'Validating ' . $user->getNickname() . "'s phone number...";
    
    $sql = "
        UPDATE " . USERS . "
        SET `phone_rejected` = '0000-00-00 00:00:00',
            `phone_verified` = '0000-00-00 00:00:00',
            `phone_confirm_string` = '" . $phone_confirm_string . "'
        WHERE `id` = " . $id;
    mysql_query($sql);
    
    if (mysql_affected_rows()) {
        try {
    	    $message = 'Confirm code: ' . $phone_confirm_string . ' (or follow URL)';
    	    $url = SERVER_URL . 'confirm_phone.php?user=' . $id . 
    	        '&phone=' . $phone . '&phoneconfirmstr=' . $phone_confirm_string;
    	    Notification::sendShortSMS($user, 'Worklist phone validation', $message, $url, true);
    	    echo "Ok\n";
        } catch (Exception $e) {
            echo "Failed\n";
        }
    } else {
        echo "Failed\n";
    }
    
    $i++;
}

echo 'Process done: ' . $i . ' numbers processed.';
