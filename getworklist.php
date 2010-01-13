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

$limit = 30;
$page = isset($_REQUEST["page"])?$_REQUEST["page"]:1;
$filter = isset($_REQUEST["filter"])?explode("/",$_REQUEST["filter"]):array();

$rt = mysql_query("select count(*) from ".WORKLIST);
$row = mysql_fetch_row($rt);
$items = intval($row[0]);

$cPages = ceil($items/$limit); 

$where = "where 1";
if (!empty($where)) {
    $where = "where ";
    foreach ($filter as $val) {
        $val = strtoupper(mysql_real_escape_string($val));
        if ($val == 'ALL') {
            $where .= "1 or ";
        } else {
            $where .= "status='$val' or ";
        }
    }
    $where .= "0";
}

$query = "select ".WORKLIST.".id,summary,status,nickname,username,TIMESTAMPDIFF(SECOND,created,NOW()) as delta from ".WORKLIST. 
         " left join ".USERS." on ".WORKLIST.".owner_id=".USERS.".id".
         " $where order by ".USERS.".id desc limit " . ($page-1)*$limit . ",$limit";
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
