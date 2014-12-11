<?php
/**
 *    Payments:  
 *        List of users receiving payments for the 
 *        period defined, Toggle-able view under each user 
 *        that will show the individual jobs and payments 
 *        for each / or just a one line summary with total 
 *        amount.  All payments can be checked to pay, or 
 *        to not pay, with a way to check all and clear all.
 *        Job Task_IDs can be moused over, as in the journal, 
 *        to see the info, as well as clicked to open in a 
 *        separate tab, if needed.  Pay Now button needed.  
 *        Clicking pay now gives a confirm popup. 
 */

class PaymentsController extends Controller {
    public function run() {
        //send non-payers back to the reports page.
        if (empty($_SESSION['is_payer'])) {
            $this->view = null;
            Utils::redirect("./reports");
        }

        $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
        $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
        $userId = Session::uid();

        $payer_id = $userId;
        // set default fund to worklist
        $fund_id = 3;

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

        $this->sql_get_fee_totals = "
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
                wl.status = 'Done'
                AND f.paid = '0'
                AND f.withdrawn = '0'
                AND f.amount > 0
                AND u.paypal_verified = '1'
                AND u.has_W2 = 0
                AND wl.project_id IN (" . $sql_get_fund_projects . ")
            GROUP BY f.user_id
            ";

        $this->sql_get_bonus_totals = false;

        // only pull bonuses for if worklist fund chosen - temporary hardcoding
        // until we determine further solution
        if ($fund_id == 3) {
            $this->sql_get_bonus_totals = "
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
                    AND b.withdrawn = 0
                    AND u.paypal_verified = '1' 
                    AND b.bonus = 1
                    AND u.has_W2 = 0
               GROUP BY b.user_id
                ";
        }

        $action = (isset($_POST["action"])) ? $_POST["action"] : '';

        // Initialize empty arrays if no fees or bonuses were selected
        if (!isset($_POST['payfee'])) {
            $_POST['payfee'] = array();
        }
        if (!isset($_POST['paybonus'])) {
            $_POST['paybonus'] = array();
        }

        $pp_message = $httpParsedResponseAr = $alert_msg = $message = "";

        //Check action - should be confirm, pay or not set
        switch ($action) {
            case 'confirm':
                //$fees_csv = implode(',', $_POST["payfee"]);
                //pull list of payees from db based on the time span
                $payee_totals = $this->getUserTotalsArray();

            break; 
            
            case 'pay':
                //collect confirmed payees and run paypal transaction
                //include_once("../paypal-password.php");
                if ($this->checkAdmin($_POST['password']) == '1') {
                    error_log("Made it Admin!");
                    if(empty($_POST['pp_api_username']) || empty($_POST['pp_api_password']) || empty($_POST['pp_api_signature'])){
                        $alert_msg = "You need to provide all credentials!";
                        break;
                    }

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
                    $bonus_id_csv = $num_bonuses ? implode(',', $_POST["paybonus"]) : 0 ;
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
                    $bonus_info_results = mysql_query($bonus_info_sql) or error_log("bonussql failed: ".mysql_error()."\n$bonus_info_sql");

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
                                'uniqueID' => $fees_data["fee_id"],
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

                    // Execute the API operation; see the PPHttpPost function
                    $httpParsedResponseAr = $this->PPHttpPost($nvpStr, $_POST);
                    #$httpParsedResponseAr = array("ACK" => "SUCCESS");

                    if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
                        error_log('masspay success!');
                        $pp_message = '<p>MassPay Completed Successfully! - $'.$totalFees.' Paid.</p>';
                        if (isset($_GET["debug"])) {
                            $pp_message .= '<p><pre>'.print_r($httpParsedResponseAr, true).'</pre></p>';
                        }
                        //$fee_sql_update = "UPDATE ".FEES." SET paid=1, paid_date='".date("Y-m-d H:i:s")."' WHERE id in (".$fees_csv.")";
                        //$update_fees_paid = mysql_query($fee_sql_update);

                        $summaryData = Fee::markPaidByList(explode(',', $fee_id_csv), $user_paid = 0, $paid_notes='', $paid = 1, $fund_id);
                        if ($bonus_id_csv) {
                            Bonus::markPaidByList(explode(',', $bonus_id_csv),  $user_paid = 0, $paid = 1, false, $fund_id);
                        }

                    } else  {
                        $alert_msg = "MassPay Failure"; 
                        $pp_message = '<p>MassPay failed:</p><p><pre>' . print_r($httpParsedResponseAr, true).'</pre></p>';
                        if(!Utils::send_email('kordero@gmail.com', 'Masspay Fail', $pp_message)) {
                            error_log("view-payments:MassPayFailure: Utils::send_email failed");
                        }
                    }

                } else {
                    $error_msg = 'Invalid MassPay Authentication<br />';
                    $error_msg .= 'IP: '. $_SERVER['REMOTE_ADDR'].'<br />';
                    $error_msg .= 'UserID: '.$userId;
                    if (!Utils::send_email("kordero@gmail.com", "Masspay Invalid Auth Attempt", $error_msg)) {
                        error_log("view-payments:MassPayAuth: Utils::send_email failed");
                    }
                    $alert_msg = "Invalid Authentication"; 
                }
                break; 
            
            default:
                //pull list of payees from db based on the time span
                $payee_totals = $this->getUserTotalsArray();
            break;
        }
        
