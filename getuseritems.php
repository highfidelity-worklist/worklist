<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history

	include("config.php");
	include("class.session_handler.php");
	include("functions.php");

	$userId = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : 0;
	if (empty($userId))
	    return;

	$query = "SELECT `" . WORKLIST . "`.`id`, `summary`, `bid_amount`, `bid_done`,"
		  . " TIMESTAMPDIFF(SECOND, NOW(), bids.`bid_done`) AS `future_delta` FROM `" . WORKLIST . "`"
		  . " LEFT JOIN `" . BIDS . "` ON `bidder_id` = `mechanic_id` AND `worklist_id` = `" . WORKLIST . "`.`id`"
		  . " WHERE `mechanic_id` = $userId AND status = 'WORKING'";
	$rt = mysql_query($query);

	$items = array();

	while($row = mysql_fetch_assoc($rt)){

		$row['relative'] = relativeTime($row['future_delta']);	    
		$items[] = $row;
	}

	$json = json_encode($items);
    
	echo $json;