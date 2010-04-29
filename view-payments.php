<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
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

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

//open db connection
$db = @mysql_connect (DB_SERVER, DB_USER, DB_PASSWORD) or die ('I cannot connect to the database because: ' . mysql_error());
$db = @mysql_select_db(DB_NAME);

$rowclass = 'rowodd';

//Check action - should be confirm, pay or not set
switch ($_POST["action"])
{
    case 'confirm':
    $fees_csv = implode(',', $_POST["payfee"]);
    //pull list of payees from db based on the time span
        $group_payees_sql = "SELECT sum(f.amount) as total_amount, u.id AS mechanic_id, u.nickname AS mechanic_nick, u.paypal_email AS mechanic_paypal_email, wl.summary AS worklist_item FROM (fees f LEFT JOIN users u ON f.user_id = u.id) LEFT JOIN worklist wl ON f.worklist_id = wl.id WHERE wl.status = 'DONE' AND f.paid = '0' AND f.withdrawn = '0' AND f.amount > 0 AND u.paypal = '1' GROUP BY f.user_id ORDER BY u.id";
        $payee_group_query = mysql_query($group_payees_sql);
    break; 
    
    case 'pay':
    //collect confirmed payees and run paypal transaction
    
    include_once("paypal-functions.php");
    include_once("classes/Fee.class.php");   

    //Get fee information for paypal transaction 
    $num_fees = count($_POST["payfee"]);
    $fees_csv = implode(',', $_POST["payfee"]);
    $fees_info_sql = 'SELECT f.id AS fee_id, f.amount AS amount, f.worklist_id AS worklist_id, u.id AS mechanic_id, u.nickname AS mechanic_nick, u.paypal_email AS mechanic_paypal_email, wl.summary AS worklist_item FROM (fees f LEFT JOIN users u ON f.user_id = u.id) LEFT JOIN worklist wl ON f.worklist_id = wl.id WHERE f.id in ('.$fees_csv.')';
    $fees_info_results = mysql_query($fees_info_sql);

    // Set request-specific fields.
    $emailSubject =urlencode('You\'ve got money!');
    $receiverType = urlencode('EmailAddress');
    $currency = urlencode('USD');                           // or other currency ('GBP', 'EUR', 'JPY', 'CAD', 'AUD')

    // Add request-specific fields to the request string.
    $nvpStr="&EMAILSUBJECT=$emailSubject&RECEIVERTYPE=$receiverType&CURRENCYCODE=$currency";

    //build payment data array
    $receiversArray = array();
    $i = 0;   //index value
    while ($fees_data = mysql_fetch_array($fees_info_results)) {
        $receiverData = array(  'receiverEmail' => $fees_data["mechanic_paypal_email"],
                                'amount' => $fees_data["amount"],
                                'uniqueID' => $fees_data["fee_id"],
                                'note' => '#'.$fees_data["worklist_id"].' - '.$fees_data["worklist_item"]);
        $receiversArray[$i] = $receiverData;
        $i++;
    }

    //build nvp string
    foreach($receiversArray as $i => $receiverData) {
        $receiverEmail = urlencode($receiverData['receiverEmail']);
        $amount = urlencode($receiverData['amount']);
        $uniqueID = urlencode($receiverData['uniqueID']);
        $note = urlencode($receiverData['note']);
        $nvpStr .= "&L_EMAIL$i=$receiverEmail&L_Amt$i=$amount&L_UNIQUEID$i=$uniqueID&L_NOTE$i=$note";
    }



    // Execute the API operation; see the PPHttpPost function in the paypal-functions.php file.
    $httpParsedResponseAr = PPHttpPost('MassPay', $nvpStr);

    if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
        $pp_message = '<p>MassPay Completed Successfully!</p>';
        if (isset($_GET["debug"])) { $pp_message .= '<p><pre>'.print_r($httpParsedResponseAr, true).'</pre></p>'; }
        //$fee_sql_update = "UPDATE ".FEES." SET paid=1, paid_date='".date("Y-m-d H:i:s")."' WHERE id in (".$fees_csv.")";
        //$update_fees_paid = mysql_query($fee_sql_update);
        
        $summaryData = Fee::markPaidByList(explode($fees_csv), $user_paid=0, $paid_notes='', $paid=1);

        foreach ($summaryData as $user_id=>$data) {
            if ($data[0] > 0) {
                $mail = 'SELECT `username`,`rewarder_points` FROM '.USERS.' WHERE `id` = '.$user_id;
                $userData = mysql_fetch_array(mysql_query($mail));

                $subject = "LoveMachine paid you $".$data[0];
                $body  = "You earned ".$data[1]." rewarder points.  You currently have ".$userData['rewarder_points']." points available to reward other LoveMachiners with. ";
                $body .= "Reward them now on the Rewarder page:<br/>&nbsp;&nbsp;&nbsp;&nbsp;".SERVER_BASE."worklist/rewarder.php<br/><br/>";
                $body .= "Thank you!<br/><br/>Love,<br/>Philip and Ryan<br/>";

                sl_send_email($userData['username'], $subject, $body);
            }
        }

    } else  {
        $pp_message = '<p>MassPay failed:</p><p><pre>' . print_r($httpParsedResponseAr, true).'</pre></p>';
        //TODO: add a email send here to alert someone?
    }


    break; 
    
    default:
    //pull list of payees from db based on the time span
        $group_payees_sql = "SELECT sum(f.amount) as total_amount, u.id AS mechanic_id, u.nickname AS mechanic_nick, u.paypal_email AS mechanic_paypal_email FROM (fees f LEFT JOIN users u ON f.user_id = u.id)  LEFT JOIN worklist wl ON f.worklist_id = wl.id WHERE wl.status = 'DONE' AND f.paid = '0' AND f.withdrawn = '0' AND f.amount > 0 AND u.paypal = '1' GROUP BY f.user_id"; 
        $payee_group_query = mysql_query($group_payees_sql);
    break;
}



