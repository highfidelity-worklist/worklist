<?php
//  vim:ts=4:et

//  Copyright (c) 2009, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history


include("config.php");
include("class.session_handler.php");

error_reporting(-1);
$limit = 30;
$page = isset($_REQUEST["page"])?$_REQUEST["page"]:1;
$sfilter = isset($_REQUEST["sfilter"])?explode("/",$_REQUEST["sfilter"]):array();
$ufilter = isset($_REQUEST["ufilter"])?intval($_REQUEST["ufilter"]):0;

$where = '';
$unpaid_join = '';
$fees = "total_fees";
if (!empty($sfilter)) {
    $where = "where (";
    foreach ($sfilter as $val) {
        $val = strtoupper(mysql_real_escape_string($val));
        if ($val == 'ALL') {
            $where .= "1 or ";
        } else if ($val == 'UNPAID') {
            $where .= "(`unpaid_fees`>0 and status='DONE') or ";
            $fees = "unpaid_fees";
            $unpaid_join = "LEFT JOIN (SELECT `worklist_id`, SUM(amount) AS `unpaid_fees` FROM `".FEES."` WHERE `paid`=0 AND `withdrawn` = 0 GROUP BY `worklist_id`) AS `unpaid` ON `".WORKLIST."`.`id` = `unpaid`.`worklist_id` ";
        } else {
            $where .= "status='$val' or ";
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
    $where .= "(creator_id='$ufilter' or owner_id='$ufilter' or mechanic_id='$ufilter' or user_id='$ufilter')";
}

if($_REQUEST['query']!='' & $_REQUEST['query']!='Search...') {
    $query = $_REQUEST['query'];
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
        $array=explode(" ",rawurldecode($_REQUEST['query']));

        foreach ($array as $item) {
            $item = mysql_escape_string($item);
            $where.=" AND ( summary LIKE '%$item%' OR `".WORKLIST."`.`notes` LIKE '%$item%' OR `".FEES."`.notes LIKE '%$item%') ";
        }
    }    
}

$qcnt  = "SELECT count(*)";

//mega-query with total fees and latest bid for the worklist item
$qsel  = "SELECT DISTINCT `".WORKLIST."`.`id`, `summary`, `status`, `funded`, `ou`.`nickname`, `ou`.`username`,`mu`.`nickname` as mechanic_nickname,
	         `mu`.`username` as mechanic_username,TIMESTAMPDIFF(SECOND, `created`, NOW()) as `delta`, `$fees`, `bid_amount`,`creator_id`, (SELECT COUNT(`".BIDS."`.id) FROM `".BIDS."` WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND (`".BIDS."`.`withdrawn` = 0)) as bid_count, TIMESTAMPDIFF(SECOND,NOW(),(SELECT `".BIDS."`.`bid_done` FROM `".BIDS."` WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` and `".BIDS."`.`accepted` = 1)) as bid_done";
$qbody = "FROM `".WORKLIST."` 
          LEFT JOIN `".USERS."` AS ou ON `".WORKLIST."`.`owner_id` = `ou`.`id`
          LEFT JOIN `".FEES."` ON `worklist`.`id` = `".FEES."`.`worklist_id`
          LEFT OUTER JOIN `".USERS."` AS mu ON `".WORKLIST."`.`mechanic_id` = `mu`.`id`
          LEFT JOIN (SELECT `worklist_id`, SUM(amount) AS `total_fees` FROM `".FEES."` WHERE `withdrawn` = 0 GROUP BY `worklist_id`) AS `totals` ON `".WORKLIST."`.`id` = `totals`.`worklist_id` 
          $unpaid_join
          LEFT JOIN (SELECT `".BIDS."`.`worklist_id`, `".BIDS."`.`bid_amount` FROM `".BIDS."`, (SELECT MAX(`bid_created`) AS `latest`, `worklist_id` 
          FROM `".BIDS."` GROUP BY `worklist_id`) AS `latest_bids` WHERE `".BIDS."`.`worklist_id` = `latest_bids`.`worklist_id` 
          AND `".BIDS."`.`bid_created` = `latest_bids`.`latest` AND (`".BIDS."`.`withdrawn` = 0)) AS `bids` ON `".WORKLIST."`.`id` = `bids`.`worklist_id` 
          $where";
$qorder = "ORDER BY `".WORKLIST."`.`priority` ASC LIMIT " . ($page-1)*$limit . ",$limit";

$rtCount = mysql_query("$qcnt $qbody");
if ($rtCount) {
    $row = mysql_fetch_row($rtCount);
    $items = intval($row[0]);
} else {
    $items = 0;
}
$cPages = ceil($items/$limit); 
$worklist = array(array($items, $page, $cPages));

// Construct json for history
$rtQuery = mysql_query("$qsel $qbody $qorder");
for ($i = 1; $rtQuery && $row=mysql_fetch_assoc($rtQuery); $i++)
{
    if (!empty($row['username'])) {
        $nickname = $row['nickname'];
        $username = ''; //tcrowe: security: disabled for now. $row['username'];
    } else {
        $nickname = $username = '';
    }
    $worklist[$i] = array($row['id'], $row['summary'], $row['status'], $nickname, $username, $row['delta'], $row[$fees], $row['bid_amount'], $row['creator_id'], $row['mechanic_nickname'], $row['mechanic_username'], $row['bid_count'], $row['bid_done'], $row['funded']);
}
                      
$json = json_encode($worklist);
echo $json;     
