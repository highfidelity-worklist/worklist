<?php
//  vim:ts=4:et

//  Copyright (c) 2009, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");

if (empty($_SESSION['userid']) || empty($_SESSION['is_runner'])) {
    return;
}

$limit = 30;
$page = isset($_REQUEST["page"])?$_REQUEST["page"]:1;

$ufilter = isset($_REQUEST["ufilter"])?intval($_REQUEST["ufilter"]):0;
$where = '';
if ($ufilter) {
    $where = "AND `".FEES."`.`user_id` = $ufilter";
}

$qcnt  = "SELECT count(*)";
$qsel = "SELECT `".FEES."`.id as fee_id, `worklist_id`,`summary`,`desc`,`status`,`".USERS."`.`nickname` as `payee`,`".FEES."`.`amount`, `category`";
$qbody = "FROM `".FEES."`
          INNER JOIN `".WORKLIST."` ON `worklist`.`id` = `".FEES."`.`worklist_id`
          LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
          WHERE `amount` != 0 AND `".FEES."`.`withdrawn` = 0 AND `".FEES."`.`paid` != 1 $where";
$qorder = "ORDER BY `".USERS."`.`nickname` ASC, `status` ASC, `worklist_id` ASC LIMIT " . ($page-1)*$limit . ",$limit";

$rtCount = mysql_query("$qcnt $qbody");
if ($rtCount) {
    $row = mysql_fetch_row($rtCount);
    $items = intval($row[0]);
} else {
    $items = 0;
}
$cPages = ceil($items/$limit); 
$report = array(array($items, $page, $cPages));

// Construct json for history
$rtQuery = mysql_query("$qsel $qbody $qorder");
for ($i = 1; $rtQuery && $row=mysql_fetch_assoc($rtQuery); $i++)
{
    $report[$i] = array($row['worklist_id'], $row['fee_id'], $row['summary'], $row['desc'], $row['status'], $row['payee'], $row['amount'], $row['category']);
}

$json = json_encode($report);
echo $json;     
