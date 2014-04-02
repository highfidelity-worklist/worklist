<?php

class ConfirmationController extends Controller {
    public $view = null;

    public function run() {
        $msg = "";
        $to = 1;
        $user = new User();

        if (isset($_REQUEST['str'])) {
            $email = mysql_real_escape_string(base64_decode($_REQUEST['str']));
            $confirm_string = substr($_REQUEST['cs'], 0, 10);

            // verify the email belongs to a user
            if ($user->findUserByUsername($email) && substr($user->getConfirm_string(), 0, 10) == $confirm_string) {
                $sql = "
                    UPDATE " . USERS . "
                    SET
                        confirm = 1,
                        is_active = 1
                    WHERE username = '" . $email . "'";

                mysql_query($sql);

                // send welcome email
                sendTemplateEmail($user->getUsername(), 'welcome', array('nickname' => $user->getNickname()), 'Worklist <contact@worklist.net>');
                if (REQUIRELOGINAFTERCONFIRM) {
                    Session::init(); // User must log in AFTER confirming (they're not allowed to before)
                } else {
                    LoginController::loginUser($user); //Optionally can login with confirm URL
                }
            } else {
                Utils::redirect('./signup');
            }
        } elseif (isset($_REQUEST['ppstr'])) {
            // paypal address confirmation
            $paypal_email = mysql_real_escape_string(base64_decode($_REQUEST['ppstr']));
            $hash = mysql_real_escape_string($_REQUEST['pp']);

            // verify the email belongs to a user
            if (! $user->findUserByPPUsername($paypal_email, $hash)) {
                // hacking attempt, or some other error
                Utils::redirect('./login');
            } else {
                $user->setPaypal_verified(true);
                $user->setPaypal_hash('');
                $user->save();
                Utils::redirect('./settings?ppconfirmed');
            }
        } elseif (isset($_REQUEST['emstr'])) {
            // new email address confirmation
            $new_email = mysql_real_escape_string(base64_decode($_REQUEST['emstr']));

            if (! $user->findUserByUsername($_SESSION['username'])) {
                Utils::redirect('login'); //we are not logged in
            }
            //save new email
            $user->setUsername($new_email); 
            $user->save();
            $_SESSION['username'] = $new_email; 
            Utils::redirect('./settings?emconfirmed');
        }

        Utils::redirect('./settings');
    }
}