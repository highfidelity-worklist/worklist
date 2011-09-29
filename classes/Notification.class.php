<?php
require_once 'send_email.php';
require_once 'workitem.class.php';
require_once 'functions.php';
require_once 'lib/Sms.php';
require_once 'Project.class.php';

/**
 * This class is responsible for working with notification
 * subscriptions
 */
class Notification {
    const REVIEW_NOTIFICATIONS = 1;
    const BIDDING_NOTIFICATIONS = 2;
    const MY_REVIEW_NOTIFICATIONS = 4;
    const MY_COMPLETED_NOTIFICATIONS = 8;
    const PING_NOTIFICATIONS = 16;
    const MY_BIDS_NOTIFICATIONS = 32;
    const SELF_EMAIL_NOTIFICATIONS = 64;
    const FUNCTIONAL_NOTIFICATIONS = 128;
    const BIDDING_EMAIL_NOTIFICATIONS = 256;
    const REVIEW_EMAIL_NOTIFICATIONS = 512;
    const MY_AUTOTEST_NOTIFICATIONS = 1024;
 
    /**
     *  Sets flags using list of integers passed as arguments
     *
     * @return resulting integer with combined flags
     */
    public static function setFlags() {
            $result = 0;
            foreach(func_get_args() as $flag) {
                $result = $result | $flag;
            }
            return $result;
    }

    /**
     * Check if given flag is set for value
     *
     * @param $value value to check against
     * @param $flag flag to check
     * @return boolean returns true if given flag is set for value
     */
    public static function isNotified($value, $flag = self::REVIEW_NOTIFICATIONS) {
            $result = ($value & $flag) === $flag;
            return $result;
    }

    /**
     * Get list of users who have given flag set
     * List is returned only for active users!
     * This flags are for SMS notifications only
     * @param $flag flag to compare with
     * @return list of users with given flag
     */
    public static function getNotificationEmails($flag = self::REVIEW_NOTIFICATIONS, $workitem = 0) {
         
        $result = array();
        
        switch($flag) {
        case self::FUNCTIONAL_NOTIFICATIONS:
            $users=implode(",", array($workitem->getCreatorId(), $workitem->getRunnerId(), $workitem->getMechanicId()));
            $sql = "SELECT u.username FROM `" . USERS . "` u WHERE u.notifications & $flag != 0 AND u.id!=" .getSessionUserId(). " AND u.id IN({$users})";
            $res = mysql_query($sql);
            if($res) {
                while($row = mysql_fetch_row($res)) {
                    $result[] = $row[0];
                }
            }
            break;
        case self::REVIEW_NOTIFICATIONS :
        case self::REVIEW_EMAIL_NOTIFICATIONS :
        case self::BIDDING_NOTIFICATIONS :
        case self::BIDDING_EMAIL_NOTIFICATIONS :
            $sql = "SELECT u.username FROM `" . USERS . "` u WHERE u.notifications & $flag != 0";
            $res = mysql_query($sql);
            if($res) {
                while($row = mysql_fetch_row($res)) {
                    $result[] = $row[0];
                }
            }
            break;
        case self::MY_REVIEW_NOTIFICATIONS :
        case self::MY_COMPLETED_NOTIFICATIONS :
        case self::MY_BIDS_NOTIFICATIONS:
            $users=implode(",", array($workitem->getCreatorId(), $workitem->getRunnerId(), $workitem->getMechanicId()));
            $sql = "SELECT u.username FROM `" . USERS . "` u WHERE u.notifications & $flag != 0 AND u.id!=" .getSessionUserId(). " AND u.id IN({$users})";
            $res = mysql_query($sql);
            if($res) {
                while($row = mysql_fetch_row($res)) {
                    $result[] = $row[0];
                }
            }
            break;
        case self::MY_AUTOTEST_NOTIFICATIONS:
            $users=implode(",", array($workitem->getCreatorId(), $workitem->getRunnerId(), $workitem->getMechanicId()));
            $sql = "SELECT u.username FROM `" . USERS . "` u WHERE u.id IN({$users})";
            $res = mysql_query($sql);
            if($res) {
                while($row = mysql_fetch_row($res)) {
                    $result[] = $row[0];
                }
            }
            break;         
        }
        return $result;
    }

