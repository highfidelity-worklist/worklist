<?php
/**
 * Copyright (c) 2014, High Fidelity Inc.
 * All Rights Reserved. 
 *
 * http://highfidelity.io
 */

require_once ("config.php");
require_once ("models/DataObject.php");
require_once ("models/Review.php");
require_once ("models/Users_Favorite.php");
require_once ("models/Budget.php");

Session::check();

if (!defined("ALL_ASSETS"))      define("ALL_ASSETS", "all_assets");

// TODO: add API keys to these function calls
// uploadProfilePicture
// getSystemDrawerJobs
// getTimezone

if(validateAction()) {
    if(!empty($_REQUEST['action'])){
        mysql_connect (DB_SERVER, DB_USER, DB_PASSWORD);
        mysql_select_db (DB_NAME);
        switch($_REQUEST['action']){
            case 'updateuser':
                validateAPIKey();
                updateuser();
                break;
            case 'pushVerifyUser':
                validateAPIKey();
                pushVerifyUser();
                break;
            case 'login':
                validateAPIKey();
                loginUserIntoSession();
                break;
            case 'getTaskPosts':
                getTaskPosts();
                break;
            case 'uploadProfilePicture':
                uploadProfilePicture();
                break;
            case 'updateProjectList':
                validateAPIKey();
                updateProjectList();
                break;
            case 'getSystemDrawerJobs':
                getSystemDrawerJobs();
                break;
            case 'bidNotification':
                validateAPIKey();
                sendBidNotification();
                break;
            case 'processW2Masspay':
                validateAPIKey();
                processW2Masspay();
                break;
            case 'doScanAssets':
                validateAPIKey();
                doScanAssets();
                break;
            case 'version':
                validateAPIKey();
                exec('svnversion > ver');
                break;
            case 'jobsPastDue':
                validateAPIKey();
                sendPastDueNotification();
                break;
            case 'sendContactEmail':
                // @TODO: why do we require an API key for this?
                // I don't get it. The request is sent via JS, so if we included the API key it would
                // then become visible to all who want to see it, leaving the form open for abuse... - lithium
                // validateAPIKey();
                sendContactEmail();
                break;
            case 'getTimezone':
                getTimezone();
                break;
            case 'updateLastSeen':
                updateLastSeen();
                break;
            case 'sendTestNotifications':
                validateAPIKey();
                sendTestNotifications();
                break;
            case 'autoPass':
                validateAPIKey();
                autoPassSuggestedJobs();
                break;
            case 'processPendingReviewsNotifications':
                validateAPIKey();
                processPendingReviewsNotifications();
                break;
            case 'pruneJournalEntries' : 
                validateAPIKey();
                pruneJournalEntries();
                break;
            case 'createRepo':
                createRepo();
                break;
            case 'createSandbox':
                createSandbox();
                break;
            case 'createDatabaseNewProject':
                createDatabaseNewProject();
                break;
            case 'sendNewProjectEmails':
                sendNewProjectEmails();
                break;
            case 'modifyConfigFile':
                modifyConfigFile();
                break;
            case 'addPostCommitHook':
                addPostCommitHook();
                break;
            case 'deployStagingSite':
                deployStagingSite();
                break;
            case 'getFavoriteUsers':
                getFavoriteUsers();
                break;
            case 'getTwilioCountries':
                getTwilioCountries();
                break;
            case 'deployErrorNotification':
                validateAPIKey();
                deployErrorNotification();
                break;
            case 'saveSoundSettings':
                saveSoundSettings();
                break;
            case 'sendNotifications':
                validateAPIKey();
                sendNotifications();
                break;
            case 'checkInactiveProjects':
                validateAPIKey();
                checkInactiveProjects();
                break;
            case 'checkRemovableProjects':
                validateAPIKey();
                checkRemovableProjects();
                break;
            case 'addProject':
                addProject();
                break;
            case 'addWorkitem':
                addWorkitem();
                break;
            case 'setFavorite':
                setFavorite();
                break;
            case 'manageBudget':
                manageBudget();
                break;
            case 'getBidItem':
                getBidItem();
                break;
            case 'getBonusHistory':
                getBonusHistory();
                break;
            case 'getFeeItem':
                getFeeItem();
                break;
            case 'getCodeReviewStatus':
                getCodeReviewStatus();
                break;
            case 'getFeeSums':
                getFeeSums();
                break;
            case 'getJobInformation':
                getJobInformation();
                break;
            case 'getMultipleBidList':
                getMultipleBidList();
                break;
            case 'getProjects':
                getProjects();
                break;
            case 'getReport':
                getReport();
                break;
            case 'getSkills':
                getSkills();
                break;
            case 'getStats':
                $req =  isset($_REQUEST['req'])? $_REQUEST['req'] : 'table';
                $interval =  isset($_REQUEST['req'])? $_REQUEST['req'] : 30;
                echo json_encode(getStats($req, $interval));
                break;
            case 'getUserItem':
                getUserItem();
                break;
            case 'getUserItems':
                getUserItems();
                break;
            case 'getUserList':
                getUserList();
                break;
            case 'getUsersList':
                getUsersList();
                break;
            case 'getUserStats':
                getUserStats();
                break;
            case 'getWorkitem':
                getWorkitem();
                break;
            case 'getWorklist':
                getWorklist();
                break;
            case 'payBonus':
                payBonus();
                break;
            case 'payCheck':
                payCheck();
                break;
            case 'pingTask':
                pingTask();
                break;
            case 'refreshFilter':
                refreshFilter();
                break;
            case 'userReview':
                userReview();
                break;
            case 'workitemSandbox':
                workitemSandbox();
                break;
            case 'testFlight':
                testFlight();
                break;
            case 'updateBudget':
                updateBudget();
                break;
            case 'userNotes':
                userNotes();
                break;
            case 'visitQuery':
                visitQuery();
                break;
            case 'wdFee':
                wdFee();
                break;
            case 'budgetInfo':
                budgetInfo();
                break;
            case 'budgetHistory':
                budgetHistory();
                break;
            case 'timeline':
                timeline();
                break;
            case 'newUserNotification':
                validateAPIKey();
                sendNewUserNotification();
                break;
            default:
                die("Invalid action.");
        }
    }
}

