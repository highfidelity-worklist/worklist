<?php

class StatusController extends Controller {
    public function run() {
        $entry = new EntryModel();
        $this->write('entries', $entry->latest(60 * 24 * 30, 200));
        parent::run();
    }
}
