<?php
//  vim:ts=4:et
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
require_once 'config.php';
require_once 'class.session_handler.php';
require_once 'send_email.php';
require_once 'update_status.php';
require_once 'workitem.class.php';
require_once 'classes/Project.class.php';
require_once 'functions.php';
require_once 'timezones.php';
require_once 'lib/Sms.php';
require_once 'classes/Repository.class.php';
require_once 'models/DataObject.php';
require_once 'models/Users_Favorite.php';

    $statusMapRunner = array("DRAFT" => array("SUGGESTED","BIDDING"),
                 "SUGGESTED" => array("BIDDING","PASS"),
                 "SUGGESTEDwithBID" => array("BIDDING","PASS"),
                 "BIDDING" => array("PASS"),
                 "WORKING" => array("REVIEW", "FUNCTIONAL"),
                 "FUNCTIONAL" => array("REVIEW", "WORKING"),
                 "REVIEW" => array("WORKING", "COMPLETED", "DONE"),
                 "COMPLETED" => array("REVIEW", "DONE"),
                 "DONE" => array("REVIEW"),
                 "PASS" => array("REVIEW"));

    $statusMapMechanic = array("SUGGESTED" => array("PASS", "REVIEW"),
                 "SUGGESTEDwithBID" => array("PASS"),
                 "WORKING" => array("FUNCTIONAL"),
                 "FUNCTIONAL" => array("REVIEW", "WORKING"),
                 "REVIEW" => array("COMPLETED", "WORKING"),
                 "COMPLETED" => array("WORKING"),
                 "DONE" => array("WORKING", "FUNCTIONAL"),
                 "PASS" => array("FUNCTIONAL"));

$get_variable = 'job_id';
if (! defined("WORKITEM_URL")) { define("WORKITEM_URL", SERVER_URL . "workitem.php?$get_variable="); }
if (! defined("WORKLIST_REDIRECT_URL")) { define("WORKLIST_REDIRECT_URL", SERVER_URL . "worklist.php?$get_variable="); }
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

if (empty($worklist_id)) {
    return;
} else {
    // feed links will be made specific to the workitem
    $inWorkItem = true;
}

//Set an empty variable for $journal_message to avoid errors/warnings with .=
$journal_message = null;

//initialize the workitem class
$workitem = new WorkItem();
try {
    $workitem->loadById($worklist_id);
} catch(Exception $e) {
    $error  = $e->getMessage();
    die($error);
}

$mechanic_id = $user->getId();
$redirectToDefaultView = false;
$redirectToWorklistView = false;
$promptForReviewUrl = true;
$runner_budget = $user->getBudget();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

if (isset($_REQUEST['withdraw_bid'])) {
    $action = "withdraw_bid";
} else if(isset($_POST['save_workitem'])) {
    $action = "save_workitem";
} else if(isset($_POST['place_bid'])) {
    $action = "place_bid";
} else if(isset($_POST['swb'])) {
    $action = "swb";
} else if(isset($_POST['edit_bid'])) {
    $action = "edit_bid";
} else if(isset($_POST['add_fee'])) {
    $action = "add_fee";
} else if (isset($_POST['add_tip'])) {
    $action = "add_tip";
} else if(isset($_POST['accept_bid'])) {
    $action = "accept_bid";
} else if(isset($_POST['accept_multiple_bid'])) {
    $action = "accept_multiple_bid";
} else if(isset($_POST['status-switch'])) {
    $action = "status-switch";
} else if(isset($_POST['save-review-url'])) {
    $action = "save-review-url";
} else if(isset($_POST['invite-people'])) {
    $action = "invite-people";
} else if (isset($_POST['newcomment'])) {
    $action = 'new-comment';
} else if (isset($_POST['start_codereview'])) {
    $action = "start_codereview";
} else if (isset($_POST['finish_codereview'])) {
    $action = "finish_codereview";
} else if (isset($_POST['cancel_codereview'])) {
    $action = "cancel_codereview";
}

// for any other action user has to be logged in
if ($action != 'view') {
    checkLogin();
    $action_error = '';
    $action = $workitem->validateAction($action, &$action_error);
}

