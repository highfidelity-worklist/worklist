<?php
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");
require 'workitem.class.php';

$blankbid = array(
    'id' => 0,
    'bidder_id' => 0,
    'worklist_id' => 0,
    'email' => '*name hidden*',
    'bid_amount' => '0',
    'done_in' => '',
    'notes' => '',
);
$blankjson = json_encode($blankbid);

$item = isset($_REQUEST['item']) ? (int)$_REQUEST['item'] : 0;
if ($item == 0) {
    echo $blankjson;
    return;
}

$userId = getSessionUserId();
$user = new User();
if ($userId > 0) {
    $user = $user->findUserById($userId);
} else {
    $user->setId(0);
}
// Guest or hacking
if ($user->getId() == 0) {
    echo $blankjson;
    return;
}

$bid = new Bid($item);

if ($bid->id) {
    $workItem = new WorkItem();
    $workItem->conditionalLoadByBidId($item);
    // Runner, item creator, or bidder can see item.
    if ($user->isRunner() || ($user->getId() == $workItem->getCreatorId()) || ($user->getId() == $bid->bidder_id)) {
        $bid->setAnyAccepted($workItem->hasAcceptedBids());
        $row = $bid->toArray();
        $row['notes'] = html_entity_decode($row['notes'], ENT_QUOTES);
        $json = json_encode($row);
        echo $json;
    } else {
        echo $blankjson;
    }
}

