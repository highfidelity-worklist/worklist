<?php

require_once('models/DataObject.php');
require_once('models/Budget.php');

class GithubController extends Controller {
    public $view = null;

    public function run($action, $param) {
        $method = '';
        switch($action) {
            case 'login':
            case 'safe':
            case 'logout':
            case 'connect':
            case 'authorize':
            case 'signup':
                $method = $action;
                break;
            default:
                $method = 'index';
                break;
        }
        $this->$method($param);
    }

    /**
     * default controller method, pull requests event handler
     * code moved from the old /GitHub.php
     */
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
                $project = new Project();
                $project->$value($payload);
            }
        }
    }

    public function login($redir = './') {
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
                    'redirect_uri' => WORKLIST_URL . 'github/login/' . $redir,
                    'state' => $_SESSION['github_auth_state'],
                    'code' => $_GET['code']
                ));
                if (isset($response->access_token) && $response->access_token) {
                    $this->access_token = $access_token = $response->access_token;
                    $gh_user = $this->apiRequest($apiURLBase . 'user');
                    if (!$gh_user) {
                        // maybe a wrong access token
                        Utils::redirect('./');
                    }

                    $userId = Session::uid();
                    $user = new User($userId);
                    $testUser = new User();
                    if ($user->getId()) {
                        // user is already logged in in worklist, let's just check if credentials are
                        // already stored and save them in case they're not
                        if (!$testUser->findUserByAuthToken($access_token)) {
                            // credentials not stored in db and not used by any other user
                            $user->storeCredentials($access_token);
                        } else {
                            // credentials found, let's just sync account with GH data
                            $this->sync($user, $gh_user);
                        }
                        Utils::redirect($redir);
                    } else {
                        // user not logged in in worklist, let's check whether he already has a 
                        // github-linked account in worklist
                        if ($user->findUserByAuthToken($access_token)) {
                            // already linked account, let's log him in
                            if ($user->isActive()) {
                                $this->sync($user, $gh_user);
                                User::login($user, $redir);
                            } else {
                                // users that didn't confirmed their email addresses
                                $jobs = new JobController();
                                $jobs->view->jumbotron =
                                    "<h2>E-mail confirmation required!</h2>
                                    <p>
                                      Please check your inbox and follow your e-mail confirmation message
                                      from Worklist. Then try to login again.
                                    </p>
                                    ";
                                $jobs->listView();
                                return;
                            }
                            return;
                        } else {
                            // unknown token, taking to the signup page
                            $this->view = new AuthView();
                            $this->write('access_token', $access_token);
                            $this->write('default_username', isset($gh_user->email) ? $gh_user->email : '');
                            $this->write('default_location', isset($gh_user->location) ? $gh_user->location : '');
                            $this->view->redir_url = $redir;
                            parent::run();
                            return;
                        }
                    }
                    return;
                } else {
                    // probably a refresh on the Auth view, which generated an error
                    // because of the expired verification code, let's save an error just in case
                    error_log(print_r($response, true));
                }
            }
        }
        // let's generate the session state value an try to authorize
        self::generateStateAndLogin($redir);
    }

    public function safe() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->view = new SafeLoginView();
            parent::run();
            return;
        }
        $this->view = null;
        $username = $_POST['username'];
        $password = $_POST['password'];
        $redir = $_POST['redir'];
        $user = new User();
        if ($user->findUserByUsername($username) && $user->authenticate($password)) {
            User::login($user, $redir);
        } else {
            // safe login failed
            Utils::redirect($redir);
        }
    }

    public function logout($redir = './') {
        $this->view = null;
        unset($_SESSION['username']);
        unset($_SESSION['userid']);
        unset($_SESSION['confirm_string']);
        unset($_SESSION['nickname']);
        unset($_SESSION['running']);
        unset($_SESSION['access_token']);
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
        Utils::redirect($redir);
    }

    /**
     * Used on github authorization between projects and users (see github.js)
     * Code moved from the old /GitHub.php file
     */
    public function connect() {
        $GitHub = new User(Session::uid());
        $workitem = new WorkItem();
        $workitem->loadById((int) $_GET['job']);
        $projectId = $workitem->getProjectId();
        $project = new Project($projectId);
        $connectResponse = $GitHub->processConnectResponse($project);
        if (!$connectResponse['error']) {

            if ($GitHub->storeCredentials($connectResponse['data']['access_token'], $project->getGithubId())) {
                $journal_message = sprintf("%s has been validated for project ##%s##" ,
                    $GitHub->getNickname(),
                    $project->getName());
                Utils::systemNotification($journal_message);

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

    /**
     * Post-AuthView process: link github auth to existing users
     */
    public function authorize() {
        $this->view = null;
        $success = false;
        $msg = '';
        try {
            $access_token = isset($_POST["access_token"]) ? trim($_POST["access_token"]) : "";
            $username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
            $password = isset($_POST["password"]) ? $_POST["password"] : "";
            $user = new User();

            if (empty($access_token)) {
                throw new Exception("Access token not provided.");
            } else if (!$user->findUserByUsername($username) || !$user->isActive() || !$user->authenticate($password)) {
                throw new Exception("Invalid credentials.");
            }

            User::login($user, false);
            $testUser = new User();
            if (!$testUser->findUserByAuthToken($access_token)) {
                // github authorization not used by any other user
                $user->storeCredentials($access_token);
            }
            $success = true;
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }
        echo json_encode(array('success' => $success, 'msg' => $msg));
    }

    /**
     * Synchronise data between GitHub and Worklist User
     *
     * @param User $user Worklist User object
     * @param object $gh_user GitHub User JSON object
     */
    public function sync($user, $gh_user) {

        /**
         * Compare User nickname with GitHub login. If they differ,
         * verify the GitHub login does not already exist in Worklist.
         *
         * If it exists, try the GitHub name with spaces removed
         * If it sill exists, append a random number to the login, and
         * to the name, until we get a unique Worklist nickname
         */
        if ($user->getNickname() != $gh_user->login) {
            $nicknameTestUser = new User();
            $nickname = $gh_user->login;
            if ($nicknameTestUser->findUserByNickname($nickname)) {
                $nickname = preg_replace('/[^a-zA-Z0-9]/', '', $gh_user->name);
            }

            while ($nicknameTestUser->findUserByNickname($nickname)) {
                $rand = mt_rand(1, 99999);
                $nickname = $gh_user->login . $rand;
                if ($nicknameTestUser->findUserByNickname($nickname)) {
                    $nickname = preg_replace('/[^a-zA-Z0-9]/', '', $gh_user->name) . $rand;
                }
            }

            $user->setNickname($nickname);
        }

        // save the name to the worklist database
        if (isset($gh_user->name)) {
            $fullname = $gh_user->name;
            $nameArray = explode(' ', $fullname);
            $user->setFirst_name($nameArray[0]);
            $user->setLast_name(end($nameArray));
        }

        $user->setPicture($gh_user->avatar_url);

        $user->save();
    }

    /**
     * Post-AuthView process: create new accounts for new users
     */
    public function signup() {
        global $countrylist;

        $this->view = null;
        $success = false;
        $msg = '';
        try {
            $access_token = isset($_POST["access_token"]) ? trim($_POST["access_token"]) : "";
            $country = isset($_POST["country"]) ? trim($_POST["country"]) : "";
            $username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
            $password = isset($_POST["password"]) ? $_POST["password"] : "";
            $pass2 = isset($_POST["password2"]) ? $_POST["password2"] : "";

            $usernameTestUser = new User();
            $tokenTestUser = new User();
            $usernameTestUser->findUserByUsername($username);
            $tokenTestUser->findUserByAuthToken($access_token);
            if (empty($access_token)) {
                throw new Exception("Access token not provided.");
            } else if (empty($country) || !array_key_exists($country, $countrylist)) {
                throw new Exception("Invalid country." . $country);
            } else if (empty($username) || !filter_var($username, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid username.");
            } else if (empty($password) || $password != $pass2) {
                throw new Exception("Invalid passwords.");
            } else if ($usernameTestUser->getId()) {
                throw new Exception("Username already taken.");
            } else if ($tokenTestUser->getId()) {
                throw new Exception("Access token already in use.");
            }

            $this->access_token = $access_token;
            $gh_user = $this->apiRequest(GITHUB_API_URL . 'user');

            if (!$gh_user) {
                throw new Exception("Unable to read user credentials from github.");
            }

            $nicknameTestUser = new User();
            $nickname = $gh_user->login;
            if ($nicknameTestUser->findUserByNickname($nickname)) {
                $nickname = preg_replace('/[^a-zA-Z0-9]/', '', $gh_user->name);
            }
            while ($nicknameTestUser->findUserByNickname($nickname)) {
                $rand = mt_rand(1, 99999);
                $nickname = $gh_user->login . $rand;
                if ($nicknameTestUser->findUserByNickname($nickname)) {
                    $nickname = preg_replace('/[^a-zA-Z0-9]/', '', $gh_user->name) . $rand;
                }
            }

            $user = User::signup($username, $nickname, $password, $access_token, $country);
            $success = true;

            $this->sync($user, $gh_user);

            // Email user
            $subject = "Registration";
            $link = SECURE_SERVER_URL . "confirmation?cs=" . $user->getConfirm_string() . "&str=" . base64_encode($user->getUsername());
            $body = 
                '<p>' . $user->getNickname() . ': </p>' .
                '<p>You are one click away from an account on Worklist:</p>' . 
                '<p><a href="' . $link . '">Click to verify your email address</a> and activate your account.</p>'.
                '<p>Welcome aboard, <br /> Worklist / High Fidelity</p>';

            $plain = 
                $user->getNickname() . "\n\n" .
                "You are one click away from an account on Worklist: \n\n" .
                'Click/copy following URL to verify your email address activate your account:' . $link . "\n\n" .
                "Welcome aboard, \n Worklist / High Fidelity\n";

            $msg = 
                "An email containing a confirmation link was sent to your email address. " . 
                "Please click on that link to verify your email address and activate your account.";

            if (!Utils::send_email($user->getUsername(), $subject, $body, $plain)) {
                error_log("SignupController: Utils::send_email failed");
                $msg = 'There was an issue sending email. Please try again or notify admin@lovemachineinc.com';
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }
        echo json_encode(array('success' => $success, 'msg' => $msg));
    }

    public function federated() {
        $this->view = new FederatedView(); 
    }

    /**
     * Start the login process by sending the user to Github's authorization page 
     */
    public static function generateStateAndLogin($redir) {
        // Generate a random hash and store in the session for security
        $_SESSION['github_auth_state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);     
        $params = array(
            'client_id' => GITHUB_OAUTH2_CLIENT_ID,
            'redirect_uri' => WORKLIST_URL . 'github/login/' . $redir,
            'scope' => 'public_repo',
            'state' => $_SESSION['github_auth_state']
        );
     
        // Redirect the user to Github's authorization page
        $url = GITHUB_AUTHORIZE_URL . '?' . http_build_query($params);
        Utils::redirect($url, false);
    }

    private function apiRequest($url, $post=FALSE, $headers=array()) {
        $headers[] = 'Accept: application/json';
        if (isset($this->access_token) && $this->access_token) {
            $headers[] = 'Authorization: bearer ' . $this->access_token;
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