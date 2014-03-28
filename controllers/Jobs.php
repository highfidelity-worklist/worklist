<?php

class JobsController extends Controller {
    public function run() {
        // Get the page number to show, set default to 1
        $this->write('page', isset($_REQUEST["page"]) ? (int) $_REQUEST['page'] : 1);

        $journal_message = '';

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
        }

        $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
        $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
        $is_admin = !empty($_SESSION['is_admin']) ? 1 : 0;

        $workitem = new WorkItem();

        $filter = new Agency_Worklist_Filter();

        // krumch 20110418 Set to open Worklist from Journal
        $filter->initFilter();
        $filter->setName('.worklist');
        $project_id = 0;

        if (! empty($_REQUEST['status'])) {
            $filter->setStatus($_REQUEST['status']);
        } else {
            if (array_key_exists('status', $_REQUEST)) {
                $filter->setStatus('ALL');
            }
        }

        if (isset($_REQUEST['project'])) {
            $project = new Project();
            try {
                $project->loadByName($_REQUEST['project']);
                if ($project_id = $project->getProjectId()) {
                    $filter->setProjectId($project_id);
                }
            } catch(Exception $e) {
                $filter->setProjectId(0);
            }
            unset($project);
        }

        if (! empty($_REQUEST['user'])) {
            $filter->setUser($_REQUEST['user']);
        } else {
            if (array_key_exists('user', $_REQUEST)) {
                $filter->setUser(0);
            }
        }

        if ($userId > 0 && isset($_POST['save_item'])) {
            $args = array(
                'itemid',
                'summary',
                'project_id',
                'status',
                'notes',
                // @TODO: I don't think bid_fee_* fields are relevant anymore -- lithium
                'bid_fee_desc',
                'bid_fee_amount',
                'bid_fee_mechanic_id',
                'invite',
                // @TODO: Same goes for is_expense and is_rewarder.. -- lithium
                'is_expense',
                'is_rewarder',
                'is_bug',
                'bug_job_id'
            );

            foreach ($args as $arg) {
                // Removed mysql_real_escape_string, because we should
                // use it in sql queries, not here. Otherwise it can be applied twice sometimes
                $$arg = (! empty($_POST[$arg])) ? $_POST[$arg] : '';
            }

            $creator_id = $userId;

            if (!empty($_POST['itemid']) && ($_POST['status']) != 'Draft') {
                $workitem->loadById($_POST['itemid']);
            } else {
                $workitem->setCreatorId($creator_id);
            }
            $workitem->setSummary($summary);

            $workitem->setBugJobId($bug_job_id);
            // not every runner might want to be assigned to the item he created - only if he sets status to 'BIDDING'
            if ($status == 'Bidding' && $user->getIs_runner() == 1) {
                $runner_id = $userId;
            } else {
                $runner_id = 0;
            }

            $workitem->setRunnerId($runner_id);
            $workitem->setProjectId($project_id);
            $workitem->setStatus($status);
            $workitem->setNotes($notes);
            $workitem->is_bug = isset($is_bug) ? true : false;
            $workitem->save();

            $journal_message .= '**#' . $workitem->getId() . '**';
            if (!empty($_POST['itemid']) && ($_POST['status']) != 'Draft') {
                $journal_message .= " updated ";
            } else {
                $journal_message .= " added ";
            }
            $journal_message .= 'by @' . $nick;

            Notification::statusNotify($workitem);
            if (is_bug) {
                $bug_journal_message = " (bug of job **#".$bug_job_id."**)";
                notifyOriginalUsersBug($bug_job_id, $workitem);
            }

            if (empty($_POST['itemid']))  {
                $bid_fee_itemid = $workitem->getId();
                $journal_message .= "\n\n**" . $summary . '**';
                if (!empty($_POST['files'])) {
                    $files = explode(',', $_POST['files']);
                    foreach ($files as $file) {
                        $sql = 'UPDATE `' . FILES . '` SET `workitem` = ' . $bid_fee_itemid . ' WHERE `id` = ' . (int)$file;
                        mysql_query($sql);
                    }
                }
            } else {
                $bid_fee_itemid = $itemid;
                $journal_message .=  $bug_journal_message . "\n\n**" . $summary . '**';
            }

            if (! empty($_POST['invite'])) {
                $people = explode(',', $_POST['invite']);
                invitePeople($people, $workitem);
            }

            // send journal notification is there is one
            if (! empty($journal_message)) {
                sendJournalNotification(stripslashes($journal_message));
            }
        }

        // Prevent reposts on refresh
        if (! empty($_POST)) {
            unset($_POST);
            $this->view = null;
            Utils::redirect('./jobs');
            exit();
        }

        $worklist_id = isset($_REQUEST['job_id']) ? intval($_REQUEST['job_id']) : 0;

        $this->write('filter', $filter);
        $this->write('req_status', isset($_GET['status']) ? $_GET['status'] : '');
        $this->write('review_only', (isset($_GET['status']) &&  $_GET['status'] == 'needs-review') ? 'true' : 'false');
    }
}
