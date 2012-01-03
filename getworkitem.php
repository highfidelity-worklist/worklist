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

$query = "SELECT
        w.id,
        w.summary,
        c.nickname creator,
        w.status job_status,
        w.notes,
        p.name project,
        r.nickname runner
    FROM ".WORKLIST." w
    LEFT JOIN ".USERS." c ON w.creator_id = c.id
    LEFT JOIN ".USERS." r ON w.runner_id = r.id
    LEFT JOIN ".PROJECTS." p ON w.project_id = p.project_id
    WHERE w.id = '$item'
        AND (w.status <> 'DRAFT'
        OR (w.status = 'DRAFT' AND w.creator_id = '$userId'))";
$rt = mysql_query($query);
if ($rt) {
    $row = mysql_fetch_assoc($rt);
    $row['notes'] = preg_replace("/\r?\n/", "<br />", $row['notes']);
    $query1 = ' SELECT c.comment, u.nickname '
            . ' FROM ' . COMMENTS . ' AS c '
            . ' INNER JOIN ' . USERS . ' AS u ON c.user_id = u.id ' 
            . ' WHERE c.worklist_id = ' . $row['id']
            . ' ORDER BY c.id DESC '
            . ' LIMIT 1';

    $rtc = mysql_query($query1);
    if ($rt) {
        $rowc = mysql_fetch_assoc($rtc);
        $row['comment'] = preg_replace("/\r?\n/", "<br />", $rowc['comment']);
        $row['commentAuthor'] = $rowc['nickname'];
    } else {
        $row['comment'] = 'No comments yet.';
    }
    $json = json_encode($row);
} else {
    $json = json_encode(array('error' => "No data available"));
}
echo $json;
