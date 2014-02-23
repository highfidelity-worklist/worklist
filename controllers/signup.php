<?php

class SignupController extends Controller {
    public function run() {
        $signup = true;
        $country = "";
        $msg = "";
        $to = 1;

        $fields_to_htmlescape = array(
            'paypal_email' => '',
            'contactway' => '',
            'payway' => '',
            'skills' => '',
            'timezone' => '',
            'country' => '',
            'findus' => ''
        );

        $fields_to_not_escape = array(
            'nickname' => '',
            'username' => '',
            'password' => '',
            'confirmpassword' => '',
            'sign_up' => '',
            'confirm' => '',
            'paypal' => '',
            'about' => ''
        );

        // Remove html tags from about box
        if (isset($_POST['about'])) {
            $_POST['about'] = strip_tags($_POST['about']);
        }

        $minimal_POST = @array_intersect_key($_POST, $fields_to_not_escape + $fields_to_htmlescape);

        $country = '';
        $minimal_POST['username'] = $username = isset($_POST['username']) ? strtolower(trim($_POST['username'])) : '';

        $error = new Error();
        if(isset($minimal_POST['sign_up'])) {
            if(empty($username) || empty($_REQUEST["nickname"]) || (! empty($_GET['authtype']) && empty($minimal_POST['password'])) || (! empty($_GET['authtype']) && empty($minimal_POST['confirmpassword']))){
                $error->setError("Please fill all required fields.");
            }
            $about = isset($minimal_POST['about']) ? $minimal_POST['about'] : "";
            if(strlen($about) > 150){
                $error->setError("Text in field can't be more than 150 characters!");
            }

            if(! $error->getErrorFlag()) {
                $send_confirm_email = false;
                $minimal_POST = array_merge($minimal_POST, array_map('htmlspecialchars', array_intersect_key($minimal_POST, $fields_to_htmlescape)));
                unset($minimal_POST['confirmpassword']);
                unset($minimal_POST['sign_up']);
                $review_notify = !empty($_POST['review_notify']) ? Notification::REVIEW_NOTIFICATIONS : 0;
                $bidding_notify = !empty($_POST['bidding_notify']) ? Notification::BIDDING_NOTIFICATIONS : 0;

                if(! isset($minimal_POST['paypal'])){
                    $minimal_POST['paypal_email'] = '';
                }

                foreach ($minimal_POST as $key => $value) {
                    if (! isset($params[$key])) {
                        $params[$key] = $value;
                    }
                }

                $newUser = array();
                foreach($_POST as $key => $value){
                    if(Utils::registerKey($key)){
                        $newUser[$key] = $value;
                    }
                }

                $newUser['username'] = $minimal_POST['username'];
                $newUser['password'] = '{crypt}' . Utils::encryptPassword($minimal_POST['password']);
                $newUser['nickname'] = $minimal_POST['nickname'];
                $newUser['confirm_string'] = uniqid();
                $newUser['added'] = "NOW()";
                $newUser['notifications'] = Notification::setFlags($review_notify, $bidding_notify);
                $newUser['w9_status'] = $_POST['country'] == 'US' ? 'awaiting-receipt' : 'not-applicable';

                // test nickname
                $testNickname = new User();
                if ($testNickname->findUserByNickname($newUser['nickname'])) {
                    $error->setError('Nickname already in use.');
                } else {

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

                    $columns = substr($columns, 0, (strlen($columns) - 1));
                    $columns .= ")";
                    $values = substr($values, 0, (strlen($values) - 1));
                    $values .= ")";
                    $sql .= $columns." ".$values;
                    $res = mysql_query($sql);
                    $user_id = mysql_insert_id();

                    // Email user
                    $subject = "Registration";
                    $link = SECURE_SERVER_URL . "confirmation.php?cs=" . $newUser['confirm_string'] . "&str=" . base64_encode($newUser['username']);
                    $body = "<p>You are only one click away from completing your registration with the Worklist!</p>";
                    $body .= "<p><a href=\"".$link."\">Click here to verify your email address and activate your account.</a></p>";

                    $plain = "You are only one click away from completing your registration!\n\n";
                    $plain .= "Click the link below or copy into your browser's window to verify your email address and activate your account.\n";
                    $plain .= $link."\n\n";
                    $confirm_txt = "An email containing a confirmation link was sent to your email address. Please click on that link to verify your email address and activate your account.";

                    if(!send_email($newUser['username'], $subject, $body, $plain)) {
                        error_log("signup.php: send_email failed");
                        $confirm_txt = "There was an issue sending email. Please try again or notify admin@lovemachineinc.com";
                    }

                    // paypal email
                    if (! empty($newUser['paypal_email'])) {
                        $paypal_hash = md5(date('r', time()));;

                        $subject = "Payment address verification";
                        $link = SECURE_SERVER_URL . "confirmation.php?pp=".$paypal_hash . "&ppstr=" . base64_encode($newUser['paypal_email']);
                        $worklist_link = SERVER_URL . "jobs";
                        $body  = "<p>Please confirm your payment email address to activate payments on your account and enable you to start placing bids in the <a href='" . $worklist_link . "'>Worklist</a>.</p>";
                        $body .= '<br/><a href="' . $link . '">Click here to verify your payment address</a></p>';

                        $plain  = 'Please confirm your payment email address to activate payments on your account and enable you to start placing bids in the worklist.' . "\n\n";
                        $plain .= $link . "\n\n";

                        $confirm_txt .= "<br/><br/>An email containing a confirmation link was also sent to your Paypal email address. Please click on that link to verify your Paypal address and activate payments on your account.";
                        if (! send_email($newUser['paypal_email'], $subject, $body, $plain)) {
                            error_log("signup.php: send_email failed");
                            $confirm_txt = "There was an issue sending email. Please try again or notify admin@lovemachineinc.com";
                        }
                    }
                }
            }
        }

        $this->write('confirm_txt', $confirm_txt);
        $this->write('error', $error);
        $this->write('input', array(
            'username' => (isset($_POST['username']) ? $_POST['username'] : ""),
            'nickname' => (isset($_POST['nickname']) ? $_POST['nickname'] : ""),
            'about' => (isset($_POST['about']) ? $_POST['about'] : ""),
            'findus' => (isset($_POST['findus']) ? strip_tags($_POST['findus']) : ""),
            'contactway' => (isset($_POST['contactway']) ? strip_tags($_POST['contactway']) : ""),
            'skills' => (isset($_POST['skills']) ? strip_tags($_POST['skills']) : ""),
            'country' => (isset($_POST['country']) ? strip_tags($_POST['country']) : ""),
            'city' => (isset($userInfo['city']) ? $userInfo['city'] : (isset($_REQUEST['city']) ? $_REQUEST['city'] : '')),
            'bidding_notify' => (isset($_REQUEST['bidding_notify']) ? $_REQUEST['bidding_notify'] : ''),
            'review_notify' => (isset($_REQUEST['review_notify']) ? $_REQUEST['review_notify'] : '')
        ));
        parent::run();
    }
}
