<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

ini_set('display_errors', 1);

include_once(dirname(__FILE__) . '/config.php');

include_once(dirname(__FILE__) . '/chat.class.php');

if (empty($_SERVER['HTTPS'])) {
    echo "error: insecure request.";
    exit;
}

// @TODO: Isn't database connection handled elsewhere, config.php?
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD) or die(mysql_error());;
mysql_select_db(DB_NAME) or die(mysql_error());

// Password should already be sha1 encoded
$username = isset($_REQUEST['user']) ? mysql_real_escape_string($_REQUEST['user']) : '';
$password = isset($_REQUEST['pwd']) ? mysql_real_escape_string($_REQUEST['pwd']) : '';
$sql = "select id, nickname from ".USERS." where username='$username' and password='$password' and confirm='1'";
if (! $res = mysql_query($sql)) { error_log("jadd.mysql: ".mysql_error()); }

if($res && mysql_num_rows($res) > 0) {
    $row = mysql_fetch_assoc($res);

    $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';
    $data = $chat->sendEntry($row['nickname'], $message, array('userid' => $row['id']), false, false);
    
    
    if($data['status'] == 'ok') {
        echo "ok";
    } else {
        echo "error: failed while writing entry with status: {$data['status']}.";
    }
} else {
    echo "error: invalid user.";
}
