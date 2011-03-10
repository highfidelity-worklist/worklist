<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
require_once("config.php");
require_once("class.session_handler.php");
include_once("check_new_user.php"); 
require_once("functions.php");
require_once("send_email.php");
require_once("update_status.php");
require_once("workitem.class.php");
require_once('lib/Agency/Worklist/Filter.php');
require_once('classes/UserStats.class.php');
require_once('classes/Repository.class.php');

$page=isset($_REQUEST["page"])?intval($_REQUEST["page"]):1; //Get the page number to show, set default to 1
$is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
$is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
$journal_message = '';
$nick = '';

$workitem = new WorkItem();

$userId = getSessionUserId();
if( $userId > 0 )	{
  initUserById($userId);
	$user = new User();
	$user->findUserById( $userId );
	$nick = $user->getNickname();
	$userbudget =$user->getBudget();
	$budget = number_format($userbudget);
}

$filter = new Agency_Worklist_Filter();
$filter->setName('.worklist')
       ->initFilter();

if ($userId > 0 ) {
    $args = array( 'itemid', 'summary', 'project_id', 'skills', 'status', 'notes', 'bid_fee_desc', 'bid_fee_amount',
                   'bid_fee_mechanic_id', 'invite', 'is_expense', 'is_rewarder');
    foreach ($args as $arg) {
    		// Removed mysql_real_escape_string, because we should 
    		// use it in sql queries, not here. Otherwise it can be applied twice sometimes
        $$arg = !empty($_POST[$arg])?$_POST[$arg]:'';
    }

    $creator_id = $userId;

    if (!empty($_POST['itemid'])) {
        $workitem->loadById($_POST['itemid']);
        $journal_message .= $nick . " updated ";
    } else {
        $workitem->setCreatorId($creator_id);
        $journal_message .= $nick . " added ";
    }
    $workitem->setSummary($summary);

    // not every runner might want to be assigned to the item he created - only if he sets status to 'BIDDING'
    if($status == 'BIDDING' && ($user->getIs_runner() == 1 || $user->getBudget() > 0)){
        $runner_id = $userId;
    }else{
        $runner_id = 0;
    }

    $skillsArr = explode(', ', $skills);

    $workitem->setRunnerId($runner_id);
    $workitem->setProjectId($project_id);
    $workitem->setStatus($status);
    $workitem->setNotes($notes);
    $workitem->setWorkitemSkills($skillsArr);
    $workitem->save();

    Notification::statusNotify($workitem);

    if(empty($_POST['itemid']))  {
        $bid_fee_itemid = $workitem->getId();
        $journal_message .= " item #$bid_fee_itemid: $summary. ";
        if (!empty($_POST['files'])) {
            $files = explode(',', $_POST['files']);
            foreach ($files as $file) {
                $sql = 'UPDATE `' . FILES . '` SET `workitem` = ' . $bid_fee_itemid . ' WHERE `id` = ' . (int)$file;
                mysql_query($sql);
            }
        }
    } else {
        $bid_fee_itemid = $itemid;
        $journal_message .=  "item #$itemid: $summary. ";
    }

    if (!empty($_POST['invite'])) {
        $people = explode(',', $_POST['invite']);
        invitePeople($people, $bid_fee_itemid, $summary, $notes);
    }

    if ($bid_fee_amount > 0) {
        $journal_message .= AddFee($bid_fee_itemid, $bid_fee_amount, 'Bid', $bid_fee_desc, $bid_fee_mechanic_id, $is_expense, $is_rewarder);
    }
} else {
    echo json_encode(array( 'error' => "Invalid parameters !"));
    return;
}

if (!empty($journal_message)) {
    //sending journal notification
    $data = array();
    $data['user'] = JOURNAL_API_USER;
    $data['pwd'] = sha1(JOURNAL_API_PWD);
    $data['message'] = stripslashes($journal_message);
    $prc = postRequest(JOURNAL_API_URL, $data,1);
}

echo json_encode(array( 'return' => "Done!"));
