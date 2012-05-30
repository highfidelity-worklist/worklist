<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com


if ( strpos(strtolower($_SERVER['SCRIPT_NAME']),strtolower(basename(__FILE__))) ) {
    header("Location: ../../index.php");
    die("...");
}

require_once('config.php');
require_once('functions.php');
require_once('workitem.class.php');
require_once('send_email.php');
require_once('classes/Project.class.php');
require_once('classes/User.class.php');
require_once('classes/Notification.class.php');

function autoPassJobs() {
    $con = mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
    if (!$con) {
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db(DB_NAME, $con);
    $sql = "SELECT id FROM `" . WORKLIST ."` WHERE  status  IN ( 'SUGGESTED' , 'SUGGESTEDwithBID', 'BIDDING') AND DATEDIFF(now() , status_changed) > 30";
    
    $result = mysql_query($sql);
    $delay = 0;
    if(mysql_num_rows($result) > 1) {
        $delay = 5;
    }
    while ($row = mysql_fetch_assoc($result)) {
        $status = 'PASS';
        $workitem = new WorkItem($row['id']);
        $prev_status = $workitem->getStatus();
        
        // change status of the workitem to PASS.
        $workitem->setStatus($status);
        if ($workitem->save()) {
            
            $recipients = array('creator');
            $emails = array();
            $data = array('prev_status' => $prev_status);
            
            if ($prev_status == 'BIDDING') {
                $recipients[] = 'usersWithBids';
                $emails = preg_split('/[\s]+/', ADMINS_EMAILS);
            }
            
            //notify
            Notification::workitemNotify(
                array(
                    'type' => 'auto-pass',
                    'workitem' => $workitem,
                    'recipients' => $recipients,
                    'emails' => $emails
                ),
                $data
            );
            
            //sendJournalnotification
            $journal_message = "Otto updated item #" . $workitem->getId() . ": " . $workitem->getSummary() . ". Status set to " . $status;
            sendJournalNotification(stripslashes($journal_message));            
        } else {
            error_log("Otto failed to update the status of workitem #" . $workitem->getId() . " to " . $status);
        }
        sleep($delay);
    }
    mysql_free_result($result);
    mysql_close($con); 
}


