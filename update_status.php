<?php
//  vim:ts=4:et

include_once("config.php");
include_once("functions.php");
include_once("class.session_handler.php");

// If user isn't logged in then return and do nothing
//if( !isset($_SESSION['userid']) ) return;

//Detect if we are being included as a library or invoked to update
if(realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
    /*
     * This is the controller section of to determine what action to perform.
     * Actions:
     * - Update: This will update or add a status to the database
     * - Get: This will retrieve a status from the database and return it.
     */
    $action = "";
    
    if( isset($_REQUEST["action"]) ) $action = $_REQUEST["action"];
    switch($action){
        case "update":
    
            // check for referer
            if(!checkReferer()){
                die(json_encode(array('error' => 'Wrong referer')));
            }
            // check if user updates his status from journal
            if( $_REQUEST['csrf_token'] != $_SESSION['csrf_token'] and $_REQUEST['csrf_token'] != 'worklist'){
                die(json_encode(array('error' => 'Invalid token')));
            }
            update_status($_REQUEST["status"]);
            break;
        default:
            echo(json_encode(array('status' => true, 'error' => 'no valid action selected' )));
            return;
    } 
}


/* Updates a users status, saves it to the database and sends a message to the journal
 * @param status is the text status submitted to be udpated
 */
function update_status($status = ""){    
    if(isset($_SESSION['userid'])){
        if($status != ""){
            $journal_message =  $_SESSION['nickname'] . ' is ' . $status;
    
        // Insert new status to the database
            $insert = "INSERT INTO ".USER_STATUS."(id, status, timeplaced) VALUES(" . $_SESSION['userid'] . ", '" .  mysql_real_escape_string($status) . "', NOW())";    
            if (!mysql_query($insert)) error_log("update_status.mysq: ".mysql_error());
    
        //Send message to the Journal
            $journal_message = sendJournalNotification($journal_message);
            if($journal_message != 'ok') {
                echo(json_encode(array('status' => false, 'error' => $journal_message)));
                return;
            }
        }
        echo(json_encode(array('status' => true )));
    } else {
     echo(json_encode(array('status' => false)));
    }
    return;
}

?>