    /**
     * Notifications for workitem statuses
     *
     * @param String $status - status of workitem
     */
    public static function statusNotify($workitem) {
        switch($workitem->getStatus()) {
            case 'FUNCTIONAL':
                $emails = self::getNotificationEmails(self::FUNCTIONAL_NOTIFICATIONS, $workitem);
                $options = array('type' => 'new_functional',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);
                self::workitemSMSNotify($options);
                break;
            case 'REVIEW':
                $emails = self::getNotificationEmails(self::REVIEW_EMAIL_NOTIFICATIONS);
                $myEmails= self::getNotificationEmails(self::MY_REVIEW_NOTIFICATIONS,$workitem);
                $myEmails=array_diff($myEmails,$emails); // Remove already existing emails in $emails list
                $emails=array_merge($emails,$myEmails);
                $emails=array_unique($emails);
                $options = array('type' => 'new_review',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);
                $emails = self::getNotificationEmails(self::REVIEW_NOTIFICATIONS);
                $myEmails= self::getNotificationEmails(self::MY_REVIEW_NOTIFICATIONS,$workitem);
                $myEmails=array_diff($myEmails,$emails); // Remove already existing emails in $emails list
                $emails=array_merge($emails,$myEmails);
                $emails=array_unique($emails);
                $options = array('type' => 'new_review',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemSMSNotify($options);
                // Send SMS to users who have "My jobs set to review" checked
                $options = array('type' => 'my_review',
                    'workitem' => $workitem,
                    'emails' => $myEmails);
                self::workitemSMSNotify($options);
                break;
            case 'BIDDING':
                $emails = self::getNotificationEmails(self::BIDDING_EMAIL_NOTIFICATIONS);
                $options = array('type' => 'new_bidding',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);
                $emails = self::getNotificationEmails(self::BIDDING_NOTIFICATIONS);
                $options = array('type' => 'new_bidding',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemSMSNotify($options);
                break;
            case 'COMPLETED':
                $emails= self::getNotificationEmails(self::MY_COMPLETED_NOTIFICATIONS,$workitem);
                $options = array('type' => 'my_completed',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemSMSNotify($options);
                break;
            case 'SUGGESTEDwithBID':
                $emails= self::getNotificationEmails(self::MY_BIDS_NOTIFICATIONS,$workitem);
                $options = array('type' => 'suggestedwithbid',
                'workitem' => $workitem,
                'emails' => $emails);
                $data = array('notes' => $workitem->getNotes());
                self::workitemNotify($options, $data);
                self::workitemSMSNotify($options, $data);             
            break;
        }
    }

    /**
     *  This function notifies selected recipients about updates of workitems
     * except for currently logged in user
     *
     * @param Array $options - Array with options:
     * type - type of notification to send out
     * workitem - workitem object with updated data
     * recipients - array of recipients of the message ('creator', 'runner', 'mechanic')
     * emails - send message directly to list of emails (array) -
     * if 'emails' is passed - 'recipients' option is ignored
     * @param Array $data - Array with additional data that needs to be passed on
     * @param boolean $includeSelf - forece user receive email from self generated action
     * example: 'who' and 'comment' - if we send notification about new comment
     */
    public static function workitemNotify($options, $data = null, $includeSelf = true) {

        $recipients = isset($options['recipients']) ? $options['recipients'] : null;
        $emails = isset($options['emails']) ? $options['emails'] : array();

        $workitem = $options['workitem'];
        if (isset($options['project_name'])) {
            $project_name = $options['project_name'];
        } else {
            $project = new Project();
            $project->loadById($workitem->getProjectId());
            $project_name = $project->getName();
        }

        $revision = isset($options['revision']) ? $options['revision'] : null;
        
        $itemId = $workitem -> getId();
        $itemLink = '<a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>#' . $itemId
                    . '</a> (' . $workitem -> getSummary() . ')';
        $itemTitle = '#' . $itemId  . ' (' . $workitem -> getSummary() . ')';
        $itemTitleWithProject = '#' . $itemId  . ': ' . $project_name . ': (' . $workitem -> getSummary() . ')';
        $body = '';
        $subject = '';
        $headers=array();
        switch ($options['type']) {
            case 'comment':
                $subject = 'Comment: ' . $itemTitle;
                $body  = 'New comment was added to the item ' . $itemLink . '.<br>';
                $body .= $data['who'] . ' says:<br />'
                         . $data['comment'];
            break;
            
            case 'fee_added':
                if($workitem->getStatus() != 'DRAFT') {
                $subject = 'Fee: ' . $itemTitle;
                $body = 'New fee was added to the item ' . $itemLink . '.<br>'
                        . 'Who: ' . $data['fee_adder'] . '<br>'
                        . 'Amount: ' . $data['fee_amount'];
                }
            break;

            case 'tip_added':
                $subject = 'You\'ve received a tip from ' . $data['tip_adder'] . ' on job #' . $itemId;
                $body = $data['tip_adder'] . ' tipped you $' . $data['tip_amount'] . ' on job ' . $itemLink . ' for:<br><br>' . $data['tip_desc'] . '<br><br>Yay!';
            break;

            case 'bid_accepted':
                $subject = 'Accepted: ' . $itemTitle;
                $body = 'Your bid was accepted for ' . $itemLink . '<br/>'
                        . 'Promised by: ' . $_SESSION['nickname'];
            break;

            case 'bid_placed':
                $subject = 'Bid: ' . $itemTitle ;
                $body =  'New bid was placed for ' . $itemLink . '<br /><br />'
                    . 'Amount: $' . number_format($data['bid_amount'], 2) . '<br />'
                    . 'Done In: ' . $data['done_in'] . '<br />'
                    . 'Expires: ' . $data['bid_expires'] . '<br /><br />'
                    . 'Bidder Email: <a href="mailto:' . $_SESSION['username'] . '">' . $_SESSION['username'] . '</a><br /><br />'
                    . 'Notes: ' . $data['notes'] . '<br />';

                $urlacceptbid  = '<br /><a href="' . SERVER_URL . 'workitem.php';
                $urlacceptbid .= '?job_id=' . $itemId . '&bid_id=' . $data['bid_id'] . '&action=accept_bid">Click here to accept bid.</a>';
                $body .=  $urlacceptbid;
            break;

            case 'bid_updated':
                $subject = 'Bid: ' . $itemTitle;
                $body = 'Bid updated for ' . $itemLink . '<br /><br/>'
                    . 'Amount: $' . number_format($data['bid_amount'], 2) . '<br />'
                    . 'Done In: ' . $data['done_in'] . '<br />'
                    . 'Expires: ' . $data['bid_expires'] . '<br /><br />'
                    . 'Bidder Email: <a href="mailto:' . $_SESSION['username'] . '">' . $_SESSION['username'] . '</a><br /><br />'
                    . 'Notes: ' . $data['notes'] . '<br />';
                $urlacceptbid  = '<br /><a href="' . SERVER_URL . 'workitem.php';
                $urlacceptbid .= '?job_id=' . $itemId . '&bid_id=' . $data['bid_id'] .
                                 '&action=accept_bid">Click here to accept bid.</a>';
                $body .=  $urlacceptbid;
            break;

            case 'modified-functional':
                $subject = "Functional: ".$itemTitle;
                $body = $_SESSION['nickname'] . ' updated item ' . $itemLink . '<br>'
                        . $data['changes'];
            break;
            
            case 'modified':
            if($workitem->getStatus() != 'DRAFT') {
                $subject = $data['title'] . ": " . $itemTitle;
                $body = $_SESSION['nickname'] . ' updated item ' . $itemLink . '<br>'
                        . $data['changes'];
            }
            break;

            case 'new_bidding':
                $subject = "Bidding: " . $itemTitleWithProject;
                $body = "Summary:<br>".$workitem -> getSummary() .
                         '<br><br>Notes:<br>'.$workitem->getNotes();
                $body .= '<br><br>You are welcome to bid <a href='.SERVER_URL.
                         'workitem.php?job_id=' . $itemId . '>here</a>.';
            break;

            case 'new_review':
                $subject = "Review: ".$itemTitle;
                $body = 'New item is available for review: ' . $itemLink . '<br>';
            break;
            case 'new_functional':
                $subject = "Functional: ".$itemTitle;
                $body =  'New item is available for functional: ' . $itemLink . '<br>';
            break;
            case 'bug_found':
                $subject = "New bug of item #".$workitem->getBugJobId().
                            " has been reported - ".$itemTitle." -";
                
                $body = "<p>A bug has been reported related to item #".
                $workitem->getBugJobId().
                            " : ".$workitem->getBugJobSummary()."</p>";

                $body .= "<p>New item #" . $itemId . " summary: " .
                            $workitem -> getSummary() . ".</p>";
                $body .= "<p>" . $workitem->getNotes(). "</p>";
                $body .= '<br><br><a href=' . SERVER_URL . 'workitem.php?job_id=' .
                            $itemId . '>View new item</a>.';
            break;
            case 'suggested':
                $subject = "Suggested: " . $itemTitle;
                $body =  'Summary:<br/> ' . $workitem -> getSummary() ;
                $body.= '<br/><br/>Notes: ' . $data['notes'] ;
                $body .= '<br><br>You can see the task <a href=' . SERVER_URL .
                         'workitem.php?job_id=' . $itemId . '>here</a>.';
            break;
            case 'suggestedwithbid':
                $subject = "Suggested With Bid: " . $itemTitle;
                $body =  'Summary:<br/> ' . $workitem -> getSummary();
                $body .= '<br/><br/>Notes: ' . $data['notes'];
                $body .= '<br><br>You can see the task <a href=' . SERVER_URL.
                         'workitem.php?job_id=' . $itemId . '>here</a>.';                         
            break;
            case 'autotestsuccess':
                $reusableString = $project_name . '(v' . $revision . ')';
                $reusableString .= '#';
                $reusableString .= $itemId;
                $reusableString .= ':';
                $reusableString .= $workitem -> getSummary();
                $subject = 'Commit Success - ' . $reusableString;
                $body =  'Congrats!';
                $body .= '<br/><br/>Your Commit - ' . $reusableString . ' was a success!';
                $body .= '<br><br>Click <a href="';
                $body .= 'http://svn.worklist.net/revision.php?repname=';
                $body .= $project_name;
                $body .= '&rev=';
                $body .= $revision;
                $body .= '">here</a>';
                $body .= ' to see the webSVN commit notes.';
                $body .= '<br/><br/>-worklist.net';
            break; 
            case 'autotestfailure':
                $reusableString = $project_name . '(v' . $revision . ')';
                $reusableString .= '#';
                $reusableString .= $itemId;
                $reusableString .= ':';
                $reusableString .= $workitem -> getSummary();
                $subject = 'Commit Failure - ' . $reusableString;
                $body =  'Otto says: No Commit for you!';
                $body .= '<br/><br/>Your Commit - ';
                $body .= $reusableString;
                $body .=" failed the Autotester!";
                $body .= '<br><br>See test results <a href="http://bit.ly/jGfIkj">here</a> ';
                $body .= 'Please look at the test results and determine if you need to modify your commit.';
                $body .= 'You can type "@faq CommitTests" in the Journal for more information.';
                $body .= '<br/><br/>-worklist.net';
            break;
        }

    
        $current_user = new User();
        $current_user->findUserById(getSessionUserId());
        if($recipients) {
            foreach($recipients as $recipient) {
                /**
                 *  If there is need to get a new list of users
                 *  just add a get[IDENTIFIER]Id function to
                 *  workitem.class.php that returns a single user id
                 *  or an array with user ids */
                $method = 'get' . ucfirst($recipient) . 'Id';
                $recipientUsers=$workitem->$method();
                if(!is_array($recipientUsers)) {
                    $recipientUsers=array($recipientUsers);
                }
                foreach($recipientUsers as $recipientUser) {
                    if($recipientUser>0) {
                        //Does the recipient exists
                        $rUser = new User();
                        $rUser->findUserById($recipientUser);

                        if(($username = $rUser->getUsername())){
                            // Check to see if user doesn't want to be notified (if user is recipient, doesn't have check on settings and not forced to receive then exclude)
                            if ( $current_user->getUsername() == $username ) {
                                if ( ! Notification::isNotified($current_user->getNotifications(), Notification::SELF_EMAIL_NOTIFICATIONS)
                                    || $includeSelf == false) {
                                    continue;
                                }
                            }

                            // check if we already sending email to this user
                            if(!in_array($username, $emails)){
                                $emails[] = $username;
                            }
                        }
                    }
                }
            }
        }

        if (count($emails) > 0) {
            foreach($emails as $email) {
                if (!send_email($email, $subject, $body, null, $headers)) {
                    error_log("Notification:workitem: send_email failed " . json_encode(error_get_last()));
                }
            }
        }
    }

    /**
     * This function is similar to workitemNotify but sends messages as sms
     *
     * @param Array $options - array of options:
     * type - type of the message
     * emails - list of emails of users you want to send sms to
     * recipients - array of recipients of the message ('creator', 'runner', 'mechanic')
     * workitem - current workitem object to send info about
     **/
    public static function workitemSMSNotify($options) {
        $recipients = isset($options['recipients']) ? $options['recipients'] : null;
        $emails = isset($options['emails']) ? $options['emails'] : array();
        $workitem = $options['workitem'];
        $project_name = isset($options['project_name']) ? $options['project_name'] : null;
        $revision = isset($options['revision']) ? $options['revision'] : null;        
        switch($options['type']) {

            case 'new_bidding':
                $subject = 'Bidding';
                $message = $workitem->getId() . ' '.$workitem->getSummary();
            break;

            case 'new_review':
                $subject = 'Review';
                $message = $workitem->getId() . ' '.$workitem->getSummary();
            break;
            
            case 'new_functional':
                $subject = 'Functional';
                $message = $workitem->getId() . ' '.$workitem->getSummary();
            break;
            
            case 'my_review':
                $subject = 'Review';
                $message = $workitem->getId() . ' '.$workitem->getSummary();
            break;

            case 'my_completed':
                $subject = 'Completed';
                $message = $workitem->getId() . ' '.$workitem->getSummary();
            break;

            case 'bug_found':
                $subject = "Bug for #".$workitem->getBugJobId();
                $message = 'New workitem #' . $workitem->getId();
            break;

            case 'autotestsuccess':
                $subject = "Commit Success";
                $message = 'Commit Success! ' . $project_name . '(v' . $revision . ')';
            break; 
            
            case 'autotestfailure':
                $subject = "Commit Failed";
                $message = 'Commit Failed #' . $project_name . '(v' . $revision . ')';
                $message .= 'See test results here: http://bit.ly/jGfIkj';
            break;            
           
            default:
                trigger_error("Invalid SMS type. Options argument: ".var_export($options, true), E_USER_WARNING);
                return false;
            break; 
            
        }

        $current_user = new User();
        $current_user->findUserById(getSessionUserId());

        $sms_recipients = array();
        
        foreach($emails as $email) {
            //error_log("SMS email (".$options['type']."):".$email);

            // do not send sms to the same user making changes
            if($email != $current_user->getUsername()) {

                $sms_user = new User();
                $sms_user->findUserByUsername($email);
                $sms_recipients[] = $sms_user->getId();
            }
        }
    
        $current_user = new User();
        $current_user->findUserById(getSessionUserId());
        if($recipients) {
            foreach($recipients as $recipient) {
                /**
                 * If there is need to get a new list of users
                 * just add a get[IDENTIFIER]Id function to
                 * workitem.class.php that returns a single user id
                 * an array with user ids
                 **/
                $method = 'get' . ucfirst($recipient) . 'Id';
                $recipientUsers=$workitem->$method();
                if(!is_array($recipientUsers)) {
                    $recipientUsers=array($recipientUsers);
                }
                foreach($recipientUsers as $recipientUser) {
                    if($recipientUser>0) {
                        // check if we already sending email to this user
                        if(!in_array($recipientUser, $sms_recipients)){
                            $sms_recipients[] = $recipientUser;
                        }
                    }
                }
            }
        }

        if(count($sms_recipients) > 0) {
            setlocale(LC_CTYPE, "en_US.UTF-8");
            $esc_subject = escapeshellarg($subject);
            $esc_message = escapeshellarg($message);
            $args = '"'.$subject . '" "' . $message . '" ';
            foreach($sms_recipients as $recipient) {
                $args .= $recipient . ' ';
            }
            $application_path = dirname(dirname(__FILE__)) . '/';
            exec('php ' . $application_path . 'tools/smsnotifications.php '
            . $args . ' > /dev/null 2>/dev/null &');
        }
    }


    /**
    * Function to send an sms message to given user
    *
    * @param User $recipient - user object to send message to
    * @param String $subject - subject of the message
    * @param String $message - actual message content
    */
    public static function sendSMS($recipient, $subject, $message) {
        notify_sms_by_object($recipient, $subject, $message);
        return true;
    }
    
    // get list of past due bids
    public function emailPastDueJobs(){
        $html_start = "<html><head><title>Worklist</title></head><body>";
        $html_end = "</body></html>";
        $html = '';
        $qry = "SELECT w.*, b.*, b.id as bid_id, u.id as runner_id, u.username as runner_email FROM " . WORKLIST . " w LEFT JOIN " . BIDS . " b ON w.id = b.worklist_id".
            " LEFT JOIN " . USERS . " u ON w.runner_id = u.id".
            " WHERE (w.status = 'WORKING' OR w.status = 'REVIEW' OR w.status = 'PRE-FLIGHT' OR w.status = 'COMPLETED')".
            " AND b.accepted = 1 AND b.past_notified = '0000-00-00 00:00:00' AND b.withdrawn = 0";
        $worklist = mysql_query($qry) or (error_log("select past due bids error: " . mysql_error()) && die);
        $wCount = mysql_num_rows($worklist);
        if($wCount > 0){
            $counter = 0;
            while ($row = mysql_fetch_assoc($worklist)) {
                if (strtotime($row['bid_done']) < time()) {
                    $counter++;
                    $subject = "Job #" . $row['worklist_id'] . " is now past due!";
                    $html = $html_start;
                    $html .= "<p>------------------------------------------</p>";
                    $html .= "<p>Job <a href='" . SERVER_URL . "workitem.php?job_id=" . $row['worklist_id'] . "&action=view'>#" . $row['id'] . "</a> (" . $row['summary'] . ")</p>";
                    $html .= "<p>Done by time has now passed.<br />";
                    $html .= "Job url: " . SERVER_URL . "workitem.php?job_id=" . $row['worklist_id'] . "</p>";
                    $html .= "<p>- Worklist.net</p>";
                    $html .= "<p>------------------------------------------</p>";
                    $html .= $html_end;

                    send_email($row['email'], $subject, $html);
                    send_email($row['runner_email'], $subject, $html);
                    // now need to set this notified flag to now date
                    $bquery = "UPDATE ".BIDS." SET past_notified = NOW() WHERE id = ".$row['bid_id'];
                    $queryB = mysql_query($bquery)or (error_log("update past due bids error: " . mysql_error()) && die);
                }
            }
        }
    }
    
    // HOME PAGE CONTACT/ADD PROJECT FORM EMAIL
    public function emailContactForm($name, $email, $phone, $proj_name, $proj_desc){
        $subject = "Worklist - Add Project Contact Form";
        $html = "<html><head><title>Worklist - Add Project Contact Form</title></head><body>";
        $html .= "<h2>Project Contact Information:</h2>";
        $html .= "<p><strong>Name:</strong> " . $name . "</p>";
        $html .= "<p><strong>Email:</strong> " . $email . "</p>";
        $html .= "<p><strong>Phone #:</strong> " . $phone . "</p>";
        $html .= "<p><strong>Project Name:</strong> " . $proj_name . "</p>";
        $html .= "<p><strong>Project Desctiption:</strong><br />" . nl2br($proj_desc) . "</p>";
        $html .= "</body></html>";
        if(send_email("contact@worklist.net", $subject, $html)){
            return true;
        }
        return false;
    }
    
    public static function autoTestNofications($workItemId,$result,$revision) {
        $workItem = new workItem;
        $workItem->loadById($workItemId);
        $project = new Project();
        $project->loadById($workItem->getProjectId());
        $emails = self::getNotificationEmails(self::MY_AUTOTEST_NOTIFICATIONS,$workItem);
        $typeOfNotification = ($result=='success') ? 'autotestsuccess' : 'autotestfailure';
        $options = array('type' => $typeOfNotification,
        'workitem' => $workItem,
        'emails' => $emails,
        'project_name' => $project->getName(),
        'revision' => $revision);
        self::workitemNotify($options);
        self::workitemSMSNotify($options);   
    }    
    
    public function notifyBudget($amount, $reason, $giver, $receiver) {
        if (!$amount || $amount < 0.01 || ! $giver || ! $receiver) {
            return false;
        }

        $subject = "Worklist - You've Got Budget!";
        $html = "<html><head><title>Worklist - You've Got Budget!</title></head><body>";
        $html .= "<h2>You've Got Budget!</h2>";
        $html .= "<p>Hello " . $receiver->getNickname() . ",<br />" . $giver->getNickname() . " granted you $" . number_format($amount, 2) .
        " of budget for: " . $reason . "</p>";
        $html .= "<p>Don't spend it all in one place!</p><p>- Worklist.net</p>";
        $html .= "</body></html>";

        if (!send_email($receiver->getUsername(), $subject, $html)) {
            error_log("Notification:workitem: send_email failed " . json_encode(error_get_last()));
        }
    }

    public function notifySMSBudget($amount, $reason, $giver, $receiver) {
        if (!$amount || $amount < 0.01 || ! $giver || ! $receiver) {
            return false;
        }
        
        $message = $giver->getNickname() . ' granted you \$' . number_format($amount, 2) . ' of budget for: ' . $reason;
        
        setlocale(LC_CTYPE, "en_US.UTF-8");
        $esc_subject = escapeshellarg('Budget Granted');
        $esc_message = escapeshellarg($message);
        $args = '"'.$esc_subject . '" "' . $esc_message . '" ';

        $args .= $receiver->getId() . ' ';
        
        $application_path = dirname(dirname(__FILE__)) . '/';
        exec('php ' . $application_path . 'tools/smsnotifications.php '
        . $args . ' > /dev/null 2>/dev/null &');
        
    }

}
