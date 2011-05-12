<?php
//  vim:ts=4:et

//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

/**
*    Page: view-payments.php
*    Features:  List of users receiving payments for the 
*        period defined, Toggle-able view under each user 
*        that will show the individual jobs and payments 
*        for each / or just a one line summary with total 
*        amount.  All payments can be checked to pay, or 
*        to not pay, with a way to check all and clear all.
*        Job Task_IDs can be moused over, as in the journal, 
*        to see the info, as well as clicked to open in a 
*        separate tab, if needed.  Pay Now button needed.  
*        Clicking pay now gives a confirm popup.
*    Author: Jason (jkofoed@gmail.com)
*    Date: 2010-04-01 [Happy April Fool's!]
*/

#ini_set('display_errors', 1);
#error_reporting(E_ALL);

include("config.php");
include("class.session_handler.php");
include_once("functions.php");
include_once("send_email.php");

//send non-payers back to the reports page.
if (empty($_SESSION['is_payer'])) {
   header("Location:reports.php");
}

$is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
$is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
$userId = getSessionUserId();

$payer_id = $userId;
// set default fund to below92
$fund_id = 2;

if (isset($_REQUEST['fund_id'])) {
    $fund_id = mysql_real_escape_string($_REQUEST['fund_id']);
    // clear POST if this was just a fund change
    if (! isset($_REQUEST['action'])) {
        unset($_POST);
    }
}

//open db connection
$db = @mysql_connect (DB_SERVER, DB_USER, DB_PASSWORD) or die ('I cannot connect to the database because: ' . mysql_error());
$db = @mysql_select_db(DB_NAME);

// get a list of projects so we can display the project name in table
$sql_get_fund_projects_array = "
    SELECT
        project_id, name
    FROM
        ".PROJECTS."
    WHERE
        fund_id = " . $fund_id;

// sql sub-query for limiting fees to specific fund
$sql_get_fund_projects = "
    SELECT
        project_id
    FROM
        ".PROJECTS."
    WHERE
        fund_id = " . $fund_id;

if ($fund_id == 0) {
    $sql_get_fund_projects = '0';
}

$fund_projects = array();
$fund_projects[0] = 'none';
$fund_projects_query = mysql_query($sql_get_fund_projects_array);
while ($project = mysql_fetch_array($fund_projects_query)) {
    $fund_projects[$project['project_id']] = $project['name'];
}

$sql_get_fee_totals = "
    SELECT
        sum(f.amount) AS total_amount,
        u.id AS mechanic_id,
        u.nickname AS mechanic_nick,
        u.paypal_email AS mechanic_paypal_email,
        wl.summary AS worklist_item, f.bonus AS bonus, 'BONUS' AS bonus_desc
    FROM
        (".FEES." f LEFT JOIN ".USERS." u ON f.user_id = u.id)
        LEFT JOIN ".WORKLIST." wl ON f.worklist_id = wl.id
    WHERE
        wl.status = 'DONE'
        AND f.paid = '0'
        AND f.withdrawn = '0'
        AND f.amount > 0
        AND u.paypal = '1'
        AND u.has_W2 = 0
        AND wl.project_id IN (" . $sql_get_fund_projects . ")
    GROUP BY f.user_id
    ";

$sql_get_bonus_totals = "
    SELECT
        sum(b.amount) AS total_amount,
        b.user_id AS mechanic_id,
        b.desc AS worklist_item,
        u.nickname AS mechanic_nick,
        u.paypal_email AS mechanic_paypal_email
    FROM
        ".FEES." b
        LEFT JOIN ".USERS." u on u.id = b.user_id
    WHERE
        b.paid = 0
        AND u.paypal = '1' and b.bonus = 1
        AND u.has_W2 = 0
   GROUP BY b.user_id
    ";


