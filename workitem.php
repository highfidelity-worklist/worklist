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
require_once 'lib/Sms.php';

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
if (!defined("WORKLIST_URL")) {
    define("WORKLIST_URL",SERVER_URL . "worklist.php?$get_variable=");
}
$worklist_id = isset($_REQUEST[$get_variable]) ? intval($_REQUEST[$get_variable]) : 0;
$is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';

//initialize user accessing the page
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

//initialize the workitem class
$workitem = new WorkItem();
$workitem->loadById($worklist_id);
$mechanic_id = $user->getId();
$redirectToDefaultView = false;
$redirectToWorklistView = false;

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
}else if (isset($_POST['newcomment'])) {
	$comment = new Comment();
	if (isset($_POST['worklist_id']) && !empty($_POST['worklist_id'])) {
		$comment->setWorklist_id((int) $_POST['worklist_id']);
	}
	if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
		$comment->setUser_id((int) $_POST['user_id']);
	}
	if (isset($_POST['comment_id']) && !empty($_POST['comment_id'])) {
		$comment->setComment_id((int) $_POST['comment_id']);
	}
	if (isset($_POST['comment']) && !empty($_POST['comment'])) {
		$comment->setComment(nl2br($_POST['comment']));
	}
	$comment->save();

	$journal_message .= $_SESSION['nickname'] . " posted a comment on issue #$worklist_id: " . $workitem->getSummary();

	workitemNotify(array('type' => 'comment', 
		      'workitem' => $workitem,
		      'recipients' => array('creator', 'runner', 'mechanic')),
		       array('who' => $_SESSION['nickname'],
			     'comment' => nl2br($_POST['comment'])));

	$redirectToDefaultView = true;
}

// Save WorkItem was requested. We only support Update here
$notifyEmpty = true;
if($action =='save_workitem') {

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
	    changeStatus($workitem, $status, $user);
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
	$notifyEmpty = false;
    }

 	$redirectToWorklistView = true;
    $journal_message .= $_SESSION['nickname'] . " updated item #$worklist_id: $summary.  $new_update_message";
}



if($action =='status-switch' && $user->getId() > 0){

    $status = $_POST['quick-status'];
    changeStatus($workitem, $status, $user);
    $workitem->save();
    $new_update_message = "Status set to $status. ";
    $notifyEmpty = false;
    $journal_message = $_SESSION['nickname'] . " updated item #$worklist_id: " . $workitem->getSummary() . ".  $new_update_message";
}

    if(!$notifyEmpty){
	  workitemNotify(array('type' => 'modified',
			      'workitem' => $workitem,
			      'recipients' => array('runner', 'creator', 'mechanic')), 
			  array('changes' => $new_update_message));
    }

if (isset($_SESSION['userid']) && $action =="place_bid"){
    $args = array('bid_amount','done_by', 'notes', 'mechanic_id');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }
    if ($_SESSION['timezone'] == '0000') $_SESSION['timezone'] = '+0000';
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
    $journal_message = "A bid of ".number_format($bid_amount,2)." was placed on item #$worklist_id: $summary.";

    //sending email to the runner of worklist item
    $row = $workitem->getRunnerSummary($worklist_id);
    if(!empty($row)) {
	$id = $row['id'];
        $summary = $row['summary'];
        $username = $row['username'];
    }

    // notify runner of new bid
    workitemNotify(array('type' => 'bid_placed',
			 'workitem' => $workitem,
			 'recipients' => array('runner')), 
		    array('done_by' => $done_by,
			  'bid_amount' => $bid_amount,
			  'notes' => $notes,
			  'bid_id' => $bid_id));

    $runner = new User();
    $runner->findUserById($workitem->getRunnerId());
    try {
        $smsMessage = new Sms_Message($runner, 'Bid placed', $journal_message);
        $smsBackend = Sms::createBackend($smsMessage, null, array(
            'mailFrom'    => $mail_user['smsuser']['from'],
            'mailReplyTo' => $mail_user['smsuser']['replyto']
        ));
        $smsBackend->send($smsMessage);
    } catch (Sms_Backend_Exception $e) {
    }

//    sl_notify_sms_by_id($workitem->getOwnerId(), 'Bid placed', $journal_message);

    $redirectToDefaultView = true;
}


// Request submitted from Add Fee popup
if (isset($_SESSION['userid']) && $action == "add_fee") {
    $args = array('itemid', 'fee_amount', 'fee_category', 'fee_desc', 'mechanic_id', 'is_expense');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }
    $journal_message = AddFee($itemid, $fee_amount, $fee_category, $fee_desc, $mechanic_id, $is_expense);

    // notify runner of new fee
    workitemNotify(array('type' => 'fee_added',
			 'workitem' => $workitem,
			 'recipients' => array('runner')), 
		    array('fee_adder' => $user->getNickname(),
			  'fee_amount' => $fee_amount));


    $runner = new User();
    $runner->findUserById($workitem->getRunnerId());
    try {
        $smsMessage = new Sms_Message($runner, 'Fee added', $journal_message);
        $smsBackend = Sms::createBackend($smsMessage, null, array(
            'mailFrom'    => $mail_user['smsuser']['from'],
            'mailReplyTo' => $mail_user['smsuser']['replyto']
        ));
        $smsBackend->send($smsMessage);
    } catch (Sms_Backend_Exception $e) {
    }

    $redirectToDefaultView = true;
}

