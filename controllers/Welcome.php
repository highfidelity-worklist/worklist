<?php

class WelcomeController extends Controller {
    public function run() {
        $stats = Utils::getStats('Bidding');
        $this->write('biddingJobs', array_slice($stats, 0, 10));
        parent::run();
    }
}
