<?php

class ReportsView extends View {
    public $title = 'Reports - Worklist';

    public $stylesheets = array(
        'css/datepicker.css',
        'css/reports.css'
    );

    public $scripts = array(
        'js/jquery/jquery.tablednd_0_5.js',
        'js/jquery/jquery.metadata.js',
        'js/raphael/raphael-min.js',
        'js/timeline-chart.js',
        'js/reports.js'
    );

    public function render() {
        $this->activeUsers = $this->read('activeUsers');
        $this->activeRunners = $this->read('activeRunners');
        $this->w2_only = $this->read('w2_only');
        $this->showTab = $this->read('showTab');
        return parent::render();
    }

    public function users() {
        $users = User::getUserList(Session::uid(), false, 0, true);
        $ret = array();
        $default = isset($_REQUEST['user']) ? $_REQUEST['user'] : null;
        $ret[] = array(
            'id' => 0,
            'nickname' => 'ALL',
            'selected' => is_null($default)
        );
        foreach($users as $user) {
            $ret[] = array(
                'id' => $user->getId(),
                'nickname' => $user->getNickname(),
                'selected' => ($default === $user->getId())
            );
        }
        return $ret;
    }

    public function projects() {
        $projects = Project::getProjects();
        $ret = array();
        $default = isset($_REQUEST['project_id']) ? $_REQUEST['project_id'] : null;
        foreach($projects as $project) {
            $ret[] = array_merge($project, array(
                'selected' => ($project['project_id'] === $default)
            ));
        }
        return $ret;
    }

    public function status() {
        $statuses = WorkItem::getStates();
        $ret = array();
        $default = isset($_REQUEST['status']) ? $_REQUEST['status'] : 'Done';
        foreach ($statuses as $status) {
            $ret[] = array(
                'status' => $status,
                'selected' => ($default == $status)
            );
        }
        return $ret;
    }

    public function runners() {
        $active = $this->read('activeUsers');
        $runners = User::getUserList(Session::uid(), $active, 1, true);
        $ret = array();
        $default = isset($_REQUEST['runner']) ? $_REQUEST['runner'] : null;
        foreach($runners as $runner) {
            $ret[] = array(
                'id' => $runner->getId(),
                'nickname' => $runner->getNickname(),
                'selected' => ($default === $runner->getId())
            );
        }
        return $ret;
    }

    public function funds() {
        $funds = Fund::getFunds();
        $ret = array();
        $default = isset($_REQUEST['fund_id']) ? $_REQUEST['fund_id'] : -1;
        $ret[] = array(
            'id' => -1,
            'name' => 'ALL',
            'selected' => ($default == -1)
        );
        foreach ($funds as $fund) {
            $fund['selected'] = ($default == $fund['id']);
            $ret[] = $fund;
        }
        $ret[] = array(
            'id' => 0,
            'name' => 'Not Funded',
            'selected' => ($default == 0)
        );
        return $ret;
    }

    public function filterPaidStatusAll() {
        return !isset($_REQUEST['paidstatus']) || $_REQUEST['paidstatus'] == 'ALL';
    }

    public function filterPaidStatusPaid() {
        return isset($_REQUEST['paidstatus']) && $_REQUEST['paidstatus'] == '1';
    }

    public function filterPaidStatusUnpaid() {
        return isset($_REQUEST['paidstatus']) && $_REQUEST['paidstatus'] == '0';
    }

    public function filterTypeAll() {
        return !isset($_REQUEST['type']) || $_REQUEST['type'] == 'ALL';
    }

    public function filterTypeFee() {
        return isset($_REQUEST['type']) && $_REQUEST['type'] == 'Fee';
    }

    public function filterTypeBonus() {
        return isset($_REQUEST['type']) && $_REQUEST['type'] == 'Bonus';
    }

    public function filterTypeExpense() {
        return isset($_REQUEST['type']) && $_REQUEST['type'] == 'Expense';
    }

    public function filterOrderedByName() {
        return !isset($_REQUEST['order']) || $_REQUEST['order'] == 'name';
    }

    public function filterOrderedByDate() {
        return isset($_REQUEST['order']) && $_REQUEST['order'] == 'date';
    }

    public function filterStartDate() {
        return (
            isset($_REQUEST['start']) && $_REQUEST['start']
                ? $_REQUEST['start']
                : date("m/d/Y",strtotime('-90 days', time()))
        );
    }

    public function filterEndDate() {
        return (
            isset($_REQUEST['end']) && $_REQUEST['end']
                ? $_REQUEST['end']
                : date("m/d/Y",time())
        );
    }

    public function filterDirAsc() {
        return (
            (isset($_REQUEST['dir']) && $_REQUEST['dir']) == 'DESC'
                ? 'false'
                : 'true'
        );
    }
}
