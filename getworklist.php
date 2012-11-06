<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

// AJAX request from ourselves to retrieve history

include_once ("config.php");
include_once ("class.session_handler.php");
require_once ("lib/Agency/Worklist/Filter.php");

// Test for a string containing 0 characters of anything other than 0-9 and #
// After a quick trim ofcourse! :)
// I know regex is usually the bad first stop, but there would be no back tracking in this
// particular regular expression
if (preg_match("/^\#?\d+$/", $query = trim($_REQUEST['query']))) {
    // if we reach here, include workitem package, autoloaded (hans)
    include_once ("functions.php");
    $workitem = new WorkItem();
    if ($workitem->idExists($id = ltrim($query, '#'))) {
        $obj = array('redirect',$id);
        die(JSON_encode($obj));
    }
    // if we're not dead continue on!
}
$limit = 30;

$_REQUEST['name'] = '.worklist';
$filter = new Agency_Worklist_Filter($_REQUEST);

$is_runner = !empty( $_SESSION['is_runner'] ) ? 1 : 0;
$userId = isset($_SESSION['userid'])? $_SESSION['userid'] : 0;

$sfilter = explode('/', $filter->getStatus());
$ufilter = $filter->getUser();
$pfilter = !empty($_POST['project_id']) ? $_POST['project_id'] : $filter->getProjectId();
$cfilter = !empty($_POST['inComment']) ? $_POST['inComment'] : $filter->getInComment();
$ofilter = $filter->getSort();
$subofilter = $filter->getSubSort();
$dfilter = $filter->getDir();
$page = $filter->getPage();
$where = '';

// Status filter
if ($sfilter) {
    $where = "WHERE (";
    foreach ($sfilter as $val) {

        $val = mysql_real_escape_string($val);

            if (($val == 'ALL' || $val == '') && !$is_runner) {
                /**
                 * if current user is not a runner and is filtering by ALL 
                 * status it wont fetch workitems in DRAFT status
                 */
                $where .= "1 AND status != 'Draft' OR ";
            }
            if (($val == 'ALL' || $val == '') && $is_runner == 1 ){
                /**
                 * if current user is a runner and is filtering by ALL status 
                 * it wont fetch workitems in DRAFT status created by any other
                 * user
                 */
                $where .= "1 AND status != 'Draft' OR (status = 'Draft' AND creator_id = $userId) OR  ";
            }
            if ($val == 'Draft'){
                /**
                 * if filtering by DRAFT status will only fetch workitems in 
                 * DRAFT status created by current user
                 */
                $where .= "(status = 'Draft' AND creator_id = $userId) OR  ";
            } else {
                /**
                 * if filtering by any status different than ALL and DRAFT it 
                 * won't do any magic
                 */
                $where .= "status='$val' OR ";
            }
    }
    $where .= "0)";
}

