<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

ini_set('display_errors', 1);
error_reporting(-1);

ob_start();

require_once(dirname(dirname(__FILE__)).'/config.php');
 
$con = mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
if (!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db(DB_NAME, $con);

$sql = " UPDATE " . FEES . " AS f, " . WORKLIST . " AS w, " . USERS . " AS u " 
     . " SET f.paid = 1, f.paid_date = NOW() "
     . " WHERE f.paid = 0 AND f.worklist_id = w.id AND w.status = 'DONE' "
     . "   AND f.withdrawn = 0 "
     . "   AND f.user_id = u.id "
     . "   AND u.has_W2 = 1 "
     . "   AND f.date <  CAST(DATE_FORMAT(NOW() ,'%Y-%m-01') as DATE); ";
 
// Marks all Fees from the past month as paid (for DONEd jobs)
$result = mysql_query($sql);

$total = mysql_affected_rows();

if( $total) {
    echo "{$total} fees were set.";
} else {
    echo "No records were found!";
}
echo "<br/> $sql";

mysql_close($con);
