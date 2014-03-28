<?php

class LoginView extends View {
    public $layout = 'NewWorklist';

    public $title = 'Login | Worklist';

    public $stylesheets = array(
        'css/login.css'
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
