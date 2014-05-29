<?php

class AddJobView extends View {
    public $title = 'Add job - Worklist';
    public $stylesheets = array(
        'css/addjob.css'
    );
    public $scripts = array(
        'https://raw.github.com/ProgerXP/FileDrop/master/filedrop.js',
        'https://fgnass.github.io/spin.js/spin.min.js',
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

    public function skills() {
        $skill = new SkillModel();
        return $skill->loadAll();
    }
}
