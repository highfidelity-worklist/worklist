<?php

class AddJobView extends View {
    public $title = 'Add job - Worklist';
    protected $jobId = 0;
    protected $workItem = array();
    protected $worklist = array();
    protected $editing = false;
    public $stylesheets = array(
        'css/addjob.css'
    );
    public $scripts = array(
        'js/filedrop/filedrop-min.js',
        'js/spin.js/spin.min.js',
        'js/addjob.js'
    );

    public function render() {
        $this->jobId = $this->read('jobId');
        if ($this->jobId > 0) {
           $this->editing = true;
           $this->workItem = WorkItem::getById($this->jobId);
           $WorkItem = new WorkItem();
           $this->worklist = $WorkItem->getWorkItem($this->jobId);
           $this->title = 'Edit #'. $this->jobId .' job - Worklist';
        }
        return parent::render();
    }

    public function userIsInternal() {
        $user = new User($this->currentUser['id']);

        return $user->isInternal();
    }

    public function projects() {
        $user = $this->currentUser;
        $current = empty($this->workItem) ? 'hifi' : $this->workItem()->getProjectName();
        $activeOnly = !($user['is_runner'] || $user['is_admin'] || $user['is_payer']);
        $projects = Project::getProjects($activeOnly);
        $ret = array();
        foreach ($projects as $project) {
            $ret[] = array(
                'id'        => $project['project_id'],
                'name'      => $project['name'],
                'current'   => ($project['name'] == $current) ? true : false
            );
        }
        return $ret;
    }

    public function skills() {
        $skillModel = new SkillModel();
        $skillList = $skillModel->loadAll();
        $skills = array();
        foreach($skillList as $skill) {
            $skillObj = array("id" => $skill->id, "skill" => $skill->skill, "checked" => false);
            if ($this->editing() && in_array($skill->skill, $this->workItem()->getSkills())) {
                $skillObj['checked'] = true;
            }
            array_push($skills, $skillObj);
        }
        return $skills;
    }

    public function internalUsers() {
        return User::getInternals();
    }

    public function editing() {
        return $this->editing;
    }

    public function workItem() {
        return $this->workItem;
    }

    public function status() {
        $statusList = array(
                        array("name" => "Bidding", "selected" => true),
                        array("name" => "Suggestion", "selected" => false),
                        array("name" => "In Progress", "selected" => false),
                        array("name" => "Draft", "selected" => false)
                      );
        $statusMatch = false;
        if ($this->editing) {
            $statusList[0]['selected'] = false;

            foreach($statusList as $key => $status) {
                if ($status['name'] == $this->workItem()->getStatus()) {
                    $statusList[$key]['selected'] = true;
                    $statusMatch = true;
                    breaK;
                }
            }
            if (!$statusMatch) {
                array_push($statusList, array("name" => $this->workItem()->getStatus(), "selected" => true));
            }
        }
        return $statusList;
    }

    public function getBudgetCombo() {
        $user = User::find($this->currentUser['id']);
        return $user->getBudgetCombo($this->workItem()->getBudget_id());
    }

    public function canSeeBudgetArea() {
        $worklist = $this->worklist;
        $user = User::find($this->currentUser['id']);
        return (
            $user->isRunnerOfWorkitem($this->workItem)
          || $_SESSION['userid'] == $worklist['budget_giver_id']
          || strpos(BUDGET_AUTHORIZED_USERS, "," . $_SESSION['userid'] . ",") !== false
        );
    }

    public function isRunnerOfWorkitem() {
        $workitem = $this->workItem;
        $user = User::find($this->currentUser['id']);
        return $user->isRunnerOfWorkitem($workitem);
    }

    public function canReassignRunner() {
        $workitem = $this->workItem;
        $user = User::find($this->currentUser['id']);
        return (int) (($workitem->getIsRelRunner() || ($user->getIs_admin() == 1 && $this->currentUser['is_runner'])));
    }

    public function editableRunnerBox() {
        $workitem = $this->workItem;
        $worklist = $this->worklist;
        $user = User::find($this->currentUser['id']);
        $runnerslist = Project::getAllowedRunnerlist($worklist['project_id']);
        $ret = '<select name="runner">';
        foreach ($runnerslist as $runner) {
            $ret .=
                '<option value="' . $runner->getId() . '"' . (($worklist['runner_id'] == $runner->getId()) ? ' selected="selected"' : '') . '>' .
                    $runner->getNickname() .
                '</option>';
        }
        $ret .= '</select>';
        return $ret;
    }
}