/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/payments.css" rel="stylesheet" type="text/css">
<link href="css/ui.toaster.css" rel="stylesheet" type="text/css">
<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.tablednd_0_5.js"></script>
<script type="text/javascript" src="js/jquery.template.js"></script>
<script type="text/javascript" src="js/jquery.jeditable.min.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="js/timepicker.js"></script>
<script type="text/javascript" src="js/jquery.tabSlideOut.v1.3.js"></script>
<script type="text/javascript" src="js/ui.toaster.js"></script>
<script type="text/javascript" src="js/payments.js"></script>
</head>
<body>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->
<div id="outside">
<div id="container">
<div id="welcome"></div>
<div id="left"></div>

<div id="center">

<?php 
if (!isset($_POST["action"]) || ($_POST["action"] != 'pay')) {
?>
<div id="select-actions">Actions: [<a href="javascript:void(0);" onclick="toggleCBs('toggle');">Invert Selection</a>] | [<a href="javascript:void(0);" onclick="toggleCBs('select');">Select All</a>] | [<a href="#" onclick="toggleCBs('unselect');">Select None</a>]</div>
<form action="view-payments.php?<?php echo isset($_GET["order"])?'order='.$_GET["order"]:''; ?>" method="POST">
<table id="payments-table">
    <thead><tr class="table-hdng">
        <th>Pay</th>
        <th>Mechanic</th>
        <th>Fee&nbsp;ID</th>
        <th>Task&nbsp;ID</th>
        <th>Amount</th>
        <th width="450">Description</th>
    </tr></thead>
    <tbody>
<?php if (!isset($_POST["action"])) { ?>
    <input type="hidden" id="action" name="action" value="confirm" />
<?php } else { ?>
    <input type="hidden" id="action" name="action" value="pay" />
<?php } ?>
    

<?php 

while ($payees = mysql_fetch_array($payee_group_query)) {
    echo "\r\n"; //added \r\n to make output code modestly presentable
    echo '<tr><td><input type="checkbox" name="'.$payees["mechanic_id"].'fees" onclick="javascript:toggleCBGroup(\'fees'.$payees["mechanic_id"].'\', this);" /></td>';    
    echo '<td colspan="3" align="left"><a href="javascript:void(0);" onclick="toggleVis(\'indfees'.$payees["mechanic_id"].'\')">'.$payees["mechanic_nick"].'</a></td>';
    echo '<td align="right" onclick="toggleBox(\'payfee'.$payees["mechanic_id"].'\')">'.$payees["total_amount"].'</td>';
    echo '<td>&nbsp;</td></tr></tbody>'; 
    echo "\r\n"; //added \r\n to make output code modestly presentable
    $ind_sql = "SELECT f.* FROM (fees f LEFT JOIN users u ON f.user_id = u.id)  LEFT JOIN worklist wl ON f.worklist_id = wl.id WHERE wl.status = 'DONE' AND f.paid = '0' AND f.withdrawn = '0' AND u.paypal = '1' AND f.amount > 0 AND f.user_id = '".$payees["mechanic_id"]."'";
    $ind_query = mysql_query($ind_sql);
    if (mysql_num_rows($ind_query) > 0) {
        $fee_rows = '';
        $display_set = false;
        while ($ind_fees = mysql_fetch_array($ind_query)) {
            $fee_rows .= '<tr class="'.$rowclass.'">';
            $fee_rows .= '<td class="fee-row"><input type="checkbox" class="fees'.$payees["mechanic_id"].'" name="payfee[]" id="payfee'.$ind_fees["id"].'" value="'.$ind_fees["id"].'"';
            if (isset($_POST["action"]) && ($_POST["action"] == 'confirm') && in_array($ind_fees["id"], $_POST["payfee"])) { $fee_rows .= ' checked="checked"'; $display_set = true;}
            $fee_rows .= ' /></td>';
            $fee_rows .= '<td>'.strftime("%m-%d-%Y", strtotime($ind_fees["date"])).'</td>';
            $fee_rows .= '<td onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["id"].'</td>';
            $fee_rows .= '<td align="left" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')"><a class="worklist-item" id="worklist-"'.$ind_fees["worklist_id"].'" href="workitem.php?job_id='.$ind_fees["worklist_id"].'" target="_blank">#'.$ind_fees["worklist_id"].'</td>';
            //$fee_rows .= '<td onclick="toggleBox(\'payfee'.$payee["id"].'\')">'.$payee["mechanic_nick"].'</td>';
            $fee_rows .= '<td align="right" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["amount"].'</td>';
            $fee_rows .= '<td align="left" onclick="toggleBox(\'payfee'.$ind_fees["id"].'\')">'.$ind_fees["desc"].'</td>';
            $fee_rows .= '</tr>';
            $fee_rows .=  "\r\n"; //added \r\n to make output code modestly presentable
            $rowclass=='rowodd'?$rowclass='roweven':$rowclass='rowodd';
        }
        echo '<tbody id="indfees'.$payees["mechanic_id"].'"';
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
    <input type="submit" id="commit-btn" name="commit" value="<?php echo isset($_POST["action"])?'Pay Now':'Confirm'; ?>" />
</div>
</form>

<?php
} else {
    echo urldecode($pp_message);
    echo '<p><a href="view-payments.php">Process More Payments.</a></p>';
}
?>

<?php include("footer.php"); ?>
</body>
</html>
