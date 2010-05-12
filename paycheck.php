<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

// Include config to get database ready
include('config.php');
// Include session class for session handling
include('class.session_handler.php');
// Include functions
include('functions.php');
include_once("send_email.php");
include_once("classes/Fee.class.php");

$is_payer = !empty($_SESSION['is_payer']) ? true : false;
// Check if we have a payer
if (!$is_payer) {
	exit('{"success": false, "message": "Nothing to see here. Move along!" }');
}

// Get clean data
if (isset($_REQUEST['paid_check']) && ($_REQUEST['paid_check'] == '1')) {
	$paid_check = 1;
} else {
	$paid_check = 0;
}
$paid_notes = $_REQUEST['paid_notes'];
if (isset($paid_notes) && !empty($paid_notes)) {
	$paid_notes = mysql_real_escape_string($_REQUEST['paid_notes']);
} else {
	die('{"success": false, "message": "You must write a note!" }');
}

if (isset($_REQUEST['itemid']) && !empty($_REQUEST['itemid'])) {
	$fee_id = mysql_real_escape_string($_REQUEST['itemid']);
} else {
	die('{"success": false, "message": "No fee set!" }');
}

// What user is paying
$user = $_SESSION['userid'];

// Exit of this script
if (Fee::markPaidById($fee_id, $user, $paid_notes, $paid_check)) {
    /* Only send the email when marking as paid. */
    if ($paid_check) {
        $fees_query  =  'SELECT  `amount`,`user_id`,`worklist_id`,`desc` FROM '.FEES.'  WHERE `id` =  '.$fee_id;
        $result1 = mysql_query($fees_query);
        $fee_pay= mysql_fetch_array($result1);
        $total_fee_pay = $fee_pay['amount'];
   
        $summary =  getWorkItemSummary($fee_pay['worklist_id']);

        $mail = 'SELECT `username` FROM '.USERS.' WHERE `id` = '.$fee_pay['user_id'].'';
        $userData = mysql_fetch_array(mysql_query($mail));

        $subject = "LoveMachine paid you ".$total_fee_pay ." for ". $summary;
        $body  = "Cha-ching! You've got funds!<br/>";
        $body .= "Fee Description : ".nl2br($fee_pay['desc'])."<br/>";
        $body .= "Paid Notes : ".nl2br($_REQUEST['paid_notes'])."<br/><br/>";
        $body .= "See you in the Workroom!<br/><br/>Love,<br/><br/>Eliza @ the LoveMachine<br/>";

        sl_send_email($userData['username'], $subject, $body);
    }
    die('{"success": true, "message": "Payment has been saved!" }');
} else {
    die('{"success": false, "message": "Hmm, that was unexpected... Payment Failed!" }');
}
