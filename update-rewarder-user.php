<?php
//  vim:ts=4:et

//  Copyright (c) 2009, LoveMachine Inc.  
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

if (empty($_REQUEST["id"])) {
    echo 'error: args';
    return;
}

$id = intval($_REQUEST["id"]);
$points = isset($_REQUEST["points"]) ? max(0, intval($_REQUEST["points"])) : 0;
$delete = isset($_REQUEST["delete"]) ? intval($_REQUEST["delete"]) : 0;

$newUser = false;
$user = new User();
$user->findUserById($_SESSION['userid']);
$availPoints = $user->getRewarder_points();

$query = "SELECT `rewarder_points` FROM `".REWARDER."` WHERE `giver_id`='".$_SESSION['userid']."' AND `receiver_id`='$id'";
$rt = mysql_query($query);
if ($rt && ($row = mysql_fetch_assoc($rt))) {
    if (!$delete) {
        $delta = min($availPoints, $points - $row['rewarder_points']);
        $points = $row['rewarder_points'] + $delta;
        $query = "UPDATE `".REWARDER."` SET `rewarder_points`='$points' WHERE `giver_id`='".$_SESSION['userid']."' AND `receiver_id`='$id'";
    } else {
        $delta = -$row['rewarder_points'];
        $query = "DELETE FROM `".REWARDER."` WHERE `giver_id`='".$_SESSION['userid']."' AND `receiver_id`='$id'";
    }
} else {
    $newUser = true;
    $delta = min($availPoints, $points);
    $points = $delta;
    $query = "INSERT INTO `".REWARDER."` (`giver_id`,`receiver_id`,`rewarder_points`) VALUES ('".$_SESSION['userid']."','$id','$points')";
}

if ($newUser || $delete || $delta != 0) {
    $rt = mysql_query($query);
    if ($rt) {
        $user->setRewarder_points($availPoints - $delta);
        $user->save();
    }
}

$rewarderList = GetRewarderUserList($_SESSION['userid']);
$json = json_encode(array($user->getRewarder_points(), $rewarderList));
echo $json;
