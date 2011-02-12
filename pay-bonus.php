<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request to pay bonus to a user

require_once("config.php");
require_once("functions.php");
require_once("send_email.php");
require_once("class.session_handler.php");

$error = false;
$message = '';

// user must be logged in
if (! isset($_SESSION['userid'])) {
    $error = true;
    $message = 'error: unauthorized';
} else {

    $userId = getSessionUserId();

    $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
    $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;

    if( $userId > 0 && ( $is_runner || $is_payer ) ) {
        $giver = new User();
        $giver->findUserById($_SESSION['user_id']);
        $budget = $giver->getBudget();

        // validate required fields
        if (empty($_REQUEST['receiver_id']) || empty($_REQUEST['amount'])) {
            $error = true;
            $message = 'error: args';
        }

        $amount = floatval($_REQUEST['amount']);
        $receiver_id = intval($_REQUEST['receiver_id']);
        $reason = $_REQUEST['reason'];

    } else {
        $error = true;
        $message = 'error: session';
    }
}


if (! $error) {

    if ($amount >= $budget) {
        if (payBonusToUser($receiver_id, $amount, $reason)) {
            // deduct amount from balance
            $giver->setBudget($budget - $amount)->save();

            $receiver = getUserById($receiver_id);
            $receiver_email = $receiver->username;

            sendTemplateEmail( $receiver_email, 'bonus_received', array('amount' => $amount, 'reason' => $reason));
            if (sendJournalNotification($receiver->nickname . ' received a bonus of $' . $amount) == 'ok') {
            } else {
                // journal notification failed
            }
            $error = false;
            $message =  'Paid ' . $receiver->nickname . ' a bonus of $' . $amount;
        } else {
            $error = true;
            $message = 'DB error';
        }
        
    } else {
        $error = true;
        $message = 'Not enough budget available to pay bonus';
    }

}

$json = json_encode(array('success' => !$error, 'message' => $message));
echo $json;
