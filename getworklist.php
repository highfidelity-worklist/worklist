<?php
//  Copyright (c) 2009, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history

include("config.php");
include("class.session_handler.php");

$limit = 30;
$page = isset($_REQUEST["page"])?$_REQUEST["page"]:1;
$sfilter = isset($_REQUEST["sfilter"])?explode("/",$_REQUEST["sfilter"]):array();
$ufilter = isset($_REQUEST["ufilter"])?intval($_REQUEST["ufilter"]):0;

$where = '';
if (!empty($sfilter)) {
    $where = "where (";
    foreach ($sfilter as $val) {
        $val = strtoupper(mysql_real_escape_string($val));
        if ($val == 'ALL') {
            $where .= "1 or ";
        } else {
            $where .= "status='$val' or ";
        }
    }
    $where .= "0)";
}
if (!empty($ufilter) && $ufilter != 'ALL') {
    if (empty($where)) {
        $where = "where ";
    } else {
        $where .= " and ";
    }
    $where .= "(creator_id='$ufilter' or owner_id='$ufilter')";
}

$rt = mysql_query("select count(*) from ".WORKLIST." $where");
$query = "select count(*) from ".WORKLIST." left join ".USERS." on ".WORKLIST.".owner_id=".USERS.".id $where";
$rt = mysql_query($query);
$row = mysql_fetch_row($rt);
$items = intval($row[0]);

$cPages = ceil($items/$limit); 

$query = "select DISTINCT(".WORKLIST.".id),summary,status,nickname,username,TIMESTAMPDIFF(SECOND,created,NOW()) as delta from ".WORKLIST. 
         " left join ".USERS." on ".WORKLIST.".owner_id=".USERS.".id".
         " $where order by ".WORKLIST.".created desc limit " . ($page-1)*$limit . ",$limit";
$rt = mysql_query($query);

// Construct json for history
$worklist = array(array($items, $page, $cPages));
for ($i = 1; $row=mysql_fetch_assoc($rt); $i++)
{
    if (!empty($row['username'])) {
        $nickname = $row['nickname'];
        $username = $row['username'];
    } else {
        $nickname = $username = '';
    }
    $worklist[] = array($row['id'], $row['summary'], $row['status'], $nickname, $username, $row['delta']);
}
                      
$json = json_encode($worklist);
echo $json;     
