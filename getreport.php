<?php
//  vim:ts=4:et

//  Copyright (c) 2009, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");

if (empty($_SESSION['userid']) || (empty($_SESSION['is_runner']) && empty($_SESSION['is_payer']))) {
    return;
}

$limit = 30;

if(isset($_REQUEST['from_date'])) {
  $from_date = $_REQUEST['from_date'];
}
if(isset($_REQUEST['to_date'])) {
  $to_date = $_REQUEST['to_date'];
}
if(isset($_REQUEST['paid_status'])) {
  $paidStatus = $_REQUEST['paid_status'];
}

$page = isset($_REQUEST["page"])?$_REQUEST["page"]:1;

$dateRangeFilter = '';
if(isset($from_date) && isset($to_date))
{
	$mysqlFromDate = mysql_real_escape_string(GetTimeStamp($from_date));
	$mysqlToDate = mysql_real_escape_string(GetTimeStamp($to_date));
	$dateRangeFilter = ($from_date && $to_date) ? " AND DATE(`date`) BETWEEN '".$mysqlFromDate."' AND '".$mysqlToDate."'" : "";
}

$paidStatusFilter = '';
if(isset($paidStatus) && ($paidStatus)!="ALL")
{
	$paidStatus= mysql_real_escape_string($paidStatus);
    $paidStatusFilter = " AND `".FEES."`.`paid` = ".$paidStatus."";
}

$ufilter = isset($_REQUEST["ufilter"])?intval($_REQUEST["ufilter"]):0;
$where = '';
if ($ufilter) {
    $where = " AND `".FEES."`.`user_id` = $ufilter";
}

if($dateRangeFilter) {
  $where = $where . $dateRangeFilter;
}

if($paidStatusFilter) {
  
  $where = $where . $paidStatusFilter;
}

$qcnt  = "SELECT count(*)";
$qsel = "SELECT `".FEES."`.id as fee_id, DATE_FORMAT(`paid_date`, '%m-%d-%Y') as paid_date,`worklist`.`funded`,  `worklist_id`,`summary`,`desc`,`status`,`".USERS."`.`nickname` as `payee`,`".FEES."`.`amount`";
$qsum = "SELECT SUM(`amount`) as page_sum FROM (SELECT `amount` ";
$qbody = " FROM `".FEES."`
           INNER JOIN `".WORKLIST."` ON `worklist`.`id` = `".FEES."`.`worklist_id`
           LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
           WHERE `amount` != 0 AND `".FEES."`.`withdrawn` = 0 AND `worklist`.status = 'DONE' $where ";
$qorder = "ORDER BY `".USERS."`.`nickname` ASC, `status` ASC, `worklist_id` ASC LIMIT " . ($page-1)*$limit . ",$limit";

$rtCount = mysql_query("$qcnt $qbody");
if ($rtCount) {
    $row = mysql_fetch_row($rtCount);
    $items = intval($row[0]);
} else {
    $items = 0;
}
$cPages = ceil($items/$limit); 
//echo "$qsel $qbody";

$qSumClose = " LIMIT " . ($page-1)*$limit . ",$limit ) fee_sum ";
$sumResult = mysql_query("$qsum $qbody $qSumClose");
if ($sumResult) {
    $get_row = mysql_fetch_row($sumResult);
    $pageSum = $get_row[0];
} else {
    $pageSum = 0;
}
$report = array(array($items, $page, $cPages,$pageSum));


// Construct json for history
$rtQuery = mysql_query("$qsel $qbody $qorder");
for ($i = 1; $rtQuery && $row=mysql_fetch_assoc($rtQuery); $i++)
{
    $report[$i] = array($row['worklist_id'], $row['fee_id'], $row['summary'], $row['desc'], $row['funded'], $row['payee'], $row['amount'], $row['paid_date']);
}

$json = json_encode($report);
echo $json;     
