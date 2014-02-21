<?php

require_once('lib/Sms.php');
require_once('models/DataObject.php');
require_once('models/Budget.php');
require_once('models/Users_Favorite.php');

class JobController extends Controller {
    public function run($job_id) {
        $this->write('statusListRunner', array("Draft", "Suggested", "SuggestedWithBid", "Bidding", "Working", "Functional", "Review", "Completed", "Done", "Pass"));
        $statusListMechanic = array("Working", "Functional", "Review", "Completed", "Pass");
        $this->write('statusListMechanic', $statusListMechanic);
        $this->write('statusListCreator', array("Suggested", "Pass"));

        $get_variable = 'job_id';
        if (! defined("WORKITEM_URL")) { define("WORKITEM_URL", SERVER_URL . "job/"); }
        if (! defined("WORKLIST_REDIRECT_URL")) { define("WORKLIST_REDIRECT_URL", SERVER_URL . "jobs?$get_variable="); }
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

        // TODO: Would be good to take out all the checks for isset($_SESSION['userid'] etc. and have them use $user instead, check $user->getId() > 0.
        if (empty($worklist_id)) {
            return;
        } else {
            // feed links will be made specific to the workitem
            $inWorkItem = true;
        }
        //Set an empty variable for $journal_message to avoid errors/warnings with .=
        $journal_message = null;

        //initialize the workitem class
        $workitem = new WorkItem();
        try {
            $workitem->loadById($worklist_id);
        } catch(Exception $e) {
            $error  = $e->getMessage();
            die($error);
        }

        // we need to be able to grant runner rights to a project founder for all jobs for their project
        $workitem_project = Project::getById($workitem->getProjectId());
        $is_project_founder = false;
        if($workitem_project->getOwnerId() == $_SESSION['userid']){
            $is_project_founder = true;
        }
        $this->write('is_project_founder', $is_project_founder);

        $this->write('isGitHubConnected', $user->isGithub_connected($workitem_project->getGithubId()));

        //used for is_project_runner rights
        $is_project_runner = false;
        if($workitem->getIsRelRunner() == 1){
            $is_project_runner = true;
        }
        $this->write('is_project_runner', $is_project_runner);

        $redirectToDefaultView = false;
        $redirectToWorklistView = false;
        $promptForReviewUrl = true;
        $runner_budget = $user->getBudget();

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

        if ($workitem->getStatus() == 'Done' && $action == 'edit') {
            $action = 'view';
        }

        $view_bid_id = 0;
        $order_by = isset($_REQUEST['order']) ? $_REQUEST['order'] : 'ASC';
        if ($order_by != "DESC") {
            $order_by = "ASC";
        }

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
        } else if(isset($_REQUEST['save-review-url'])) {
            $action = "save-review-url";
        } else if(isset($_REQUEST['invite-people'])) {
            $action = "invite-people";
        } else if (isset($_REQUEST['newcomment'])) {
            $action = 'new-comment';
        } else if (isset($_REQUEST['start_codereview'])) {
            $action = "start_codereview";
        } else if (isset($_REQUEST['finish_codereview'])) {
            $action = "finish_codereview";
        } else if (isset($_REQUEST['cancel_codereview'])) {
            $action = "cancel_codereview";
        }



        if ($action == 'view_bid') {
            $action = "view";
            $view_bid_id = isset($_REQUEST['bid_id']) ? $_REQUEST['bid_id'] : 0;
        }

        // for any other action user has to be logged in
        if ($action != 'view' && $action != 'invite-people') {
            checkLogin();
            $action_error = '';
            $action = $workitem->validateAction($action, $action_error);
        }

        // Save WorkItem was requested. We only support Update here
        $notifyEmpty = true;
        $job_changes = array();
        $status_change = '';
        if ($action =='save_workitem') {
            $args = array(
                'summary',
                'notes',
                'status',
                'project_id',
                'sandbox',
                'skills',
                'is_bug',
                'bug_job_id',
                'budget-source-combo'
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
            $is_bug = !empty($_REQUEST['is_bug'])? 1 : 0;
            $budget_id = !empty($_REQUEST['budget-source-combo'])? (int) $_REQUEST['budget-source-combo'] : 0;
            // First check to see if this is marked as a bug
            if ($workitem->getIs_bug() != $is_bug) {
                error_log("bug changed it");
                if($is_bug) {
                    $new_update_message .= 'Marked as a bug. ';
                } else {
                    $new_update_message .= 'Marked as not being a bug. ';
                }
                $job_changes[] = '-bug';
            }
            $workitem->setIs_bug($is_bug);
            $old_budget_id = -1;
            if ($workitem->getBudget_id() != $budget_id) {
                $new_update_message .= 'Budget changed. ';
                $old_budget_id = (int) $workitem->getBudget_id();
                $workitem->setBudget_id($budget_id);
            }
            // summary
            if (isset($_REQUEST['summary']) && $workitem->getSummary() != $summary) {
                $workitem->setSummary($summary);
                $new_update_message .= "Summary changed. ";
                if ($workitem->getStatus() != 'Draft') {
                    $job_changes[] = '-summary';
                }
            }

            if (isset($_REQUEST['skills'])) {
                $skillsArr = explode(', ', $skills);
                // remove empty values
                foreach ($skillsArr as $key => $value) {
                    if (empty($value)) {
                        unset($skillsArr[$key]);
                    }
                }
                // get current skills
                $skillsCur = $workitem->getSkills();
                // have skills been updated?
                $skillsDiff = array_diff($skillsArr, $skillsCur);
                if (is_array($skillsDiff) && ! empty($skillsDiff)) {
                    if ($workitem->getStatus() != 'Draft') {
                        $new_update_message .= 'Skills updated: ' . implode(', ', $skillsArr);
                    }
                    // remove nasty end comma
                    $new_update_message = rtrim($new_update_message, ', ') . '. ';
                    $job_changes[] = '-skills';
                }
                $workitem->setWorkitemSkills($skillsArr);
            }

            // status
            if ($is_project_runner
                || $userId == $workitem->getRunnerId()
                || (in_array($status, $statusListMechanic))) {

                if ($workitem->getStatus() != $status && !empty($status) && $status != 'Draft') {
                    if ($this->changeStatus($workitem, $status, $user)) {
                        if (!empty($new_update_message)) {  // add commas where appropriate
                            $new_update_message .= ", ";
                        }
                        $status_change = '-' . ucfirst(strtolower($status));
                        $new_update_message .= "Status set to $status. ";
                        if ($status == 'Done') {
                            $displayDialogAfterDone = true;
                        }
                    }
                }
            }
            $related = "";
            if ($workitem->getNotes() != $notes && isset($_REQUEST['notes'])) {
                $workitem->setNotes($notes);
                $new_update_message .= "Notes changed. ";
                $job_changes[] = '-notes';
                $related = getRelated($notes);
            }
            // project

            if ($workitem->getProjectId() != $project_id) {
                $workitem->setProjectId($project_id);
                if ($workitem->getStatus() != 'Draft') {
                    $new_update_message .= "Project changed. ";
                    $job_changes[] = '-project';
                }
            }
            // Sandbox
            if ($workitem->getSandbox() != $sandbox) {
                $workitem->setSandbox($sandbox);
                $new_update_message .= "Sandbox changed. ";
                $job_changes[] = '-sandbox';
            }
            // Send invites
            if (!empty($_REQUEST['invite'])) {
                $people = explode(',', $_REQUEST['invite']);
                invitePeople($people, $workitem);
                $new_update_message .= "Invitations sent. ";
                $job_changes[] = '-invitation';
            }
            //Check if bug_job_id has changed and send notifications if it has
            if($workitem->getBugJobId() != $bug_job_id) {
                //Bug job Id changed
                $workitem->setBugJobId($bug_job_id);
                $new_update_message .= "Bug job Id changed. ";
                $job_changes[] = '-bug job id';
                if($bug_job_id > 0) {
                    //Load information about original job and notify
                    //users with fees and runner
                    Notification::workitemNotify(array('type' => 'bug_found',
                                                    'workitem' => $workitem,
                                                    'recipients' => array('runner', 'usersWithFeesBug')));
                    Notification::workitemSMSNotify(array('type' => 'bug_found',
                                                    'workitem' => $workitem,
                                                    'recipients' => array('runner', 'usersWithFeesBug')));
                }
            }
            
            //if job is a bug, notify to journal
            if($bug_job_id > 0) {
                $workitem->setIs_bug(1);
                $bugJournalMessage= " (bug of #" . $workitem->getBugJobId() .")";
            } elseif (isset($_REQUEST['is_bug']) && $_REQUEST['is_bug'] == 'on') {
                $bugJournalMessage = " (which is a bug)";
            } elseif (isset($is_bug) && $is_bug == 1) {
                $bugJournalMessage = " (which is a bug)";
            } else {
                $bugJournalMessage= "";
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

             $redirectToDefaultView = true;
             if ($workitem->getStatus() != 'Draft') {
                $journal_message .= $_SESSION['nickname'] . " updated item #$worklist_id " .
                                    $bugJournalMessage  .": ". $workitem->getSummary() .
                                    $new_update_message . $related;
                
                $options = array(
                    'type' => 'workitem-update',
                    'workitem' => $workitem
                );
                $data = array(
                    'nick' => $_SESSION['nickname'],
                    'bug_journal_message' => $bugJournalMessage,
                    'new_update_message' => $new_update_message,
                    'related' => $related
                );
                Notification::workitemNotifyHipchat($options, $data);
             }
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

                if ($_POST['order_by'] != "DESC") {
                    $order_by = "ASC";
                } else {
                    $order_by = "DESC";
                }

                
                // Send journal notification
                if ($workitem->getStatus() != 'Draft') {
                    $related = getRelated($comment);
                    $journal_message .= $_SESSION['nickname'] . " posted a comment on issue #$worklist_id: " . $workitem->getSummary() . $related;
                    
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
                        'related' => $related
                    );
                    
                    Notification::workitemNotify($options, $data, false);
                    Notification::workitemNotifyHipchat($options, $data);
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
                        'date' => relativeTime(strtotime($comment->getDate()) - time()));
                ob_start();
                $json = json_encode($result);
            } else {
                $json = json_encode(array('success' => false));
            }
            echo $json;
            ob_end_flush();
            exit;
        }

