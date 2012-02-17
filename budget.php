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
        if ($budget->loadById($budget_id)){
            $sourceBudgetReason = "";
            if ($budget->seed != 1 && $budget->source_budget_id > 0) {
                $budgetSeed = new Budget();
                if ($budgetSeed->loadById($budget->source_budget_id)){
                    $sourceBudgetReason = $budgetSeed->reason;
                }
            }
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
     * Get the budget update view
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
        if ($budget->loadById($budget_id)){
            $budget->notes = $_REQUEST['budgetNote'];
            $budget->reason = $_REQUEST['budgetReason'];
            if ($budget->save('id')) {
                $this->respond(true, 'Data saved');
            } else {
                $this->respond(true, 'Error in update budget.');
            }
        } else {
            $this->respond(true, 'Invalid budget id');
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

