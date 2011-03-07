<?php
/**
 * Coffee And Power
 * Copyright (c) 2010 LoveMachine, LLc.
 * All rights reserved.
 */
require_once ("config.php");
require_once 'class.session_handler.php';
require_once 'functions.php';
require_once ("models/DataObject.php");
require_once ("models/Review.php");

$userReview = new UserReview();
$userReview->validateRequest(array('action'));

$action = $_REQUEST['action'];
$userReview->$action(); 

class UserReview {
    
    public function __construct() {
    }
    
    /**
     * Get the review
     */
    public function getView() {
        $ret = $this->validateRequest(array('reviewee_id'));
        $reviewer_id = getSessionUserId();
        $reqUser = new User();
        if ($reviewer_id > 0) {
            $reqUser->findUserById($reviewer_id);
        } else {
            echo "You have to be logged in to access user info!";
        }

        $reviewee_id = (int) $_REQUEST['reviewee_id'];
        $review = new Review();
        if ($review->loadById($reviewer_id,$reviewee_id) ){
            echo "<textarea class='userReview'>" . $review->review . "</textarea>";
            exit(0);
        }else{
            echo "<textarea class='userReview'></textarea>";
            exit(0);
        }			
    }


    /**
     * Verify that the code entered by the user is the same in the database
     */
    public function saveReview() {
        $ret = $this->validateRequest(array('userReview','reviewee_id'));

        $reviewer_id = getSessionUserId();
        $reqUser = new User();
        if ($reviewer_id > 0) {
            $reqUser->findUserById($reviewer_id);
        } else {
            $this->respond(false, "You have to be logged in to access user info!",''); 
        }
        $userReview = $_REQUEST['userReview'];
        $reviewee_id = (int) $_REQUEST['reviewee_id'];
        if ($reviewer_id == $reviewee_id) {
            $this->respond(true, "Self review is not allowed.",'');
        }
        $review = new Review();
        if ($review->loadById($reviewer_id,$reviewee_id) ){
            if ($userReview == "") {
                if ($review->removeRow(" reviewer_id = ".$reviewer_id . " AND reviewee_id = ".$reviewee_id)) {
                    $this->respond(true, "Review deleted.",'');
                } else {
                    $this->respond(false, "Cannot delete review! Please retry later.",''); 
                }  
            } else {
                $review->review = $userReview;
                if ($review->save('reviewer_id','reviewee_id')) {
                    $this->respond(true, "Review updated.",'');
                } else {
                    $this->respond(false, "Cannot update review! Please retry later.",''); 
                }  
            }
        } else {
            if ($userReview != "") {
                $values = array(
                    'reviewer_id' => $reviewer_id,
                    'reviewee_id' => $reviewee_id,
                    'review' => $userReview
                );
                        
                if ($review->insertNew($values)) {  
                    $myReview = $review->getReviews($reviewee_id,$reviewer_id,' AND r.reviewer_id=' . $reviewer_id);      
                    if (count($myReview) == 0) {
                        $review->removeRow(" reviewer_id = ".$reviewer_id . " AND reviewee_id = ".$reviewee_id);
                        $this->respond(true, "Review with no paid fee is not allowed.",'');
                    }
                    $this->respond(true, "Review saved.",array('myReview' => $myReview));
                } else {
                    $this->respond(false, "Cannot create new review! Please retry later.",''); 
                }
            } else {
                $this->respond(true, "New empty review is not saved.",'');
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

