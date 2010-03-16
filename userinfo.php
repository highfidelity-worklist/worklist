<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

    require_once 'config.php';
    require_once 'class.session_handler.php';
    require_once 'functions.php';
    require_once 'timezones.php';
    require_once 'sandbox-util-class.php';

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    if( isset( $_POST['save_roles'] ) && !empty( $_SESSION['is_runner'] ) ){ //only runners can change other user's roles info
	$is_runnerSave = isset($_POST['isrunner']) ? 1 : 0;
	$is_payerSave = isset($_POST['ispayer']) ? 1 : 0;
	$user_idSave = intval($_POST['userid']);
	mysql_unbuffered_query("UPDATE `users` SET `is_runner`='$is_runnerSave', `is_payer`='$is_payerSave' WHERE `id` =".$user_idSave);
    }

    $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;

    if(isset($_REQUEST['id'])){
	
	$userId = (int)$_REQUEST['id'];
    }else{
	
	die("No id provided");
    }

    // $reqUser - id of user requesting the page
    $reqUserId = getSessionUserId();
    $reqUser = new User();
    if ($reqUserId > 0) {
	$reqUser->findUserById($reqUserId);
    } else {
	
	die("You have to be logged in to access user info!");
    }

    $user = new User();
    $user->findUserById($userId);


    if($action =='create-sandbox') {
          $result = array();
          try {
            if(!$is_runner) {
                throw new Exception("Access Denied");
            }
            $args = array('unixusername','projects');
            foreach ($args as $arg) {
              $$arg = mysql_real_escape_string($_REQUEST[$arg]);
            }

            $projectList = explode(",",str_replace(" ","",$projects));

            // Create sandbox for user
            $sandboxUtil->createSandbox($user -> getUsername(), $user -> getNickname(), $unixusername, $projectList);

            // If sb creation was successful, update users table
	    $user -> setHas_sandbox(1);
	    $user -> setUnixusername($unixusername);
	    $user -> setProjects_checkedout($projects);
	    $user -> save();

          }catch(Exception $e) {
            $result["error"] = $e->getMessage();
          }
          echo json_encode($result);
          die();
    }


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="copyright" content="Copyright (c) 2009, LoveMachine Inc.  All Rights Reserved. http://www.lovemachineinc.com ">
	<link type="text/css" href="css/CMRstyles.css" rel="stylesheet" />
	<link type="text/css" href="css/worklist.css" rel="stylesheet" />
	<link type="text/css" href="css/userinfo.css" rel="stylesheet" />
	<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
</head><?php
    include('userinfo.inc');
?> 
<script type="text/javascript">

  var userId = <?php echo $userId; ?>;
  var available = 0;
  var rewarded = 0;

  $(document).ready(function(){
  
  $('#quick-reward').dialog({ autoOpen: false });

  $('a#reward-link').click(function(){

	$('#quick-reward form input[type="text"]').val('');

	$.getJSON('get-rewarder-user.php', {'id': userId}, function(json){

		rewarded = json.rewarded;
		available = json.available;
		$('#quick-reward #already').text(rewarded);
		$('#quick-reward #available').text(available);

		$('#quick-reward').dialog('open');
	});

	return false;
  });

  $('#quick-reward form input[type="submit"]').click(function(){

	$('#quick-reward').dialog('close');
  
	    var toReward = parseInt(rewarded) + parseInt($('#toreward').val());

            $.ajax({
                url: 'update-rewarder-user.php',
                data: 'id=' + userId + '&points=' + toReward,
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {

                }
            });
	return false;
  });

  $('#create_sandbox').click(function(){

	$.ajax({
		type: "POST",
		url: 'userinfo.php',
		dataType: 'json',
		data: {
		action: "create-sandbox",
		id: userId,
		unixusername: $('#unixusername').val(),
		projects: $('#projects').val()
	},
	success: function(json) {

		if(json.error) {
			alert("Sandbox Creation failed:"+json.error);
		} else {
			$('#popup-user-info').dialog('close');
		}
	}
	});

	return false;
  });



  });
</script>