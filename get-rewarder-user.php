<?php
//  vim:ts=4:et

//  Copyright (c) 2009, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request to get info for single rewarder user

include("config.php");
include("class.session_handler.php");
include("functions.php");

if (!isset($_SESSION['userid'])) {
    echo 'error: unauthorized';
    return;
}

if (empty($_REQUEST["id"])) {
    echo 'error: args';
    return;
}

$id = intval($_REQUEST["id"]);

$newUser = false;
$user = new User();
$user->findUserById($_SESSION['userid']);
$availPoints = $user->getRewarder_points();

$query = "SELECT `rewarder_points` FROM `".REWARDER."` WHERE `giver_id`='".$_SESSION['userid']."' AND `receiver_id`='$id'";
$rt = mysql_query($query);
if ($rt && ($row = mysql_fetch_assoc($rt))) {
        $points = $row['rewarder_points'];
} else {
	$points = 0;
}

$json = json_encode(array('rewarded' => $points, 'available' => $availPoints));
echo $json;
