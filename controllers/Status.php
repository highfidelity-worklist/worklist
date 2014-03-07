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
        $this->write('gh_events', $this->listGithubEvents(GITHUB_ORGANIZATION));

        $entry = new EntryModel();
        $this->write('entries', $entry->latest(60 * 24 * 30, 50));
        parent::run();
    }

    protected function listGithubEvents($org) {
        try {
            $response = $this->client->getHttpClient()->get('orgs/' . $org . '/events');
            $events = Github\HttpClient\Message\ResponseMediator::getContent($response);
            return $events;
        } catch(Exception $e) {
            return false;
        }
    }
}