function getUserTotalsArray() {
    // Retuns an array with the total amount owed to each user including
    // worklist payments and bonuses. Fields in the array are:
    //  total_amount, mechanic_id, worklist_item, mechanic_nick, mechanic_payal_email
    // Creating 1 SQL statement to combine FEES and BONUS_PAYMENTS blew my mind
    // so I'm combining it in PHP. -Alexi 2011-03-03
    global $sql_get_fee_totals, $sql_get_bonus_totals;
    

    $fee_totals_query   = mysql_query($sql_get_fee_totals);
    $bonus_totals_query = mysql_query($sql_get_bonus_totals);

    $totals_array = array();
    while ($fees_array = mysql_fetch_array($fee_totals_query, MYSQL_ASSOC)) {
        $totals_array[] = $fees_array;
    }
    
    if (mysql_num_rows($bonus_totals_query) == 0) {
        // If there are no bonuses, return now
        return $totals_array;
    }

    while ($bonus_array = mysql_fetch_array($bonus_totals_query, MYSQL_ASSOC)) {
        $bonus_applied = false;
        foreach ($totals_array as $t_id => $fee_payee) {
            // Loop through payee fees to try to add the bonus total there
            if ($bonus_array['mechanic_id'] == $fee_payee['mechanic_id']) {
                $totals_array[$t_id]['total_amount'] = sprintf("%01.2f", 
                    $totals_array[$t_id]['total_amount'] + $bonus_array['total_amount']);
                $bonus_applied = true;
            }
        }
        if (!$bonus_applied) {
            // If no existing payments found for the user, append them to the array
            $totals_array[] = $bonus_array;
        }
    }
    return $totals_array;
}

$rowclass = 'rowodd';

$action = (isset($_POST["action"])) ? $_POST["action"] : '';

// Initialize empty arrays if no fees or bonuses were selected
if (!isset($_POST['payfee'])) {
    $_POST['payfee'] = array();
}
if (!isset($_POST['paybonus'])) {
    $_POST['paybonus'] = array();
}

$message = "";

