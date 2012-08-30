<?php

    include('config.php');
    include("class.session_handler.php");
    include("helper/checkJournal_session.php");
    require_once('functions.php');

    // check for referer
    if(!checkReferer()){
        die(json_encode(array('error' => 'Wrong referer')));
    }
    // check if we access this page from the script
    if($_POST['csrf_token'] != $_SESSION['csrf_token']){
        die(json_encode(array('error' => 'Invalid token')));
    }

	mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD) or die(mysql_error());;
	mysql_select_db(DB_NAME) or die(mysql_error());

	switch($_POST['what']){

	case 'guestlist':

	  if(isset($_SESSION['userid'])){
		// get 5 latest messages written by guests with distinct ips
		$guestEntries = array();
		$sql = "SELECT DISTINCT * FROM "
			. "(SELECT * FROM `".ENTRIES."` WHERE author = 'Guest' ORDER BY `id` DESC ) AS `t1` "
			. "GROUP BY `ip` ORDER BY `id` DESC LIMIT 5";
		$res = mysql_query($sql);
		while($row = mysql_fetch_assoc($res)){

			if(Penalty::getSimpleStatus(0, $row['ip']) == Penalty::NOT_PENALIZED){
				$guestEntries[] = $row;
			}
		}
		echo json_encode($guestEntries);
	  }
	break;

	// check if user can penalize another user
	case 'checkpenalize':

	  if(isset($_SESSION['userid'])){
	      $status = '';
	      switch(Penalty::checkPenalize($_POST['penalizer_id'], $_POST['penalated_id'], $_POST['penalated_ip'])){

	      case Penalty::CANT_PENALIZE:
		$status = 'CANT_PENALIZE';
	      break;

	      case Penalty::ALREADY_PENALIZED:
		$status = 'ALREADY_PENALIZED';
	      break;

	      case Penalty::ALREADY_SUSPENDED:
		$status = 'ALREADY_SUSPENDED';
	      break;

	      case Penalty::CAN_PENALIZE:
		$status = 'CAN_PENALIZE';
	      break;

	      case Penalty::IN_BOX:
		$status = 'IN_BOX';
	      break;

	      }
	      echo json_encode(array('status' => $status));
	  }
	break;

	case 'penalize':

	  if(isset($_SESSION['userid'])){
	    	Penalty::penalizeUser(array('id' => $_POST['penalated_id'],
				    'ip' => $_POST['penalated_ip'],
				    'reason' => $_POST['reason'],
				    'from' => $_POST['penalizer_id']));

	  }
	break;

	case 'getreasons':
	      $penaltyData = $_POST['penalated_id'] == 0 ? Penalty::getUserPenalties($_POST['penalated_id'], $_POST['penalated_ip']) : Penalty::getUserPenalties($_POST['penalated_id']);
	      echo json_encode($penaltyData['data']);

	break;
	}

?> 
