<?php
/* TODO (joanne)
 * as time permits we need to compare the journal messages here to those in notification class
 * and clean out whatever code is no longer in use.
 */
require_once('models/DataObject.php');
require_once('models/Budget.php');
require_once('models/Users_Favorite.php');

class JobController extends Controller {
    public $is_runner = 0;
    public $is_internal = 0;

    /**
     * Non existing method call will fall to run this method, so here
     * we guess that the requestor is trying to reach a specific job.
     * Let's take it's arguments and route to the right process path.
     */
    public function __call($id, $arguments) {
        $arguments = array_merge(array($id), $arguments);
        call_user_func_array(array($this, 'view'), $arguments);
    }

    public function view($job_id) {
        $this->write('statusListRunner', array("Draft", "Suggestion", "Bidding", "In Progress", "QA Ready", "Code Review", "Merged", "Done", "Pass"));
        $statusListMechanic = array("In Progress", "QA Ready", "Code Review", "Merged", "Pass");
        $this->write('statusListMechanic', $statusListMechanic);
        $this->write('statusListCreator', array("Suggestion", "Pass"));

        if (! defined("WORKITEM_URL")) { define("WORKITEM_URL", SERVER_URL); }
        if (! defined("WORKLIST_REDIRECT_URL")) { define("WORKLIST_REDIRECT_URL", SERVER_URL); }
        $worklist_id = intval($job_id);
        $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
        $currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';

        //initialize user accessing the page
        $userId = getSessionUserId();
        $user = new User();
        if ($userId > 0) {
            $user->findUserById($userId);
        } else {
            $user->setId(0);
        }
        $this->write('user', $user);

        // TODO: Would be good to take out all the checks for isset($_SESSION['userid'] etc. and have them use $user instead, check $user->getId() > 0.
        if (empty($worklist_id)) {
            $this->view = null;
            return;
        }
        //Set an empty variable for $journal_message to avoid errors/warnings with .=
        $journal_message = null;

        //initialize the workitem class
        $workitem = new WorkItem();
        try {
            $workitem->loadById($worklist_id);
        } catch(Exception $e) {
            $error  = $e->getMessage();
            $this->view = null;
            die($error);
        }

        if ($workitem->isInternal() && ! $user->isInternal()) {
            $this->write('msg', 'You don\'t have permissions to view this job.');
            $this->write('link', WORKLIST_URL);
            $this->view = new ErrorView();
            parent::run();
            exit;
        }

        if ($workitem->getStatus() == 'Draft' &&  $workitem->getCreatorId() != $_SESSION['userid']){
            $this->write('msg', 'You don\'t have permissions to view this job.');
            $this->write('link', WORKLIST_URL);
            $this->view = new ErrorView();
            parent::run();
            exit;
        }

        $this->write('workitem', $workitem);

        // we need to be able to grant runner rights to a project founder for all jobs for their project
        $workitem_project = Project::getById($workitem->getProjectId());
        $is_project_founder = false;
        if($workitem_project->getOwnerId() == $_SESSION['userid']){
            $is_project_founder = true;
        }
        $this->write('workitem_project', $workitem_project);
        $this->write('is_project_founder', $is_project_founder);

        $this->write('isGitHubConnected', $user->isGithub_connected($workitem_project->getGithubId()));

        //used for is_project_runner rights
        $is_project_runner = false;
        if($workitem->getIsRelRunner() == 1){
            $is_project_runner = true;
        }
        $this->write('is_project_runner', $is_project_runner);

        $redirectToDefaultView = false;

        $promptForReviewUrl = true;
        $runner_budget = $user->getBudget();

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';
        if ($workitem->getStatus() == 'Done' && $action == 'edit') {
            $action = 'view';
        }

        $view_bid_id = 0;

        if (isset($_REQUEST['withdraw_bid'])) {
            $action = "withdraw_bid";
        } else if(isset($_REQUEST['decline_bid'])) {
            $action = "decline_bid";
        } else if(isset($_REQUEST['save_workitem'])) {
            $action = "save_workitem";
        } else if(isset($_REQUEST['place_bid'])) {
            $action = "place_bid";
        } else if(isset($_REQUEST['swb'])) {
            $action = "swb";
        } else if(isset($_REQUEST['edit_bid'])) {
            $action = "edit_bid";
        } else if(isset($_REQUEST['add_fee'])) {
            $action = "add_fee";
        } else if (isset($_REQUEST['add_tip'])) {
            $action = "add_tip";
        } else if(isset($_REQUEST['accept_bid'])) {
            $action = "accept_bid";
        } else if(isset($_REQUEST['accept_multiple_bid'])) {
            $action = "accept_multiple_bid";
        } else if(isset($_REQUEST['status-switch'])) {
            $action = "status-switch";
        } else if (isset($_REQUEST['newcomment'])) {
            $action = 'new-comment';
        }

        if ($action == 'view_bid') {
            $action = "view";
            $this->write('view_bid_id', isset($_REQUEST['bid_id']) ? $_REQUEST['bid_id'] : 0);
        }

        // for any other action user has to be logged in
        if ($action != 'view') {
            checkLogin();
            $action_error = '';
            $action = $workitem->validateAction($action, $action_error);
        }
        $this->write('action', $action);

        // Save WorkItem was requested. We only support Update here
        $notifyEmpty = true;
        $job_changes = array();
        $status_change = '';
        if ($action =='save_workitem') {
            $this->edit($worklist_id);
        }

        if ($action == 'new-comment') {
            if ((isset($_REQUEST['worklist_id']) && !empty($_REQUEST['worklist_id'])) &&
                (isset($_REQUEST['user_id'])     && !empty($_REQUEST['user_id']))     &&
                (isset($_REQUEST['comment'])     && !empty($_REQUEST['comment']))) {

                if (isset($_REQUEST['comment_id']) && !empty($_REQUEST['comment_id'])) {
                    $parent_comment = (int) $_REQUEST['comment_id'];
                } else {
                    $parent_comment = NULL;
                }
                $worklist_id = (int) $_REQUEST['worklist_id'];
                $user_id = (int) $_REQUEST['user_id'];
                $comment = $_REQUEST['comment'];
                $rt = $this->addComment($worklist_id,
                    $user_id,
                    $comment,
                    $parent_comment);

                // Send journal notification
                if ($workitem->getStatus() != 'Draft') {
                    $related = getRelated($comment);
                    $journal_message .= '@' . $_SESSION['nickname'] . ' posted a comment on #' . $worklist_id . $related;

                    $options = array(
                        'type' => 'comment',
                        'workitem' => $workitem,
                        'recipients' => array('creator', 'runner', 'mechanic', 'followers'),
                        'emails' => $rt['correspondent']
                    );
                    $data = array(
                        'who' => $_SESSION['nickname'],
                        // removed nl2br as it's cleaner to be able to choose if this is used on output
                        'comment' => $comment,
                        'related' => $related,
                        'comment-id' => $rt['id']
                    );

                    Notification::workitemNotify($options, $data, false);
                    Notification::workitemNotifyHipchat($options, $data);

                    // workitem mentions
                    $matches = array();
                    if (preg_match_all(
                        '/@(\w+)/',
                        $comment,
                        $matches,
                        PREG_SET_ORDER
                    )) {
                        foreach ($matches as $mention) {
                            // validate the username actually exists
                            if ($recipient = User::find($mention[1])) {

                                // exclude creator, designer, developer and followers
                                if (
                                    $recipient->getId() != $workitem->getRunnerId() &&
                                    $recipient->getId() != $workitem->getMechanicId() &&
                                    $recipient->getId() != $workitem->getCreatorId() &&
                                    ! $workitem->isUserFollowing($recipient->getId())
                                ) {

                                    $emailTemplate = 'workitem-mention';
                                    $comment_url = WORKLIST_URL . $workitem->getId() . '#comment-' . $rt['id'];
                                    $data = array(
                                        'job_id' => $workitem->getId(),
                                        'summary' => $workitem->getSummary(),
                                        'author' => $_SESSION['nickname'],
                                        'text' => $comment,
                                        'link' => '<a href="' . $comment_url . '">See the comment</a>'
                                    );

                                    $senderEmail = 'Worklist - ' . $_SESSION['nickname'] . ' <contact@worklist.net> ';
                                    sendTemplateEmail($recipient->getUsername(), $emailTemplate, $data, $senderEmail);
                                }
                            }
                        }
                    }

                }
                sendJournalNotification($journal_message);
                $comment = new Comment();
                $comment->findCommentById((int) $rt['id']);
                $result = array('success' => true,
                                'id' => $rt['id'],
                        'comment' => replaceEncodedNewLinesWithBr(linkify($comment->getComment())),
                        'avatar' =>  $comment->getUser()->getAvatar(),
                        'nickname' => $comment->getUser()->getNickname(),
                        'userid' => $comment->getUser()->getId(),
                        'date' => relativeTime(strtotime($comment->getDate()) - strtotime(Model::now())));
                ob_start();
                $json = json_encode($result);
            } else {
                $json = json_encode(array('success' => false));
            }
            $this->view = null;
            echo $json;
            ob_end_flush();
            exit;
        }

        if ($action =='status-switch') {
            $status = $_REQUEST['quick-status'];
            $status_error = '';
            if ($status == 'Done' && $workitem->getProjectId() == 0) {
                $status_error = "No project associated with workitem. Could not set to DONE.";
            } else {
                if ($this->changeStatus($workitem, $status, $user)) {
                    if ($workitem->save() == false) {
                        $status_error = "Error in save workitem process. Could not change the status.";
                    } else {
                        if ($status == 'Merged') {
                            $workitem->addFeesToCompletedJob();
                        }

                        if ($status != 'Draft'){
                            $new_update_message = "Status set to *$status*. ";
                            $notifyEmpty = false;
                            $status_change = '-' . ucfirst(strtolower($status));
                                if ($status == 'QA Ready') {
                                    Notification::workitemNotify(array('type' => 'new_qa',
                                        'workitem' => $workitem,
                                        'status_change' => $status_change,
                                        'job_changes' => $job_changes,
                                        'recipients' => array($workitem->getRunnerId(), 'creator', 'mechanic', 'followers')),
                                        array('changes' => $new_update_message));
                                    $notifyEmpty = true;
                                }
                                if ($status == 'Code Review') {
                                    Notification::workitemNotify(array('type' => 'new_review',
                                        'workitem' => $workitem,
                                        'status_change' => $status_change,
                                        'job_changes' => $job_changes,
                                        'recipients' => array($workitem->getRunnerId(), 'creator', 'mechanic', 'followers', 'reviewNotifs')),
                                        array('changes' => $new_update_message));
                                    $notifyEmpty = true;
                                }

                            $journal_message = '\\#' . $worklist_id . ' updated by @' . $_SESSION['nickname'] . ' ' . $new_update_message;
                        }
                    }
                } else {

                    $message = '';
                    if ($status & 4) { //sandbox not updated
                        $message .= " - Sandbox is not up-to-date\n";
                    }
                    if ($status & 8) { //sandbox has conflicts
                        $message .= " - Sandbox contains conflicted files\n";
                    }
                    if ($status & 16) { //sandbox has not-included files
                        $message .= " - Sandbox contains 'not-included' files\n";
                    }

                    $status_error = "Sandbox verification failed. " . $message;
                }
            }
        }

        if (!$notifyEmpty) {
            $options = array(
                'type' => 'modified',
                'workitem' => $workitem,
                'status_change' => $status_change,
                'job_changes' => $job_changes,
                'recipients' => array('runner', 'creator', 'mechanic', 'followers')
            );
            $data = array(
                'changes' => $new_update_message
            );
            Notification::workitemNotify($options, $data);
        }

        if ($action == "place_bid") {
            //Escaping $notes with mysql_real_escape_string is generating \n\r instead of <br>
            //a new variable is used to send the unenscaped notes in email alert.
            //so it can parse the new line as <BR>   12-Mar-2011 <webdev>

            $args = array('bid_amount', 'done_in', 'bid_expires', 'notes', 'mechanic_id');
            foreach ($args as $arg) {
                $$arg = mysql_real_escape_string($_REQUEST[$arg]);
            }
            $bid_amount = (float) $bid_amount;
            $mechanic_id = (int) $mechanic_id;

            if ($_SESSION['timezone'] == '0000') $_SESSION['timezone'] = '+0000';
            $summary = getWorkItemSummary($worklist_id);

            if($mechanic_id != getSessionUserId()) {
                $row = $workitem->getUserDetails($mechanic_id);
                if (! empty($row)) {
                    $nickname = $row['nickname'];
                    $username = $row['username'];
                } else {
                    $username = "unknown-{$username}";
                    $nickname = "unknown-{$mechanic_id}";
                }
            } else {
                $mechanic_id = $_SESSION['userid'];
                $username = $_SESSION['username'];
                $nickname = $_SESSION['nickname'];
            }

            if ($user->isEligible()) {
                $bid_id = $workitem->placeBid($mechanic_id, $username, $worklist_id, $bid_amount, $done_in, $bid_expires, $notes);
                //sending email to the runner of worklist item or all runners if not assigned

                $row = $workitem->getRunnerSummary($worklist_id);
                if(!empty($row)) {
                $id = $row['id'];
                    $summary = $row['summary'];
                    $username = $row['username'];
                }

                $options = array(
                    'type' => 'bid_placed',
                    'workitem' => $workitem,
                    'recipients' => array($workitem->getRunnerId() == '' ? 'projectRunners' : 'runner'),
                    'jobsInfo' => $user->jobsForProject('Done', $workitem->getProjectId(), 1, 3),
                    'totalJobs' => $user->jobsCount(array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')),
                    'activeJobs' => $user->jobsCount(array('In Progress', 'QA Ready', 'Review'))
                    );
                $journal_message = 'A bid was placed on #' . $worklist_id;

                $data = array(
                     'done_in' => $done_in,
                     'bid_expires' => $bid_expires,
                     'bid_amount' => $bid_amount,
                     'notes' => replaceEncodedNewLinesWithBr($notes),
                     'bid_id' => $bid_id,
                );

                // notify runner of new bid
                Notification::workitemNotify($options, $data);

                $status=$workitem->loadStatusByBidId($bid_id);
                $data['new_update_message'] = $new_update_message;
                Notification::workitemNotifyHipchat($options, $data);

            } else {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            }
            $redirectToDefaultView = true;
        }

        // Edit Bid
        if ($action =="edit_bid") {
            if (! $user->isEligible() ) {
                error_log("Input forgery detected for user $userId: attempting to $action (isEligible in job)");
            } else {
                //Escaping $notes with mysql_real_escape_string is generating \n\r instead of <br>
                //a new variable is used to send the unenscaped notes in email alert.
                //so it can parse the new line as <BR>   12-Mar-2011 <webdev>

                $args = array('bid_id', 'bid_amount', 'done_in', 'bid_expires', 'notes');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                }

                $bid_amount = (float) $bid_amount;
                $mechanic_id = (int) $mechanic_id;

                if ($_SESSION['timezone'] == '0000') $_SESSION['timezone'] = '+0000';
                $summary = getWorkItemSummary($worklist_id);
                $bid_id = $workitem->updateBid($bid_id, $bid_amount, $done_in, $bid_expires, $_SESSION['timezone'], $notes);

                // Journal notification
                $journal_message = 'Bid updated on #' . $worklist_id;

                //sending email to the runner of worklist item
                $row = $workitem->getRunnerSummary($worklist_id);
                if(!empty($row)) {
                $id = $row['id'];
                    $summary = $row['summary'];
                    $username = $row['username'];
                }

                $options = array(
                    'type' => 'bid_updated',
                    'workitem' => $workitem,
                    'recipients' => array('runner'),
                    'jobsInfo' => $user->jobsForProject('Done', $workitem->getProjectId(), 1, 3),
                    'totalJobs' => $user->jobsCount(array('In Progress', 'QA Ready', 'Review', 'Merged', 'Done')),
                    'activeJobs' => $user->jobsCount(array('In Progress', 'QA Ready', 'Review'))
                );
                $data = array(
                    'done_in' => $done_in,
                    'bid_expires' => $bid_expires,
                    'bid_amount' => $bid_amount,
                    'notes' => replaceEncodedNewLinesWithBr($notes),
                    'bid_id' => $bid_id
                );

                // notify runner of new bid
                Notification::workitemNotify($options, $data);
                Notification::workitemNotifyHipchat($options, $data);

            }
            $redirectToDefaultView = true;
        }
        // Request submitted from Add Fee popup
        if ($action == "add_fee") {
            if(! $user->isEligible()) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $args = array('itemid', 'fee_amount', 'fee_desc', 'mechanic_id', 'is_expense', 'is_rewarder');
                foreach ($args as $arg) {
                    if (isset($_REQUEST[$arg]))  {
                       $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                    }
                    else {
                        $$arg = '';
                    }
                }
                $itemid = (int) $itemid;
                $fee_amount = (float) $fee_amount;
                $mechanic_id = (int) $mechanic_id;

                $journal_message = AddFee($itemid, $fee_amount, '', $fee_desc, $mechanic_id, '', '');

                if ($workitem->getStatus() != 'Draft') {
                    $options = array(
                        'type' => 'fee_added',
                        'workitem' => $workitem,
                        'recipients' => array('runner')
                    );
                    $data = array(
                        'fee_adder' => $user->getNickname(),
                        'fee_amount' => $fee_amount,
                        'fee_desc' => $fee_desc,
                        'mechanic_id' => $mechanic_id,
                    );

                    Notification::workitemNotify($options, $data);

                    $data['nick'] = $_SESSION['nickname'];
                    Notification::workitemNotifyHipchat($options, $data);

                    // update budget
                    $runner = new User();
                    $runner->findUserById($workitem->getRunnerId());
                    $runner->updateBudget(-$fee_amount, $workitem->getBudget_id());

                }
                $redirectToDefaultView = true;
            }
        }

