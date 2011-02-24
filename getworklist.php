<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history


include("config.php");
include("class.session_handler.php");
require_once('lib/Agency/Worklist/Filter.php');

ob_start();
// Test for a string containing 0 characters of anything other than 0-9 and #
// After a quick trim ofcourse! :)
// I knowww regex is usually the bad first stop, but there would be no back tracking in this
// particular regular expression
if (preg_match("/^\#\d+$/",$query = trim($_REQUEST['query']))) {
	// if we reach here, include workitem package
	include_once("workitem.class.php");
	$workitem = new Workitem();
	if ($workitem->idExists($id = ltrim($query,"#"))) {
		$obj = array('redirect',$id);
		die(JSON_encode($obj));
	}
	// if we're not dead continue on!
}
$limit = 30;

$_REQUEST['name'] = '.worklist';
$filter = new Agency_Worklist_Filter($_REQUEST);

$is_runner = !empty( $_SESSION['is_runner'] ) ? 1 : 0;

$sfilter = explode('/', $filter->getStatus());
$ufilter = $filter->getUser();
$pfilter = $filter->getProjectId();
$ofilter = $filter->getSort();
$subofilter = $filter->getSubSort();
$dfilter = $filter->getDir();
$page = $filter->getPage();

$where = '';
$unpaid_join = '';
$bFilterStatusContainBidding=false;
if (!empty($sfilter)) {
    $where = "where (";
    foreach ($sfilter as $val) {

        $val = strtoupper(mysql_real_escape_string($val));
        if ($val == 'ALL') {
            $where .= "1 or ";
        } else {
            $where .= "status='$val' or ";
        }
        if( $val == 'BIDDING' ) {
            $bFilterStatusContainBidding=true;
        }
    }
    $where .= "0)";
}

if (!empty($ufilter) && $ufilter != 'ALL') {
    if (empty($where)) {
        $where = "where ";
    } else {
        $where .= " and ";
    }

    // Runner and query is User->Bidding we only show the items the user
    // is currently bidding on.
    if ($is_runner) {
        $severalStatus = "";
        foreach ($sfilter as $val) {
            if ($val == 'ALL') {
                $status_cond = "";
            } else {
                $status_cond = "status='$val' AND";
            }
            if ($val == 'BIDDING') {
                $where .= $severalStatus . "( $status_cond ( mechanic_id='$ufilter' OR `bidder_id`='$ufilter' OR `runner_id` = '$ufilter'))";
            } else  {
                $where .= $severalStatus . "( $status_cond ( creator_id='$ufilter' OR runner_id='$ufilter' OR mechanic_id='$ufilter'  OR `".FEES."`.user_id='$ufilter'))";
            }
            $severalStatus = " OR ";
        }
    } else { // Else if the current user is looking for his bids, we show, else nothing.
        $userId = isset($_SESSION['userid'])? $_SESSION['userid'] : 0;
        if( $userId == $ufilter )  {
            $where .= "(creator_id='$ufilter' OR runner_id='$ufilter' OR mechanic_id='$ufilter' OR (`".FEES."`.user_id='$ufilter' AND `".FEES."`.`withdrawn` = 0)
                        OR (`bidder_id`='$ufilter' AND `withdrawn` = 0))";
        }   else    {
            $where .= "(creator_id='$ufilter' OR runner_id='$ufilter' OR mechanic_id='$ufilter' OR (`".FEES."`.user_id='$ufilter' AND `".FEES."`.`withdrawn` = 0))";
        }
    }
}
if (!empty($pfilter) && $pfilter != 'ALL') {
    if (empty($where)) {
        $where = "where ";
    } else {
        $where .= " and ";
    }
    $where .= " `".WORKLIST."`.`project_id` = '{$pfilter}' ";
}

