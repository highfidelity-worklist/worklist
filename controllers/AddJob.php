<?php

class AddJobController extends Controller {
    public function run() {
        checkLogin();
        parent::run();
    }
}
