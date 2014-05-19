<?php

class AddJobView extends View {
    public $title = 'Add task / Report bug - Worklist';
    public $stylesheets = array(
        'css/addjob.css'
    );
    public $scripts = array(
        'js/jquery/jquery.template.js',
        'js/ajaxupload/ajaxupload.js',
        'js/skills.js',
        'js/addjob.js'
    );

    public function render() {
        $this->first_selected = empty($current);
        return parent::render();
    }

    public function projects() {
        $user = $this->currentUser;
        $current = $this->read('current');
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
}
