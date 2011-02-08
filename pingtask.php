<?php
//  vim:ts=4:et
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

require_once 'config.php';
require_once 'class.session_handler.php';
require_once 'send_email.php';
require_once 'functions.php';
require_once 'lib/Sms.php';

// Get sender Nickname
$id = getSessionUserId();
$user = getUserById( $id );
$nickname = $user->nickname;
$email = $user->username;
$msg = $_REQUEST['msg'];
$send_mail = false;
if( isset( $_REQUEST['mail'] ) ){
    $send_mail = intval( $_REQUEST['mail'] );
}

// ping about concrete task
if(isset($_REQUEST['id'])){
    $item_id = intval($_REQUEST['id']);
    $who = $_REQUEST['who'];

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
	}

    // Compose journal message
    $out_msg = $nickname." sent a ping to ".$receiver_nick." about item #".$item_id;
    $out_msg .= ": ".$msg;

    // Send to journal
    sendJournalNotification( $out_msg );

    // Send mail
    if( $send_mail )	{
        $mail_subject = $nickname." sent you a ping for item #".$item_id;
        $mail_msg = "<p>Dear ".$receiver_nick.",<br/>".$nickname." sent you a ping about item ";
        $mail_msg .= "<a href='http://dev.sendlove.us/worklist/workitem.php?job_id=".$item_id."&action=view'>#".$item_id."</a>";
        $mail_msg .= "</p><p>Message:<br/>".$msg."</p><p>You can answer to ".$nickname." at: ".$email."</p>";

        if (!sl_send_email( $receiver_email, $mail_subject, $mail_msg)) { error_log("pingtask.php:id: sl_send_email failed"); }

        // sms
        try {
            $user = new User();
            $user->findUserById($receiver->id);
            $config = Zend_Registry::get('config')->get('sms', array());
            if ($config instanceof Zend_Config) {
                $config = $config->toArray();
            }
            $sms = new Sms_Message($user, $mail_subject, $msg);
            Sms::send($sms, $config);
        } catch (Sms_Backend_Exception $e) {
        }
    }

}else{

    // just send general ping to user

    $receiver = getUserById(intval($_REQUEST['userid']));
    $receiver_nick = $receiver->nickname;
    $receiver_email = $receiver->username;

    // Compose journal message
    $out_msg = $nickname." sent a ping to " . $receiver_nick;
    $out_msg .= ": ".$msg;

    // Send to journal
    sendJournalNotification( $out_msg );

    if( $send_mail )    {
        $mail_subject = $nickname." sent you a ping.";
        $mail_msg = "<p>Dear ".$receiver_nick.",<br/>".$nickname." sent you a ping. ";
        $mail_msg .= "</p><p>Message:<br/>".$msg."</p><p>You can answer to ".$nickname." at: ".$email."</p>";

        if(!sl_send_email( $receiver_email, $mail_subject, $mail_msg)) { error_log("pingtask.php:!id: sl_send_email failed"); }

        // sms
        try {
            $user = new User();
            $user->findUserById($receiver->id);
            $config = Zend_Registry::get('config')->get('sms', array());
            if ($config instanceof Zend_Config) {
                $config = $config->toArray();
            }
            $sms = new Sms_Message($user, $mail_subject, $msg);
            Sms::send($sms, $config);
        } catch (Sms_Backend_Exception $e) {
        }
    }
}