$query = $filter->getQuery();
$commentsjoin ="";
if($query!='' && $query!='Search...') {
    $searchById = false;
     if(is_numeric(trim($query))) {
        $rt = mysql_query("select count(*) from ".WORKLIST." LEFT JOIN `".FEES."` ON `".WORKLIST."`.`id` = `".FEES."`.`worklist_id` $where AND `".WORKLIST."`.`id` = " .$query);
        $row = mysql_fetch_row($rt);
        $rowCount = intval($row[0]);
        if($rowCount >0)
        {
            $searchById = true;
            $where .= " AND `". WORKLIST ."`.`id` = " . $query;
        }
    }
    if(!$searchById) {
		// #11500
		// INPUT: 'one OR    two   three' ;
		// RESULT: 'one two,three' ;
		// split the query into an array using space as delimiter 
		// remove empty elements
		// convert spaces into commas
		// change ',OR,' into  space
		$query = preg_replace('/,OR,/', ' ', implode(',', array_filter(explode(' ', $query)))) ;
    
    	$array=explode(",",rawurldecode($query));

        foreach ($array as $item) {
            $item = mysql_escape_string($item);
            $where.=" AND ( MATCH (summary, `".WORKLIST."`.`notes`) AGAINST ('$item')OR MATCH (`".FEES."`.notes) AGAINST ('$item') OR MATCH (`ru`.`nickname`) AGAINST ('$item') OR MATCH (`cu`.`nickname`) AGAINST ('$item') OR MATCH (`mu`.`nickname`) AGAINST ('$item') OR MATCH (`com`.`comment`) AGAINST ('$item')) ";
        }
		$commentsjoin = " LEFT OUTER JOIN `".COMMENTS."` AS `com` ON `".WORKLIST."`.`id` = `com`.`worklist_id`";
    }
}

$totals = 'CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_totals` (
           `worklist_id` int(11) NOT NULL,
           `total_fees` decimal(10,2) NOT NULL,
           INDEX worklist_id(worklist_id))';

$emptyTotals = 'TRUNCATE `tmp_totals`';

$fillTotals = 'INSERT INTO `tmp_totals`
               SELECT `worklist_id`, SUM(amount) FROM `'.FEES.'` WHERE `withdrawn` = 0 GROUP BY `worklist_id`';

mysql_query($totals);
mysql_query($emptyTotals);
mysql_query($fillTotals);

$latest = 'CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_latest` (
           `worklist_id` int(11) NOT NULL,
           `latest` DATETIME NOT NULL,
           INDEX worklist_id(worklist_id))';

$emptyLatest = 'TRUNCATE `tmp_latest`';

$fillLatest = 'INSERT INTO `tmp_latest`
               (SELECT `worklist_id`,
                MAX(`bid_created`) AS `latest`
                FROM `'.BIDS.'` WHERE `withdrawn` = 0 GROUP BY `worklist_id`)';

mysql_query($latest);
mysql_query($emptyLatest);
mysql_query($fillLatest);

if($is_runner){
	$showLatest = 'AND `'.BIDS.'`.`bid_created` = `tmp_latest`.`latest`';
	if (($sfilter[0] == 'BIDDING') && (!empty($ufilter) && $ufilter != 'ALL')) {
		$showLatest = 'AND (`'.BIDS.'`.`bid_created` = `tmp_latest`.`latest` OR `'.BIDS.'`.`bidder_id` = '.$ufilter.')';
	}
}
else{
	$showLatest = 'AND `'.BIDS.'`.`bid_created` = `tmp_latest`.`latest`';
	if (($sfilter[0] == 'BIDDING') && (!empty($ufilter) && $ufilter != 'ALL')) {
		$showLatest = 'AND `'.BIDS.'`.`bidder_id` = ' . $ufilter ;
	}
}
$bids = 'CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_bids` (
         `worklist_id` int(11) NOT NULL,
         `bid_amount` decimal(10,2) NOT NULL,
         `bidder_id`  int(11) NOT NULL,
         INDEX worklist_id(worklist_id))';

$emptyBids = 'TRUNCATE `tmp_bids`';

$fillBids = "INSERT INTO `tmp_bids`
             SELECT `".BIDS."`.`worklist_id`,`".BIDS."`.`bid_amount`,`".BIDS."`.`bidder_id`
             FROM `".BIDS."`, `tmp_latest`
             WHERE `".BIDS."`.`worklist_id` = `tmp_latest`.`worklist_id`
              $showLatest
              AND (`".BIDS."`.`withdrawn` = 0)";

mysql_query($bids);
mysql_query($emptyBids);
mysql_query($fillBids);

$qcnt  = "SELECT count(DISTINCT `".WORKLIST."`.`id`)";