// Save WorkItem was requested. We only support Update here
$notifyEmpty = true;
$message_title = '';
if ($action =='save_workitem') {
    $args = array('summary', 'notes', 'status', 'project_id', 'sandbox', 'skills',
                'is_bug', 'bug_job_id');
    foreach ($args as $arg) {
        if (!empty($_POST[$arg])) {
            $$arg = $_POST[$arg];
        } else {
            $$arg = '';
        }
    }

    // code to add specifics to journal update messages
    $new_update_message='';
    $is_bug = !empty($_REQUEST['is_bug'])? 1 : 0;
    // First check to see if this is marked as a bug
    if ($workitem->getIs_bug() != $is_bug) {
        error_log("bug changed it");
        if($is_bug) {
	        $new_update_message .= 'Marked as a bug. ';
        } else {
	        $new_update_message .= 'Marked as not being a bug. ';
        }
        if ($message_title == '') {
            $message_title = 'Bug';
        } else {
            $message_title .= ' / Bug';
        }
    }
	$workitem->setIs_bug($is_bug);

    // summary
    if (isset($_POST['summary']) && $workitem->getSummary() != $summary) {
        $workitem->setSummary($summary);
        if ($workitem->getStatus() != 'DRAFT') {
            $new_update_message .= "Summary changed. ";
            if ($message_title == '') {
                $message_title = 'Summary';
            } else {
                $message_title .= ' / Summary';
            }
        }
    }

    if (isset($_POST['skills'])) {
        $skillsArr = explode(', ', $skills);
        // remove empty values
        foreach ($skillsArr as $key => $value) {
            if (empty($value)) {
                unset($skillsArr[$key]);
            }
        }
        // get current skills
        $skillsCur = $workitem->getSkills();
        // have skills been updated?
        $skillsDiff = array_diff($skillsArr, $skillsCur);
        if (is_array($skillsDiff) && ! empty($skillsDiff)) {
            if ($workitem->getStatus() != 'DRAFT') {
                $new_update_message .= 'Skills updated: ' . implode(', ', $skillsArr);
            }
            // remove nasty end comma
            $new_update_message = rtrim($new_update_message, ', ') . '. ';
            if ($message_title == '') {
                $message_title = 'Skills';
            } else {
                $message_title .= ' / Skills';
            }
        }
        $workitem->setWorkitemSkills($skillsArr);
    }

    // status
    if ($is_runner
        || $userId == $workitem->getRunnerId()
        || (($status == 'BIDDING' || $status == 'WORKING') && $user->getBudget() > 0)
        || (in_array($status, $statusMapMechanic[$workitem->getStatus()]) && array_key_exists($workitem->getStatus(), $statusMapMechanic))) {

        if ($workitem->getStatus() != $status && !empty($status) && $status != 'DRAFT') {
            if (changeStatus($workitem, $status, $user)) {
                if (!empty($new_update_message)) {  // add commas where appropriate
                    $new_update_message .= ", ";
                    $message_title .= "/" . ucfirst(strtolower($status));;
                }
                $message_title = ucfirst(strtolower($status));;
                $new_update_message .= "Status set to $status. ";
            }
        }
    }
    if ($workitem->getNotes() != $notes && isset($_POST['notes'])) {
        $workitem->setNotes($notes);
        $new_update_message .= "Notes changed. ";
        if ($message_title == '') {
            $message_title = 'Notes changed';
        } else {
            $message_title .= ' / Notes changed';
        }
    }
    // project
    if ( $workitem->getProjectId() != $project_id) {
        $workitem->setProjectId($project_id);
        if ($workitem->getStatus() != 'DRAFT') {
            $new_update_message .= "Project changed. ";
            if ($message_title == '') {
                $message_title = 'Project changed';
            } else {
                $message_title .= ' / Project changed';
            }
        }
    }
    // Sandbox
    if ( $workitem->getSandbox() != $sandbox) {
        $workitem->setSandbox($sandbox);
        $new_update_message .= "Sandbox changed. ";
        if ($message_title == '') {
            $message_title = 'Sandbox URL';
        } else {
            $message_title .= ' / Sandbox URL';
        }
    }
    // Send invites
    if (!empty($_POST['invite'])) {
        $people = explode(',', $_POST['invite']);
        invitePeople($people, $worklist_id, $workitem->getSummary(), $workitem->getNotes());
        $new_update_message .= "Invitations sent. ";
        if ($message_title == '') {
            $message_title = 'Invitations';
        } else {
            $message_title .= ' / Invitations';
        }
    }
    //Check if bug_job_id has changed and send notifications if it has
    if($workitem->getBugJobId() != $bug_job_id) {
        //Bug job Id changed
        $workitem->setBugJobId($bug_job_id);
        $new_update_message .= "Bug job Id changed. ";
        if ($message_title == '') {
            $message_title = 'Bug job Id';
        } else {
            $message_title .= ' / Bug job Id';
        }
        if($bug_job_id > 0) {
            //Load information about original job and notify
            //users with fees and runner
            Notification::workitemNotify(array('type' => 'bug_found',
                                            'workitem' => $workitem,
                                            'recipients' => array('runner', 'usersWithFeesBug')));
            Notification::workitemSMSNotify(array('type' => 'bug_found',
                                            'workitem' => $workitem,
                                            'recipients' => array('runner', 'usersWithFeesBug')));
        }
    }
    //if job is a bug, notify to journal
    if($bug_job_id > 0) {
		error_log("bug_job_id:".$bug_job_id);
	    $workitem->setIs_bug(1);
        $bugJournalMessage= " (bug of #" . $workitem->getBugJobId() .")";
    } elseif (isset($_POST['is_bug']) && $_POST['is_bug'] == 'on') {
	    $bugJournalMessage = " (which is a bug)";
    } elseif (isset($is_bug) && $is_bug == 1) {
        $bugJournalMessage = " (which is a bug)";
    }
    else
    {
        $bugJournalMessage= "";
    }
	error_log($workitem->getIs_bug());
    if (empty($new_update_message)) {
        $new_update_message = " No changes.";
    } else {
        $workitem->save();
        $new_update_message = " Changes: $new_update_message";
    	$notifyEmpty = false;
    }

     $redirectToDefaultView = true;
     if ($workitem->getStatus() != 'DRAFT') {
         $journal_message .= $_SESSION['nickname'] . " updated item #$worklist_id ".
                            $bugJournalMessage  .": ". $workitem->getSummary() .
                            $new_update_message;
     }
}

