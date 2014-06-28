<?php

/**
 * Here lives Budget management api methods
 * most of these methods were previously living at api.php
 */


class BudgetController extends JsonController {
    public function run($action, $param = '') {
        $method = '';
        switch($action) {
            case 'allocated':
            case 'submitted':
            case 'paid':
            case 'transferred':
                if (!isset($_SESSION['is_runner']) || $_SESSION['is_runner'] != 1) {
                    return $this->setOutput(array(
                        'success' => false,
                        'message' => 'Not enough rights!'
                    ));
                }
                $this->handleSorting();
                $method = $action;
                break;
            default:
                $method = 'info';
                $param = $action;
                break;
        }
        $params = preg_split('/\//', $param);
        call_user_func_array(array($this, $method), $params);
    }

    public function info() {

    }

    public function allocated($id) {
        $budget_id = (int) $id;
        $csv = is_string($id) && substr($id, -4) == '.csv';
        if ($budget_id > 0) {
            $filter = " `budget_id`={$budget_id} AND ";
        } else {
            $filter = " `runner_id`='{$_SESSION['userid']}' AND ";
        }
        $sql = "SELECT w.`id`, w.`budget_id`, w.`summary`, w.`status`, b.`reason` " .
               " FROM " . WORKLIST . " w " .
               " LEFT JOIN " . BUDGETS . " b ON w.budget_id = b.id ".
               " WHERE {$filter} `status` IN ('QA Ready', 'In Progress', 'Review', 'Merged')";

        $sql_q = mysql_query($sql) or die(mysql_error());
        $items = array();
        while ($row = mysql_fetch_assoc($sql_q)) {
            // Get fees
            $fees = BudgetTools::getFees($row['id']);
            // Get people working there
            $who = BudgetTools::getWho($row['id']);
            // Get Date of Working
            $created = BudgetTools::getDateWorking($row['id']);
            // Get payment status
            $paid = BudgetTools::getPaymentStatus($row['id']);
            if ($paid['paid'] == 1) {
                // Get paid date
                $paid_date = BudgetTools::getPaidDate($row['id']);
                if (!$paid_date['paid_date']) {
                    $paid_date['paid_date'] = "No Data";
                }
            } else {
                $paid_date = array('paid_date'=>"Not Paid");
            }
            $items[] = array('id'=>$row['id'], 'budget_id'=>$row['budget_id'], 'budget_title'=>$row['reason'],
                            'summary'=>$row['summary'], 'who'=>$who, 'amount'=>$fees['amount'],
                            'status'=>$row['status'], 'created'=>$created['date'], 'paid'=>$paid_date['paid_date']);
        }
        if ($csv) {
            BudgetTools::exportCSV($items);
            return $this->output = false;
        } else {
            if ($this->sort && $this->desc) {
                $items = BudgetTools::sortItems($items, $this->desc, $this->sort);
            }
            return $this->setOutput(array(
                'success' => true,
                'items' => $items
            ));
        }
    }