//Check action - should be confirm, pay or not set
switch ($action) {
    case 'confirm':
        //$fees_csv = implode(',', $_POST["payfee"]);
        //pull list of payees from db based on the time span
        $payee_totals = getUserTotalsArray();

    break; 
    
    case 'pay':
        //collect confirmed payees and run paypal transaction
        include_once("paypal-password.php");
        if (checkAdmin($_POST['password']) == '1') { 
            error_log("Made it Admin!");
            if(empty($_POST['pp_api_username']) || empty($_POST['pp_api_password']) || empty($_POST['pp_api_signature'])){
                $alert_msg = "You need to provide all credentials!";
                break;
            }
            include_once("paypal-functions.php");
            include_once("classes/Fee.class.php");   

            //Get fee information for paypal transaction 
            $num_fees = count($_POST["payfee"]);
            $fee_id_csv = implode(',', $_POST["payfee"]);
            $fees_info_sql = 'SELECT
                    f.id AS fee_id,
                    f.amount AS amount,
                    f.worklist_id AS worklist_id,
                    u.id AS mechanic_id,
                    u.nickname AS mechanic_nick,
                    u.paypal_email AS mechanic_paypal_email,
                    wl.summary AS worklist_item  
                FROM
                    ('.FEES.' f LEFT JOIN '.USERS.' u ON f.user_id = u.id)
                    LEFT JOIN '.WORKLIST.' wl ON f.worklist_id = wl.id
                WHERE
                    f.id in ('.$fee_id_csv.')';
            $fees_info_results = mysql_query($fees_info_sql);

            $num_bonuses = count($_POST["paybonus"]);
            $bonus_id_csv = implode(',', $_POST["paybonus"]);
            $bonus_info_sql = '
                SELECT
                    b.id AS fee_id,
                    b.amount AS amount,
                    "BONUS" AS worklist_id,
                    b.user_id AS mechanic_id,
                    u.nickname AS mechanic_nick,
                    u.paypal_email AS mechanic_paypal_email,
                    b.desc AS worklist_item
                FROM
                    '.FEES.' b
                    LEFT JOIN '.USERS.' u on u.id = b.user_id
                WHERE
                    b.id in ('.$bonus_id_csv.') and b.bonus = 1
                ';
            $bonus_info_results = mysql_query($bonus_info_sql);

            // Set request-specific fields.
            $emailSubject = urlencode('You\'ve got money!');
            $receiverType = urlencode('EmailAddress');
            // TODO Other currency ('GBP', 'EUR', 'JPY', 'CAD', 'AUD') ?
            $currency = urlencode('USD');

            // Add request-specific fields to the request string.
            $nvpStr="&EMAILSUBJECT=$emailSubject&RECEIVERTYPE=$receiverType&CURRENCYCODE=$currency";

            //build payment data array
            $message .= "<pre>";
            $receiversArray = array();
            $totalFees = 0; //log data
            if (mysql_num_rows($fees_info_results)) {
                $message .= "Fees:\n";
                while ($fees_data = mysql_fetch_array($fees_info_results)) {
                    $receiversArray[] = array(
                        'receiverEmail' => $fees_data["mechanic_paypal_email"],
                        'amount' => $fees_data["amount"],
                        'uniqueID' => $fees_data["fee_id"],
                        'note' => 'Worklist #'.$fees_data["worklist_id"].' - '.$fees_data["worklist_item"]);
                    $totalFees = $totalFees + $fees_data["amount"];
                    $message .= "    ".$fees_data['mechanic_paypal_email']." - $".$fees_data['amount']."\n";
                }
            }
            if (mysql_num_rows($bonus_info_results) > 0) {
                $message .= "Bonuses:\n";
                while ($fees_data = mysql_fetch_array($bonus_info_results)) {
                    $receiversArray[] = array(
                        'receiverEmail' => $fees_data["mechanic_paypal_email"],
                        'amount' => $fees_data["amount"],
                        'uniqueID' => "bonus-".$fees_data["fee_id"],
                        'note' => $fees_data["worklist_id"].' - '.$fees_data["worklist_item"]);
                    $totalFees = $totalFees + $fees_data["amount"];
                    $message .= "    ".$fees_data['mechanic_paypal_email']." - $".$fees_data['amount']."\n";
                }
            }
            $message .= "</pre>";

            //build nvp string
            foreach($receiversArray as $i => $receiverData) {
                $receiverEmail = urlencode($receiverData['receiverEmail']);
                $amount = urlencode($receiverData['amount']);
                $uniqueID = urlencode($receiverData['uniqueID']);
                $note = urlencode($receiverData['note']);
                $nvpStr .= "&L_EMAIL$i=$receiverEmail&L_Amt$i=$amount&L_UNIQUEID$i=$uniqueID&L_NOTE$i=$note";
            }

            // Execute the API operation; see the PPHttpPost function in the paypal-functions.php file.
            $httpParsedResponseAr = PPHttpPost('MassPay', $nvpStr, $_POST);
            #$httpParsedResponseAr = array("ACK" => "SUCCESS");

            if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
                $pp_message = '<p>MassPay Completed Successfully! - $'.$totalFees.' Paid.</p>';
                if (isset($_GET["debug"])) {
                    $pp_message .= '<p><pre>'.print_r($httpParsedResponseAr, true).'</pre></p>';
                }
                //$fee_sql_update = "UPDATE ".FEES." SET paid=1, paid_date='".date("Y-m-d H:i:s")."' WHERE id in (".$fees_csv.")";
                //$update_fees_paid = mysql_query($fee_sql_update);

                $summaryData = Fee::markPaidByList(explode(',', $fee_id_csv), $user_paid=0, $paid_notes='', $paid=1);
                Bonus::markPaidByList(explode(',', $bonus_id_csv), $paid=1);

            } else  {
                $alert_msg = "MassPay Failure"; 
                $pp_message = '<p>MassPay failed:</p><p><pre>' . print_r($httpParsedResponseAr, true).'</pre></p>';
                if(!send_email('finance@lovemachineinc.com', 'Masspay Fail', $pp_message)) {
                    error_log("view-payments:MassPayFailure: send_email failed");
                }
            }

        } else {
            $error_msg = 'Invalid MassPay Authentication<br />';
            $error_msg .= 'IP: '. $_SERVER['REMOTE_ADDR'].'<br />';
            $error_msg .= 'UserID: '.$userId;
            if (!send_email("finance@lovemachineinc.com", "Masspay Invalid Auth Attempt", $error_msg)) {
                error_log("view-payments:MassPayAuth: send_email failed");
            }
            $alert_msg = "Invalid Authentication"; 
        }
        break; 
    
    default:
        //pull list of payees from db based on the time span
        $payee_totals = getUserTotalsArray();
    break;
}



/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>
<title>PayPal MassPay Run</title>
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/payments.css" rel="stylesheet" type="text/css">
<link href="css/ui.toaster.css" rel="stylesheet" type="text/css">

<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/jquery.tabSlideOut.v1.3.js"></script>
<script type="text/javascript" src="js/ui.toaster.js"></script>
<script type="text/javascript" src="js/payments.js"></script>
</head>
<body onLoad="updateTotalFees('0');">
<?php include("format.php"); ?>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->
<div class="floatLeft">
    <h1>PayPal MassPay Run</h1>