if ($action == 'new-comment') {
    if ((isset($_POST['worklist_id']) && !empty($_POST['worklist_id'])) &&
        (isset($_POST['user_id'])     && !empty($_POST['user_id']))     &&
        (isset($_POST['comment'])     && !empty($_POST['comment']))) {
        
        if (isset($_POST['comment_id']) && !empty($_POST['comment_id'])) {
            $parent_comment = $_POST['comment_id'];
        } else {
            $parent_comment = NULL;
        }

        addComment($_POST['worklist_id'],
                   $_POST['user_id'],
                   $_POST['comment'],
                   $parent_comment);
        
        // Send journal notification
        if ($workitem->getStatus() != 'DRAFT') {
            $journal_message .= $_SESSION['nickname'] . " posted a comment on issue #$worklist_id: " . $workitem->getSummary();
            Notification::workitemNotify(array(
                'type' => 'comment',
                'workitem' => $workitem,
                'recipients' => array('creator', 'runner', 'mechanic'),
                'emails' => $corespondent),
                array(
                    'who' => $_SESSION['nickname'],
                    // removed nl2br as it's cleaner to be able to choose if this is used on output
                    'comment' => $_POST['comment']
                ));
        }
    }

    $redirectToDefaultView = true;
}

if($action =='invite-people') {
    // Send invitation
    if (invitePerson($_POST['invite'], $worklist_id, $workitem->getSummary(), $workitem->getNotes())) {
        $result = array('sent'=>'yes','person'=> htmlentities($_POST['invite']));
    }else{
        $result = array('sent'=>'no','person'=> htmlentities($_POST['invite']));
    }
    if($_POST['json'] =='y') {
      ob_start();
      $json = json_encode($result);
      echo $json;
      ob_end_flush();
      exit;
    }
}
if($action == 'start_codereview') {
    $workitem->setCRStarted(1);
    $workitem->save();
    $journal_message = $_SESSION['nickname'] . " has started a code review for #$worklist_id: " . $workitem->getSummary();
}

if($action == 'finish_codereview') {
    $args = array('itemid', 'crfee_amount', 'fee_category', 'crfee_desc', 'mechanic_id', 'is_expense', 'is_rewarder');
    foreach ($args as $arg) {
        if (isset($_POST[$arg])) {
               $$arg = mysql_real_escape_string($_POST[$arg]);
        } else {
            $$arg = '';
        }
    } 
    if($crfee_desc == '') {
        $crfee_desc = 'Code Review';
    } else {
        $crfee_desc = 'Code Review - '. $crfee_desc;
    }
    sendJournalNotification(AddFee($itemid, $crfee_amount, $fee_category, $crfee_desc, $mechanic_id, $is_expense, $is_rewarder));
    $workitem->setCRCompleted(1);
    $workitem->save();
    $journal_message = $_SESSION['nickname'] . " has completed their code review for #$worklist_id: " . $workitem->getSummary();
    $data = array();
    $data['subj'] = "#$worklist_id: " . $workitem->getSummary();
    $data['task'] = "<a href='".SERVER_URL."workitem.php?job_id=".$worklist_id."'>#".$worklist_id."</a> ".$workitem->getSummary();
    $data['reviewer'] = $_SESSION['nickname'];
    $data['creator'] = $workitem->getCreator()->getNickname();
    $data['runner'] = $workitem->getRunner()->getNickname();
    $data['mechanic'] = $workitem->getMechanic()->getNickname();
    
    $notifyUser = new User;
    $notifyUser->findUserById($workitem->getMechanicId());
    if (! sendTemplateEmail($notifyUser->getUsername(), 'code-review-finished', $data)) {
        error_log("workitem.php: send_email failed on finish code review notification to mechanic");
    }
    $notifyUser->findUserById($workitem->getRunnerId());
    if (! sendTemplateEmail($notifyUser->getUsername(), 'code-review-finished', $data)) {
        error_log("workitem.php: send_email failed on finish code review notification to runner");
    }
}

if($action == 'cancel_codereview') {
    $workitem->setCRStarted(0);
    $workitem->save();
    $journal_message = $_SESSION['nickname'] . " has canceled their code review for #$worklist_id: " . $workitem->getSummary();
}

