<?php

class ConfirmationView extends View {
    public $title = 'Email confirmation - Worklist';

    public $stylesheets = array(
        'css/worklist.css'
    );

    public $scripts = array(
        'js/ajaxupload/ajaxupload.js',
        'js/confirmation.js'
    );

    public function render() {
        $this->userCountry = $this->read('userCountry');

        return parent::render();
    }
}