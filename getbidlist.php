<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history

include("config.php");
include("class.session_handler.php");

$limit = 5;
$page = isset($_REQUEST["page"])?intval($_REQUEST["page"]) : 1;
if(isset($_REQUEST["worklist_id"])){
  $worklist_id = intval($_REQUEST["worklist_id"]);
//end execution if no worklist_id provided
}else{
  exit;
}


$rt = mysql_query("SELECT COUNT(*) FROM ".BIDS." WHERE `worklist_id` = ".$worklist_id);
$row = mysql_fetch_row($rt);
$items = intval($row[0]);

$cPages = ceil($items/$limit); 

$query = "SELECT *, TIMESTAMPDIFF(SECOND, `bid_created`, NOW()) as `delta`, DATE_FORMAT(`bid_done`, '%m/%d/%Y') FROM `".BIDS. 
         "` WHERE worklist_id=".$worklist_id.
         " ORDER BY `id` DESC LIMIT " . ($page-1)*$limit . ",$limit";
$rt = mysql_query($query);

// Construct json for history
$bidlist = array(array($items, $page, $cPages));

while($row = mysql_fetch_assoc($rt)){
  $json_row = array(); 
  foreach($row as $item){
    $json_row[] = $item;
  }
  $bidlist[] = $json_row;
}
                      
$json = json_encode($bidlist);
echo $json;     
