<?php

class AddJobView extends View {
    public $title = 'Add task / Report bug - Worklist';

    public $stylesheets = array(
        'css/worklist.css',
        'css/smoothness/lm.ui.css',
        'css/smoothness/white-theme.lm.ui.css',
        'css/tooltip.css',
        'css/font-awesome/css/font-awesome.min.css',
        'css/addjob.css'
    );

    public $scripts = array(
        "js/jquery/jquery.timeago.js",
        "js/jquery/jquery.metadata.js",
        "js/jquery/jquery.template.js",
        "js/ajaxupload/ajaxupload.js",
        "js/uploadFiles.js",
        "js/skills.js",
        "js/addjob.js"
    );

    public function render() {
        $projects = $this->read('projects');
        $current = $this->read('current');
        $this->projects = array();
        foreach ($projects as $project) {
            $this->projects[] = array(
                'id' => $project['id'],
                'name' => $project['name'],
                'current' => ($project['name'] == $current) ? true : false
            );
        }
        $this->first_selected = empty($current);
        return parent::render();
    }
}