        // Accept a bid
        if ($action == 'accept_bid') {
            if (!isset($_REQUEST['bid_id']) ||
                !isset($_REQUEST['budget_id'])) {
                $_SESSION['workitem_error'] = "Missing parameter to accept a bid!";
            } else {
                $bid_id = intval($_REQUEST['bid_id']);
                $budget_id = intval($_REQUEST['budget_id']);

                $budget = new Budget();
                if (!$budget->loadById($budget_id) ) {
                    $_SESSION['workitem_error'] = "Invalid budget!";
                }
                $is_job_runner = $workitem->getRunnerId() == getSessionUserId();
                $is_assigned = $workitem->getAssigned_id() == getSessionUserId();
                // only runners can accept bids
                if (($is_project_runner || $is_job_runner || $is_assigned || ($user->getIs_admin() == 1
                     && $is_runner) && !$workitem->hasAcceptedBids() && $workitem->getStatus() == "Bidding")) {
                    // query to get a list of bids (to use the current class rather than breaking uniformity)
                    // I could have done this quite easier with just 1 query and an if statement..
                    $bids = (array) $workitem->getBids($workitem->getId());
                    $exists = false;
                    foreach ($bids as $array) {
                        if ($array['id'] == $bid_id) {
                            $exists = true;
                            $bid_amount = $array["bid_amount"];
                            break;
                        }
                    }

                    if ($exists) {
                        $remainingFunds = $budget->getRemainingFunds();
                        if($bid_amount <= $remainingFunds) {
                            $bid_info = $workitem->acceptBid($bid_id, $budget_id);
                            $budget->recalculateBudgetRemaining();

                            // Journal notification
                            $journal_message .= '@' . $_SESSION['nickname'] .
                                " accepted {$bid_info['bid_amount']} from ".
                                $bid_info['nickname'] . " on #" .$bid_info['worklist_id'] ." Status set to *In Progress*.";

                            $options = array(
                                'type' => 'bid_accepted',
                                'workitem' => $workitem,
                                'recipients' => array('mechanic', 'followers')
                            );

                            // mail notification - including any data returned from acceptBid
                            Notification::workitemNotify($options, $bid_info);

                            $data = $bid_info;
                            $data['nick'] = $_SESSION['nickname'];
                            Notification::workitemNotifyHipchat($options, $data);

                            $bidder = new User();
                            $bidder->findUserById($bid_info['bidder_id']);

                            // Update Budget
                            $runner = new User();
                            $runner->findUserById($workitem->getRunnerId());
                            $runner->updateBudget(-$bid_amount, $workitem->getBudget_id());

                            // Send email to not accepted bidders
                            $this->sendMailToDiscardedBids($worklist_id);
                        } else {
                            $overBudget = money_format('%i', $bid_amount - $remainingFunds);
                            $_SESSION['workitem_error'] = "Failed to accept bid. Accepting this bid would make you " . $overBudget . " over your budget!";
                        }
                    } else {
                        $_SESSION['workitem_error'] = "Failed to accept bid, bid has been deleted!";
                    }
                } else {
                    if ($workitem->getIsRelRunner() || $workitem->getRunnerId() == $_SESSION['userid']) {
                        if ($workitem->hasAcceptedBids()) {
                            $_SESSION['workitem_error'] = "Failed to accept bid on task with an accepted bid!";
                        } else {
                            $_SESSION['workitem_error'] = "Accept Bid Failed, unknown task state!";
                        }
                    }
                }
            }
            $redirectToDefaultView = true;
        }

