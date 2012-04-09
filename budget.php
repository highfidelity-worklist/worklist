<?php
/**
 * Coffee And Power
 * Copyright (c) 2012 LoveMachine, LLc.
 * All rights reserved.
 */
require_once ("config.php");
require_once 'class.session_handler.php';
require_once 'functions.php';
require_once ("models/DataObject.php");
require_once ("models/Budget.php");
include_once("send_email.php");

$budgetInfo = new BudgetInfo();
$budgetInfo->validateRequest(array('action'));

$action = $_REQUEST['action'];
$budgetInfo->$action(); 

class BudgetInfo {
    
    public function __construct() {
    }
    
    /**
     * Get the budget view
     */
    public function getView() {
        $reqUserId = getSessionUserId();
        $user = new User();
        if ($reqUserId > 0) {
            $user->findUserById($reqUserId);
        } else {
            echo "You have to be logged in to access user info!";
        }
        include("dialogs/popup-give-budget.inc");
        exit(0);
    }
    /**
     * Get the budget update view
     */
    public function getUpdateView() {
        $reqUserId = getSessionUserId();
        $user = new User();
        if ($reqUserId > 0) {
            $user->findUserById($reqUserId);
        } else {
            echo "You have to be logged in to access user info!";
        }
        $this->validateRequest(array('budgetId'));
        $budget_id = $_REQUEST['budgetId'];
        
        $budget = new Budget();
        if ($budget->loadById($budget_id)) {
            $sourceBudgetReason = "";
            if ($budget->seed != 1 && $budget->source_budget_id > 0) {
                $budgetSeed = new Budget();
                if ($budgetSeed->loadById($budget->source_budget_id)) {
                    $sourceBudgetReason = $budgetSeed->reason;
                }
            }
            $budgetClosed = !$budget->active;
            $allocated = $budget->getAllocatedFunds();
            $submitted = $budget->getSubmittedFunds();
            $paid = $budget->getPaidFunds();
            $transfered = $budget->getTransferedFunds();
            //$transfered = 0;
            $remaining = $budget->amount - $allocated - $submitted - $paid - $transfered;
            ob_start();
            include("dialogs/popup-update-budget.inc");
            $html = ob_get_contents();
            ob_end_clean();
            $this->respond(true, 'Returning data', array(
                'html' => $html
            ));
        } else {
            $this->respond(true, 'Invalid budget id');
        }
    }
    /**
     * Update the budget Reason and Note
     */
    public function updateBudget() {
        $reqUserId = getSessionUserId();
        $user = new User();
        if ($reqUserId > 0) {
            $user->findUserById($reqUserId);
        } else {
            echo "You have to be logged in to access user info!";
        }
        $this->validateRequest(array('budgetId', 'budgetReason', 'budgetNote'));
        $budget_id = $_REQUEST['budgetId'];
        
        $budget = new Budget();
        if ($budget->loadById($budget_id)) {
            if ($reqUserId == $budget->receiver_id ||
                $budget->giver_id == $reqUserId) {         
                $budget->notes = $_REQUEST['budgetNote'];
                $budget->reason = $_REQUEST['budgetReason'];
                if ($budget->save('id')) {
                    $this->respond(true, 'Data saved');
                } else {
                    $this->respond(false, 'Error in update budget.');
                }
            } else {
                $this->respond(false, 'You aren\'t authorized to update this budget!');
            }
        } else {
            $this->respond(false, 'Invalid budget id');
        }
    }
    /**
    Return the sum of fees that are not already paid for all the workitems linked to a specific budget
    **/
    public function getSumOfFeeNotPaidByBudget($budget_id) {
        $query = "SELECT SUM(`amount`) FROM `" . FEES . 
            "` WHERE paid = 0 AND amount > 0  AND `" . FEES . 
            "`.`withdrawn` != 1 AND ((worklist_id = 0 AND budget_id = " . $budget_id . ") OR worklist_id IN (SELECT id FROM " . 
                WORKLIST . " WHERE budget_id = " . $budget_id . "))";
        $result_query = mysql_query($query);
        $row = $result_query ? mysql_fetch_row($result_query) : null;
        return !empty($row) ? $row[0] : null;
    }
    /**
     * Close the budget 
     */
     
