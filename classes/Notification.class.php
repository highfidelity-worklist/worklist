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
            return (bool) $result;
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
            $sql = "SELECT u.username 
                FROM `" . USERS . "` u 
                WHERE u.notifications & $flag != 0 
                  AND u.id! = " . getSessionUserId() . " 
                  AND u.id IN({$users}) 
                  AND u.is_active = 1";
            $res = mysql_query($sql);
            if($res) {
                while($row = mysql_fetch_row($res)) {
                    $result[] = $row[0];
                }
            }
            break;
        case self::REVIEW_EMAIL_NOTIFICATIONS :
        case self::BIDDING_EMAIL_NOTIFICATIONS :
            $uid = getSessionUserId();
            $sql = "SELECT u.username 
                FROM `" . USERS . "` u 
                WHERE ((u.notifications & $flag != 0 && u.id != " . $uid . ") 
                      OR ((u.notifications & $flag) != 0 AND (u.notifications & " . self::SELF_EMAIL_NOTIFICATIONS . ") != 0 && u.id = " . $uid . "))
                  AND u.is_active = 1";
            $res = mysql_query($sql);
            if($res) {
                while($row = mysql_fetch_row($res)) {
                    $result[] = $row[0];
                }
            }
            break;

        case self::REVIEW_NOTIFICATIONS :
        case self::BIDDING_NOTIFICATIONS :
            $sql = "SELECT u.username 
                FROM `" . USERS . "` u 
                WHERE u.notifications & $flag != 0 
                  AND u.is_active = 1";
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
            $sql = "SELECT u.username 
                FROM `" . USERS . "` u 
                WHERE u.notifications & $flag != 0 
                  AND u.id! = " . getSessionUserId() . " 
                  AND u.id IN({$users}) 
                  AND u.is_active = 1";
            $res = mysql_query($sql);
            if($res) {
                while($row = mysql_fetch_row($res)) {
                    $result[] = $row[0];
                }
            }
            break;
        case self::MY_AUTOTEST_NOTIFICATIONS:
            $users=implode(",", array($workitem->getCreatorId(), $workitem->getRunnerId(), $workitem->getMechanicId()));
            $sql = "SELECT u.username 
                FROM `" . USERS . "` u 
                WHERE u.id IN({$users}) 
                  AND u.is_active = 1";
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
                $emails=array_unique($emails);
                $options = array('type' => 'new_review',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);

                $emails = self::getNotificationEmails(self::REVIEW_NOTIFICATIONS);
                $myEmails = self::getNotificationEmails(self::MY_REVIEW_NOTIFICATIONS,$workitem);
                $myEmails = array_diff($myEmails, $emails); // Remove already existing emails in $emails list
                $myEmails = array_unique($myEmails);
                $emails = array_unique($emails);
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
     * @param boolean $includeSelf - force user receive email from self generated action
     * example: 'who' and 'comment' - if we send notification about new comment
     */
    public static function workitemNotify($options, $data = null, $includeSelf = true) {

        $recipients = isset($options['recipients']) ? $options['recipients'] : null;
        $emails = isset($options['emails']) ? $options['emails'] : array();

        $workitem = $options['workitem'];
        $userstats = isset($options['userstats']) ? $options['userstats'] : null;
       
        if (isset($options['project_name'])) {
            $project_name = $options['project_name'];
        } else {
            try {
                $project = new Project();
                $project->loadById($workitem->getProjectId());
                $project_name = $project->getName();
            } catch (Exception $e) {
                error_log($e->getMessage() . " Workitem: #" . $workitem->getId() . " " . " has an invalid project id:" . $workitem->getProjectId());
                $project_name = "";
            }
            
        }

        $revision = isset($options['revision']) ? $options['revision'] : null;
        
        $itemId = $workitem -> getId();
        $itemLink = '<a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>#' . $itemId
                    . '</a>  ' . $workitem -> getSummary() . ' ';
        $itemTitle = '#' . $itemId  . ' (' . $workitem -> getSummary() . ')';
        $itemTitleWithProject = '#' . $itemId  . ': ' . $project_name . ': (' . $workitem -> getSummary() . ')';
        $body = '';
        $subject = '#' . $itemId . ' ' . html_entity_decode($workitem -> getSummary(), ENT_QUOTES);
        $from_address = '<noreply-'.$project_name.'@worklist.net>';
        $headers=array('From' => '"'.$project_name.'-'.strtolower( $workitem -> getStatus() ).'" '.$from_address);
        switch ($options['type']) {
            case 'comment':
                $headers['From'] = '"' . $project_name . '-comment" ' . $from_address;
                $body  = 'New comment was added to the item ' . $itemLink . '.<br>';
                $body .= $data['who'] . ' says:<br />'
                      . $data['comment'] . '<br /><br />'
                      . 'Project: ' . $project_name . '<br />'
                      . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                      if($workitem->getRunner() != '') {
                          $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                      }
                      if($workitem->getMechanic() != '') {
                          $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                      }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;
            
            case 'fee_added':
                if($workitem->getStatus() != 'DRAFT') {
                $headers['From'] = '"' . $project_name . '-fee added" ' . $from_address;
                $body = 'New fee was added to the item ' . $itemLink . '.<br>'
                        . 'Who: ' . $data['fee_adder'] . '<br/>'
                        . 'Amount: ' . $data['fee_amount'] . '<br/>'
                        . '<div>Fee Notes: ' . $data['fee_desc'] . '</div><br/><br/>'
                        . 'Project: ' . $project_name . '<br/>'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                        if($workitem->getRunner() != '') {
                            $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                        }
                        if($workitem->getMechanic() != '') {
                            $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                }
            break;
            
            case 'fee_deleted':
                if($workitem->getStatus() != 'DRAFT') {
                    $headers['From'] = '"' . $project_name . '-fee deleted" ' . $from_address;
                    $body = "<p>Your fee has been deleted by: ".$_SESSION['nickname']."<br/><br/>";
                    $body .= "If you think this has been done in error, please contact the job Runner.</p>";
                    $body .= 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                    }
                    $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                    . 'You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                    . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                }
            break;

            case 'tip_added':
                $headers['From'] = '"' . $project_name . '-tip added" ' . $from_address;
                $body = $data['tip_adder'] . ' tipped you $' . $data['tip_amount'] . ' on job ' . $itemLink . ' for:<br><br>' . $data['tip_desc'] . '<br><br>Yay!' . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                       if($workitem->getRunner() != '') {
                           $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                       }
                       if($workitem->getMechanic() != '') {
                           $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                       }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                break;

            case 'bid_accepted':
                $headers['From'] = '"' . $project_name . '-bid accepted" ' . $from_address;
                $body = 'Your bid was accepted for ' . $itemLink . '<br/><br />'
                        . 'Promised by: ' . $_SESSION['nickname'] . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                        if($workitem->getRunner() != '') {
                            $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                        }
                        if($workitem->getMechanic() != '') {
                            $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }

                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'The job can be viewed <a href=' . SERVER_URL . 'workitem.php?job_id=' . $itemId . '>here</a><br /><br />';

                // render the github branch-created-sub template if necessary
                if (!empty($data) && array_key_exists('branch_name', $data)) {
                    $template = 'branch-created-sub';
                    include(dirname(__FILE__) . "/../email/en.php");

                    $replacedTemplate = !empty($data) ?
                        templateReplace($emailTemplates[$template], $data) :
                        $emailTemplates[$template];

                    $body .= $replacedTemplate['body'];
                }

                $body .= '<br /><a href="' . SERVER_URL . '">www.worklist.net</a>';

            break;

            case 'bid_placed':
            	$userstats->setItemsPerPage(3);
            	$projectId = $workitem->getProjectId();;
            	$jobsInfo = $userstats->getUserItemsForASpecificProject('DONE', $projectId);
            	$lastThreeJobs = $jobsInfo['joblist'];
            	$workItemUrl = '<a href="' . SERVER_URL . 'workitem.php';
            	//create the last three jobs and link them to those Jobs.
            	foreach ($lastThreeJobs as $row){
            		$jobs .= $workItemUrl;
            		$jobs .= '?job_id=' . $row['id'] . '&action=view">#' . $row['id'] . '</a>' . ' - ' . $row['summary'] . '<br />';
            	}
            	//if no Jobs then display 'None'
            	if (!$jobs){
            		$jobs = 'None <br />';
            	}
      
            	//now get total jobs and total jobs and create links
            	$totalJobs = $workItemUrl;
            	$totalJobs .= '?job_id=' . $workitem->getId() . '&action=view&userinfotoshow=' . $_SESSION['userid'] . '">' . $userstats->getTotalJobsCount() . '</a><br />';
            	$totalActiveJobs = $workItemUrl;
            	$totalActiveJobs .= '?job_id=' . $workitem->getId() . '&action=view&userinfotoshow=' . $_SESSION['userid'] . '">' . $userstats->getActiveJobsCount() . '</a><br />';
                
            	$headers['From'] = '"' . $project_name . '-new bid" ' . $from_address;
                $body =  'New bid was placed for ' . $itemLink . '<br /><br />'
                    . 'Amount: $' . number_format($data['bid_amount'], 2) . '<br />'
                    . 'Done In: ' . $data['done_in'] . '<br />'
                    . 'Expires: ' . $data['bid_expires'] . '<br /><br />'
                    . 'Bidder Name: <a href="mailto:' . $_SESSION['nickname'] . '">' . $_SESSION['nickname'] . '</a><br /><br />'
                    . 'Bidder Email: <a href="mailto:' . $_SESSION['username'] . '">' . $_SESSION['username'] . '</a><br /><br />'
                    . 'Notes: ' . $data['notes'] . '<br /><br />'
                    . 'Total Jobs: ' . $totalJobs
                    . 'Active Jobs: ' . $totalActiveJobs
                    . 'Last 3 Jobs for ' . $project_name . ':<br />'
                    . $jobs . '<br />'
                    . 'Project: ' . $project_name . '<br />'
                    . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                    }
                $body .= 'Notes: '. $workitem->getNotes() . '<br /><br />';

                $urlAcceptBid  = '<br />' . $workItemUrl;
                $urlAcceptBid .= '?job_id=' . $itemId . '&bid_id=' . $data['bid_id'] . '&action=view_bid">Click here to accept bid.</a>';
                $body .=  $urlAcceptBid;
            break;

            case 'bid_updated':
                $headers['From'] = '"' . $project_name . '-bid updated" ' . $from_address;
                $body = 'Bid updated for ' . $itemLink . '<br /><br/>'
                    . 'Amount: $' . number_format($data['bid_amount'], 2) . '<br />'
                    . 'Done In: ' . $data['done_in'] . '<br />'
                    . 'Expires: ' . $data['bid_expires'] . '<br /><br />'
                    . 'Bidder Email: <a href="mailto:' . $_SESSION['username'] . '">' . $_SESSION['username'] . '</a><br /><br />'
                    . 'Notes: ' . $data['notes'] . '<br /><br />'
                    . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />';
                $urlacceptbid  = '<br /><a href="' . SERVER_URL . 'workitem.php';
                $urlacceptbid .= '?job_id=' . $itemId . '&bid_id=' . $data['bid_id'] .
                                 '&action=view_bid">Click here to accept bid.</a>';
                $body .=  $urlacceptbid;
            break;

            case 'bid_discarded':
                $headers['From'] = '"' . $project_name . '-bid not accepted" ' . $from_address;
                $body = "<p>Hello " . $data['who'] . ",</p>";
                $body .= "<p>Thanks for adding your bid to <a href='".SERVER_URL."workitem.php?job_id=".$itemId."'>#".$itemId."</a> '" . $workitem -> getSummary() . "'. This job has just been filled by another mechanic.</br></p>";
                $body .= "There is lots of work to be done so please keep checking the <a href='".SERVER_URL."'>worklist</a> and bid on another job soon!</p>";
                $body .= "<p>Hope to see you in the Workroom soon. :)</p>";
            break;

            case 'modified-functional':
                $from_changes = "";
                if (!empty($options['status_change'])) {
                    $from_changes = $options['status_change'];
                }
                if (isset($options['job_changes'])) {
                    if (count($options['job_changes']) > 0) {
                        $from_changes .= $options['job_changes'][0];
                        if (count($options['job_changes']) > 1) {
                            $from_changes .= ' +other changes';
                        }
                    }
                }

                if ($from_changes) {
                    $headers['From'] = '"' . $project_name . $from_changes . '" ' . $from_address;
                } else {
                    $status_change = '-' . strtolower($workitem->getStatus());
                    $headers['From'] = '"' . $project_name . $status_change . '" ' . $from_address;
                }
                $body = $_SESSION['nickname'] . ' updated item ' . $itemLink . '<br>'
                    . $data['changes'] . '<br /><br />'
                    . 'Project: ' . $project_name . '<br />'
                    . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />'

                    . 'Runner: ';
                if ($workitem->getRunnerId()) $body .= $workitem->getRunner()->getNickname();
                $body.= '<br />'

                    . 'Mechanic: ';
                if ($workitem->getMechanicId()) $body .= $workitem->getMechanic()->getNickname();

                $body.= '<br /><br />'
                    . 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                    . 'You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                    . '<a href="' . SERVER_URL . '">www.worklist.net</a>';
            break;
            
            case 'modified':
                if($workitem->getStatus() != 'DRAFT') {
                    $from_changes = "";
                    if (!empty($options['status_change'])) {
                        $from_changes = $options['status_change'];
                    }
                    if (isset($options['job_changes'])) {
                        if (count($options['job_changes']) > 0) {
                            $from_changes .= $options['job_changes'][0];
                            if (count($options['job_changes']) > 1) {
                                $from_changes .= ' +other changes';
                            }
                        }
                    }
                    if (!empty($from_changes)) {
                        $headers['From'] = '"' . $project_name . $from_changes . '" ' . $from_address;
                    } else {
                        $status_change = '-' . strtolower($workitem->getStatus());
                        $headers['From'] = '"' . $project_name . $status_change . '" ' . $from_address;
                    }
                    $body = $_SESSION['nickname'] . ' updated item ' . $itemLink . '<br>'
                        . $data['changes'] . '<br /><br />'
                        . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                    }
                    $body .= 'Notes: '. $workitem->getNotes() . '<br /><br />'
                        . 'You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                        . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                }
            break;

            case 'new_bidding':
                $body = "Summary: " . $itemLink . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                   $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You are welcome to bid the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;

            case 'new_review':
                $body = "New item is available for review: " . $itemLink . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                    $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                
            break;
            case 'new_functional':
                $body = "New item is available for functional: " . $itemLink ;
                $body.= '<br/><br/>Project: ' . $project_name;
                $body.= '<br/>Creator: ' . $workitem->getCreator()->getNickname();

                $body.= '<br/>Runner: ';
                if ($workitem->getRunnerId()) $body.= $workitem->getRunner()->getNickname();

                $body.= '<br/>Mechanic: ';
                if ($workitem->getMechanicId()) $body.= $workitem->getMechanic()->getNickname();

                $body.= '<br><br>Notes:<br>' .$workitem->getNotes();
                $body.= '<br><br>You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />';
                $body.= '<br><br><a href="' . SERVER_URL . '">www.worklist.net</a>';
                
            break;
            case 'bug_found':
                $headers['From'] = '"' . $project_name . '-bug" ' . $from_address;
                
                $body = "<p>A bug has been reported related to item #".
                $workitem->getBugJobId().
                            " : ".$workitem->getBugJobSummary()."</p>";

                $body .= "<br/><p>New item #" . $itemId . " summary: " .
                            $workitem -> getSummary() . ".</p>";
                $body .= "<br/><p>Notes:" . $workitem->getNotes(). "</p><br/><br/>";
                $body .= '<br/><p>Project: ' . $project_name . "";
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname(). "";
                if($workitem->getRunner() != '') {
                    $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . "";
                }
                if($workitem->getMechanic() != '') {
                   $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname() . "</p><br/>";
                }
                $body .= '<br><br><a href=' . SERVER_URL . 'workitem.php?job_id=' .
                            $itemId . '>View new item</a>.';
            break;
            case 'suggested':
                $body =  'Summary: ' . $itemLink . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                    $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;
            case 'suggestedwithbid':
                $body =  'Summary: ' . $itemLink . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes: ' . $workitem->getNotes() . '<br /><br />'
                . 'You are welcome to bid the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;                       
            break;
            case 'autotestsuccess':
                $reusableString = $project_name . '(v' . $revision . ')';
                $reusableString .= '#';
                $reusableString .= $itemId;
                $reusableString .= ':';
                $reusableString .= $workitem -> getSummary();
                $headers['From'] = '"' . $project_name . '-committed" ' . $from_address;
                $body =  'Congrats!';
                $body .= '<br/><br/>Your Commit - ' . $reusableString . ' was a success!';
                $body .= '<br><br>Click <a href="';
                $body .= 'http://svn.worklist.net/revision.php?repname=';
                $body .= $project_name;
                $body .= '&rev=';
                $body .= $revision;
                $body .= '">here</a>';
                $body .= ' to see the webSVN commit notes.';
                $body .= '<br/><br/><a href="http://www.worklist.net">www.worklist.net</a>';
            break; 
            case 'autotestfailure':
                $reusableString = $project_name . '(v' . $revision . ')';
                $reusableString .= '#';
                $reusableString .= $itemId;
                $reusableString .= ':';
                $reusableString .= $workitem -> getSummary();
                $headers['From'] = '"' . $project_name . '-commit fail" ' . $from_address;
                $body =  'Otto says: No Commit for you!';
                $body .= '<br/><br/>Your Commit - ';
                $body .= $reusableString;
                $body .=" failed the Autotester!";
                $body .= '<br><br>See test results <a href="http://bit.ly/jGfIkj">here</a> ';
                $body .= 'Please look at the test results and determine if you need to modify your commit.';
                $body .= 'You can type "@faq CommitTests" in the Journal for more information.';
                $body .= '<br/><br/><a href="http://www.worklist.net">www.worklist.net</a>';
            break;
            
            case 'invite-user':
                $headers['From'] = '"' . $project_name . '-invited" ' . $from_address;
                $body = "<p>Hello you!</p>";
                $body .= "<p>You have been invited by " . $_SESSION['nickname'] . " at the Worklist to bid on ";
                $body .= "<a href=\"" . SERVER_URL . "workitem.php?job_id=$itemId\">" . $workitem -> getSummary() . "</a>.</p>\n";
                $body .= "<p>Description:</p>";
                $body .= "<p>------------------------------</p>\n";
                $body .= "<p>" . $workitem -> getNotes() . "</p>\n";
                $body .= "<p>------------------------------</p>\n";
                $body .= "<p>To bid on that job Just follow <a href=\"" . SERVER_URL . "workitem.php?job_id=$itemId\">this link</a>.</p>\n";
                $body .= "<p>Hope to see you soon.</p>\n";
            break;
            case 'invite-email':
                $headers['From'] = '"' . $project_name . '-invitation" ' . $from_address;
                $body = "<p>Well, hello there!</p>\n";
                $body .= "<p>" . $_SESSION['nickname'] . " from the Worklist thought you might be interested in bidding on this job:</p>\n";
                $body .= "<p>Summary of the job: " . $workitem -> getSummary() . "</p>\n";
                $body .= "<p>Description:</p>\n";
                $body .= "<p>------------------------------</p>\n";
                $body .= "<p>" . $workitem -> getNotes() . "</p>\n";
                $body .= "<p>------------------------------</p>\n";
                $body .= "<p>To bid on that job, follow the link, create an account (less than a minute) and set the price you want to be paid for completing it!</p>\n";
                $body .= "<p>This item is part of a larger body of work being done at Worklist. You can join our Live Workroom to ask more questions by going ";
                $body .= "<a href=\"" . SERVER_BASE . "\">here</a>. You will be our 'Guest' while there but can also create an account if you like so we can refer to you by name.</p>\n";
                $body .= "<p>If you are the type that likes to look before jumping in, here are some helpful links to get you started.</p>\n";
                $body .= "<p>[<a href=\"http://www.lovemachineinc.com/\">www.lovemachineinc.com</a> | Learn more about LoveMachine the company]<br />\n";
                $body .= "[<a href=\"http://svn.worklist.net/\">svn.worklist.net</a> | Browse our SVN repositories]<br />\n";
                $body .= "[<a href=\"https://dev.sendllove.us/\">dev.sendllove.us</a> | Play around with SendLove]<br />\n";
                $body .= "[<a href=\"" . WORKLIST_URL . "/\">" . WORKLIST_URL . "</a> | Look over all our open work items]<br />\n";
                $body .= "[<a href=\"" . JOURNAL_URL . "/\">" . JOURNAL_URL . "</a> | Talk with us in our Journal]<br />\n";
                $body .= "<p>Hope to see you soon.</p>\n";
            break;

            case 'sb_authorization_failed':
                $headers['From'] = '"' . $project_name . '-sandbox" ' . $from_address;
                $body = 'Authorizing sandbox for job ';
                $body .= '<a href=' . SERVER_URL . 'workitem.php?job_id=' . $itemId . '>#' . $itemId . '</a>';
                $body .= ' has failed with the following error message: <br /><br />';
                $body .= "Sandbox is not authorized:<br />";
                $body .= $data['message'];
                $body .= '<br /><br /><a href="http://www.worklist.net">www.worklist.net</a>';
            break;
            
            case 'code-review-completed':
                $headers['From'] = '"' . $project_name . '-review complete" ' . $from_address;
                $body = '<p>Hello,</p>';
                $body .= '<p>The code review on task '.$itemLink.' has been completed by ' . $_SESSION['nickname'] . '</p>';
                $body .= '<br>';
                $body .= '<p>Project: '.$project_name.'<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname() . '</p>';
                $body .= '<p>Notes: ' . $workitem->getNotes() . '<br /></p>';
                $body .= '<p>You can view the job <a href='.SERVER_URL.'workitem.php?job_id='.$itemId.'>here</a>.' . '<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;
            
            case 'job_past_due':
                $headers['From'] = '"' . $project_name . '-pastdue" ' . $from_address;
                $body = "<p>Job " . $itemLink . "<br />";
                $body .= "The done by time has now passed.</p>";
                $body .= '<p>Project: ' . $project_name . '<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                $body .= 'Mechanic: ' . $workitem->getMechanic()->getNickname() . '</p>';
                $body .= '<p>Notes: ' . $workitem->getNotes() . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . SERVER_URL . 'workitem.php?job_id=' . $itemId . '">here</a>.<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;
            
            case 'expired_bid':
                $headers['From'] = '"' . $project_name . '-expired bid" ' . $from_address;
                $body = "<p>Job " . $itemLink . "<br />";
                $body .= "Your Bid on #" . $itemId . " has expired and this task is still available for Bidding.</p>";
                $body .= "<p>Your Bid Info<br />";
                $body .= "Bid Amount : $" . $data['bid_amount'] . "</p>";
                $body .= '<p>Project: ' . $project_name . '<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if ($workitem->getRunnerId()) {
                    $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                $body .= '<p>Notes: ' . $workitem->getNotes() . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . SERVER_URL . 'workitem.php?job_id=' . $itemId . '">here</a>.<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;
            case 'auto-pass':
                $headers['From'] = '"' . $project_name . "- Auto PASSED" . '" ' . $from_address;    
                if (isset($data['prev_status']) && $data['prev_status'] == 'BIDDING') {
                    $headers['From'] = '"' . $project_name . "- BIDDING Item Auto PASSED" . '" ' . $from_address;
                    $body = "Otto has triggered an auto-PASS for job #" . $itemId . ". You may reactivate this job by updating the status or contacting an admin." . '<br/><br/>';
                } else {
                    $body = "Otto has triggered an auto-PASS for your suggested job. You may reactivate this job by updating the status or contacting an admin." . '<br/><br/>';
                }
                $body .= "Summary: " . $itemLink . ": " . $workitem->getSummary() . '<br/>'
                    . 'Project: ' . $project_name . '<br />'
                    . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />'
                    . 'Notes: '. $workitem->getNotes() . '<br /><br />'
                    . 'You can view the job <a href='.SERVER_URL.'workitem.php?job_id=' . $itemId . '>here</a>.' . '<br /><br />'
                    . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;
            
            case 'virus-found':
                $headers['From'] = '"' . $project_name . '-upload error" ' . $from_address;
                $body  = '<p>Hello, <br /><br /> The file ' . $options['file_name'] . ' (' . $options['file_title'] . ') ' .
                    'that you uploaded for this workitem was scanned and found to be containing a virus and will be quarantined. <br /><br />' .
                    'Please upload a clean copy of the file.</p>';
                $body .= '<p>Project: ' . $project_name . '<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if ($workitem->getRunnerId()) {
                    $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                $body .= '<p>Notes: ' . $workitem->getNotes() . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . SERVER_URL . 'workitem.php?job_id=' . $itemId . '">here</a>.<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;

            case 'virus-error':
                $headers['From'] = '"' . $project_name . '-upload error" ' . $from_address;
                $body  = '<p>Hello, <br /><br /> The file ' . $options['file_name'] . ' (' . $options['file_title'] . ') ' .
                    'that you uploaded for this workitem caused an unknown error during scanning. <br /><br />' .
                    'Please upload a clean copy of the file.</p>';
                $body .= '<p>Project: ' . $project_name . '<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if ($workitem->getRunnerId()) {
                    $body .= 'Runner: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                $body .= '<p>Notes: ' . $workitem->getNotes() . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . SERVER_URL . 'workitem.php?job_id=' . $itemId . '">here</a>.<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
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
                            // Check to see if user doesn't want to be notified 
                            // If user is recipient, doesn't have check on settings and not forced to receive then exclude), 
                            // except for followers
                            if ( !strcmp($current_user->getUsername(), $username) && strcmp(ucfirst($recipient), 'Followers') ) {
                                if ( ! Notification::isNotified($current_user->getNotifications(), Notification::SELF_EMAIL_NOTIFICATIONS)
                                    || $includeSelf == false) {
                                    continue;
                                }
                            }

                            // check if we already sending email to this user
                            if(!in_array($username, $emails)){
                                array_push($emails, $username);
                            }
                        }
                    }
                }
            }
        }

        $emails = array_unique($emails);
        if (count($emails) > 0) {
            foreach($emails as $email) {
                // Small tweak for mails to followers on bid acceptance
                if($options['type'] == 'bid_accepted' && strcmp($email, $workitem->getMechanic()->getUsername())) {
                    $body = str_replace('Your', $workitem->getMechanic()->getNickname() . "'s", $body);
                }
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
    
    /**
     * Function to send short (non-split) SMS with an opional url
     * which will be shortened before sending the sms
     * 
     * @param User $recipient - user object to send message to
     * @param String $subject - subject of the message
     * @param String $message - actual message content
     * @param String $url - url to be shortened and appened
     */
    public static function sendShortSMS($recipient, $subject, $message, $url = '') {
        $max_chars = 110;
        $chars_left = $max_chars - (strlen(trim($subject)) +1);
        
        if (parse_url($url) !== false) {
            $shortUrl = new ShortUrl($url);
            $shortUrl = trim($shortUrl->getShortUrl());
            $chars_left -= strlen($shortUrl) +2;
        }
        
        if (strlen($message) > $chars_left) {
            $sms_content = substr($message, 0, $chars_left -3) . '...';
        } else {
            $sms_content = $message;
        }
        
        if (isset($shortUrl)) {
            $sms_content .= ': ' . $shortUrl;
        }
        
        return self::sendSMS($recipient, $subject, $sms_content);
    }
    
    // get list of past due bids
    public function emailPastDueJobs(){
        $qry = "SELECT w.id worklist_id, b.bid_done, b.id bid_id, b.email bid_email
            FROM " . WORKLIST . " w 
              LEFT JOIN " . BIDS . " b ON w.id = b.worklist_id
              LEFT JOIN " . USERS . " u ON w.runner_id = u.id
              LEFT JOIN " . USERS . " bu ON bu.id = b.bidder_id
            WHERE (w.status = 'WORKING' OR w.status = 'REVIEW' OR w.status = 'COMPLETED')
              AND b.accepted = 1 
              AND (b.past_notified = '0000-00-00 00:00:00' OR b.past_notified IS NULL) 
              AND b.withdrawn = 0
              AND bu.is_active = 1";
        $worklist = mysql_query($qry) or (error_log("select past due bids error: " . mysql_error()) && die);
        $wCount = mysql_num_rows($worklist);
        if($wCount > 0){
            while ($row = mysql_fetch_assoc($worklist)) {
                if (strtotime($row['bid_done']) < time()) {
                    
                    $options = array();
                    $options['recipients'] = array("runner");
                    $options['emails'] = array($row['bid_email']);
                    $options['workitem'] = new workItem();
                    $options['workitem']->loadById($row['worklist_id']);
                    $options['type'] = "job_past_due";
                    
                    self::workitemNotify($options);
                    
                    // now need to set this notified flag to now date
                    $bquery = "UPDATE ".BIDS." SET past_notified = NOW() WHERE id = ".$row['bid_id'];
                    $queryB = mysql_query($bquery)or (error_log("update past due bids error: " . mysql_error()) && die);
                }
            }
        }
    }
    
    // HOME PAGE CONTACT/ADD PROJECT FORM EMAIL
    public function emailContactForm($name, $email, $phone, $proj_name, $proj_desc, $website){
        $subject = "Worklist - Add Project Contact Form";
        $html = "<html><head><title>Worklist - Add Project Contact Form</title></head><body>";
        $html .= "<h2>Project Contact Information:</h2>";
        $html .= "<p><strong>Name:</strong> " . $name . "</p>";
        $html .= "<p><strong>Email:</strong> " . $email . "</p>";
        $html .= "<p><strong>Phone #:</strong> " . $phone . "</p>";
        $html .= "<p><strong>Project Name:</strong> " . $proj_name . "</p>";
        $html .= "<p><strong>Website:</strong> " . $website . "</p>";
        $html .= "<p><strong>Project Description:</strong><br />" . nl2br($proj_desc) . "</p>";
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
    
    public function notifyBudgetAddFunds($amount, $giver, $receiver, $grantor, $add_funds_to_budget) {
        if (!$amount || $amount < 0.01 || ! $giver || ! $receiver || ! $grantor) {
            return false;
        }

        $subject = "Worklist - Budget Funds Added!";
        $html = "<html><head><title>Worklist - Budget Funds Added!</title></head><body>";
        $html .= "<h2>You've Got Budget Funds!</h2>";
        $html .= "<p>Hello " . $receiver->getNickname() . ",<br />Your Budget grant from " . 
            $grantor->getNickname() . " has been increased by $" . number_format($amount, 2) .
            " (add funds by " . $giver->getNickname() . ").</p>";
        $html .= "<p>Budget id: " . $add_funds_to_budget->id . "</p>";
        $html .= "<p>Reason: " . $add_funds_to_budget->reason . "</p>";
        $html .= "<p>Remaining amount: $" . number_format($amount + $add_funds_to_budget->remaining, 2) . "</p>";
        $html .= "<p>- Worklist.net</p>";
        $html .= "</body></html>";

        if (!send_email($receiver->getUsername(), $subject, $html)) {
            error_log("Notification:workitem: send_email failed " . json_encode(error_get_last()));
        }
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

    
    public function notifySeedBudget($amount, $reason, $source, $giver, $receiver) {
        if (!$amount || $amount < 0.01 || ! $giver || ! $receiver) {
            return false;
        }

        $subject = "Seed Budget Granted";
        $html = "<html><head><title>Seed Budget Granted</title></head><body>";
        $html .= "<h2>Seed Budget Granted by " . $giver->getNickname() . "</h2>";
        $html .= "<p>To: " . $receiver->getNickname() . 
                "<br />From: " . $giver->getNickname() . 
                "<br />Amount: $" . number_format($amount, 2) .
                "<br />For: " . $reason  .
                "<br />Source: " . $source . "</p>";
        $html .= "</body></html>";

        $emailReceiver = new User();
        $emailReceiverArray = explode(",", BUDGET_AUTHORIZED_USERS);
        for ($i = 1 ; $i < sizeof($emailReceiverArray) - 1 ; $i++) { 
            if ($emailReceiver->findUserById($emailReceiverArray[$i])) {
                if (!send_email($emailReceiver->getUsername(), $subject, $html)) {
                    error_log("Notification:workitem: send_email failed " . json_encode(error_get_last()));
                }
            } else {
                error_log("Notification:workitem: send_email failed, invalid receiver id " . 
                    $emailReceiverArray[$i]);
            }
        }
    }

    public function notifySMSSeedBudget($amount, $reason, $source, $giver, $receiver) {
        if (!$amount || $amount < 0.01 || ! $giver || ! $receiver) {
            return false;
        }
        
        $message = 'To: ' . $receiver->getNickname() . ' AMT: \$' . number_format($amount, 2) . 
                    ' RE: ' . $reason;
        
        setlocale(LC_CTYPE, "en_US.UTF-8");
        $esc_subject = escapeshellarg('Seed Budget Granted by ' . $giver->getNickname());
        $esc_message = escapeshellarg($message);
        $args = '"'.$esc_subject . '" "' . $esc_message . '" ';

        $application_path = dirname(dirname(__FILE__)) . '/';

        $emailReceiver = new User();
        $emailReceiverArray = explode(",", BUDGET_AUTHORIZED_USERS);
        for ($i = 1 ; $i < sizeof($emailReceiverArray) - 1 ; $i++) { 
            if ($emailReceiver->findUserById($emailReceiverArray[$i])) {
                $argsSent = $args . $emailReceiver->getId() . ' ';
                
                $application_path = dirname(dirname(__FILE__)) . '/';
                exec('php ' . $application_path . 'tools/smsnotifications.php '
                    . $args . ' > /dev/null 2>/dev/null &');
            } else {
                error_log("Notification:workitem: send_sms failed, invalid receiver id " . 
                    $emailReceiverArray[$i]);
            }
        }
        
    }
    // get list of expired bids
    public function emailExpiredBids(){
        $qry = "SELECT w.id worklist_id, b.email bid_email, b.id as bid_id, b.bid_amount, r.username runner_email
            FROM " . WORKLIST . " w
              LEFT JOIN " . BIDS . " b ON w.id = b.worklist_id
              LEFT JOIN " . USERS . " u ON u.id = b.bidder_id
              LEFT JOIN " . USERS . " r ON r.id = w.runner_id
              WHERE w.status = 'BIDDING'
              AND b.expired_notify = 0
              AND b.bid_expires < NOW()
              AND u.is_active = 1
              AND b.withdrawn = 0
            ORDER BY b.worklist_id DESC";
        $worklist = mysql_query($qry);
        $wCount = mysql_num_rows($worklist);
        if($wCount > 0){
            while ($row = mysql_fetch_assoc($worklist)) {
                $options = array();
                $options['emails'] = array($row['bid_email'], $row['runner_email']);
                $options['workitem'] = new workItem();
                $options['workitem']->loadById($row['worklist_id']);
                $options['type'] = "expired_bid";
                $data = array('bid_amount'=>$row['bid_amount']);

                self::workitemNotify($options, $data);
                
                $bquery = "UPDATE " . BIDS . " SET expired_notify = 1 WHERE id = " . $row['bid_id'];
                mysql_query($bquery);
            }
        }
    }

}
