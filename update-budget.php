<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

// AJAX request to add/update a rewarder user

include("config.php");
include("class.session_handler.php");
include("functions.php");

$error = false;
$message = '';

if (!isset($_SESSION['userid'])) {
    echo 'error: unauthorized';
    return;
}

$receiver_id = intval($_REQUEST['receiver_id']);
$amount = isset($_REQUEST['amount']) ? floatval($_REQUEST['amount']) : 0;
$reason = mysql_real_escape_string($_REQUEST['reason']);
if (empty($receiver_id) || empty($reason) || empty($amount)) {
    echo 'error: args';
    return;
}

$giver = new User();
$receiver = new User();
if (!$giver->findUserById($_SESSION['userid']) || !$receiver->findUserById($receiver_id)) {
    echo 'error: invalid user';
    return;
}

$stringAmount = number_format($amount, 2);

if ($amount <= $giver->getBudget()) {
    $giver->setBudget($giver->getBudget() - $amount)->save();
    $receiver->setBudget($receiver->getBudget() + $amount)->save();

    $query = "INSERT INTO `".BUDGET_LOG."` (`giver_id`,`receiver_id`,`amount`,`reason`,`transfer_date`) VALUES ('".$_SESSION['userid']."','$receiver_id','$amount','$reason',NOW())";
    mysql_unbuffered_query($query);
    
    $query2 = " UPDATE `" . USERS . "` SET `is_runner` = 1 WHERE `id` = $receiver_id AND `is_runner` = 0 ";
    mysql_unbuffered_query($query2);

    $journal_message = $giver->getNickname() . " budgeted " . $receiver->getNickname() . " $" . number_format($amount, 2) .
    " for " . $_REQUEST['reason'] . ".";
    sendJournalNotification($journal_message);
    
    Notification::notifyBudget($amount, $reason, $giver, $receiver);
    Notification::notifySMSBudget($amount, $reason, $giver, $receiver);
    
    $receiver = getUserById($receiver_id);
    $message =  'You gave ' . '$' . $stringAmount . ' budget to ' . $receiver->nickname;
} else {
    $error = true;
    $message = 'You do not have enough budget available to give this amount.';
}

$json = json_encode(array('success' => !$error, 'message' => $message));
echo $json;


