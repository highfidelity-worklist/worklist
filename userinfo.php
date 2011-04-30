<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

    ob_start();

    require_once 'config.php';
    require_once 'class.session_handler.php';
    require_once 'functions.php';
    require_once 'timezones.php';
    require_once 'sandbox-util-class.php';
    require_once 'lib/Agency/Worklist/Filter.php';
    require_once 'models/DataObject.php';
    require_once 'models/Review.php';

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $reqUserId = getSessionUserId();
    $reqUser = new User();
    if ($reqUserId > 0) {
        $reqUser->findUserById($reqUserId);
        $budget = $reqUser->getBudget();
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
        $isPaypalVerified = isset($_POST['paypal_verified']) ? 1 : 0;
        $user_idSave = intval($_POST['userid']);

        $saveUser = new User();
        $saveUser->findUserById($user_idSave);
        $saveUser->setHas_w9approval($hasW9);
        $saveUser->setIs_runner($is_runnerSave);
        $saveUser->setIs_payer($is_payerSave);
        $saveUser->save();
    }
    if (isset($_POST['save_salary']) && $is_payer) { //only payers can change other user's roles info
        // Detect what's been changed
        $salary_changed = intval($_POST['salary_changed']);
        $manager_changed = intval($_POST['manager_changed']);
    
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

        // Send journal notification depending on what's been changed
        if ($salary_changed) {
            sendJournalNotification("A new salary has been set for ".$saveUserSalary->getNickname());
        }
        if ($manager_changed) {
            sendJournalNotification("The manager for ".$saveUserSalary->getNickname() . " is now set to ".$manager->getNickname());
        }
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
        sendJournalNotification("The manager for ".$user->getNickname() . " is now set to ".$manager->getNickname());
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

    if ($action =='create-sandbox') {
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
            $sandboxUtil = new SandBoxUtil;
            $sandboxUtil->createSandbox($user -> getUsername(), $user -> getNickname(), $unixusername, $projectList);

            // If sb creation was successful, update users table
            $user->setHas_sandbox(1);
            $user->setUnixusername($unixusername);
            $user->setProjects_checkedout($projects);
            $user->save();
            // add to project_users table
            foreach ($projectList as $project) {
                $project_id = Project::getIdFromRepo($project);
                $user->checkoutProject($project_id);
            }
        
          }catch(Exception $e) {
            $result["error"] = $e->getMessage();
          }
          echo json_encode($result);
          die();
    }

    $reviewee_id = (int) $userId;
    $review = new Review();
    $reviewsList = $review->getReviews($reviewee_id,$reqUserId);


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
        <link type="text/css" href="css/review.css" rel="stylesheet" />
        <link href="css/LVstyles.css" rel="stylesheet" type="text/css">
        <link media="all" type="text/css" href="css/jquery-ui.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="css/smoothness/lm.ui.css"/>

        <script type="text/javascript" src="js/jquery-1.5.2.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.8.12.min.js"></script>
        <script type="text/javascript" src="js/jquery.livevalidation.js"></script>
        <script type="text/javascript" src="js/jquery.blockUI.js"></script>
        <script type="text/javascript" src="js/jquery.autogrow.js"></script>
        <script type="text/javascript">
        // This global variable user_id should not be used anymore, replace it by userInfo.user_id .
        // All the variables should be included in the object userInfo
        // To have a smooth migration, the variable is kept for the moment.
            var user_id = <?php echo $userId; ?>;
            var current_id = <?php echo $reqUserId; ?>;
                                                
            var userInfo = {
                manager: <?php echo $manager; ?>,
                user_id: <?php echo $userId; ?>,
                nickName: '<?php echo $user->getNickName(); ?>'
            };
        </script>
        <script type="text/javascript" src="js/userstats.js"></script>
        <script type="text/javascript" src="js/userNotes.js"></script>
        <script type="text/javascript" src="js/review.js"></script>
        <script type="text/javascript" src="js/userinfo.js"></script>
        <title>User info</title>
    </head>
<body>
<?php include('userinfo.inc'); ?>
<!-- Popup for ping task  -->
<?php require_once('dialogs/popup-pingtask.inc') ?>

</body>
</html>