        // Accept Multiple  bid
        if ($action=='accept_multiple_bid') {
            if (!isset($_REQUEST['budget_id'])) {
                $_SESSION['workitem_error'] = "Missing budget to accept a bid!";
            } else {
                $bid_id = $_REQUEST['chkMultipleBid'];
                $mechanic_id = $_REQUEST['mechanic'];
                $budget_id = intval($_REQUEST['budget_id']);
                $budget = new Budget();
                if (!$budget->loadById($budget_id) ) {
                    $_SESSION['workitem_error'] = "Invalid budget!";
                }
                if (count($bid_id) > 0) {
                //only runners can accept bids
                    if (($is_project_runner || $workitem->getRunnerId() == getSessionUserId() ||
                         ($user->getIs_admin() == 1 && $is_runner)) && !$workitem->hasAcceptedBids() && $workitem->getStatus() == "Bidding") {
                        $total = 0;
                        foreach ($bid_id as $bid) {
                            $currentBid = new Bid();
                            $currentBid->findBidById($bid);
                            $total = $total + $currentBid->getBid_amount();
                        }

                        $remainingFunds = $budget->getRemainingFunds();
                        if ($total <= $remainingFunds) {
                            foreach ($bid_id as $bid) {
                                $bids = (array) $workitem->getBids($workitem->getId());
                                $exists = false;
                                foreach ($bids as $array) {
                                    if ($array['id'] == $bid) {
                                        if ($array['bidder_id'] == $mechanic_id) {
                                            $is_mechanic = true;
                                        } else {
                                            $is_mechanic = false;
                                        }
                                        $exists = true;
                                        break;
                                    }
                                }
                                if ($exists) {
                                    $bid_info = $workitem->acceptBid($bid, $budget_id, $is_mechanic);
                                    // Journal notification
                                    $journal_message .= '@' . $_SESSION['nickname'] . " accepted {$bid_info['bid_amount']} from ".
                                        $bid_info['nickname'] . " " . ($is_mechanic ? ' as Developer ' : '') .
                                        "on #".$bid_info['worklist_id']. " Status set to *In Progress*.";
                                    // mail notification
                                    Notification::workitemNotify(array('type' => 'bid_accepted',
                                                 'workitem' => $workitem,
                                                 'recipients' => array('mechanic', 'followers')));
                                } else {
                                    $_SESSION['workitem_error'] = "Failed to accept bid, bid has been deleted!";
                                }
                            }
                            // Send email to not accepted bidders
                            $this->sendMailToDiscardedBids($worklist_id);

                            $runner = new User();
                            $runner->findUserById($workitem->getRunnerId());
                            $runner->updateBudget(-$total, $workitem->getBudget_id());
                        } else {
                            $overBudget = money_format('%i', $total - $remainingFunds);
                            $_SESSION['workitem_error'] = "Failed to accept bids. Accepting this bids would make you " . $overBudget . " over your budget!";
                        }
                    }
                }
            }
            $redirectToDefaultView = true;
        }
        //Withdraw a bid
        if ($action == "withdraw_bid") {
            if (isset($_REQUEST['bid_id'])) {
                withdrawBid(intval($_REQUEST['bid_id']), $_REQUEST['withdraw_bid_reason']);
            } else {
                $fee_id = intval($_REQUEST['fee_id']);
                $res = mysql_query('SELECT f.bid_id, f.amount, w.runner_id FROM `' . FEES . '` AS f, ' . WORKLIST . ' AS w WHERE f.`id`=' . $fee_id . ' AND f.worklist_id = w.id');
                $fee = mysql_fetch_object($res);
                if ((int)$fee->bid_id !== 0) {
                    withdrawBid($fee->bid_id, $_REQUEST['withdraw_bid_reason']);
                } else {
                    deleteFee($fee_id);
                }

                // Update Runner's Budget
                $runner = new User();
                $runner->findUserById($fee->runner_id);
                $runner->updateBudget($fee->amount, $workitem->getBudget_id());
            }
            $redirectToDefaultView = true;
        }

        //Decline a bid
        if ($action == "decline_bid") {
            if (isset($_REQUEST['bid_id'])) {
                withdrawBid(intval($_REQUEST['bid_id']), $_REQUEST['decline_bid_reason']);
            } else {
                $fee_id = intval($_REQUEST['fee_id']);
                $res = mysql_query('SELECT f.bid_id, f.amount, w.runner_id FROM `' . FEES . '` AS f, ' . WORKLIST . ' AS w WHERE f.`id`=' . $fee_id . ' AND f.worklist_id = w.id');
                $fee = mysql_fetch_object($res);
                if ((int)$fee->bid_id !== 0) {
                    withdrawBid($fee->bid_id, $_REQUEST['decline_bid_reason']);
                } else {
                    deleteFee($fee_id);
                }

                // Update Runner's Budget
                $runner = new User();
                $runner->findUserById($fee->runner_id);
                $runner->updateBudget($fee->amount, $workitem->getBudget_id());
            }
            $redirectToDefaultView = true;
        }

        // we have a Journal message, send it to Journal - except for DRAFTS
        if(isset($journal_message) && $workitem->getStatus() != 'Draft') {
            sendJournalNotification($journal_message);
            //$postProcessUrl = WORKITEM_URL . $worklist_id . "?msg=" . $journal_message;
        }

        if ($redirectToDefaultView) {
            $this->redirect('./' . $worklist_id);
        }

        // handle the makeshift error I made..
        $erroneous = false;
        if (isset($_SESSION['workitem_error'])) {
            $erroneous = true;
            $the_errors = $_SESSION['workitem_error'];
            unset($_SESSION['workitem_error']);
            $this->write('erroneous', $erroneous);
            $this->write('the_errors', $the_errors);
        }
        // Process the request normally and display the page.

        //get workitem from db
        $worklist = $workitem->getWorkItem($worklist_id);
        $this->write('worklist', $worklist);

        //get bids
        $bids = $workitem->getBids($worklist_id);
        // get only those bids that have not expired, used to determine whether
        // runner can edit the job notes
        $this->write('activeBids', (array) $workitem->getBids($workitem->getId(), false));

        //Findout if the current user already has any bids.
        // Yes, it's a String instead of boolean to make it easy to use in JS.
        // Suppress names if not is_runner, or creator of Item. Still show if it's user's bid.

