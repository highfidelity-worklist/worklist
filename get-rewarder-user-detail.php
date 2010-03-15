<?php
//  vim:ts=4:et

//  Copyright (c) 2009, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request to add/update a rewarder user

include("config.php");
include("class.session_handler.php");
include("functions.php");

if (!isset($_SESSION['userid']) || empty($_SESSION['is_auditor'])) {
    echo 'error: unauthorized';
    return;
}

if (empty($_REQUEST['id'])) {
    echo 'error: args';
    return;
}

$userid = intval($_REQUEST['id']);

$user = new User();
$user->findUserById($userid);

$query = "SELECT `nickname` as `giver_nickname`, `".REWARDER."`.`rewarder_points` FROM `".REWARDER."` ".
    "LEFT JOIN `".USERS."` ON `".USERS."`.`id`=`".REWARDER."`.`giver_id` ".
    "WHERE `receiver_id`='$userid' AND `".REWARDER."`.`rewarder_points` > 0 ORDER BY `giver_nickname`";
$rt = mysql_query($query);
$rewarderList = array();
while ($rt && ($row = mysql_fetch_assoc($rt))) {
    $rewarderList[] = array($row['giver_nickname'], $row['rewarder_points']);
}

$json = json_encode(array($user->getNickname(), $rewarderList));
echo $json;