if($action =='save-review-url') {

    $sandbox = (!empty($_POST['sandbox-url'])) ? $_POST['sandbox-url'] : $workitem->getSandbox();
    $notes = (!empty($_POST['review-notes'])) ? $_POST['review-notes'] : null;
    
    $status_review = $_POST['quick-status-review'];
    if(!empty($status_review)) {
       changeStatus($workitem, $status_review, $user);
    }
    $workitem->setSandbox($sandbox);
    $workitem->save();
    $new_update_message = " sandbox url : $sandbox ";
    if(!empty($status_review)) {
        $new_update_message .= " Status set to $status_review. ";
        $message_title = ucfirst(strtolower($status_review));
    } else {
        $message_title = "Sandbox URL";
    }
    if ($notes) {
        //add review notes
        $fee_amount = 0.00;
        $fee_desc = 'Review Notes:'. $notes;
        $mechanic_id = $user->getId();
        $itemid = $workitem->getId();
        $is_expense = 1;
        $fee_category = '';
        AddFee($itemid, $fee_amount, $fee_category, $fee_desc, $mechanic_id, $is_expense);
    }
    $notifyEmpty = false;
    if ($status_review == 'FUNCTIONAL') {
      Notification::workitemNotify(array('type' => 'modified-functional',
          'workitem' => $workitem,
          'recipients' => array('runner', 'creator', 'mechanic')),
            array('changes' => $new_update_message));
      $notifyEmpty = true;
    }
    $journal_message = $_SESSION['nickname'] . " updated item #$worklist_id: " . $workitem->getSummary() . ".  $new_update_message";
    $promptForReviewUrl = false;
}

if ($action =='status-switch') {
    $status = $_POST['quick-status'];
    $status_error = '';

    if ($status == 'DONE' && $workitem->getProjectId() == 0) {
        $status_error = "No project associated with workitem. Could not set to DONE.";
    } else {
        if (changeStatus($workitem, $status, $user)) {
            $workitem->save();
            if ($status != 'DRAFT') {
                $new_update_message = "Status set to $status. ";
                $notifyEmpty = false;
                $message_title = ucfirst(strtolower($status));
                if ($status == 'FUNCTIONAL') {
                    Notification::workitemNotify(array('type' => 'modified-functional',
                    'workitem' => $workitem,
                    'recipients' => array('runner', 'creator', 'mechanic')),
                    array('changes' => $new_update_message));
                    $notifyEmpty = true;
                }
                if ($status != 'DRAFT') {
                    $journal_message = $_SESSION['nickname'] . " updated item #$worklist_id: " . $workitem->getSummary() . ".  $new_update_message";
                }
            }
        }
    }
}

if(!$notifyEmpty) {
  Notification::workitemNotify(array('type' => 'modified',
              'workitem' => $workitem,
              'recipients' => array('runner', 'creator', 'mechanic')),
              array('changes' => $new_update_message, 'title' => $message_title));
}

