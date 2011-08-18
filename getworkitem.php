<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history

include("config.php");
include("class.session_handler.php");

$userId = isset($_SESSION['userid'])? $_SESSION['userid'] : 0;

$item = isset($_REQUEST["item"]) ? intval($_REQUEST["item"]) : 0;
if (empty($item))
    return;

$query = "SELECT ".WORKLIST.".`id`, `summary`, `nickname`, `status`, `notes` FROM ".WORKLIST. 
         " LEFT JOIN ".USERS." ON ".WORKLIST.".`creator_id` = ".USERS.".`id` WHERE ".WORKLIST.".id = '$item'
         AND ".WORKLIST.".status != 'DRAFT' OR (" .WORKLIST. ".status = 'DRAFT' AND " .WORKLIST. ".`creator_id` = '$userId')";
$rt = mysql_query($query);
$row = mysql_fetch_assoc($rt);

$json = json_encode(array( $row['summary'], $row['nickname'], $row['status'], $row['notes'], $row['id']));
echo $json;     
