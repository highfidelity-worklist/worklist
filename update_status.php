<?php
include_once("config.php");
include_once("functions.php");
include_once("class.session_handler.php");

// If user isn't logged in then return and do nothing
if (!isset($_SESSION['userid'])) {
	return;	
}

/*
 * This is the controller section of to determine what action to perform.
 * Actions:
 * - Update: This will update or add a status to the database
 * - Get: This will retrieve a status from the database and return it.
 */
$action = "";

if(isset($_REQUEST["action"])){
	$action = $_REQUEST["action"];
}

switch($action){
	case "update":
		update_status($_REQUEST["status"]);
		break;
	case "get":
		return get_status();
		break;
	default:
		return;	
	
} 


/* Updates a users status, saves it to the database and sends a message to the journal
 * @param status is the text status submitted to be udpated
 */
function update_status($status = ""){	
	$journal_message =  $_SESSION['nickname'];
	
	mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME);
	// Insert new status to the database
	$insert = "INSERT INTO user_status(id, status, timeplaced) VALUES(" . $_SESSION['userid'] . ", '" .  mysql_real_escape_string($status) . "', NOW())";
	
	mysql_query($insert);
	
	//Send message to the Journal
	if($status != ""){
		$journal_message .= " is " . $status;
		
		sendJournalNotification($journal_message);
	}
}

/* Get the users latest status update
 * @param as_string if set to true will return just the plain text status message, if false will return json object with status and timeplaced
 * @return an xml structure containing the status and timeplaced OR if as_string is true will return the string status only
 */
function get_status($as_string = true){
	if (empty($_SESSION['userid'])) return "";

	// Connect to the database
	$connection = mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME,$connection);	
	
	$select = "SELECT status, timeplaced FROM user_status WHERE id=" . $_SESSION['userid'] . " ORDER BY timeplaced DESC LIMIT 1";
	

	$res = mysql_query($select, $connection);
	
	//If there was nothing returned from the query then generate return
	if(mysql_num_rows($res) > 0){ 
	
		while($row = mysql_fetch_assoc($res)){
		  $status_info[] = $row;
		 
		}
		
		if(!$as_string){
			// Return the array as a json element
			return json_encode($status_info);
		}
		else {
			return $status_info[0]["status"];	
		}
	}
	
	return "";
	
}

?>
