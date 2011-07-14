<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");
require_once('lib/Agency/Worklist/Filter.php');

$limit = 30;

$_REQUEST['name'] = '.reports';
$filter = new Agency_Worklist_Filter($_REQUEST);
$from_date = mysql_real_escape_string($filter->getStart());
$to_date = mysql_real_escape_string($filter->getEnd());
$paidStatus = $filter->getPaidstatus();
$page = $filter->getPage();
$w2_only = (int) $_REQUEST['w2_only'];
$dateRangeFilter = '';

if (isset($from_date) || isset($to_date)) {
    $mysqlFromDate = GetTimeStamp($from_date);
    $mysqlToDate = GetTimeStamp($to_date);
    $dateRangeFilter = " AND DATE(`date`) BETWEEN '".$mysqlFromDate."' AND '".$mysqlToDate."'" ;
}

$w2Filter = '';
if ($w2_only) {
    $w2Filter = " AND " . USERS . ".`has_w2` = 1";
}

$paidStatusFilter = '';
if (isset($paidStatus) && ($paidStatus) !="ALL") {
    $paidStatus= mysql_real_escape_string($paidStatus);
    $paidStatusFilter = " AND `".FEES."`.`paid` = ".$paidStatus."";
}

$sfilter = $filter->getStatus();
$pfilter = $filter->getProjectId();
$fundFilter = $filter->getFund_id();
$ufilter = $filter->getUser();
$order = $filter->getOrder();
$dir = $filter->getDir();
$type = $filter->getType();

$queryType = isset( $_REQUEST['qType'] ) ? $_REQUEST['qType'] :'detail';

$where = '';
if ($ufilter) {
    $where = " AND `".FEES."`.`user_id` = $ufilter ";
}

if ($sfilter){
    if($sfilter != 'ALL') {
        $where .= " AND `" . WORKLIST . "`.status = '$sfilter' "; 
    }
}
if ($pfilter) {
    // ignore the fund filter?
    if($pfilter != 'ALL') {
      $where .= " AND `" . WORKLIST . "`.project_id = '$pfilter' "; 
    } elseif ($fundFilter) {
        $where .= " AND `".PROJECTS."`.`fund_id` = " . $fundFilter;
    }
} elseif (isset($fundFilter) && $fundFilter != -1) {
    if ($fundFilter == 0) {
        $where .= " AND `".PROJECTS."`.`fund_id` = " . $fundFilter . " || `".PROJECTS."`.`fund_id` IS NULL";
    } else {
        $where .= " AND `".PROJECTS."`.`fund_id` = " . $fundFilter;
    }
}



if ($type == 'Fee') {
    $where .= " AND `".FEES."`.expense = 0 AND `".FEES."`.rewarder = 0 AND `".FEES. "`.bonus = 0";
} else if ($type == 'Expense') {
    $where .= " AND `".FEES."`.expense = 1 AND `".FEES."`.rewarder = 0 AND `".FEES. "`.bonus = 0";
} else if ($type == 'Bonus') {
    $where .= " AND (rewarder = 1 OR bonus = 1)";
} else if ($type == 'ALL') {
    $where .= " AND `".FEES."`.expense = 0 AND `".FEES."`.rewarder = 0";
}

// Add option for order results
$orderby = "ORDER BY ";
switch ($order) {
    case 'date':
        $orderby .= "`".FEES."`.`date`";
        break;

    case 'name':
    case 'payee':
        $orderby .= "`".USERS."`.`nickname`";
        break;

    case 'desc':
        $orderby .= "`".FEES."`.`desc`";
        break;
        
    case 'summary':
        $orderby .= "`".WORKLIST."`.`summary`";
        break;

    case 'paid_date':
        $orderby .= "`".FEES."`.`paid_date`";
        break;

    case 'id':
        $orderby .= "`".FEES."`.`worklist_id`";
        break;

    case 'fee':
        $orderby .= "`".FEES."`.`amount`";
        break;

    case 'jobs':
        $orderby .= "`jobs`";
        break;

    case 'avg_job':
        $orderby .= "`average`";
        break;

    case 'total_fees':
        $orderby .= "`total`";
        break;
}

if ($dateRangeFilter) {
    $where .= $dateRangeFilter;
}

if (! empty($w2Filter)) {
    $where .= $w2Filter;
}

if ($paidStatusFilter) {
  $where .= $paidStatusFilter;
}