        $currentUserHasBid = "false";
        if(!empty($bids) && is_array($bids)) {
            foreach ($bids as &$bid) {
                if($bid['email'] == $currentUsername ) {
                    $currentUserHasBid = "true";
                    //break;
                }

                if (!($user->getId() == $bid['bidder_id'] || $user->isRunnerOfWorkitem($workitem) || $workitem->getIsRelRunner() && !$worklist['runner_id']))  {
                    if ($user->getIs_admin() == 0) {
                        $bid['nickname'] = '*name hidden*';
                        $bid['bid_amount'] = '***';
                        $bid['email'] = '********';
                        $bid['notes'] = '********';
                    }
                }
                $bid['bid_created'] = convertTimezone($bid['unix_bid_created']);
                if ($bid['unix_bid_accepted'] > 0) {
                    $bid['bid_accepted'] = convertTimezone($bid['unix_bid_accepted']);
                } else {
                    $bid['bid_accepted'] = '';
                }
                if ($bid['unix_done_full'] > 0 && !empty($bid['unix_done_full'])) {
                    $bid['unix_done_full'] = convertTimezone($bid['unix_done_full']);
                } else {
                    $bid['unix_done_full'] = '';
                }


                // calculate Total Time to Complete
                if (isset($bid['unix_done_by']) && $bid['unix_done_by'] != 0) {
                    $timeToComplete = (int) $bid['unix_done_by'] - (int) $bid['unix_bid_created'];
                    if ($bid['unix_bid_accepted'] > 0) {
                        $timeElapsed = (int) $bid['unix_now'] - (int) $bid['unix_bid_accepted'];
                        $timeToComplete -= $timeElapsed;
                    }
                    $fullDays    = floor($timeToComplete/(60*60*24));
                    $fullHours   = floor(($timeToComplete-($fullDays*60*60*24))/(60*60));
                    $fullMinutes = floor(($timeToComplete-($fullDays*60*60*24)-($fullHours*60*60))/60);
                    $bid['time_to_complete']= $fullDays . ($fullDays==1?" day, ":" days, ").$fullHours. ($fullHours==1?" hour and ":" hours and ").$fullMinutes.($fullMinutes==1?" minute.":" minutes.");
                } else {
                    $bid['time_to_complete'] = null;
                }
            }
        }
        // break reference to $bid
        unset($bid);
        //get fees
        $fees = $workitem->getFees($worklist_id);
        $this->write('fees', $fees);

        $user_id = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";
        $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
        $is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;
        $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
        $creator_id = isset($worklist['creator_id']) ? $worklist['creator_id'] : 0;
        $mechanic_id = isset($worklist['mechanic_id']) ? $worklist['mechanic_id'] : 0;
        $runner_id = isset($worklist['runner_id']) ? $worklist['runner_id'] : 0;
        $status_error = '';

        $has_budget = 0;
        if (! empty($user_id)) {
            $user = new User();
            $user->findUserById($user_id);
            if ($user->getBudget() > 0) {
                $has_budget = 1;
            }

            // fee defaults to 0 for internal users
            $crFee = 0;
            if (! $user->isInternal()) {
                // otherwise, lookup reviewer fee on the Project
                $crFee = $this->getCRFee($workitem);
            }

            $this->write('crFee', $crFee);
        }

        $workitem = WorkItem::getById($worklist['id']);
        if ($worklist['project_id']) {
            $workitem_project = new Project($worklist['project_id']);
        }
        $projects = Project::getProjects();

        $allowEdit = false;
        $classEditable = "";
        if (($workitem->getIsRelRunner() && is_null($worklist['runner_id']) || ($user->getIs_admin() == 1 && $is_runner)) ||
             ($creator_id == $user_id && $worklist['status'] == 'Suggestion' && is_null($worklist['runner_id'])) ||
             ($runner_id == $user_id)) {
            $allowEdit = true;
            if ($action !="edit") {
                $classEditable = " editable";
            }
        }
        $this->write('classEditable', $classEditable);
        $this->write('allowEdit', $allowEdit);

