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

$is_payer = !empty($_SESSION['is_payer']) ? true : false;
// Check if we have a payer
if (!$is_payer) {
	exit('{"success": false, "message": "You are not allowed to be here!" }');
}

// Get clean data
if (isset($_REQUEST['paid_check']) && ($_REQUEST['paid_check'] == 'on')) {
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
$user = mysql_real_escape_string($_SESSION['userid']);
// Now we can be shure it's clean data

// Here comes the database part
$query = 'UPDATE `' . FEES . '` 
			SET `user_paid` = ' . $user . ',
			`notes` = "' . $paid_notes . '",
			`paid` = ' . $paid_check . ' 
			WHERE `id` = ' . $fee_id . ' LIMIT 1';


// Exit of this script
if (mysql_query($query)) {
    $fees_query  =  'SELECT  `amount`,`user_id`,`worklist_id`,`desc` FROM '.FEES.'  WHERE `id` =  '.$fee_id;
    $result1 = mysql_query($fees_query);
    $fee_pay= mysql_fetch_array($result1);
    $total_fee_pay = $fee_pay['amount'];

   
   
    $summary =  getWorkItemSummary($fee_pay['worklist_id']);

  

    $subject =" Love machine paid you ".$total_fee_pay ." for ". $summary;
    $body = "Fee Description : ".nl2br($fee_pay['desc'])."<br><br>";
    $body .="Paid Notes : ".nl2br($_REQUEST['paid_notes'])."<br>"."<br>"."<br>"."<br>"."Thank you! "."<br>"."<br>"."Love,"."<br>"."<br>"."Philip and Ryan"."<br>"."<br>";

    $mail = 'SELECT `username` FROM '.USERS.' WHERE `id` = '.$fee_pay['user_id'].'';
    $username= mysql_fetch_array(mysql_query($mail));

    sl_send_email($username['username'], $subject, $body);
    die('{"success": true, "message": "Payment has been saved!" }');


} else {
	die('{"success": false, "message": "Something went technically wrong!" }');
}
