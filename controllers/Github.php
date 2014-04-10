<?php

require_once('models/DataObject.php');
require_once('models/Budget.php');

class GithubController extends Controller {
    public function run($action) {
        $method = '';
        switch($action) {
            case 'index':
            case 'login':
            case 'connect':
                $method = $action;
                break;
            default:
                $method = 'index';
                break;
        }
        $this->$method();
    }

    public $view = null;

    // default method
    public function index() {
        // This is an array of events that are allowed, if not here we just ignore for now
        $eventHandlers = array(
            'pull_request'
        );

        $eventsInRequest = array();

        if (array_key_exists('payload', $_POST)) {
            // Webhook callbacks contain a POSTed JSON payload, if we have it, process it
            
            // Create object with JSON payload
            $payload = json_decode($_REQUEST['payload']);
            
            foreach ($payload as $key => $value) {
                if (in_array($key, $eventHandlers)) {
                    $eventsInRequest[] = $key;
                }
            }
            
            // I dont think a payload may include multiple events, however, just in case
            // we list the events that we have a handler for, and run each in sequence
            foreach ($eventsInRequest as $key => $value) {
                $GitHubProject = new GitHubProject();
                $GitHubProject->$value($payload);
            }
        }
    }

    public function connect() {
        $GitHub = new GitHubUser(getSessionUserId());
        $workitem = new WorkItem();
        $workitem->loadById((int) $_GET['job']);
        $projectId = $workitem->getProjectId();
        $project = new Project($projectId);
        $connectResponse = $GitHub->processConnectResponse($project->getGithubId(), $project->getGithubSecret());
        if (!$connectResponse['error']) {

            if ($GitHub->storeCredentials($connectResponse['data']['access_token'], $project->getGithubId())) {
                $journal_message = sprintf("%s has been validated for project ##%s##" ,
                    $GitHub->getNickname(),
                    $project->getName());
                sendJournalNotification($journal_message);

                Utils::redirect('./' . $workitem->getId());
            } else {
                // Something went wrong updating the users details, close this window and
                // display a proper error message to the user
                $message = 'Something went wrong and we could not complete the authorization process with GitHub. Please try again.';
            };
        } else {
            // We have an error on the response, close this window and display an error message
            // to the user
            $message = 'We received an error when trying to complete the authorization process with GitHub. Please notify a member of the O-Team for assistance.';
        };
        echo $message;
    }

    public function login() {
        $this->view = null;
        $authorizeURL = GITHUB_AUTHORIZE_URL;
        $tokenURL = GITHUB_TOKEN_URL;
        $apiURLBase = GITHUB_API_URL;

        // When Github redirects the user back here, there will be a "code" and "state" parameter in the query string
        if (isset($_GET['code']) && $_GET['code']) {
            // Verify the state matches our stored state
            if (isset($_GET['state']) && $_SESSION['github_auth_state'] == $_GET['state']) {
                // Exchange the auth code for a token
                $response = $this->apiRequest($tokenURL, array(
                    'client_id' => GITHUB_OAUTH2_CLIENT_ID,
                    'client_secret' => GITHUB_OAUTH2_CLIENT_SECRET,
                    'redirect_uri' => WORKLIST_URL . 'github/login',
                    'state' => $_SESSION['github_auth_state'],
                    'code' => $_GET['code']
                ));
                if (isset($response->access_token) && $response->access_token) {
                    $_SESSION['github_auth_access_token'] = $access_token = $response->access_token;
                    $gh_user = $this->apiRequest($apiURLBase . 'user');
                    //print_r($gh_user); die;
                    if (!$gh_user) {
                        // maybe a wrong access token
                        Utils::redirect('./');
                        die;                
                    }

                    $userId = getSessionUserId();
                    $user = new GitHubUser($userId);
                    $testUser = new GitHubUser($userId);
                    if ($user->getId()) {
                        // user is already logged in in worklist, let's just check if credentials are
                        // already stored and save them in case they're not
                        if (! $user->linkedToAuthToken($access_token) && !$testUser->findUserByAuthToken($access_token)) {
                            // credentials not stored in db and not used by any other user
                            $user->storeCredentials($access_token);
                        }
                        Utils::redirect('./');
                    } else {
                        // user not logged in in worklist, let's check whether he already has a 
                        // github-linked account in worklist
                        if ($user->findUserByAuthToken($access_token)) {
                            // already linked account, let's log him in
                            if ($user->isActive()) {
                                LoginController::loginUser($user);
                            }
                            return;
                        } else {
                            // unknown token, taking to the signup page
                            $this->view = new GithubAuthView();
                            parent::run();
                            return;
                        }
                    }
                    return;
                } else {
                    error_log(print_r($response, true));
                }
            }
        } else { 
            // Start the login process by sending the user to Github's authorization page 
            // Generate a random hash and store in the session for security
            $_SESSION['github_auth_state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
            unset($_SESSION['github_auth_access_token']);
         
            $params = array(
                'client_id' => GITHUB_OAUTH2_CLIENT_ID,
                'redirect_uri' => WORKLIST_URL . 'github/login',
                'scope' => 'user,repo',
                'state' => $_SESSION['github_auth_state']
            );
         
            // Redirect the user to Github's authorization page
            $url = $authorizeURL . '?' . http_build_query($params);
            Utils::redirect($url, false);
        }
         
    }

    private function apiRequest($url, $post=FALSE, $headers=array()) {
        $headers[] = 'Accept: application/json';
        if (isset($_SESSION['github_auth_access_token']) && $_SESSION['github_auth_access_token']) {
            $headers[] = 'Authorization: bearer ' . $_SESSION['github_auth_access_token'];
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, array('User-Agent: Worklist.net')));
        $response = curl_exec($ch);
        return json_decode($response);
    }        
}