    public function closeOutBudget() {
        $reqUserId = getSessionUserId();
        $user = new User();
        if ($reqUserId > 0) {
            $user->findUserById($reqUserId);
        } else {
            echo "You have to be logged in to access user info!";
        }
        $this->validateRequest(array('budgetId'));
        $budget_id = $_REQUEST['budgetId'];
        
        $budget = new Budget();
        if ($budget->loadById($budget_id)) {
            if ($budget->active != 1) {
                $this->respond(false, 'This budget is already closed.');
                return;
            }
            if ($budget->seed == 1) {
                $this->respond(false, 'This budget is a seed budget and can\'t be closed for the moment.');
                return;
            }
            if ($reqUserId == $budget->receiver_id ||
                $budget->giver_id == $reqUserId) {  
                $budgetGiver = new User();
                if (!$budgetGiver->findUserById($budget->giver_id)) {
                    $this->respond(false, 'Invalid giver id.');
                    return;
                }
                $budgetReceiver = new User();
                if (!$budgetReceiver->findUserById($budget->receiver_id)) {
                    $this->respond(false, 'Invalid receiver id.');
                    return;
                }
                // all the child budgets are closed ?
                $childrenNotClosed = $budget->getChildrenNotClosed($budget->id);
                if ($childrenNotClosed == 0) {
                    // all the budgeted jobs are paid ?
                    
                    $feeAmountNotPaid = $this->getSumOfFeeNotPaidByBudget($budget->id);
                    if ($feeAmountNotPaid === null) {
                        $remainingFunds = $budget->getRemainingFunds();
                        if ($remainingFunds >= 0) {
                            $budget->original_amount = $budget->amount;
                            $budget->amount = $budget->original_amount - $remainingFunds;
                            $budget->active = 0;
                            $budgetReceiver->updateBudget(- $remainingFunds, $budget->id, false);
                            $budgetGiver->updateBudget($remainingFunds, $budget->source_budget_id);
                            if ($budget->save('id')) {
                                $this->sendBudgetcloseOutEmail(array(
                                    "budget_id" => $budget->id,
                                    "reason" => $budget->reason,
                                    "giver_id" => $budget->giver_id,
                                    "receiver_id" => $budget->receiver_id,
                                    "receiver_nickname" => $budgetReceiver->getNickname(),
                                    "receiver_email" => $budgetReceiver->getUsername(),
                                    "giver_nickname" => $budgetGiver->getNickname(),
                                    "giver_email" => $budgetGiver->getUsername(),
                                    "remainingFunds" => $remainingFunds,
                                    "original_amount" => $budget->original_amount,
                                    "amount" => $budget->amount
                                ));
                                $this->respond(true, 'Budget closed');
                            } else {
                                $this->respond(false, 'Error in update budget.');
                            }
                        } else {
                            if ($reqUserId == $budget->receiver_id) {
                                $this->respond(false, 'Your budget is spent. Please contact the grantor (' . 
                                    $budgetGiver->getNickname() . ') for additional funds.');
                            } else {
                                $budget->original_amount = $budget->amount;
                                $budget->amount = $budget->original_amount - $remainingFunds;
                                $budget->active = 0;
                                $budgetReceiver->updateBudget(- $remainingFunds, $budget->id, false);
                                $budgetGiver->updateBudget($remainingFunds, $budget->source_budget_id);
                                if ($budget->save('id')) {  
                                    $this->sendBudgetcloseOutEmail(array(
                                        "budget_id" => $budget->id,
                                        "reason" => $budget->reason,
                                        "giver_id" => $budget->giver_id,
                                        "receiver_id" => $budget->receiver_id,
                                        "receiver_nickname" => $budgetReceiver->getNickname(),
                                        "receiver_email" => $budgetReceiver->getUsername(),
                                        "giver_nickname" => $budgetGiver->getNickname(),
                                        "giver_email" => $budgetGiver->getUsername(),
                                        "remainingFunds" => $remainingFunds,
                                        "original_amount" => $budget->original_amount,
                                        "amount" => $budget->amount
                                    ));
                                    $this->respond(true, 'Budget closed');
                                } else {
                                    $this->respond(false, 'Error in update budget.');
                                }
                            }
                        }
                    } else {
                        $this->respond(false, 'Some fees are not paid.');
                    }
                } else {
                    $this->respond(false, "This budget has one or more sub-allocated budget that are still active." .
                        "You may not close out this budget until the other budgets are closed out.");
                }
            } else {
                $this->respond(false, 'You aren\'t authorized to update this budget!');
            }
        } else {
            $this->respond(false, 'Invalid budget id');
        }
    }
     
    public function sendBudgetcloseOutEmail($options) {
    
        $subject = "Closed - Budget " . $options["budget_id"] . " (For " . $options["reason"] . ")";
        $link = SECURE_SERVER_URL . "team.php?showUser=" . $options["receiver_id"] . "&tab=tabBudgetHistory";
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
            error_log("budget.php: send_email failed on closed out budget");
        }
        if ($options["remainingFunds"] < 0) {
            if (!send_email($options["giver_email"], $subject, $body, $plain)) { 
                error_log("budget.php: send_email failed on closed out budget");
            }
        }
    }
    
    /**
     * Check that all the @fields were sent on the request
     * returns true/false.
     * 
     * @fields has to be an array of strings
     */
    public function validateRequest($fields, $return=false) {
        // If @fields ain't an array return false and exit
        if (!is_array($fields)) {
            return false;
        }
        
        foreach ($fields as $field) {
            if (!isset($_REQUEST[$field])) {
                // If we specified that the function must return do so
                if ($return) {
                    return false;
                } else { // If not, send the default reponse and exit
                    $this->respond(false, "Not all params supplied.");
                }
            }
        }
    }

    
    /**
     * Sends a json encoded response back to the caller
     * with @succeeded and @message
     */
    public function respond($succeeded, $message, $params=null) {
        $response = array('succeeded' => $succeeded,
                          'message' => $message,
                          'params' => $params);
        echo json_encode($response);
        exit(0);
    }
}

