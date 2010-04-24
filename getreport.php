<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");

if (empty($_SESSION['userid'])) {
    return;
}

$limit = 30;

if(isset($_REQUEST['from_date'])) {
  $from_date = mysql_real_escape_string($_REQUEST['from_date']);
}
if(isset($_REQUEST['to_date'])) {
  $to_date = mysql_real_escape_string($_REQUEST['to_date']);
}
if(isset($_REQUEST['paid_status'])) {
  $paidStatus = $_REQUEST['paid_status'];
}

$page = isset($_REQUEST["page"])?$_REQUEST["page"]:1;

$dateRangeFilter = '';
if(isset($from_date) && isset($to_date))
{
	$mysqlFromDate = GetTimeStamp($from_date);
	$mysqlToDate = GetTimeStamp($to_date);
	$dateRangeFilter = ($from_date && $to_date) ? " AND DATE(`date`) BETWEEN '".$mysqlFromDate."' AND '".$mysqlToDate."'" : "";
}

$paidStatusFilter = '';
if(isset($paidStatus) && ($paidStatus)!="ALL")
{
	$paidStatus= mysql_real_escape_string($paidStatus);
    $paidStatusFilter = " AND `".FEES."`.`paid` = ".$paidStatus."";
}

$sfilter = isset($_REQUEST['sfilter']) ? $_REQUEST["sfilter"] : '';
$ufilter = isset($_REQUEST["ufilter"])?intval($_REQUEST["ufilter"]):0;
$order = isset( $_REQUEST['order'] ) ? $_REQUEST['order'] :'';
$queryType = isset( $_REQUEST['qType'] ) ? $_REQUEST['qType'] :'detail';

require_once 'lib/Worklist/Filter.php';
$WorklistFilter = new Worklist_Filter(array(
    Worklist_Filter::CONFIG_COOKIE_EXPIRY => (60 * 60 * 24 * 30),
    Worklist_Filter::CONFIG_COOKIE_PATH   => '/' . APP_BASE,
    Worklist_Filter::CONFIG_COOKIE_NAME => 'reports'
));

$WorklistFilter->setSfilter($sfilter)
               ->setUfilter($ufilter)
               ->saveFilters();

$where = '';
if ($ufilter) {
    $where = " AND `".FEES."`.`user_id` = $ufilter ";
}

if ($sfilter){
    if($sfilter != 'ALL'){
      $where .= " AND `" . WORKLIST . "`.status = '$sfilter' "; 
    }
}

// Add option for order results
$orderby = "ORDER BY ";
if( $order )    {
    if( $order == 'date' )  {
        $orderby .= "`".FEES."`.`date` DESC";
    } else if( $order == 'name' ) {
        $orderby .= "`".USERS."`.`nickname` ASC";
    }
}

if($dateRangeFilter) {
  $where = $where . $dateRangeFilter;
}

