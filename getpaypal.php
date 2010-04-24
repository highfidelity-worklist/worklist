<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include_once("functions.php");
require_once('lib/Worklist/Filter.php');

if (empty($_SESSION['userid'])) {
    return;
}

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

if (isset($_REQUEST['from_date'])) {
  $from_date = $_REQUEST['from_date'];
}
if (isset($_REQUEST['to_date'])) {
  $to_date = $_REQUEST['to_date'];
}

$page = isset($_REQUEST["page"])?$_REQUEST["page"]:1;

$dateRangeFilter = '';
if (isset($from_date) && isset($to_date)) {
    $mysqlFromDate = mysql_real_escape_string(GetTimeStamp($from_date));
    $mysqlToDate = mysql_real_escape_string(GetTimeStamp($to_date));
    $dateRangeFilter = ($from_date && $to_date) ? "DATE(`date`) BETWEEN '".$mysqlFromDate."' AND '".$mysqlToDate."'" : "";
}

$sfilter = isset($_REQUEST['sfilter']) ? $_REQUEST["sfilter"] : '';
$ufilter = isset($_REQUEST["ufilter"]) ? intval($_REQUEST["ufilter"]):0;
$order = isset( $_REQUEST['order'] ) ? $_REQUEST['order'] :'';
$jfilter = isset($_REQUEST['jfilter']) ? intval($_REQUEST["jfilter"]):0;

$WorklistFilter = new Worklist_Filter(array(
    Worklist_Filter::CONFIG_COOKIE_EXPIRY => (60 * 60 * 24 * 30),
    Worklist_Filter::CONFIG_COOKIE_PATH   => '/' . APP_BASE,
    Worklist_Filter::CONFIG_COOKIE_NAME => 'pp_reports'
));

$WorklistFilter->setSfilter($sfilter)
               ->setUfilter($ufilter)
               ->saveFilters();

$where = '';
if ($ufilter) {
    $where = "`user_id` = $ufilter ";
}

if ($sfilter) {
    if ($where != '') {
        $where .= " AND ";
    }
    if ($sfilter != 'ALL'){
        $where .= "`".PAYPAL_LOG."`.`status` = '$sfilter' "; 
    }
}

if ($jfilter) {
    $where .= " AND `worklist_id` = $jfilter ";
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
    if ($where != '') {
        $where .= " AND ";
    }
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