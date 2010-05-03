<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request to get love sent to an user

include("config.php");
include("class.session_handler.php");
include('functions.php');

if (!isset($_SESSION['userid'])) {
    echo 'error: unauthorized';
    return;
}

if (empty($_REQUEST['id'])) {
    echo 'error: args';
    return;
}

// From user
$m_userid = intval($_SESSION['userid']);
$m_user = new User();
$m_user->findUserById($m_userid);
$m_username = $m_user->getUsername();
$m_email = mysql_real_escape_string($m_username);

// Sent to user
$userid = intval($_REQUEST['id']);
$user = new User();
$user->findUserById($userid);
$username = $user->getUsername();
$email = mysql_real_escape_string($username);

$db = mysql_connect("localhost", "project_tofor", "test30");
if (!$db) {
    echo 'error: DB connection';
    return;
}
mysql_select_db("sendlove_dev", $db);

$sql = "SELECT `why`, TIMESTAMPDIFF(SECOND,`at`,NOW()) AS `when` 
        FROM `love` LEFT JOIN `users` ON 
        `love`.`giver`  = `users`.`username` AND `users`.`company_id` = `love`.`company_id` 
        WHERE `username` = '$m_email' AND `receiver` = '$email' ORDER BY `at` DESC LIMIT 20";

$loveArray = array();
$res = mysql_query($sql);

if ($res) {
    while ($row = mysql_fetch_assoc($res)) {
        $loveArray[] = $row;
    }
} else {
    echo 'error: query error';
    return;
}

echo json_encode($loveArray);

mysql_close($db);

?>