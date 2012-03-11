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
require_once 'models/DataObject.php';
require_once 'models/Budget.php';

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
        $giver->findUserById($userId);
        $budget = $giver->getBudget();

        // validate required fields
        if (empty($_REQUEST['budget-source-combo-bonus']) || empty($_REQUEST['receiver_id']) || empty($_REQUEST['amount'])) {
            $error = true;
            $message = 'error: args';
        }
        
        $budget_source_combo = (int) $_REQUEST['budget-source-combo-bonus'];
        $budgetSource = new Budget();
        if (!$budgetSource->loadById($budget_source_combo) ) {
            $error = true;
            $message = 'Invalid budget!';
        }

        $amount = floatval($_REQUEST['amount']);
        $stringAmount = number_format($amount, 2);

        $receiver_id = intval($_REQUEST['receiver_id']);
        $reason = $_REQUEST['reason'];

    } else {
        $error = true;
        $message = 'error: session';
    }

}


if (! $error) {
    $remainingFunds = $budgetSource->getRemainingFunds();
    if ($amount <= $budget && $amount <= $remainingFunds) {
        if (payBonusToUser($receiver_id, $amount, $reason)) {
            // deduct amount from balance
            $giver->updateBudget(- $amount, $budget_source_combo);

            $receiver = getUserById($receiver_id);
            $receiver_email = $receiver->username;

            sendTemplateEmail( $receiver_email, 'bonus_received', array('amount' => $stringAmount, 'reason' => $reason));
            if (sendJournalNotification($receiver->nickname . ' received a bonus of $' . $stringAmount) == 'ok') {
            } else {
                // journal notification failed
            }
            $error = false;
            $message =  'Paid ' . $receiver->nickname . ' a bonus of $' . $stringAmount;
        } else {
            $error = true;
            $message = 'DB error';
        }

    } else {
        $error = true;
        $message = 'You do not have enough budget available to pay this bonus.';
    }

}

$json = json_encode(array('success' => !$error, 'message' => $message));
echo $json;