if ($action == "place_bid") {
    //Escaping $notes with mysql_real_escape_string is generating \n\r instead of <br>
    //a new variable is used to send the unenscaped notes in email alert.
    //so it can parse the new line as <BR>   12-Mar-2011 <webdev>
    $unescaped_notes = nl2br($_POST['notes']);

    $args = array('bid_amount', 'done_in', 'bid_expires', 'notes', 'mechanic_id');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }
    if ($_SESSION['timezone'] == '0000') $_SESSION['timezone'] = '+0000';
    $summary = getWorkItemSummary($worklist_id);

    if($mechanic_id != getSessionUserId()) {
        $row = $workitem->getUserDetails($mechanic_id);
        if (! empty($row)) {
            $nickname = $row['nickname'];
            $username = $row['username'];
        } else {
            $username = "unknown-{$username}";
            $nickname = "unknown-{$mechanic_id}";
        }
    } else {
        $mechanic_id = $_SESSION['userid'];
        $username = $_SESSION['username'];
        $nickname = $_SESSION['nickname'];
    }

    if ($user->isEligible()) {
        $bid_id = $workitem->placeBid($mechanic_id, $username, $worklist_id, $bid_amount, $done_in, $bid_expires, $notes);
        // Journal notification
        $journal_message = "A bid was placed on item #$worklist_id: $summary.";
        //sending email to the runner of worklist item

        $row = $workitem->getRunnerSummary($worklist_id);
        if(!empty($row)) {
        $id = $row['id'];
            $summary = $row['summary'];
            $username = $row['username'];
        }

        $sms_message = "(Bid) $" . number_format($bid_amount, 2) . " from " . $_SESSION['username'] . " done in $done_in on #$worklist_id $summary";

        // notify runner of new bid
        Notification::workitemNotify(
            array(
                'type' => 'bid_placed',
                'workitem' => $workitem,
                'recipients' => array('runner')
            ),
            array(
                 'done_in' => $done_in,
                 'bid_expires' => $bid_expires,
                 'bid_amount' => $bid_amount,
                 'notes' => $unescaped_notes,
                 'bid_id' => $bid_id
            )
        );

        // sending sms message to the runner
        $runner = new User();
        $runner->findUserById($workitem->getRunnerId());
        if (Notification::isNotified($runner->getNotifications(), Notification::MY_BIDS_NOTIFICATIONS)) {
            Notification::sendSMS($runner, 'Bid', $sms_message);
        }
        $status=$workitem->loadStatusByBidId($bid_id);
        if ($status == "SUGGESTEDwithBID") {
            if (changeStatus($workitem, $status, $user)) {
                $new_update_message = "Status set to $status. ";
                $notifyEmpty = false;
                $journal_message .= "$new_update_message";
            }
        }
        if(!$notifyEmpty) {
            Notification::workitemNotify(array('type' => 'suggestedwithbid',
            'workitem' => $workitem,
            'recipients' => array('projectRunners')),
            array('notes' => $unescaped_notes));
        }
    } else {
        // we don't return anything. user has tried to circumvent security measures to place a bid
    }

    $redirectToDefaultView = true;
    // echo 'redirect is set to true ' . $redirectToDefaultView;
}
// Edit Bid
if ($action =="edit_bid") {

    //Escaping $notes with mysql_real_escape_string is generating \n\r instead of <br>
    //a new variable is used to send the unenscaped notes in email alert.
    //so it can parse the new line as <BR>   12-Mar-2011 <webdev>
    $unescaped_notes = nl2br($_POST['notes']);

    $args = array('bid_id', 'bid_amount', 'done_in_edit', 'bid_expires_edit', 'notes');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }

    if ($_SESSION['timezone'] == '0000') $_SESSION['timezone'] = '+0000';
    $summary = getWorkItemSummary($worklist_id);
    $bid_id = $workitem->updateBid($bid_id, $bid_amount, $done_in_edit, $bid_expires_edit, $_SESSION['timezone'], $notes);

    // Journal notification
    $journal_message = "Bid updated on item #$worklist_id: $summary.";

    $sms_message = "(Bid updated) $" . number_format($bid_amount, 2) . " from " . $_SESSION['username'] . " done in $done_in_edit on #$worklist_id $summary";
    //sending email to the runner of worklist item
    $row = $workitem->getRunnerSummary($worklist_id);
    if(!empty($row)) {
    $id = $row['id'];
        $summary = $row['summary'];
        $username = $row['username'];
    }
    // notify runner of new bid
    Notification::workitemNotify(array(
        'type' => 'bid_updated',
        'workitem' => $workitem,
        'recipients' => array('runner')
    ), array(
        'done_in' => $done_in_edit,
        'bid_expires' => $bid_expires_edit,
        'bid_amount' => $bid_amount,
        'notes' => $unescaped_notes,
        'bid_id' => $bid_id
    ));

    // sending sms message to the runner
    $runner = new User();
    $runner->findUserById($workitem->getRunnerId());
    if(Notification::isNotified($runner->getNotifications(), Notification::MY_BIDS_NOTIFICATIONS)) {
        Notification::sendSMS($runner, 'Updated', $journal_message);
    }

    $redirectToDefaultView = true;
}
// Request submitted from Add Fee popup
if ($action == "add_fee") {
    $args = array('itemid', 'fee_amount', 'fee_category', 'fee_desc', 'mechanic_id', 'is_expense', 'is_rewarder');
    foreach ($args as $arg) {
        if (isset($_POST[$arg]))  {
           $$arg = mysql_real_escape_string($_POST[$arg]);
        }
    else { $$arg = '';
        }
    }
        $journal_message = AddFee($itemid, $fee_amount, $fee_category, $fee_desc, $mechanic_id, $is_expense, $is_rewarder);

    if($workitem->getStatus() != 'DRAFT') {
        // notify runner of new fee
        Notification::workitemNotify(array('type' => 'fee_added',
                 'workitem' => $workitem,
                 'recipients' => array('runner')),
                 array('fee_adder' => $user->getNickname(),
                 'fee_amount' => $fee_amount));
    

    // send sms message to runner
    $runner = new User();
    $runner->findUserById($workitem->getRunnerId());
    $runner->updateBudget(-$fee_amount);

        if(Notification::isNotified($runner->getNotifications(), Notification::MY_BIDS_NOTIFICATIONS)) {
        Notification::sendSMS($runner, 'Fee', $journal_message);
        }
    }
    $redirectToDefaultView = true;
}

if ($action == "add_tip") {
    $args = array('itemid', 'tip_amount', 'tip_desc', 'mechanic_id');
    foreach ($args as $arg) {
        if (isset($_POST[$arg])) {
            $$arg = mysql_real_escape_string($_POST[$arg]);
        } else {
            $$arg = '';
        }
    }

    // is the logged in user the mechanic on the task?
    if ($workitem->getMechanicId() == getSessionUserId()) {
        $journal_message = AddTip($itemid, $tip_amount, $tip_desc, $mechanic_id);

        // notify recipient of new tip
        $recipient = new User();
        $recipient->findUserById($mechanic_id);
        Notification::workitemNotify(array('type' => 'tip_added',
            'workitem' => $workitem,
            'emails' => array($recipient->getUsername())),
            array('tip_adder' => $user->getNickname(),
              	  'tip_desc' => $tip_desc,
              	  'tip_amount' => $tip_amount
	    )
        );
    }
    
    $redirectToDefaultView = true;
}

