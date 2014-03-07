<?php

use \Michelf\Markdown;

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
        $gh_events = $this->read('gh_events');
        $entries = array_merge($worklist_entries, $gh_events);
        usort($entries, array('StatusView', 'sortEntries'));
        $ret = '';
        $now = 0;

        foreach($entries as $entry) {
            if (!$now) {
                $now = strtotime(Model::now());
            }
            if (get_class($entry) == 'EntryModel') {
                $id = $entry->id;
                $type = 'worklist';
                $date = $entry->date;
                if (strtotime($entry->date) < strtotime('2014-03-06 00:00:00')) {
                    $content = $entry->entry;
                } else {
                    $content = self::markdown($entry->entry);
                }                
            } else { // github event
                if (!preg_match('/^(PullRequest(ReviewComment)?|IssueComment)Event$/', $entry['type'])) {
                    continue;
                }                
                $id = $entry['id'];
                $type = 'github' . preg_replace('/Event$/', '', $entry['type']);
                $date = $entry['created_at'];
                $content = self::formatGithubEntry($entry);
            }
            $time = strtotime($date);

            $ret .= 
                  '<li entryid="' . $id . '" date="' . $date  . '" type="' . $type .  '">'
                .     '<h4>' . relativeTime($time - $now) . '</h4>'
                .     $content
                . '</li>';
        }
        return $ret;
    }

    static function sortEntries($a, $b) {
        if (get_class($a) == 'EntryModel') {
            $date_a = $a->date;
        } else { // github event
            $date_a = $a['created_at'];
        }
        if (get_class($b) == 'EntryModel') {
            $date_b = $b->date;
        } else { // github event
            $date_b = $b['created_at'];
        }
        $time_a = strtotime($date_a);
        $time_b = strtotime($date_b);
        return ($time_a == $time_b) ? 0 : ($time_a > $time_b ? +1 : -1);
    }

    static function formatGithubEntry($entry) {
        $ret = '*GIT*: ';
        extract($entry);
        switch($type) {
            case 'PullRequestEvent':
                $ret .= 
                        '@' . $actor['login'] . ' ' . $payload['action'] . ' [#'
                     . $payload['number'] . '](' . $payload['pull_request']['html_url'] . ')'
                     . "\n\n**" . $payload['pull_request']['title'] . '**';
                break;
            case 'IssueCommentEvent':
                $ret .= 
                        '@' . $actor['login'] . ' commented on [#'
                     . $payload['issue']['number'] . '](' . $payload['issue']['html_url'] . ')'
                     . "\n\n**" . $payload['issue']['title'] . '**';
                break;
            case 'PullRequestReviewCommentEvent':
                $pr_number = preg_replace('^https?:\/\/.+\/pulls\/', '', $payload['comment']['pull_request_url']);
                $ret .= 
                        '@' . $actor['login'] . ' commented on [#'
                     . $pr_number . '](' . $payload['comment']['html_url'] . ')';
                break;
        }
        return self::markdown($ret);
    }

    static function markdown($text) {
        return Markdown::defaultTransform($text);
    }
}