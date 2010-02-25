<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");
require 'workitem.class.php';

$blankbid = array('id' => 0, 
				  'bidder_id' => 0,
				  'worklist_id' => 0,
				  'email' => '*not displayed*',
				  'bid_amount' => '0',
				  'done_by' => '',
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
	$workItem->getWorkItemByBid($item);
	$workitem = $workItem->getWorkItem($bid->workitem_id);
	// Runner, item owner, or bidder can see item.
	if ($user->isRunner() || ($user->getId() == $workitem['owner_id']) || ($user->getId() == $bid->bidder_id)) {
		$bid->setAnyAccepted($workItem->hasAcceptedBids($bid->workitem_id));
		$json = json_encode($bid->toArray());
		echo $json;
	} else {
		echo $blankjson;
	}
}

