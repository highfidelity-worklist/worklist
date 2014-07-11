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
           $filter->setUser(0);
        }

        if (! empty($_REQUEST['query'])) {
            $filter->setQuery($_REQUEST['query']);
        } else {
            $filter->setQuery("");
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