        if($action =='invite-people') {
           
            // Send invitation
            $people = explode(',', $_REQUEST['invite']);
            $nonExistingPeople = invitePeople($people, $workitem);
            $json = json_encode($nonExistingPeople);
            echo $json;
            exit;
           
        }
        if($action == 'start_codereview') {
            if(!($user->isEligible() && $userId == $workitem->getMechanicId())) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $workitem->setCRStarted(1);
                $workitem->save();
                $journal_message = $_SESSION['nickname'] . " has started a code review for #$worklist_id: " . $workitem->getSummary();
                
                $options = array(
                    'type' => 'code-review-started',
                    'workitem' => $workitem
                );
                $data = array(
                    'nick' => $_SESSION['nickname']
                );
                Notification::workitemNotifyHipchat($options, $data);
            }
        }

        if($action == 'finish_codereview') {
            // ensure user is alowed to end review, and review is open
            if(!($user->isEligible() &&
            $workitem->getCRStarted() == 1 &&
            $workitem->getCRCompleted() != 1 &&
            $this->hasRights($userId, $workitem))) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $args = array('itemid', 'crfee_amount', 'fee_category', 'crfee_desc', 'is_expense', 'is_rewarder');
                foreach ($args as $arg) {
                    if (isset($_REQUEST[$arg])) {
                           $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                    } else {
                        $$arg = '';
                    }
                } 
                if($crfee_desc == '') {
                    $crfee_desc = 'Code Review';
                } else {
                    $crfee_desc = 'Code Review - '. $crfee_desc;
                }
                $journal_message = AddFee($itemid, $crfee_amount, $fee_category, $crfee_desc, $workitem->getCReviewerId(), $is_expense, $is_rewarder);
                sendJournalNotification($journal_message);
                $workitem->setCRCompleted(1);
                $workitem->save();

                $myRunner = new User();
                $myRunner->findUserById($workitem->getRunnerId());
                $myRunner->updateBudget(-$crfee_amount, $workitem->getBudget_id());

                if(Notification::isNotified($myRunner->getNotifications(), Notification::MY_BIDS_NOTIFICATIONS)) {
                    Notification::sendShortSMS($myRunner, 'Fee', $journal_message, WORKITEM_URL . $worklist_id);
                }
                
                $journal_message = $_SESSION['nickname'] . " has completed their code review for #$worklist_id: " . $workitem->getSummary();
                
                $options = array(
                    'type' => 'code-review-completed',
                    'workitem' => $workitem,
                    'recipients' => array('runner', 'mechanic', 'followers')
                );
                Notification::workitemNotify($options);
                
                $data = array(
                    'nick' => $_SESSION['nickname']
                );
                Notification::workitemNotifyHipchat($options, $data);
            }
        }

        if($action == 'cancel_codereview') {
            // ensure user is allowed to cancel review, and review is open
            if(!($user->isEligible() &&
            $workitem->getCRStarted() == 1 &&
            $workitem->getCRCompleted() != 1 &&
            $this->hasRights($userId, $workitem))) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $workitem->setCRStarted(0);
                $workitem->save();
                $journal_message = $_SESSION['nickname'] . " has canceled their code review for #$worklist_id: " . $workitem->getSummary();
                
                $options = array(
                    'type' => 'code-review-canceled',
                    'workitem' => $workitem,
                );
                $data = array(
                    'nick' => $_SESSION['nickname'],
                );
                Notification::workitemNotifyHipchat($options, $data);
            }    
        }

        if($action =='save-review-url') {
            if(!($is_project_runner || 
            ($mechanic_id == $user_id) &&
            ($worklist['status'] != 'Done'))) {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            } else {
                $sandbox = (!empty($_REQUEST['sandbox-url'])) ? $_REQUEST['sandbox-url'] : $workitem->getSandbox();
                $notes = (!empty($_REQUEST['review-notes'])) ? $_REQUEST['review-notes'] : null;
                
                $status_review = $_REQUEST['quick-status-review'];
                $status_error = false;

                if(! empty($status_review) && $workitem->getStatus() != $status_review) {
                    $old_status = $workitem->getStatus();

                    $status = $this->changeStatus($workitem, $status_review, $user);

                    if ($status !== true) {
                        // status change failed due to sandbox issues
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
                        // revert to the old status, but still save the sandbox change
                        $workitem->setStatus($old_status);
                    }
                }
                $workitem->setSandbox($sandbox);
                $workitem->save();
                if ($status_error === false) {
                    $new_update_message = " sandbox url : $sandbox ";
                    if(!empty($status_review)) {
                        $new_update_message .= " Status set to $status_review. ";
                        $status_change = '-' . ucfirst(strtolower($status_review));
                    } else {
                        $job_changes[] = '-sandbox';
                    }
                    if ($notes) {
                        //add review notes
                        $fee_amount = 0.00;
                        $fee_desc = 'Review Notes:'. $notes;
                        $mechanic_id = $user->getId();
                        $itemid = $workitem->getId();
                        $is_expense = 1;
                        $fee_category = '';
                        AddFee($itemid, $fee_amount, $fee_category, $fee_desc, $mechanic_id, $is_expense);
                    }
                    $notifyEmpty = false;
                    if ($status_review == 'FUNCTIONAL') {
                        $status_change = '-functional';
                        Notification::workitemNotify(array('type' => 'modified-functional',
                            'workitem' => $workitem,
                            'status_change' => $status_change,
                            'job_changes' => $job_changes,
                            'recipients' => array('runner', 'creator', 'mechanic', 'followers')),
                            array('changes' => $new_update_message));
                      $notifyEmpty = true;
                    }

                    $journal_message = $_SESSION['nickname'] . " updated item #$worklist_id: " . $workitem->getSummary() . ".  $new_update_message";
                }

                $promptForReviewUrl = false;
            }
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
                        if ($status == 'Completed') {
                            $workitem->addFeesToCompletedJob();
                        }

                        if ($status == 'Done') {
                            $displayDialogAfterDone = true;
                        }

                        if ($status != 'Draft') {
                            $new_update_message = "Status set to $status. ";
                            $notifyEmpty = false;
                            $status_change = '-' . ucfirst(strtolower($status));
                            if ($status == 'Functional') {
                                Notification::workitemNotify(array('type' => 'modified-functional',
                                'workitem' => $workitem,
                                'status_change' => $status_change,
                                'job_changes' => $job_changes,
                                'recipients' => array('runner', 'creator', 'mechanic', 'followers')),
                                array('changes' => $new_update_message));
                                $notifyEmpty = true;
                            }
                            $journal_message = $_SESSION['nickname'] . " updated item #$worklist_id: " . $workitem->getSummary() . ".  $new_update_message";
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
            $bid_amount = (int) $bid_amount;
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
                // Journal notification
                $journal_message = "A bid was placed on item #$worklist_id: $summary.";
                //sending email to the runner of worklist item

                $row = $workitem->getRunnerSummary($worklist_id);
                if(!empty($row)) {
                $id = $row['id'];
                    $summary = $row['summary'];
                    $username = $row['username'];
                }

                $sms_message = "Bid $" . number_format($bid_amount, 2) . " from " . getSubNickname($_SESSION['nickname']) . " done in $done_in on #$worklist_id $summary";
                
                $options = array(
                     'type' => 'bid_placed',
                     'workitem' => $workitem,
                     'recipients' => array('runner'),
                     'userstats' => new UserStats($_SESSION['userid'])
                );
                $data = array(
                     'done_in' => $done_in,
                     'bid_expires' => $bid_expires,
                     'bid_amount' => $bid_amount,
                     'notes' => replaceEncodedNewLinesWithBr($notes),
                     'bid_id' => $bid_id,
                );
                
                // notify runner of new bid
                Notification::workitemNotify($options, $data);

                // sending sms message to the runner
                $runner = new User();
                $runner->findUserById($workitem->getRunnerId());
                if (Notification::isNotified($runner->getNotifications(), Notification::MY_BIDS_NOTIFICATIONS)) {
                    Notification::sendShortSMS($runner, 'Bid', $sms_message, WORKITEM_URL . $worklist_id);
                }
                $status=$workitem->loadStatusByBidId($bid_id);
                if ($status == "SuggestedWithBid") {
                    if ($this->changeStatus($workitem, $status, $user)) {
                        $new_update_message = "Status set to $status. ";
                        $notifyEmpty = false;
                        $journal_message .= "$new_update_message";
                    }
                }
                
                $data['new_update_message'] = $new_update_message;
                Notification::workitemNotifyHipchat($options, $data);
                
                if(!$notifyEmpty) {
                    $options = array(
                        'type' => 'suggestedwithbid',
                        'workitem' => $workitem,
                        'recipients' => array('projectRunners')
                    );
                    $data = array('notes' => $notes);
                    Notification::workitemNotify($options, $data);
                }
            } else {
                error_log("Input forgery detected for user $userId: attempting to $action.");
            }

            $redirectToDefaultView = true;
            // echo 'redirect is set to true ' . $redirectToDefaultView;
        }

        // Edit Bid
        if ($action =="edit_bid") {
            if (! $user->isEligible() ) {
                error_log("Input forgery detected for user $userId: attempting to $action (isEligible in job)");
            } else {
                //Escaping $notes with mysql_real_escape_string is generating \n\r instead of <br>
                //a new variable is used to send the unenscaped notes in email alert.
                //so it can parse the new line as <BR>   12-Mar-2011 <webdev>

                $args = array('bid_id', 'bid_amount', 'done_in_edit', 'bid_expires_edit', 'notes');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                }

                $bid_amount = (int) $bid_amount;
                $mechanic_id = (int) $mechanic_id;

                if ($_SESSION['timezone'] == '0000') $_SESSION['timezone'] = '+0000';
                $summary = getWorkItemSummary($worklist_id);
                $bid_id = $workitem->updateBid($bid_id, $bid_amount, $done_in_edit, $bid_expires_edit, $_SESSION['timezone'], $notes);

                // Journal notification
                $journal_message = "Bid updated on item #$worklist_id: $summary.";

                $sms_message = "(Bid updated) $" . number_format($bid_amount, 2) . " from " . $_SESSION['username'] . " done in $done_in_edit on #$worklist_id $summary";
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
                    'recipients' => array('runner')
                );
                $data = array(
                    'done_in' => $done_in_edit,
                    'bid_expires' => $bid_expires_edit,
                    'bid_amount' => $bid_amount,
                    'notes' => replaceEncodedNewLinesWithBr($notes),
                    'bid_id' => $bid_id
                );
                
                // notify runner of new bid
                Notification::workitemNotify($options, $data);
                Notification::workitemNotifyHipchat($options, $data);

                // sending sms message to the runner
                $runner = new User();
                $runner->findUserById($workitem->getRunnerId());
                if(Notification::isNotified($runner->getNotifications(), Notification::MY_BIDS_NOTIFICATIONS)) {
                    Notification::sendShortSMS($runner, 'Updated', $journal_message, WORKITEM_URL . $worklist_id);
                }
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

                    // send sms message to runner
                    $runner = new User();
                    $runner->findUserById($workitem->getRunnerId());
                    $runner->updateBudget(-$fee_amount, $workitem->getBudget_id());

                    if(Notification::isNotified($runner->getNotifications(), Notification::MY_BIDS_NOTIFICATIONS)) {
                        Notification::sendShortSMS($runner, 'Fee', $journal_message, WORKITEM_URL . $worklist_id);
                    }
                }
                $redirectToDefaultView = true;
            }
        }

        if ($action == "add_tip") {
            $args = array('itemid', 'tip_amount', 'tip_desc', 'mechanic_id');
            foreach ($args as $arg) {
                if (isset($_REQUEST[$arg])) {
                    $$arg = mysql_real_escape_string($_REQUEST[$arg]);
                } else {
                    $$arg = '';
                }
            }

            $itemid = (int) $itemid;
            $fee_amount = (float) $tip_amount;
            $mechanic_id = (int) $mechanic_id;

            // is the logged in user the mechanic on the task?
            if ($workitem->getMechanicId() == getSessionUserId()) {
                $journal_message = AddTip($itemid, $tip_amount, $tip_desc, $mechanic_id);

                // notify recipient of new tip
                $recipient = new User();
                $recipient->findUserById($mechanic_id);
                
                $options = array(
                    'type' => 'tip_added',
                    'workitem' => $workitem,
                    'emails' => array($recipient->getUsername())
                );
                $data = array(
                    'tip_adder' => $user->getNickname(),
                    'tip_desc' => $tip_desc,
                    'tip_amount' => $tip_amount
                );
                
                Notification::workitemNotify($options, $data);
                
                $data['nick'] = $_SESSION['nickname'];
                $data['tipped_nickname'] = $recipient->getNickname();
                Notification::workitemNotifyHipchat($options, $data);
            }
            
            $redirectToDefaultView = true;
        }

        // Accept a bid
        if ($action == 'accept_bid') {
            if (!isset($_REQUEST['bid_id']) ||
                !isset($_REQUEST['budget_id'])) {
                $_SESSION['workitem_error'] = "Missing parameter to accept a bid!";
                $redirectToDefaultView = true;
            } else {

                $bid_id = intval($_REQUEST['bid_id']);
                $budget_id = intval($_REQUEST['budget_id']);
                
                $budget = new Budget();
                if (!$budget->loadById($budget_id) ) {
                    $_SESSION['workitem_error'] = "Invalid budget!";
                    $redirectToDefaultView = true;
                }
                // only runners can accept bids        
                if (($is_project_runner || $workitem->getRunnerId() == $_SESSION['userid'] || ($user->getIs_admin() == 1
                     && $is_runner) && !$workitem->hasAcceptedBids() &&
                    $workitem->getStatus() == "Bidding" || $workitem->getStatus() == "SuggestedWithBid")) {
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
                            $journal_message .= $_SESSION['nickname'] .
                                " accepted {$bid_info['bid_amount']} from " .
                                $bid_info['nickname'] . " on item #{$bid_info['worklist_id']}: " .
                                $bid_info['summary'] . ". Status set to Working.";
                            
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

                            //send sms notification to bidder
                            Notification::sendShortSMS($bidder, 'Accepted', $journal_message, WORKITEM_URL . $worklist_id);

                            $redirectToDefaultView = true;

                            // Send email to not accepted bidders
                            $this->sendMailToDiscardedBids($worklist_id);
                        } else {
                            $overBudget = money_format('%i', $bid_amount - $remainingFunds);
                            $_SESSION['workitem_error'] = "Failed to accept bid. Accepting this bid would make you " . $overBudget . " over your budget!";
                            $redirectToDefaultView = true;
                        }
                    }
                    else {
                        $_SESSION['workitem_error'] = "Failed to accept bid, bid has been deleted!";
                        $redirectToDefaultView = true;
                    }
                } else {
                    if ($workitem->getIsRelRunner() || $workitem->getRunnerId() == $_SESSION['userid']) {
                        if ($workitem->hasAcceptedBids()) {
                            $_SESSION['workitem_error'] = "Failed to accept bid on task with an accepted bid!";
                        } else {
                            $_SESSION['workitem_error'] = "Accept Bid Failed, unknown task state!";
                        }
                        $redirectToDefaultView = true;
                    }
                }
            }
        }

        // Accept Multiple  bid
        if ($action=='accept_multiple_bid') {
            if (!isset($_REQUEST['budget_id'])) {
                $_SESSION['workitem_error'] = "Missing budget to accept a bid!";
                $redirectToDefaultView = true;
            } else {
                $bid_id = $_REQUEST['chkMultipleBid'];
                $mechanic_id = $_REQUEST['mechanic'];
                $budget_id = intval($_REQUEST['budget_id']);
                $budget = new Budget();
                if (!$budget->loadById($budget_id) ) {
                    $_SESSION['workitem_error'] = "Invalid budget!";
                    $redirectToDefaultView = true;
                }
                if (count($bid_id) > 0) {
                //only runners can accept bids
                    if (($is_project_runner || $workitem->getRunnerId() == getSessionUserId() ||
                         ($user->getIs_admin() == 1 && $is_runner)
                        ) &&
                        !$workitem->hasAcceptedBids() &&
                        (
                            $workitem->getStatus() == "Bidding" ||
                            $workitem->getStatus() == "SuggestedWithBid"
                        )) {
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
                                    $journal_message .= $_SESSION['nickname'] . " accepted {$bid_info['bid_amount']} from ". 
                                        $bid_info['nickname'] . " " . ($is_mechanic ? ' as MECHANIC ' : '') . 
                                        "on item #".$bid_info['worklist_id'].": " . $bid_info['summary'] . 
                                        ". Status set to Working. ";
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
                            $redirectToDefaultView = true;

                            $runner = new User();
                            $runner->findUserById($workitem->getRunnerId());
                            $runner->updateBudget(-$total, $workitem->getBudget_id());
                        } else {
                            $overBudget = money_format('%i', $total - $remainingFunds);
                            $_SESSION['workitem_error'] = "Failed to accept bids. Accepting this bids would make you " . $overBudget . " over your budget!";
                            $redirectToDefaultView = true;
                        }
                    }
                }
            }
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

        if ($action == false) {
            $redirectToDefaultView = $redirectToWorklistView = $postProcessUrl = false;
        }

        if ($redirectToDefaultView) {
            $postProcessUrl = WORKITEM_URL . $worklist_id . "?order=" . $order_by;
            if ($workitem->getStatus() == 'Done') {
                $displayDialogAfterDone = true;
            }
        }
        if ($redirectToWorklistView) {
            $postProcessUrl = WORKLIST_REDIRECT_URL . $worklist_id;
        }

        // we have a Journal message, send it to Journal - except for DRAFTS
        if(isset($journal_message) && $workitem->getStatus() != 'Draft') {
            sendJournalNotification($journal_message);
            //$postProcessUrl = WORKITEM_URL . $worklist_id . "?msg=" . $journal_message;
        }

        // if a post process URL was set, redirect and die
        if(isset($postProcessUrl) && ! empty($postProcessUrl)) {
            header("Location: " . $postProcessUrl);
            die();
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

                if (!($user->getId() == $bid['bidder_id'] 
                 || $user->isRunnerOfWorkitem($workitem) || ($worklist['status'] == 'SuggestedWithBid' && $workitem->getIsRelRunner())))  {
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

        //total fee
        $total_fee = $workitem->getSumOfFee($worklist_id);

        //accepted bid amount
        $accepted_bid_amount = 0;
        foreach ($fees as $fee){
            if ($fee['desc'] == 'Accepted Bid') {
                $accepted_bid_amount = $fee['amount'];  
            }
        }

        //code review fees
        $project = new Project();
        $project_roles = $project->getRoles($workitem->getProjectId(), "role_title = 'Reviewer'");
        if (count($project_roles) != 0) {
            $crRole = $project_roles[0];
            if ($crRole['percentage'] !== null && $crRole['min_amount'] !== null) {
                $crFee = ($crRole['percentage'] / 100) * $accepted_bid_amount;
                if ((float) $crFee < $crRole['min_amount']) {
                   $crFee = $crRole['min_amount']; 
                }
            }
        } else {
            $crFee = 0;
        }


        $user_id = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";
        $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
        $is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;
        $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
        $creator_id = isset($worklist['creator_id']) ? $worklist['creator_id'] : 0;
        $mechanic_id = isset($worklist['mechanic_id']) ? $worklist['mechanic_id'] : 0;

        $has_budget = 0;
        if (! empty($user_id)) {
            $user = new User();
            $user->findUserById($user_id);
            if ($user->getBudget() > 0) {
                $has_budget = 1;
            }
        }

        $workitem = WorkItem::getById($worklist['id']);
        if ($worklist['project_id']) {
            $workitem_project = new Project($worklist['project_id']);
        }
        $projects = Project::getProjects();

        $allowEdit = false;
        $classEditable = "";
        if (($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) ||
            ($creator_id == $user_id && $worklist['status'] == 'Suggested' && is_null($worklist['runner_id'])) ||
            ($creator_id == $user_id && $worklist['status'] == 'SuggestedWithBid' && is_null($worklist['runner_id']))) {
            $allowEdit = true;
            if ($action !="edit") {
                $classEditable = " editable";
            }
        }
        $this->write('classEditable', $classEditable);
        $this->write('allowEdit', $allowEdit);

        $hideFees = false;
        if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'Suggested' || $worklist['status'] == 'SuggestedWithBid') {
            $hideFees = true;
        }
        $this->write('hideFees', $hideFees);


        $this->write('worklist', $worklist);
        $this->write('workitem', $workitem);
        $this->write('user', $user);
        $this->write('workitem_project', $workitem_project);
        $this->write('bids', $bids);

        $this->write('userHasRights', $this->hasRights($user_id, $workitem));

        $this->write('mechanic', $workitem->getUserDetails($worklist['mechanic_id']));

        global $displayDialogAfterDone;
        if ($displayDialogAfterDone == true && $worklist['mechanic_id'] > 0) {
            $_SESSION['displayDialogAfterDone'] = false;
            $this->write('displayDialogAfterDone', true);
        } else {
            $this->write('displayDialogAfterDone', false);
        }

        $this->write('order_by', $order_by);
        $this->write('action', $action);
        $this->write('action_error', isset($action_error) ? $action_error : '');

        $this->write('comments', Comment::findCommentsForWorkitem($worklist['id']));
        $this->write('taskPosts', $this->getTaskPosts($worklist['id']));
        $this->write('message', isset($message) ? $message : '');
        
        parent::run();
    }

    function hasRights($userId, $workitem) {
        $project = new Project();
        $project->loadById($workitem->getProjectId());
        $users_favorite = new Users_Favorite();
        
        if($project->getCrUsersSpecified()) { // if only specified users are allowed
            if($project->isProjectCodeReviewer($userId)){
                return true;
            }
            return false;
        } else {
            if ($project->getCrAnyone()) {
                return true;
            } else if ($project->getCrAdmin()) {
                $admin_fav = $users_favorite->getMyFavoriteForUser($project->getOwnerId(), $userId);
                if ($admin_fav['favorite']) {
                    return true;
                }
            } else if ($project->getCrFav() && $users_favorite->getUserFavoriteCount($userId) >= 3) {
                return true;
            } else if ($project->getCrRunner()) {
                $runner_fav = $users_favorite->getMyFavoriteForUser($workitem->getRunnerId(),$userId);
                if ($runner_fav['favorite']) {
                    return true;
                }
            } else if($project->isProjectCodeReviewer($userId)){
                return true;
            }
        }
        return false;
    }

    function sendMailToDiscardedBids($worklist_id)    {
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

    function changeStatus($workitem, $newStatus, $user) {

        $allowable = array("Draft", "Suggested", "SuggestedWithBid", "Review", "Functional", "Pass", "Completed");

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
        if ($newStatus == "Review") {
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
                        $comment = "Code review available here:\n$result";
                        $rt = $this->addComment($workitem->getId(), $user->getId(), $comment);
                    } catch (Exception $ex) {
                        error_log("Could not paste diff: \n$ex");
                    }
                }
            } elseif ($repoType == 'git') {
                $GitHubUser = new GitHubUser($workitem->getMechanicId());
                $pullResults = $GitHubUser->createPullRequest($workitem->getId(), $thisProject);

                if (!$pullResults['error'] && !isset($pullResults['data']['errors'])) {
                    $codeReviewURL = $pullResults['data']['html_url'] . '/files';
                    $comment = "Code review available here:\n" . $codeReviewURL;
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
        
        if ($newStatus == 'Functional' && $repoType == 'git') {
            $runner = $workitem->getRunnerId();
            $GitHubUser = new GitHubUser($runner);
            $runnerEmail = $GitHubUser->getUsername();
            $GitHubBidder = new GitHubUser($workitem->getMechanicId());
            $githubDetails = $GitHubBidder->getGitHubUserDetails($thisProject);
            $gitHubUsername = $githubDetails['data']['login'];
            $GitHubProject = new GitHubProject();
            $repoDetails = $GitHubProject->extractOwnerAndNameFromRepoURL($thisProject->getRepository());
            $usersFork = 'https://github.com/' . $gitHubUsername . "/" . $repoDetails['name'] . ".git";
            $emailTemplate = 'functional-howto';
            $data = array(
                'branch_name' => $workitem->getId(),
                'runner' => $GitHubUser->getNickname(),
                'users_fork' => $usersFork,
                'master_repo' => str_replace('https://', 'git://', $thisProject->getRepository())
            );
            $senderEmail = 'Worklist <contact@worklist.net>';
            sendTemplateEmail($runnerEmail, $emailTemplate, $data, $senderEmail);
        } else if ($newStatus =='Functional' && ! ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 ))) {
            $status = $workitem->validateFunctionalReview();

            if ($status === true || $status == 0) {
                return true;
            }
            
            return $status;
        } 

        if ($newStatus == 'Working') {
            $thisProject->setActive(1);
            $thisProject->save();
        }

        // notifications for subscribed users
        Notification::massStatusNotify($workitem);
        
        if ($newStatus != 'SuggestedWithBid') {
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
        
        return true;
    }

    function addComment($worklist_id, $user_id, $comment_text, $parent_comment_id) {
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

    function  getTaskPosts($item_id) {
        return '';
        
        //global $chat;
        //$query = $item_id;
        //$response = new AjaxResponse($chat);
        //try {
        //    $data = $response->latestFromTask($item_id);
        //} catch(Exception $e) {
        //    $data['error'] = $e->getMessage();
        //}
        //return $data['html'];
    }
}



