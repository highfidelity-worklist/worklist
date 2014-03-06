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
        $this->write('pullreqs', $this->listRepoPullRequests(GITHUB_REPO));

        $entry = new EntryModel();
        $this->write('entries', $entry->latest(60 * 24 * 30, 50));
        parent::run();
    }

    protected function listRepoPullRequests($repo, $state = 'all') {
        try {
            list($owner, $repo) = explode('/', $repo);
            $pullreqs = $this->client->api('pull_request')->all($owner, $repo, $state);
            return $pullreqs;
        } catch(Exception $e) {
            return false;
        }
    }
}