if($paidStatusFilter) {

  $where = $where . $paidStatusFilter;
}
if($queryType == "detail") {

$qcnt  = "SELECT count(*)";
$qsel = "SELECT `".FEES."`.id as fee_id, DATE_FORMAT(`paid_date`, '%m-%d-%Y') as paid_date,  `worklist_id`,`summary`,`desc`,`status`,`".USERS."`.`nickname` as `payee`,`".FEES."`.`amount`";
$qsum = "SELECT SUM(`amount`) as page_sum FROM (SELECT `amount` ";
$qbody = " FROM `".FEES."`
           INNER JOIN `".WORKLIST."` ON `worklist`.`id` = `".FEES."`.`worklist_id`
           LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
           WHERE `amount` != 0 AND `".FEES."`.`withdrawn` = 0 $where ";
$qorder = "$orderby, `status` ASC, `worklist_id` ASC LIMIT " . ($page-1)*$limit . ",$limit";

$rtCount = mysql_query("$qcnt $qbody");
if ($rtCount) {
    $row = mysql_fetch_row($rtCount);
    $items = intval($row[0]);
} else {
    $items = 0;
}
$cPages = ceil($items/$limit);

$qPageSumClose = "ORDER BY `".USERS."`.`nickname` ASC, `status` ASC, `worklist_id` ASC LIMIT " . ($page-1)*$limit . ",$limit ) fee_sum ";
$sumResult = mysql_query("$qsum $qbody $qPageSumClose");
if ($sumResult) {
    $get_row = mysql_fetch_row($sumResult);
    $pageSum = $get_row[0];
} else {
    $pageSum = 0;
}
$qGrandSumClose = "ORDER BY `".USERS."`.`nickname` ASC, `status` ASC, `worklist_id` ASC ) fee_sum ";
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
for ($i = 1; $rtQuery && $row=mysql_fetch_assoc($rtQuery); $i++)
{
    $report[$i] = array($row['worklist_id'], $row['fee_id'], $row['summary'], $row['desc'], $row['payee'], $row['amount'], $row['paid_date']);
}

$json = json_encode($report);
echo $json;
} else if ($queryType == "chart" ) {
    $fees = array();
    $uniquePeople = array();
    $feeCount = array();
    if(isset($from_date)) {
      $fromDate = getMySQLDate($from_date);
    }
    if(isset($to_date)) {
      $toDate = getMySQLDate($to_date);
    }
    $fromDateTime = mktime(0,0,0,substr($fromDate,5,2),  substr($fromDate,8,2), substr($fromDate,0,4));
    $toDateTime = mktime(0,0,0,substr($toDate,5,2),  substr($toDate,8,2), substr($toDate,0,4));

    $daysInRange = round( abs($toDateTime-$fromDateTime) / 86400, 0 );
    $dateRangeType = 'd';
    $dateRangeQuery = "DATE_FORMAT(`date`,'%Y-%m-%d') ";
    if($daysInRange > 31 && $daysInRange <= 180) {
      $dateRangeType = 'w';
      $dateRangeQuery = "yearweek(`date`, 3) ";
    } else if($daysInRange > 180 && $daysInRange <= 365) {
      $dateRangeType = 'm';
      $dateRangeQuery = "DATE_FORMAT(`date`,'%Y-%m') ";
    } else if($daysInRange > 365 && $daysInRange <= 730) {
      $dateRangeType = 'q';
      $dateRangeQuery = "concat(year(`date`),QUARTER(`date`)) ";
    } else if($daysInRange > 730) {
      $dateRangeType = 'y';
      $dateRangeQuery = "DATE_FORMAT(`date`,'%Y') ";
    }

    $qbody = " FROM `".FEES."`
	      INNER JOIN `".WORKLIST."` ON `worklist`.`id` = `".FEES."`.`worklist_id`
	      LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
	      WHERE `amount` != 0 AND `".FEES."`.`withdrawn` = 0 $where ";
    $qgroup = " GROUP BY fee_date";

    $qcols = "SELECT " . $dateRangeQuery . " as fee_date, count(1) as fee_count,sum(amount) as total_fees, count(distinct user_id) as unique_people ";

    $res = mysql_query("$qcols $qbody $qgroup");
    if($res && mysql_num_rows($res) > 0) {
        while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
	  if ($row['fee_count'] >=1 ) {
            $feeCount[$row['fee_date']] = $row['fee_count'];
            $fees[$row['fee_date']] = $row['total_fees'];
            $uniquePeople[$row['fee_date']] = $row['unique_people'];
           }
        }
    }
    $json_data = array('fees' => fillAndRollupSeries($fromDate, $toDate, $fees, false, $dateRangeType), 'uniquePeople' => fillAndRollupSeries($fromDate, $toDate, $uniquePeople, false, $dateRangeType), 'feeCount' => fillAndRollupSeries($fromDate, $toDate, $feeCount, false, $dateRangeType), 'labels' => fillAndRollupSeries($fromDate, $toDate, null, true, $dateRangeType), 'fromDate' => $fromDate, 'toDate' => $toDate);
    $json = json_encode($json_data);
    echo $json;
}

function  getMySQLDate($sourceDate)
{
    if (empty($sourceDate)) $sourceDate = date('Y/m/d');
    $date_array = explode("/",$sourceDate); // split the array

    $targetDate = mktime(0, 0, 0, $date_array[0]  , $date_array[1], $date_array[2]);

    return date('Y-m-d',$targetDate); 
}

/**
 * quarterByDate()
 * 
 * Return numeric representation of a quarter from passed free-form date.
 * 
 * @param mixed $date
 * @return integer
 */
function quarterByDate($date)
{
    return (int)floor(date('m', strtotime($date)) / 3.1) + 1;
}