        $this->write('fund_id', $fund_id);
        $this->write('message', $message);
        $this->write('pp_message', $pp_message);
        $this->write('alert_msg', $alert_msg);
        $this->write('payee_totals', $payee_totals);
        $this->write('fund_projects', $fund_projects);
        $this->write('sql_get_fund_projects', $sql_get_fund_projects);
        $this->write('input', array(
            'action' => isset($_POST['action']) ? $_POST['action'] : '',
            'order' => isset($_GET["order"]) ? 'order='.$_GET["order"] : ''
        ));
        parent::run();
    }

    public function report() {
        if (empty($_SESSION['is_runner']) && empty($_SESSION['is_payer']) && isset($_POST['paid'])) {
            $this->view = null;
            Utils::redirect("jobs");
            return;
        }

        $this->view = new ReportsView();

        if (!empty($_REQUEST['payee'])) {
            $payee = new User();
            $payee->findUserByNickname($_REQUEST['payee']);
            $_REQUEST['user'] = $payee->getId();
        }

        $showTab = 0;
        if (!empty($_REQUEST['view'])) {
            if ($_REQUEST['view'] == 'chart') {
                $showTab = 1;
            }
            if ($_REQUEST['view'] == 'payee') {
                $showTab = 2;
            }
        }
        $this->write('showTab', $showTab);

        $w2_only = 0;
        if (! empty($_REQUEST['w2_only'])) {
            if ($_REQUEST['w2_only'] == 1) {
                $w2_only = 1;
            }
        }
        $this->write('w2_only', $w2_only);

        $_REQUEST['name'] = '.reports';
        if(isset($_POST['paid']) && !empty($_POST['paidList']) && !empty($_SESSION['is_payer'])) {
            // we need to decide if we are dealing with a fee or bonus and call appropriate routine
            $fees_id = explode(',', trim($_POST['paidList'], ','));
            foreach($fees_id as $id) {
                $query = "SELECT `id`, `bonus` FROM `".FEES."` WHERE `id` = $id ";
                $result = mysql_query($query);
                $row = mysql_fetch_assoc($result);
                if($row['bonus']) {
                    bonus::markPaidById($id,$user_paid=0, $paid=1, true, $fund_id=false);
                } else {
                    Fee::markPaidById($id, $user_paid=0, $paid_notes='', $paid=1, true, $fund_id=false);
                }
            }
        }

        parent::run();
    }

    public function reportApi() {
        $this->view = null;

        $limit = 30;

        $_REQUEST['name'] = '.reports';
        $from_date = $_REQUEST['start'];
        $to_date = $_REQUEST['end'];
        $paidStatus = $_REQUEST['paidstatus'];
        $page = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
        $w2_only = (int) $_REQUEST['w2_only'];
        $dateRangeFilter = '';

        if (isset($from_date) || isset($to_date)) {
            $mysqlFromDate = $this->GetTimeStamp($from_date);
            $mysqlToDate = $this->GetTimeStamp($to_date);
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

        $sfilter = (
            isset($_REQUEST['status'])
                ? $_REQUEST['status']
                : 'Bidding,In Progress,QA Ready,Review,Merged,Suggestion'
        );
        $pfilter = (int) $_REQUEST['project_id'];
        $fundFilter = $_REQUEST['fund_id'] ? (int) $_REQUEST['fund_id'] : -1;
        $ufilter = (int) $_REQUEST['user'];
        $rfilter = (int) $_REQUEST['runner'];
        $order = isset($_REQUEST['order']) ? $_REQUEST['order'] : 'name';
        $dir = isset($_REQUEST['dir']) ? $_REQUEST['dir'] : 'ASC';
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'ALL';
        $queryType = isset($_REQUEST['qType']) ? $_REQUEST['qType'] : 'detail';

        $where = '';
        if ($ufilter) {
            $where = " AND `".FEES."`.`user_id` = $ufilter ";
        }

        if ($rfilter) {
            $where = " AND `".FEES."`.`user_id` = $rfilter AND `" . WORKLIST . "`.runner_id = $rfilter ";
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

            $json = json_encode($report);
            echo $json;
        } else if ($queryType == "chart" ) {
            $fees = array();
            $uniquePeople = array();
            $feeCount = array();
            if(isset($from_date)) {
              $fromDate = ReportTools::getMySQLDate($from_date);
            }
            if(isset($to_date)) {
              $toDate = ReportTools::getMySQLDate($to_date);
            }
            $fromDateTime = mktime(0,0,0,substr($fromDate,5,2),  substr($fromDate,8,2), substr($fromDate,0,4));
            $toDateTime = mktime(0,0,0,substr($toDate,5,2),  substr($toDate,8,2), substr($toDate,0,4));

            $daysInRange = round( abs($toDateTime-$fromDateTime) / 86400, 0 );
            $rollupColumn = ReportTools::getRollupColumn('`date`', $daysInRange);
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
            $json_data = array('fees' => ReportTools::fillAndRollupSeries($fromDate, $toDate, $fees, false, $dateRangeType),
                'uniquePeople' => ReportTools::fillAndRollupSeries($fromDate, $toDate, $uniquePeople, false, $dateRangeType),
                'feeCount' => ReportTools::fillAndRollupSeries($fromDate, $toDate, $feeCount, false, $dateRangeType),
                'labels' => ReportTools::fillAndRollupSeries($fromDate, $toDate, null, true, $dateRangeType),
                'fromDate' => $fromDate, 'toDate' => $toDate);
            $json = json_encode($json_data);
            echo $json;
        } else if($queryType == "payee") {
            $payee_report = array();
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
    }

    private function getUserTotalsArray() {
        // Retuns an array with the total amount owed to each user including
        // worklist payments and bonuses. Fields in the array are:
        //  total_amount, mechanic_id, worklist_item, mechanic_nick, mechanic_payal_email
        // Creating 1 SQL statement to combine FEES and BONUS_PAYMENTS blew my mind
        // so I'm combining it in PHP. -Alexi 2011-03-03
        $sql_get_fee_totals = $this->sql_get_fee_totals;
        $sql_get_bonus_totals = $this->sql_get_bonus_totals;
        
        $fee_totals_query   = mysql_query($sql_get_fee_totals);

        $totals_array = array();
        while ($fees_array = mysql_fetch_array($fee_totals_query, MYSQL_ASSOC)) {
            $totals_array[] = $fees_array;
        }
        
        if ($sql_get_bonus_totals) {
            $bonus_totals_query = mysql_query($sql_get_bonus_totals);

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
        }

        return $totals_array;
    }

    /*******************************************************
        PPHttpPost: NVP post function for masspay.
        Author: Jason (jkofoed@gmail.com)
        Date: 2010-04-01 [Happy April Fool's!]
    ********************************************************/
    private function PPHttpPost($nvpStr_, $credentials) {
        $environment = PAYPAL_ENVIRONMENT;
        $pp_user = $credentials['pp_api_username'];
        $pp_pass = $credentials['pp_api_password'];
        $pp_signature = $credentials['pp_api_signature'];

        $API_Endpoint = "https://api-3t.paypal.com/nvp";
        if("sandbox" === $environment || "beta-sandbox" === $environment) {
            $API_Endpoint = "https://api.$environment.paypal.com/nvp";
        }
        $version = urlencode('51.0');

        // Set the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // Set the API operation, version, and API signature in the request.
        $nvpreq = 'METHOD=MassPay&VERSION='.$version.'&PWD='.$pp_pass.'&USER='.$pp_user.'&SIGNATURE='.$pp_signature.''.$nvpStr_;

        // Set the request as a POST FIELD for curl.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        // Get response from the server.
        error_log('requesting paypal payment');
        $httpResponse = curl_exec($ch);
        error_log($httpResponse);

        if(!$httpResponse) {
            exit("MassPay failed: ".curl_error($ch).'('.curl_errno($ch).')');
        }

        // Extract the response details.
        $httpResponseAr = explode("&", $httpResponse);
        $httpParsedResponseAr = array();
        foreach ($httpResponseAr as $i => $value) {
            $tmpAr = explode("=", $value);
            if(sizeof($tmpAr) > 1) {
                $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
            }
        }
        $httpParsedResponseAr["nvpEndpoint"] = $API_Endpoint;
        $httpParsedResponseAr["nvpString"] = $nvpreq;
        if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
            exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
        }

        return $httpParsedResponseAr;
    }

    private function checkAdmin($pass) {
        //checks admin login.
        $sql = "SELECT * FROM ".PAYPAL_ADMINS." WHERE `password` = '".md5($pass)."'";
        $result = mysql_query($sql);
        //if successful, this will be 1, otherwise 0
        return mysql_num_rows($result);
    }

    private function GetTimeStamp($MySqlDate, $i='') {
        if (empty($MySqlDate)) $MySqlDate = date('Y/m/d');
        $date_array = explode("/",$MySqlDate); // split the array

        $var_year = $date_array[0];
        $var_month = $date_array[1];
        $var_day = $date_array[2];
        $var_timestamp=$date_array[2]."-".$date_array[0]."-".$date_array[1];
        //$var_timestamp=$var_month ."/".$var_day ."-".$var_year;
        return($var_timestamp); // return it to the user
    }
}
