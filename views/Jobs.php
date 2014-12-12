<?php

class JobsView extends View {
    public $title = 'Jobs - Worklist';
    public $stylesheets = array(
        'css/jobs.css'
    );
    public $scripts = array(
        'js/jobs.js'
    );

    public function render() {
        $this->page = $this->read('page');
        $this->review_only = $this->read('review_only');
        return parent::render();
    }

    public function projects() {
        $projects = Project::getProjects();
        $default = $this->read('projectFilter');
        $ret = array();
        $ret[] = array(
            'project_id' => '0',
            'name' => 'All projects',
            'selected' => !$default
        );
        foreach($projects as $project) {
            $ret[] = array_merge($project, array(
                'selected' => ($project['project_id'] == $default)
            ));
        }
        return $ret;
    }

    public function projectFilter() {
        return $this->read('projectFilter');
    }

    public function queryFilter() {
        return $this->read('queryFilter');
    }

    public function statusFilter() {
        return $this->read('statusFilter');
    }

    public function labelsFilter() {
        return $this->read('labelsFilter');
    }

    public function followingFilter() {
        return $this->read('followingFilter');
    }
}
