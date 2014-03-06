<?php

class StatusView extends View {
    public $layout = 'NewWorklist';

    public $title = "Status - Worklist";

    public $stylesheets = array(
        'css/status.css'
    );

    public $scripts = array(
        'js/status.js'
    );

    public function render() {
        return parent::render();
    }

    public function biddingJobsCount() {
        $stats = getStats('currentlink');
        return $stats['count_b'];
    }

    public function entries() {
        return $this->read('entries');
    }
}