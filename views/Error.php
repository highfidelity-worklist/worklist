<?php

class ErrorView extends View {
    public $title = 'Error';
    public $link = '';
    public $msg = '';

    public $stylesheets = array(
        'css/newworklist.css',
        'css/error.css',
        'css/font-awesome/css/font-awesome.min.css'
    );

    public $scripts = array(
        'js/error.js'
    );

    public function render() {
        $this->msg = $this->read('msg');
        $this->link = $this->read('link');
        $this->layout = 'EmptyBody';
        return parent::render();
    }
}
