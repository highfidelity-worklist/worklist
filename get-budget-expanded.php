<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");

// Check that this info is requested by a runner
if (!isset($_SESSION['is_runner']) || $_SESSION['is_runner'] != 1) {
    echo "Error: Unauthorized";
    die;
}

// Check a section request is given
if (!isset($_REQUEST['section'])) {
    echo "No section requested.";
    die;
}
$budget_id = 0;
if (isset($_REQUEST['budget_id'])) {
    $budget_id = (int) $_REQUEST['budget_id'];
}

// Check if we've received sorting request
$sortby = "";
$desc = "";
$sort = false;
if (isset($_REQUEST['sortby']) && isset($_REQUEST['desc'])) {
    switch ($_REQUEST['sortby']) {
        case 'be-id':
            $sortby = 'id';
            break;
        case 'be-budget':
            $sortby = 'budget_id';
            break;
        case 'be-summary':
            $sortby = 'summary';
            break;
        case 'be-who':
            $sortby = 'who';
            break;
        case 'be-amount':
            $sortby = 'amount';
            break;
        case 'be-status':
            $sortby = 'status';
            break;
        case 'be-created':
            $sortby = 'created';
            break;
        case 'be-paid':
            $sortby = 'paid';
            break;
    }
    $desc = $_REQUEST['desc'];
    $sort = true;
}

$section = $_REQUEST['section'];

if (!isset($_REQUEST['action'])) {
	switch ($section) {
		case 0:
		    if ($sort) {
		        echo getAllocated($budget_id, $sortby, $desc);
		    } else {
		        echo getAllocated($budget_id);
		    }
		    break;
		case 1:
	        if ($sort) {
                echo getSubmitted($budget_id, $sortby, $desc);
            } else {
                echo getSubmitted($budget_id);
            }
		    break;
		case 2:
	        if ($sort) {
                echo getPaid($budget_id, $sortby, $desc);
            } else {
                echo getPaid($budget_id);
            }
		    break;
	}
} else {
    if ($_REQUEST['action'] == 'export') {
        // Export to CSV
	    switch ($section) {
	        case 0:
	            $data = json_decode(getAllocated());
	            exportCSV($data);
	            break;
	        case 1:
	            $data = json_decode(getSubmitted());
	            exportCSV($data);
	            break;
	        case 2:
	            $data = json_decode(getPaid());
	            exportCSV($data);
	            break;
	    }
    }
}

function exportCSV($data) {    
    // Create with headers
    $csv = "Worklist ID,Budget,Summary,Who,Amount,Status,Created,Paid\n";
    
    foreach ($data as $item) {
        $csv .= $item->id.",";
        $csv .= $item->budget_id.",";
        $csv .= str_replace(",", "", $item->summary).",";
        $csv .= str_replace(",", "", $item->who).",";
        $csv .= $item->amount.",";
        $csv .= $item->status.",";
        $csv .= $item->created.",";
        $csv .= $item->paid."\n";
    }
    
    // Output headers to force download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="Report.csv"');
    echo $csv;
}

function getAllocated($budget_id = 0, $sort = NULL, $desc = NULL) {
    if ($budget_id > 0) {
        $filter = " `budget_id`={$budget_id} AND ";
    } else {
        $filter = " `runner_id`='{$_SESSION['userid']}' AND ";
    }
    $sql = "SELECT w.`id`, w.`budget_id`, w.`summary`, w.`status`, b.`reason` " .
           " FROM " . WORKLIST . " w " .
           " LEFT JOIN " . BUDGETS . " b ON w.budget_id = b.id ".
           " WHERE {$filter} `status` IN ('working','review','completed')";

    $sql_q = mysql_query($sql) or die(mysql_error());
    $items = array();
    
    while ($row = mysql_fetch_assoc($sql_q)) {
        // Get fees
        $fees = getFees($row['id']);
        
        // Get people working there
        $who = getWho($row['id']);
        $ids = getWho($row['id'], true);
        
        // Get Date of Working
        $created = getDateWorking($row['id']);
        
        // Get payment status
        $paid = getPaymentStatus($row['id']);
        
        if ($paid['paid'] == 1) {
            // Get paid date
            $paid_date = getPaidDate($row['id']);
            if (!$paid_date['paid_date']) {
                $paid_date['paid_date'] = "No Data";
            }
        } else {
            $paid_date = array('paid_date'=>"Not Paid");
        }
    
            $items[] = array('id'=>$row['id'], 'budget_id'=>$row['budget_id'], 'budget_title'=>$row['reason'], 
                            'summary'=>$row['summary'], 'who'=>$who, 'ids'=>$ids, 'amount'=>$fees['amount'],
                            'status'=>$row['status'], 'created'=>$created['date'], 'paid'=>$paid_date['paid_date']);
    }
    // If required sort the result
    if ($sort && $desc) {
        return json_encode(sortItems($items, $desc, $sort));
    } else {
        return json_encode($items);
    }
}

