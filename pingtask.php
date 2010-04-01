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

$item_id = intval($_REQUEST['id']);
$msg = $_REQUEST['msg'];
$send_mail = false;
if( isset( $_REQUEST['mail'] ) )
	$send_mail = intval( $_REQUEST['mail'] );

$who = $_REQUEST['who'];

// Get sender Nickname
$id = getSessionUserId();
$user = getUserById( $id );
$nickname = $user->nickname;
$email = $user->username;

// Get item
$item = getWorklistById( $item_id );

if( $who == 'mechanic' ) {
    // Get mechanic Nickname & email
    $receiver_id = $item['mechanic_id'];
    $receiver = getUserById( $receiver_id );
    $receiver_nick = $reciever->nickname;
    $receiver_email = $receiver->username;
} else if( $who == 'runner' ) {
    // Get runner Nickname & email
    $receiver_id = $item['runner_id'];
    $receiver = getUserById( $receiver_id );
    $receiver_nick = $receiver->nickname;
    $receiver_email = $reciever->username;
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
	$mail_msg .= "</p><p>Message:<br/>".$msg."</p><p>You can answer to ".$nickname." at: ".$email."</p><p>LoveMachine</p>";

	sl_send_email( $receiver_email, $mail_subject, $mail_msg);
}
	
?>
