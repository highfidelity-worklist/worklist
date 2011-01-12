<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

    require_once 'config.php';
    require_once 'class.session_handler.php';
    require_once 'functions.php';
    require_once 'timezones.php';
    require_once 'sandbox-util-class.php';
    require_once 'lib/Agency/Worklist/Filter.php';

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $reqUserId = getSessionUserId();
    $reqUser = new User();
    if ($reqUserId > 0) {
        $reqUser->findUserById($reqUserId);
    } else {
        die("You have to be logged in to access user info!");
    }
    $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
	$is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
    
    $filter = new Agency_Worklist_Filter($_REQUEST);

    if (isset($_POST['save_roles']) && $is_runner) { //only runners can change other user's roles info
        $is_runnerSave = isset($_POST['isrunner']) ? 1 : 0;
        $is_payerSave = isset($_POST['ispayer']) ? 1 : 0;
        $hasW9 = isset($_POST['w9']) ? 1 : 0;
        $user_idSave = intval($_POST['userid']);

        $saveUser = new User();
        $saveUser->findUserById($user_idSave);
        $saveUser->setHas_w9approval($hasW9);
        $saveUser->setIs_runner($is_runnerSave);
        $saveUser->setIs_payer($is_payerSave);
        $saveUser->save();
    }
    if (isset($_POST['save_salary']) && $is_payer) { //only payers can change other user's roles info
		$annual_salarySave = mysql_real_escape_string($_POST['annual_salary']);
        $user_idSaveSalary = intval($_POST['userid']);
        $manager_id = intval($_POST['manager']);
        $saveUserSalary = new User();
        $saveUserSalary->findUserById($user_idSaveSalary);
		$saveUserSalary->setAnnual_salary($annual_salarySave);
        $saveUserSalary->setManager($manager_id);
        $saveUserSalary->save();
        
        $manager = new User();
        $manager->findUserById($manager_id);
        
        // Send journal notification
        sendJournalNotification("Salary and manager for ".$saveUserSalary->getNickname() . " new manager is ".$manager->getNickname());
    }
    if (isset($_POST['save_manager']) && $is_runner) {
        $user_id = intval($_POST['userid']);
        $manager_id = intval($_POST['manager']);
        $user = new User();
        $user->findUserById($user_id);
        $user->setManager($manager_id);
        $user->save();
        
        $manager = new User();
        $manager->findUserById($manager_id);
        
        // Send journal notification
        sendJournalNotification("Manager changed for ".$user->getNickname() . " to ".$manager->getNickname());
    }

    if (isset($_REQUEST['id'])) {
        $userId = (int)$_REQUEST['id'];
    } else {
        die("No id provided");
    }

    if (isset($_POST['give_budget']) && $_SESSION['userid'] == $reqUser->getId()) {
    }

    $user = new User();
    $user->findUserById($userId);
	$Annual_Salary = "";
	if($user->getAnnual_salary() >0){
		$Annual_Salary = $user->getAnnual_salary();
	}
    $userStats = new UserStats($userId);

    $manager = $user->getManager();

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
        <meta name="copyright" content="Copyright (c) 2009-2010, LoveMachine Inc.  All Rights Reserved. http://www.lovemachineinc.com" />
        <link type="text/css" href="css/CMRstyles.css" rel="stylesheet" />
        <link type="text/css" href="css/worklist.css" rel="stylesheet" />
        <link type="text/css" href="css/userinfo.css" rel="stylesheet" />
        <link type="text/css" href="css/userNotes.css" rel="stylesheet" />

        <link media="all" type="text/css" href="css/jquery-ui.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="css/smoothness/lm.ui.css"/>
        <script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.8.4.min.js"></script>

        <script type="text/javascript" src="js/userstats.js"></script>
        <script type="text/javascript" src="js/userNotes.js"></script>
        <title>User info</title>
    </head>
<body>
<?php include('userinfo.inc'); ?>
<!-- Popup for ping task  -->
<?php require_once('dialogs/popup-pingtask.inc') ?>

<script type="text/javascript">

  var user_id = <?php echo $userId; ?>;
  var available = 0;
  var rewarded = 0;
