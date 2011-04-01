<?php

require_once 'send_email.php';
require_once 'workitem.class.php';
require_once 'functions.php';
require_once 'lib/Sms.php';

/**
 * This class is responsible for working with notification
 * subscriptions
 */
class Notification{

    const REVIEW_NOTIFICATIONS = 1;
    const BIDDING_NOTIFICATIONS = 2;
    const MY_REVIEW_NOTIFICATIONS = 4;
    const MY_COMPLETED_NOTIFICATIONS = 8;
    const PING_NOTIFICATIONS = 16;
    const MY_BIDS_NOTIFICATIONS = 32;
    
 
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
        case self::REVIEW_NOTIFICATIONS :
        case self::BIDDING_NOTIFICATIONS :
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
            $users=implode(",", array($workitem->getCreatorId(), $workitem->getRunnerId(), $workitem->getMechanicId()));
            $sql = "SELECT u.username FROM `" . USERS . "` u WHERE u.notifications & $flag != 0 AND u.id!=" .getSessionUserId(). " AND u.id IN({$users})";
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
            case 'REVIEW':
                $emails = self::getNotificationEmails(self::REVIEW_NOTIFICATIONS);
                $myEmails= self::getNotificationEmails(self::MY_REVIEW_NOTIFICATIONS,$workitem);
                $myEmails=array_diff($myEmails,$emails); // Remove already existing emails in $emails list
                $emails=array_merge($emails,$myEmails);
                $emails=array_unique($emails);
                $options = array('type' => 'new_review',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);
                self::workitemSMSNotify($options);
                // Send SMS to users who have "My jobs set to review" checked
                $options = array('type' => 'my_review',
                    'workitem' => $workitem,
                    'emails' => $myEmails);
                self::workitemSMSNotify($options);
                break;
            case 'BIDDING':
                $emails = self::getNotificationEmails(self::BIDDING_NOTIFICATIONS);
                $options = array('type' => 'new_bidding',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);
                self::workitemSMSNotify($options);
                break;
            case 'COMPLETED':
                $emails= self::getNotificationEmails(self::MY_COMPLETED_NOTIFICATIONS,$workitem);
                $options = array('type' => 'my_completed',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemSMSNotify($options);
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
     * example: 'who' and 'comment' - if we send notification about new comment
     */
    public static function workitemNotify($options, $data = null) {

        $recipients = isset($options['recipients']) ? $options['recipients'] : null;
        $emails = isset($options['emails']) ? $options['emails'] : array();

        $workitem = $options['workitem'];
        $itemId = $workitem -> getId();
        $itemLink = '<a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>#' . $itemId
                    . '</a> (' . $workitem -> getSummary() . ')';
        $itemTitle = '#' . $itemId  . ' (' . $workitem -> getSummary() . ')';
        $body = '';
        $subject = '';
        $headers="";
        switch ($options['type']) {
            case 'comment':
                $subject = 'Comment: ' . $itemTitle;
                $body  = 'New comment was added to the item ' . $itemLink . '.<br>';
                $body .= $data['who'] . ' says:<br />'
                         . $data['comment'];
            break;
            
            case 'fee_added':
                $subject = 'Fee: ' . $itemTitle;
                $body = 'New fee was added to the item ' . $itemLink . '.<br>'
                        . 'Who: ' . $data['fee_adder'] . '<br>'
                        . 'Amount: ' . $data['fee_amount'];
            break;

            case 'bid_accepted':
                $subject = 'Accepted: ' . $itemTitle;
                $body = 'Your bid was accepted for ' . $itemLink . '<br/>'
                        . 'Promised by: ' . $_SESSION['nickname'];
            break;

            case 'bid_placed':
                $subject = 'Bid: ' . $itemTitle;
                $body =  'New bid was placed for ' . $itemLink . '<br>'
                        . 'Details of the bid:<br>'
                        . 'Bidder Email: ' . $_SESSION['username'] . '<br>'
                        . 'Done By: ' . $data['done_by'] . '<br>'
                        . 'Bid Amount: ' . $data['bid_amount'] . '<br>'
                        . 'Notes: ' . $data['notes'] . '<br>';
                $urlacceptbid = '<br><a href=' . SERVER_URL . 'workitem.php';
                $urlacceptbid .= '?job_id=' . $itemId . '&bid_id=' . $data['bid_id'] . '&action=accept_bid>Click here to accept bid.</a>';
                $body .=  $urlacceptbid;
            break;

            case 'bid_updated':
                $subject = 'Bid: ' . $itemTitle;
                $body = 'Bid Updated for ' . $itemLink . '<br>'
                        . 'Details of the bid:<br>'
                        . 'Bidder Email: ' . $_SESSION['username'] . '<br>'
                        . 'Done By: ' . $data['done_by'] . '<br>'
                        . 'Bid Amount: ' . $data['bid_amount'] . '<br>'
                        . 'Notes: ' . $data['notes'] . '<br>';
                $urlacceptbid = '<br><a href=' . SERVER_URL . 'workitem.php';
                $urlacceptbid .= '?job_id=' . $itemId . '&bid_id=' . $data['bid_id'] .
                                 '&action=accept_bid>Click here to accept bid.</a>';
                $body .=  $urlacceptbid;
            break;

            case 'modified':
                $subject = "Modified: ".$itemTitle;
                $body = $_SESSION['nickname'] . ' updated item ' . $itemLink . '<br>'
                        . $data['changes'];
            break;

            case 'new_bidding':
                $subject = "Bidding: ".$itemTitle;
                $body =  "Summary:<br>".$workitem -> getSummary() .
                         '<br><br>Notes:<br>'.$workitem->getNotes();
                $body .= '<br><br>You are welcome to bid <a href='.SERVER_URL.
                         'workitem.php?job_id=' . $itemId . '>here</a>.';
            break;

            case 'new_review':
                $subject = "Review: ".$itemTitle;
                $body =  'New item is available for review: ' . $itemLink . '<br>';
            break;

            case 'suggested':
                $subject = "Suggested: " . $itemId . "(".$workitem -> getSummary().")" ;
                $body =  'Summary: ' . $workitem -> getSummary() . '<br>';
                $body.= 'Notes: ' . $data['notes'] . '<br>';
            break;
        }

    
        $current_user = new User();
        $current_user->findUserById(getSessionUserId());
        if($recipients) {
            foreach($recipients as $recipient) {
                if($recipient == 'projectRunners') {
                    $runners = $workitem->getProjectRunners();
                    foreach($runners as $runner) {
                        $recipientUser = new User();
                        $recipientUser->findUserById($runner);
                        if($username = $recipientUser->getUsername()) {
                            // check if we already sending email to this user
                            if(!in_array($username, $emails)) {
                                $emails[] = $username;
                            }
                        }
                    }
                } else {
                    $recipientUser = new User();
                    $method = 'get' . ucfirst($recipient) . 'Id';
                    $recipientUser->findUserById($workitem->$method());
                    if(($username = $recipientUser->getUsername())) {
                        // check if we already sending email to this user
                        if(!in_array($username, $emails)) {
                            $emails[] = $username;
                        }
                    }
                }
            }
        }

        if(count($emails) > 0) {
            foreach($emails as $email) {
                if(!sl_send_email($email, $subject, $body, null, $headers)) {
                    error_log("Notification:workitem: sl_send_email failed");
                }
            }
        }
    }

    /**
     * This function is similar to woritemNotify but sends messages as sms
     *
     * @param Array $options - array of options:
     * type - type of the message
     * emails - list of emails of users you want to send sms to
     * workitem - current workitem object to send info about
     */
    public static function workitemSMSNotify($options) {

        $emails = isset($options['emails']) ? $options['emails'] : array();
        $workitem = $options['workitem'];
        switch($options['type']) {

            case 'new_bidding':
                $subject = 'Bidding';
                $message = 'Workitem #' . $workitem->getId() . ' is available for bidding';
            break;

            case 'new_review':
                $subject = 'Review';
                $message = 'Workitem #' . $workitem->getId() . ' is available for review';
            break;

            case 'my_review':
                $subject = 'Review';
                $message = 'Workitem #' . $workitem->getId() . ' is available for review';
            break;

            case 'my_completed':
                $subject = 'Completed';
                $message = 'Workitem #' . $workitem->getId() . ' is now completed';
            break;
        }

        $current_user = new User();
        $current_user->findUserById(getSessionUserId());
        $sms_recipients = array();
        foreach($emails as $email) {
            error_log("SMS email (".$options['type']."):".$email);

            // do not send sms to the same user making changes
            if($email != $current_user->getUsername()) {

                $sms_user = new User();
                $sms_user->findUserByUsername($email);
                $sms_recipients[] = $sms_user->getId();
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
        try {
            $config = Zend_Registry::get('config')->get('sms', array());
            if ($config instanceof Zend_Config) {
                $config = $config->toArray();
            }
            $smsMessage = new Sms_Message($recipient, $subject, $message);
            Sms::send($smsMessage, $config);
        } catch (Sms_Backend_Exception $e) {
        }
    }
} 
