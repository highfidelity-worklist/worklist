<?php
//  vim:ts=4:et
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

$application_path = dirname(dirname(__FILE__)) . '/';
  
require_once 'config.php';
require_once 'class.session_handler.php';
require_once 'send_email.php';
require_once 'functions.php';
require_once 'lib/Sms.php';
require_once 'classes/Notification.class.php';

checkLogin();

// Get sender Nickname
$id = getSessionUserId();
$user = getUserById($id);
$nickname = $user->nickname;
$email = $user->username;
$msg = $_REQUEST['msg'];
// send mail is hardcoded to on
$send_mail = true;
$send_chat = isset($_REQUEST['journal']) ? (int) $_REQUEST['journal'] : false;
$send_cc = isset($_REQUEST['cc']) ? (int) $_REQUEST['cc'] : false;

// ping about concrete task
if (isset($_REQUEST['id'])) {
    $item_id = intval($_REQUEST['id']);
    $who = $_REQUEST['who'];
    $bid_id = intval($_REQUEST['bid_id']);
    // Get item
    $item = getWorklistById( $item_id );

    if( $who == 'mechanic' ) {
        // Get mechanic Nickname & email
        $receiver_id = $item['mechanic_id'];
        $receiver = getUserById( $receiver_id );
        $receiver_nick = $receiver->nickname;
        $receiver_email = $receiver->username;
    } else if( $who == 'runner' ) {
        // Get runner Nickname & email
        $receiver_id = $item['runner_id'];
        $receiver = getUserById( $receiver_id );
        $receiver_nick = $receiver->nickname;
        $receiver_email = $receiver->username;
    } else if($who == 'creator' ) {
        // Get runner Nickname & email
        $receiver_id = $item['creator_id'];
        $receiver = getUserById( $receiver_id );
        $receiver_nick = $receiver->nickname;
        $receiver_email = $receiver->username;
    } else if ($who == 'bidder') {
        // Get bidder Nickname & email
        $bid = new Bid();
        $bid->findBidById($bid_id);
        $bid_info = $bid->toArray();
        $receiver_id = $bid_info['bidder_id'];
        $receiver = getUserById( $receiver_id );
        $receiver_nick = $receiver->nickname;
        $receiver_email = $receiver->username;
    }

    // Compose journal message
    if ($send_chat) {
        $out_msg = $nickname . " sent a ping to " . $receiver_nick . " about item #" .$item_id;
        $out_msg .= ": " . $msg;

        // Send to journal
        sendJournalNotification($out_msg);
    }

    // Send mail
    if ($send_mail && $who != 'bidder') {
        $mail_subject = $nickname." sent you a ping for item #".$item_id;
        $mail_msg = "<p>Dear ".$receiver_nick.",<br/>".$nickname." sent you a ping about item ";
        $mail_msg .= "<a href='".WORKLIST_URL."/workitem.php?job_id=".$item_id."&action=view'>#".$item_id."</a>";
        $mail_msg .= "</p><p>Message:<br/>".$msg."</p><p>You can answer to ".$nickname." at: ".$email."</p>";
        $headers = array('X-tag' => 'ping, task', 'Reply-To' => '"' . $nickname . '" <' . $email . '>');
        if ($send_cc) {
            $headers['Cc'] = '"' . $nickname . '" <' . $email . '>';
        }
        if (!send_email($receiver_email, $mail_subject, $mail_msg, '', $headers)) { 
            error_log('pingtask.php:id: send_email failed');
        }

        // sms
        $user = new User();
        $user->findUserById($receiver->id);
        if (Notification::isNotified($user->getNotifications(), Notification::PING_NOTIFICATIONS)) {
            notify_sms_by_object($user, $mail_subject, $msg);
        }
    } else if ($send_mail && $who == 'bidder') {
        $project = new Project();
        $project->loadById($item['project_id']);
        $project_name = $project->getName();
        $mail_subject = "#" . $item_id . " - " . $item['summary'];
        $mail_msg = "<p>The Runner for #" . $item_id . " sent a reply to your bid.</p>";
        $mail_msg .= "<p>Message from " . $nickname . ":<br/>" . $msg . "</p>";
        $mail_msg .= "<p>Your bid info:</p>";
        $mail_msg .= "<p>Amount: " . $bid_info['bid_amount'] . "<br />Done in: " . $bid_info['bid_done_in'] . "<br />Expires: " . $bid_info['bid_expires'] . "</p>";
        $mail_msg .= "<p>Notes: " . $bid_info['notes'] . "</p>";
        $mail_msg .= "<p>You can view the job here. <a href='".WORKLIST_URL."/workitem.php?job_id=" . $item_id . "&action=view'>#" . $item_id . "</a></p>";
        $mail_msg .= "<p>-Worklist.net</p>";
        $headers = array('From' => '"'. $project_name.'-bid reply" <'. SMS_SENDER . '>', 'X-tag' => 'ping, task', 'Reply-To' => '"' . $nickname . '" <' . $email . '>');
        if ($send_cc) {
            $headers['Cc'] = '"' . $nickname . '" <' . $email . '>';
        }
        if (!send_email($receiver_email, $mail_subject, $mail_msg, '', $headers)) { 
            error_log('pingtask.php:id: send_email failed');
        }

        // sms
        $user = new User();
        $user->findUserById($receiver->id);
        if (Notification::isNotified($user->getNotifications(), Notification::PING_NOTIFICATIONS)) {
            notify_sms_by_object($user, $mail_subject, $msg);
        }
    }

} else {

    // just send general ping to user

    $receiver = getUserById(intval($_REQUEST['userid']));
    $receiver_nick = $receiver->nickname;
    $receiver_email = $receiver->username;

    if ($send_chat) {
        // Compose journal message
        $out_msg = $nickname." sent a ping to " . $receiver_nick;
        $out_msg .= ": ".$msg;

        // Send to journal
        echo sendJournalNotification( $out_msg );
    }

    if( $send_mail )    {
        $mail_subject = $nickname." sent you a ping.";
        $mail_msg = "<p>Dear ".$receiver_nick.",<br/>".$nickname." sent you a ping. ";
        $mail_msg .= "</p><p>Message:<br/>".$msg."</p><p>You can answer to ".$nickname." at: ".$email."</p>";

        $headers = array('X-tag' => 'ping', 'Reply-To' => '"' . $nickname . '" <' . $email . '>');
        if ($send_cc) {
            $headers['Cc'] = '"' . $nickname . '" <' . $email . '>';
        }
        if (!send_email($receiver_email, $mail_subject, $mail_msg, '', $headers)) { 
            error_log("pingtask.php:!id: send_email failed");
        }

        // sms
        try {
            $user = new User();
            $user->findUserById($receiver->id);
            if(Notification::isNotified($user->notifications, Notification::PING_NOTIFICATIONS)) {
                notify_sms_by_object($user, $mail_subject, $msg);
            }
        } catch (Sms_Backend_Exception $e) {
        }
    }
}
