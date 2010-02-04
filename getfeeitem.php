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

$query = "SELECT id, paid, notes FROM ".FEES." WHERE ".FEES.".id='{$item}'";

$rt = mysql_query($query);
$row = mysql_fetch_assoc($rt);

$json = json_encode(array($row['id'], $row['paid'], $row['notes']));
echo $json;     
