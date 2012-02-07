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
require_once ("models/Review.php");
include_once("send_email.php");
require_once 'models/Users_Favorite.php';

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

