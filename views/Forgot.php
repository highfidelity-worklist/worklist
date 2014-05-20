<?php

class ForgotView extends View {
    public $title = 'Recover Password - Worklist';

    public function render() {
        $this->msg = $this->read('msg');
        return parent::render();
    }
}