</div>
<div class="clear"></div>
<?php 
    if (isset($alert_msg)) { echo "<h2>".$alert_msg."</h2>"; }
    if (!isset($_POST["action"]) || ($_POST["action"] != 'pay')) {
        // If the action is set & not "pay", generate payment report...
?>
<div id="select-fund">
    <form id="fundForm" method="POST" action="view-payments.php">
        <label id="label-fund" for="fund_id">Fund:</label>
        <select name="fund_id" id="fund_id">
            <option value="0" <?php echo ($fund_id == 0 ? 'selected="selected"' : '');?>>Not funded</option>
            <option value="1" <?php echo ($fund_id == 1 ? 'selected="selected"' : ''); ?>>Below92</option>
            <option value="2" <?php echo ($fund_id == 2 ? 'selected="selected"' : ''); ?>>CandP</option>
        </select>
    </form>
</div>
<div id="select-actions">
    Actions: [<a href="javascript:void(0);" onClick="toggleCBs('toggle');">Invert Selection</a>]
    | [<a href="javascript:void(0);" onClick="toggleCBs('select');">Select All</a>]
    | [<a href="#" onClick="toggleCBs('unselect');">Select None</a>]
</div>
<div class="clear"></div>
<form action="view-payments.php?<?php echo isset($_GET["order"])?'order='.$_GET["order"]:''; ?>" method="POST">
<table id="payments-table">
    <thead><tr class="table-hdng">
        <th>Pay</th>
        <th>Mechanic</th>
        <th>Fee/Bonus&nbsp;ID</th>
        <th>Task&nbsp;ID</th>
        <th>Project</th>
        <th>Amount</th>
        <th width="450">Description</th>
    </tr></thead>
    <tbody>
    <input type="hidden" id="action" name="action" value="<?php
        echo isset($_POST['action']) ? "pay" : "confirm";
        ?>" />

<?php

foreach ($payee_totals as $payee) {
    echo "\r\n"; //added \r\n to make output code modestly presentable
    echo '<tr><td><input type="checkbox" name="'.$payee["mechanic_id"].'fees" onclick="javascript:toggleCBGroup(\'fees'.$payee["mechanic_id"].'\', this);" rel="0" /></td>';
    echo '<td colspan="4" align="left"><a href="javascript:void(0);" onclick="toggleVis(\'indfees'.$payee["mechanic_id"].'\')">'.$payee["mechanic_nick"].'</a></td>';
    echo '<td align="right" onclick="toggleBox(\'payfee'.$payee["mechanic_id"].'\')">'.$payee["total_amount"].'</td>';
    echo '<td>&nbsp;</td></tr></tbody>'; 
    echo "\r\n"; //added \r\n to make output code modestly presentable

    $fee_rows = '';
    $display_set = false;

    // Display fees for each user
    $ind_sql = "
        SELECT f.*, wl.project_id
        FROM
            (".FEES." f LEFT JOIN ".USERS." u ON f.user_id = u.id)
            LEFT JOIN ".WORKLIST." wl ON f.worklist_id = wl.id
        WHERE
            wl.status = 'DONE'
            AND f.paid = '0'
            AND f.withdrawn = '0'
            AND u.paypal = '1'
            AND f.amount > 0
            AND f.user_id = '".$payee["mechanic_id"]."'
            AND wl.project_id IN (" . $sql_get_fund_projects . ")";
    $ind_query = mysql_query($ind_sql);
    if (mysql_num_rows($ind_query) > 0) {
        while ($ind_fees = mysql_fetch_array($ind_query)) {
            $fee_rows .= '<tr class="'.$rowclass.'">';
            $fee_rows .= '<td class="fee-row"><input type="checkbox" class="fees'.
                $payee["mechanic_id"].'" name="payfee[]" id="payfee'.$ind_fees["id"].
                '" value="'.$ind_fees["id"].'" onclick="updateTotalFees(\'1\');" rel="'.$ind_fees["amount"].'"';
            if (isset($_POST["action"]) && ($_POST["action"] == 'confirm') &&
                in_array($ind_fees["id"], $_POST["payfee"])) {
                $fee_rows .= ' checked="checked"';
                $display_set = true;
            }
            $fee_rows .= ' /></td>';
            $fee_rows .= '<td>'.strftime("%m-%d-%Y", strtotime($ind_fees["date"])).'</td>';
            $fee_rows .= '<td onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["id"].'</td>';
            $fee_rows .= '<td align="left" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.
                '<a class="worklist-item" id="worklist-"'.$ind_fees["worklist_id"].'" href="workitem.php?job_id='.
                $ind_fees["worklist_id"].'" target="_blank">#'.$ind_fees["worklist_id"].'</a></td>';
            //$fee_rows .= '<td onclick="toggleBox(\'payfee'.$payee["id"].'\')">'.$payee["mechanic_nick"].'</td>';
            $fee_rows .= '<td align="left">'.$fund_projects[$ind_fees["project_id"]].'</td>';
            $fee_rows .= '<td align="right" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["amount"].'</td>';
            $fee_rows .= '<td align="left" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["desc"].'</td>';
            $fee_rows .= '</tr>';
            $fee_rows .=  "\r\n"; //added \r\n to make output code modestly presentable
            $rowclass=='rowodd'?$rowclass='roweven':$rowclass='rowodd';
        }
    }

    // Display bonuses for each user
    $bonus_sql = "
        SELECT
            b.id AS id,
            b.amount AS amount,
            b.desc AS notes,
            b.date AS date,
            u.nickname AS payer_name
        FROM
            fees b
            LEFT JOIN users u ON u.id = b.payer_id
        WHERE
            b.user_id = ".$payee['mechanic_id']."
            AND b.paid=0 and b.bonus=1";
    $bonus_query = mysql_query($bonus_sql);
    if (mysql_num_rows($bonus_query) > 0) {
        while ($ind_bonus = mysql_fetch_array($bonus_query)) {
            $fee_rows .= '<tr class="'.$rowclass.'">';
            $fee_rows .= '<td class="fee-row"><input type="checkbox" class="fees'.
                $payee["mechanic_id"].'" name="paybonus[]" id="paybonus'.$ind_bonus["id"].
                '" value="'.$ind_bonus["id"].'" onclick="updateTotalFees(\'1\');" rel="'.$ind_bonus["amount"].'"';
            if (isset($_POST["action"]) && ($_POST["action"] == 'confirm') &&
               in_array($ind_bonus["id"], $_POST["paybonus"])) {
                $fee_rows .= ' checked="checked"';
                $display_set = true;
            }
            $fee_rows .= ' /></td>';
            $fee_rows .= '<td>'.strftime("%m-%d-%Y", strtotime($ind_bonus["date"])).'</td>';
            $fee_rows .= '<td onclick="toggleBox(\'paybonus'.$ind_bonus["id"].'\')">'.$ind_bonus["id"].'</td>';
            $fee_rows .= '<td colspan="2" align="left" onclick="toggleBox(\'paybonus'.$ind_bonus["id"].'\')">BONUS</td>';
            $fee_rows .= '<td align="right" onclick="toggleBox(\'paybonus'.$ind_bonus["id"].'\')">'.$ind_bonus["amount"].'</td>';
            $fee_rows .= '<td align="left" onclick="toggleBox(\'paybonus'.$ind_bonus["id"].'\')">'.
                         '(FROM: '.$ind_bonus['payer_name'].') '.$ind_bonus["notes"].'</td>';
            $fee_rows .= '</tr>';
            $fee_rows .=  "\r\n"; //added \r\n to make output code modestly presentable
            $rowclass=='rowodd'?$rowclass='roweven':$rowclass='rowodd';
        }
    }

    if (mysql_num_rows($ind_query) > 0 || mysql_num_rows($bonus_query) > 0) {
        echo '<tbody id="indfees'.$payee["mechanic_id"].'"';
        if ($display_set == false) {           
            echo ' style="display: none;"';
        }
        echo '>';
        echo $fee_rows;
        echo '</tbody>';
    }
}

?>
</table>
<div id="submit-btns">
    <?php if (isset($_POST["action"]) && ($_POST["action"] == 'confirm')) { ?>
        Password: <input type="password" name="password" id="password" />
        <br>Paypal API Username: <input type="text" name="pp_api_username" id="pp_api_username" />
        <br>Paypal API Password: <input type="password" name="pp_api_password" id="pp_api_password" />
        <br>Paypal API Signature: <input type="password" name="pp_api_signature" id="pp_api_signature" /><br>
    <?php } ?> 
    <input type="submit" id="commit-btn" name="commit" value="<?php echo isset($_POST["action"])?'Pay Now':'Confirm'; ?>" />
    &nbsp;&nbsp;Total Selected: $<input type="text" id="total-selected-fees" disabled="disabled" value="0.00" />
    <input type="hidden" name="fund_id" value="<?php echo $fund_id; ?>" />
</div>
</form>

<?php
} else {
    echo $message;
    echo urldecode($pp_message);
    $logmsg = 'PayPal Error: '.date('Y-m-d H:i:s').' '.$pp_message.' ParsedResp:'.print_r($httpParsedResponseAr, true);
    echo '<p><a href="view-payments.php">Process More Payments.</a></p>';
}
?>
<?php include("footer.php"); ?>
