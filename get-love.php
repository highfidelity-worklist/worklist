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
$fromUser = new User();
$fromUser->findUserById($_SESSION['userid']);
$fromUsername = mysql_real_escape_string($fromUser->getUsername());

// Sent to user
$user = new User();
$user->findUserById($_REQUEST['id']);
$username = mysql_real_escape_string($user->getUsername());

$love = getUserLove($username, $fromUsername);
$total_love = getUserLove($username);

echo json_encode(array($love, $total_love));

?>