<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");

if(isset($_REQUEST["worklist_id"])){
  $worklist_id = intval($_REQUEST["worklist_id"]);
//end execution if no worklist_id provided
}else{
  exit;
}

$rt = mysql_query("SELECT SUM(`amount`) FROM `".FEES."` WHERE worklist_id = ".$worklist_id);
$row = mysql_fetch_row($rt);
$total = $row[0];

$query = "SELECT `".FEES."`.`id`, `amount`, `nickname`, `desc`, DATE_FORMAT(`date`, '%m/%d/%Y'), `paid` FROM `".FEES. 
         "`, `".USERS."` WHERE worklist_id = ".$worklist_id." AND `".USERS."`.`id` = `user_id`";
$rt = mysql_query($query);

// Construct json for list
$feelist = array(array($total));

while($row = mysql_fetch_assoc($rt)){
  $json_row = array(); 
  foreach($row as $item){
    $json_row[] = $item;
  }
  $feelist[] = $json_row;
}
                      
$json = json_encode($feelist);
echo $json;     
 