//  var showTabs = <?php echo $is_runner; ?>;
  stats.setUserId(user_id);

  $(document).ready(function(){

    userNotes.init();
    
    $('#select_manager').val('<?php echo $manager; ?>');

//    if(showTabs){
        $("#tabs").tabs({
            cache : true,
            ajaxOptions: {
                cache : true,
                success: function() {
                },                
				error: function( xhr, status, index, anchor ) {
					$( anchor.hash ).html(
						"Couldn't load this tab." );
				}
			}
        });
        $(".tabs-bottom .ui-tabs-nav, .tabs-bottom .ui-tabs-nav > *")
        .removeClass("ui-corner-all ui-corner-top")
        .addClass("ui-corner-bottom");
//    } 

    $('#popup-pingtask').dialog({ autoOpen: false, width: 400, show: 'fade', hide: 'fade'});

    $('#send-ping-btn').click(function()    {
        var msg = $('#ping-msg').val();
        var mail = 0;

        if( $('#send-mail:checked').val() ) mail = 1;
        
        $.ajax({
            type: "POST",
            url: 'pingtask.php',
            data: 'userid=' + user_id + '&msg=' + msg + '&mail=' + mail,
            dataType: 'json',
            success: function() {}
        });
        $('#popup-pingtask').dialog('close');
        return false;
        
    });

    $('#nickname-ping').click(function() {
        $('#popup-pingtask').dialog('option', 'title', 'Ping user');
        $('#popup-pingtask form h5').html('Ping message:');
        $('#popup-pingtask').dialog('open');
        return false;
    });

	$('#changeUserStatus').change(function() {
		var change = $.ajax({
			type: 'post',
			url: 'jsonserver.php',
			data: {
				status: $(this).val(),
				userid: <?php echo $userId; ?>,
				action: 'changeUserStatus'
			},
			dataType: 'json',
			success: function() {}
		});
	});

  $('#give-budget').dialog({ autoOpen: false, show: 'fade', hide: 'fade'});
  $('#give').click(function(){
	$('#give-budget form input[type="text"]').val('');
	$('#give-budget').dialog('open');
	return false;
  });
  $('#give-budget form input[type="submit"]').click(function(){
        $('#give-budget').dialog('close');

	    var toReward = parseInt(rewarded) + parseInt($('#toreward').val());
            $.ajax({
                url: 'update-budget.php',
                data: 'receiver_id=' + $('#budget-receiver').val() + '&reason=' + $('#budget-reason').val() + '&amount=' + $('#budget-amount').val(),
                dataType: 'json',
                type: "POST",
                cache: false,
                success: function(json) {
                    $('#info-budget').text(json);
                }
            });
	return false;
  });


  $('#quick-reward').dialog({ autoOpen: false, show: 'fade', hide: 'fade'});

  $('a#reward-link').click(function(){
	$('#quick-reward form input[type="text"]').val('');
        //Wire off rewarder functions for now - GJ 5/24
       return false;

	$.getJSON('get-rewarder-user.php', {'id': user_id}, function(json){

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
        //Wire off rewarder functions for now - GJ 5/24
       return false;
  
	    var toReward = parseInt($('#toreward').val());

        $.ajax({
            url: 'reward-user.php',
            data: 'id=' + user_id + '&points=' + toReward,
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
		id: user_id,
		unixusername: $('#unixusername').val(),
		projects: $('#projects').val()
	},
	success: function(json) {

		if(json.error) {
			alert("Sandbox Creation failed:"+json.error);
		} else {
			alert("Sandbox created successfully");
			$('#popup-user-info').dialog('close');
		}
	}
	});

	return false;
  });

  });
  
  $('#salary').submit(function(e) {
      // Get the specified salary
      var salary = $('#annual_salary').val();
      
      // Get the manager
      var manager = $('#select_manager :selected').text();
      
      if (salary === '' || salary < 0) {
          return true;
      } else {
          if (manager !== 'None') {
              return true;
          } else {
              // Show an alert window
              alert('Users with salary must have a manager.');
              return false;
          }
      }
  });

    $("#loading").ajaxStart(function(){                                                                                                                             
	       $(this).show();                                                                                                                                      
    });                                                                                                                                                             
    $("#loading").ajaxStop(function(){                                                                                                                              
	       $(this).hide();                                                                                                                                      
    });

</script>
</body>
</html>
