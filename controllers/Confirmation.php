<?php

class ConfirmationController extends Controller {
    public function run() {
        $msg = "";
        $to = 1;
        $user = new User();

        if (!empty($_REQUEST['saveW9Names'])) {
            $user_id = isset($_POST['userid']) ? mysql_real_escape_string($_POST['userid']) : "";
            $first_name = isset($_POST['first_name']) ? mysql_real_escape_string($_POST['first_name']) : "";
            $last_name = isset($_POST['last_name']) ? mysql_real_escape_string($_POST['last_name']) : ""; 
            if( !$user->findUserById( (int) $user_id) ) {
                error_log("Failed to load user by ID while saving names for W9");
                exit(0);
            }
            $user->setFirst_name($first_name);
            $user->setLast_name($last_name);
            $user->save();
        }

        if(!empty($_REQUEST['newPayPalEmail']) && !empty($_REQUEST['userId'])) {
            $paypal_hash = md5(date('r', time()));
            $paypal_email = $_REQUEST['newPayPalEmail'];
            
            if (! $user->findUserById((int) $_REQUEST['userId'])) {
                error_log("Failed to load user by ID on paypal email change");
                exit(0);
            }
            
            if ($user->isPaypalVerified()) {
                error_log("Trying to change user " . (int) $_REQUEST['userId'] . " paypal address on ConfirmationController");
                exit(0);
            }
            
            if ($user->getCountry() == 'US') {
                $user->setW9_accepted('NOW()');
            }
            
            $subject = "Your payment account has been set";

            $link = SECURE_SERVER_URL . "confirmation?pp=" . $paypal_hash . "&ppstr=" . base64_encode($paypal_email);
            $worklist_link = SERVER_URL . "jobs";

            $body  = '<p>Dear ' . $user->getNickname() . ',</p>';
            $body .= '<p>Please confirm your payment email address to activate payments on your account and enable you to start placing bids in the 
                    <a href="' . $worklist_link . '">Worklist</a>.</p>';
            $body .= '<p><a href="' . $link . '">Click here to confirm your payment address</a></p>';

            $plain  = 'Dear ' . $user->getNickname() . ',' . "\n\n";
            $plain .= 'Please confirm your payment email address to activate payments on your accounts and enable you 
                    to start placing bids in the Worklist.' . "\n\n";
            $plain .= $link . "\n\n";
                    
            $confirm_txt = "An email containing a confirmation link was sent to your payment email address. 
                    Please click on that link to verify your payment email address and activate your account.";
            if (! send_email($paypal_email, $subject, $body, $plain)) { 
                error_log("ConfirmationController: send_email failed");
                $confirm_txt = "There was an issue sending email. Please try again or notify admin@lovemachineinc.com";
            }

            $user->setPaypal_verified(false);
            $user->setPaypal_hash($paypal_hash);
            $user->setPaypal_email($paypal_email);
            $user->save();
            echo "email sent";
            exit(0);
        }

        if (isset($_REQUEST['str'])) {
            $email = mysql_real_escape_string(base64_decode($_REQUEST['str']));

            // verify the email belongs to a user
            if (! $user->findUserByUsername($email)) {
                $this->view = null;
                Utils::redirect('signup');
            } else {
                $data = array(
                    "username" => base64_decode($_REQUEST['str']),
                    "token" => $_REQUEST['cs']
                );

                $sql = "
                    UPDATE " . USERS . "
                    SET
                        confirm = 1,
                        is_active = 1
                    WHERE username = '" . mysql_real_escape_string(base64_decode($_REQUEST['str'])) . "'";

                mysql_query($sql);
                // send welcome email
                $data = array(
                    'nickname' => $user->getNickname()
                );

                sendTemplateEmail($user->getUsername(), 'welcome', $data, 'Worklist <contact@worklist.net>');
                if (REQUIRELOGINAFTERCONFIRM) {
                    Session::init(); // User must log in AFTER confirming (they're not allowed to before)
                } else {
                    initSessionData($row); //Optionally can login with confirm URL
                }
            }
        } elseif (isset($_REQUEST['ppstr'])) {
            // paypal address confirmation
            $paypal_email = mysql_real_escape_string(base64_decode($_REQUEST['ppstr']));
            $hash = mysql_real_escape_string($_REQUEST['pp']);

            // verify the email belongs to a user
            if (! $user->findUserByPPUsername($paypal_email, $hash)) {
                // hacking attempt, or some other error
                $this->view = null;
                Utils::redirect('login');
            } else {
                $user->setPaypal_verified(true);
                $user->setPaypal_hash('');
                $user->save();
                $this->view = null;
                Utils::redirect('settings?ppconfirmed');
            }
        } elseif (isset($_REQUEST['emstr'])) {
            // new email address confirmation
            $new_email = mysql_real_escape_string(base64_decode($_REQUEST['emstr']));

            if (! $user->findUserByUsername($_SESSION['username'])) {
                $this->view = null;
                Utils::redirect('login'); //we are not logged in
            }
            //save new email
            $user->setUsername($new_email); 
            $user->save();
            $_SESSION['username'] = $new_email; 
            $this->view = null;
            Utils::redirect('settings?emconfirmed');
        }

        $this->write('userCountry', $user->getCountry());
        parent::run();
    }
}