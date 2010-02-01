<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com


include("config.php");
include("class.session_handler.php");
include("timezones.php");

$item = isset($_REQUEST["item"]) ? intval($_REQUEST["item"]) : 0;
if (empty($item))
    return;

$query = "SELECT `id`, `nickname`, `username`, `about`, `contactway`, `payway`, `skills`, `timezone`, DATE_FORMAT(`added`, '%m/%d/%Y'), `is_runner` FROM `".USERS.
	  "` WHERE `id` = '$item'";

$rt = mysql_query($query);
$row = mysql_fetch_assoc($rt);
$json_row = array(); 
foreach($row as $item){
  $json_row[] = $item;
}
//changing timezone to human-readable
if($json_row[7]){
  $json_row[7] = $timezoneTable[$json_row[7]];
}

$json = json_encode($json_row);
echo $json;     
