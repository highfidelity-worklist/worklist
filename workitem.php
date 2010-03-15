<?php
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history
require_once 'config.php';
require_once 'class.session_handler.php';
require_once 'send_email.php';
require_once 'workitem.class.php';
require_once 'functions.php';


    $statusMapRunner = array("SUGGESTED" => array("BIDDING","SKIP"),
				 "BIDDING" => array("SKIP"),
				 "WORKING" => array("REVIEW"),
				 "REVIEW" => array("WORKING", "DONE"),
				 "DONE" => array("REVIEW"),
				 "SKIP" => array("REVIEW"));

    $statusMapMechanic = array("SUGGESTED" => array("SKIP", "REVIEW"),
				 "WORKING" => array("REVIEW"),
				 "REVIEW" => array("WORKING"),
				 "DONE" => array("WORKING", "REVIEW"),
				 "SKIP" => array("REVIEW"));

$get_variable = 'job_id';

if (!defined("WORKITEM_URL")) {
    define("WORKITEM_URL",SERVER_URL . "workitem.php?$get_variable=");
}

$worklist_id = isset($_REQUEST[$get_variable]) ? intval($_REQUEST[$get_variable]) : 0;
$is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
$currentUsername = $_SESSION['username'];

$userId = getSessionUserId();
$user = new User();
if ($userId > 0) {
    $user->findUserById($userId);
} else {
    $user->setId(0);
}
// TODO: Would be good to take out all the checks for isset($_SESSION['userid'] etc. and have them use $user instead, check $user->getId() > 0.

if(empty($worklist_id)) {
    return;
}
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';
if (isset($_REQUEST['withdraw_bid'])) {
    $action = "withdraw_bid";
}else if(isset($_POST['save_workitem'])) {
    $action = "save_workitem";
}else if(isset($_POST['place_bid'])) {
    $action = "place_bid";
}else if(isset($_POST['add_fee'])) {
    $action = "add_fee";
}else if(isset($_POST['accept_bid'])) {
    $action = "accept_bid";
}else if(isset($_POST['status-switch'])) {
    $action = "status-switch";
}

//initialize the workitem class
$workitem = new WorkItem();
$mechanic_id = $user->getId();
$redirectToDefaultView = false;

// Save WorkItem was requested. We only support Update here
if($action =='save_workitem') {

    $workitem->loadById($worklist_id);
    $args = array('summary','notes', 'status');
    foreach ($args as $arg) {
        $$arg = $_POST[$arg];
    }

    // code to add specifics to journal update messages
    $new_update_message='';

    // summary
    if ($workitem->getSummary() != $summary){
        $workitem->setSummary($summary);
        $new_update_message .= "Summary changed. ";
    }
    // status
    if ($is_runner || (in_array($status, $statusMapMechanic[$workitem->getStatus()]) && array_key_exists($workitem->getStatus(), $statusMapMechanic))) {
        if ($workitem->getStatus() != $status && !empty($status)){
            $workitem->setStatus($status);
            if (!empty($new_update_message)){  // add commas where appropriate
                $new_update_message .= ", ";
            }
            $new_update_message .= "Status set to $status. ";
        }
    }
    if ($workitem->getNotes() != $notes) {
        $workitem->setNotes($notes);
        $new_update_message .= "Notes changed. ";
    }

    // Send invites
    if (!empty($_POST['invite'])) {
        $people = explode(',', $_POST['invite']);
        invitePeople($people, $worklist_id, $workitem->getSummary(), $workitem->getNotes());
        $new_update_message .= "Invitations sent. ";
    }

    if (empty($new_update_message)){
        $new_update_message = " No changes.";
    } else {
        $workitem->save();
        $new_update_message = " Changes: $new_update_message";
    }

    $redirectToDefaultView = true;
    $journal_message .= $_SESSION['nickname'] . " updated item #$worklist_id: $summary.  $new_update_message";
}

if($action =='status-switch' && $user->getId() > 0){

    $workitem->loadById($worklist_id);
    $status = $_POST['quick-status'];
    $workitem->setStatus($status);
    $workitem->save();
    $new_update_message = "Status set to $status. ";
    $journal_message = $_SESSION['nickname'] . " updated item #$worklist_id: " . $workitem->getSummary() . ".  $new_update_message";
}


