<?php

class AddJobController extends Controller {
    public function run() {
        checkLogin();
        $this->write('current', isset($_GET['project']) ? $_GET['project'] : '');
        parent::run();
    }
}
