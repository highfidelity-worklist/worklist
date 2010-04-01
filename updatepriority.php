<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history

include("config.php");
include("class.session_handler.php");
include("functions.php");

if (!isset($_SESSION['userid'])) {
    echo 'error: unauthorized';
    return;
}

$id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : -1;
$previd = isset($_REQUEST["previd"]) ? intval($_REQUEST["previd"]) : -1;
if ($id < 0 || $previd < 0) {
    echo 'error: args';
    return;
}

$query = "set @pri := -1";
$rt = mysql_query($query);

// calculating position change
$query = "SET @rownum := 0";
mysql_unbuffered_query($query);

if ($rt) {
    $query = "update ".WORKLIST." set priority=(@pri := @pri + 2) where 1 order by priority";
    $rt = mysql_query($query);
}

if ($rt) {

    $query = "SELECT summary, priority, position FROM (
SELECT *, @rownum := @rownum+1 AS position FROM `".WORKLIST."` WHERE `status` = 'BIDDING' ORDER BY `priority` ) AS position_table WHERE id = '$id'";
    $rt = mysql_query($query);
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $summary = $row['summary'];    
        $origpri = $row['priority'];
        $origpos = $row['position'];
    }
}

if ($rt) {
    if ($previd > 0) {
        $query = "select priority from ".WORKLIST." where id='$previd'";
        $rt = mysql_query($query);
        if ($rt) {
            $row = mysql_fetch_assoc($rt);
            $newpri = $row['priority']+1;
        }
    } else {
        $newpri = 0;
    }
}

if ($rt) {
    $query = "update ".WORKLIST." set priority='$newpri' where id='$id'";
    $rt = mysql_query($query);
}

if ($rt) {

    // getting position afterwards
    $query = "SET @rownum := 0";
    mysql_unbuffered_query($query);

    $query = "SELECT position FROM (
SELECT *, @rownum := @rownum+1 AS position FROM `".WORKLIST."` WHERE `status` = 'BIDDING' ORDER BY `priority` ) AS position_table WHERE id = '$id'";
    $rt = mysql_query($query);
    $row = mysql_fetch_assoc($rt);
    $newpos = $row['position'];

    if($origpos != $newpos){ // they will be the same if position is changed near usual items. not BIDDING
	$data = array();
	$data['user'] = JOURNAL_API_USER;
	$data['pwd'] = sha1(JOURNAL_API_PWD);
	$data['message'] = $_SESSION['nickname'] . " moved #$id: $summary from position $origpos to position $newpos";
	$prc = postRequest(JOURNAL_API_URL, $data);
    }

} else {
    echo 'error: database';
    return;
}

echo 'ok';
