<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

// AJAX request to add/update a rewarder user

include("config.php");
include("class.session_handler.php");
include("functions.php");
require_once 'models/DataObject.php';
require_once 'models/Budget.php';
include_once("send_email.php");

$error = false;
$message = '';

if (!isset($_SESSION['userid'])) {
    echo json_encode(array('success' => false, 'message' => 'error: unauthorized'));
    return;
}

if (!isset($_REQUEST['budget_seed']) || !isset($_REQUEST['budget_source']) 
    || !isset($_REQUEST['budget_source_combo']) || !isset($_REQUEST['budget_note'])) {
    echo json_encode(array('success' => false, 'message' => 'error: args'));
    return;
}
$budget_seed = (int) $_REQUEST['budget_seed'];
$budget_source = mysql_real_escape_string($_REQUEST['budget_source']);
$budget_source_combo = (int) $_REQUEST['budget_source_combo'];
$budget_note = mysql_real_escape_string($_REQUEST['budget_note']);
if ($budget_seed == 1) {
    $budget_source_combo = 0;
    $source = $budget_source;
    if (empty($source)) {
        echo json_encode(array('success' => false, 'message' => 'Source field is mandatory'));
        return;
    }
} else {
    $source = "Amount from budget id: " . $budget_source_combo;
    if ($budget_source_combo == 0) {
        echo json_encode(array('success' => false, 'message' => 'Source field is mandatory'));
        return;
    }
}

$receiver_id = intval($_REQUEST['receiver_id']);
$amount = isset($_REQUEST['amount']) ? floatval($_REQUEST['amount']) : 0;
$reason = mysql_real_escape_string($_REQUEST['reason']);
if (empty($receiver_id) || empty($reason) || empty($amount)) {
    echo json_encode(array('success' => false, 'message' => 'For and amount fields are mandatory'));
    return;
}

$giver = new User();
$receiver = new User();
if (!$giver->findUserById($_SESSION['userid']) || !$receiver->findUserById($receiver_id)) {
    echo json_encode(array('success' => false, 'message' => 'error: invalid user'));
    return;
}

$stringAmount = number_format($amount, 2);

if ($budget_seed != 1) {
    $budget = new Budget();
    if (!$budget->loadById($budget_source_combo) ) {
        echo json_encode(array('success' => false, 'message' => 'Invalid budget!'));
        return;
    }
    $remainingFunds = $budget->getRemainingFunds();
}


if ($budget_seed == 1 || 
    ($amount <= $giver->getBudget() && $amount <= $remainingFunds)) {
    if ($budget_seed != 1) {
        $giver->updateBudget(- $amount, $budget_source_combo);
    }
    $receiver->setBudget($receiver->getBudget() + $amount)->save();

    $query = "INSERT INTO `" . BUDGETS . 
            "` (`giver_id`, `receiver_id`, `amount`, `remaining`, `reason`, `transfer_date`, `seed`, `source_data`, `source_budget_id`, `notes`, `active`) VALUES ('" .
            $_SESSION['userid'] . 
            "', '$receiver_id', '$amount','$amount', '$reason', NOW(), '$budget_seed', '$source', '$budget_source_combo', '$budget_note', 1)";
    if (mysql_unbuffered_query($query)){    
        $query2 = " UPDATE `" . USERS . "` SET `is_runner` = 1 WHERE `id` = $receiver_id AND `is_runner` = 0 ";
        if (mysql_unbuffered_query($query2)) {
            $journal_message = $giver->getNickname() . " budgeted " . $receiver->getNickname() . " $" . number_format($amount, 2) .
            " for " . $_REQUEST['reason'] . ".";
            sendJournalNotification($journal_message);            
            Notification::notifyBudget($amount, $reason, $giver, $receiver);
            Notification::notifySMSBudget($amount, $reason, $giver, $receiver);
            if ($budget_seed == 1) {
                Notification::notifySeedBudget($amount, $reason, $source, $giver, $receiver);
                Notification::notifySMSSeedBudget($amount, $reason, $source, $giver, $receiver);
            }
            $receiver = getUserById($receiver_id);
            $message =  'You gave ' . '$' . $stringAmount . ' budget to ' . $receiver->nickname;
        } else {
            $error = true;
            $message = 'Error in query.';
            error_log("update-budget.php error in sql:" . $query2 . mysql_error());
        }
    } else {
        $error = true;
        $message = 'Error in query.';
        error_log("update-budget.php error in sql:" . $query . mysql_error());
    }
} else {
    $error = true;
    $message = 'You do not have enough budget available to give this amount.';
}

$json = json_encode(array('success' => !$error, 'message' => $message));
echo $json;


