<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

ini_set('display_errors', 1);
error_reporting(-1);


if(php_sapi_name() != 'cli'){
    die('Can only be called from command line!');
}

require_once('config.php');
require_once('functions.php');
require_once('workitem.class.php');
require_once('send_email.php');
require_once('classes/Project.class.php');
require_once('classes/User.class.php');
require_once('classes/Notification.class.php');

$con = mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
if (!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db(DB_NAME, $con);
$sql = "SELECT id FROM `worklist` WHERE  status  in ( 'SUGGESTED' , 'SUGGESTEDwithBID')  and DATEDIFF(now() , status_changed) > 30";

$result = mysql_query($sql);
$delay = 0;
if(mysql_num_rows($result) > 1) {
    $delay = 5;
}
while ($row = mysql_fetch_assoc($result)) {
    $status = 'PASS';
    $workitem = new WorkItem($row['id']);
    
    // change status of the workitem to PASS.
    $workitem->setStatus($status);
    $workitem->save();
    
    //notify creator
    Notification::workitemNotify(array('type' => 'auto-pass',
                                       'workitem' => $workitem,
                                       'recipients' => array('creator')));    
    
    //sendJournalnotification
    $journal_message = "Otto updated item #" . $workitem->getId() . ": " . $workitem->getSummary() . ". Status set to PASS";
    sendJournalNotification(stripslashes($journal_message));
    
    sleep($delay);
}
mysql_free_result($result);
mysql_close($con);
