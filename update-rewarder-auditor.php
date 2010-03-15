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

if (empty($_REQUEST['id']) || empty($_REQUEST['toggle'])) {
    echo 'error: args';
    return;
}

$userid = intval($_REQUEST['id']);

$query = "SELECT `is_auditor` FROM `".USERS."` WHERE `id`='$userid' AND `confirm`=1";
$rt = mysql_query($query);
if ($rt && ($row = mysql_fetch_assoc($rt))) {
    $is_auditor = $row['is_auditor'] ? 0 : 1;
    $query = "UPDATE `".USERS."` SET `is_auditor`='$is_auditor' WHERE `id`='$userid'";
    $rt = mysql_query($query);
}

echo 'ok';
