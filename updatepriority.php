<?php
//  vim:ts=4:et

//  Copyright (c) 2009, LoveMachine Inc.  
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
$bump = isset($_REQUEST["bump"]) ? intval($_REQUEST["bump"]) : 0;
if ($id < 0 || $previd < 0) {
    echo 'error: args';
    return;
}

$query = "set @pri := -1";
$rt = mysql_query($query);

if ($rt) {
    $query = "update ".WORKLIST." set priority=(@pri := @pri + 2) where 1 order by priority";
    $rt = mysql_query($query);
}

if ($rt) {
    $query = "select summary, priority from ".WORKLIST." where id='$id'";
    $rt = mysql_query($query);
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $summary = $row['summary'];    
        $origpri = $row['priority'];
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
    if ($bump > 0) $bump = "+$bump";

    $data = array();
    $data['user'] = JOURNAL_API_USER;
    $data['pwd'] = sha1(JOURNAL_API_PWD);
    $data['message'] = $_SESSION['nickname'] . " $bump $summary";
    $prc = postRequest(JOURNAL_API_URL, $data);
} else {
    echo 'error: database';
    return;
}

echo 'ok';