// Accept a bid
if ($action == 'accept_bid') {
    $bid_id = intval($_REQUEST['bid_id']);
    // only runners can accept bids

    if ((
            $is_runner == 1 ||
            $workitem->getRunnerId() == $_SESSION['userid']
        ) &&
        !$workitem->hasAcceptedBids() &&
        (
            strtoupper($workitem->getStatus()) == "BIDDING" ||
            $workitem->getStatus() == "SUGGESTEDwithBID"
        )) {
        // query to get a list of bids (to use the current class rather than breaking uniformity)
        // I could have done this quite easier with just 1 query and an if statement..
        $bids = (array) $workitem->getBids($workitem->getId());
        $exists = false;
        foreach ($bids as $array) {
            if ($array['id'] == $bid_id) {
                $exists = true;
                $bid_amount = $array["bid_amount"];
                break;
            }
        }

        if ($exists) {
            if($bid_amount <= $runner_budget) {
                $bid_info = $workitem->acceptBid($bid_id);

                // Journal notification
                $journal_message .= $_SESSION['nickname'] .
                    " accepted {$bid_info['bid_amount']} from " .
                    $bid_info['nickname'] . " on item #{$bid_info['worklist_id']}: " .
                    $bid_info['summary'] . ". Status set to WORKING.";

                // mail notification
                Notification::workitemNotify(array(
                    'type' => 'bid_accepted',
                    'workitem' => $workitem,
                    'recipients' => array('mechanic')
                ));

                $bidder = new User();
                $bidder->findUserById($bid_info['bidder_id']);
                
                // Update Budget
                $runner = new User();
                $runner->findUserById($workitem->getRunnerId());
                $runner->updateBudget(-$bid_amount);

                //send sms notification to bidder
                Notification::sendSMS($bidder, 'Accepted', $journal_message);

                $redirectToDefaultView = true;

                // Send email to not accepted bidders
                sendMailToDiscardedBids($worklist_id);
            } else {
                $overBudget = money_format('%i', $bid_amount - $runner_budget);
                $_SESSION['workitem_error'] = "Failed to accept bid. Accepting this bid would make you " . $overBudget . " over your budget!";
                $redirectToDefaultView = true;
            }
        }
        else {
            $_SESSION['workitem_error'] = "Failed to accept bid, bid has been deleted!";
            $redirectToDefaultView = true;
        }
    }
}

// Accept Multiple  bid
if ($action=='accept_multiple_bid') {
    $bid_id = $_REQUEST['chkMultipleBid'];
    $mechanic_id = $_REQUEST['mechanic'];
    if(count($bid_id) > 0) {
    //only runners can accept bids
        if ((
                $is_runner == 1 ||
                $workitem->getRunnerId() == getSessionUserId()
            ) &&
            !$workitem->hasAcceptedBids() &&
            (
                strtoupper($workitem->getStatus()) == "BIDDING" ||
                $workitem->getStatus() == "SUGGESTEDwithBID"
            )) {
            $total = 0;
            foreach($bid_id as $bid) {
                $currentBid = new Bid();
                $currentBid->findBidById($bid);
                $total = $total + $currentBid->getBid_amount();
            }
            
            if ($total <= $runner_budget) {
                foreach($bid_id as $bid) {
                    $bids = (array) $workitem->getBids($workitem -> getId());
                    $exists = false;
                    foreach ($bids as $array) {
                        if ($array['id'] == $bid) {
                            if ($array['bidder_id'] == $mechanic_id) {
                                $is_mechanic = true;
                            } else {
                                $is_mechanic = false;
                            }
                            $exists = true;
                            break;
                        }
                    }
                    if ($exists) {
                        $bid_info = $workitem->acceptBid($bid, $is_mechanic);
                        // Journal notification
                        $journal_message .= $_SESSION['nickname'] . " accepted {$bid_info['bid_amount']} from ". $bid_info['nickname'] . " " . ($is_mechanic ? ' as MECHANIC ' : '') . "on item #".$bid_info['worklist_id'].": " . $bid_info['summary'] . ". Status set to WORKING. ";
                        // mail notification
                        Notification::workitemNotify(array('type' => 'bid_accepted',
                                     'workitem' => $workitem,
                                     'recipients' => array('mechanic')));
                    } else {
                        $_SESSION['workitem_error'] = "Failed to accept bid, bid has been deleted!";
                    }
                }
                // Send email to not accepted bidders
                sendMailToDiscardedBids($worklist_id);
                $redirectToDefaultView = true;

                $runner = new User();
                $runner->findUserById($workitem->getRunnerId());
                $runner->updateBudget(-$total);
            } else {
                $overBudget = money_format('%i', $total - $runner_budget);
                $_SESSION['workitem_error'] = "Failed to accept bids. Accepting this bids would make you " . $overBudget . " over your budget!";
                $redirectToDefaultView = true;
            }
        }
    }
}
//Withdraw a bid
if ($action == "withdraw_bid") {
    if (isset($_REQUEST['bid_id'])) {
        withdrawBid(intval($_REQUEST['bid_id']), $_POST['withdraw_bid_reason']);
    } else {
        $fee_id = intval($_REQUEST['fee_id']);
        $res = mysql_query('SELECT f.bid_id, f.amount, w.runner_id FROM `' . FEES . '` AS f, ' . WORKLIST . ' AS w WHERE f.`id`=' . $fee_id . ' AND f.worklist_id = w.id');
        $fee = mysql_fetch_object($res);
        if ((int)$fee->bid_id !== 0) {
            withdrawBid($fee->bid_id, $_POST['withdraw_bid_reason']);
        } else {
            deleteFee($fee_id);
        }

        // Update Runner's Budget
        $runner = new User();
        $runner->findUserById($fee->runner_id);
        $runner->updateBudget($fee->amount);
    }
    $redirectToDefaultView = true;
}

