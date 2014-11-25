<?php

class WelcomeController extends Controller {
    public function run() {
        $stats = getStats('Bidding');
        $this->write('biddingJobs', array_slice($stats, 0, 10));
        parent::run();
    }
}
