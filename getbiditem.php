<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com


include("config.php");
include("class.session_handler.php");
include("functions.php");

$item = isset($_REQUEST["item"]) ? intval($_REQUEST["item"]) : 0;
if (empty($item))
    return;

$query = "SELECT `id`, `bidder_id`, `email`, `bid_amount`, `notes`, UNIX_TIMESTAMP(`bid_done`) AS `done_by`,
          TIMESTAMPDIFF(SECOND, NOW(), `bid_done`) AS `future_delta`
          FROM `".BIDS.
	  "` WHERE `id` = '$item'";

$rt = mysql_query($query);
$row = mysql_fetch_assoc($rt);
$row["done_by"] = getUserTime($row['done_by']);
$json = json_encode($row);
echo $json;     