// User filter
if (!empty($ufilter) && $ufilter != 'ALL') {
    if (empty($where)) {
        $where = "WHERE (";
    } else {
        $where .= " AND (";
    }

    $severalStatus = "";
    foreach ($sfilter as $val) {
        if ($val == 'ALL') {
            $status_cond = "";
        } else {
            $status_cond = "status='$val' AND";
        }
        if (($is_runner && $val == 'Bidding' || $val == 'SuggestedWithBid' && $ufilter == $userId)) {
            /**
             * If current user is a runner and filtering for himself and 
             * (BIDDING or SwB) status then fetch all workitems where he 
             * is mechanic, runner, creator or has bids.
             */ 
            $where .= $severalStatus .
                "( $status_cond (`mechanic_id` = '$ufilter' OR `runner_id` = '$ufilter' OR `creator_id` = '$ufilter'
                OR `" . WORKLIST . "`.`id` in (SELECT `worklist_id` FROM `" . BIDS . "` where `bidder_id` = '$ufilter')
                ))";
        } else if ((!$is_runner && $val == 'Bidding' || $val == 'SuggestedWithBid' && $ufilter == $userId)) {
            /**
             * If current user is a runner and filtering for certain user and 
             * (BIDDING or SwB) status then fetch all workitems where selected
             * user is runner, creator or has bids.
             */ 
            $where .= $severalStatus . "( $status_cond ( `runner_id` = '$ufilter' OR `creator_id` = '$ufilter'
                OR `" . WORKLIST . "`.`id` in (SELECT `worklist_id` FROM `" . BIDS . "` where `bidder_id` = '$ufilter')
                ))";
        } else if (($val == 'Bidding' || $val == 'SuggestedWithBid') && $ufilter != $userId) {
            /**
             * If current user is not a runner and is filtering for certain user
             * and (BIDDING or SwB) status then fetch all workitems where selected
             * user is mechanic, runner or creator.
             */ 
            $where .= $severalStatus . "( $status_cond ( mechanic_id='$ufilter' OR `runner_id` = '$ufilter' OR creator_id='$ufilter'))";
        } else if ($val == 'Working' || $val =='Review' || $val =='Functional' || $val =='Completed' ) {
            /**
             * If current user is filtering for any user (himself or not) and 
             * (WORKING or REVIEW or FUNCTIONAL or COMPLETED) status then fetch
             * all workitems where selected user is mechanic, creator or runner.
             */ 
            $where .= $severalStatus . "( $status_cond ( mechanic_id='$ufilter' OR `creator_id`='$ufilter' OR `runner_id` = '$ufilter'))";
        } else  {
            /**
             * If current user is filtering for any user (himself or not) and 
             * didn't match above cases (filtering ALL or any other status
             * different than BIDDING, SwB, WORKING, REVIEW, FUNCTIONAL and 
             * COMPLETED) then fetch all workitems where selected user is
             * creator, runner, mechanic, has fees or has bids
             */
            $where .= $severalStatus .
                "( $status_cond (`creator_id` = '$ufilter' OR `runner_id` = '$ufilter' OR `mechanic_id` = '$ufilter'
                OR `" . FEES . "`.user_id = '$ufilter'
                OR `" . WORKLIST . "`.`id` in (SELECT `worklist_id` FROM `" . BIDS . "` where `bidder_id` = '$ufilter')
                ))";
        }
        $severalStatus = " OR ";
    }
    $where .= ')';
}

// Project filter
if (!empty($pfilter) && $pfilter != 'All') {
    if (empty($where)) {
        $where = "WHERE ";
    } else {
        $where .= " AND ";
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
        $commentPart = "";

        foreach ($array as $item) {
            $item = mysql_escape_string($item);
            if ($cfilter == 1) {
                $commentPart = " OR MATCH (`com`.`comment`) AGAINST ('$item')";
            }
            $where .= " AND ( MATCH (summary, `" . WORKLIST . "`.`notes`) AGAINST ('$item')OR MATCH (`" . FEES . 
                "`.notes) AGAINST ('$item') OR MATCH (`ru`.`nickname`) AGAINST ('$item') OR MATCH (`cu`.`nickname`) AGAINST ('$item') OR MATCH (`mu`.`nickname`) AGAINST ('$item') " . 
                $commentPart . ") ";
        }
        if ($cfilter == 1) {
            $commentsjoin = "LEFT OUTER JOIN `" . COMMENTS . "` AS `com` ON `" . WORKLIST . "`.`id` = `com`.`worklist_id`";
        }
    }
}

$qcnt  = "SELECT count(DISTINCT `".WORKLIST."`.`id`)";

//mega-query with total fees and latest bid for the worklist item
$qsel  = "SELECT `".WORKLIST."`.`id`, `summary`, `status`,
          `bug_job_id` AS `bug_job_id`,
          `cu`.`nickname` AS `creator_nickname`,
          `ru`.`nickname` AS `runner_nickname`,
          `mu`.`nickname` AS `mechanic_nickname`,
          `proj`.`name` AS `project_name`,
          `worklist`.`project_id` AS `project_id`,
          TIMESTAMPDIFF(SECOND, `created`, NOW()) as `delta`,
          SUM(`" . FEES . "`.amount) AS `total_fees`,
          (SELECT `bid_amount`
              FROM `" . BIDS . "`
              WHERE `withdrawn` = 0
              AND (`bid_expires` > NOW()
              OR `bid_expires` = '0000-00-00 00:00:00')
              AND `worklist_id` = `worklist`.`id`
              ORDER BY bid_created DESC LIMIT 1) `bid_amount`,

          `creator_id`, `runner_id`, `mechanic_id`,
          (SELECT COUNT(`".BIDS."`.`id`) FROM `".BIDS."`
           WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND (`".BIDS."`.`withdrawn` = 0) AND (NOW() < `".BIDS."`.`bid_expires` OR `bid_expires`='0000-00-00 00:00:00') LIMIT 1) as bid_count,
          TIMESTAMPDIFF(SECOND,NOW(), (SELECT `".BIDS."`.`bid_done` FROM `".BIDS."`
           WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND `".BIDS."`.`accepted` = 1 LIMIT 1)) as bid_done,
           (SELECT COUNT(`".COMMENTS."`.`id`) FROM `".COMMENTS."`
           WHERE `".COMMENTS."`.`worklist_id` = `".WORKLIST."`.`id`) AS `comments`";

