<?php

class HelpController extends Controller {
    public function run() {
        Session::check();
        parent::run();
    }
}
