<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request to add/update a rewarder user

include("config.php");
include("class.session_handler.php");
include("functions.php");

if (!isset($_SESSION['userid'])) {
    echo 'error: unauthorized';
    return;
}

$receiver_id = isset($_REQUEST['receiver_id']) ? intval($_REQUEST['receiver_id']) : 0;
$amount = isset($_REQUEST['amount']) ? floatval($_REQUEST['amount']) : 0;
if (empty($receiver_id) || empty($_REQUEST['reason']) || empty($amount)) {
    echo 'error: args';
    return;
}

$reason = mysql_real_escape_string($_REQUEST['reason']);

$giver = new User();
$receiver = new User();
if (!$giver->findUserById($_SESSION['userid']) || !$receiver->findUserById($receiver_id)) {
    echo 'error: invalid user';
    return;
}

$amount = min($giver->getBudget(), $amount);
if ($amount > 0) {
    $giver->setBudget($giver->getBudget() - $amount)->save();
    $receiver->setBudget($receiver->getBudget() + $amount)->save();

    $query = "INSERT INTO `".BUDGET_LOG."` (`giver_id`,`receiver_id`,`amount`,`reason`,`transfer_date`) VALUES ('".$_SESSION['userid']."','$receiver_id','$amount','$reason',NOW())";
    mysql_unbuffered_query($query);

    $data = array();
    $data['user'] = JOURNAL_API_USER;
    $data['pwd'] = sha1(JOURNAL_API_PWD);
    $data['message'] = $giver->getNickname() . " budgeted " . $receiver->getNickname() . " " . number_format($amount, 2) . " for $reason";
    postRequest(JOURNAL_API_URL, $data);
}

$json = json_encode(number_format($giver->getBudget(), 2));
echo $json;
