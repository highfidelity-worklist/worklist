<?php

class ErrorView extends View {
    public $title = 'Error';

    public $stylesheets = array(
        'css/newworklist.css',
        'css/error.css',
        'css/font-awesome/css/font-awesome.min.css'
    );

    public $scripts = array(
        '//use.typekit.net/xyu1mnf.js',
        'js/error.js'
    );

    public function render() {
        $this->msg = $this->read('msg');
        $this->layout = 'EmptyBody';
        return parent::render();
    }
}
