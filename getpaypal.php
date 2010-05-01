<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");
require_once('lib/Agency/Worklist/Filter.php');

if (empty($_SESSION['userid'])) {
    return;
}

$_REQUEST['name'] = '.reports';
$filter = new Agency_Worklist_Filter($_REQUEST);

// If the item id is set we will return the transaction info
if (isset($_REQUEST['get_t_id'])) {
    $t_id = $_REQUEST['get_t_id'];
    
    // Columns to get
	$info_q = "SELECT `id`,`fee_id`,`payment_gross`,`payment_fee`,`payee_paypal_email`,`currency`,
	          `masspay_txn_id`,`masspay_status_reason`,`masspay_run_status`,
	          DATE_FORMAT(`date_created`, '%m-%d-%Y') as paid_date,`status`,`deny_reason`  
	          FROM `".PAYPAL_LOG."` WHERE `id`=$t_id";
	
	$info_q = mysql_query($info_q);
	if (!$info_q)
	    return;
	while ($row = mysql_fetch_assoc($info_q)) {
	    $t_info = array($row['id'],$row['fee_id'],$row['payment_gross'],$row['payment_fee'],$row['payee_paypal_email'],
	              $row['currency'],$row['masspay_txn_id'],$row['masspay_status_reason'],$row['masspay_run_status'],
	              $row['paid_date'],$row['status'],$row['deny_reason']);
        // Return results as json
	    echo json_encode($t_info);
	    return;
	}
}

$limit = 30;

$from_date = mysql_real_escape_string($filter->getStart());
$to_date = mysql_real_escape_string($filter->getEnd());

$page = $filter->getPage();

$dateRangeFilter = '';
if (isset($from_date) && isset($to_date)) {
    $mysqlFromDate = GetTimeStamp($from_date);
    $mysqlToDate = GetTimeStamp($to_date);
    $dateRangeFilter = ($from_date && $to_date) ? "DATE(`date`) BETWEEN '".$mysqlFromDate."' AND '".$mysqlToDate."'" : "";
}

$sfilter = $filter->getStatus();
$ufilter = $filter->getUser();
$order = $filter->getOrder();
$jfilter = $filter->getJob();

$where = '';
if ($ufilter) {
    $where = "`user_id` = $ufilter AND ";
}

if ($sfilter) {
    if ($sfilter != 'ALL'){
        $where .= "`".PAYPAL_LOG."`.`status` = '$sfilter' AND "; 
    }
}

if ($jfilter) {
    $where .= "`worklist_id` = $jfilter AND";
}

// Add option for order results
$orderby = "ORDER BY ";
if ($order)    {
    // Order results chronologically
    if( $order == 'Chrono' )  {
        $orderby .= "`date_created` DESC";
    } else if ( $order == 'Alpha' ) { // Else order alphabetically
        $orderby .= "`nickname` ASC";
    }
}

if ($dateRangeFilter) {
    $where = $where . $dateRangeFilter;
}

$qcnt  = "SELECT count(*)";

// Columns to get
$qsel = "SELECT `summary`,`worklist_id`,`payment_gross`,DATE_FORMAT(`date_created`, '%m-%d-%Y') as paid_date,
        `nickname`,`".PAYPAL_LOG."`.`status`,`".PAYPAL_LOG."`.`id`";

$qsum = "SELECT SUM(`payment_gross`) as page_sum FROM (SELECT `payment_gross` ";

$qbody = " FROM `".PAYPAL_LOG."`
           LEFT JOIN `".FEES."` ON `".FEES."`.`id` = `fee_id`
           LEFT JOIN `".WORKLIST."` ON `".WORKLIST."`.`id` = `worklist_id`
           LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `user_id`
           WHERE $where ";

$qorder = "$orderby, `".PAYPAL_LOG."`.`status` ASC, `worklist_id` ASC LIMIT " . ($page-1)*$limit . ",$limit";



$rtCount = mysql_query("$qcnt $qbody");
if ($rtCount) {
    $row = mysql_fetch_row($rtCount);
    $items = intval($row[0]);
} else {
    $items = 0;
}
$cPages = ceil($items/$limit);

$qPageSumClose = "ORDER BY `nickname` ASC, `".PAYPAL_LOG."`.`status` ASC, `worklist_id` ASC LIMIT " . ($page-1)*$limit . ",$limit ) fee_sum ";
$sumResult = mysql_query("$qsum $qbody $qPageSumClose");

if ($sumResult) {
    $get_row = mysql_fetch_row($sumResult);
    $pageSum = $get_row[0];
} else {
    $pageSum = 0;
}

$qGrandSumClose = "ORDER BY `nickname` ASC, `".PAYPAL_LOG."`.`status` ASC, `worklist_id` ASC ) fee_sum ";
$grandSumResult = mysql_query("$qsum $qbody $qGrandSumClose");

if ($grandSumResult) {
    $get_row = mysql_fetch_row($grandSumResult);
    $grandSum = $get_row[0];
} else {
    $grandSum = 0;
}

$report = array(array($items, $page, $cPages,$pageSum,$grandSum));

// Construct json for history
$rtQuery = mysql_query("$qsel $qbody $qorder");
for ($i = 1; $rtQuery && $row=mysql_fetch_assoc($rtQuery); $i++) {
    $report[$i] = array($row['summary'], $row['worklist_id'], $row['payment_gross'],
                  $row['paid_date'], $row['nickname'], $row['status'], $row['id']);
}

$json = json_encode($report);
echo $json;

?>