// Highlight jobs I bid on in a different color
// 14-JUN-2010 <Tom>
if ((isset($_SESSION['userid']))) {
    $qsel .= ", (SELECT `".BIDS."`.`id` FROM `".BIDS."` WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id` AND `".BIDS."`.`bidder_id` = ".$_SESSION['userid']." AND `withdrawn` = 0  AND (`".WORKLIST."`.`status`='Bidding' OR `".WORKLIST."`.`status`='SuggestedWithBid') ORDER BY `".BIDS."`.`id` DESC LIMIT 1) AS `current_bid`";
    $qsel .= ", (SELECT `".BIDS."`.`bid_expires` FROM `".BIDS."` WHERE `".BIDS."`.`id` = `current_bid`) AS `current_expire`";
    $qsel .= ", (SELECT COUNT(`".BIDS."`.`id`) FROM `".BIDS."` WHERE `".BIDS."`.`worklist_id` = `".WORKLIST."`.`id`  AND (`".WORKLIST."`.`status`='Bidding' OR `".WORKLIST."`.`status`='SuggestedWithBid') AND `".BIDS."`.`bidder_id` = ".$_SESSION['userid']." AND `withdrawn` = 0 AND (`bid_expires` > NOW() OR `bid_expires`='0000-00-00 00:00:00')) AS `bid_on`";
}

$qbody = "FROM `".WORKLIST."`
          LEFT JOIN `".USERS."` AS cu ON `".WORKLIST."`.`creator_id` = `cu`.`id`
          LEFT JOIN `".USERS."` AS ru ON `".WORKLIST."`.`runner_id` = `ru`.`id`
          LEFT JOIN `" . USERS . "` AS mu ON `" . WORKLIST . "`.`mechanic_id` = `mu`.`id`
          LEFT JOIN `" . FEES . "` ON `" . WORKLIST . "`.`id` = `" . FEES . "`.`worklist_id` AND `" . FEES . "`.`withdrawn` = 0
          LEFT JOIN `".PROJECTS."` AS `proj` ON `".WORKLIST."`.`project_id` = `proj`.`project_id`
          $commentsjoin
          $where
          ";

if ($ofilter == "delta") {
    $idsort = $dfilter == 'DESC' ? 'ASC' : 'DESC';
    $qorder = "GROUP BY `".WORKLIST."`.`id` ORDER BY `".WORKLIST."`.`id` {$idsort} LIMIT "
        . ($page-1)*$limit . ",{$limit}";
}else{
    $qorder = "GROUP BY `".WORKLIST."`.`id` ORDER BY {$ofilter} {$dfilter},{$subofilter} {$dfilter}  LIMIT "
        . ($page-1)*$limit . ",{$limit}";
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

// Construct json for history
$qry="$qsel $qbody $qorder";

//Don't export mysql errors to the browser by default
$rtQuery = mysql_query($qry) or error_log('getworklist mysql error: '. mysql_error());
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
         8 => ($is_runner == 1) ? $row['bid_amount'] : 0,
         9 => $row['creator_id'],
        10 => $row['bid_count'],
        11 => ($row['status'] == 'Done') ? date("m/d/Y",time()+$row['bid_done']):$row['bid_done'],
        12 => $row['comments'],
        13 => $row['runner_id'],
        14 => $row['mechanic_id'],
        15 => (!empty($row['bid_on']) ? $row['bid_on'] : 0),
        16 => $row['project_id'],
        17 => $row['project_name'],
        18 => $row['bug_job_id'],
        19 => (!empty($row['current_expire']) && strtotime($row['current_expire'])<time() && trim($row['current_expire'])!='0000-00-00 00:00:00') ? 'expired' : 0,
        20 => (!empty($row['current_bid']) ? $row['current_bid'] : 0),
    );
}

$json = json_encode($worklist);
echo $json;

?>
