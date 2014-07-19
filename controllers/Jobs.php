<?php

class JobsController extends Controller {
    public function run($action, $param = '') {
        $method = '';
        switch($action) {
            case 'getProjectJobs':
                $method = $action;
                break;
            default:
                $method = 'index';
                $param = empty($param) ? $action : $action."/".$param;
                break;
        }

        $params = preg_split('/\//', $param);
        call_user_func_array(array($this, $method), $params);
    }

    public function index($projectName = null, $filterName = null) {
        // $nick is setup above.. and then overwritten here -- lithium
        $nick = '';
        $userId = getSessionUserId();
        $is_internal = 0;
        if ($userId > 0) {
            initUserById($userId);
            $user = new User();
            $user->findUserById($userId);
            // @TODO: this is overwritten below..  -- lithium
            $nick = $user->getNickname();
            $userbudget =$user->getBudget();
            $budget = number_format($userbudget);
            $is_internal = $user->isInternal();
        }

        $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
        $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
        $is_admin = !empty($_SESSION['is_admin']) ? 1 : 0;

        $workitem = new WorkItem();

        $filter = new Agency_Worklist_Filter();

        // krumch 20110418 Set to open Worklist from Journal
        $filter->initFilter();
        $filter->setName('.worklist');

        if (! empty($_REQUEST['status'])) {
            $filter->setStatus($_REQUEST['status']);
        } else {
            $filter->setStatus('ALL');
        }

        if ($projectName != null) {
            $project = Project::find($projectName);
            if ($project) {
                $filter->setProjectId($project->getProjectId());
            } else {
                $filter->setProjectId(0);
            }
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

        $filter->setFollowing(($filterName != null && $filterName == "following") ? true : false);
        $filter->setStatus(($filterName != null && $filterName == "passed") ? "Done,Pass" : "Bidding,In Progress,QA Ready,Review,Merged,Suggestion");

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
        parent::run();
    }
    public function getProjectJobs() {
        $this->view = null;
        $filter = new Agency_Worklist_Filter();
        $filter->setStatus(!empty($_REQUEST['status']) ? $_REQUEST['status'] : "Bidding,In Progress,QA Ready,Review,Merged,Suggestion");
        $filter->setProjectId(!empty($_REQUEST['project_id']) ? $_REQUEST['project_id'] : $filter->getProjectId());
        echo json_encode(Project::getProjectJobs($filter, $_REQUEST['query'], $_REQUEST['offset'], $_REQUEST['limit'], $is_runner, $is_internal));
    }
}
