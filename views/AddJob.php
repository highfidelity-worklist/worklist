<?php

class AddJobView extends View {
    public $layout = 'NewWorklist';
    public $title = 'Add task / Report bug - Worklist';
    public $stylesheets = array('css/addjob.css');
    public $scripts = array(
        'js/ajaxupload/ajaxupload.js',
        'js/uploadFiles.js',
        'js/skills.js',
        'js/addjob.js'
    );

    public function render() {
        $projects = $this->read('projects');
        $current = $this->read('current');
        $this->projects = array();
        foreach ($projects as $project) {
            $this->projects[] = array(
                'id'        => $project['id'],
                'name'      => $project['name'],
                'current'   => ($project['name'] == $current) ? true : false
            );
        }
        $this->first_selected = empty($current);
        return parent::render();
    }
}