function validateAction() {
    if (validateRequest()) {
        return true;
    } else {
        return false;
    }
}

function validateRequest() {
    if( ! isset($_SERVER['HTTPS'])) {
        error_log("Only HTTPS connection is accepted.");
        die("Only HTTPS connection is accepted.");
    } else if ( ! isset($_REQUEST['action'])) {
        error_log("API not defined");
        die("API not defined");
    } else {
        return true;
    }
}

function validateAPIKey() {
    if( ! isset($_REQUEST["api_key"])) {
        error_log("No api key defined.");
        die("No api key defined.");
    } else if(strcmp($_REQUEST["api_key"],API_KEY) != 0 ) {
        error_log("Wrong api key provided.");
        die("Wrong api key provided.");
    } else {
        return true;
    }
}

/*
* Setting session variables for the user so he is logged in
*/
function loginUserIntoSession(){
    $db = new Database();
    $uid = (int) $_REQUEST['user_id'];
    $sid = $_REQUEST['session_id'];
    $csrf_token = md5(uniqid(rand(), TRUE));

    $sql = "SELECT * FROM ".WS_SESSIONS." WHERE session_id = '".mysql_real_escape_string($sid, $db->getLink())."'";
    $res = $db->query($sql);

    $session_data  ="running|s:4:\"true\";";
    $session_data .="userid|s:".strlen($uid).":\"".$uid."\";";
    $session_data .="username|s:".strlen($_REQUEST['username']).":\"".$_REQUEST['username']."\";";
    $session_data .="nickname|s:".strlen($_REQUEST['nickname']).":\"".$_REQUEST['nickname']."\";";
    $session_data .="admin|s:".strlen($_REQUEST['admin']).":\"".$_REQUEST['admin']."\";";
    $session_data .="csrf_token|s:".strlen($csrf_token).":\"".$csrf_token."\";";

    if(mysql_num_rows($res) > 0){
        $sql = "UPDATE ".WS_SESSIONS." SET ".
             "session_data = '".mysql_real_escape_string($session_data,$db->getLink())."' ".
             "WHERE session_id = '".mysql_real_escape_string($sid, $db->getLink())."';";
        $db->query($sql);
    } else {
        $expires = time() + SESSION_EXPIRE;
        $db->insert(WS_SESSIONS,
            array("session_id" => $sid,
                  "session_expires" => $expires,
                  "session_data" => $session_data),
            array("%s","%d","%s")
        );
    }
}

function uploadProfilePicture() {
    // check if we have a file
    if (empty($_FILES)) {
        respond(array(
            'success' => false,
            'message' => 'No file uploaded!'
        ));
    }

    if (empty($_REQUEST['userid'])) {
        respond(array(
            'success' => false,
            'message' => 'No user ID set!'
        ));
    }

    $ext = end(explode(".", $_FILES['profile']['name']));
    $tempFile = $_FILES['profile']['tmp_name'];
    $imgName = strtolower($_REQUEST['userid'] . '.' . $ext);
    $path = APP_IMAGE_PATH . $imgName;

    try {
        File::s3Upload($tempFile, $path);

        $query = "
            UPDATE `" . USERS . "` 
            SET `picture` = '" . mysql_real_escape_string($imgName) . "' ,
            `s3bucket` = '" . S3_BUCKET ."'
            WHERE `id` = " . (int) $_REQUEST['userid'] . "
            LIMIT 1";

        if (! mysql_query($query)) {
            error_log("s3upload mysql: ".mysql_error());
            respond(array(
                'success' => false,
                'message' => SL_DB_FAILURE
            ));
        }

        respond(array(
            'success' => true,
            'picture' => $imgName
        ));

    } catch (Exception $e) {
        $success = false;
        $error = 'There was a problem uploading your file';
        error_log(__FILE__.": Error uploading images to S3:\n$e");
            
        return $this->setOutput(array(
            'success' => false,
            'message' => 'An error occured while uploading the file, please try again!'
        ));            
    }


}