        $hideFees = false;
        if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'Suggestion') {
            $hideFees = true;
        }
        $this->write('hideFees', $hideFees);


        $this->write('bids', $bids);

        $this->write('userHasCodeReviewRights', $this->hasCodeReviewRights($user_id, $workitem));

        $this->write('mechanic', $workitem->getUserDetails($worklist['mechanic_id']));

        $reviewer = new User();
        $reviewer->findUserById($workitem->getCReviewerId());
        $this->write('reviewer', $reviewer);

        $this->write('action_error', isset($action_error) ? $action_error : '');

        $this->write('comments', Comment::findCommentsForWorkitem($worklist['id']));
        $this->write('entries', $this->getTaskPosts($worklist['id']));
        $this->write('message', isset($message) ? $message : '');
        $this->write('currentUserHasBid', $currentUserHasBid);
        $this->write('has_budget', $has_budget);
        $this->write('promptForReviewUrl', $promptForReviewUrl);
        $this->write('status_error', $status_error);
        $this->write('{{userinfotoshow}}', (isset($_REQUEST['userinfotoshow']) && isset($_SESSION['userid'])) ? $_REQUEST['userinfotoshow'] : 0);
        $job_analytics = VisitQueryTools::visitQuery($worklist_id);
        $this->write('viewCount', $job_analytics['views']);
        $job_views = ($job_analytics['views'] > 1 ? " views" : " view");
        $this->write('views', $job_views);
        parent::run();
    }

    public function add() {
        $this->view = null;
        if (isset($_POST['api_key'])) {
            validateAPIKey();
            $user = User::find($_POST['creator']);
            $userId = $user->getId();
        } else {
            checkLogin();
            $userId = getSessionUserId();
        }
        if (!$userId) {
            header('HTTP/1.1 401 Unauthorized', true, 401);
            echo json_encode(array('error' => "Invalid parameters !"));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->view = new AddJobView();
            parent::run();
            return;
        }
        $this->view = null;

        $journal_message = '';
        $workitem_added = false;
        $nick = '';

        $workitem = new WorkItem();

        initUserById($userId);
        $user = new User();
        $user->findUserById( $userId );
        $nick = $user->getNickname();
        $runner_id = Project::isAllowedRunnerForProject($user->getId(), $_REQUEST['project_id']) ? $userId : '';
        $itemid = $_REQUEST['itemid'];
        $summary = $_REQUEST['summary'];
        $project_id = $_REQUEST['project_id'];
        $labels = $_REQUEST['labels'];
        $status = (Project::isAllowedRunnerForProject($user->getId(), $_REQUEST['project_id'])
                   || ($user->getIs_admin() == 1 && $user->getIs_runner())) ? $_REQUEST['status'] : 'Suggestion';
        $notes = $_REQUEST['notes'];
        $is_expense = $_REQUEST['is_expense'];
        $is_rewarder = $_REQUEST['is_rewarder'];
        $is_internal = $_REQUEST['is_internal'];
        $fileUpload = $_REQUEST['fileUpload'];

        $assigned_id = 0;
        if ((int) $_REQUEST['assigned']) {
            $assignedUser = User::find($_REQUEST['assigned']);
            if ($assignedUser->isInternal()) {
                $assigned_id = $assignedUser->getId();
            }
        }

        if (! empty($_POST['itemid'])) {
            $workitem->loadById($_POST['itemid']);
        } else {
            $workitem->setCreatorId($userId);
            $workitem_added = true;
        }
        $workitem->setSummary($summary);
        $labelsArr = explode(',', $labels);
        $workitem->setRunnerId($runner_id);
        $workitem->setProjectId($project_id);
        $workitem->setStatus($status);
        $workitem->setNotes($notes);
        $workitem->setWorkitemLabels($labelsArr);
        $workitem->setIs_internal($is_internal);
        $workitem->setAssigned_id($assigned_id);
        $workitem->save();
        $related = getRelated($notes);
        Notification::massStatusNotify($workitem);

        if ($assigned_id) {
            $emailTemplate = 'job-assigned';
            $data = array(
                'job_id' => $workitem->getId(),
                'summary' => $workitem->getSummary(),
                'assigner' => $user->getNickname(),
                'assigned' => $assignedUser->getNickname()
            );
            $senderEmail = 'Worklist - ' . $user->getNickname() . ' <contact@worklist.net> ';
            sendTemplateEmail($assignedUser->getUsername(), $emailTemplate, $data, $senderEmail);
        }

        // if files were uploaded, update their workitem id
        $file = new File();
        // update images first
        if (isset($fileUpload['uploads'])) {
            foreach ($fileUpload['uploads'] as $image) {
                $file->findFileById($image);
                $file->setWorkitem($workitem->getId());
                $file->save();
            }
        }
        if (empty($_POST['itemid'])) {
            $bid_fee_itemid = $workitem->getId();
            $journal_message .= "\\\\#"  . $bid_fee_itemid . ' created by @' . $nick . ' Status set to ' . $status;
            if (!empty($_POST['files'])) {
                $files = explode(',', $_POST['files']);
                foreach ($files as $file) {
                    $sql = 'UPDATE `' . FILES . '` SET `workitem` = ' . $bid_fee_itemid . ' WHERE `id` = ' . (int)$file;
                    mysql_query($sql);
                }
            }
        } else {
            $bid_fee_itemid = $itemid;
            $journal_message .= '\\#' . $bid_fee_itemid . ' updated by ' . $nick . 'Status set to ' . $status;
        }
        $journal_message .=  "$related. ";

        // don't send any journal notifications for DRAFTS
        if (!empty($journal_message) && $status != 'Draft') {

            sendJournalNotification(stripslashes($journal_message));

            if ($workitem_added) {
                $options = array(
                    'type' => 'workitem-add',
                    'workitem' => $workitem,
                );
                $data = array(
                    'notes' => $notes,
                    'nick' => $nick,
                    'status' => $status
                );

                Notification::workitemNotifyHipchat($options, $data);
            }

            // workitem mentions
            $matches = array();
            if (preg_match_all(
                '/@(\w+)/',
                $workitem->getNotes(),
                $matches,
                PREG_SET_ORDER
            )) {

                foreach ($matches as $mention) {
                    // validate the username actually exists
                    if ($recipient = User::find($mention[1])) {

                        // exclude creator, designer, developer and followers
                        if (
                            $recipient->getId() != $workitem->getRunnerId() &&
                            $recipient->getId() != $workitem->getMechanicId() &&
                            $recipient->getId() != $workitem->getCreatorId() &&
                            ! $workitem->isUserFollowing($recipient->getId())
                        ) {
                            $emailTemplate = 'workitem-mention';
                            $data = array(
                                'job_id' => $workitem->getId(),
                                'summary' => $workitem->getSummary(),
                                'author' => $_SESSION['nickname'],
                                'text' => $workitem->getNotes(),
                                'link' => '<a href="' . WORKLIST_URL . $workitem->getId() . '">See the workitem</a>'
                            );

                            $senderEmail = 'Worklist - ' . $_SESSION['nickname'] . ' <contact@worklist.net> ';
                            sendTemplateEmail($recipient->getUsername(), $emailTemplate, $data, $senderEmail);
                        }
                    }
                }
            }
        }

        // Notify Runners of new suggested task
        if ($status == 'Suggestion' && $project_id != '') {
            $options = array(
                'type' => 'suggested',
                'workitem' => $workitem,
                'recipients' => array('projectRunners')
            );
            $data = array(
                'notes' => $notes,
                'nick' => $nick,
                'status' => $status
            );

            Notification::workitemNotify($options, $data);
        }

        echo json_encode(array(
            'return' => "Done!",
            'workitem' => $workitem->getId()
        ));

    }

    /**
     * Toggle the is_internal field for a Workitem/Job
     *
     * The user must be internal in order to perform this action, therefore
     * we pass the session user to the method
     */
    public function toggleInternal($job_id) {
        $workitem = new WorkItem($job_id);
        $resp = $workitem->toggleInternal($_SESSION['userid']);
        $this->view = null;

        echo json_encode(array(
            'success' => true,
            'message' => 'Internal toggled: ' . $resp
        ));
    }


    /**
     * Toggle the is_internal field for a Workitem/Job
     *
     * The user must be internal in order to perform this action, therefore
     * we pass the session user to the method
     */
    public function toggleFollowing($job_id) {
        $this->view = null;
        $workitem = new WorkItem($job_id);
        $resp = $workitem->toggleUserFollowing($_SESSION['userid']);

        echo json_encode(array(
            'success' => true,
            'message' => 'Following Toggled' . $resp
        ));
    }

    public function startCodeReview($id) {
        $this->view = null;

        /**
         * Avoid Code Review races issues by opening a shared block
         * memory globally exclusive by using the job id as key.
         */
        do {
            // inmediatly after first parallel reviewer has run this shmop_open line
            $sem_id = shmop_open($workitem_id, "n", 0644, 1);
        } while ($sem_id === false); // parallel attempts need to hang until shmop_delete is called

        try {
            $workitem = new WorkItem($id);
            $user = User::find(getSessionUserId());
            if (!$user->isEligible() || $userId == $workitem->getMechanicId()) {
                throw new Exception('Action not allowed');
            }
            $status = $workitem->startCodeReview($user->getId());
            if ($status === null) {
                throw new Exception('Code Review not available right now');
            } else if ($status === true || (int) $status == 0) {
                $journal_message = '@' . $user->getNickname() . ' has started a code review for #' . $id. ' ';
                sendJournalNotification($journal_message);
                Notification::workitemNotifyHipchat(array(
                    'type' => 'code-review-started',
                    'workitem' => $workitem,
                ), array('nick' => $user->getNickname()));
                echo json_encode(array(
                    'success' => true,
                    'message' => $journal_message,
                    'codeReview' => array(
                        'started' => true,
                        'feeAmount' => $this->getCRFee($workitem),
                    )
                ));
            }
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }

        // running this line means that parallel request is freed from the shmop_open while
        shmop_delete($sem_id);
    }

    public function cancelCodeReview($id) {
        $this->view = null;
        try {
            $workitem = new WorkItem($id);
            $user = User::find(getSessionUserId());
            if (
                !$user->isEligible() || $workitem->getCRStarted() != 1 ||
                $workitem->getCRCompleted() == 1 || !$this->hasCodeReviewRights($user->getId(), $workitem)
            ) {
                throw new Exception('Action not allowed');
            }
            $workitem->setCRStarted(0);
            $workitem->setCReviewerId(0);
            $workitem->save();
            $journal_message = '@' . $user->getNickname() . ' has canceled their code review for #' . $id. ' ';
            sendJournalNotification($journal_message);
            Notification::workitemNotifyHipchat(array(
                'type' => 'code-review-canceled',
                'workitem' => $workitem,
            ), array('nick' => $user->getNickname()));
            echo json_encode(array(
                'success' => true,
                'message' => $journal_message,
                'codeReview' => $codeReviewInfo
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function endCodeReview($id, $fee = 0) {
        $this->view = null;
        try {
            $workitem = new WorkItem($id);
            $user = User::find(getSessionUserId());
            if (
                !$user->isEligible() || $workitem->getCRStarted() != 1 ||
                $workitem->getCRCompleted() == 1 || !$this->hasCodeReviewRights($user->getId(), $workitem)
            ) {
                throw new Exception('Action not allowed');
            }

            $desc = strlen(trim($_POST['desc'])) ? 'Code Review - ' . trim($_POST['desc']) : '';
            $journal_message = AddFee($workitem->getId(), $fee, 'Code Review', $desc, $workitem->getCReviewerId(), '', '');
            sendJournalNotification($journal_message);
            $workitem->setCRCompleted(1);
            $workitem->save();
            $myRunner = User::find($workitem->getRunnerId());
            $myRunner->updateBudget(-$fee, $workitem->getBudget_id());
            $journal_message = '@' . $_SESSION['nickname'] . ' has completed their code review for #' . $workitem->getId();
            sendJournalNotification($journal_message);
            $options = array(
                'type' => 'code-review-completed',
                'workitem' => $workitem,
                'recipients' => array('runner', 'mechanic', 'followers')
            );
            Notification::workitemNotify($options);
            Notification::workitemNotifyHipchat($options, array(
                'nick' => $_SESSION['nickname']
            ));
            echo json_encode(array(
                'success' => true,
                'message' => $journal_message
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function updateSandboxUrl($id) {
        $this->view = null;
        try {
            $workitem = new WorkItem($id);
            $user = User::find(getSessionUserId());
            if (($workitem->getMechanicId() != $user->getId() && !$workitem->getIsRelRunner()) || $workitem->getStatus() == 'Done') {
                throw new Exception('Action not allowed');
            }
            $url = trim($_POST['url']);
            $notes = trim($_POST['notes']) ? trim($_POST['notes']) : null;
            $workitem->setSandbox($url);
            $workitem->save();
            if ($notes) {
                //add review notes
                $fee_amount = 0.00;
                $fee_desc = 'Review Notes: ' . $notes;
                $mechanic_id = $workitem->getMechanicId();
                $itemid = $workitem->getId();
                $is_expense = 1;
                $fee_category = '';
                AddFee($itemid, $fee_amount, $fee_category, $fee_desc, $mechanic_id, $is_expense);
            }
            $journal_message = '\\#' . $workitem->getId() . ' updated by @' . $user->getNickname() . " Branch URL: $url";
            sendJournalNotification($journal_message);
            echo json_encode(array(
                'success' => false,
                'message' => $journal_message
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    protected function hasCodeReviewRights($userId, $workitem) {
        $project = new Project();
        $project->loadById($workitem->getProjectId());
        $users_favorite = new Users_Favorite();

        if($project->isCodeReviewer($userId)){
            return true;
        }
        return false;
    }

    protected function sendMailToDiscardedBids($worklist_id)    {
        // Get all bids marked as not accepted
        $query = "SELECT bids.email, u.nickname FROM ".BIDS." as bids
                        INNER JOIN ".USERS." as u on (u.id = bids.bidder_id)
                        WHERE bids.worklist_id=$worklist_id AND bids.withdrawn = 0 AND bids.accepted = 0";
        $result_query = mysql_query($query);
        $bids = array();
        while($row = mysql_fetch_assoc($result_query)) {
            $bids[] = $row;
        }

        $workitem = new WorkItem($worklist_id);
        $mechanic = $workitem->getMechanic()->getUsername();
        foreach ( $bids as $bid ) {
            // Make sure the mechanic is not sent a discarded email
            if ($mechanic != $bid['email']){
                Notification::workitemNotify(
                    array(
                        'type' => 'bid_discarded',
                        'workitem' => $workitem,
                        'emails' => array($bid['email'])
                    ),
                    array(
                        'who' => $bid['nickname']
                    ));
            }
        }
    }

    protected function changeStatus($workitem, $newStatus, $user) {

        $allowable = array("Draft", "Suggestion", "Code Review", "QA Ready", "Pass", "Merged");

        if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && ($is_runner))) {
            if($newStatus == 'Bidding' && in_array($workitem->getStatus(), $allowable)) {
                $workitem->setRunnerId($user->getId());
            }
        }

        if ($newStatus == 'Done' && $workitem->getProjectId() == 0) {
            return false;
        }
    // Set all 0.00 fees as paid
        if ($newStatus == 'Done' ) {
            if (!$workitem->flagAll0FeesAsPaid()) {
                return false;
            }
        }


        $workitem->setStatus($newStatus);
        $projectId = $workitem->getProjectId();
        $thisProject = new Project($projectId);
        $repoType = $thisProject->getRepo_type();

        // Generate diff and send to pastebin if we're in REVIEW
        if ($newStatus == "Code Review") {
            //reset code_review flags
            $workitem->resetCRFlags();
            if ($repoType == 'svn') {
                if (substr($workitem->getSandbox(), 0, 4) == "http") {

                    // Sandbox URLs look like:
                    // https://dev.worklist.net/~johncarlson21/worklist
                    // 0     12               3              4
                    $sandbox_array = explode("/", $workitem->getSandbox());

                    $username = isset($sandbox_array[3]) ? $sandbox_array[3] : "~";
                    $username = substr($username, 1); // eliminate the tilde

                    $sandbox = isset($sandbox_array[4]) ? $sandbox_array[4] : "";

                    try {
                        $result = SandBoxUtil::pasteSandboxDiff($username, $workitem->getId(), $sandbox);
                        $comment = "Code Review available here:\n$result";
                        $rt = $this->addComment($workitem->getId(), $user->getId(), $comment);
                    } catch (Exception $ex) {
                        error_log("Could not paste diff: \n$ex");
                    }
                }
            } elseif ($repoType == 'git') {
                $GitHubUser = new User($workitem->getMechanicId());
                $pullResults = $GitHubUser->createPullRequest($workitem->getId(), $workitem->getSummary(), $thisProject);

                if (!$pullResults['error'] && !isset($pullResults['data']['errors'])) {
                    $codeReviewURL = $pullResults['data']['html_url'] . '/files';
                    $comment = "Code Review available here:\n" . $codeReviewURL;
                } else {
                    $comment = $pullResults['error']
                        ? "We had problems making your request to GitHub\n"
                        : "The following error was returned when making your pull request:\n";
                    $comment .= isset($pullResults['data']['errors'])
                        ? $pullResults['data']['errors'][0]['message']
                        : "Unknown error";
                }
                $rt = $this->addComment($workitem->getId(), $user->getId(), $comment);
            }
        }

        if ($newStatus == 'QA Ready' && $repoType == 'git') {
            $runner = $workitem->getRunnerId();
            $GitHubUser = new User($runner);
            $runnerEmail = $GitHubUser->getUsername();
            $GitHubBidder = new User($workitem->getMechanicId());
            $githubDetails = $GitHubBidder->getGitHubUserDetails($thisProject);
            $gitHubUsername = $githubDetails['data']['login'];
            $repoDetails = $thisProject->extractOwnerAndNameFromRepoURL();
            $usersFork = 'https://github.com/' . $gitHubUsername . "/" . $repoDetails['name'] . ".git";
            $data = array(
                'branch_name' => $workitem->getId(),
                'runner' => $GitHubUser->getNickname(),
                'users_fork' => $usersFork,
                'master_repo' => str_replace('https://', 'git://', $thisProject->getRepository())
            );
            $senderEmail = 'Worklist <contact@worklist.net>';
            sendTemplateEmail($runnerEmail, $emailTemplate, $data, $senderEmail);
        } else if ($newStatus =='QA Ready' && ! ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 ))) {
            return true;
        }

        if ($newStatus == 'In Progress') {
            $thisProject->setActive(1);
            $thisProject->save();
            $options = array(
                'type' => 'status-notify',
                'workitem' => $workitem,
            );
            $data = array(
                'nick' => $user->getNickname(),
                'status' => $newStatus,
            );
            Notification::workitemNotifyHipchat($options, $data);
        }
        // notifications for subscribed users
        Notification::massStatusNotify($workitem);

        if ($newStatus == 'Bidding') {
            $options = array(
                'type' => 'new_bidding',
                'workitem' => $workitem,
            );
            Notification::massStatusNotify($workitem);
        }
        if ($newStatus == 'Code Review') {
            $options = array(
                'type' => 'new_review',
                'workitem' => $workitem,
            );
            Notification::massStatusNotify($workitem);
        }
        return true;
    }

    protected function addComment($worklist_id, $user_id, $comment_text, $parent_comment_id) {
        // in case the comment is a reply to another comment,
        // we'll fetch the original comment's email <mikewasmike>
        $comment = new Comment();
        $comment->setWorklist_id((int) $worklist_id);
        $comment->setUser_id((int) $user_id);
        $correspondent = null;

        if (isset($parent_comment_id)) {
            $comment->setComment_id((int) $parent_comment_id);
            $originalComment = new Comment();
            $originalComment->findCommentById((int) $parent_comment_id);
            $cuser = new User();
            $cuser->findUserById($originalComment->getUser_id());
            // add the author of the parent comment, as long as it's not the
            // same as the logged in user, in order to prevent email notification
            // to the author of the new comment
            if ($cuser->isActive() && ($cuser->getId() != getSessionUserId())) {
                $correspondent = array($cuser->getUsername());
            } else {
                $correspondent = array();
            }
        }

        $comment->setComment($comment_text);

        try {
            $id = $comment->save();
        } catch(Exception $e) {
            error_log("Failure saving comment:\n".$e);
        }
        $redirectToDefaultView = true;
        $result = array('correspondent' => $correspondent, 'id' => $id);
        return $result;
    }

    protected function getTaskPosts($item_id) {
        $entry = new EntryModel();
        return $entry->latestFromTask($item_id);
    }

    protected function getCRFee($workitem) {
        $accepted_bid_amount = 0;
        foreach ($workitem->getFees($workitem->getId()) as $fee){
            if ($fee['desc'] == 'Accepted Bid') {
                $accepted_bid_amount = $fee['amount'];
            }
        }
        $project = new Project();
        $project_roles = $project->getRoles($workitem->getProjectId(), "role_title = 'Reviewer'");
        if (count($project_roles) != 0) {
            $crRole = $project_roles[0];
            if ($crRole['percentage'] !== null && $crRole['min_amount'] !== null) {
                $crFee = ($crRole['percentage'] / 100) * $accepted_bid_amount;
                return ((float) $crFee < $crRole['min_amount']) ? $crRole['min_amount'] : $crFee;
            }
        }
        return 0;
    }

    public function search() {
        $this->view = null;

        $user = User::find(getSessionUserId());
        $isRunner = $user->is_runner;
        $publicOnly = !$user->isInternal();

        $query = isset($_REQUEST['query']) ? $_REQUEST['query'] : null;
        if ($query != null) {
            if (preg_match("/^\#?\d+$/", $query)) {
                $id = ltrim($query, '#');
                if (Project::isJobId($id)) {
                    echo json_encode(array('redirect' => $id));
                    return;
                }
            }
        }

        $conds = array();
        $groupConds = array();
        $extraFields = array();

        $projectFilter = isset($_REQUEST['project_id']) ? $_REQUEST['project_id'] : '';
        if (!empty($projectFilter) && $projectFilter != 'All') {
            $projectId = (int) $projectFilter;
            $conds[] = "`w`.`project_id` = '{$projectId}'";
        }

        $statusFilter = isset($_REQUEST['status']) && !empty($_REQUEST['status'])
            ? preg_split('/,/', $_REQUEST['status'])
            :
                empty($query)
                    ? array('Active')
                    : array();
        if ($statusFilter) {
            $statusCond = '';
            foreach ($statusFilter as $status) {
                if (empty($status)) {
                    continue;
                }
                switch($status) {
                    case 'Draft':
                    case 'Suggestion':
                    case 'Bidding':
                    case 'In Progress':
                    case 'QA Ready':
                    case 'Review':
                    case 'Merged':
                    case 'Done':
                    case 'Pass':
                        $statusCond .= (empty($statusCond) ? '' : ' OR ') .
                            "`w`.`status` = '{$status}'";
                        break;

                    // Pseudo status filters
                    case 'Code Review':
                        $statusCond .= (empty($statusCond) ? '' : ' OR ') .
                            "`w`.`status` = 'Review'";
                        break;
                    case 'Needs-Review':
                        $statusCond .= (empty($statusCond) ? '' : ' OR ') . "
                                `w`.`status` = 'Review'
                            AND `w`.`code_review_started` = 0";
                        break;
                    case 'Active':
                        $statusCond .= (empty($statusCond) ? '' : ' OR ') . "
                               `w`.`status` = 'Suggestion'
                            OR `w`.`status` = 'Bidding'
                            OR `w`.`status` = 'In Progress'
                            OR `w`.`status` = 'QA Ready'
                            OR `w`.`status` = 'Review'
                            OR `w`.`status` = 'Merged'";
                        break;
                }
            }
            if (!empty($statusCond)) {
                $conds[] = "({$statusCond})";
            }
        }

        $participants = array();
        if (isset($_REQUEST['participated']) && !empty($_REQUEST['participated'])) {
            $participants = preg_split('/,/', $_REQUEST['participated']);
        }

        $mentions = array();
        $groupedMentions = '';
        if (preg_match_all(
            '/@(\w+)/',
            $query,
            $mentions,
            PREG_SET_ORDER
        )) {
            $mentionUsersFound = array();
            foreach ($mentions as $mention) {
                $mentionUser = User::find($mention[1]);
                if ($mentionId = $mentionUser->getId()) {
                    $mentionUsersFound[] = $mentionUser->getNickname();
                    $participants[] = $mentionId;
                    $query = trim(preg_replace('/@' . $mentionUser->getNickname() . '/', '', $query));
                }
            }
            $groupedMentions = '(' . implode(' ', $mentionUsersFound) . ')';
        }

        if (count($participants) > 0) {
            $extraFields[] = "GROUP_CONCAT(DISTINCT `com`.`user_id`) AS `commentators`";
            $extraFields[] = "GROUP_CONCAT(DISTINCT `f`.`user_id`) AS `payees`";
            if ($isRunner || in_array($user->getId(), $participants)) {
                $extraFields[] = "GROUP_CONCAT(DISTINCT `b`.`bidder_id`) AS `bidders`";
            }

            foreach($participants as $participant) {
                $participantUser = User::find($participant);
                if (!$participantId = $participantUser->getId()) {
                    continue;
                }
                $participantNickname = $participantUser->getNickname();
                $fieldName = "{$participantNickname}Mentioned";
                $extraFields[] = "SUM(
                    CASE WHEN
                          MATCH(`w`.`summary`, `w`.`notes`)
                            AGAINST ('$participantNickname @$participantNickname' IN BOOLEAN MODE)
                      OR  MATCH(`f`.`notes`)
                            AGAINST ('$participantNickname @$participantNickname' IN BOOLEAN MODE)
                      OR  MATCH (`com`.`comment`)
                            AGAINST ('$participantNickname @$participantNickname' IN BOOLEAN MODE)
                    THEN 1 ELSE 0 END
                ) AS `{$fieldName}`";

                $groupConds[] = "(
                    `{$fieldName}` > 0
                    OR `w`.`mechanic_id` = '{$participantId}'
                    OR `w`.`runner_id` = '{$participantId}'
                    OR `w`.`creator_id` = '{$participantId}'
                    OR `commentators` REGEXP '(^|\:){$participantId}($|\:)'
                    OR `payees` REGEXP '(^|\:){$participantId}($|\:)'
                    " . (
                        ($isRunner || $user->getId() == $participantId)
                            ? "OR `bidders` REGEXP '(^|\:){$participantId}($|\:)'"
                            : ''
                    ) . "
                )";
            }
        }

        if ($publicOnly) {
            $conds[] .= "`w`.is_internal = 0";
        }

        if (isset($_REQUEST['following']) && !empty($_REQUEST["following"])) {
            $conds[] = "`fol`.`user_id` IS NOT NULL";
        }

        /* text search */
        if ($query != null) {
            $safeQuery = str_replace('\'', '\\\'', rawurldecode($query));
            $conds[] = "(
                   MATCH(`w`.`summary`, `w`.`notes`)
                     AGAINST ('$safeQuery' IN BOOLEAN MODE)
                OR MATCH(`f`.`notes`)
                  AGAINST ('$safeQuery' IN BOOLEAN MODE)
                OR MATCH (`com`.`comment`)
                  AGAINST ('$safeQuery' IN BOOLEAN MODE)
            )";
        }

        /* labels filter */
        if (isset($_REQUEST["labels"]) && !empty($_REQUEST["labels"])) {
            $labels = preg_split('/,/', $_REQUEST["labels"]);
            if ($labels) {
                $labelsCond = '';
                foreach($labels as $label) {
                    if (!strlen(trim($label))) {
                        continue;
                    }
                    $label = mysql_real_escape_string($label);
                    $labelsCond .= (strlen($labelsCond) ? ' AND ' : '') .
                        "labels LIKE '%:{$label}:%'";
                }
                if ($labelsCond) {
                    $groupConds[] = "({$labelsCond})";
                }
            }
        }

        echo json_encode(WorkItem::search($query, $conds, $groupConds, $extraFields, $_REQUEST['offset'], $_REQUEST['limit']));
    }

    public function edit($worklist_id = 0) {
        if (isset($_POST['api_key'])) {
            validateAPIKey();
            $user = User::find($_POST['creator']);
            $userId = $user->getId();
        } else {
            checkLogin();
            $userId = getSessionUserId();
            $user = User::find($userId);
        }
        if (!$userId) {
            header('HTTP/1.1 401 Unauthorized', true, 401);
            echo json_encode(array('error' => "Invalid parameters !"));
            return;
        }
        $this->view = null;
        $worklist_id = isset($_REQUEST['worklist_id']) ? $_REQUEST['worklist_id'] : $worklist_id;
        $notifyEmpty = true;
        $workitem = new WorkItem();
        try {
            $workitem->loadById($worklist_id);
        } catch(Exception $e) {
            $error  = $e->getMessage();
            die($error);
        }

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            if (($workitem->isInternal() && ! $user->isInternal()) || !$this->canEdit($workitem, $user)) {
                $this->write('msg', 'You don\'t have permissions to edit this job.');
                $this->write('link', WORKLIST_URL);
                $this->view = new ErrorView();
                parent::run();
                exit;
            }
            $this->view = null;
            $this->write('jobId', $worklist_id);
            $this->write('user', $user);
            $this->write('workitem', $workitem);
            $this->view = new AddJobView();
            parent::run();
            return;
        }

        $journal_message = null;
        $status_change = '';
        $status = $user->getIs_runner() ? $_REQUEST['status'] : 'Suggestion';
        $fileUpload = isset($_REQUEST['fileUpload']) ? $_REQUEST['fileUpload'] : array();
        $statusList = $this->getStatusList($workitem, $user);
        $args = array(
                'summary',
                'notes',
                'status',
                'project_id',
                'sandbox',
                'budget_id',
                'assigned',
                'is_internal',
                'runner_id'
            );

            foreach ($args as $arg) {
                if (!empty($_REQUEST[$arg])) {
                    $$arg = $_REQUEST[$arg];
                } else {
                    $$arg = '';
                }
            }

            // code to add specifics to journal update messages
            $new_update_message='';
            $budget_id = !empty($_REQUEST['budget_id']) ? (int) $_REQUEST['budget_id'] : 0;
            $project_id = !empty($_REQUEST['project_id']) ? (int) $_REQUEST['project_id'] : 0;
            $runner_id = !empty($_REQUEST['runner_id']) ? (int) $_REQUEST['runner_id'] : 0;
            $sandbox = isset($_REQUEST['sandbox']) ?  $_REQUEST['sandbox'] : '';
            $old_budget_id = -1;

            if (isset($_REQUEST['budget_id']) && $workitem->getBudget_id() != $budget_id) {
                $new_update_message .= 'Budget changed. ';
                $old_budget_id = (int) $workitem->getBudget_id();
                $workitem->setBudget_id($budget_id);
                $job_changes[] = '-budget';
            }

            if (isset($_REQUEST['is_internal']) && $user->isInternal()
                && $workitem->isInternal() != (int) $_REQUEST['is_internal']) {
                $is_internal = !empty($_REQUEST['is_internal'])? (int) $_REQUEST['is_internal'] : 0;
                $workitem->setIs_internal($is_internal);
                $workitem->save();
            }
            // summary
            if (isset($_REQUEST['summary']) && $workitem->getSummary() != $_REQUEST['summary']) {
                $summary = $_REQUEST['summary'];
                $workitem->setSummary($summary);
                $new_update_message .= "Summary changed. ";
                if ($workitem->getStatus() != 'Draft') {
                    $job_changes[] = '-summary';
                }
            }

            if (isset($_REQUEST['labels']) && !empty($_REQUEST['labels'])) {
                $labelsArr = explode(',', $_REQUEST['labels']);
                // remove empty values
                foreach ($labelsArr as $key => $value) {
                    $labelsArr[$key] = trim($value);
                    if (empty($value)) {
                        unset($labelsArr[$key]);
                    }
                }
                // get current labels
                $labelsCur = $workitem->getLabels();
                // have labels been updated?
                $labelsDiff = array_diff($labelsArr, $labelsCur);
                if (is_array($labelsDiff) && !empty($labelsDiff)) {
                    if ($workitem->getStatus() != 'Draft') {
                        $new_update_message .= 'Labels updated: ' . implode(', ', $labelsArr);
                    }
                    // remove nasty end comma
                    $new_update_message = rtrim($new_update_message, ', ') . '. ';
                    $job_changes[] = '-labels';
                }

                $workitem->setWorkitemLabels($labelsArr);
                $workitem->save();
            }

            if ($workitem->getIsRelRunner() || $userId == $workitem->getRunnerId()
                || (in_array($status, $statusList))) {

                if ($workitem->getStatus() != $status && !empty($status) && $status != 'Draft') {
                    if ($this->changeStatus($workitem, $status, $user)) {
                        if (!empty($new_update_message)) {  // add commas where appropriate
                            $new_update_message .= ", ";
                        }
                        $status_change = '-' . ucfirst(strtolower($status));
                        if ($status == 'Bidding' && Project::isAllowedRunnerForProject($user->getId(), $workitem->getProjectId())) {
                            $workitem->setRunnerId($user->getId());
                        }
                        $new_update_message .= "Status set to *$status*. ";
                    }
                }
            }
            $related = "";
            $notes = trim($_REQUEST['notes']);
            if (!empty($notes) && ($workitem->getNotes() != $notes)) {
                $workitem->setNotes($notes);
                $new_update_message .= "Notes changed. ";
                $job_changes[] = '-notes';
                $related = getRelated($notes);
            }
            // project
            if ($project_id && $workitem->getProjectId() != $project_id) {
                $workitem->setProjectId($project_id);
                if ($workitem->getStatus() != 'Draft') {
                    $new_update_message .= "Project changed. ";
                    $job_changes[] = '-project';
                }
            }
            // Sandbox
            if (isset($_REQUEST['sandbox']) && $workitem->getSandbox() != $sandbox) {
                $workitem->setSandbox($sandbox);
                $new_update_message .= "Branch changed. ";
                $job_changes[] = '-branch';
            }

            // Designer
            if (isset($_REQUEST['runner_id']) && $runner_id && $workitem->getRunnerId() != $runner_id) {
                $older_runner = $workitem->getRunner();
                $workitem->setRunnerId($runner_id);
                $workitem->save();
                if (!empty($older_runner)) {
                    $runner = User::find($runner_id);
                    $message = '\\#' . $workitem->getId() . ' updated by @' . $_SESSION['nickname'] . ' Designer reassigned to @' . $runner->getNickname();
                    sendJournalNotification($message);
                    $emails = !empty($older_runner) ? array($older_runner->getUsername()) : array();
                    array_push($emails, $runner->getUsername());
                    if ($workitem->getCreator()) {
                        array_push($emails, $workitem->getCreator()->getUsername());
                    }
                    if ($workitem->getMechanic()) {
                        array_push($emails, $workitem->getMechanic()->getUsername());
                    }
                    if ($user->getSelf_notif()) {
                        array_push($emails, $user->getUsername());
                    }
                    $options = array(
                        'type' => 'change-designer',
                        'workitem' => $workitem,
                        'emails' => $emails
                    );
                    $data = array(
                        'runner_nickname' => $runner->getNickname()
                    );
                    Notification::workitemNotify($options, $data);
                }
            }

            // Assignee
            $assigned = !empty($_REQUEST['assigned'])? (int) $_REQUEST['assigned'] : 0;
            $assigneeChanged = false;
            if ($user->isInternal() && isset($_REQUEST['assigned']) && $workitem->getAssigned_id() != $assigned) {
                if ((int) $assigned == 0) {
                    $workitem->setAssigned_id(0);
                    $new_update_message .= "Assignee removed. ";
                    $job_changes[] = '-assignee';
                } else {
                    $assignedUser = User::find($assigned);
                    if ($assignedUser->isInternal()) {
                        $assigneeChanged = true;
                        $workitem->setAssigned_id($assignedUser->getId());
                        $currentStatus = $workitem->getStatus();
                        $new_update_message .= "Assignee changed. ";
                        if ($currentStatus == 'Draft' || $currentStatus == 'Suggestion') {
                            $workitem->setStatus('Bidding');
                            $new_update_message .= "Status set to *Bidding*. ";
                        }
                        $job_changes[] = '-assignee';
                    }
                }
            }

            if (empty($new_update_message)) {
                $new_update_message = " No changes.";
            } else {
                $workitem->save();
                if ($old_budget_id > 0) {
                    $budget = new Budget();
                    if ($budget->loadById($old_budget_id)) {
                        $budget->recalculateBudgetRemaining();
                    } else {
                        error_log("Old budget id not found: " . $old_budget_id);
                    }
                    if ($budget->loadById($workitem->getBudget_id())) {
                        $budget->recalculateBudgetRemaining();
                    } else {
                        error_log("New budget id not found: " . $workitem->getBudget_id());
                    }
                }
                $new_update_message = " Changes: $new_update_message";
                $notifyEmpty = false;
            }

            if ($workitem->getStatus() != 'Draft' && !$notifyEmpty) {
                $journal_message .= '\\#' . $worklist_id . ' updated by @' . $_SESSION['nickname'] .
                                    $new_update_message . $related;

                $options = array(
                    'type' => 'workitem-update',
                    'workitem' => $workitem
                );
                $data = array(
                    'nick' => $_SESSION['nickname'],
                    'new_update_message' => $new_update_message,
                    'related' => $related
                );
                Notification::workitemNotify($options, $data, false);
                Notification::workitemNotifyHipchat($options, $data);
                sendJournalNotification($journal_message);
            }

            if ($assigneeChanged) {
                $emailTemplate = 'job-assigned';
                $data = array(
                    'job_id' => $workitem->getId(),
                    'summary' => $workitem->getSummary(),
                    'assigner' => $user->getNickname(),
                    'assigned' => $assignedUser->getNickname()
                );
                $senderEmail = 'Worklist - ' . $user->getNickname() . ' <contact@worklist.net> ';
                sendTemplateEmail($assignedUser->getUsername(), $emailTemplate, $data, $senderEmail);
            }
            // if files were uploaded, update their workitem id
            $file = new File();
            // update images first
            if (isset($fileUpload['uploads'])) {
                foreach ($fileUpload['uploads'] as $image) {
                    $file->findFileById($image);
                    $file->setWorkitem($workitem->getId());
                    $file->save();
                }
            }

           if (!$notifyEmpty) {
                $options = array(
                    'type' => 'modified',
                    'workitem' => $workitem,
                    'status_change' => $status_change,
                    'job_changes' => $job_changes,
                    'recipients' => array('runner', 'creator', 'mechanic', 'followers')
                );
                $data = array(
                    'changes' => $new_update_message
                );
                Notification::workitemNotify($options, $data);
            }
            echo json_encode(array(
                'return' => "Done!",
                'workitem' => $workitem->getId(),
                'success' =>  true
            ));
    }

    private function canEdit($workitem, $user) {
       return ((($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $user->getIs_runner()))
                 && $workitem->getStatus() != 'Done') || ($workitem->getCreatorId() == $user->getId()
                 && ($workitem->getStatus() == 'Suggestion') ));
    }

    private function getStatusList($workitem, $user) {
        $statusList = array();
        if (!($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $user->getIs_runner()))
            &&($workitem->getMechanicId() == $user->getId())
            && $workitem->getStatus() != 'Done') {
            $statusList = array("In Progress", "QA Ready", "Code Review", "Merged", "Pass");
        } else if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $user->getIs_runner())) {
            $statusList = array("Draft", "Suggestion", "Bidding", "In Progress", "QA Ready", "Code Review", "Merged", "Done", "Pass");
        } else if ($workitem->getCreatorId() == $user->getId()
            && $workitem->getStatus() != 'In Progress'
            && $workitem->getStatus() != 'QA Ready' && $workitem->getStatus() != 'Review'
            && $workitem->getStatus() != 'Merged' && $workitem->getStatus() != 'Done') {
            $statusList = array("In Progress", "QA Ready", "Code Review", "Merged", "Pass", "Suggestion");
        }
        return $statusList;
    }

    public function listView($projectName = null, $filterName = null) {
        $this->view = new JobsView();
        // $nick is setup above.. and then overwritten here -- lithium
        $nick = '';
        $userId = getSessionUserId();
        if ($userId > 0) {
            initUserById($userId);
            $user = new User();
            $user->findUserById($userId);
            // @TODO: this is overwritten below..  -- lithium
            $nick = $user->getNickname();
            $userbudget =$user->getBudget();
            $budget = number_format($userbudget);
            $this->is_internal = $user->isInternal();
        }

        $this->is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
        $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
        $is_admin = !empty($_SESSION['is_admin']) ? 1 : 0;

        $workitem = new WorkItem();

        $queryFilter = empty($_REQUEST['query']) ? '' : $_REQUEST['query'];
        $this->write('queryFilter', $queryFilter);
        $this->write('followingFilter', ($filterName != null && $filterName == "following") ? true : false);

        if ($projectName != null && $projectName != "all") {
            $project = Project::find($projectName);
            $this->write('projectFilter', $project ? $project->getProjectId() : 0);
        } else {
            $this->write('projectFilter', 0);
        }

        if ($filterName != null && $filterName != "following") {
            $this->write('statusFilter', $filterName);
        } else {
            $this->write('statusFilter', empty($queryFilter) ? 'Active' : 'All');
        }

        $this->write('labelsFilter', array_slice(func_get_args(), 2));

        // Prevent reposts on refresh
        if (! empty($_POST)) {
            unset($_POST);
            $this->view = null;
            Utils::redirect('./jobs');
            exit();
        }

        $worklist_id = isset($_REQUEST['job_id']) ? intval($_REQUEST['job_id']) : 0;

        $this->write('req_status', isset($_GET['status']) ? $_GET['status'] : '');
        $this->write('review_only', (isset($_GET['status']) &&  $_GET['status'] == 'needs-review') ? 'true' : 'false');
        parent::run();
    }
}
