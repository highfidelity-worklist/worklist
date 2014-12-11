<?php

class SettingsController extends Controller {
    public function run() {
        Utils::checkLogin();

        $userId = Session::uid();
        $user = new User();
        if ($userId) {
            $user->findUserById($userId);
        }
        $this->write('user', $user);

        $userSystem = new UserSystemModel();
        $this->write('userSystems', $userSystem->getUserSystemsWithPlaceholder($userId));

        $msg = "";
        $company = "";

        $saveArgs = array();
        $messages = array();
        $errors = 0;
        $error = new Error();
        $settings_link = SECURE_SERVER_URL . "settings";
        $worklist_link = SECURE_SERVER_URL . "jobs";
        $returned_json = array();


        // process updates to user's settings
        if (isset($_POST['save']) && $_POST['save']) {
            $bidding_notif = ($_POST['bidding_notif']);
            if ($bidding_notif != $user->getBidding_notif()) {
                $saveArgs['bidding_notif'] = 1;
            }

            $review_notif = ($_POST['review_notif']);
            if ($review_notif != $user->getReview_notif()) {
                $saveArgs['review_notif'] = 1;
            }

            $self_notif = ($_POST['self_notif']);
            if ($self_notif != $user->getSelf_notif()) {
                $saveArgs['self_notif'] = 1;
            }

            if (isset($_POST['timezone'])) {
                $timezone = mysql_real_escape_string(trim($_POST['timezone']));
                $saveArgs['timezone'] = 0;
            }

            $country = trim($_POST['country']);
            if ($country != $user->getCountry()) {
                $messages[] = "Your country has been updated.";
                $saveArgs['country'] = 1;
            }

            if ($user->getTimezone() != $_POST['timezone']) {
                  $messages[] = "Your timezone has been updated.";
            }


            $about = isset($_POST['about']) ? strip_tags(substr($_POST['about'], 0, 150)) : "";

            if ($about != $user->getAbout()) {
                $saveArgs['about'] = 1;
                $messages[] = "Your personal information (about) has been updated.";
            }

            $userSystem->storeUsersSystemsSettings(
                $userId,
                $_POST['system_id'],
                $_POST['system_operating_systems'],
                $_POST['system_hardware'],
                $_POST['system_delete']
            );

            $paypal = 0;
            $paypal_email = '';
            // defaulting to paypal at this stage
            $payway = 'paypal';
            $paypal = 1;
            $paypal_email = isset($_POST['paypal_email']) ? mysql_real_escape_string($_POST['paypal_email']) : "";

            if ($paypal_email != $user->getPaypal_email()) {
                $saveArgs = array_merge($saveArgs, array('paypal' => 0, 'paypal_email' => 0, 'payway' => 1));
                $messages[] = "Your payment information has been updated.";                
            }

            if (!$user->getW9_accepted() && $user->getCountry() == 'US') {
                $w9_accepted = 'NOW()';
                $saveArgs['w9_accepted'] = 0;
            }

            $paypalPrevious = $user->getPaypal_email();

            // user deleted paypal email, deactivate
            if (empty($paypal_email)) {
                $user->setPaypal_verified(false);
                $user->setPaypal_email('');
                $user->save();
            // user changed paypal address
            } else if ($paypalPrevious != $paypal_email) {
                $paypal_hash = md5(date('r', time()));;
                // generate email
                $subject = "Your payment details have changed";

                $link = SECURE_SERVER_URL . "confirmation?pp=" . $paypal_hash . "&ppstr=" . base64_encode($paypal_email);

                $body  = '<p>Dear ' . $user->getNickname() . ',</p>';
                $body .= '<p>Please confirm your payment email address to activate payments on your account and enable you to start placing bids in the <a href="' . $worklist_link . '">Worklist</a>.</p>';
                $body .= '<p><a href="' . $link . '">Click here to confirm your payment address</a></p>';

                $plain  = 'Dear ' . $user->getNickname() . ',' . "\n\n";
                $plain .= 'Please confirm your payment email address to activate payments on your accounts and enable you to start placing bids in the Worklist.' . "\n\n";
                $plain .= $link . "\n\n";

                $confirm_txt = "An email containing a confirmation link was sent to your payment email address. Please click on that link to verify your payment email address and activate your account.";
                if (! Utils::send_email($paypal_email, $subject, $body, $plain)) {
                    error_log("SettingsController: Utils::send_email failed");
                    $confirm_txt = 'There was an issue sending email. Please try again or notify ' . SUPPORT_EMAIL ;
                }

                $user->setPaypal_verified(false);
                $user->setPaypal_hash($paypal_hash);
                $user->setPaypal_email($paypal_email);
                $user->save();
            }

            // do we have data to update?
            if (!empty($saveArgs)) {

                $sql = "UPDATE `" . USERS . "` SET ";
                foreach ($saveArgs as $arg => $esc) {

                    if ($esc) {
                        $$arg = mysql_real_escape_string(htmlspecialchars($$arg));
                    }

                    if (is_int($$arg) || ($arg == "w9_accepted" && $$arg == 'NOW()')) {
                        $sql .= "`$arg` = " . $$arg . ",";
                    } else {
                        $sql .= "`$arg` = '" . $$arg ."',";
                    }
                }

                $sql = rtrim($sql, ',');
                $sql .= " WHERE id = {$_SESSION['userid']}";
                $res = mysql_query($sql);

                if (!$res) {
                    error_log("Error in saving settings: " . mysql_error() . ':' . $sql);
                    die("Error in saving settings. " );
                }

                // Email user
                if (!empty($messages)) {
                    $to = $_SESSION['username'];
                    $subject = "Settings";
                    $body  = 
                        '<p>Congratulations!</p>' .
                        '<p>You have successfully updated your settings with Worklist: <ul>';
                    foreach ($messages as $msg) {
                        $body .= '<li>'. $msg . '</li>';
                    }
                    $body .= 
                        '</ul>' .
                        '<p><br/>You can view your settings <a href=' . $settings_link . '>here</a></p>' .
                        '<p><a href=' . $worklist_link . '>www.worklist.net</a></p>';

                    if(!Utils::send_email($to, $subject, $body)) { error_log("SettingsController: Utils::send_email failed"); }

                    $msg="Account updated successfully!";
                }


                if (isset($_POST['timezone'])) {
                  $_SESSION['timezone'] = trim($_POST['timezone']);
                }

                if (isset($confirm_txt) && ! empty($confirm_txt)) {
                    echo $confirm_txt;
                    exit;
                }
                $this->view = null;

                // reset session data
                $user->findUserById($userId);
                $id = $user->getId();
                $username = $user->getUsername();
                $nickname = $user->getNickname();
                Utils::setUserSession($user->getId(), $user->getUsername(), $user->getNickname(), $user->getIs_admin());

                $returned_json['user_systems'] = $userSystem->getUserSystemsJSON($userId);

                echo json_encode($returned_json);
                // exit on ajax post - if we experience issues with a blank settings page, need to look at the ajax submit functions
                die;
            }
        }

        // getting userInfo to prepopulate fields
        $userInfo = array();
        $qry = "SELECT * FROM ".USERS." WHERE id='".$_SESSION['userid']."'";
        $rs = mysql_query($qry);
        if ($rs) {
            $userInfo = mysql_fetch_array($rs);
        }

        $userInfo['avatar'] = $user->getAvatar();

        $this->write('userInfo', $userInfo);
        parent::run();
    }
}