//mega-query with total fees and latest bid for the worklist item
$qsel  = "SELECT DISTINCT  `".WORKLIST."`.`id`,`summary`,`status`,
	      `cu`.`nickname` AS `creator_nickname`,
	      `ru`.`nickname` AS `runner_nickname`,
	      `mu`.`nickname` AS `mechanic_nickname`,
          `proj`.`name` AS `project_name`,
          `worklist`.`project_id` AS `project_id`,
	      TIMESTAMPDIFF(SECOND, `created`, NOW()) as `delta`,
	      `total_fees`,`bid_amount`,`creator_id`,`runner_id`,`mechanic_id`,
	      (SELECT COUNT(`".BIDS."`.`id`) FROM `".BIDS."`
	       WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND (`".BIDS."`.`withdrawn` = 0) LIMIT 1) as bid_count,
          TIMESTAMPDIFF(SECOND,NOW(), (SELECT `".BIDS."`.`bid_done` FROM `".BIDS."`
           WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND `".BIDS."`.`accepted` = 1 LIMIT 1)) as bid_done,
           (SELECT COUNT(`".COMMENTS."`.`id`) FROM `".COMMENTS."`
           WHERE `".COMMENTS."`.`worklist_id` = `".WORKLIST."`.`id`) AS `comments`";

// Highlight jobs I bid on in a different color
// 14-JUN-2010 <Tom>
if (($ufilter == 'ALL') && ($bFilterStatusContainBidding) && (isset($_SESSION['userid']))) {
    $qsel .= ", (SELECT COUNT(`".BIDS."`.`id`) FROM `".BIDS."` WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND `".BIDS."`.`bidder_id` = ".$_SESSION['userid']." AND `withdrawn` = 0) AS `bid_on`";
}

// add where clause to not show status-level if bid was withdrawn.
// $where .= " AND (`withdrawn` = 0)";
// The previous filter has been removed for the follwong reason:
// with the original fix #11929, some items are not displayed in the list (like 12425)
// with the fix in #12437,  the missing workitems are available in the list but with a search criteria there was duplicate workitems in the list
// without the filter added in #11929, the 2 previous issues are fixed 


$qbody = "FROM `".WORKLIST."`
          LEFT JOIN `".USERS."` AS cu ON `".WORKLIST."`.`creator_id` = `cu`.`id`
          LEFT JOIN `".USERS."` AS ru ON `".WORKLIST."`.`runner_id` = `ru`.`id`
          LEFT JOIN `".FEES."` ON `".WORKLIST."`.`id` = `".FEES."`.`worklist_id`
		  $commentsjoin
		  LEFT OUTER JOIN `".USERS."` AS mu ON `".WORKLIST."`.`mechanic_id` = `mu`.`id`
          LEFT JOIN `tmp_totals` AS `totals` ON `".WORKLIST."`.`id` = `totals`.`worklist_id`
          $unpaid_join
          LEFT JOIN `tmp_bids` AS `bids` ON `".WORKLIST."`.`id` = `bids`.`worklist_id`
          LEFT JOIN `".PROJECTS."` AS `proj` ON `".WORKLIST."`.`project_id` = `proj`.`project_id`
          $where ";

if($ofilter == "delta"){
	$qorder = "ORDER BY {$ofilter} {$dfilter} LIMIT " . ($page-1)*$limit . ",{$limit}";
}else{
	$qorder = "ORDER BY {$ofilter} {$dfilter},{$subofilter} {$dfilter}  LIMIT " . ($page-1)*$limit . ",{$limit}";
}

$rtCount = mysql_query("$qcnt $qbody");
if ($rtCount) {
    $row = mysql_fetch_row($rtCount);
    $items = intval($row[0]);
} else {
    $items = 0;
}
$cPages = ceil($items/$limit);
$worklist = array(array($items, $page, $cPages));
/*echo(json_encode(array("qry" => $qsel.$qbody.$qorder)));*/

// Construct json for history
$rtQuery = mysql_query("$qsel $qbody $qorder");
$qry =$qsel.$qbody.$qorder;
echo mysql_error();
while ($rtQuery && $row=mysql_fetch_assoc($rtQuery)) {

    $worklist[] = array(
         0 => $row['id'],
         1 => $row['summary'],
         2 => $row['status'],
         3 => $row['creator_nickname'],
         4 => $row['runner_nickname'],
         5 => $row['mechanic_nickname'],
         6 => $row['delta'],
         7 => $row['total_fees'],
         8 => $row['bid_amount'],
         9 => $row['creator_id'],
        10 => $row['bid_count'],
        11 => $row['bid_done'],
        12 => $row['comments'],
        13 => $row['runner_id'],
        14 => $row['mechanic_id'],
        15 => (!empty($row['bid_on']) ? $row['bid_on'] : 0),
        16 => $row['project_id'],
        17 => $row['project_name']
	);
}

$json = json_encode($worklist);
echo $json;
ob_end_flush();
?>
