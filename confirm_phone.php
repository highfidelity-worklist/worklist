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

if ($user->findUserById($userid) && $user->isActive()) {
    if (!empty($phoneconfirmstr)) {
        if ((! empty($_SESSION['userid']) && $_SESSION['userid'] == $userid) 
          || $user->authenticate($password)) 
        {
            $user_phone_verified = $user->getPhone_verified();
            $user_phone = $user->getPhone();
            $user_phone_confirm_string = $user->getPhone_confirm_string();
            
            if ($phone == $user_phone 
              && substr($user_phone_verified, 0, 10) == '0000-00-00' 
              && $phoneconfirmstr == $user_phone_confirm_string)
            {
                $user->setPhone_verified(date('Y-m-d H:i'))
                     ->setPhone_confirm_string('');
                  
                $user->save();
                
                $username = $user->getUsername();
                $nickname = $user->getNickname();
                $admin = $user->getIs_admin();
                
                Utils::setUserSession($userid, $username, $nickname, $admin);
                
                if ($_POST['redir']) {
                    $_SESSION['redirectFromLogin'] = true;
                    Utils::redirect(urldecode($_POST['redir']));
                } else {
                    Utils::redirect('worklist.php');
                }
            } else {
                die('Invalid operation');
            }
        } else {
            die('Authentication failed');
        }
    }
    
} else {
    die('user id not valid');
}

// XSS scripting fix
$redir = strip_tags(! empty($_REQUEST['redir']) ? $_REQUEST['redir'] : '');


/*********************************** HTML layout begins here  *************************************/
include("head.html");
?>
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css">

<!-- jquery file is for LiveValidation -->
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>
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

            <p><input type="submit" id="Confirm" value="Confirm" name="Confirm" class="text-field" alt="Confirm phone number"></p>
            </form>
        </div>
        </div>
    </div>
    </div>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
