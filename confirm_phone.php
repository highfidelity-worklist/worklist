<?php

//  vim:ts=4:et

//  Copyright (c) 2012, Coffee & Power, Inc.
//  All Rights Reserved.
//  http://www.coffeeandpower.com

ob_start();
require_once ("config.php");
require_once ("class.session_handler.php");
require_once ("functions.php");

$phone = isset($_REQUEST['phone']) ? trim($_REQUEST['phone']) : '';
$phoneconfirmstr = isset($_REQUEST['phoneconfirmstr']) ? trim($_REQUEST['phoneconfirmstr']) : '';
$userid = isset($_REQUEST['user']) ? trim($_REQUEST['user']) : '';
$password = $_POST ? $_REQUEST['password'] : '';
$clean_phone = isset($_POST['clean_phone']) && $_POST['clean_phone']; 

// not enough data, redirect to worklist
if (empty($phone) || (empty($userid) && empty($_SESSION['userid']))) {
    Utils::redirect('worklist.php');
}

// if logged in as other user then redirect to worklist
if (!empty($userid) && !empty($_SESSION['userid']) && $userid != $_SESSION['userid']) {
    Utils::redirect('worklist.php');
}

if (empty($userid)) {
    $userid = $_SESSION['userid'];
}

$user = new User();
$validated = false;
$cleaned = false;
if ($user->findUserById($userid) && $user->isActive()) {
    if ((! empty($_SESSION['userid']) && $_SESSION['userid'] == $userid) || $user->authenticate($password)) {
        $user_phone_verified = $user->getPhone_verified();
        $user_phone = $user->getPhone();
        $user_phone_confirm_string = $user->getPhone_confirm_string();
        if ($phone == $user_phone && substr($user_phone_verified, 0, 10) == '0000-00-00') {
            $error = new Error();
            if ($phoneconfirmstr && ($phoneconfirmstr == $user_phone_confirm_string) || $clean_phone) {
                if ($phoneconfirmstr && $phoneconfirmstr == $user_phone_confirm_string) {
                    // phone validated, save and redirect
                    $user->setPhone_verified(date('Y-m-d H:i'))
                         ->setPhone_confirm_string('')
                         ->save();
                    $validated = true;
                } else if ($clean_phone) {
                    $user->setPhone_verified('0000-00-00 00:00')
                         ->setPhone_confirm_string('')
                         ->setPhone('')
                         ->save();
                    $cleaned = true;
                }
                
                $username = $user->getUsername();
                $nickname = $user->getNickname();
                $admin = $user->getIs_admin();
                
                Utils::setUserSession($userid, $username, $nickname, $admin);
                
                if ($_POST['redir']) {
                    $_SESSION['redirectFromLogin'] = true;
                    $redir = urldecode($_POST['redir']);
                } else {
                    $redir = SERVER_URL;
                }
            } else {
                if (isset($_REQUEST['phoneconfirmstr'])) {
                    $error->setError("Wrong confirm string. Please try again.");
                }
            }
        } else {
            $error->setError('Invalid operation.');
        }
    } else {
        $error->setError('Authentication failed.');
    }
    
} else {
    die('user id not valid');
}

// XSS scripting fix
if (!isset($redir)) {
    $redir = strip_tags(! empty($_REQUEST['redir']) ? $_REQUEST['redir'] : '');
}


/*********************************** HTML layout begins here  *************************************/
include("head.html");
?>
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css">

<!-- jquery file is for LiveValidation -->
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
<script type="text/javascript">
$(function() {
    $('#clean_phone').change(function() {
        if ($(this).is(':checked')) {
            if (confirm('Are you sure you want to clean your phone settings?')) {
                $('#phoneconfirmstr').val('');
                $('#phone-confirm').submit();
            } else {
                $(this).attr('checked', false);
            }
        }
    });
});
</script>
<title>Worklist - Confirm your phone number</title>
</head>

<body>
<?php include("format_signup.php"); ?>
    <br>
    <h1>Phone number confirmation</h1>
    
    <?php if (empty($_SESSION['userid'])) { ?>
	    <div id="in-rt">
	        <h3 style="margin-bottom:0;">New to the Worklist?</h3>
	        <p><a href="signup.php">Sign up now!</a></p>
	                        
	        <h3 style="margin-bottom:0;">Forget your password?</h3>
	        <p><a href="forgot.php">Recover it here</a></p>
	    </div>
    <?php } ?>
    
    <div id="in-lt">
    <?php if(! ($validated || $cleaned)) { ?>     
        <?php if(isset($error)){ ?>
            <?php foreach($error->getErrorMessage() as $msg){ ?>
              <p class="LV_invalid"><?php echo $msg; ?></p>
            <?php } ?>
        <?php } ?>

      <div>
        <div id="phone-confirm-form" class="worklist">
        <div id="phoneConfirmFormHolder">
            <form id="phone-confirm" action="" method="post">
            <input type="hidden" name="redir" value="<?php echo $redir ?>" />
            <div class="LVspace">
                <label>E-mail: <br />
                <input type="text" id="username" name="username" class="text-field" size="40" disabled="disabled" value="<?php echo $user->getUsername(); ?>"/>
                </label>
            </div>

            <div class="LVspace">
                <label>Phone: <br />
                <input type="text" id="phone" name="phone" class="text-field" size="40" disabled="disabled" value="<?php echo $user->getPhone(); ?>"/>
                </label>
            </div>

            <div class="LVspace">
                <label>Code: <br />
                <input type="text" id="phoneconfirmstr" name="phoneconfirmstr" class="text-field" size="40" />
                </label>
            </div>

            <?php if (empty($_SESSION['userid'])) { ?>
            <div class="LVspace">
                <label>Password<br />
                <input type="password" id="password" name="password" class="text-field" size="40"  />
                </label>
            </div>
            <?php } ?>

            <p>
                <input type="submit" id="Confirm" value="Confirm" name="Confirm" class="text-field" alt="Confirm phone number">
                - OR -
                <span class="clean_phone">
                  <label for="clean_phone">clean phone settings and don't ask me again</label>
                  <input id="clean_phone" type="checkbox" name="clean_phone" />
                </span>
            </p>
            </form>
        </div>
        </div>
      </div>
    <?php }?>
    <?php if ($validated) { ?>
      <p>Thank you!, your phone number has just been validated and is now able to receive SMS.</p>
      <p>You're now being redirected...</p>
      <?php if (isset($redir) && ! empty($redir)) { ?>
        <script type="text/javascript">
        setTimeout(function() { window.location.href = '<?php echo $redir; ?>'; }, 5000);
        </script>
      <?php }?>
    <?php } else if ($cleaned) { ?>
      <p>
          Your phone number has just been cleaned so now you wont be able to receive SMS.
          If you wish to receive sms please update your settings.
      </p>
      <p>You're now being redirected...</p>
      <?php if (isset($redir) && ! empty($redir)) { ?>
        <script type="text/javascript">
          setTimeout(function() { window.location.href = '<?php echo $redir; ?>'; }, 5000);
        </script>
      <?php }?>
    <?php } ?>
    </div>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