function updateuser(){
    $sql = "UPDATE ".USERS." ".
           "SET ";
    $id = (int)$_REQUEST["user_id"];
    foreach($_REQUEST["user_data"] as $key => $value){
        $sql .= $key." = '".mysql_real_escape_string($value)."', ";
    }
    $sql = substr($sql,0,(strlen($sql) - 1));
    $sql .= " ".
            "WHERE id = ".$id;
    mysql_query($sql);
}

function pushVerifyUser(){
    $user_id = intval($_REQUEST['id']);
    $sql = "UPDATE " . USERS . " SET `confirm` = '1', is_active = '1' WHERE `id` = $user_id";
    mysql_unbuffered_query($sql);

    respond(array('success' => false, 'message' => 'User has been confirmed!'));
}

function updateProjectList(){
    $repo = basename($_REQUEST['repo']);

    $project = new Project();
    $project->loadByRepo($repo);
    $commit_date = date('Y-m-d H:i:s');
    $project->setLastCommit($commit_date);
    $project->save();
}

function getSystemDrawerJobs(){
    $sql = " SELECT "
         . " SUM(CASE WHEN w.status = 'Bidding' THEN 1 ELSE 0 END) AS bidding, "
         . " SUM(CASE WHEN w.status = 'Review'  THEN 1 ELSE 0 END) AS review "
         . " FROM " . WORKLIST . " AS w "
         . " WHERE w.status = 'Bidding' OR (w.status = 'Review' "
         .   " AND w.code_review_completed = 0 "
         .   " AND w.code_review_started = 0);";
    
    $result = mysql_query($sql);
    if ($result && ($row = mysql_fetch_assoc($result))) {
        $bidding_count = $row['bidding'];
        $review_count = $row['review'];
        $need_review = array();
        if ($review_count) {
            $sql = " SELECT w.id, w.summary "
                .  " FROM " . WORKLIST . " AS w "
                .  " WHERE w.status = 'Review' "
                .    " AND w.code_review_completed = 0 "
                .    " AND w.code_review_started = 0"
                .  " LIMIT 7;"; 
            $result = mysql_query($sql);
            while ($row = mysql_fetch_assoc($result)) {
                $need_review[] = array(
                    'id' => $row['id'],
                    'summary' => $row['summary'] 
                );
            }
        }
        respond(array(
            'success' => true, 
            'bidding' => $bidding_count, 
            'review' => $review_count,
            'need_review' => $need_review
        ));
    } else {
        respond(array('success' => false, 'message' => "Couldn't retrieve jobs"));
    }
}

function sendBidNotification() {
    require_once('./classes/Notification.class.php');
    $notify = new Notification();
    $notify->emailExpiredBids();
}

function sendPastDueNotification() {
    require_once('./classes/Notification.class.php');
    $notify = new Notification();
    $notify->emailPastDueJobs();
}