/** 
* Fills a series with linear data, filling any gaps with null values. 
* The resulting array can directly be used in a chart assuming the labels use same data set.
*
*
*/
function fillAndRollupSeries($strDateFrom, $strDateTo, $arySeries, $fillWithDate, $dateType = 'd') {
  $arySeriesData = array();
  $aryRollupData = array();
  $currentDate = mktime(0,0,0,substr($strDateFrom,5,2),  substr($strDateFrom,8,2), substr($strDateFrom,0,4));
  $toDate = mktime(0,0,0,substr($strDateTo,5,2),  substr($strDateTo,8,2), substr($strDateTo,0,4));
  $xLabels = array();
  $x1Labels = array();
  $x2Labels = array();
  $xFullLabels = array();
  $previousDate = $currentDate;
  while ($currentDate <= $toDate) {
    $x2Label = null;
    $xFullLabel = null;
    if($dateType == 'd') {
      $key = date('Y-m-d', $currentDate);
      $x1Label = date('d',$currentDate);
      $xFullLabel = date('m/d/Y', $currentDate);
      if(date('d',$currentDate) == '01' || sizeof($x1Labels) == 0) {
	$x2Label= date('M',$currentDate) ;
      } 

      $currentDate = mktime(0,0,0,substr($key,5,2),  substr($key,8,2)+1, substr($key,0,4));
    } else if($dateType == 'w') {
      $key = date('oW', $currentDate);
      $weekStart = strtotime('+0 week mon', $currentDate);
      $weekEnd = strtotime('+0 week sun', $currentDate);
      if(date('m', $weekStart) == date('m', $weekEnd)) {
	$x1Label = date('d',$weekStart) ."-" . date('d',$weekEnd) ; 
	$xFullLabel = date('M d',$weekStart) ." - " . date('d, Y',$weekEnd) ; 
      } else {
	$x1Label = date('M d',$weekStart) ."-" . date('M d',$weekEnd) ; 
	$xFullLabel = date('M d',$weekStart) ." - " . date('M d, Y',$weekEnd) ; 
      }
      if (date('m',$weekStart) != date('m',$previousDate)) {
	$x2Label = date('M',$weekStart);
      }

      if(date('W',$currentDate) == '01' || sizeof($x1Labels) == 0) {
	$x2Label = date('M',$weekStart) . " " .date('Y',$currentDate) ;
      }
      // Store the current date as previous for identifying group changes
      $previousDate = $currentDate ;
      $currentDate = strtotime('+1 week', $weekStart); 
    } else if($dateType == 'm') {
      $key = date('Y-m', $currentDate);
      $x1Label = date('M',$currentDate);
      if(date('m',$currentDate) == '01' || sizeof($x1Labels) == 0) {
	$x2Label = date('Y',$currentDate) ;
      }
      $xFullLabel = date('M Y',$currentDate); 
      $currentDate = mktime(0,0,0,substr($key,5,2)+1,  1, substr($key,0,4));
    } else if($dateType == 'q') {
      $currentQuarter = quarterByDate(date('Y-m', $currentDate));
      $key = date('Y', $currentDate) . $currentQuarter;
      $x1Label  = date('Y', $currentDate) . ' Q' . $currentQuarter;
      $xFullLabel = $x1Label; 
      $quarterStart = mktime(0,0,0, 1+ ($currentQuarter - 1) * 3,  1, substr($key,0,4));
      $currentDate = strtotime('+3 month', $quarterStart); 
    } else if ($dateType == 'y') {
      $key = date('Y', $currentDate);
      $x1Label  = date('Y',$currentDate);
      $xFullLabel = $x1Label; 
      $currentDate = mktime(0,0,0,1,  1, substr($key,0,4)+1);
    } 

    if($fillWithDate) {
      $x1Labels[] = $x1Label;
      $x2Labels[] = $x2Label;
      $xFullLabels[]= $xFullLabel;
    } else if(isset($arySeries[$key])) {
	$arySeriesData[] = $arySeries[$key];
    } else {
	$arySeriesData[] = null;
    }
 }
  if($fillWithDate) {
    $arySeriesData = array('x1' => $x1Labels, 'x2' => $x2Labels, 'xFull' => $xFullLabels);
  }
  return $arySeriesData;
}