function getSubmitted($budget_id = 0, $sort = NULL, $desc = NULL) {
    if ($budget_id > 0) {
        $filter = " `budget_id`={$budget_id} AND ";
    } else {
        $filter = " `runner_id`='{$_SESSION['userid']}' AND ";
    }
    $sql = "SELECT w.`id`, w.`budget_id`, w.`summary`, w.`status`, b.`reason` ".
           " FROM " . WORKLIST . " w " .
           " LEFT JOIN " . BUDGETS . " b ON w.budget_id = b.id".
           " WHERE {$filter} `status`='done'";

    $sql_q = mysql_query($sql) or die(mysql_error());
    $items = array();
    
    while ($row = mysql_fetch_assoc($sql_q)) {
        // Get fees
        $fees = getFees($row['id']);
        
        // Get people working there
        $who = getWho($row['id']);
        $ids = getWho($row['id'], true);
        
        // Get Date of Working
        $created = getDateWorking($row['id']);
        
        // Get payment status
        $paid = getPaymentStatus($row['id']);     
        
        if ($paid['paid'] != 1) {
            if ($fees['amount'] > 0) {
                $paid_date = array('paid_date'=>"Not Paid");
                $items[] = array('id'=>$row['id'], 'budget_id'=>$row['budget_id'], 'budget_title'=>$row['reason'],
                                'summary'=>$row['summary'], 'who'=>$who, 'ids'=>$ids, 'amount'=>$fees['amount'],
                                'status'=>$row['status'], 'created'=>$created['date'], 'paid'=>$paid_date['paid_date']);
            }
        }
    }
    // If required sort the result
    if ($sort && $desc) {
        return json_encode(sortItems($items, $desc, $sort));
    } else {
        return json_encode($items);
    }
}

function getPaid($budget_id = 0, $sort = NULL, $desc = NULL) {
    if ($budget_id > 0) {
        $filter = " `budget_id`={$budget_id} AND ";
    } else {
        $filter = " `runner_id`='{$_SESSION['userid']}' AND ";
    }
    $sql = "SELECT w.`id`, w.`budget_id`, w.`summary`, w.`status`, b.`reason` ".
           " FROM " . WORKLIST . "  w " .
           " LEFT JOIN " . BUDGETS . " b ON w.budget_id = b.id".
           " WHERE  {$filter} `status`='done'";

    $sql_q = mysql_query($sql) or die(mysql_error());
    $items = array();
    
    while ($row = mysql_fetch_assoc($sql_q)) {
        // Get fees
        $fees = getFees($row['id']);
        
        // Get people working there
        $who = getWho($row['id']);
        $ids = getWho($row['id'], true);
        
        // Get Date of Working
        $created = getDateWorking($row['id']);
        
        // Get payment status
        $paid = getPaymentStatus($row['id']);
        
        if ($paid['paid'] == 1) {
            // Get Paid date
            $paid_date = getPaidDate($row['id']);
            if (!$paid_date['paid_date']) {
                $paid_date['paid_date'] = "No Data";
            }
	        
            $items[] = array('id'=>$row['id'], 'budget_id'=>$row['budget_id'], 'budget_title'=>$row['reason'],
                            'summary'=>$row['summary'], 'who'=>$who, 'ids'=>$ids, 'amount'=>$fees['amount'],
                            'status'=>$row['status'], 'created'=>$created['date'], 'paid'=>$paid_date['paid_date']);
        }
    }
    // If required sort the result
    if ($sort && $desc) {
        return json_encode(sortItems($items, $desc, $sort));
    } else {
        return json_encode($items);
    }
}

