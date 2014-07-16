<?php

/**
 * Here lives Budget management api methods
 * most of these methods were previously living at api.php
 */

require_once('models/DataObject.php');
require_once ("models/Budget.php");

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
            case 'info':
            case 'update':
            case 'close':
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

    public function info($id) {
        try {
            $user = User::find(getSessionUserId());
            if (!$user->getId()) {
                throw new Exception('You have to be logged in to access user info!');
            }
            $budget = new Budget();
            $budget->loadById((int) $id);
            if (!$budget->id) {
                throw new Exception('Invalid budget id');
            }
            $ssources = $budget->loadSources();
            $budgetClosed = !$budget->active;
            $allocated = $budget->getAllocatedFunds();
            $submitted = $budget->getSubmittedFunds();
            $paid = $budget->getPaidFunds();
            $transfered = $budget->getTransferedFunds();
            $remaining = $budget->amount - $allocated - $submitted - $paid - $transfered;
            $data = array(
                'amount' => (float) $budget->amount,
                'closed' => $budgetClosed,
                'reason' => $budget->reason,
                'req_user_authorized' => strpos(BUDGET_AUTHORIZED_USERS, "," . $reqUserId . ",") !== false,
                'seed' => (int) $budget->seed,
                'sources' => $sources,
                'notes' => $budget->notes,
                'remaining' => (float) $remaining,
                'allocated' => (float) $allocated,
                'submitted' => (float) $submitted,
                'paid' => (float) $paid,
                'transferred' => (float) $transfered,
                'receiver_id' => $budget->receiver_id
            );
            return $this->setOutput(array(
                'success' => true,
                'data' => $data
            ));
        } catch (Exception $e) {
            return $this->setOutput(array(
                'success' => true,
                'message' => $e->getMessage()
            ));
        }
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

    public function update($id) {
        try {
            if (!getSessionUserId()) {
                throw new Exception('Not enough rights');
            }

            if (!isset($_POST['budget_seed']) || (!isset($_POST['source_txt']) && !isset($_POST['source_id'])) || !isset($_POST['budget_note'])) {
                throw new Exception('Invalid parameters');
            }

            $budget_seed = (int) $_POST['budget_seed'];
            $source_txt = mysql_real_escape_string($_POST['source_txt']);
            $source_id = (int) $_POST['source_id'];
            $add_funds_to = (int) $id;
            $budget_note = mysql_real_escape_string($_POST['budget_note']);
            if ($budget_seed == 1) {
                $source_id = 0;
                $source = $source_txt;
                if (empty($source)) {
                    throw new Exception('Source field is mandatory');
                }
            } else {
                $source = "Amount from budget id: " . $source_id;
                if ($source_id == 0) {
                    throw new Exception('Source field is mandatory');
                }
            }

            $receiver_id = intval($_POST['receiver_id']);
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $reason = mysql_real_escape_string($_POST['reason']);
            if (empty($receiver_id)) {
                throw new Exception('Receiver field is mandatory');
            }
            if (empty($amount)) {
                throw new Exception('Amount field is mandatory');
            }
            if ($add_funds_to == 0 && empty($reason)) {
                throw new Exception('For field is mandatory');
            }

            $giver = new User();
            $receiver = new User();
            if (!$giver->findUserById(getSessionUserId()) || !$receiver->findUserById($receiver_id)) {
                throw new Exception('Invalid user');
            }

            $stringAmount = number_format($amount, 2);

            if ($budget_seed != 1) {
                $budget = new Budget();
                if (!$budget->loadById($source_id) ) {
                    throw new Exception('Invalid budget!');
                }
                // Check if user is owner of source budget
                if ($budget->receiver_id != getSessionUserId()) {
                    error_log('Possible Hacking attempt: User ' . getSessionUserId() . ' attempted to budget ' . $amount . ' to ' . $receiver_id . ' from budget ' . $budget->id);
                    throw new Exception('You\'re not the owner of this budget!');
                }
                $remainingFunds = $budget->getRemainingFunds();
            }

            $add_funds_to_budget = false;
            if ($add_funds_to != 0) {
                $add_funds_to_budget = new Budget();
                if (!$add_funds_to_budget->loadById($add_funds_to) ) {
                    throw new Exception('Invalid budget');
                }
                $grantor = new User();
                if (!$grantor->findUserById($add_funds_to_budget->giver_id)) {
                    throw new Exception('Invalid grantor');
                }
            }

            if ($budget_seed != 1 && ($amount > $budget->getRemainingFunds())) {
                throw new Exception('Not enough budget available (total: $' . $giver->getBudget() . " from budget #" . $budget->id . ")");
            }

            $receiver->setBudget($receiver->getBudget() + $amount)->save();
            if ($add_funds_to == 0) {
                $query = "
                    INSERT INTO `" . BUDGETS . "` (
                        `giver_id`,
                        `receiver_id`,
                        `amount`,
                        `remaining`,
                        `reason`,
                        `transfer_date`,
                        `seed`,
                        `source_data`,
                        `notes`,
                        `active`
                    ) VALUES (
                        '" .  $_SESSION['userid'] . "',
                        '$receiver_id',
                        '$amount',
                        '$amount',
                        '$reason',
                        NOW(),
                        '$budget_seed',
                        '$source',
                        '$budget_note',
                        1
                    )";
                if (!mysql_unbuffered_query($query)){
                    throw new Exception('Error in query.');
                }
                $add_funds_to =  mysql_insert_id();
            } else {
                $query = "
                    UPDATE `" . BUDGETS . "`
                    SET `amount`= `amount` + $amount, `remaining` = `remaining` + $amount
                    WHERE id = $add_funds_to";
                if (!mysql_unbuffered_query($query)) {
                    throw new Exception('Error in query.');
                }
            }
            $query = "
                INSERT INTO `" . BUDGET_SOURCE . "` (
                    `giver_id`,
                    `budget_id`,
                    `source_budget_id`,
                    `amount_granted`,
                    `original_amount`,
                    `transfer_date`,
                     `source_data`
                ) VALUES (
                    '" .  $_SESSION['userid'] . "',
                    '$add_funds_to',
                    '$source_id',
                    '$amount',
                    '0',
                    NOW(),
                    '$source'
                )";
            if (!mysql_unbuffered_query($query)){
                throw new Exception('Error in query.');
            }
            if ($budget_seed != 1) {
                $giver->updateBudget(-$amount, $source_id);
                $budget = new Budget();
                $budget->loadById($add_funds_to);
                $reason = $budget->reason;
            }
            $query2 = "
                UPDATE `" . USERS . "`
                SET `is_runner` = 1
                WHERE `id` = $receiver_id
                  AND `is_runner` = 0 ";
            if (!mysql_unbuffered_query($query2)) {
                throw new Exception('Error in query.');
            }

            sendJournalNotification('@' . $giver->getNickname() . ' budgeted @' . $receiver->getNickname() . " $" . number_format($amount, 2) . " for " . $reason . ".");
            if ($add_funds_to_budget == false) {
                Notification::notifyBudget($amount, $reason, $giver, $receiver);
            } else {
                Notification::notifyBudgetAddFunds($amount, $giver, $receiver, $grantor, $add_funds_to_budget);
            }
            if ($budget_seed == 1) {
                Notification::notifySeedBudget($amount, $reason, $source, $giver, $receiver);
            }
            $receiver = getUserById($receiver_id);
            return $this->setOutput(array(
                'success' => true,
                'message' => 'You gave ' . '$' . $stringAmount . ' budget to ' . $receiver->nickname
            ));
        } catch (Exception $e) {
            return $this->setOutput(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }

    }

    public function close($id) {
        try {
            $user = User::find(getSessionUserId());
            if (!$user->getId()) {
                throw new Exception('You have to be logged in to access user info!');
            }
            $budget_id = (int) $id;

            $budget = new Budget();
            if (!$budget->loadById($budget_id)) {
                throw new Exception('Invalid budget id');
            }

            if ($budget->active != 1) {
                throw new Exception('This budget is already closed.');
            }

            if ($user->getId() != $budget->receiver_id && $budget->giver_id != $user->getId()) {
                throw new Exception('Not enough rights');
            }
            $budgetGiver = new User();
            if (!$budgetGiver->findUserById($budget->giver_id)) {
                throw new Exception('Invalid giver id.');
            }
            $budgetReceiver = new User();
            if (!$budgetReceiver->findUserById($budget->receiver_id)) {
                throw new Exception('Invalid receiver id.');
            }
            // all the child budgets are closed ?
            $childrenNotClosed = $budget->getChildrenNotClosed($budget->id);
            if ($childrenNotClosed != 0) {
                throw new Exception(
                    "This budget has one or more sub-allocated budget that are still active. " .
                    "You may not close out this budget until the other budgets are closed out."
                );
            }

            // all the budgeted jobs are paid ?
            $feeAmountNotPaid = $this->getSumOfFeeNotPaidByBudget($budget->id);
            if ($feeAmountNotPaid !== null) {
                throw new Exception('Some fees are not paid.');
            }

            $remainingFunds = $budget->getRemainingFunds();
            if ($remainingFunds >= 0) {
                $budget->original_amount = $budget->amount;
                $budget->amount = $budget->original_amount - $remainingFunds;
                $budget->active = 0;
                $budgetReceiver->updateBudget(- $remainingFunds, $budget->id, false);
                $this->closeOutBudgetSource($remainingFunds, $budget, $budgetReceiver, $budgetGiver);
                if (!$budget->save('id')) {
                    throw new Exception('Error in update budget.');
                }
            } else {
                if ($user->getId() == $budget->receiver_id) {
                    throw new Exception('Your budget is spent. Please contact the grantor (' . $budgetGiver->getNickname() . ') for additional funds.');
                }
                $budget->original_amount = $budget->amount;
                $budget->amount = $budget->original_amount - $remainingFunds;
                $budget->active = 0;
                $budgetReceiver->updateBudget(- $remainingFunds, $budget->id, false);
                $this->closeOutBudgetSource($remainingFunds, $budget, $budgetReceiver, $budgetGiver);
                if (!$budget->save('id')) {
                    throw new Exception('Error in update budget.');
                }
            }
            $this->setOutput(array(
                'success' => true,
                'message' => 'Budget closed'
            ));
        } catch (Exception $e) {
            return $this->setOutput(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    private function getSumOfFeeNotPaidByBudget($budget_id) {
        $query = "
            SELECT SUM(`amount`)
            FROM `" . FEES . "`
            WHERE paid = 0
              AND amount > 0
            AND `" . FEES . "`.`withdrawn` != 1
            AND (
                    (worklist_id = 0 AND budget_id = {$budget_id})
                  OR worklist_id IN
                    (
                      SELECT id
                      FROM " . WORKLIST . "
                      WHERE budget_id = {$budget_id}
                        AND status != 'Pass'
                    )
                )";
        $result_query = mysql_query($query);
        $row = $result_query ? mysql_fetch_row($result_query) : null;
        return !empty($row) ? $row[0] : null;
    }

    private function closeOutBudgetSource($remainingFunds, $budget, $budgetReceiver, $budgetGiver) {
        $sources = $budget->loadSources(" ORDER BY s.transfer_date DESC");
        if ($sources == null) {
            $this->setOutput(array(
                'success' => true,
                'message' => 'No source budget found!'
            ));
            exit;
        }
        foreach ($sources as $source) {
            $budgetGiver = new User();
            if (!$budgetGiver->findUserById($source["giver_id"])) {
                $this->setOutput(array(
                    'success' => true,
                    'message' => 'Invalid giver id.'
                ));
                exit;
            }
            if ($remainingFunds < 0) {
                if ($budget->seed != 1) {
                    $budget->updateSources($source["source_id"], - $remainingFunds);
                    $budgetGiver->updateBudget($remainingFunds, $source["budget_id"]);
                }
                $this->sendBudgetcloseOutEmail(array(
                    "budget_id" => $budget->id,
                    "reason" => $budget->reason,
                    "giver_id" => $source["giver_id"],
                    "receiver_id" => $budget->receiver_id,
                    "receiver_nickname" => $budgetReceiver->getNickname(),
                    "receiver_email" => $budgetReceiver->getUsername(),
                    "giver_nickname" => $budgetGiver->getNickname(),
                    "giver_email" => $budgetGiver->getUsername(),
                    "remainingFunds" => $remainingFunds,
                    "original_amount" => $budget->original_amount,
                    "amount" => $budget->amount,
                    "seed" => $budget->seed
                ));
                return;
            } else {
                if ($remainingFunds > $source["amount_granted"]) {
                    $remainingFundsToGiveBack = $source["amount_granted"];
                    $remainingFunds = $remainingFunds - $source["amount_granted"];
                } else {
                    $remainingFundsToGiveBack = $remainingFunds;
                    $remainingFunds = 0;
                }
                if ($budget->seed != 1) {
                    $budget->updateSources($source["source_id"], - $remainingFundsToGiveBack);
                    $budgetGiver->updateBudget($remainingFundsToGiveBack, $source["budget_id"]);
                }
                $this->sendBudgetcloseOutEmail(array(
                    "budget_id" => $budget->id,
                    "reason" => $budget->reason,
                    "giver_id" => $source["giver_id"],
                    "receiver_id" => $budget->receiver_id,
                    "receiver_nickname" => $budgetReceiver->getNickname(),
                    "receiver_email" => $budgetReceiver->getUsername(),
                    "giver_nickname" => $budgetGiver->getNickname(),
                    "giver_email" => $budgetGiver->getUsername(),
                    "remainingFunds" => $remainingFundsToGiveBack,
                    "original_amount" => $budget->original_amount,
                    "amount" => $budget->amount,
                    "seed" => $budget->seed
                ));
                if ($remainingFunds == 0) {
                    return;
                }
            }
        }
        if ($remainingFunds != 0) {
            error_log("closeOutBudgetSource, remainingFunds not equal to 0, budget id: " . $budget->id);
        }
    }

    private function sendBudgetcloseOutEmail($options) {
        $subject = "Closed - Budget ";
        if ($options["seed"] == 1) {
            $subject = "Closed - Seed Budget ";
        }
        $subject .= $options["budget_id"] . " (For " . $options["reason"] . ")";
        $link = SECURE_SERVER_URL . "team?showUser=" . $options["receiver_id"] . "&tab=tabBudgetHistory";
        $body = '<p>Hello ' . $options["receiver_nickname"] . '</p>';
        $body .= '<p>Your budget has been closed out:</p>';
        $body .= "<p>Budget " . $options["budget_id"] . " for " . $options["reason"] . "</p>";
        $body .= "<p>Requested Amount : $" . $options["original_amount"] . "</p>";
        $body .= "<p>Allocated Amount : $" . $options["amount"] . "</p>";
        if ($options["remainingFunds"] > 0) {
            $body .= "<p>Congrats! You had a budget surplus of $" . $options["remainingFunds"] . "</p>";
        } else if ($options["remainingFunds"] == 0) {
            $body .= "<p>Good job! Your budget was right on target!</p>";
        } else {
            $body .= "<p>Your budget balance was over by $" . $options["remainingFunds"] . "</p>";
        }
        $body .= '<p>Click <a href="' . $link . '">here</a> to see this budget.</p>';
        $body .= '<p>- Worklist.net</p>';

        $plain = 'Hello ' . $options["receiver_nickname"] . '\n\n';
        $plain .= 'Your budget has been closed out:\n\n';
        $plain .= "Budget " . $options["budget_id"] . " for " . $options["reason"] . "\n\n";
        $plain .= "Requested Amount : $" . $options["original_amount"] . "\n\n";
        $plain .= "Allocated Amount : $" . $options["amount"] . "\n\n";
        if ($options["remainingFunds"] > 0) {
            $plain .= "Congrats! You had a budget surplus of $" . $options["remainingFunds"] . "\n\n";
        } else if ($options["remainingFunds"] == 0) {
            $plain .= "Good job! Your budget was right on target!\n\n";
        } else {
            $plain .= "Your budget balance was over by $" . $options["remainingFunds"] . "\n\n";
        }
        $plain .= 'Click ' . $link . ' to see this budget.\n\n';
        $plain .= '- Worklist.net\n\n';

        if (!send_email($options["receiver_email"], $subject, $body, $plain)) {
            error_log("BudgetInfo: send_email failed on closed out budget");
        }
        if ($options["remainingFunds"] < 0 || $options["seed"] == 1) {
            if (!send_email($options["giver_email"], $subject, $body, $plain)) {
                error_log("BudgetInfo: send_email failed on closed out budget");
            }
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