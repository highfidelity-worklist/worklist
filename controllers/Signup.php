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
        if (isset($minimal_POST['sign_up'])) {
            $gh_user = null;
            $access_token = '';
            if (isset($_POST['github_auth_link']) && $_SESSION['github_auth_access_token']) {
                $access_token = $_SESSION['github_auth_access_token'];
                $gh_user = $this->apiRequest(GITHUB_API_URL . 'user');
                if (!$gh_user) {
                    $error->setError("Unable to read user credentials from github.");
                }
            }
            if (!$gh_user) {
                if(empty($username) || empty($_REQUEST["nickname"]) || (! empty($_GET['authtype']) && empty($minimal_POST['password'])) || (! empty($_GET['authtype']) && empty($minimal_POST['confirmpassword']))){
                    $error->setError("Please fill all required fields.");
                }
                $about = isset($minimal_POST['about']) ? $minimal_POST['about'] : "";
                if(strlen($about) > 150){
                    $error->setError("Text in field can't be more than 150 characters!");
                }                
            }
            if(! $error->getErrorFlag()) {
                // test nickname
                $testNickname = new User();

                if (!$gh_user) {
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
                } else {
                    $nickname = $gh_user->login;
                    if ($testNickname->findUserByNickname($nickname)) {
                        $nickname = preg_replace('/[^a-zA-Z0-9]/', '', $gh_user->name);
                    }
                    while ($testNickname->findUserByNickname($nickname)) {
                        $rand = mt_rand(1, 99999);
                        $nickname = $gh_user->login . $rand;
                        if ($testNickname->findUserByNickname($nickname)) {
                            $nickname = preg_replace('/[^a-zA-Z0-9]/', '', $gh_user->name) . $rand;
                        }
                    }
                    $newUser['username'] = $minimal_POST['username'];
                    $newUser['nickname'] = $nickname;
                    $newUser['confirm_string'] = uniqid();
                    $newUser['added'] = "NOW()";
                    $newUser['w9_status'] = 'not-applicable';
                }

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

                    if ($gh_user) {
                        if ($github_user = New GitHubUser($user_id)) {
                            $github_user->storeCredentials($access_token);
                        }
                    }

                    // Email user
                    $subject = "Registration";
                    $link = SECURE_SERVER_URL . "confirmation?cs=" . $newUser['confirm_string'] . "&str=" . base64_encode($newUser['username']);
                    $body = 
                        '<p>' . $newUser['nickname'] . ': </p>' .
                        '<p>You are one click away from an account on Worklist:</p>' . 
                        '<p><a href="' . $link . '">Click to verify your email address</a> and activate your account.</p>'.
                        '<p>Welcome aboard, <br /> Worklist / High Fidelity</p>';

                    $plain = 
                        $newUser['nickname'] . "\n\n" .
                        "You are one click away from an account on Worklist: \n\n" .
                        'Click/copy following URL to verify your email address activate your account:' . $link . "\n\n" .
                        "Welcome aboard, \n Worklist / High Fidelity\n";

                    $confirm_txt = 
                        "An email containing a confirmation link was sent to your email address. " . 
                        "Please click on that link to verify your email address and activate your account.";

                    if(!send_email($newUser['username'], $subject, $body, $plain)) {
                        error_log("SignupController: send_email failed");
                        $confirm_txt = 
                            "There was an issue sending email. Please try again or notify admin@lovemachineinc.com";
                    }

                    // paypal email
                    if (! empty($newUser['paypal_email'])) {
                        $paypal_hash = md5(date('r', time()));;

                        $subject = "Payment address verification";
                        $link = SECURE_SERVER_URL . "confirmation?pp=".$paypal_hash . "&ppstr=" . base64_encode($newUser['paypal_email']);
                        $worklist_link = SERVER_URL . "jobs";
                        $body  = "<p>Please confirm your payment email address to activate payments on your account and enable you to start placing bids in the <a href='" . $worklist_link . "'>Worklist</a>.</p>";
                        $body .= '<br/><a href="' . $link . '">Click here to verify your payment address</a></p>';

                        $plain  = 'Please confirm your payment email address to activate payments on your account and enable you to start placing bids in the worklist.' . "\n\n";
                        $plain .= $link . "\n\n";

                        $confirm_txt .= "<br/><br/>An email containing a confirmation link was also sent to your Paypal email address. Please click on that link to verify your Paypal address and activate payments on your account.";
                        if (! send_email($newUser['paypal_email'], $subject, $body, $plain)) {
                            error_log("SignupController: send_email failed");
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