    public function submitted($id) {
        $budget_id = (int) $id;
        $csv = is_string($id) && substr($id, -4) == '.csv';
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
            $fees = BudgetTools::getFees($row['id']);
            // Get people working there
            $who = BudgetTools::getWho($row['id']);
            // Get Date of Working
            $created = BudgetTools::getDateWorking($row['id']);
            // Get payment status
            $paid = BudgetTools::getPaymentStatus($row['id']);
            if ($paid['paid'] != 1) {
                if ($fees['amount'] > 0) {
                    $paid_date = array('paid_date'=>"Not Paid");
                    $items[] = array('id'=>$row['id'], 'budget_id'=>$row['budget_id'], 'budget_title'=>$row['reason'],
                                    'summary'=>$row['summary'], 'who'=>$who, 'amount'=>$fees['amount'],
                                    'status'=>$row['status'], 'created'=>$created['date'], 'paid'=>$paid_date['paid_date']);
                }
            }
        }
        if ($csv) {
            BudgetTools::exportCSV($items);
            return $this->output = false;
        } else {
            if ($this->sort && $this->desc) {
                $items = BudgetTools::sortItems($items, $this->desc, $this->sort);
            }
            return $this->setOutput(array(
                'success' => true,
                'items' => $items
            ));
        }
    }

    public function paid($id) {
        $budget_id = (int) $id;
        $csv = is_string($id) && substr($id, -4) == '.csv';
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
            $fees = BudgetTools::getFees($row['id']);
            // Get people working there
            $who = BudgetTools::getWho($row['id']);
            // Get Date of Working
            $created = BudgetTools::getDateWorking($row['id']);
            // Get payment status
            $paid = BudgetTools::getPaymentStatus($row['id']);
            if ($paid['paid'] == 1) {
                // Get Paid date
                $paid_date = BudgetTools::getPaidDate($row['id']);
                if (!$paid_date['paid_date']) {
                    $paid_date['paid_date'] = "No Data";
                }
                $items[] = array('id'=>$row['id'], 'budget_id'=>$row['budget_id'], 'budget_title'=>$row['reason'],
                                'summary'=>$row['summary'], 'who'=>$who, 'amount'=>$fees['amount'],
                                'status'=>$row['status'], 'created'=>$created['date'], 'paid'=>$paid_date['paid_date']);
            }
        }
        if ($csv) {
            BudgetTools::exportCSV($items);
            return $this->output = false;
        } else {
            if ($this->sort && $this->desc) {
                $items = BudgetTools::sortItems($items, $this->desc, $this->sort);
            }
            return $this->setOutput(array(
                'success' => true,
                'items' => $items
            ));
        }
    }

    public function transferred($id) {
        $budget_id = (int) $id;
        $csv = is_string($id) && substr($id, -4) == '.csv';
        $order = "";
        if ($this->sort === NULL) {
            $order = "id DESC";
        } else {
            if ($this->desc == 'true') {
                $order = "{$this->sort} DESC";
            } else {
                $order = "{$this->sort} ASC";
            }
        }

        if ($budget_id == 0) {
            $where = "b.`active` = 1 AND bs.`giver_id` = " . getSessionUserId();
        } else {
            $where = "bs.`source_budget_id`={$budget_id}";
        }

        $sql = "SELECT b.`id`, b.`reason` AS budget_title, b.`notes`, b.`transfer_date` AS created, bs.`amount_granted` AS amount, u.nickname AS who, b.receiver_id " .
               " FROM " . BUDGETS . " b " .
               "   LEFT JOIN " . USERS . " u ON b.receiver_id = u.id " .
               "   INNER JOIN " . BUDGET_SOURCE . " bs ON b.id = bs.budget_id " .
               " WHERE {$where} " .
               " ORDER BY {$order} " ;

        $sql_q = mysql_query($sql) or die(mysql_error());
        $items = array();
        while ($row = mysql_fetch_assoc($sql_q)) {
            $items[] = array('id'=>$row['id'], 'budget_title'=>$row['budget_title'],
                             'notes'=>$row['notes'], 'who'=>$row['who'],
                             'receiver_id'=>$row['receiver_id'], 'amount'=>$row['amount'],
                             'created'=> substr($row['created'], 0, 10));
        }
        if ($csv) {
            BudgetTools::exportCSV_Transferred($items);
            return $this->output = false;
        } else {
            return $this->setOutput(array(
                'success' => true,
                'items' => $items
            ));
        }
    }

    private function handleSorting() {
        $this->sort = $this->desc = null;
        if (isset($_REQUEST['sortby']) && isset($_REQUEST['desc'])) {
            switch ($_REQUEST['sortby']) {
                case 'be-id':
                case 'bet-id':
                    $this->sort = 'id';
                    break;
                case 'be-budget':
                    $this->sort = 'budget_id';
                    break;
                case 'bet-budget':
                    $this->sort = 'budget_title';
                    break;
                case 'be-summary':
                    $this->sort = 'summary';
                    break;
                case 'bet-notes':
                    $this->sort = 'notes';
                    break;
                case 'be-who':
                case 'bet-who':
                    $this->sort = 'who';
                    break;
                case 'be-amount':
                case 'bet-amount':
                    $this->sort = 'amount';
                    break;
                case 'be-status':
                    $this->sort = 'status';
                    break;
                case 'be-created':
                case 'bet-created':
                    $this->sort = 'created';
                    break;
                case 'be-paid':
                    $this->sort = 'paid';
                    break;
            }
            $this->desc = $_REQUEST['desc'];
        }
    }
}