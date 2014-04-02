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
                LoginController::loginUser($user, false); //Optionally can login with confirm URL
                $jobs = new JobsController();
                $jobs->view->jumbotron = 
                    "
                        <h2>Welcome to the Worklist!</h2>
                        <p>
                            You can now browse through our open jobs and look for items that you'd like to work on for us!
                        </p>
                        <p>
                            Before you can bid, you'll need to update your settings page with your payment details.  
                            In addition, US Developers must upload a W9.  To navigate to your settings page, click 
                            on your name in the upper right hand corner of the jobs page and choose settings.
                        </p>
                        <p>
                            You can also chat with us and ask questions in our <a href=https://gitter.im/highfidelity/worklist>Gitter room.</a>.
                        </p>
                        <p>
                            Happy Bidding and hope to see you commiting code soon!
                        </p>                         
                    ";
                $jobs->run();
                return;
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