if (isset($_SESSION['userid']) && $action =="place_bid"){
    $args = array('bid_amount','done_by', 'notes', 'mechanic_id');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }

    $summary = getWorkItemSummary($worklist_id);


    if($mechanic_id != $_SESSION['userid'])
    {
        $row = $workitem->getUserDetails($mechanic_id);
        if(!empty($row)){
            $nickname = $row['nickname'];
            $username = $row['username'];
        }
        else
        {
            $username = "unknown-{$username}";
            $nickname = "unknown-{$mechanic_id}";
        }
    }
    else
    {
        $mechanic_id = $_SESSION['userid'];
        $username = $_SESSION['username'];
        $nickname = $_SESSION['nickname'];
    }

    $bid_id = $workitem->placeBid($mechanic_id,$username,$worklist_id,$bid_amount,$done_by,$_SESSION['timezone'],$notes);


    // Journal notification
    $journal_message = "A bid of \${$bid_amount} was placed on item #$worklist_id: $summary.";

    //sending email to the owner of worklist item
    $row = $workitem->getOwnerSummary($worklist_id);
    if(!empty($row)) {
	$id = $row['id'];
        $summary = $row['summary'];
        $username = $row['username'];
        $ownerIsRunner = $row['is_runner'];
    }

    sendMailToOwner($worklist_id, $bid_id, $summary, $username, $done_by, $bid_amount, $notes, $ownerIsRunner);
    $workitem->loadById($worklist_id);
    sl_notify_sms_by_id($workitem->getOwnerId(), 'Bid placed', $journal_message); 

    $redirectToDefaultView = true;
}


// Request submitted from Add Fee popup
if (isset($_SESSION['userid']) && $action == "add_fee") {
    $args = array('itemid', 'fee_amount', 'fee_category', 'fee_desc', 'mechanic_id');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }
    $journal_message = AddFee($itemid, $fee_amount, $fee_category, $fee_desc, $mechanic_id);
    $workitem->loadById($_POST['itemid']);
    sl_notify_sms_by_id($workitem->getOwnerId(), 'Fee added', $journal_message);
    $redirectToDefaultView = true;
}

// Accept a bid
if ($action=='accept_bid'){
    $bid_id = intval($_REQUEST['bid_id']);
    //only runners can accept bids
	//commenting out extra query which makes no sense
	//$item_id = $workitem->getWorkItemByBid($bid_id);
	$item_id = intval($_REQUEST['job_id']);
	$workitem->loadById($item_id);
	if (($is_runner == 1 || $workitem->getOwnerId() == $user->getId()) && !$workitem->hasAcceptedBids($item_id) && (strtoupper($workitem->getStatus()) == "BIDDING")) {
		// query to get a list of bids (to use the current class rather than breaking uniformity)
		// I could have done this quite easier with just 1 query and an if statement..
		$bids = (array) $workitem->getBids($item_id);
		$exists = false;
		foreach ($bids as $array) {
			if ($array['id'] == $bid_id) {
				$exists = true;
				break;
			}
		}
		if ($exists) {
			$bid_info = $workitem->acceptBid($bid_id);
			//commenting out redundancy, uncomment later if needed.. highly unlikely
			//$item_id = $workitem->getWorkItemByBid($bid_id);

			// Journal notification
			$journal_message .= $_SESSION['nickname'] . " accepted {$bid_info['bid_amount']} from ". $bid_info['nickname'] . " on item #$item_id: " . $bid_info['summary'] . ". Status set to WORKING.";

			//sending email to the bidder
			$subject = "Bid accepted: " . $bid_info['summary'];
			$body = "Your bid was accepted for <a href='" . SERVER_URL . "workitem.php?job_id=$item_id'>#$item_id</a><br>\n";
			$body .= "Promised by: {$_SESSION['nickname']}</p>";
			$body .= "<p>Love,<br/>Worklist</p>";
			sl_send_email($bid_info['email'], $subject, $body);
			sl_notify_sms_by_id($bid_info['bidder_id'], $subject, $body);
			$redirectToDefaultView = true;

			// Send email to not accepted bidders
			sendMailToDiscardedBids($worklist_id);
		}
		else {
			$_SESSION['workitem_error'] = "Failed to accept bid, bid has been withdrawn!";
			$redirectToDefaultView = true;
		}
	}
}

