<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

require_once ("config.php");

Session::check();

$signup = true;
$phone = $country = $provider = $authtype = "";
$msg = "";
$to = 1;

$fields_to_htmlescape = array(
    'paypal_email' => '',
    'contactway' => '',
    'payway' => '',
    'skills' => '',
    'timezone' => '',
    'int_code' => '',
    'phone' => '',
    'country' => '',
    'provider' => '',
    'smsaddr' => '',
    'findus' => ''
);

$fields_to_not_escape = array(
    'nickname' => '',
    'username' => '',
    'password' => '',
    'confirmpassword' => '',
    'sign_up' => '',
    'phone_edit' => '',
    'confirm' => '',
    'paypal' => '',
    'about' => '',
    'openid' => ''
);

// Remove html tags from about box
if (isset($_POST['about'])) {
    $_POST['about'] = strip_tags($_POST['about']);
}

$minimal_POST = @array_intersect_key($_POST, $fields_to_not_escape + $fields_to_htmlescape);

// TODO: Code repeated from settings.php. Must be put in a library
// compute smsaddr from phone and provider
//error_log("signup: ".$minimal_POST['country'].';'.$smslist[$minimal_POST['country']][$minimal_POST['provider']]) ;
$prov_address = (isset($minimal_POST['country']) && isset($smslist[$minimal_POST['country']][$minimal_POST['provider']])) ? $smslist[$minimal_POST['country']][$minimal_POST['provider']] : '';
$country = '';
$provider = '';
$int_code = '';
$phone = isset($minimal_POST['phone']) ? preg_replace('/\D/', '', $minimal_POST['phone']) : '';
$sms_flags = 0;
$minimal_POST['smsaddr'] = str_replace('{n}', $phone, $prov_address);

$minimal_POST['username'] = $username = isset($_POST['username']) ? strtolower(trim($_POST['username'])) : '';

