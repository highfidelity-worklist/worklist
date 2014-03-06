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
        $worklist_entries = array_reverse($this->read('entries'));
        $gh_pullreqs = $this->read('pullreqs');
        $entries = array_merge($worklist_entries, $gh_pullreqs);
        usort($entries, array('StatusView', 'sortEntries'));
        $ret = '';
        $now = 0;

        foreach($entries as $entry) {
            if (!$now) {
                $now = strtotime(Model::now());
            }
            if (get_class($entry) == 'EntryModel') {
                $id = $entry->id;
                $source = 'worklist';
                $class = $entry->oldFormatEntry ? '' : 'markdown';
                $date = $entry->date;
                $content = $entry->oldFormatEntry() ? $entry->entry : $entry->markdownEntry();
            } else { // github pull request
                $id = $entry['sha'];
                $source = 'github-pullreq';
                $class = '';
                $date = $entry['updated_at'];
                $content = $entry['state'] . ' - ' . $entry['title'];
            }
            $time = strtotime($date);

            $ret .= 
                  '<li entryid="' . $id . '" date="' . $date  . '" source="' . $source .  '" class="' . $class . '">'
                .     '<h4>' . relativeTime($time - $now) . '</h4>'
                .     $content
                . '</li>';
        }
        return $ret;
    }

    static function sortEntries($a, $b) {
        if (get_class($a) == 'EntryModel') {
            $date_a = $a->date;
        } else { // pull request
            $date_a = $a['updated_at'];
        }
        if (get_class($b) == 'EntryModel') {
            $date_b = $b->date;
        } else { // pull request
            $date_b = $b['updated_at'];
        }
        $time_a = strtotime($date_a);
        $time_b = strtotime($date_b);
        return ($time_a == $time_b) ? 0 : ($time_a > $time_b ? +1 : -1);
    }
}