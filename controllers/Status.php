<?php

class StatusController extends Controller {
    protected $token = GITHUB_API_TOKEN;
    protected $client = null;

    public function run() {
        $this->client = new Github\Client(
            new Github\HttpClient\CachedHttpClient(array(
                'cache_dir' => TEMP_DIR . DIRECTORY_SEPARATOR . 'github'
            ))
        );
        $this->client->authenticate($this->token, '', Github\Client::AUTH_HTTP_TOKEN);

        // first: request github for 3 pages with 30 events data each 
        $events = array_merge(
            $this->listGithubEvents(GITHUB_ORGANIZATION, 1),
            $this->listGithubEvents(GITHUB_ORGANIZATION, 2),
            $this->listGithubEvents(GITHUB_ORGANIZATION, 3)
        );
        $this->write('gh_events', $events);

        // then: find out $minutes_ago based on the last event in order to 
        // use as date limit when looking for worklist entries
        $fromTime = strtotime(self::olderGithubEventDate($events));
        $toTime = strtotime(Model::now());
        $minutes_ago = round(abs($toTime - $fromTime) / 60, 2);

        // and, finally: fetches worklist data
        $entry = new EntryModel();
        $this->write('entries', $entry->latest($minutes_ago, 90));

        // alright, ready to go :)
        parent::run();
    }

    /**
     * returns the older date from the latest status-renderable github event, used 
     * when requesting for worklist entries (when setting a $minutes_ago date limit)
     */
    static function olderGithubEventDate($events) {
        $reverse_events = array_reverse($events);
        foreach($reverse_events as $event) {
            if (!preg_match('/^(Fork|PullRequest(ReviewComment)?|IssueComment)Event$/', $event['type'])) {
                continue;
            }
            return $event['created_at'];
        }
    }

    /**
     * Brings events from github for a specified organization/user at specified page.
     * Accorging to github api, each page request returns 30 entries.
     */
    protected function listGithubEvents($org, $page = 1) {
        try {
            $response = $this->client->getHttpClient()->get('orgs/' . $org . '/events?page=' . $page);
            $events = Github\HttpClient\Message\ResponseMediator::getContent($response);
            return $events;
        } catch(Exception $e) {
            return false;
        }
    }
}