// Accept a bid
if ($action=='accept_bid'){
    $bid_id = intval($_REQUEST['bid_id']);
    //only runners can accept bids

	if (($is_runner == 1 || $workitem->getRunnerId() == $_SESSION['userid']) && !$workitem->hasAcceptedBids() && (strtoupper($workitem->getStatus()) == "BIDDING")) {
		// query to get a list of bids (to use the current class rather than breaking uniformity)
		// I could have done this quite easier with just 1 query and an if statement..
		$bids = (array) $workitem->getBids($workitem -> getId());
		$exists = false;
		foreach ($bids as $array) {
			if ($array['id'] == $bid_id) {
				$exists = true;
				break;
			}
		}
		if ($exists) {
			$bid_info = $workitem->acceptBid($bid_id);

			// Journal notification
			$journal_message .= $_SESSION['nickname'] . " accepted {$bid_info['bid_amount']} from ". $bid_info['nickname'] . " on item #$item_id: " . $bid_info['summary'] . ". Status set to WORKING.";
	  
			// mail notification
			workitemNotify(array('type' => 'bid_accepted', 
					     'workitem' => $workitem,
					     'recipients' => array('mechanic')));

            $bidder = new User();
            $bidder->findUserById($bid_info['bidder_id']);
            try {
                $smsMessage = new Sms_Message($bidder, 'Bid accepted', $journal_message);
                $smsBackend = Sms::createBackend($smsMessage, null, array(
                    'mailFrom'    => $mail_user['smsuser']['from'],
                    'mailReplyTo' => $mail_user['smsuser']['replyto']
                ));
                $smsBackend->send($smsMessage);
            } catch (Sms_Backend_Exception $e) {
            }
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
if($redirectToWorklistView) {
    $postProcessUrl = WORKLIST_URL . $worklist_id;
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
// Suppress names if not is_runner, or creator of Item. Still show if it's user's bid.

$currentUserHasBid = "false";
if(!empty($bids) && is_array($bids)) {
    foreach($bids as &$bid) {
        if($bid['email'] == $currentUsername ){
            $currentUserHasBid = "true";
            //break;
        }
        if ((!$user->isRunner()) && ($user->getId() != $worklist['creator_id'])) {
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

function changeStatus($workitem,$newStatus, $user){

    $allowable = array("SUGGESTED", "REVIEW", "SKIP");
    
    if($user->getIs_runner() == 1){

	  if($newStatus == 'BIDDING' && in_array($workitem->getStatus(), $allowable)){

		$workitem->setRunnerId($user->getId());	    
	  }

	  $workitem->setStatus($newStatus);

    }else{

	$workitem->setStatus($newStatus);
    }
}

function workitemNotify($options, $data = null){

	$recipients = $options['recipients'];
	$workitem = $options['workitem'];
	$itemId = $workitem -> getId();
	$itemLink = '<a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>#' . $itemId 
			    . '</a> (' . $workitem -> getSummary() . ')';
	$itemTitle = '#' . $itemId  . ' (' . $workitem -> getSummary() . ')';
	$body = '';
	$subject = '';

	switch($options['type']){

	    case 'comment':
    
		  $subject = 'New comment: ' . $itemTitle;
		  $body = 'New comment was added to the item ' . $itemLink . '.<br>';
		  $body .= $data['who'] . ' says:<br />'
			    . $data['comment'];

	    break;

	    case 'fee_added':

		  $subject = 'Fee added: ' . $itemTitle;
		  $body = 'New fee was added to the item ' . $itemLink . '.<br>'
			. 'Who: ' . $data['fee_adder'] . '<br>'
			. 'Amount: ' . $data['fee_amount'];    

	    break;

	    case 'bid_accepted':

		  $subject = 'Bid accepted: ' . $itemTitle;
		  $body = 'Your bid was accepted for ' . $itemLink . '<br>'
			. 'Promised by: ' . $_SESSION['nickname'];

	    break;

	    case 'bid_placed':

		  $subject = 'New bid: ' . $itemTitle;
		  $body =  'New bid was placed for ' . $itemLink . '<br>'
			 . 'Details of the bid:<br>'
			 . 'Bidder Email: ' . $_SESSION['username'] . '<br>'
			 . 'Done By: ' . $data['done_by'] . '<br>'
			 . 'Bid Amount: ' . $data['bid_amount'] . '<br>'
			 . 'Notes: ' . $data['notes'] . '<br>';

		  $urlacceptbid = '<br><a href=' . SERVER_URL . 'workitem.php';
		  $urlacceptbid .= '?job_id=' . $itemId . '&bid_id=' . $data['bid_id'] . '&action=accept_bid>Click here to accept bid.</a>';
		  $body .=  $urlacceptbid;

	    break;

	    case 'modified':

		  $subject = "Item modified: ".$itemTitle;
		  $body =  $_SESSION['nickname'] . ' updated item ' . $itemLink . '<br>'
			 . $data['changes'];

	    break;

	}

	$body .= '<p>Love,<br/>Workitem</p>';
	
	$emails = array();
	foreach($recipients as $recipient){
		$recipientUser = new User();
		$method = 'get' . ucfirst($recipient) . 'Id'; 
		$recipientUser->findUserById($workitem->$method());

		// do not send mail to the same user making changes 
		if(($username = $recipientUser->getUsername()) && $recipientUser->getId() != getSessionUserId()){
	      
			// check if we already sending email to this user
			if(!in_array($username, $emails)){

				$emails[] = $username;
			}
		}
	}

	foreach($emails as $email){

		sl_send_email($email, $subject, $body);
	}
}