if($queryType == "detail") {

    $qcnt = "SELECT count(*)";
    $qsel = "SELECT `".FEES."`.id AS fee_id, DATE_FORMAT(`paid_date`, '%m-%d-%Y') AS paid_date,`worklist_id`,`".WORKLIST."`.`summary` AS `summary`,`desc`,`status`,`".USERS."`.`nickname` AS `payee`,`".FEES."`.`amount`, `".USERS."`.`paypal` AS `paypal`, `expense` AS `expense`,`rewarder` AS `rewarder`,`bonus` AS `bonus`, `" . USERS . "`.`has_W2` AS `has_W2`";
    $qsum = "SELECT SUM(`amount`) as page_sum FROM (SELECT `amount` ";
    $qbody = " FROM `".FEES."`
               LEFT JOIN `".WORKLIST."` ON `".WORKLIST."`.`id` = `".FEES."`.`worklist_id`
               LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
               LEFT JOIN ".PROJECTS." ON `".WORKLIST."`.`project_id` = `".PROJECTS."`.`project_id`
               WHERE `amount` != 0 AND `".FEES."`.`withdrawn` = 0 $where ";
    $qorder = "$orderby $dir, `status` ASC, `worklist_id` ASC LIMIT " . ($page - 1) * $limit . ",$limit";

$rtCount = mysql_query("$qcnt $qbody");
if ($rtCount) {
    $row = mysql_fetch_row($rtCount);
    $items = intval($row[0]);
} else {
    $items = 0;
    die(json_encode(array()));
}
$cPages = ceil($items/$limit);

$qPageSumClose = "$orderby $dir, `status` ASC, `worklist_id` ASC LIMIT " . ($page - 1) * $limit . ", $limit ) fee_sum ";

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
$report = array(array($items, $page, $cPages, $pageSum, $grandSum));


// Construct json for history
$rtQuery = mysql_query("$qsel $qbody $qorder");
for ($i = 1; $rtQuery && $row = mysql_fetch_assoc($rtQuery); $i++) {
    $report[$i] = array($row['worklist_id'], $row['fee_id'], $row['summary'], $row['desc'], $row['payee'], $row['amount'], $row['paid_date'], $row['paypal'],$row['expense'],$row['rewarder'],$row['bonus'],$row['has_W2']);
}

$concatR = '';
if ($row['rewarder'] ==1) {
    $concatR = "R";
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
    $rollupColumn = getRollupColumn('`date`', $daysInRange);
    $dateRangeType = $rollupColumn['rollupRangeType'];

    $qbody = " FROM `".FEES."`
	      LEFT JOIN `".WORKLIST."` ON `worklist`.`id` = `".FEES."`.`worklist_id`
	      LEFT JOIN `".USERS."` ON `".USERS."`.`id` = `".FEES."`.`user_id`
          LEFT JOIN ".PROJECTS." ON `".WORKLIST."`.`project_id` = `".PROJECTS."`.`project_id`
          
	      WHERE `amount` != 0 AND `".FEES."`.`withdrawn` = 0 $where ";
    $qgroup = " GROUP BY fee_date";

    $qcols = "SELECT " . $rollupColumn['rollupQuery'] . " as fee_date, count(1) as fee_count,sum(amount) as total_fees, count(distinct user_id) as unique_people ";

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
    $json_data = array('fees' => fillAndRollupSeries($fromDate, $toDate, $fees, false, $dateRangeType), 
        'uniquePeople' => fillAndRollupSeries($fromDate, $toDate, $uniquePeople, false, $dateRangeType), 
        'feeCount' => fillAndRollupSeries($fromDate, $toDate, $feeCount, false, $dateRangeType), 
        'labels' => fillAndRollupSeries($fromDate, $toDate, null, true, $dateRangeType), 
        'fromDate' => $fromDate, 'toDate' => $toDate);
    $json = json_encode($json_data);
    echo $json;
} else if($queryType == "payee") {
    $payee_report = array();
    $page = $filter->getPage();
    $count_query = " SELECT count(1) FROM ";
    $query = " SELECT   `nickname` AS payee_name, count(1) AS jobs, sum(`amount`) / count(1) AS average, sum(`amount`) AS total  FROM `".FEES."` 
               LEFT JOIN `".USERS."` ON `".FEES."`.`user_id` = `".USERS."`.`id`
               LEFT JOIN `".WORKLIST."` ON `worklist`.`id` = `".FEES."`.`worklist_id`
               LEFT JOIN ".PROJECTS." ON `".WORKLIST."`.`project_id` = `".PROJECTS."`.`project_id`
               WHERE  `".FEES."`.`paid` = 1  ".$where." GROUP BY  `user_id` ";
    $result_count = mysql_query($count_query."(".$query.") AS payee_name");
    if ($result_count) {
        $count_row = mysql_fetch_row($result_count);
        $items = intval($count_row[0]);
    } else {
        $items = 0;
        die(json_encode(array()));
    }
    $countPages = ceil($items/$limit);
    $payee_report[] = array($items, $page, $countPages);
    if(!empty($_REQUEST['defaultSort']) && $_REQUEST['defaultSort'] == 'total_fees') {
        $query .= " ORDER BY total DESC";
    } else { 
        $query .= $orderby." ".$dir;
    }
    $query .= "  LIMIT " . ($page - 1) * $limit . ",$limit";
    $result = mysql_query($query);
    if($result && mysql_num_rows($result) > 0) {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
         $payee_name = $row['payee_name']; 
         $jobs = $row['jobs'];
         $total = number_format($row['total'], 2, '.', '');
         $average =  number_format($row['average'], 2, '.', '');
         $payee_report[] = array($payee_name, $jobs, $average, $total);
      }
    }
    echo json_encode($payee_report);
}

function  getRollupColumn($columnName, $daysInRange) {
    $dateRangeType = 'd';
    $dateRangeQuery = "DATE_FORMAT(" .$columnName . ",'%Y-%m-%d') ";
    if($daysInRange > 31 && $daysInRange <= 180) {
        $dateRangeType = 'w';
        $dateRangeQuery = "yearweek(" .$columnName . ", 3) ";
    } else if($daysInRange > 180 && $daysInRange <= 365) {
        $dateRangeType = 'm';
        $dateRangeQuery = "DATE_FORMAT(" .$columnName . ",'%Y-%m') ";
    } else if($daysInRange > 365 && $daysInRange <= 730) {
        $dateRangeType = 'q';
        $dateRangeQuery = "concat(year(" .$columnName . "),QUARTER(" .$columnName . ")) ";
    } else if($daysInRange > 730) {
        $dateRangeType = 'y';
        $dateRangeQuery = "DATE_FORMAT(" .$columnName . ",'%Y') ";
    }
    return array('rollupRangeType' => $dateRangeType, 'rollupQuery' => $dateRangeQuery);
}

function  getMySQLDate($sourceDate) {
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
function quarterByDate($date) {
    return (int)floor(date('m', strtotime($date)) / 3.1) + 1;
}

/** 
* Fills a series with linear data, filling any gaps with null values. 
* The resulting array can directly be used in a chart assuming the labels use same data set.
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