if ($action == false) {
    $redirectToDefaultView = $redirectToWorklistView = $postProcessUrl = false;
}

if ($redirectToDefaultView) {
    $postProcessUrl = WORKITEM_URL . $worklist_id;
}
if ($redirectToWorklistView) {
    $postProcessUrl = WORKLIST_REDIRECT_URL . $worklist_id;
}

// we have a Journal message, send it to Journal - except for DRAFTS
if(isset($journal_message) && $workitem->getStatus() != 'DRAFT') {
    sendJournalNotification($journal_message);
    //$postProcessUrl = WORKITEM_URL . $worklist_id . "&msg=" . $journal_message;
}

// if a post process URL was set, redirect and die
if(isset($postProcessUrl) && ! empty($postProcessUrl)) {
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
// get only those bids that have not expired, used to determine whether
// runner can edit the job notes
$activeBids = (array) $workitem->getBids($workitem->getId(), false);

//Findout if the current user already has any bids.
// Yes, it's a String instead of boolean to make it easy to use in JS.
// Suppress names if not is_runner, or creator of Item. Still show if it's user's bid.

$currentUserHasBid = "false";
if(!empty($bids) && is_array($bids)) {
    foreach($bids as &$bid) {
        if($bid['email'] == $currentUsername ) {
            $currentUserHasBid = "true";
            //break;
        }
        if ((!$user->isRunner())) {
            if ($user->getId() != $bid['bidder_id'])  {
                $bid['nickname'] = '*name hidden*';
                $bid['bid_amount'] = '***';
                $bid['email'] = '********';
                $bid['notes'] = '********';
            }
        }
        $bid['bid_created'] = convertTimezone($bid['unix_bid_created']);
        if ($bid['unix_bid_accepted'] > 0) {
            $bid['bid_accepted'] = convertTimezone($bid['unix_bid_accepted']);
        } else {
            $bid['bid_accepted'] = '';
        }
        if ($bid['unix_done_full'] > 0 && !empty($bid['unix_done_full'])) {
            $bid['unix_done_full'] = convertTimezone($bid['unix_done_full']);
        } else {
            $bid['unix_done_full'] = '';
        }


        // calculate Total Time to Complete
        if (isset($bid['unix_done_by']) && $bid['unix_done_by'] != 0) {
            $timeToComplete = (int) $bid['unix_done_by'] - (int) $bid['unix_bid_created'];
            if ($bid['unix_bid_accepted'] > 0) {
                $timeElapsed = (int) $bid['unix_now'] - (int) $bid['unix_bid_accepted'];
                $timeToComplete -= $timeElapsed;
            }
            $fullDays    = floor($timeToComplete/(60*60*24));
            $fullHours   = floor(($timeToComplete-($fullDays*60*60*24))/(60*60));
            $fullMinutes = floor(($timeToComplete-($fullDays*60*60*24)-($fullHours*60*60))/60);
            $bid['time_to_complete']= $fullDays . ($fullDays==1?" day, ":" days, ").$fullHours. ($fullHours==1?" hour and ":" hours and ").$fullMinutes.($fullMinutes==1?" minute.":" minutes.");
        } else {
            $bid['time_to_complete'] = null;
        }
    }
}
// break reference to $bid
unset($bid);
//get fees
$fees = $workitem->getFees($worklist_id);

//total fee
$total_fee = $workitem->getSumOfFee($worklist_id);

//accepted bid amount
$accepted_bid_amount = 0;
foreach($fees as $fee){
    if ($fee['desc'] == 'Accepted Bid') {
        $accepted_bid_amount = $fee['amount'];  
    }
}

//code review fees
$project = new Project();
$project_roles = $project->getRoles($workitem->getProjectId(),"role_title ='Code Review'");
if (count($project_roles) != 0 && $project_roles['percentage'] !== null && $project_roles['min_amount'] !== null) {
    $crFee = ($project_roles['percentage'] / 100) * $accepted_bid_amount;
    if ((float) $crFee < $project_roles['min_amount']) {
       $crFee = $project_roles['min_amount']; 
    }
} else {
    $crFee = (10 / 100 ) * $accepted_bid_amount;
    if ((float) $crFee < 2.00) {
       $crFee = 2.00; 
    }
}

require_once "workitem.inc";

function hasRights($workitem,$userId) {
    $project = new Project();
    $project->loadById($workitem->getProjectId());
    $users_favorite = new Users_Favorite();
    if ($project->getCrAnyone()) {
        return true;
    } else if ($project->getCrAdmin()) {
        $admin_fav = $users_favorite->getMyFavoriteForUser($project->getOwnerId(), $userId);
        if ($admin_fav['favorite']) {
            return true;
        }
    } else if ($project->getCrFav() && $users_favorite->getUserFavoriteCount($userId) >= 3) {
        return true;
    } else if ($project->getCrRunner()) {
        $runner_fav = $users_favorite->getMyFavoriteForUser($workitem->getRunnerId(),$userId);
        if ($runner_fav['favorite']) {
            return true;
        }
    }
    return false;
}

function sendMailToDiscardedBids($worklist_id)    {
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

    foreach( $bids as $bid )    {
        $subject = "Job: ".$item['summary'];
        $body = "<p>Hello ".$bid['nickname'].",</p>";
        $body .= "<p>Thanks for adding your bid to <a href='".SERVER_URL."workitem.php?job_id=".$item['id']."'>#".$item['id']."</a> '".$item['summary']."'. This job has just been filled by another mechanic.</br></p>";
        $body .= "There is lots of work to be done so please keep checking the <a href='".SERVER_URL."'>worklist</a> and bid on another job soon!</p>";
        $body .= "<p>Hope to see you in the Workroom soon. :)</p>";

        if(!send_email($bid['email'], $subject, $body)) { error_log("workitem.php: send_email failed"); }
    }
}

function changeStatus($workitem, $newStatus, $user) {

    $allowable = array("DRAFT", "SUGGESTED", "SUGGESTEDwithBID", "REVIEW", "FUNCTIONAL", "PASS", "COMPLETED");

    if ($user->getIs_runner() == 1 || $user->getBudget() > 0) {
        if($newStatus == 'BIDDING' && in_array($workitem->getStatus(), $allowable)) {
            $workitem->setRunnerId($user->getId());
        }
    }
    
    if ($newStatus == 'DONE' && $workitem->getProjectId() == 0) {
        return false;
    }

    $workitem->setStatus($newStatus);
    
    // Generate diff and send to pastebin if we're in REVIEW
    if ($newStatus == "REVIEW") {
        //reset core_review flags
        $workitem->resetCRFlags();
        if (substr($workitem->getSandbox(), 0, 4) == "http") {
            require_once("sandbox-util-class.php");
            
            // Sandbox URLs look like:
            // https://dev.worklist.net/~johncarlson21/worklist
            // 0     12               3              4
            $sandbox_array = explode("/", $workitem->getSandbox());

            $username = isset($sandbox_array[3]) ? $sandbox_array[3] : "~";
            $username = substr($username, 1); // eliminate the tilde

            $sandbox = isset($sandbox_array[4]) ? $sandbox_array[4] : "";

            try {
                $result = SandBoxUtil::pasteSandboxDiff($username, $workitem->getId(), $sandbox);
                $comment = "Code review available here:\n$result";
                addComment($workitem->getId(), $user->getId(), $comment);
            } catch (Exception $ex) {
                error_log("Could not paste diff: \n$ex");
            }
        }
    }

    // notifications for subscribed users
    Notification::statusNotify($workitem);
    
    return true;
}

function addComment($worklist_id, $user_id, $comment_text, $parent_comment_id=NULL) {
    // in case the comment is a reply to another comment,
    // we'll fetch the original comment's email <mikewasmike>
    $corespondent = NULL; 
    $comment = new Comment();
    $comment->setWorklist_id((int) $worklist_id);
    $comment->setUser_id((int) $user_id);

    if (isset($parent_comment_id)) {
        $comment->setComment_id((int) $parent_comment_id);
        $originalComment = new Comment(); 
        $originalComment->findCommentById((int) $parent_comment_id);
        $cuser = new User();
        $cuser->findUserById($originalComment->getUser_id());
        $corespondent = array($cuser->getUsername());
    }
    
    $comment->setComment($comment_text);

    try {
        $comment->save();
    } catch(Exception $e) {
        error_log("Failure saving comment:\n".$e); 
    }  
    $redirectToDefaultView = true;

}