if(isset($minimal_POST['sign_up'])) {
    $error = new Error();
    if(empty($username) || empty($_REQUEST["nickname"]) || (! empty($_GET['authtype']) && ($_GET['authtype'] != 'openid') && empty($minimal_POST['password'])) || (! empty($_GET['authtype']) && ($_GET['authtype'] != 'openid') && empty($minimal_POST['confirmpassword']))){
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
        unset($minimal_POST['phone_edit']);
        unset($minimal_POST['sign_up']);
        $minimal_POST['sms_flags'] = (! empty($_POST['journal_alerts']) ? SMS_FLAG_JOURNAL_ALERTS : 0) | (! empty($_POST['bid_alerts']) ? SMS_FLAG_BID_ALERTS : 0);
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

// if we have openid authentication there are a few prefilled values
//Add test for GET:authtype to reduce warnings
if (!empty($_GET['authtype']) && $_GET['authtype'] == 'openid') {
  $_POST['nickname'] = rawurldecode($_GET['nickname']);
  $_POST['username'] = rawurldecode($_GET['email']);
  $country = rawurldecode($_GET['country']);
  $_POST['timezone'] = rawurldecode($_GET['timezone']);
  $authtype = 'openid';
}

/*********************************** HTML layout begins here  *************************************/

include("head.php");
include("opengraphmeta.php");
?>

<!-- Add page-specific scripts and styles here, see head.php for global scripts and styles  -->

<link href="css/worklist.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/sendlove.js"></script>
<script type="text/javascript" src="js/skills.js"></script>
<script type="text/javascript" src="js/userSkills.js"></script>

<title>Worklist | Sign Up to the Worklist</title>
</head>

<?php if(isset($error)){?>
<?php if($error->getErrorFlag() == 1){?>
<body>
<?php } else {?>
<body onload="openbox('Signup Confirmation', 1)">
<?php }?>
<?php } else {?>
<body>
<?php }?>

<?php include("format_signup.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<!-- Light Box Code Start -->
<div id="filter" onClick="closebox()"></div>
<div id="box">
<p align="center">Email Confirmation</p>
<p><font style="color: #624100; font-size: 12px; font-family: Verdana;"><?php echo isset($confirm_txt)?$confirm_txt:'' ?></font></p>
<p>&nbsp;</p>
<p align="center"><strong><a href="#" onClick="closebox()">Close</a></strong></p>
</div>
<!-- Light Box Code End -->

<h1>Create a New Account</h1>
<p>
    <i>Sign up for Worklist to make bids on jobs or create new projects.</i>
</p>
     <?php if(isset($error) && $error->getErrorFlag() == 1): ?>
            <?php foreach($error->getErrorMessage() as $msg):?>
              <p class="LV_invalid"><?php echo $msg; ?></p>
            <?php endforeach;?>
        <?php endif; ?>
        <form action="" name="signup" id="signupForm" method="post">
        <?php echo(($authtype === 'openid') ? '<input type="hidden" name="openid" value="' . rawurldecode($_GET['id']) . '" />' : '');?>
     <!-- Column containing left part of the fields -->
<div class="left-col">
<div class="LVspace">
    <p>
        <label for="username">Email </label>
        <br />
        <span class="required-bullet">*</span>
        <input type="text" id="username" name="username" class="text-field" size="35" value="<?php echo isset($_POST['username']) ? $_POST['username'] : ""; ?>" />
    </p>
</div>
<script type="text/javascript">
    var username = new LiveValidation('username', {validMessage: "Valid email address."});
    username.add( Validate.Email );
    username.add(Validate.Length, { minimum: 4, maximum: 50 } );
</script>
            <?php if (empty($_GET['authtype']) || $_GET['authtype'] != 'openid' ) :?>
<div class="LVspace">
    <p>
        <label for="password">Password </label>
        <br />
        <span class="required-bullet">*</span>
        <input type="password" id="password" name="password" class="text-field" size="35" />
    </p>
</div>
<script type="text/javascript">
    var password = new LiveValidation('password',{ validMessage: "You have an OK password." });
    password.add(Validate.Length, { minimum: 5, maximum: 255 } );
</script>

<div class="LVspace">
    <p>
        <label>Confirm Password</label> <br />
        <span class="required-bullet">*</span>
        <input name="confirmpassword" id="confirmpassword" type="password" class="text-field" size="35" />
    </p>
</div>
<script type="text/javascript">
    var confirmpassword = new LiveValidation('confirmpassword', {validMessage: "Passwords Match."});
    confirmpassword.add(Validate.Custom1, { match: 'password'} );
</script>
      <?php endif; ?>
<div class="LVspace">
    <p>
        <label for="nickname">Nickname </label>
        <br />
        <span class="required-bullet">*</span>
        <input type="text" id="nickname" name="nickname" class="text-field" size="35" value="<?php echo isset($_POST['nickname']) ? $_POST['nickname'] : ""; ?>" />
    </p>
</div>
<?php
    $smsaddr=!empty($_REQUEST['smsaddr'])? $_REQUEST['smsaddr']:'';
    $country=!empty($_REQUEST['country'])? $_REQUEST['country']:'';
    $phone=!empty($_REQUEST['phone'])? $_REQUEST['phone']:'';
?>
<div id="sms-country">
    <p><label>Country<br />
    <span class="required-bullet">*</span> <select id="country" name="country" style="width:274px">
        <?php
        if (empty($country) || $country == '--') {
            //$selected not set by this point, we want to default so do that
            echo '<option value="US">United States -- Default</option> <option disabled="disabled">-----------</option>';
        }
        foreach ($countrylist as $code=>$cname) {
            $selected = ($country == $code) ? "selected=\"selected\"" : "";
            echo '<option value="'.$code.'" '.$selected.'>'.$cname.'</option>';
        }
        ?>
    </select>
    </label><br/>
    </p>
</div>
<div id="cityDiv">
    <p><label for="City">City</label><br />
    <span class="required-bullet">*</span> <input type="text" id="city" name="city" class="text-field"
        size="35"
        value="<?php echo isset($userInfo['city']) ? $userInfo['city'] : (isset($_REQUEST['city'])?$_REQUEST['city']:''); ?>" />
    </p>
</div>

<div class="LVspace height50">
    <p>
        <label for="timezone">What timezone are you in?</label><br />
           <span class="required-bullet"> &nbsp;</span>
        <select id="timezone" name="timezone">
            <?php
                foreach ($timezoneTable as $key => $value) {
                    echo '<option value = "'.$key.'">'.$value.'</option>';
                }
            ?>
        </select>
    </p>
</div>

</div>
<!-- end of left-col div -->
<div class="right-col">
<div class="LVspacehg">
<p><label for="about">What do we need to know about you?</label><br />
<textarea id="about" name="about" cols="35" rows="4"><?php echo isset($_POST['about']) ? $_POST['about'] : ""; ?></textarea>
</p>
</div>
<script type="text/javascript">
        var about = new LiveValidation('about');
        about.add(Validate.Length, { minimum: 0, maximum: 150 } );
</script>
<div class="LVspace">
<p><label for="findus">How did you find us?</label><br />
<input type="text" id="findus" name="findus" class="text-field"
    size="35"
    value="<?php echo isset($_POST['findus']) ? strip_tags($_POST['findus']) : ""; ?>" />
</p>
</div>
<div class="LVspace">
<p><label for="contactway">What is the preferred way to contact you?</label><br />
<input type="text" id="contactway" name="contactway" class="text-field"
    size="35"
    value="<?php echo isset($_POST['contactway']) ? strip_tags($_POST['contactway']) : ""; ?>" />
</p>
</div>

<div class="LVspace">
    <p>
        <label for="skills">Pick three skills you think are your strongest</label><br />
        <input type="text" id="skills" name="skills" class="text-field" size="35" value="<?php echo isset($_POST['skills']) ? strip_tags($_POST['skills']) : ""; ?>" />
    </p>
</div>

<?php
    include("sms-inc.php");
?>
    <input type="checkbox" name="bidding_notify" id="bidding_notify" <?php echo !empty($_REQUEST['bidding_notify'])? ' checked="checked" ':''; ?> />Notify me when a new job is set to bidding<br />
    <input type="checkbox" name="review_notify" id="review_notify" <?php echo !empty($_REQUEST['review_notify'])? ' checked="checked" ':''; ?> />Notify me when any job is set to review<br /><br />

</div>
<div class="signupContainer">
    <p><input type="submit" value="Sign Up" alt="Sign Up" name="sign_up" /></p>
</div>
</form>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
