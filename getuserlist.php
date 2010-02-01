<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com


include("config.php");
include("class.session_handler.php");

$limit = 30;
$page = isset($_REQUEST["page"])?intval($_REQUEST["page"]) : 1;
$letter = isset($_REQUEST["letter"]) ? mysql_real_escape_string(trim($_REQUEST["letter"])) : "";
if($letter == "all"){
  $letter = ".*";
}
if($letter == "_"){ //numbers
  $letter = "[0-9]";
}

$rt = mysql_query("SELECT COUNT(*) FROM `users` WHERE `nickname` REGEXP '^$letter'");
$row = mysql_fetch_row($rt);
$users = intval($row[0]);

$cPages = ceil($users/$limit); 

$query = "SELECT `id`, `nickname`, `is_runner` FROM `users` WHERE `nickname` REGEXP '^$letter' ORDER BY `nickname` ASC LIMIT " . ($page-1)*$limit . ",$limit";
$rt = mysql_query($query);

// Construct json for pagination
$userlist = array(array($users, $page, $cPages));

while($row = mysql_fetch_assoc($rt)){
  $json_row = array(); 
  foreach($row as $user){
    $json_row[] = $user;
  }
  $userlist[] = $json_row;
}
                      
$json = json_encode($userlist);
echo $json;     
