<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request to add/update a rewarder user

include("config.php");
include("class.session_handler.php");
include("functions.php");

if (!isset($_SESSION['userid'])) {
    echo 'error: unauthorized';
    return;
}

$user = new User();
$user->findUserById($_SESSION['userid']);

$rewarderList = GetRewarderUserList($_SESSION['userid']);
$json = json_encode(array($user->getRewarder_points(), $rewarderList));
echo $json;