function getFees($id) {
    $sql = "SELECT SUM(".FEES.".`amount`) AS `amount` ".
           "FROM ".FEES.
           " WHERE ".FEES.".`worklist_id`={$id} AND ".FEES.".`withdrawn`!=1";
    $sql_q = mysql_query($sql) or die(mysql_error()); 
    return mysql_fetch_array($sql_q);
}

function getWho($id, $getIds=false) {
	$who = "";
	$sql = "SELECT ".USERS.".`id`,`nickname` FROM ".FEES.
	       " LEFT JOIN ".USERS." ON ".USERS.".`id`=".FEES.".`user_id`".
	       " WHERE ".FEES.".`worklist_id` = {$id} AND ".FEES.".`withdrawn`=0 GROUP BY `nickname`";
	        
	$sql_q = mysql_query($sql) or die(mysql_error());
	
	if ($getIds) {
	    $ids = array();
		while($row = mysql_fetch_assoc($sql_q)) {
		    $ids[] = $row['id'];
		}
		return $ids;
	} else {
	   while($row = mysql_fetch_assoc($sql_q)) {
           $who .= $row['nickname'].", ";
	   }
	   return substr($who, 0, -2);
	}
}

function getDateWorking($id) {
	$sql = "SELECT DATE_FORMAT(".FEES.".`date`, '%m/%d/%Y') as `date` ".
	       "FROM ".FEES." LEFT JOIN ".WORKLIST." ON ".WORKLIST.".`id`=".FEES.".`worklist_id` ".
	       "WHERE ".FEES.".`user_id`=".WORKLIST.".`mechanic_id` AND ".WORKLIST.".`id`={$id} LIMIT 1";
	$sql_q = mysql_query($sql) or die(mysql_error());
	
	$data = mysql_fetch_array($sql_q);
	if ($data['date']) {
	   return $data;
	} else {
	   $data['date'] = "No Data";
	   return $data;
	} 
}

function getPaymentStatus($id) {
	$sql = "SELECT ".FEES.".`paid` ".
	       "FROM ".FEES." LEFT JOIN ".WORKLIST." ON ".WORKLIST.".`id`=".FEES.".`worklist_id` ".
	       "WHERE ".FEES.".`worklist_id` = {$id} AND `withdrawn`!=1 AND `amount`>0 GROUP BY ".FEES.".`paid`";
	$sql_q = mysql_query($sql) or die(mysql_error());
	return mysql_fetch_array($sql_q);
}

function getPaidDate($id) {
	$sql = "SELECT DATE_FORMAT(".FEES.".`paid_date`, '%m/%d/%Y') as `paid_date` ".
	       "FROM ".FEES." LEFT JOIN ".WORKLIST." ON ".WORKLIST.".`id`=".FEES.".`worklist_id` ".
	       "WHERE ".FEES.".`worklist_id` = {$id} GROUP BY ".FEES.".`paid_date`";
	$sql_q = mysql_query($sql) or die(mysql_error());
	return mysql_fetch_array($sql_q);
}

function sortItems($items, $desc, $sort) {
	$order = SORT_ASC;
	if ($desc) {
	    $order = SORT_DESC;
	}
	foreach($items as $key => $key_row) {
	    $sortby_idx[$key] = $key_row[$sort];
	}
	$type = SORT_STRING;
	if ($sort == 'id' || $sort == 'amount' || $sort == 'created' || $sort == 'paid') {
	    $type = SORT_NUMERIC;
	}
	array_multisort($sortby_idx, $order, $type, $items);
	return $items;
}

?>
<script type="text/javascript" src="js/common.js"></script>
<?php include('userinfo.inc'); ?>