//Withdraw a bid
if (isset($_SESSION['userid']) && $action == "withdraw_bid") {
    if (isset($_REQUEST['bid_id'])) {
        withdrawBid(intval($_REQUEST['bid_id']));
    } else {
        $fee_id = intval($_REQUEST['fee_id']);
        $res = mysql_query('SELECT bid_id FROM `' . FEES . '` WHERE `id`=' . $fee_id);
        $fee = mysql_fetch_object($res);
        if ((int)$fee->bid_id !== 0) {
            withdrawBid($fee->bid_id);
        } else {
            deleteFee($fee_id);
        }
    }
    $redirectToDefaultView = true;
}

if($redirectToDefaultView) {
    $postProcessUrl = WORKITEM_URL . $worklist_id;
}

// We have a Journal message. Send it to Journal
if(isset($journal_message)) {
    sendJournalNotification($journal_message);
    //$postProcessUrl = WORKITEM_URL . $worklist_id . "&msg=" . $journal_message;
}
// if a post process URL was set, redirect and die
if(isset($postProcessUrl)) {
    header("Location: " . $postProcessUrl);
    die();
}
// handle the makeshift error I made..
$erroneous = false;
if (isset($_SESSION['workitem_error'])) {
	$erroneous = true;
	$the_errors = $_SESSION['workitem_error'];
	unset($_SESSION['workitem_error']);
}
// Process the request normally and display the page.

//get worklist
$worklist = $workitem->getWorkItem($worklist_id);

//get bids
$bids = $workitem->getBids($worklist_id);

//Findout if the current user already has any bids.
// Yes, it's a String instead of boolean to make it easy to use in JS.
// Suppress names if not is_runner, or owner of Item. Still show if it's user's bid.

$currentUserHasBid = "false";
if(!empty($bids) && is_array($bids)) {
    foreach($bids as &$bid) {
        if($bid['email'] == $currentUsername ){
            $currentUserHasBid = "true";
            //break;
        }
        if ((!$user->isRunner()) && ($user->getId() != $worklist['owner_id'])) {
            if ($user->getId() != $bid['bidder_id']) {
                $bid['nickname'] = '*name hidden*';
            }
        }
    }
}
// break reference to $bid
unset($bid);
//get fees
$fees = $workitem->getFees($worklist_id);

//total fee
$total_fee = $workitem->getSumOfFee($worklist_id);
include "workitem.inc";

function sendMailToDiscardedBids($worklist_id)	{
    // Get all bids marked as not accepted
    $query = "SELECT bids.email, u.nickname FROM ".BIDS." as bids
					INNER JOIN ".USERS." as u on (u.id = bids.bidder_id)
					WHERE bids.worklist_id=$worklist_id and bids.withdrawn = 0 AND bids.accepted = 0";
    $result_query = mysql_query($query);
    $bids = array();
    while($row = mysql_fetch_assoc($result_query)) {
        $bids[] = $row;
    }

    $workitem = new WorkItem();
    $item = $workitem->getWorkItem($worklist_id);

    foreach( $bids as $bid )	{
        $subject = "Job Filled: ".$item['summary'];
        $body = "<p>Hey ".$bid['nickname'].",</p>";
        $body .= "<p>Thanks for adding your bid to <a href='".SERVER_URL."workitem.php?job_id=".$item['id']."'>#".$item['id']."</a> '".$item['summary']."'. This job has just been filled by another mechanic.</br></p>";
        $body .= "There are lots of work to be done so please keep checking the <a href='".SERVER_URL."'>worklist</a> and bidding!</br></p><p>Thanks!</p>LoveMachine</p>";

        sl_send_email($bid['email'], $subject, $body);
    }
}

function sendMailToOwner($itemid, $bid_id, $summary, $username, $done_by, $bid_amount, $notes, $is_runner) {
    $subject = "new bid: ".$summary;
    $body =  "<p>New bid was placed for worklist item #$itemid: \"$summary\"<br/>";
    $body .= "Details of the bid:<br/>";
    $body .= "Bidder Email: ".$_SESSION['username']."<br/>";
    $body .= "Done By: ".$done_by."<br/>";
    $body .= "Bid Amount: ".$bid_amount."<br/>";
    $body .= "Notes: ".$notes."</p>";
    if ($is_runner) {
        $urlacceptbid = '<br><a href='.SERVER_URL.'workitem.php';
        $urlacceptbid .= '?job_id='.$itemid.'&bid_id='.$bid_id.'&action=accept_bid>Click here to accept bid.</a>';
        $body .=  $urlacceptbid;
    }
    $body .= "<p>Love,<br/>Workitem</p>";
    sl_send_email($username, $subject, $body);
}
