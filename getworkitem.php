<?php
//  Copyright (c) 2009, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("functions.php");

if (!checkReferer()) die;

$item = isset($_REQUEST["item"]) ? intval($_REQUEST["item"]) : 0;
if (empty($item))
    return;

$query = "select summary,nickname,value,contract,expense,contract,status,notes from ".WORKLIST. 
         " left join ".USERS." on ".WORKLIST.".owner_id=".USERS.".id where ".WORKLIST.".id='$item'";
error_log($query);
$rt = mysql_query($query);
$row = mysql_fetch_assoc($rt);

$json = json_encode(array($row['summary'], $row['nickname'], $row['value'], $row['contract'],
                         $row['expense'], $row['contract'], $row['status'], $row['notes']));
echo $json;     
