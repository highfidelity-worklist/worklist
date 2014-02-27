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
                            $id = $user->getId();
                            $username = $user->getUsername();
                            $nickname = $user->getNickname();
                            $admin = $user->getIs_admin();

                            Utils::setUserSession($id, $username, $nickname, $admin);
                            $this->view  = null;
                            if ($_POST['redir']) {
                                $_SESSION['redirectFromLogin'] = true;
                                Utils::redirect(urldecode($_POST['redir']));
                            } else { 
                                if (!empty($_POST['reauth'])) {
                                    Utils::redirect(urldecode($_POST['reauth']));
                                } else {
                                    Utils::redirect('./');
                                }
                            }

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
                           header("Location: jobs");
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

        $username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';
        $authorizeURL = 'https://github.com/login/oauth/authorize';
        $tokenURL = 'https://github.com/login/oauth/access_token';
        $apiURLBase = 'https://api.github.com/';

        // When Github redirects the user back here, there will be a "code" and "state" parameter in the query string
        if (isset($_GET['code']) && $_GET['code']) {
            // Verify the state matches our stored state
            if (isset($_GET['state']) && $_SESSION['state'] == $_GET['state']) {
                // Exchange the auth code for a token
                $token = $this->apiRequest($tokenURL, array(
                    'client_id' => GITHUB_OAUTH2_CLIENT_ID,
                    'client_secret' => GITHUB_OAUTH2_CLIENT_SECRET,
                    'redirect_uri' => WORKLIST_URL . 'github',
                    'state' => $_SESSION['state'],
                    'code' => $_GET['code']
                ));
                if (isset($token->access_token)) {
                    $_SESSION['access_token'] = $token->access_token;
                } else {
                    error_log(print_r($token, true));
                }
            }
        }
         
        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $gh_user = $this->apiRequest($apiURLBase . 'user');
            //error_log(print_r($gh_user, true));
            if (!isset($gh_user->email)) {
                $username = $gh_user->login . '@github';
            }
            $username = $gh_user->email;
            $user = new User();
            if ($username && $user->findUserByUsername($username)) {
                if ($user->isActive()) {
                    $id = $user->getId();
                    $username = $user->getUsername();
                    $nickname = $user->getNickname();
                    $admin = $user->getIs_admin();
                    Utils::setUserSession($id, $username, $nickname, $admin);
                } else {
                    die('User is deactivated');
                }
            } else {
                $testNickname = new User();
                $nickname = $gh_user->login;
                if ($testNickname->findUserByNickname($nickname)) {
                    $nickname = preg_replace('[^a-zA-Z0-9]', '', $gh_user->name);
                }
                while ($testNickname->findUserByNickname($nickname)) {
                    $rand = mt_rand(1, 99999);
                    $nickname = $gh_user->login . $rand;
                    if ($testNickname->findUserByNickname($nickname)) {
                        $nickname = preg_replace('[^a-zA-Z0-9]', '', $gh_user->name) . $rand;
                    }
                }
                $send_email = true;

                $newUser = array();
                $newUser['username'] = $username;
                $newUser['password'] = '';
                $newUser['nickname'] = $nickname;
                $newUser['added'] = "NOW()";

                $sql = "INSERT INTO ".USERS." ";
                $columns = "(";
                $values = "VALUES (";
                foreach($newUser as $name => $value) {
                    $columns .= "`" . $name . "`,";
                    if ($name == "added") {
                        $values .= "NOW(),";
                    } else {
                        $values .= "'" . mysql_real_escape_string($value) . "',";
                    }
                }

                $columns = substr($columns, 0, (strlen($columns) - 1)) . ")";
                $values = substr($values, 0, (strlen($values) - 1)) . ")";
                $sql .= $columns . " " . $values;
                $res = mysql_query($sql);
                $user_id = mysql_insert_id();

                Utils::setUserSession($user_id, $username, $nickname, false);
            }
            Utils::redirect('./');
        }

        // Start the login process by sending the user to Github's authorization page
        if (isset($_GET['action']) && $_GET['action'] == 'login') {
            // Generate a random hash and store in the session for security
            $_SESSION['state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
            unset($_SESSION['access_token']);
         
            $params = array(
                'client_id' => GITHUB_OAUTH2_CLIENT_ID,
                'redirect_uri' => WORKLIST_URL . 'github',
                'scope' => 'user',
                'state' => $_SESSION['state']
            );
         
            // Redirect the user to Github's authorization page
            $url = $authorizeURL . '?' . http_build_query($params);
            Utils::redirect($url);
        }
         
    }

    private function apiRequest($url, $post=FALSE, $headers=array()) {
        $headers[] = 'Accept: application/json';
        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            $headers[] = 'Authorization: bearer ' . $_SESSION['access_token'];
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
