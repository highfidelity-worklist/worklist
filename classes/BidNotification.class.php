<?php
/**
 * Coffee And Power
 * Copyright (c) 2011 LoveMachine, LLc.
 * All rights reserved.
 */


class BidNotification {
    /**
     * Constructor
     */
    public function __construct() {
        
    }

    /**
     * Destructor
     */
    public function __destructor() {
        
    }

    // get list of expired bids
    public function emailExpiredBids(){
        include('send_email.php');
        
        $html_start = "<html><head><title>Coffee And Power</title></head><body>";
        $html_end = "</body></html>";
        $html = '';
        $qry = "SELECT w.*, b.*, b.id as bid_id FROM ".WORKLIST." w LEFT JOIN ".BIDS." b ON w.id = b.worklist_id WHERE w.status = 'BIDDING' AND b.expired_notify = 0 AND b.bid_expires < NOW() ORDER BY b.worklist_id DESC";
        $worklist = mysql_query($qry);
        $wCount = mysql_num_rows($worklist);
        if($wCount > 0){
            while ($row = mysql_fetch_assoc($worklist)) {
                $subject = "Expired: #".$row['worklist_id']." (".$row['summary'].")";
                $html = $html_start;
                $html .= "<p>------------------------------------------</p>";
                $html .= "<p>Your Bid on #".$row['worklist_id']." (".$row['summary'].") has expired and this task is still available for Bidding.</p>";
                $html .= "<p>To view this task, <a href='".SERVER_URL."workitem.php?job_id=".$row['worklist_id']."&action=view'>click here.</a></p>";
                $html .= "<p>Your Bid Info<br />";
                $html .= "Bid Amount : $".$row['bid_amount']."<br />";
                $html .= "Notes: ".nl2br($row['notes'])."<br />";
                $html .= "</p>";
                $html .= "<p>------------------------------------------</p>";
                $html .= $html_end;
                send_email($row['email'], $subject, $html);
                // now need to set this bid expired_notice to 1
                $bquery = "UPDATE ".BIDS." SET expired_notify = 1 WHERE id = ".$row['bid_id'];
                mysql_query($bquery);
                
            }
        }
    }
    
}

