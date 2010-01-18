<?php
//  Copyright (c) 2009, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history

include("config.php");
include("class.session_handler.php");

$item = isset($_REQUEST["item"]) ? intval($_REQUEST["item"]) : 0;
if (empty($item))
    return;

$query = "SELECT `".BIDS."`.*, `".WORKLIST."`.`creator_id` FROM `".BIDS."`, ".WORKLIST.
	  " WHERE `worklist_id` = `".WORKLIST."`.`id` AND `".BIDS."`.`id` = '$item'";
$rt = mysql_query($query);
$row = mysql_fetch_assoc($rt);
$json_row = array(); 
foreach($row as $item){
  $json_row[] = $item;
}

$json = json_encode($json_row);
echo $json;     
