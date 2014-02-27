<?php

class LoginView extends View {
    public $title = 'Login | Worklist';

    public $stylesheets = array(
        'css/worklist.css',
        'css/login.css'
    );

    public $scripts = array(
        'js/jquery/jquery.timeago.js',
        'js/jquery/jquery.metadata.js',
        'js/ajaxupload/ajaxupload.js'
    );

    public function render() {
        $this->redir = $this->read('redir');
        $error = $this->read('error');
        $this->failed = $error->getErrorFlag();
        if ($this->failed) {
            $this->errors = array();
            foreach ($error->getErrorMessage() as $msg) {
                $this->errors[]['msg'] = $msg;
            }
        }
        return parent::render();
    }
}
