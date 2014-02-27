<?php

class ForgotView extends View {
    public $title = 'Recover Password - Worklist';

    public $stylesheets = array(
        'css/worklist.css'
    );

    public function render() {
        $this->msg = $this->read('msg');
        return parent::render();
    }
}