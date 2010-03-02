<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com


include("config.php");
include("class.session_handler.php");
include("functions.php");

$limit = 5;
$page = isset($_REQUEST["page"])?intval($_REQUEST["page"]) : 1;
if(isset($_REQUEST["worklist_id"])){
  $worklist_id = intval($_REQUEST["worklist_id"]);
//end execution if no worklist_id provided
}else{
  exit;
}

$rt = mysql_query("SELECT `owner_id` FROM ".WORKLIST." WHERE `id` = " . $worklist_id);
if (!$rt)  exit;
$row = mysql_fetch_row($rt);
$owner_id = $row[0];
error_log($owner_id);

$rt = mysql_query("SELECT COUNT(*) FROM ".BIDS." WHERE `worklist_id` = " . $worklist_id . " AND withdrawn = 0");
$row = mysql_fetch_row($rt);
$items = intval($row[0]);

$cPages = ceil($items/$limit); 

$query = "SELECT `id`, `bidder_id`, `email`, `bid_amount`, 
          TIMESTAMPDIFF(SECOND, `bid_created`, NOW()) AS `delta`, 
          TIMESTAMPDIFF(SECOND, NOW(), `bid_done`) AS `future_delta`, 
	  DATE_FORMAT(`bid_done`, '%m/%d/%Y') AS `bid_done` FROM `".BIDS. 
         "` WHERE worklist_id=".$worklist_id.
         " AND withdrawn = 0" .
         " ORDER BY `id` DESC LIMIT " . ($page-1)*$limit . ",$limit";
$rt = mysql_query($query);

// Construct json for history
$bidlist = array(array($items, $page, $cPages));

$userId = getSessionUserId();
$user = new User();
if ($userId > 0) {
	$user = $user->findUserById($userId);
} else {
	$user->setId(0);
}

while ($row = mysql_fetch_assoc($rt)){
	if (!$user->isRunner() && ($user->getId() != $owner_id) && ($user->getId() != $row['bidder_id'])) {
		$row['bidder_id'] = 0;
		$row['email'] = '*name hidden*';
	}
	$bidlist[] = $row;
}
                      
$json = json_encode($bidlist);
echo $json;     
