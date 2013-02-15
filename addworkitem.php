<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

require_once ("config.php");
require_once ("class.session_handler.php");
include_once ("check_new_user.php"); 
require_once ("functions.php");
require_once ("send_email.php");
require_once ('lib/Agency/Worklist/Filter.php');

$journal_message = '';
$nick = '';

$workitem = new WorkItem();

$userId = getSessionUserId();
if ($userId > 0) {
    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();
}

if ($userId > 0 ) {
    $args = array(
        'itemid',
        'summary',
        'project_id',
        'skills',
        'status',
        'notes',
        'invite',
        'is_expense',
        'is_rewarder',
        'is_bug',
        'bug_job_id',
        'fileUpload'
    );

    foreach ($args as $arg) {
        // Removed mysql_real_escape_string, because we should 
        // use it in sql queries, not here. Otherwise it can be applied twice sometimes
        $$arg = !empty($_REQUEST[$arg])?$_REQUEST[$arg]:'';
    }


    $creator_id = $userId;

    if (! empty($_POST['itemid'])) {
        $workitem->loadById($_POST['itemid']);
        $journal_message .= $nick . " updated ";
    } else {
        $workitem->setCreatorId($creator_id);
        $journal_message .= $nick . " added ";
    }
    $workitem->setSummary($summary);

    //If this item is a bug add original item id 
    $workitem->setBugJobId($bug_job_id);
    // not every runner might want to be assigned to the item he created - only if he sets status to 'BIDDING'
    if ($status == 'Bidding' && $user->getIs_runner() == 1) {
        $runner_id = $userId;
    } else {
        $runner_id = 0;
    }

    $skillsArr = explode(', ', $skills);

    $workitem->setRunnerId($runner_id);
    $workitem->setProjectId($project_id);
    $workitem->setStatus($status);
    $workitem->setNotes($notes);
    $workitem->setWorkitemSkills($skillsArr);
    $workitem->setIs_bug($is_bug == 'true' ? 1 : 0);
    $workitem->save();
    $related = getRelated($notes);
    Notification::statusNotify($workitem);

    // if files were uploaded, update their workitem id
    $file = new File();
    // update images first
    if (isset($fileUpload['images'])) {
        foreach ($fileUpload['images'] as $image) {
            $file->findFileById($image);
            $file->setWorkitem($workitem->getId());
            $file->save();
        }
    }
    // update documents
    if (isset($fileUpload['documents'])) {
        foreach ($fileUpload['documents'] as $document) {
            $file->findFileById($document);
            $file->setWorkitem($workitem->getId());
            $file->save();
        }
    }

    if($is_bug && $bug_job_id > 0) {
        //Load information about original job and notify
        //users with fees and runner
        Notification::workitemNotify(array(
            'type' => 'bug_found',
            'workitem' => $workitem,
            'recipients' => array('runner', 'usersWithFeesBug')
        ));
        Notification::workitemSMSNotify(array(
            'type' => 'bug_found',
            'workitem' => $workitem,
            'recipients' => array(
                'runner', 
                'usersWithFeesBug'
            )
        ));
        $bugJournalMessage= " (bug of #" . $workitem->getBugJobId() .")";
    } else {
        $bugJournalMessage= "";
    }
    
    if (empty($_POST['itemid'])) {
        $bid_fee_itemid = $workitem->getId();
        $journal_message .= " item #$bid_fee_itemid$bugJournalMessage: $summary. Status set to $status ";
        if (!empty($_POST['files'])) {
            $files = explode(',', $_POST['files']);
            foreach ($files as $file) {
                $sql = 'UPDATE `' . FILES . '` SET `workitem` = ' . $bid_fee_itemid . ' WHERE `id` = ' . (int)$file;
                mysql_query($sql);
            }
        }
    } else {
        $bid_fee_itemid = $itemid;
        $journal_message .=  "item #$itemid: $summary: Status set to $status ";
    }
    $journal_message .=  "$related. ";
    if (!empty($_POST['invite'])) {
        $people = explode(',', $_POST['invite']);
        invitePeople($people, $workitem);
    }
} else {
    echo json_encode(array('error' => "Invalid parameters !"));
    return;
}

// don't send any journal notifications for DRAFTS
if (!empty($journal_message) && $status != 'Draft') {
    //sending journal notification
    $data = array();
    sendJournalNotification(stripslashes($journal_message));
}

// Notify Runners of new suggested task
if ($status == 'SUGGESTED' && $project_id != '') {
    Notification::workitemNotify(array(
        'type' => 'suggested',
        'workitem' => $workitem,
        'recipients' => array('projectRunners')),
        array('notes' => $notes)
    );        
}

echo json_encode(array('return' => "Done!"));