function processW2Masspay() {
    if (!defined('COMMAND_API_KEY')
        or !array_key_exists('COMMAND_API_KEY',$_POST)
        or $_POST['COMMAND_API_KEY'] != COMMAND_API_KEY)
        { die('Action Not configured'); }

    $con = mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
    if (!$con) {
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db(DB_NAME, $con);

    $sql = " UPDATE " . FEES . " AS f, " . WORKLIST . " AS w, " . USERS . " AS u "
         . " SET f.paid = 1, f.paid_date = NOW() "
         . " WHERE f.paid = 0 AND f.worklist_id = w.id AND w.status = 'Done' "
         . "   AND f.withdrawn = 0 "
         . "   AND f.user_id = u.id "
         . "   AND u.has_W2 = 1 "
         . "   AND w.status_changed < CAST(DATE_FORMAT(NOW(),'%Y-%m-01') as DATE) "
         . "   AND f.date <  CAST(DATE_FORMAT(NOW() ,'%Y-%m-01') as DATE); ";

    // Marks all Fees from the past month as paid (for DONEd jobs)
    if (!$result = mysql_query($sql)) { error_log("mysql error: ".mysql_error()); die("mysql_error: ".mysql_error()); }
    $total = mysql_affected_rows();

    if( $total) {
        echo "{$total} fees were processed.";
    } else {
        echo "No fees were found!";
    }

    $sql = " UPDATE " . FEES . " AS f, " . USERS . " AS u "
         . " SET f.paid = 1, f.paid_date = NOW() "
         . " WHERE f.paid = 0 "
         . "   AND f.bonus = 1 "
         . "   AND f.withdrawn = 0 "
         . "   AND f.user_id = u.id "
         . "   AND u.has_W2 = 1 "
         . "   AND f.date <  CAST(DATE_FORMAT(NOW() ,'%Y-%m-01') as DATE); ";

    // Marks all Fees from the past month as paid (for DONEd jobs)
    if (!$result = mysql_query($sql)) { error_log("mysql error: ".mysql_error()); die("mysql_error: ".mysql_error()); }
    $total = mysql_affected_rows();

    if( $total) {
        echo "{$total} bonuses were processed.";
    } else {
        echo "No bonuses were found!";
    }
    mysql_close($con);
}

function doScanAssets() {
    $scanner = new ScanAssets();
    $scanner->scanAll();
}

function respond($val){
    exit(json_encode($val));
}

function sendContactEmail(){
    $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
    $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
    $phone = isset($_REQUEST['phone']) ? $_REQUEST['phone'] : '';
    $proj_name = isset($_REQUEST['project']) ? $_REQUEST['project'] : '';
    $proj_desc = isset($_REQUEST['proj_desc']) ? $_REQUEST['proj_desc'] : '';
    $website = isset($_REQUEST['website']) ? $_REQUEST['website'] : '';
    if (empty($phone) || empty($email) || empty($phone) || empty($proj_name) || empty($proj_desc)) {
        exit(json_encode(array('error' => 'All Fields are required!')));
    }
    require_once('./classes/Notification.class.php');
    $notify = new Notification();
    if ($notify->emailContactForm($name, $email, $phone, $proj_name, $proj_desc, $website)) {
        exit(json_encode(array('success' => true)));
    } else {
        exit(json_encode(array('error' => 'There was an error sending your message, please try again later.')));
    }
}// end sendContactEmail
function sendTestNotifications(){
    $workItemId = isset($_REQUEST['workitem_id']) ? $_REQUEST['workitem_id'] : 0 ;
    $results = isset($_REQUEST['results']) ? $_REQUEST['results'] : '';
    $revision = isset($_REQUEST['revision']) ? $_REQUEST['revision'] : '';
    require_once('./classes/Notification.class.php');
    if($workItemId > 0) {
        $notify = new Notification();
        $notify->autoTestNofications($workItemId,$results,$revision);
        exit(json_encode(array('success' => true)));
    }
}

function autoPassSuggestedJobs() {
    $con = mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
    if (!$con) {
        die('Could not connect: ' . mysql_error());
    }
    mysql_select_db(DB_NAME, $con);
    $sql = "SELECT id FROM `" . WORKLIST ."` WHERE  status  IN ( 'Suggested' , 'SuggestedWithBid', 'Bidding') AND DATEDIFF(now() , status_changed) > 30";
    
    $result = mysql_query($sql);
    $delay = 0;
    if(mysql_num_rows($result) > 1) {
        $delay = 5;
    }
    while ($row = mysql_fetch_assoc($result)) {
        $status = 'Pass';
        $workitem = new WorkItem($row['id']);
        $prev_status = $workitem->getStatus();
        
        // change status of the workitem to PASS.
        $workitem->setStatus($status);
        if ($workitem->save()) {
            
            $recipients = array('creator');
            $emails = array();
            $data = array('prev_status' => $prev_status);
            
            if ($prev_status == 'Bidding') {
                $recipients[] = 'usersWithBids';
                $emails = preg_split('/[\s]+/', ADMINS_EMAILS);
            }
            
            //notify
            Notification::workitemNotify(
                array(
                    'type' => 'auto-pass',
                    'workitem' => $workitem,
                    'recipients' => $recipients,
                    'emails' => $emails
                ),
                $data
            );
            
            //sendJournalnotification
            $journal_message = "**#" . $workitem->getId() . "** updated by @Otto \n\n**" . $workitem->getSummary() . "**. Status set to *" . $status . "*";
            sendJournalNotification(stripslashes($journal_message));            
        } else {
            error_log("Otto failed to update the status of workitem #" . $workitem->getId() . " to " . $status);
        }
        sleep($delay);
    }
    mysql_free_result($result);
    mysql_close($con); 
}

function getTimezone() {
    if (isset($_REQUEST['username'])) {
        $username = $_REQUEST['username'];
    } else {
        respond(array('succeeded' => false, 'message' => 'Error: Could not determine the user'));
    }

    $user = new User();
    if ($user->findUserByUsername($username)) {
        respond(array('succeeded' => true, 'message' => $user->getTimezone()));
    } else {
        respond(array('succeeded' => false, 'message' => 'Error: Could not determine the user'));
    }
}

function updateLastSeen() {
    if (isset($_REQUEST['username'])) {
        $username = $_REQUEST['username'];
    } else {
        respond(array('succeeded' => false, 'message' => 'Error: Could not determine the user'));
    }
    $qry = "UPDATE ". USERS ." SET last_seen = NOW() WHERE username='". $username ."'";
    if ($res = mysql_query($qry)) {
        respond(array('succeeded' => true, 'message' => 'Last seen time updated!'));
    } else {
        respond(array('succeeded' => false, 'message' => mysql_error()));
    }
}

function processPendingReviewsNotifications() {
    // Check if it is time to process notifications
    if (!isset($_REQUEST['force']) && !canProcessNotifications()) {
        return;
    }

    // process pending journal notifications
    $pendingReviews = Review::getReviewsWithPendingJournalNotifications();
    if($pendingReviews !== false && count($pendingReviews) > 0) {
        echo "<br/>Processing " . count($pendingReviews) . " reviews.";
        foreach ($pendingReviews as $review) {
            $tReview = new Review();
            $tReview->loadById($review['reviewer_id'], $review['reviewee_id']);
            if ($tReview->journal_notified == 0) {
                sendReviewNotification($tReview->reviewee_id, 'update',
                    $tReview->getReviews($tReview->reviewee_id, $tReview->reviewer_id, ' AND r.reviewer_id=' . $tReview->reviewer_id));
            } else {
                sendReviewNotification($tReview->reviewee_id, 'new', 
                    $tReview->getReviews($tReview->reviewee_id, $tReview->reviewer_id, ' AND r.reviewer_id=' . $tReview->reviewer_id));
            }
            $tReview->journal_notified = 1;
            $tReview->save('reviewer_id', 'reviewee_id');
            usleep(4000000);
        }
    } else {
        echo "<br />Processed. No pending Reviews.";
    }
    resetCronFile();
}

function canProcessNotifications() {
    $file = REVIEW_NOTIFICATIONS_CRON_FILE;
    // If no temp file is set (first time?) run it
    if (!file_exists($file)) {
        return true;
    } else {
        $hour = (int) file_get_contents($file);
        $serverHour = (int) date('H'); 
        if ($serverHour == $hour) {
            return true;
        } else {
            echo "<br/>It is not time yet.";
            echo "<br/>Next hour: " . $hour;
            echo "<br/>Current hour:" . $serverHour;
            return false;
        }
    }
}

function resetCronFile() {
    $hourLag = mt_rand(5, 12);
    $serverHour = (int) date('H');
    $newHour = $hourLag + $serverHour;
    if ($newHour > 23) {
        $newHour -= 24;
    }
    echo "<br/>Cron File Reseted.";
    echo "<br/>Next hour: " . $newHour;
    unlink(REVIEW_NOTIFICATIONS_CRON_FILE);
    file_put_contents(REVIEW_NOTIFICATIONS_CRON_FILE, $newHour);
    chmod (REVIEW_NOTIFICATIONS_CRON_FILE, 0755);
}


// Prune Journal entries by deleting all entries except the latest 100
function pruneJournalEntries() {
    $sql = " SELECT MAX(id) AS maxId FROM " . ENTRIES;
    $result = mysql_query($sql);
    if ($result) {
        $row = mysql_fetch_assoc($result);
    } else {
        die( 'Failed to get all entries');
    }
    $total = (int) $row['maxId'] - 100;

    $sql = " DELETE FROM " . ENTRIES . " WHERE id <= {$total};";
    echo $sql;
    $result = mysql_unbuffered_query($sql);
    echo "<br/> # of deleted entries: " . mysql_affected_rows();

}

function createDatabaseNewProject() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('project', $_REQUEST)) {
        try {
            if ($sandBoxUtil->createDatabaseNewProject($_REQUEST['project'], $_REQUEST['username'])) {
                echo json_encode(array('success'=>true, 'message'=>'Database created succesfully'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Database creation failed'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing Parameters'));
    }
}

function createRepo() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('project', $_REQUEST)) {
        try {
            if ($sandBoxUtil->createRepo($_REQUEST['project'])) {
                echo json_encode(array('success'=>true, 'message'=>'Repository created succesfully'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Repository not created'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function createSandbox() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('username', $_REQUEST) && array_key_exists('nickname', $_REQUEST)
        && array_key_exists('unixusername', $_REQUEST) && array_key_exists('projectname', $_REQUEST)) {
        try {
            if ($sandBoxUtil->createSandbox($_REQUEST['username'], 
                                        $_REQUEST['nickname'],
                                        $_REQUEST['unixusername'], 
                                        $_REQUEST['projectname'], 
                                        null, 
                                        $_REQUEST['newuser'])) {
                $user = new User();
                $user->findUserByNickname($_REQUEST['nickname']);
                $user->setHas_sandbox(1);
                $user->setUnixusername($_REQUEST['unixusername']);
                $user->setProjects_checkedout($_REQUEST['projectname']);
                $user->save();
                echo json_encode(array('success'=>true, 'message'=>'Sandbox created'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Sandbox creation and project checkout failed'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function sendNewProjectEmails() {
    if (array_key_exists('username', $_REQUEST) && array_key_exists('nickname', $_REQUEST)
        && array_key_exists('unixusername', $_REQUEST) && array_key_exists('projectname', $_REQUEST)) {
        $data = array();
        $data['project_name'] = $_REQUEST['projectname'];
        $data['nickname'] = $_REQUEST['unixusername'];
        $data['database_user'] = $_REQUEST['dbuser'];
        $data['repo_type'] = $_REQUEST['repo_type'];
        $data['github_repo_url'] = $_REQUEST['github_repo_url'];
        $user = new User();
        sendTemplateEmail(SUPPORT_EMAIL, 'ops-project-created', $data);
        if (!sendTemplateEmail($_REQUEST['username'], $_REQUEST['template'], $data)) {
            echo json_encode(array('success'=>false, 'message'=>'Emails not sent'));
        } else {
            echo json_encode(array('success'=>true, 'message'=>'Emails sent out'));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function modifyConfigFile() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('username', $_REQUEST) && array_key_exists('nickname', $_REQUEST)
        && array_key_exists('unixusername', $_REQUEST) && array_key_exists('projectname', $_REQUEST)) {
        if ($sandBoxUtil->modifyConfigFile($_REQUEST['unixusername'], 
                                           $_REQUEST['projectname'],
                                           $_REQUEST['dbuser'])) {
            echo json_encode(array('success'=>true, 'message'=>'Sandbox created'));
        } else {
            echo json_encode(array('success'=>false, 'message'=>'Sandbox creation and project checkout failed'));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function addPostCommitHook() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('repo', $_REQUEST)) {
        try {
            if ($sandBoxUtil->addPostCommitHook($_REQUEST['repo'])) {
                echo json_encode(array('success'=>true, 'message'=>'Post commit hook added'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Failed adding post commit hook'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function deployStagingSite() {
    $sandBoxUtil = new SandBoxUtil();
    if (array_key_exists('repo', $_REQUEST)) {
        try {
            if ($sandBoxUtil->deployStagingSite($_REQUEST['repo'])) {
                echo json_encode(array('success'=>true, 'message'=>'Post commit hook added'));
            } else {
                echo json_encode(array('success'=>false, 'message'=>'Failed adding post commit hook'));
            }
        } catch (Exception $e) {
            echo json_encode(array('success'=>false, 'message'=>$e->getMessage()));
        }
    } else {
        echo json_encode(array('success'=>false, 'message'=>'Missing parameters'));
    }
}

function getFavoriteUsers() {
    if (!$userid = (isset($_SESSION['userid']) ? $_SESSION['userid'] : 0)) {
    echo json_encode(array('favorite_users' => array()));
    return;
    }
    $users_favorite = new Users_Favorite();
    $data = array('favorite_users' => $users_favorite->getFavoriteUsers($userid));
    echo json_encode($data);
}

/**
 * Returns a list of all the countries supported by Twilio
 */
function getTwilioCountries() {
    $sql = 'SELECT `country_code`, `country_phone_prefix` FROM `' . COUNTRIES . '` WHERE `country_twilio_enabled` = 1';

    $result = mysql_query($sql);
    if(!is_resource($result)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Could not retrieve the list of twilio supported countries'
        ));
        return;
    }
    
    $list = array();
    while ($row = mysql_fetch_assoc($result)) {
        $list[$row['country_code']] = $row['country_phone_prefix'];
    }
    
    echo json_encode(array(
        'success' => true,
        'list' => $list
    ));
    return;
}

function deployErrorNotification() {

    $work_item_id = isset($_REQUEST['workitem']) ? $_REQUEST['workitem'] : 0;
    $error_msg = isset($_REQUEST['error']) ? base64_decode($_REQUEST['error']) : '';
    $commit_rev = isset($_REQUEST['rev']) ? $_REQUEST['rev'] : '';
    require_once('classes/Notification.class.php');

    $notify = new Notification();
    $notify->deployErrorNotification($work_item_id, $error_msg, $commit_rev);
    exit(json_encode(array('success' => true)));
}

function saveSoundSettings() {
    if (!$userid = (isset($_SESSION['userid']) ? $_SESSION['userid'] : 0)) {
        echo json_encode(array('success'=>false, 'message'=>'Not logged-in user'));
        return;
    }
    try {
        $settings = 0;
        $settings_arr = preg_split('/:/', $_REQUEST['settings'], 5);
        
        if ((int) $settings_arr[0]) {
            $settings = $settings | JOURNAL_CHAT_SOUND;
        }
        if ((int) $settings_arr[1]) {
            $settings = $settings | JOURNAL_SYSTEM_SOUND;
        }
        if ((int) $settings_arr[2]) {
            $settings = $settings | JOURNAL_BOT_SOUND;
        }
        if ((int) $settings_arr[3]) {
            $settings = $settings | JOURNAL_PING_SOUND;
        }
        if ((int) $settings_arr[4]) {
            $settings = $settings | JOURNAL_EMERGENCY_ALERT;
        }
        
        $user = new User();
        $user->findUserById($userid);
        $user->setSound_settings($settings);
        $user->save();
        echo json_encode(array('success'=>true, 'message'=>'Settings saved'));
    } catch(Exception $e) {
        echo json_encode(array('success'=>false, 'message'=>'Settings saving failed'));
    }
}

function sendNotifications() {
    if (! array_key_exists('command', $_REQUEST)) {
        echo json_encode(array('success' => false, 'message' => 'Missing parameters'));
        exit;
    }
    $command = $_REQUEST['command'];
    switch ($command) {
        case 'statusNotify':
            if (! array_key_exists('workitem', $_REQUEST)) {
                echo json_encode(array('success' => false, 'message' => 'Missing parameters'));
                exit;
            }
            $workitem_id = (int) $_REQUEST['workitem'];
            $workitem = new WorkItem;
            $workitem->loadById($workitem_id);
            Notification::statusNotify($workitem);
            error_log('api.php: statusNotify completed');
            break;
    }
    echo json_encode(array('success' => true, 'message' => 'Notifications sent'));
}

function checkInactiveProjects() {
    $report_message = '';
    $db = new Database();
    
    $sql_inactive_projects = "
        SELECT w.project_id, p.name, p.contact_info, u.nickname, MAX(status_changed) AS last_change 
        FROM " . WORKLIST . " AS w 
        INNER JOIN " . PROJECTS . " AS p ON w.project_id=p.project_id 
        LEFT JOIN " . USERS . " AS u ON u.id=p.owner_id 
        WHERE p.active = 1 OR 1
        GROUP BY w.project_id HAVING last_change < DATE_SUB(NOW(), INTERVAL 90 DAY) 
        ORDER BY p.name ASC";
    
    // Delete accounts which exists for at least 45 days and never have been used.
    $result = $db->query($sql_inactive_projects);
    
    while ($row = mysql_fetch_assoc($result)) {
        $project = new Project($row['project_id']);
        // send email
        $data = array( 
            'owner' => $row['nickname'],
            'projectUrl' => Project::getProjectUrl($row['project_id']),
            'projectName' => $row['name']
        );
        if (! sendTemplateEmail($row['contact_info'], 'project-inactive', $data)) {
            $report_message .= ' <p> Ok ---';
        } else {
            $report_message .= ' <p> Fail -';
        }
        $report_message .= ' Project (' . $row['project_id'] . ')- <a href="' . Project::getProjectUrl($row['project_id']) . '">' . $row['name'] . '</a> -- Last changed status: ' .  $row['last_change'] . '</p>';
        $project->setActive(0);
        $project->save();
    }
    // Send report to ops if any project was set as inactive
    if ($report_message != '') {
        $headers['From'] = DEFAULT_SENDER;
        $subject = "Inactive Projects Report";
        $body = $report_message;
        if (!send_email(OPS_EMAIL, $subject, $body, null, $headers )) {
            error_log ('checkActiveProjects cron: Failed to send email report'); 
        }
    }
}

function checkRemovableProjects() {
    $report_message = '';
    $db = new Database();

    $sql_projects = "
        SELECT p.project_id, p.name, u.nickname, p.creation_date 
        FROM " . PROJECTS . " AS p 
        LEFT JOIN " . USERS . " AS u ON u.id=p.owner_id 
        WHERE p.project_id NOT IN (SELECT DISTINCT w1.project_id 
        FROM " . WORKLIST . " AS w1)
          AND p.creation_date < DATE_SUB(NOW(), INTERVAL 180 DAY)";
    
    $result = $db->query($sql_projects);
    while ($row = mysql_fetch_assoc($result)) {
        // send email
        $data = array( 
            'owner' => $row['nickname'],
            'projectUrl' => Project::getProjectUrl($row['project_id']),
            'projectName' => $row['name'],
            'creation_date' => date('Y-m-d', strtotime($row['creation_date']))
        );
        if (sendTemplateEmail($row['contact_info'], 'project-removed', $data)) {
            $report_message .= ' <p> Ok email---';
        } else {
            $report_message .= ' <p> Failed email -';
        }
        $report_message .= ' Project (' . $row['project_id'] . ')- <a href="' . Project::getProjectUrl($row['project_id']) . '">' . $row['name'] . '</a> -- Created: ' .  $row['creation_date'] . '</p>';

    // Remove projects dependencies

        // Remove project users
        $report_message .= '<p> Users removed for project id ' . $row['project_id'] . ':</p>';
        $sql_get_project_users = "SELECT * FROM " . PROJECT_USERS . " WHERE project_id = " . $row['project_id'];
        $result_temp = $db->query($sql_get_project_users);
        while ($row_temp = mysql_fetch_assoc($result_temp)) {
            $report_message .= dump_row_values($row_temp);
        }
        
        $sql_remove_project_users = "DELETE FROM " . PROJECT_USERS . " WHERE project_id = " . $row['project_id'];
        $db->query($sql_remove_project_users);

        // Remove project runners
        $report_message .= '<p> Designers removed for project id ' . $row['project_id'] . ':</p>';
        $sql_get_project_runners = "SELECT * FROM " . PROJECT_RUNNERS . " WHERE project_id = " . $row['project_id'];
        $result_temp = $db->query($sql_get_project_runners);
        while ($row_temp = mysql_fetch_assoc($result_temp)) {
            $report_message .= dump_row_values($row_temp);
        }
        $sql_remove_project_runners = "DELETE FROM " . PROJECT_RUNNERS . " WHERE project_id = " . $row['project_id'];
        $db->query($sql_remove_project_runners);

        // Remove project roles
        $report_message .= '<p> Roles removed for project id ' . $row['project_id'] . ':</p>';
        $sql_get_project_roles = "SELECT * FROM " . ROLES . " WHERE project_id = " . $row['project_id'];
        $result_temp = $db->query($sql_get_project_roles);
        while ($row_temp = mysql_fetch_assoc($result_temp)) {
            $report_message .= dump_row_values($row_temp);
        }
        $sql_remove_project_roles = "DELETE FROM " . ROLES . " WHERE project_id = " . $row['project_id'];
        $db->query($sql_remove_project_roles);

        $url = TOWER_API_URL;
        $fields = array( 
                'action' => 'staging_cleanup',
                'name' => $row['name']
        );
                
        $result = CURLHandler::Post($url, $fields);

        // Remove project 
        $report_message .= '<p> Project id ' . $row['project_id'] . ' removed </p>';
        $sql_get_project = "SELECT * FROM " . PROJECTS . " WHERE project_id = " . $row['project_id'];
        $result_temp = $db->query($sql_get_project);
        while ($row_temp = mysql_fetch_assoc($result_temp)) {
            $report_message .= dump_row_values($row_temp);
        }
        $sql_remove_project = "DELETE FROM " . PROJECTS . " WHERE project_id = " . $row['project_id'];
        $db->query($sql_remove_project);
    }
    // Send report to ops if any project was set as inactive
    if ($report_message != '') {
        $headers['From'] = DEFAULT_SENDER;
        $subject = "Removed Projects Report";
        $body = $report_message;
        if (!send_email(OPS_EMAIL, $subject, $body, null, $headers )) {
            error_log ('checkActiveProjects cron: Failed to send email report'); 
        }
    }
}

function dump_row_values($row) {
    $dump = '<p>';
    foreach ($row as $key=> $val ) {
        $dump .= '"' . $key . '" => ' . $val . ':';
    }
    $dump .= '</p>';
    return $dump;
}

function addProject() {
    $journal_message = '';
    $nick = '';

    $userId = getSessionUserId();
    if ($userId) {
        initUserById($userId);
        $user = new User();
        $user->findUserById( $userId );
        $nick = $user->getNickname();

        $project = new Project();
        $cr_3_favorites = $_REQUEST["cr_3_favorites"];
        $args = array(
            'name',
            'description',
            'logo',
            'website',
            'checkGitHub',
            'github_repo_url',
            'defaultGithubApp',
            'githubClientId',
            'githubClientSecret'
        );

        foreach ($args as $arg) {
            $$arg = !empty($_POST[$arg]) ? $_POST[$arg] : '';
        }
        
        if (!ctype_alnum($name)) {
            die(json_encode(array('error' => "The name of the project can only contain letters (A-Z) and numbers (0-9). Please review and try again.")));
        }
        $repository = $name;

        if ($project->getIdFromName($name)) {
            die(json_encode(array('error' => "Project with the same name already exists!")));
        }

        $project->setName($name);
        $project->setDescription($description);
        $project->setWebsite($website);
        $project->setContactInfo($user->getUsername());
        $project->setOwnerId($userId);
        $project->setActive(true);
        $project->setLogo($logo);
        if ($checkGitHub == 'true') {
            $project->setRepo_type('git');
            $project->setRepository($github_repo_url);

            if ($defaultGithubApp == 'false') {
                $project->setGithubId($githubClientId);
                $project->setGithubSecret($githubClientSecret);
            }
        } else {
            $project->setRepo_type('svn');
            $project->setRepository($name);
        }
        $project->save();
        
        $journal_message = '@' . $nick . ' added project *' . $name . '*';
        if (!empty($journal_message)) {
            //sending journal notification
            sendJournalNotification($journal_message);
        }

        echo json_encode(array( 'return' => "Done!"));
    } else {
        echo json_encode(array( 'error' => "You must be logged in to add a new project!"));
    }
}

function addWorkitem() {
    $journal_message = '';
    $workitem_added = false;
    $nick = '';

    $workitem = new WorkItem();

    $userId = getSessionUserId();
    if (!$userId > 0 ) {
        echo json_encode(array('error' => "Invalid parameters !"));
        return;
    }

    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();

    $itemid = $_REQUEST['itemid'];
    $summary = $_REQUEST['summary'];
    $project_id = $_REQUEST['project_id'];
    $skills = $_REQUEST['skills'];
    $status = $_REQUEST['status'];
    $notes = $_REQUEST['notes'];
    $invite = $_REQUEST['invite'];
    $is_expense = $_REQUEST['is_expense'];
    $is_rewarder = $_REQUEST['is_rewarder'];
    $fileUpload = $_REQUEST['fileUpload'];
    $bug_job_id = $_REQUEST['bug_job_id'];
    $is_bug = false;

    if (!$user->getIs_runner() || !preg_match('/^(Draft|Suggested|Bidding|Working|Done)$/', $status)) {
        $status = 'Suggested';
    }

    if (! empty($_POST['itemid'])) {
        $workitem->loadById($_POST['itemid']);
    } else {
        $workitem->setCreatorId($userId);
        $workitem_added = true;
    }
    $workitem->setSummary($summary);

    try {
        $bugof_workitem = new WorkItem();
        $bugof_workitem->loadById($bug_job_id);
        $is_bug = true;
    } catch(Exception $e) {
        // do not set as bug
        $bug_job_id = 0;
    }
    

    //If this item is a bug add original item id 
    $workitem->setBugJobId($bug_job_id);
    // not every runner might want to be assigned to the item he created - only if he sets status to 'BIDDING'
    if ($status == 'Bidding' && $user->getIs_runner() == 1) {
        $runner_id = $userId;
    } else {
        $runner_id = 0;
    }

    $skillsArr = explode(', ', $skills);

    $workitem->setRunnerId($runner_id);
    $workitem->setProjectId($project_id);
    $workitem->setStatus($status);
    $workitem->setNotes($notes);
    $workitem->setWorkitemSkills($skillsArr);
    $workitem->setIs_bug($is_bug == 'true' ? 1 : 0);
    $workitem->save();
    $related = getRelated($notes);
    Notification::massStatusNotify($workitem);

    // if files were uploaded, update their workitem id
    $file = new File();
    // update images first
    if (isset($fileUpload['images'])) {
        foreach ($fileUpload['images'] as $image) {
            $file->findFileById($image);
            $file->setWorkitem($workitem->getId());
            $file->save();
        }
    }
    // update documents
    if (isset($fileUpload['documents'])) {
        foreach ($fileUp