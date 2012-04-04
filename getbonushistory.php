<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("functions.php");

checkLogin();

if (empty($_SESSION['is_runner'])) {
    exit(0);
}

$limit = 7;
$page = (int) $_REQUEST['page'];
$rid = (int) $_REQUEST['rid'];
$uid = (int) $_REQUEST['uid'];

$where = 'AND `'.FEES.'`.`payer_id` = ' . $uid;

// Add option for order results
$orderby = "ORDER BY `".FEES."`.`date` DESC";

$qcnt = "SELECT count(*)";
$qsel = "SELECT DATE_FORMAT(`date`, '%m-%d-%Y') as date,
                `amount`,
                `nickname`,
                `desc`";

$qbody = " FROM `".FEES."`
           LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
           WHERE `bonus` = 1 AND `amount` != 0 $where ";

$qorder = "$orderby LIMIT " . ($page - 1) * $limit . ",$limit";

$rtCount = mysql_query("$qcnt $qbody");
if ($rtCount) {
    $row = mysql_fetch_row($rtCount);
    $items = intval($row[0]);
} else {
    $items = 0;
    die(json_encode(array()));
}
$cPages = ceil($items/$limit);
$report = array(array($items, $page, $cPages));

// Construct json for history
$rtQuery = mysql_query("$qsel $qbody $qorder");
for ($i = 1; $rtQuery && $row = mysql_fetch_assoc($rtQuery); $i++) {
    $report[$i] = array($row['date'],
                        $row['amount'],
                        $row['nickname'],
                        $row['desc']);
}

$json = json_encode($report);
echo $json;
    
?>
