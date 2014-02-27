<?php

class AddJobController extends Controller {
    public function run() {
        checkLogin();
        $this->write('projects', Project::getProjects(true));
        $this->write('current', isset($_GET['project']) ? $_GET['project'] : '');
        parent::run();
    }
}
