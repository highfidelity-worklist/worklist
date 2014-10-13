<?php

class WelcomeView extends View {
    public $stylesheets = array(
        'css/welcome.css'
    );

    public function areBiddingJobs() {
        return count($this->read('biddingJobs')) > 0;
    }

    public function biddingJobs() {
        $jobs = $this->read('biddingJobs');
        $ret = array();
        foreach($jobs as $key => $job_number) {
            $workitem = WorkItem::getById($job_number);
            if (!$workitem->getProjectId()) {
                continue;
            }
            $project = Project::getById($workitem->getProjectId());
            $ret[] = array(
                'id' => $workitem->getId(),
                'summary' => $workitem->getSummary(),
                'labels' => implode(', ', $workitem->getLabels()),
                'project' => $project->getName()
            );
        }
        return $ret;
    }
}
