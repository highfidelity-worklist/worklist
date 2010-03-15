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

$user = new User();
$user->findUserById($_SESSION['userid']);

$rewarderList = GetRewarderAuditList($_SESSION['userid']);
$json = json_encode($rewarderList);
echo $json;
