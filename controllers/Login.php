<?php

class LoginController extends Controller {
    public function run() {
        // is the user already logged in?
        if (getSessionUserId() > 0) {
            $this->view  = null;
            Utils::redirect('./');
        }

        // remember the last submitted username
        $username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';

        // requesting the user to reauthenticate
        if (! empty($_REQUEST['reauth'])) { 
            $msg = 'This transaction is not authorized!';
        }

        $error = new Error();

        if (! empty($_REQUEST['expired'])) {
            $error->setError('Your session has expired. Please log in again.');
        }

        // handle login request
        if($_POST) {
            $username = isset($_REQUEST["username"]) ? trim($_REQUEST["username"]) : "";
            $password = isset($_REQUEST["password"]) ? $_REQUEST["password"] : "";
            if (empty($username)) {
                $error->setError("E-mail cannot be empty.");
            } else if(empty($password)) {
                $error->setError("Password cannot be empty.");
            } else {
                $user = new User();
                if ($user->findUserByUsername($username)) {
                    if ($user->isActive()) {
                        if ($user->authenticate($password)) {
                            self::loginUser($user, $_POST['redir'] ? urldecode($_POST['redir']) : './');
                        } else {
                            $error->setError('Invalid password');
                        }
                    } else {
                        $error->setError('User is deactivated');
                    }
                } else {
                    $error->setError('Oops. That email address or password doesn\'t seem to be working.
                        Need to <a href="./forgot">recover your password</a>?');
                }
            }
        }

        if(isset($_SESSION['username']) and isset($_SESSION['confirm_string']) and $_SESSION['username']!="") {
            $res = mysql_query("select id from ".USERS.
                               " where username = '".mysql_real_escape_string($_SESSION['username'])."' and confirm_string = '".mysql_real_escape_string($_SESSION['confirm_string'])."'");
            if($res && mysql_num_rows($res) > 0) {
                $row=mysql_fetch_array($res);
                if (!empty($_POST['redir'])) {
                     header("Location:".urldecode($_POST['redir']));
                } else {
                       if (!empty($_POST['reauth'])) {
                           header("Location:".urldecode($_POST['reauth']));
                       } else {
                           header("Location: ./jobs");
                       }
            }
                exit;
            }
        }

        // XSS scripting fix
        $redir = strip_tags(!empty($_REQUEST['redir'])?$_REQUEST['redir']:(!empty($_REQUEST['reauth'])?$_REQUEST['reauth']:''));

        $this->write('redir', $redir);
        $this->write('error', $error);
        parent::run();
    }

    public function githubAuth() {
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
                    'redirect_uri' => WORKLIST_URL . 'github',
                    'state' => $_SESSION['github_auth_state'],
                    'code' => $_GET['code']
                ));
                if (isset($response->access_token)) {
                    $_SESSION['github_auth_access_token'] = $response->access_token;
                } else {
                    error_log(print_r($response, true));
                }
            }
        }
         
        if (isset($_SESSION['github_auth_access_token']) && $_SESSION['github_auth_access_token']) {
            $access_token = $_SESSION['github_auth_access_token'];
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
                        $id = $user->getId();
                        $username = $user->getUsername();
                        $nickname = $user->getNickname();
                        $admin = $user->getIs_admin();

                        Utils::setUserSession($id, $username, $nickname, $admin);
                        Utils::redirect('./');
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
        }

        // Start the login process by sending the user to Github's authorization page
        if (isset($_GET['action']) && $_GET['action'] == 'login') {
            // Generate a random hash and store in the session for security
            $_SESSION['github_auth_state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
            unset($_SESSION['github_auth_access_token']);
         
            $params = array(
                'client_id' => GITHUB_OAUTH2_CLIENT_ID,
                'redirect_uri' => WORKLIST_URL . 'github',
                'scope' => 'user',
                'state' => $_SESSION['github_auth_state']
            );
         
            // Redirect the user to Github's authorization page
            $url = $authorizeURL . '?' . http_build_query($params);
            Utils::redirect($url);
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

    public static function loginUser($user, $redirect_url = './') {
        $userObject = User::find($user);
        $id = $userObject->getId();
        $username = $userObject->getUsername();
        $nickname = $userObject->getNickname();
        $admin = $userObject->getIs_admin();
        Utils::setUserSession($id, $username, $nickname, $admin);
        if (is_string($redirect_url)) {
            Utils::redirect($redirect_url);
        }
    }}
