<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");

$req =  isset($_REQUEST['req'])? $_REQUEST['req'] : 'table';

	if($req == 'currentlink')	{
		$query_b = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'BIDDING'");
		$query_w = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'WORKING'");
		$count_b = mysql_num_rows($query_b);
		$count_w = mysql_num_rows($query_w);
		echo "<a href='javascript:ShowStats()' id='stats'>| ". $count_b. " jobs bidding, ". $count_w. " jobs underway</a>";
		
	}	else if($req == 'current')	{
		$query_b = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'bidding'");
		$query_w = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'working'");
		$count_b = mysql_num_rows($query_b);
		$count_w = mysql_num_rows($query_w);
		$res = array($count_b, $count_w);
		echo json_encode($res);
	
	}	else	if($req == 'fees')	{
		// Get Average Fees in last 7 days
		$query = mysql_query("SELECT AVG(amount) FROM ".FEES." INNER JOIN ".WORKLIST." ON ".FEES.".worklist_id = ".WORKLIST.".id
											WHERE date > DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'DONE'");
		$rt = mysql_fetch_assoc($query);
		echo json_encode($rt);
	
	}	else if($req == 'hours')	{
		// TODO: Implement MYSQL Query
		// Get Average Hours in last 7 days
		//$query = mysql_query("SELECT TIMEDIFF(bid_done, bid_created) FROM ".BIDS." INNER JOIN ".WORKLIST." ON ".BIDS.".worklist_id = ".WORKLIST.".id
		//									WHERE status='DONE' AND bid_done > DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'DONE'");
		//$hours
		//for( $i = 1; $row = mysql_fetch_assoc($query); i++)	{
		
		//}
										
	}	else	if($req == 'table')	{
		// Get jobs done in last 7 days
		$fees_q = mysql_query("SELECT ".WORKLIST.".id,summary,nickname,amount,date,user_paid FROM ".FEES."
											INNER JOIN ".USERS." ON ".FEES.".user_id = ".USERS.".id INNER JOIN ".WORKLIST."
											ON ".FEES.".worklist_id = ".WORKLIST.".id WHERE status='DONE' AND
											date > DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY date DESC;");
		$fees = array();
		// Prepare json
		for( $i = 1; $row = mysql_fetch_assoc($fees_q); $i++)	{
			$fees[] = array($row['id'], $row['summary'], $row['nickname'], $row['amount'], $row['date'], $row['user_paid']);
		}

		$json = json_encode($fees);
		echo $json;
	}

?>