<?php

/**
 * This class is responsible for working with notification
 * subscriptions
 */
class Notification {
    public static function getBidNotificationEmails($internal_only = false) {
        $internalCond = $internal_only ? ' AND `is_internal` = 1' : '';
        $result = array();
            $uid = getSessionUserId();
            $sql = "SELECT u.username
                FROM `" . USERS . "` u
                WHERE `bidding_notif` = 1
                AND `is_active` = 1
                {$internalCond}";
            $res = mysql_query($sql);
            while($row = mysql_fetch_row($res)) {
            $bidNotifs[]= $row[0];
        }
            return $bidNotifs;
    }
    public static function getReviewNotificationEmails($internal_only = false) {
        $internalCond = $internal_only ? ' AND `is_internal` = 1' : '';
        $result = array();
            $uid = getSessionUserId();
            $sql = "SELECT u.username
                FROM `" . USERS . "` u
                WHERE ((`review_notif` = 1
                AND `id` != $uid)
                OR (self_notif = 1 and `id` = $uid)
                AND `is_active` = 1)
                {$internalCond}";
            $res = mysql_query($sql);
            while($row = mysql_fetch_row($res)) {
            $reviewNotifs[]= $row[0];
        }
            return $reviewNotifs;
    }
    public static function getSelfNotificationEmails($workitem = 0) {
        $result = array();
            $uid = getSessionUserId();
            $sql = "SELECT u.username 
                FROM `" . USERS . "` u 
                WHERE `self_notif` = 1
                AND `is_active` = 1
                AND `id` = $uid)";
            $res = mysql_query($sql);
            while($row = mysql_fetch_row($res)) {
            $selfNotifs[]= $row[0];
        }
            return $selfNotifs;
    }

    /**
     * Notifications for workitem statuses
     *
     * @param Object $workitem instance of a Workitem class
     */
    public static function statusNotify($workitem) {
        switch($workitem->getStatus()) {
            case 'Bidding':
                $emails = self::getBidNotificationEmails($workitem->isInternal());
                $options = array('type' => 'new_bidding',
                    'workitem' => $workitem,
                    'emails' => $emails);
                self::workitemNotify($options);
                break;
        }
        switch($workitem->getStatus()) {
            case 'Review':
                if (!empty($options['status_change']) &&($workitem->getStatus() == 'Code Review')) {
                    $emails = self::getReviewNotificationEmails($workitem->isInternal());
                    $options = array('type' => 'new_review',
                        'workitem' => $workitem,
                        'emails' => $emails);
                    self::workitemNotify($options);
                    break;
                }
        }
    }

    /**
     * Async wrapper to Notification::statusNotify to avoid big delays
     * on massive notifications.
     * 
     * @param object $workitem instance of a Workitem class
     */
    function massStatusNotify($workitem) {
        return CURLHandler::Post(
            SERVER_URL . 'api.php',
            array(
                'action' => 'sendNotifications',
                'api_key' => API_KEY,
                'command' => 'statusNotify',
                'workitem' => $workitem->getId()
            ),
            false,
            false,
            true
        );
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
        $itemLink = '<a href="' . WORKLIST_URL . $itemId . '"">#' . $itemId . '</a>';
        $itemTitle = '#' . $itemId  . ' (' . $workitem -> getSummary() . ')';
        $itemTitleWithProject = '#' . $itemId  . ': ' . $project_name . ': (' . $workitem -> getSummary() . ')';
        $body = '';
        $subject = '#' . $itemId . ' ' . html_entity_decode($workitem -> getSummary(), ENT_QUOTES);
        $from_address = '<noreply-'.$project_name.'@worklist.net>';
        $headers=array('From' => '"'.$project_name.'-'.strtolower( $workitem -> getStatus() ).'" '.$from_address);
        switch ($options['type']) {
            case 'comment':
                $headers['From'] = '"' . $project_name . '-comment" ' . $from_address;
                $headers['Reply-To'] = '"' . $_SESSION['nickname'] . '" <' . $_SESSION['username'] . '>';
                $body  = 'New comment was added to the item ' . $itemLink . '.<br>';
                $body .= $data['who'] . ' says:<br />'
                      . nl2br($data['comment']) . '<br /><br />'
                      . 'Project: ' . $project_name . '<br />'
                      . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                      if($workitem->getRunner() != '') {
                          $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                      }
                      if($workitem->getMechanic() != '') {
                          $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                      }
                $body .= 'Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;
            
            case 'fee_added':
                if ($workitem->getStatus() != 'Draft') {
                $headers['From'] = '"' . $project_name . '-fee added" ' . $from_address;
                $body = 'New fee was added to the item ' . $itemLink . '.<br>'
                        . 'Who: ' . $data['fee_adder'] . '<br/>'
                        . 'Amount: ' . $data['fee_amount'] . '<br/>'
                        . '<div>Fee Notes:<br/> ' . nl2br($data['fee_desc']) . '</div><br/><br/>'
                        . 'Project: ' . $project_name . '<br/>'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                        if($workitem->getRunner() != '') {
                            $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                        }
                        if($workitem->getMechanic() != '') {
                            $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }
                $body .= 'Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                }
            break;
            
            case 'fee_deleted':
                if ($workitem->getStatus() != 'Draft') {
                    $headers['From'] = '"' . $project_name . '-fee deleted" ' . $from_address;
                    $body = "<p>Your fee has been deleted by: ".$_SESSION['nickname']."<br/><br/>";
                    $body .= "If you think this has been done in error, please contact the job Designer.</p>";
                    $body .= 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                    }
                    $body .= 'Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /><br />'
                    . 'You can view the job <a href="' .  WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                    . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                }
            break;

            case 'tip_added':
                $headers['From'] = '"' . $project_name . '-tip added" ' . $from_address;
                $body = $data['tip_adder'] . ' tipped you $' . $data['tip_amount'] . ' on job ' . $itemLink . ' for:<br><br>' . $data['tip_desc'] . '<br><br>Yay!' . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                       if($workitem->getRunner() != '') {
                           $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                       }
                       if($workitem->getMechanic() != '') {
                           $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                       }
                $body .= 'Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                break;

            case 'bid_accepted':
                $headers['From'] = '"' . $project_name . '-bid accepted" ' . $from_address;
                $body = 'Your bid was accepted for ' . $itemLink . '<br/><br />'
                        . 'If this job requires you to create code, please read through and then follow our coding '
                        . 'standards which are found <a href="https://github.com/highfidelity/hifi/wiki/Coding-Standard">here</a>.<br/><br/>'
                        . 'Promised by: ' . $_SESSION['nickname'] . '<br /><br />'
                        . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                        if($workitem->getRunner() != '') {
                            $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                        }
                        if($workitem->getMechanic() != '') {
                            $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }

                $body .= 'Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /><br />'
                . 'The job can be viewed <a href="' . WORKLIST_URL . $itemId . '">here</a><br /><br />';

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
                $projectId = $workitem->getProjectId();;
                $jobsInfo = $options['jobsInfo'];
                $lastThreeJobs = $jobsInfo['joblist'];
                $workItemUrl = '<a href="' . WORKLIST_URL;
                //create the last three jobs and link them to those Jobs.
                foreach ($lastThreeJobs as $row){
                    $jobs .= $workItemUrl;
                    $jobs .= $row['id'] . '">#' . $row['id'] . '</a>' . ' - ' . $row['summary'] . '<br /><br />';
                }
                //if no Jobs then display 'None'
                if (!$jobs){
                    $jobs = 'None <br />';
                }
      
                //now get total jobs and total jobs and create links
                $totalJobs = $workItemUrl;
                $totalJobs .= $workitem->getId() . '?action=view&userinfotoshow=' . $_SESSION['userid'] . '">' . $options['totalJobs'] . ' Jobs Total</a><br />';
                $totalActiveJobs = $workItemUrl;
                $totalActiveJobs .= $workitem->getId() . '?action=view&userinfotoshow=' . $_SESSION['userid'] . '">' . $options['activeJobs'] . ' Jobs Active</a><br />';
                $urlAcceptBid  = '<br />' . $workItemUrl;
                $urlAcceptBid .= $itemId . '?bid_id=' . $data['bid_id'] . '&action=view_bid">Accept this bid </a>';
                $body .=  $urlAcceptBid;

                $headers['From'] = '"' . $project_name . '-new bid" ' . $from_address;
                $body =  ' <a href="' . SERVER_URL .'user/' .$_SESSION['userid'] . '">' . $_SESSION['nickname'] . '</a> placed a bid on
                         ' . $itemLink . '<br /><br />'
                    . 'Amount: $' . number_format($data['bid_amount'], 2) . '<br />'
                    . 'Functioning In: ' . $data['done_in'] . '<br /><br />'
                    . ' ' . nl2br($data['notes']) . '<br /><br />'
                    . $urlAcceptBid . 'or email ' . $_SESSION['nickname'] . ' via <a href="mailto:' . $_SESSION['username']. '">' . $_SESSION['username']. '</a><br /><br />'
                    .'----<br />'
                    .'Stats for ' . $_SESSION['nickname'] . ': <br />'
                    .'----<br />'
                    . $totalJobs .  '<br /><br />'
                    . $totalActiveJobs . '<br /><br />'
                    . '3 most recent jobs for ' . $project_name . ':<br /><br />'
                    . $jobs . '<br />'
                    .'----<br />'
                    .'Job Details:<br />'
                    .'----<br />'
                    . 'Project: ' . $project_name . '<br />'
                    . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br /><br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                    }
                $body .= ''. nl2br($workitem->getNotes()) . '<br /><br />' . '----<br />';
                $urlAcceptBidB  = $workItemUrl;
                $urlAcceptBidB .= $itemId . '?bid_id=' . $data['bid_id'] . '&action=view_bid">Accept ' . $_SESSION['nickname'] . ' \'s bid </a>';
                $body .=  $urlAcceptBidB . 'for $' . number_format($data['bid_amount'], 2)
                      .'<br />'
                      .'----';

           break;

            case 'bid_updated':
                $headers['From'] = '"' . $project_name . '-bid updated" ' . $from_address;
                $body = 'Bid updated for ' . $itemLink . '<br /><br/>'
                    . 'Amount: $' . number_format($data['bid_amount'], 2) . '<br />'
                    . 'Functioning In: ' . $data['done_in'] . '<br />'
                    . 'Expires: ' . $data['bid_expires'] . '<br /><br />'
                    . 'Bidder Email: <a href="mailto:' . $_SESSION['username'] . '">' . $_SESSION['username'] . '</a><br /><br />'
                    . 'Notes:<br/> ' . nl2br($data['notes']) . '<br /><br />'
                    . 'Project: ' . $project_name . '<br />'
                        . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                    if($workitem->getRunner() != '') {
                        $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                    }
                    if($workitem->getMechanic() != '') {
                        $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }
                $body .= 'Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /><br />';
                $urlacceptbid  = '<br /><a href="./';
                $urlacceptbid .= $itemId . '?bid_id=' . $data['bid_id'] .
                                 '&action=view_bid">Click here to accept bid.</a>';
                $body .=  $urlacceptbid;
            break;

            case 'bid_discarded':
                $headers['From'] = '"' . $project_name . '-bid not accepted" ' . $from_address;
                $body = "<p>Hello " . $data['who'] . ",</p>";
                $body .= "<p>Thanks for adding your bid to <a href='".WORKLIST_URL.$itemId."'>#".$itemId."</a> '" . $workitem -> getSummary() . "'. This job has just been filled by another developer.</br></p>";
                $body .= "There is lots of work to be done so please keep checking the <a href='".SERVER_URL."'>worklist</a> and bid on another job soon!</p>";
                $body .= "<p>Hope to see you in the Worklist soon. :)</p>";
            break;

            case 'modified':
                if ($workitem->getStatus() != 'Draft') {
                    $from_changes = "";
                    if (!empty($options['status_change']) &&($workitem->getStatus() == 'QA Ready')) {
                        $status_change = '-' . strtolower($workitem->getStatus());
                        $headers['From'] = '"' . $project_name . $status_change . '" ' . $from_address;
                        $body = $_SESSION['nickname'] . ' set ' . $itemLink . ' to QA Ready.<br /><br />'
                        . 'Check out the work: ' . $workitem->getSandbox() . '<br /><br />'
                        . 'Checkout the branch created for this job: git checkout ' . $workitem->getSandbox() . ' .<br /><br />'
                        . '<a href="' . WORKLIST_URL . $itemId . '">Leave a comment on the Job</a>';
                    } else {
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
                            $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                        }
                        if($workitem->getMechanic() != '') {
                            $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                        }
                        $body .= 'Notes:<br/> '. nl2br($workitem->getNotes()) . '<br /><br />'
                            . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                            . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
                    }
                }
            break;

            case 'new_bidding':
                $body = "Summary: " . $itemLink . ' ' . $workitem->getSummary() . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                   $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /><br />'
                . 'You are welcome to bid the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;

            case 'new_qa':
                $body = $_SESSION['nickname'] . ' set ' . $itemLink . ' to QA Ready.<br /><br />'
                . 'Check out the work: ' . $workitem->getSandbox() . '<br /><br />'
                . 'Checkout the branch created for this job: git checkout ' . $workitem->getSandbox() . ' .<br /><br />'
                . '<a href="' . WORKLIST_URL . $itemId . '">Leave a comment on the Job</a>';
            break;

            case 'new_review':
                $body = "New item is available for review: " . $itemLink . ' ' . $workitem->getSummary() . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                    $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;

            case 'suggested':
                $body =  'Summary: ' . $itemLink . '<br /><br />'
                . 'Project: ' . $project_name . '<br />'
                . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if($workitem->getRunner() != '') {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                if($workitem->getMechanic() != '') {
                    $body .= 'Developer: ' . $workitem->getMechanic()->getNickname()  . '<br /><br />';
                }
                $body .= 'Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /><br />'
                . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
                . '<a href="' . SERVER_URL . '">www.worklist.net</a>' ;
            break;

            case 'code-review-completed':
                $headers['From'] = '"' . $project_name . '-review complete" ' . $from_address;
                $body = '<p>Hello,</p>';
                $body .= '<p>The code review on task '.$itemLink.' has been completed by ' . $_SESSION['nickname'] . '</p>';
                $body .= '<br>';
                $body .= '<p>Project: '.$project_name.'<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                $body .= 'Developer: ' . $workitem->getMechanic()->getNickname() . '</p>';
                $body .= '<p>Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /></p>';
                $body .= '<p>You can view the job <a href="'. WORKLIST_URL . $itemId . '">here</a>.' . '<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;
            
            case 'expired_bid':
                $headers['From'] = '"' . $project_name . '-expired bid" ' . $from_address;
                $body = "<p>Job " . $itemLink . "<br />";
                $body .= "Your Bid on #" . $itemId . " has expired and this task is still available for Bidding.</p>";
                $body .= "<p>Bidder: " . $data['bidder_nickname'] . "<br />";
                $body .= "Bid Amount : $" . $data['bid_amount'] . "</p>";
                $body .= '<p>Project: ' . $project_name . '<br />';
                $body .= 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />';
                if ($workitem->getRunnerId()) {
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                $body .= '<p>Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . WORKLIST_URL . $itemId . '">here</a>.<br /></p>';
                $body .= '<p><a href="' . SERVER_URL . '">www.worklist.net</a></p>';
            break;
            case 'auto-pass':
                $headers['From'] = '"' . $project_name . "- Auto PASSED" . '" ' . $from_address;
                if (isset($data['prev_status']) && $data['prev_status'] == 'Bidding') {
                    $headers['From'] = '"' . $project_name . "- BIDDING Item Auto PASSED" . '" ' . $from_address;
                    $body = "Otto has triggered an auto-PASS for job #" . $itemId . ". You may reactivate this job by updating the status or contacting an admin." . '<br/><br/>';
                } else {
                    $body = "Otto has triggered an auto-PASS for your suggested job. You may reactivate this job by updating the status or contacting an admin." . '<br/><br/>';
                }
                $body .= "Summary: " . $itemLink . ": " . $workitem->getSummary() . '<br/>'
                    . 'Project: ' . $project_name . '<br />'
                    . 'Creator: ' . $workitem->getCreator()->getNickname() . '<br />'
                    . 'Notes:<br/> '. nl2br($workitem->getNotes()) . '<br /><br />'
                    . 'You can view the job <a href="' . WORKLIST_URL . $itemId . '">here</a>.' . '<br /><br />'
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
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                $body .= '<p>Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . WORKLIST_URL . $itemId . '">here</a>.<br /></p>';
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
                    $body .= 'Designer: ' . $workitem->getRunner()->getNickname() . '<br />';
                }
                $body .= '<p>Notes:<br/> ' . nl2br($workitem->getNotes()) . '<br /></p>';
                $body .= '<p>You can view the job ';
                $body .= '<a href="' . WORKLIST_URL . $itemId . '">here</a>.<br /></p>';
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
                        if ($workitem->isInternal() ? $rUser->isInternal() : true) {
                            if(($username = $rUser->getUsername())) {
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
     *  This function sends notification to HipChat about updates of workitems
     *
     * @param Array $options - Array with options:
     * type - type of notification to send out
     * workitem - workitem object with updated data
     * @param Array $data - Array with additional data that needs to be passed on
     */
    public static function workitemNotifyHipchat($options, $data = null) {
        $workitem = $options['workitem'];
        
        try {
            $project = new Project();
            $project->loadById($workitem->getProjectId());
            $project_name = $project->getName();
        } catch (Exception $e) {
            error_log($e->getMessage()." Workitem: #".$workitem->getId()." has an invalid project id:".$workitem->getProjectId());
            return;
        }
        
        if (!$project->getHipchatEnabled()) {
            return;
        }
        
        $itemId = $workitem->getId();
        $itemLinkShort = '<a href="' . WORKLIST_URL . $itemId . '">#' . $itemId . '</a>';
        $itemLink = $itemLinkShort . ' - ' . $workitem->getSummary();

        $message = null;
        $message_format = 'html';
        $notify = 0;
        
        switch ($options['type']) {
            case 'comment':
                $nick = $data['who'];
                $related = $data['related'];
                $message = "{$nick} posted a comment on job {$itemLink}{$related}";
            break;
            
            case 'fee_added':
                $mechanic_id = $data['mechanic_id'];
                $fee_amount = $data['fee_amount'];
                $nick = $data['nick'];
                
                if ($mechanic_id == $_SESSION['userid']) {
                    $message = "{$nick} added a fee of \${$fee_amount} to item {$itemLink}";
                } else {
                    $rt = mysql_query("SELECT nickname FROM ".USERS.
                        " WHERE id='".(int)$mechanic_id."'");
                    if ($rt) {
                        $row = mysql_fetch_assoc($rt);
                        $nickname = $row['nickname'];
                    } else {
                        $nickname = "unknown-{$mechanic_id}";
                    }
                    
                    $message = "{$nick} on behalf of {$nickname} added a fee of \${$fee_amount} to item {$itemLink}";
                }
            break;
            
            case 'fee_deleted':
                $nick = $data['nick'];
                $fee_nick = $data['fee_nick'];
                $message = "{$nick} deleted a fee from {$fee_nick} on item {$itemLink}";
            break;
            
            case 'tip_added':
                $nick = $data['nick'];
                $tipped_nickname = $data['tipped_nickname'];
                $message = "{$nick} tipped {$tipped_nickname} on job {$itemLink}";
            break;
            
            case 'bid_accepted':
                $nick = $data['nick'];
                $bid_amount = $data['bid_amount'];
                $nickname = $data['nickname'];
                $message = "{$nick} accepted {$bid_amount} from {$nickname} on item {$itemLink}. Status set to In Progress.";
            break;
            
            case 'bid_placed':
                $message = "A bid was placed on item {$itemLink}.";
                
                $new_update_message = $data['new_update_message'];
                if ($new_update_message) {
                    $message .= " {$new_update_message}";
                }
            break;
            
            case 'bid_updated':
                $message = "Bid updated on item {$itemLink}";
            break;
            
            case 'workitem-add':
                $nick = $data['nick'];
                $status = $data['status'];
                $message = "{$nick} added job {$itemLink}. Status set to {$status}";
            break;
            
            case 'code-review-completed':
                $nick = $data['nick'];
                $message = "{$nick} has completed their code review for {$itemLink}";
            break;
            
            case 'workitem-update':
                $nickname = $data['nick'];
                $new_update_message = $data['new_update_message'];
                $related = $data['related'];
                
                $message = "{$nickname} updated item {$itemLink}.{$new_update_message}{$related}";
            break;
            
            case 'status-notify':
                $nick = $data['nick'];
                $status = $data['status'];
                $message = "{$nick} updated item {$itemLink}. Status set to {$status}";
            break;
            
            case 'code-review-started':
                $nick = $data['nick'];
                $message = "{$nick} has started a code review for {$itemLink}";
            break;
            
            case 'code-review-canceled':
                $nick = $data['nick'];
                $message = "{$nick} has canceled their code review for {$itemLink}";
            break;
        }
        
        if ($message) {
            $project->sendHipchat_notification($message, $message_format, $notify);
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

    // get list of expired bids
    public function emailExpiredBids(){
        $qry = "SELECT w.id worklist_id, b.email bid_email, b.id as bid_id, b.bid_amount, r.username runner_email, u.nickname bidder_nickname
            FROM " . WORKLIST . " w
              LEFT JOIN " . BIDS . " b ON w.id = b.worklist_id
              LEFT JOIN " . USERS . " u ON u.id = b.bidder_id
              LEFT JOIN " . USERS . " r ON r.id = w.runner_id
              WHERE w.status = 'Bidding'
              AND b.expired_notify = 0
              AND b.bid_expires <> '0000-00-00 00:00:00'
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
                $options['workitem'] = new WorkItem();
                $options['workitem']->loadById($row['worklist_id']);
                $options['type'] = "expired_bid";
                $data = array('bid_amount' => $row['bid_amount'], 'bidder_nickname' => $row['bidder_nickname']);
                
                self::workitemNotify($options, $data);
                
                $bquery = "UPDATE " . BIDS . " SET expired_notify = 1 WHERE id = " . $row['bid_id'];
                mysql_query($bquery);
            }
        }
    }

}
