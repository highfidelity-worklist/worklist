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

if (isset($_REQUEST['paid_notes']) && !empty($_REQUEST['paid_notes'])) {
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
	die('{"success": true, "message": "Payment has been saved!" }');
} else {
	die('{"success": false, "message": "Something went technically wrong!" }');
}
