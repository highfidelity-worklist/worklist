<?php

class ProjectsView extends View {
    public $title = 'Projects - Worklist';

    public $stylesheets = array(
        'css/worklist.css',
        'css/projects.css'
    );
    
    public $scripts = array(
        'js/jquery/jquery.timeago.js',
        'js/jquery/jquery.metadata.js',
        'js/jquery/jquery.infinitescroll.min.js',
        'js/ajaxupload/ajaxupload.js',
        'js/project.js',
        'js/github.js'
    );
}
