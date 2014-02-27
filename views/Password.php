<?php

class PasswordView extends View {
    public $title = 'Change Password - Worklist';

    public $stylesheets = array(
        'css/worklist.css'
    );

    public $scripts = array(
        'js/password.js'
    );

    public function render() {
        $this->msg = $this->read('msg');
        return parent::render();
    }
}