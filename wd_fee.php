<?php
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//
include("config.php");

//ALL interfaces that alter money must check for a valid logged in user
include("class.session_handler.php");
include("check_session.php");


//open or attach to  db connection
$db = @mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD) or die ('I cannot connect to the database because: ' . mysql_error());
$db = @mysql_select_db(DB_NAME);

$fee_id = (int)$_GET["wd_fee_id"];
if ($fee_id < 1) { return 'Update Failed'; }

$fee_update_sql = 'UPDATE '.FEES.' SET withdrawn = \'1\' WHERE id = '.$fee_id;

//Restrict fee removal to user and those authorized to affect money
if (empty($_SESSION['is_payer']) && empty($_SESSION['is_runner']) && !empty($_SESSION['userid'])) {
    $fee_update_sql .= ' and `user_id` = ' . ($_SESSION['userid']);
}

$fee_update = mysql_query($fee_update_sql) or error_log("wd_fee mysql error: $fee_update_sql\n".json_encode($_SESSION) . mysql_error());

if ($fee_update) { 
    echo 'Update Successful!'; 
} else { 
    echo 'Update Failed!';
}

?>
