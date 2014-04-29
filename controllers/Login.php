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

}
