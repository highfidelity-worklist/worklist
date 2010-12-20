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

    /**
     *  Sets flags using list of integers passed as arguments
     *
     * @return resulting integer with combined flags
     */
    public static function setFlags(){
            $result = 0;
            foreach(func_get_args() as $flag){
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
    public static function isNotified($value, $flag = self::REVIEW_NOTIFICATIONS){
            $result = ($value & $flag) === $flag;
            return $result;
    }

    /**
     * Get list of users who have given flag set
     * List is returned only for active users!
     * @param $flag flag to compare with
     * @return list of users with given flag
     */
    public static function getNotificationEmails($flag = self::REVIEW_NOTIFICATIONS){

        $result = array();
        $sql = "SELECT u.username FROM `" . USERS . "` u WHERE u.notifications & $flag != 0";

        $res = mysql_query($sql);
        if($res){
            while($row = mysql_fetch_row($res)){
                $result[] = $row[0];
            }
        }
        return $result;
    }

    /**
     * Notifications for workitem statuses
     *
     * @param String $status - status of workitem
     */
    public static function statusNotify($workitem){
        switch($workitem->getStatus()){
            case 'REVIEW':
                $emails = self::getNotificationEmails(self::REVIEW_NOTIFICATIONS);
                $options = array('type' => 'new_review',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);
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
    public static function workitemNotify($options, $data = null){


        $recipients = isset($options['recipients']) ? $options['recipients'] : null;
        $emails = isset($options['emails']) ? $options['emails'] : array();

	$workitem = $options['workitem'];
	$itemId = $workitem -> getId();
	$itemLink = '<a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>#' . $itemId
			    . '</a> (' . $workitem -> getSummary() . ')';
	$itemTitle = '#' . $itemId  . ' (' . $workitem -> getSummary() . ')';
	$body = '';
	$subject = '';

	switch($options['type']){

	    case 'comment':

		  $subject = 'LoveMachine New comment: ' . $itemTitle;
		  $body = 'New comment was added to the item ' . $itemLink . '.<br>';
		  $body .= $data['who'] . ' says:<br />'
			    . $data['comment'];
	    break;

	    case 'fee_added':

		  $subject = 'LoveMachine Fee added: ' . $itemTitle;
		  $body = 'New fee was added to the item ' . $itemLink . '.<br>'
			. 'Who: ' . $data['fee_adder'] . '<br>'
			. 'Amount: ' . $data['fee_amount'];
	    break;

	    case 'bid_accepted':

		  $subject = 'LoveMachine Bid accepted: ' . $itemTitle;
		  $body = 'Cha-ching! Your bid was accepted for ' . $itemLink . '<br>'
			. 'Promised by: ' . $_SESSION['nickname'];
	    break;

	    case 'bid_placed':

		  $subject = 'LoveMachine New bid: ' . $itemTitle;
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

		  $subject = 'LoveMachine Bid Updated: ' . $itemTitle;
		  $body =  'Bid Updated for ' . $itemLink . '<br>'
			 . 'Details of the bid:<br>'
			 . 'Bidder Email: ' . $_SESSION['username'] . '<br>'
			 . 'Done By: ' . $data['done_by'] . '<br>'
			 . 'Bid Amount: ' . $data['bid_amount'] . '<br>'
			 . 'Notes: ' . $data['notes'] . '<br>';

		  $urlacceptbid = '<br><a href=' . SERVER_URL . 'workitem.php';
		  $urlacceptbid .= '?job_id=' . $itemId . '&bid_id=' . $data['bid_id'] . '&action=accept_bid>Click here to accept bid.</a>';
		  $body .=  $urlacceptbid;
	    break;

	    case 'modified':

		  $subject = "LoveMachine Item modified: ".$itemTitle;
		  $body =  $_SESSION['nickname'] . ' updated item ' . $itemLink . '<br>'
			 . $data['changes'];
	    break;

            case 'new_bidding':

                  $subject = "New job for bid in LoveMachine Worklist: ".$itemTitle;
                  $body =  "Summary:<br>".$workitem -> getSummary() . '<br><br>Notes:<br>'.$workitem->getNotes();
                  $body .= '<br><br>You are welcome to bid <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.';

	    break;

            case 'new_review':

		  $subject = "New LoveMachine Item in review: ".$itemTitle;
		  $body =  'New item is available for review: ' . $itemLink . '<br>';
	    break;
	}

	$body .= '<p>Love,<br/><br/>Eliza @ the LoveMachine</p>';

        $current_user = new User();
        $current_user->findUserById(getSessionUserId());
        if($recipients){
            foreach($recipients as $recipient){
                    $recipientUser = new User();
                    $method = 'get' . ucfirst($recipient) . 'Id';
                    $recipientUser->findUserById($workitem->$method());

                    if(($username = $recipientUser->getUsername())){

                            // check if we already sending email to this user
                            if(!in_array($username, $emails)){
                                    $emails[] = $username;
                            }
                    }
            }
        }

        if(count($emails) > 0){
            $to = '';
            foreach($emails as $email){

                // do not send mail to the same user making changes
                if($email != $current_user->getUsername()){
                    $to .= $email . ', ';
                }
            }

            $to = substr_replace($to, "", -2);
            $headers = 'BCC: ' . $to . PHP_EOL;
            sl_send_email('love@sendlove.us', $subject, $body, null, $headers);
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
    public static function workitemSMSNotify($options){

        $emails = isset($options['emails']) ? $options['emails'] : array();
        $workitem = $options['workitem'];
        switch($options['type']){

            case 'new_bidding':
                $subject = 'New bidding';
                $message = 'Workitem #' . $workitem->getId() . ' is available for bidding';
            break;

            case 'new_review':
                $subject = 'New review';
                $message = 'Workitem #' . $workitem->getId() . ' is available for review';
            break;
        }

        $current_user = new User();
        $current_user->findUserById(getSessionUserId());
        $sms_recipients = array();
        foreach($emails as $email){

            // do not send sms to the same user making changes
            if($email != $current_user->getUsername()){

                $sms_user = new User();
                $sms_user->findUserByUsername($email);
                $sms_recipients[] = $sms_user->getId();
            }
	}

        if(count($sms_recipients) > 0){

            setlocale(LC_CTYPE, "en_US.UTF-8");
            $esc_subject = escapeshellarg($subject);
            $esc_message = escapeshellarg($message);
            $args = '"'.$subject . '" "' . $message . '" ';
            foreach($sms_recipients as $recipient){
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
    public static function sendSMS($recipient, $subject, $message){
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