/*

////////// WORKITEM.INC CODE START HERE ///////////////////////////

    $user_id = (isset($_SESSION['userid'])) ? $_SESSION['userid'] : "";
    $is_runner = isset($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;
    $is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;
    $is_payer = isset($_SESSION['is_payer']) ? $_SESSION['is_payer'] : 0;
    $creator_id = isset($worklist['creator_id']) ? $worklist['creator_id'] : 0;
    $mechanic_id = isset($worklist['mechanic_id']) ? $worklist['mechanic_id'] : 0;

    $has_budget = 0;
    if (! empty($user_id)) {
        $user = new User();
        $user->findUserById($user_id);
        if ($user->getBudget() > 0) {
            $has_budget = 1;
        }
    }

    $workitem = WorkItem::getById($worklist['id']);
    if ($worklist['project_id']) {
        $workitem_project = new Project($worklist['project_id']);
    }
    $projects = Project::getProjects();

    $allowEdit = false;
    $classEditable = "";
    if (($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) ||
        ($creator_id == $user_id && $worklist['status'] == 'Suggested' && is_null($worklist['runner_id'])) ||
        ($creator_id == $user_id && $worklist['status'] == 'SuggestedWithBid' && is_null($worklist['runner_id']))) {
        $allowEdit = true;
        if ($action !="edit") {
            $classEditable = " editable";
        }
    }
    $hideFees = false;
    if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'Suggested' || $worklist['status'] == 'SuggestedWithBid') {
        $hideFees = true;
    }

    if ($worklist['status'] == 'Draft' && $creator_id != $user_id) { ?>
       <script type="text/javascript">
       alert("Draft jobs are only viewable by their creator.");
       window.location = "jobs";
       </script>
   <?php
    return;
    } 
    ?>

<script type="text/javascript">
    var origStatus = '<?php echo $worklist['status']; ?>';
    var worklist_id = '<?php echo($worklist_id); ?>';
    var status_error = '<?php echo $status_error; ?>';
    var runSandboxCheck = {{runSandboxCheck}};
    var mechanic_id = {{mechanic.id}};
    var mechanic_nickname = '{{mechanic.nickname}}';
    var displayDialogAfterDone = {{displayDialogAfterDone}};



$(document).ready(function() {
    if (status_error)
        openNotifyOverlay(status_error, false);
    }
    applyPopupBehavior();
    if (runSandboxCheck) {
        var html = "<span>Checking sandbox...";
        openNotifyOverlay(html, true);

        $.ajax({
            type: 'post',
            url: 'jsonserver.php',
            data: {
                workitem: workitem_id,
                userid: user_id,
                action: 'validateFunctionalReview'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success != true) {
                    $('<div id="functionalCheck"><div class="content"></div></div>')
                        .appendTo('body')
                        .hide();
                    $('#functionalCheck .content').html(data.data);
                    $('#functionalCheck').dialog({
                        modal: true,
                        resizable: false,
                        dialogClass: 'white-theme',
                        width: 'auto',
                        height: 'auto',
                        buttons: {
                            'Ok': function() {
                                $(this).dialog('close');
                            }
                        },
                        close: function() {
                            $('#functionalCheck').remove();
                        }
                    });
                    $('#functionalCheck').dialog('open');
                }
            }
        });    
    }

    $('#invite-people').dialog({
        autoOpen: false,
        dialogClass: 'white-theme',
        resizable: false,
        modal: true,
        show: 'fade',
        hide: 'fade',
        width: 'auto',
        height: 'auto',
        open: function() {  
            var autoArgs = autocompleteMultiple('getuserslist');
            $("#invite").bind("keydown", autoArgs.bind);
            $("#invite").autocomplete(autoArgs, null);               
        }
            
    });
    $("#invite-link").click(function() {
        $('#invite-people').dialog('open');
    });

    if (displayDialogAfterDone && mechanic_id) {
        WReview.displayInPopup({
            user_id: mechanic_id, 
            nickname: mechanic_nickname, 
            withTrust: true, 
            notify_now: 0
        });
    }
    <?php

    global $displayDialogAfterDone;

    if ($displayDialogAfterDone == true && $worklist['mechanic_id'] > 0) {
        $_SESSION['displayDialogAfterDone'] = false;
        $row = $workitem->getUserDetails($worklist['mechanic_id']);
        if (! empty($row)) {
            $nickname = $row['nickname'];
            echo "WReview.displayInPopup({user_id: " . $worklist['mechanic_id'] . ", nickname:'" . $row['nickname'] . "', withTrust: true, notify_now: 0});";
        }
    }
    ?>

    <?php if ($user_id > 0) { ?>
    $.get('api.php?action=getSkills', function(data) {
        var skillsData = eval(data);
        
        var autoArgsSkills = autocompleteMultiple('getskills', skillsData);
        $("#skills-edit").bind("keydown", autoArgsSkills.bind);
        $("#skills-edit").autocomplete(autoArgsSkills);               
    });
    <?php } ?>
    makeWorkitemTooltip(".worklist-item");

    $('#workitem-form').submit(function() {
        return saveWorkitem();
    });

    var userid_info_to_display = <?php echo (isset($_REQUEST['userinfotoshow']) && isset($_SESSION['userid'])) ? $_REQUEST['userinfotoshow'] : 0;?>; //holds the userid info to display
    //if the page was loaded with request to display userinfo automatically then do it.
    if (userid_info_to_display){
        window.open('userinfo.php?id=' + userid_info_to_display, '_blank');
    }
});
<!-- End User Info popup code -->
</script>






</head>
<body>
<?php require_once('header.php'); ?>

    <div class="first-line"></div>
    <div id="info-top">
        <div class="info-summ-big">
            <div class="info-ID">
                <span id="following"><span id="followingLogin"></span></span>
            </div>
            <span id="invite-link" title="Invite people" href="javascript">Invite someone</span>
            <?php if ($allowEdit && $action != "edit"): ?>

                <?php if ($worklist['status'] == "Done"): ?>
                    <span class="switchmode-done" title="Jobs set to Done status may not be edited">Edit</span>
                <?php else: ?>
                    <span class="switchmode" id="switchmode_edit">
                        <a title="Switch to Edit Mode" href="
                        <?php $format = '%s?job_id=%d&action=edit&order=%s';
                        echo sprintf($format, $_SERVER['SCRIPT_NAME'], $worklist['id'], $order_by); ?>
                        ">Edit</a>
                    </span>
                <?php endif;?>

            <?php endif;?>
            <?php if ($action == "edit"): ?>
                <span class="switchmode" id="switchmode_edit"><a title="Switch to View Mode"
                    href="<?php echo $_SERVER['SCRIPT_NAME']; ?>?job_id=<?php echo $worklist['id'];?>&action=view&order=
                    <?php echo $order_by; ?>">View</a>
                </span>
            <?php endif;?>
        </div>
    </div>
    <div id="info-top" class="info-summ-big-text">
        <span class="cabin">
        #<?php echo $worklist['id'];?>
        <?php
            //Show original item id if displayed item is a bug
            if($workitem->getBugJobId() > 0) {
                $originalItemLink = SERVER_URL."job/".
                                    $workitem->getBugJobId()."?action=view&order=" . $order_by;
                echo "  [ bug of <a href='".$originalItemLink."' class='worklist-item' id='workitem-".$workitem->getBugJobId()."' >".$workitem->getBugJobId()."</a> ]";
            }
            ?>
        </span>
        <span class="divider">&nbsp;</span>
        <span class="title <?php echo $classEditable;?>"><?php echo $worklist['summary'];?></span>
    </div>
<?php if ($action =="edit"):  ?>
        <form id="workitem-form" method="post" action="">
<?php endif; ?>
    <div id="info-top" class="top-bar">
    <div class="second-line"></div>
        <?php if ($action != "edit"): ?>
            <div id="quick-status">
                <span class="info-label"><a href="help.php#ff5" target="_blank">Job status:</a></span>&nbsp;
            <?php
            if ((!$workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner) || ($mechanic_id == $user_id) &&
                   $worklist['status'] != 'Done') || $workitem->getIsRelRunner()
                   || ($worklist['creator_id']== $user_id && $worklist['status'] != 'Done')):
            ?>
                <form id="searchForm" method="post" action="">
                    <input type="hidden" id="status-switch" name="status-switch" value="1" />
                    <select id="statusCombo" name="quick-status" class="project-dropdown hidden">
                        <option value="<?php echo $worklist['status'];?>" selected="selected"><?php echo $worklist['status'];?></option>  
                        <?php
                        if (!($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) && ($mechanic_id == $user_id) && 
                              $worklist['status'] != 'Done') { //mechanics
                            foreach ($statusListMechanic as $status) {
                                if ($status != $worklist['status']) {?>
                                    <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                            <?php
                                }
                            }
                        }
                        else if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) { //runners and admins
                            foreach ($statusListRunner as $status) {
                                if ( $status != $worklist['status'] ) {
                            ?>
                                <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                            <?php
                                }
                            }
                        } else if ($worklist['creator_id']== $user_id && $worklist['status'] != 'Working' 
                            && $worklist['status'] != 'Functional' && $worklist['status'] != 'Review' 
                            && $worklist['status'] != 'Completed' && $worklist['status'] != 'Done' ) { //creator
                            foreach ($statusListCreator as $status) {
                                if (!($status == 'Suggested' && $worklist['status'] == 'SuggestedWithBid') && $status != $worklist['status']) {
                            ?>
                                <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                            <?php
                                }
                          }
                        }
                        ?>
                    </select>
                    <script type="text/javascript">
                    $(document).ready(function() {
                    $('#statusCombo').comboBox();
                    });
                    </script>
                </form>
            <?php else : ?>

                    <form id="searchForm" method="post" action="" _lpchecked="1">
                        <div class="ui-state-default ui-corner-all ui-combobox statusCombo" style="display: inline-block; " id="container-statusCombo"><input type="text" class="ui-state-default ui-combobox-textbox" style="width: 198px; " readonly="readonly" value="<?php echo $worklist['status'];?>"></div>
                        </script>
                    </form>

            <?php endif; ?>
          </div>
        <?php endif;?>

            <?php
                if (($user->isRunnerOfWorkitem($workitem) ||
                    (array_key_exists('userid',$_SESSION) &&
                        ($_SESSION['userid'] == $worklist['budget_giver_id'] ||
                         strpos(BUDGET_AUTHORIZED_USERS, "," . $_SESSION['userid'] . ",") !== false) ) ) && !empty($worklist['budget_id'])) {
                    if ($action =="edit"): ?>
                    <?php else: ?>
                        <div class="job-budget">
                            <div class="project-label">Budget:</div>
                            <?php echo $worklist['budget_id'] . " - " . htmlspecialchars($worklist['budget_reason']);?>
                        </div>
                    <?php endif;?>
            <?php } ?>

<?php
if ($action =="edit"):  ?>
            <input type="hidden" name="save_workitem" value="save_workitem" />
            <input type="hidden" name="action" value="save_workitem" />
            <div class="project <?php if ($workitem_project->getRepo_type() == 'git') { echo ' github';} ?>">
                <span class="info-label" id="edit-project-label">Project:&nbsp;</span>
                <?php if (($is_project_runner || $creator_id == $user_id || ($is_admin && $is_runner)) && ($worklist['status'] != 'Done')):
                          $filter = new Agency_Worklist_Filter();
                          $filter->setProjectId($worklist['project_id']);
                          echo $filter->getProjectSelectbox('Select Project', 0, 'project_id', 'project_id');
                        ?>
                            <script type="text/javascript">
                                $(document).ready(function() {
                                    $('#project_id').comboBox();
                                });
                            </script>
                        <?php
                      elseif( !empty($worklist['project_id'])) :?>
                     <a href="<?php echo Project::getProjectUrl($worklist['project_id']);?>" class="edit-project">
                        <?php echo htmlspecialchars($worklist['project_name']);?>
                     </a>
                <?php else :?>
                 <span>Not assigned</span>
                <?php endif;?>

<?php else: ?>

            <div class="project <?php if ($workitem_project->getRepo_type() == 'git') { echo ' github';} ?>">
                <div class="project-label">Project:</div>
                <?php if ( !empty($worklist['project_id']) ):?>
                    <a href="<?php echo Project::getProjectUrl($worklist['project_id']);?>" target="_blank">
                        <?php echo htmlspecialchars($worklist['project_name']);?>
                    </a>
                    <?php if ( !empty($worklist['p_website']) ) {
                        $project = new Project($worklist['project_id']);
                        echo "&nbsp;<a class='project_website' href='" . $project->getWebsiteUrl() . "' target='_blank'>website</a>";
                    }?>
                <?php else :?>
                    <span>Not assigned</span>
                <?php endif;?>
 <?php endif;?>

            <?php if ($workitem_project->getRepo_type() == 'git') { ?>
            <div class="project-github">
                <h1>Requires GitHub</h1>
            </div>
            <?php } ?>
        </div>

                <div style="clear:both; float:none">&nbsp;</div>
            </div>
    <?php if ($erroneous) echo "<div style='color: red; text-align: center;'>{$the_errors}</div>"; ?>
        <div>
<?php if (isset($action_error) && !empty($action_error)) : ?>
        <div id="action-error" class="LV_invalid">Error performing requested action: <?php echo $action_error; ?></div>
<?php endif; ?>
        <div style="clear:both; float:none;"> </div>
        </div>
        <div id="page-content">
            <div id="left-panel">

                <div class="people-info">
                    <div id="creatorBox"><span id="pingCreator" class="creatorName"
                    title="<?php echo isset($_SESSION['userid']) ? "Ping Creator" : "Log in to Ping Creator"; ?>">
                    <a href="#">Job creator:</a></span>
                    <a href="userinfo.php?id=<?php echo $worklist['creator_id']; ?>" target="_blank">
                        <?php echo $worklist['creator_nickname'];?>
                    </a>
                </div>

                        <div id="runnerBox">
                        <?php if ($action =="edit"):  ?>
                            <span class="runnerName">Runner:</span>
                            <?php if ($worklist['runner_nickname'] != 'Not funded'): ?>
                                <a href="userinfo.php?id=<?php echo $worklist['runner_id']; ?>" target="_blank" id="ping-r-btn"
                                    title="<?php echo (isset($_SESSION['userid']) ? "Ping Runner" : "Log in to Ping Runner"); ?>"
                                    data-user-id="<?php echo $worklist['runner_id']; ?>">
                                    <?php echo substr($worklist['runner_nickname'], 0, 9) . (strlen($worklist['runner_nickname']) > 9 ? '...' : ''); ?>
                                </a>
                            <?php else: ?>
                                    <?php echo "&nbsp" . $worklist['runner_nickname']; ?>
                            <?php endif; ?>
                            <span class="changeRunner">
                                <?php $runnerslist = Project::getAllowedRunnerlist($worklist['project_id']);
                                echo '<select name="runner">';
                                foreach ($runnerslist as $r) {
                                    echo '<option value="' . $r->getId() . '"' . (($worklist['runner_id'] == $r->getId()) ? ' selected="selected"' : '') . '>' . $r->getNickname() . '</option>';
                                }
                                echo '</select>';?>
                                <div class="buttonContainer"><input type="button" class="smbutton" name="changerunner" value="Change Runner" /></div>
                                <div class="buttonContainer"><input type="button" class="smbutton" name="cancel" value="Cancel" /></div>
                            </span>
                        <?php else: ?>
                            <?php if ($worklist['runner_nickname'] != 'Not funded' && $worklist['runner_nickname'] != ''): ?>
                                <span id="pingRunner" class="runnerName" title="<?php echo isset($_SESSION['userid']) ? "Ping Runner" : "Log in to Ping Runner"; ?>"> <a href="#">Runner:</a></span>
                                <a href="userinfo.php?id=<?php echo $worklist['runner_id']; ?>" target="_blank"><?php echo substr($worklist['runner_nickname'], 0, 9) . (strlen($worklist['runner_nickname']) > 9 ? '...' : '') ;?></a>
                            <?php else: ?>
                                <span class="runnerName" title="Ping Runner">Runner:</span> Not funded
                            <?php endif; ?>
                        <?php endif;?>
                        </div>

                        <?php
                        $mech = '';
                        if( count($fees) >0 ) {
                            foreach( $fees as $fe )
                                if( $fe['desc'] == 'Accepted Bid' ) $mech = $fe['nickname'];
                        }
                        if($mech=='') {
                            $mech = '<span class="mechanicName">Mechanic:</span>Not assigned';
                        } else{
                            $tooltip = isset($_SESSION['userid']) ? "Ping Mechanic" : "Log in to Ping Mechanic";
                            $mech='<span id ="pingMechanic" class="mechanicName" title="' . $tooltip . '" ><a href="#">Mechanic:</a></span><a id="ping-btn" href="userinfo.php?id=' . $worklist['mechanic_id'] . '" target="_blank">' . $mech . '</a>';
                        }
                        echo '<div id="mechanicBox"> ' . $mech . '</div>';
                    ?>
                </div>
                <div id="for_view">
<?php
if ($action =="edit"):  ?>
<!-- *** Action is Edit *** -->
                    <ul style="margin:0; padding:0; border:none;">
                <!-- Edit: Summary info -->
                        <li><span class="info-label">Summary</span><span class="info-data">
                            <?php // creator can edit it's own task if freshly added 23-MAR-2011 <godka>
                            if ((($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) &&
                                  $worklist['status']!='Done') ||($creator_id==$user_id && ($worklist['status']=='Suggested' ||
                                  $worklist['status']=='SuggestedWithBid') && is_null($worklist['runner_id']))) { ?>
                            <input type="text" size="30" class="text-field" id="summary" name="summary" value="<?php echo $worklist['summary'];?>"/>
                            <?php } else { ?>
                            <span class="info-summ"><?php echo $worklist['summary'];?>
                            <?php } ?></span>
                        </li>
                <!-- Edit: Status info -->
                        <li>
                            <span class="info-label"><a href="help.php#ff5" target="_blank">Status</a></span>
                            <span class="info-data">
                            <?php if ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner) ||
                                     ($user_id == $worklist['runner_id'])){  ?><span class="info-data">
                            <select id="status" name="status" class="hidden">
                                <?php foreach ($statusListRunner as $status) { ?>
                                <option value="<?php echo $status; ?>"<?php echo $status == $worklist['status']?' selected="selected"' : ''; ?> ><?php echo $status; ?></option>
                                <?php }?>
                            </select>
                            <?php } else if ($creator_id == $user_id && $mechanic_id == $user_id){ ?><span class="info-data">
                            <select id="status" name="status">
                                <?php foreach ($statusListCreator as $status) { ?>
                                <option value="<?php echo $status; ?>" <?php echo $status == $worklist['status']?' selected="selected"' : ''; ?> ><?php echo $status; ?></option>
                                <?php } ?>
                            </select>
                            <?php } else if ($creator_id == $user_id) { ?>
                            <span class="info-data">
                            <select id="status" name="status">
                                <?php foreach ($statusListCreator as $status) { ?>
                                <option value="<?php echo $status; ?>" <?php echo $status == $worklist['status']?' selected="selected"' : ''; ?> ><?php echo $status; ?></option>
                                <?php } ?>
                            </select>
                            <?php } else { ?><?php echo $worklist['status'];?>
                            <input type="hidden" id="status" name="status" value="<?php $worklist['status']; ?>" />
                            <?php } ?></span>
                        </li>
                <!-- Edit: Notes textarea -->
                        <li>
                            <span class="sections">Notes</span>
                            <?php if (($is_project_runner || ($user->getIs_admin() == 1 && $is_runner) || $creator_id == $user_id) && ($worklist['status'] != 'Done')): ?>
                                <span id="info-notes">
                                    <textarea name="notes" id="edit-notes" class="expandable" <?php if(count($activeBids) > 0) {echo 'disabled="disabled"';} ?> ><?php echo $worklist['notes'];?></textarea>
                                </span>
                            <?php else:?>
                                <span id="info-notes"><?php echo replaceEncodedNewLinesWithBr($worklist['notes']);?></span>
                            <?php endif;?>
                        </li>
                        <?php
                            if ($user->isRunnerOfWorkitem($workitem) ||
                                $_SESSION['userid'] == $worklist['budget_giver_id'] ||
                                strpos(BUDGET_AUTHORIZED_USERS, "," . $_SESSION['userid'] . ",") !== false) {
                        ?>
                        <li>
                        <div class="budgetArea">
                            <div class="budget-label">Budget</div>
                            <?php
                                if ($user->isRunnerOfWorkitem($workitem)) {
                            ?>
                                    <span id="budget-source-combo-area">
                                        <select id="budget-source-combo" name="budget-source-combo" class="project-dropdown hidden">
                                            <?php
                                            if ( empty($worklist['budget_id']) || $worklist['budget_id'] == 0) {
                                            ?>
                                                <option value="0" selected="selected">Select a budget</option>
                                            <?php
                                            }
                                            echo $user->getBudgetCombo($worklist['budget_id']); ?>
                                        </select>
                                    </span>

                            <?php
                                } else {
                                    if (!empty($worklist['budget_id'])) {
                                        echo $worklist['budget_id'] . " - " . htmlspecialchars($worklist['budget_reason']);
                                    } else {
                                        echo "<span>Not assigned</span>";
                                    }
                                }
                            ?>
                            <div style="clear: both;"></div>
                        </div>
                        </li>
                        <?php } ?>
                    <!-- Edit: Sandbox textarea -->
                    <?php if ($mechanic_id > 0):?>
                    <li>
                        <div class="sandbox-container">
                            <span class="functional-label" >Sandbox:</span><br />
                            <?php if(($workitem->getIsRelRunner() || $creator_id == $user_id || ($user->getIs_admin() == 1 && $is_runner))
                                  && ($worklist['status'] != 'Done')): ?>
                            <input type="text" size="30" class="text-field" name="sandbox" id="sandbox" value="<?php echo htmlspecialchars($worklist['sandbox']);?>" />
                             <?php elseif ( !empty($worklist['sandbox']) ):?>
                                <a href="<?php echo $worklist['sandbox'];?>" target="_blank">
                                    <?php echo htmlspecialchars($worklist['sandbox']);?>
                                </a>
                            <?php else :?>
                                <span>Not assigned</span>
                            <?php endif;?>
                        </div>
                    </li>
                    <?php endif;?>

                    </ul>
                    <?php if($allowEdit && $action=="edit"): ?>
                        <div class="buttonContainer">
                            <input type="submit" class="smbutton" value="Save" name="save_workitem" id="save_workitem" />
                        </div>
                    <?php endif;?>
<!-- *** End of Action is Edit *** -->
<?php else: ?>
<!-- *** Action is View *** -->
                <!-- View: Key People info -->
                    <!-- View: Notes textarea -->
                        <span class="sections">Notes</span>
                        <p class="notestext"><?php echo replaceEncodedNewLinesWithBr(linkify($worklist['notes'])); ?></p>
                    <!-- View: Sandbox textarea -->
                    <?php if ($mechanic_id > 0):?>
                        <div class="sandbox-container">
                            <?php if((strcasecmp($worklist['status'], 'Working') == 0 ||
                                strcasecmp($worklist['status'], 'Review') == 0 ||
                                strcasecmp($worklist['status'], 'Functional') == 0) && ($workitem->getIsRelRunner() ||
                                ($user->getIs_admin() == 1 && $is_runner) ||($mechanic_id == $user_id))) {?>
                                <span class="iToolTip changeSBurl functional-label" id="edit_review_url">Sandbox:</span><br />
                            <?php } else {?>
                                <span class="functional-label">Sandbox:</span><br />
                            <?php }?>

                            <?php if ( !empty($worklist['sandbox']) ):?>
                                <div class="sandboxlink">
                                    <div class="relative">
                                        <a href="<?php echo $worklist['sandbox'];?>" target="_blank">
                                          <?php
                                          if (strlen(htmlspecialchars($worklist['sandbox'])) > 64) {
                                              echo substr(htmlspecialchars($worklist['sandbox']), 0, 64) . "...";
                                          } else {
                                              echo htmlspecialchars($worklist['sandbox']);
                                          }
                                      ?>
                                        </a>
                                        <span class="fadingblue">&nbsp;</span>
                                    </div>
                                </div>
                                <?php if (($worklist['status'] == 'Functional' || $worklist['status'] == 'Review')
                                        && $worklist['sandbox'] != 'N/A' || ($worklist['status'] == 'Working' &&
                                        ($user->isRunnerOfWorkitem($workitem) || $is_project_founder || $user->getId() == $mechanic_id))) { ?>
                                <div class="buttonContainer view-sandbox">
                                     <input type="submit" class="smbutton" id="view-sandbox" value="View diff" />
                                </div>
                                <?php } ?>
                            <?php else :?>
                                <span>Not assigned</span>
                            <?php endif;?>
                        </div>
                    <?php endif;?>
<!-- *** End of Action IF *** -->
<?php endif;?>
            <div class="commentZone" id="commentZone">
                <div class="info-comments">Comments</div>

                <?php
                if (!empty($user_id) && ($worklist['status'] != 'Done')) : ?>
                <a class="info-comments-scroll-entry" onclick="scrollToLastComment()">See most recent</a>
                <?php endif ?>

                <?php if (!empty($user_id) && ($worklist['status'] != 'Done') && $order_by == "DESC") : ?>
                <div class="commentform"> <a name="commentform"></a> <span class="info-label">Write a comment</span>
                    <form action="" method="post">
                        <input type="hidden" name="worklist_id" value="<?php echo $worklist['id']; ?>" />
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
                        <input type="hidden" name="order_by" value="DESC" />
                        <input type="hidden" name="comment_id" value="" />
                        <textarea name="comment" class="expand100-300" cols="20" rows="10" size="33"></textarea>
                        <br />
                        <input type="submit" name="newcomment" value="Post Comment" class="disableable" />
                    </form>
                </div>
                <?php endif; ?>

                <ul>
                <?php
                    $comments = Comment::findCommentsForWorkitem($worklist['id']);
                    if (empty($comments)) :
                        ?><div id="no_comments">No comments!</div><?php
                    else :
                        $it=0;
                        $comments_list = array();

                        if ($order_by != "DESC") :
                            $comments_list = $comments;
                        else :
                            $comments_list = array_reverse($comments, true);
                        endif;

                        foreach ($comments_list as $comment) :
                            $comment[] = $comments[$it];
                            ?><li id="comment-<?php echo $comment['id']; ?>" class="depth-<?php echo $comment['depth']; ?> <?php ($it % 2) ? print 'imOdd' : print 'imEven'; $it++; ?> <?php if ($order_by == "DESC" && $comment['depth'] > 0) echo 'desc'; ?>">
                                <div class="comment">
                                    <a href="userinfo.php?id=<?php echo $comment['comment']->getUser()->getId(); ?>" target="_blank">
                                        <image class="picture profile-link" src="<?php echo $comment['comment']->getUser()->getAvatar(); ?>" title="Profile Picture - <?php echo($comment['comment']->getUser()->getNickname()); ?>" />
                                    </a>
                                    <div class="comment-container">
                                        <div class="comment-info">
                                            <a class="author profile-link" href="userinfo.php?id=<?php echo $comment['comment']->getUser()->getId(); ?>" target="_blank">
                                                <?php echo $comment['comment']->getUser()->getNickname(); ?>
                                            </a>
                                            <span class="date"><?php echo relativeTime(strtotime($comment['comment']->getDate())-time());?></span>
                                        </div>
                                        <div class="comment-text">
                                            <?php echo replaceEncodedNewLinesWithBr(linkify($comment['comment']->getComment())); ?>
                                            <?php if (!empty($user_id) && $comment['depth'] < 6 && ($worklist['status'] != 'Done')) : ?>
                                        </div>
                                        <div class="reply-lnk">
                                            <a href="#commentform" onClick="reply(<?php echo $comment['id']; ?>);return false;">Reply</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php
                        endforeach;
                    endif;
                ?>
                </ul>
                <?php if (!empty($user_id) && ($worklist['status'] != 'Done') && $order_by == "ASC") : ?>
                <div class="commentform"> <a name="commentform"></a>
                    <form action="" method="post">
                        <input type="hidden" name="worklist_id" value="<?php echo $worklist['id']; ?>" />
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
                        <input type="hidden" name="order_by" value="ASC" />
                        <input type="hidden" name="comment_id" value="" />
                        <textarea name="comment" class="expand100-300" cols="20" rows="4" size="33"></textarea>
                        <br />
                        <div class="button-container">
                            <div class="buttonContainer"><input type="submit" class="smbutton" name="newcomment" value="Comment" class="disableable" /></div>
                            <div class="buttonContainer hidden"><input type="submit" class="smbutton" name="cancel" value="Cancel" class="disableable" /></div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <div id="scrollerPointer"></div>
            <!-- End of div commentZone -->
            </div>
    <!-- End of div for_view -->
            </div>
<!-- End of div left-panel -->
        </div>
        <div id="right-panel">

<?php
if ($action =="edit"):  ?>

            <div class="skills">
                <span class="skills-label">Skills this job requires:</span><br/>
                <?php if (count($workitem->getSkills()) > 0) : ?>
                <input type="text" size="60" class="text-field" name="skills" id="skills-edit" value="<?php echo implode(', ', $workitem->getSkills()) . ', '; ?>" /></span>
                <?php else: ?>
                <input type="text" size="60" class="text-field" name="skills" id="skills-edit" value="" /></span>
                <?php endif; ?>
                <div class="floatLeft">
                    <label id="bugLabel"><input type="checkbox" name="is_bug" id="is_bug" <?php if ($workitem->getBugJobId()>0 || $workitem->getIs_bug() == 1) echo 'checked="checked"';?>/> Bug</label>
                    (If known, linked to Job #)
                    <input type="text" id="bug_job_id" name="bug_job_id" class="text-field bug_job_id" size="48" value="<?php if($workitem->getBugJobId()>0) echo $workitem->getBugJobId();?>" />
                </div>
                <div class="bugJobSummary" id="bugJobSummary" title="<?php if($workitem->getBugJobId()>0) echo $workitem->getBugJobId(); else echo "0";?>">
                </div>
                <div class="clear"></div>
                <script type="text/javascript">
                    $(document).ready(function() {
                        if($("#is_bug").is ( ":checked" )) {
                            $("#bug_job_id").keyup();
                        }
                    });
                </script>
            </div>

<?php else: ?>

                <div class="skills">
                    <div class="skills-label">Skills this job requires:</div>
                    <?php if (count($workitem->getSkills()) > 0) : ?>
                        <?php echo implode(', ', $workitem->getSkills()); ?>
                    <?php else: ?>
                        Thou shalt find out in the missive ...
                    <?php endif; ?>
                </div>

<?php endif;?>
                    <?php if ($user_id > 0 && $user->isEligible() && $mechanic_id != $user_id) : ?>
                        <?php if ($worklist['status'] == 'Review' && (! $workitem->getCRCompleted())
                            && ((! $workitem->getCRStarted()) || $user_id == $workitem->getCReviewerId() || $workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)))
                        { ?>
                            <div class="code-review">
                            <span>Time for a code review!</span>
                            The code written for this job is ready to be reviewed.<br />
                            <b><u>View the diff</u></b> to see the code, then start the review ...
                            <div class="buttonContainer feebutton">
                                    <?php if ($workitem->getCRStarted() !=1) {
                                        if ($this->hasRights($user_id, $workitem)) {
                                            ?><input class="iToolTip cR smbutton" type="submit" value="Start Code Review" onClick="return CheckCodeReviewStatus();"/><?php
                                        } else {
                                            ?><input class="iToolTip cRDisallowed smbutton" type="submit" value="Start Code Review" onClick="return false;"/><?php
                                        }
                                    } else if ($workitem->getCRStarted() == 1 && $workitem->getCRCompleted() != 1) {
                                        if ($this->hasRights($user_id, $workitem)) {
                                            if ($user_id == $workitem->getCReviewerId() || $workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)) {
                                                ?><input class="iToolTip endCr smbutton" type="submit" value="End Code Review" onClick="return showEndReviewForm();"/><?php
                                            }
                                        }
                                    } ?>
                                <div id="review-pointer"></div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php endif; ?>

            <div id="for_view">
                <div class="moneyZone">
                    <div id="bid-panel">
                        <table width="100%" class="table-bids">
                            <caption class="table-caption" >
                                <div class="noteWrapper"><span class="sections">Bids</span></div>
                                <?php if ($user_id) : ?>
                                    <?php if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'SuggestedWithBid' || ($worklist['status'] == 'Suggested' && $worklist['creator_id']== $user_id)) : ?>
                                        <div class="buttonContainer bidbutton">
                                            <?php if ($user->isEligible()) : ?>
                                                <?php
                                                $buttonText = "Add my bid";
                                                if ($workitem_project->getRepo_type() == 'git' && !$isGitHubConnected) {
                                                    $buttonText = 'Authorize GitHub app';
                                                }
                                                ?>
                                            <input type="submit" class="smbutton" value="<?php echo $buttonText; ?>" onClick="return showConfirmForm('bid');" />
                                            <?php else: ?>
                                            <input type="submit" class="smbutton" value="Add my bid" onClick="return showIneligible('bid');" />
                                            <?php endif; ?>
                                        </div>
                                        <?php if(! empty($bids) && ($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)
                                                 || $user_id == $workitem->getRunnerId()) && count($bids) >1 && !$workitem->hasAcceptedBids()
                                                 && ((($workitem->getStatus()) == "Bidding") || $workitem->getStatus() == "SuggestedWithBid")):?>
                                            <div class="buttonContainer bidbutton">
                                                <input type="submit" value="Accept Multiple"  id="btnAcceptMultiple" onClick="javascript:AcceptMultipleBidOpen();"/>
                                            </div>
                                        <?php endif;?>
                                    <?php endif;?>
                                <?php endif;?>
                                <?php if ($user_id > 0 && $hideFees) { ?>
                                <?php } else { ?>
                                    <?php if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'SuggestedWithBid') { ?>
                                    <div class="buttonContainer bidbutton ">
                                        <input style="cursor:pointer;" class="smbutton" type="button" value="Login to Bid" onClick="sendToLogin(); return false;" />
                                    </div>
                                    <?php } ?>
                                <?php } ?>
                            </caption>
                            <thead>
                                <tr class="table-hdng">
                                    <td><span class="table-back"><span>Who</span></span></td>
                                    <td><span class="table-back"><span class="moneyPaddingSmall">Amount</span></span></td>
                                    <td class="money"><span class="table-back"><span class="moneyPaddingSmall">Done in ...</span></span></td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(empty($bids)) { ?>
                                <tr>
                                    <td style="text-align: center;" colspan="3"><span class="table-back"><span>No bids yet.</span></span></td>
                                </tr>
                            <?php
                                } else {

                                $row = 1;
                                foreach($bids as $bid) {
                                    if ($user->getId() == $bid['bidder_id'] && $bid['expires'] <= 0) {
                                        $biddings['0'] = $bid;
                                    } else if ($user->getId() != $bid['bidder_id'] && $bid['expires'] < 0) {
                                        $biddings = array();
                                    } else {
                                        $biddings['0'] = $bid;
                                    }

                                foreach($biddings as $key=>$bid) {
                                    if ($user->getId() == $bid['bidder_id'] && $bid['expires'] < 0) {
                                        $expired_class = ' expired_warn';
                                    } else {
                                        $expired_class = '';
                                    }
                                    $canSeeBid = $user->getIs_admin() == 1 || $is_project_runner || $user->isRunnerOfWorkitem($workitem) ||
                                                 $user->getId() == $bid['bidder_id'] || ($worklist['status'] == 'SUGGESTEDwithBID' && $workitem->getIsRelRunner());
                                    $row_class = "";
                                    $row_class .= ($user_id) ? 'row-bidlist-live ' : '' ;
                                    $row_class .= ($view_bid_id == $bid['id']) ? ' view_bid_id ' : '' ;
                                    $row_class .= ($row++ % 2) ? 'rowodd ' : 'roweven ';
                                    $row_class .= 'biditem';
                                    $row_class .= ($canSeeBid)
                                                ? "-" . $bid['id'] . ' clickable'
                                                : '';
                                    $row_class .= $expired_class;
                                    ?>
                                <tr class="<?php echo $row_class; ?>">
                                <?php
                                    // store bid info into jquery metadata so we won't have to fetch it again on user click
                                    // but only if user is runner or creator 15-MAR-2011 <godka>
                                    $notes = addcslashes(preg_replace("/\r?\n/", "<br />", $bid['notes']),"\\\'\"&\n\r<>");
                                    if ($canSeeBid) { ?>
                                        <script type="data"><?php echo
                                                "{id: {$bid['id']}, " .
                                                "nickname: '{$bid['nickname']}', " .
                                                "email: '{$bid['email']}', " .
                                                "amount: '{$bid['bid_amount']}', " .
                                                "bid_accepted: '{$bid['bid_accepted']}', " .
                                                "bid_created: '{$bid['bid_created']}', " .
                                                "bid_expires: '" . ($bid['expires'] ? relativeTime($bid['expires']) : "Never") . "', " .
                                                "time_to_complete: '{$bid['time_to_complete']}', " .
                                                "done_in: '{$bid['done_in']}', " .
                                                "bidder_id: {$bid['bidder_id']}, " .
                                                "notes:\"" .  replaceEncodedNewLinesWithBr($notes) . "\"}";
                                        ?></script>
                                    <?php } ?>
                                    <td>
                                    <span class="table-back">
                                        <span>
                                            <?php if ($canSeeBid) { ?>
                                                <a href="#" bidderId="<?php echo $bid['bidder_id'];?>" class="CreatorPopup"><?php echo getSubNickname($bid['nickname']);?></a>
                                            <?php } else echo $bid['nickname']; ?>
                                        </span>
                                    </span>
                                    </td>
                                    <td class="money"><span class="table-back"><span class="moneyPaddingSmall">$ <?php echo $bid['bid_amount'];?></span></span></td>
                                    <td class="money"><span class="table-back"><span class="moneyPaddingSmall"><?php echo $bid['done_in'];?></span></span></td>
<?php
    $expire_class = '';
    if (isset($bid['expires']) && $bid['expires'] != -1) {
        if ($bid['expires'] == -1) {
        } else if ($bid['expires'] <= BID_EXPIRE_WARNING) {
            $expire_class = 'class="warn"';
            if (isset($bid['expires']) && $bid['expires'] != 0) {
                $expire_class = '';
            }
        }
    }
?>
                                </tr>
                                <?php } ?>
                            <?php } ?>
                        <?php } ?>
                            </tbody>
                        </table>
                <!-- End of div bid-panel -->
                    </div>

                        <?php if ($user_id > 0) { ?>
                        <div id="fee-panel" >
                            <table width="100%" class="table-fees" cellpadding="0" cellspacing="0">
                                <caption class="table-caption" >
                                <div class="noteWrapper"><span class="sections">Fees</span></div>
                            <?php if($user_id && ($worklist['status'] != 'Done')): ?>

                                <?php if ($mechanic_id == $user_id) : ?>
                                    <div class="buttonContainer feebutton"><input class="iToolTip addTip smbutton" type="submit" value="Tip User" /></div>
                                <?php endif; ?>

                                <?php if ($user_id): ?>
                                    <div class="buttonContainer feebutton">
                                        <?php if ($user->isEligible()) : ?>
                                            <input class="iToolTip addFee smbutton" type="submit" value="Add a Fee" onClick="return showConfirmForm('fee');"/>
                                        <?php else: ?>
                                            <input class="iToolTip addFee smbutton" type="submit" value="Add a Fee" onClick="return showIneligible('fee');"/>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif;?>
                                </caption>
                                <thead>
                                <tr class="table-hdng">
                                    <td class="who"><span class="table-back"><span>Who</span></span></td>
                                    <td class="fee"><span class="table-back"><span class="moneyPaddingSmall">Amount</span></span></td>
                                    <td class="what"><span class="table-back"><span>What</span></span></td>
                                    <td class="when"><span class="table-back"><span>When</span></span></td>
                                    <td class="paid"><span class="table-back"><span>Paid</span></span></td>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $feeTotal = 0; ?>
                                <?php if(empty($fees)): ?>
                                    <tr>
                                        <td style="text-align: center;" colspan="5"><span class="table-back"><span>No fees yet.</span></span></td>
                                    </tr>
                                <?php else: $row = 1;
                                    foreach($fees as $fee):
                                        $feeTotal += (float) $fee['amount'];
                                ?>
                                    <tr class="row-feelist-live <?php ($row % 2) ? print 'rowodd' : print 'roweven'; $row++; ?> ">
                                        <script type="data"><?php echo
                                                "{id: {$fee['id']}, " .
                                                "nickname: '{$fee['nickname']}', " .
                                                "user_id: '{$fee['user_id']}', " .
                                                "amount: '{$fee['amount']}', " .
                                                "fee_created: '{$fee['date']}', " .
                                                "desc:\"" .  replaceEncodedNewLinesWithBr($fee['desc']) . "\"}";?>
                                        </script>
                                        <td class="nickname who">
                                            <span class="table-back">
                                                <span>
                                                    <a href="userinfo.php?id=<?php echo $fee['user_id'];?>" target="_blank" title="<?php echo $fee['nickname']; ?>">
                                                        <?php echo getSubNickname($fee['nickname'], 8);?>
                                                    </a>
                                                </span>
                                            </span>
                                        </td>
                                        <td class="fee"> <span class="table-back money"><span class="moneyPaddingSmall">$<?php echo $fee['amount'];?></span></span></td>
                                        <td class="pre fee-description what"><span class="table-back"><div class="arrow"></div></span></td>
                                        <td class="when"><span class="table-back"><span>
                                        <?php
                                            $date = explode("/", $fee['date']);
                                            echo date( "M j", mktime(0, 0, 0, $date[0], $date[1], $date[2]));
                                        ?></span></span></td>
                                        <td class="paid">
                                            <span class="table-back">
                                                <span>
                                                    <?php if($is_payer): ?>
                                                    <a href="#" class = "paid-link" id = "feeitem-<?php echo $fee['id'];?>"><?php echo $fee['paid'] == 0 ? "No" : "Yes";?></a>
                                                    <?php else: echo $fee['paid'] == 0 ? "No" : "Yes";?>
                                                    <?php endif;?>

                                                     <?php if ($worklist['status'] != 'Done') { ?>
                                                        <?php if(($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $is_runner)
                                                              || $user_id == $workitem->getRunnerId() || $user_id == $fee['user_id']) &&($user_id && empty($fee['paid']))):?>
                                                        - <a href="#" id="wd-<?php echo $fee['id'];?>" class="wd-link" title='Delete Entry'>delete</a>
                                                        <?php endif;?>
                                                    <?php };?>
                                                </span>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="bid-notes">
                                            <span><?php
                                                $feeDesc = replaceEncodedNewLinesWithBr($fee['desc']);
                                                echo '<b>'.truncateText($feeDesc).'</b><br /><br />';
                                            ?>
                                            <?php if (($worklist['status'] == 'Review' || $worklist['status'] == 'Completed' || $worklist['status'] == 'Done')&& ($fee['desc'] == 'Accepted Bid')) { ?>
                                            <b>Bid Notes:</b> <?php echo preg_replace("/\r?\n/", "<br />", $fee['bid_notes']);?>
                                            <?php }?></span>
                                            <div class="end-line"></div>
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                    <tr id="job-total">
                                        <td colspan="5">
                                            <div class="noteWrapper">
                                                <span class="label">Job Total :</span>
                                                <span class="data">$ <?php echo number_format($feeTotal, 2); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif;?>
                                </tbody>
                            </table>
                            <br />
                    <!-- End of div fee-panel -->
                            <form id="withdraw" method="post" action="" >
                                <input type="hidden" name="action" value="withdraw_bid" />
                                <input type="hidden" class="fee_id" name="fee_id" value="" />
                            </form>
                            <div style="clear: both"></div>
                        </div>
                    <?php } else if ($worklist['status'] == 'Bidding' || $worklist['status'] == 'SuggestedWithBid') { ?>
                    <div class="buttonContainer feebutton">
                        <input style="cursor:pointer;" type="button" class="smbutton" value="Login to Bid" onClick="sendToLogin(); return false;" />
                    </div>
                    <?php } ?>
                <!-- End of div fee-panel -->
            <!-- End of div moneyZone -->
                </div>
                <div id="uploadPanel"> </div>
                <?php if(JOURNAL_EXISTS) { // include the journal dropdown if the journal setting is true ?>
                <div id="journalPanel">
                        <h3>Activity</h3>
                        <div id="journalChat">
                            <?php
                                echo $this->getTaskPosts($worklist['id']);
                             ?>
                        </div>
                </div>
                <?php } ?>
        <!-- End of for_view-->
            </div>
<!-- End of div right-panel-->
        </div>
<!-- End of div pageContent -->
    </div>

<?php if ($action =="edit"):  ?>
                </form>
<?php endif; ?>
    <!-- Popup HTML for Placing a bid -->
    <?php if(isset($message)): ?>
    <div id="message" style="display:none; padding: 0 0.7em; margin: 0.7em 0;" class="ui-state-highlight ui-corner-all">
        <p><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span><strong>Info:</strong><?php echo $message; ?></p>
    </div>
    <?php endif;?>
    <!-- Popup for  edit bid info -->
    <?php include('dialogs/popup-edit-bid-info.inc') ?>
    <!-- Popup for place bid -->
    <?php include("dialogs/popup-place-bid.inc"); ?>
    <!-- Popup HTML for adding a fee -->
    <?php include("dialogs/popup-addfee.inc"); ?>
    <!-- Popup HTML for starting a code review -->
    <?php include("dialogs/popup-startreview.inc"); ?>
    <!-- Popup HTML for ending a code review -->
    <?php include("dialogs/popup-endreview.inc"); ?>
    <!-- Popup HTML for informing user that code review is already started review -->
    <?php include("dialogs/code-review-already-started.inc"); ?>
    <!-- Popup for ping user on task-->
    <?php require_once('dialogs/popup-pingtask.inc') ?>
    <!-- Popup for bid info-->
    <?php require_once('dialogs/popup-bid-info.inc') ?>
    <!-- Popups for showing user statistics-->
    <?php require_once('dialogs/popups-userstats.inc') ?>
    <!-- Popup for multipleBid info-->
    <?php require_once('dialogs/popup-multiple-bid-info.inc') ?>
    <!-- Popup for confirmation info-->
    <?php require_once('dialogs/popup-confirmation.inc') ?>
    <!-- Popup for breakdown of fees-->
    <?php require_once('dialogs/popup-fees.inc') ?>
    <!-- Popup for fee info-->
    <?php require_once('dialogs/popup-fee-info.inc') ?>
    <!-- Popups for entering bid withdraw reason-->
    <?php require_once('dialogs/popup-withdraw-bid.inc') ?>
    <!-- Popups for entering bid decline reason-->
    <?php require_once('dialogs/popup-decline-bid.inc') ?>
    <!-- Popup for Add Review Url-->
    <?php require_once('dialogs/review-url-dialog.inc') ?>
    <!-- Include Paid Popup -->
    <?php require_once 'dialogs/popup-paid.inc'; ?>
    <?php require_once('dialogs/budget-expanded.inc'); ?>
    <!-- Invite people popup -->
    <?php require_once 'dialogs/popup-invite-people.inc'; ?>
    <!-- inform runner that they cant edit a job -->
    <?php require_once 'dialogs/popup-runner-job-edit.inc'; ?>

<?php
    // if logged in user is the mechanic on the task, get the maximum amount
    // that they can tip from their Accepted Bid
    if ($mechanic_id == $user_id) {
        $max_tip = 0;
        foreach ($fees as $fee) {
            if ($fee['desc'] == 'Accepted Bid') {
                $max_tip = $fee['amount'];
                break;
            }
        }
        if ($max_tip > 0) {
            require_once('dialogs/popup-addtip.inc');
        }
    }
?>
<?php
    if (! $user->isEligible()) {
        require_once('dialogs/popup-ineligible.inc');
    }
?>
<!-- Footer html-->
<?php include("footer.php"); ?>
*/
