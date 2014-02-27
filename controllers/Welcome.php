<?php

class WelcomeController extends Controller {
    public function run() {
        Session::check();
        parent::run();
    }
}
