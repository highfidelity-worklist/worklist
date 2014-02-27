<?php

class ResetPassView extends View {
    public $title = 'Recover Password - Worklist';

    public $stylesheet = array(
        'css/worklist.css'
    );

    public $scripts = array(
        'js/resetpass.css'
    );

    public function render() {
        $this->msg = $this->read('msg');
        $this->un = $this->read('un');
        $this->token = $this->read('token');

        return parent::render();
    }
}