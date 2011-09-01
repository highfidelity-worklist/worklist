<?php
//  Copyright (c) 2011, LoveMachine Inc.
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
    require_once 'models/Users_Favorite.php';

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;

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

    // admin posting data
    if (isset($_POST) && !empty($_POST) && ($is_runner || $is_payer) && !$action) {

        $user_id = (int) $_POST['user_id'];

        if ($_POST['save-salary']) {
            $field = 'salary';
            $value = mysql_real_escape_string($_POST['value']);
        } elseif ($_POST['field'] == 'w9status') {
            $field = 'w9status';
            $value = mysql_real_escape_string($_POST['value']);
        } else {
            $field = $_POST['field'];
            $value = (int) $_POST['value'];
        }

        $updateUser = new User();

        if ($updateUser->findUserById($user_id)) {
            switch ($field) {
                case 'salary':
                    $updateUser->setAnnual_salary($value);
                    sendJournalNotification("A new salary has been set for " . $updateUser->getNickname());
                    break;

                case 'ispayer':
                    $updateUser->setIs_payer($value);
                    break;

                case 'isrunner':
                    $updateUser->setIs_runner($value);
                    break;

                case 'w9status':
                    if ($value) {
                        switch ($value) {
                            case 'approved':
                                if (! sendTemplateEmail($updateUser->getUsername(), 'w9-approved')) { 
                                    error_log("userinfo.php: send_email failed on w9 approved notification");
                                }

                                break;
                                
                            case 'rejected':
                                $data = array();
                                $data['reason'] = strip_tags($_POST['reason']);
                                
                                if (! sendTemplateEmail($updateUser->getUsername(), 'w9-rejected', $data)) { 
                                    error_log("userinfo.php: send_email failed on w9 rejected notification");
                                }
                                break;
                            
                            default:
                                break;
                        }
                    }
                    $updateUser->setW9_status($value);
                    break;

                case 'ispaypalverified':
                    $updateUser->setPaypal_verified($value);
                    if ($value) {
                        $updateUser->setHas_w2(false);
                    }
                    break;

                case 'isw2employee':
                    $updateUser->setHas_w2($value);
                    if ($value) {
                        $updateUser->setPaypal_verified(false);
                        $updateUser->setw9_status('not-applicable');
                    }
                    break;

                case 'manager':
                    $updateUser->setManager($value);
                    if ($value) {
                        $manager = new User();
                        $manager->findUserById($value);
                        // Send journal notification
                        sendJournalNotification("The manager for " . $updateUser->getNickname() . " is now set to " . $manager->getNickname());
                    } else {
                        sendJournalNotification("The manager for " . $updateUser->getNickname() . " has been removed");
                    }
                    break;

                case 'referrer':
                    $updateUser->setReferred_by($value);
                    if ($value) {
                        $referrer = new User();
                        $referrer->findUserById($value);

                        // Send journal notification
                        sendJournalNotification("The referrer for " . $updateUser->getNickname() . " is now set to " . $referrer->getNickname());
                    } else {
                        sendJournalNotification("The referrer for " . $updateUser->getNickname() . " has been removed");
                    }
                    break;

                case 'isactive':
                    $updateUser->setIs_active($value);
                    break;

                default:
                    break;
            }

            $updateUser->save();
            $response = array(
                'succeeded' => true,
                'message' => 'User details updated successfully'
            );
            echo json_encode($response);
            exit(0);

        } else {
            die(json_encode(array(
                'succeeded' => false,
                'message' => 'Error: Could not determine the user_id'
            )));
        }
    }

    if (isset($_REQUEST['id'])) {
        $userId = (int)$_REQUEST['id'];
    } else {
        die("No id provided");
    }

    $user = new User();
    $user->findUserById($userId);
    $Annual_Salary = "";
    if($user->getAnnual_salary() >0){
        $Annual_Salary = $user->getAnnual_salary();
    }
    $userStats = new UserStats($userId);
    $manager = $user->getManager();
    $referred_by = $user->getReferred_by();
    $hasRunJobs = $userStats->getRunJobsCount();
    $hasBeenMechanic = $userStats->getMechanicJobCount();

    if ($action =='create-sandbox') {
        $result = array();
        try {
            if (!$is_runner) {
                throw new Exception("Access Denied");
            }
            $args = array('unixusername', 'projects');
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
        } catch(Exception $e) {
            $result["error"] = $e->getMessage();
        }
        echo json_encode($result);
        die();
    }

    $reviewee_id = (int) $userId;
    $review = new Review();
    $reviewsList = $review->getReviews($reviewee_id,$reqUserId);
    $projects = getProjectList();
    $user_projects = $user->getProjects_checkedout();
    if (count($user_projects) > 0) {
        $has_sandbox = true;
    } else {
        $has_sandbox = false;
    }
    $users_favorite = new Users_Favorite();
    $favorite_enabled = 1;
    $favorite = $users_favorite->getMyFavoriteForUser($reqUserId, $userId);
    if (isset($favorite['favorite'])) {
        $favorite_enabled = $favorite['favorite'];
    }
    $favorite_count = $users_favorite->getUserFavoriteCount($userId);
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
        <link type="text/css" href="css/favorites.css" rel="stylesheet" />
        <link href="css/LVstyles.css" rel="stylesheet" type="text/css">
        <link media="all" type="text/css" href="css/jquery-ui.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="css/smoothness/lm.ui.css"/>

        <script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
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
            var admin = <?php echo $is_payer; ?>;
            var userInfo = {
                manager: <?php echo $manager; ?>,
                referred_by: <?php echo $referred_by; ?>,
                user_id: <?php echo $userId; ?>,
                nickName: '<?php echo $user->getNickName(); ?>'
            };
        </script>
        <script type="text/javascript" src="js/userstats.js"></script>
        <script type="text/javascript" src="js/userNotes.js"></script>
        <script type="text/javascript" src="js/review.js"></script>
        <script type="text/javascript" src="js/favorites.js"></script>
        <script type="text/javascript" src="js/userinfo.js"></script>
        <title>User info</title>
    </head>
<body>
<?php include('userinfo.inc'); ?>
<!-- Popup for ping task  -->
<?php require_once('dialogs/popup-pingtask.inc') ?>
</body>
</html>
