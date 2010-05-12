<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request to get info for single rewarder user

include("apifunctions.php");

if (!isset($_SESSION['userid'])) {
    echo 'error: unauthorized';
    return;
}

if (empty($_REQUEST["id"])) {
    echo 'error: args';
    return;
}

$id = intval($_REQUEST["id"]);
$points = intval($_REQUEST["points"]);

$data = rewardUser(getSessionUserId(), $id, $points);
$json = json_encode($data);
echo $json;
