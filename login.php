<?php
//  vim:ts=4:et

//  Copyright (c) 2012, Coffee & Power, Inc.
//  All Rights Reserved.
//  http://www.coffeeandpower.com

ob_start();
require_once ("config.php");
require_once("class.session_handler.php");
require_once ("functions.php");

// is the user already logged in? go to worklist.php
if (! empty($_SESSION['userid'])) {
    Utils::redirect('worklist.php');
}

$username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';

if (!empty($_REQUEST['reauth'])) { 
    $msg = 'This transaction is not authorized!';
}

$expired = !empty($_REQUEST['expired']) ? 1 : 0;

if($_POST) {
    $error = new Error();
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

                    print_r($user);
                    echo "<br/>";
                    $id = $user->getId();
                    $username = $user->getUsername();
                    $nickname = $user->getNickname();
                    $admin = $user->getIs_admin();

                    Utils::setUserSession($id, $username, $nickname, $admin);
                    

	                if ($_POST['redir']) {
	                    $_SESSION['redirectFromLogin'] = true;
	                    Utils::redirect(urldecode($_POST['redir']));
	                } else { 
	                    if (!empty($_POST['reauth'])) {
	                        Utils::redirect(urldecode($_POST['reauth']));
	                    } else {
	                        Utils::redirect('worklist.php');
	                    }
	                }
                    

                } else {
                    $error->setError('Invalid password');
                }
            } else {
                $error->setError('User is deactivated');
            }
        } else {
            $error->setError('Email Address or Password not valid');
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
                   header("Location:worklist.php");
               }
    }
        exit;
    }
}

// XSS scripting fix
$redir = strip_tags(!empty($_REQUEST['redir'])?$_REQUEST['redir']:(!empty($_REQUEST['reauth'])?$_REQUEST['reauth']:''));

/*********************************** HTML layout begins here  *************************************/

include("head.html");
?>


<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css">

<!-- jquery file is for LiveValidation -->
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>

<title>Welcome to the Worklist</title>

</head>

<body>

<?php include("format_signup.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->
    <br>     


    <h1>Login to the Worklist</h1>
                       
    <?php if ($expired) { ?>
    <p class="error">Your session has expired.  Please log in again.</p>
    <?php } ?>

    <div id="in-rt">
        <h3 style="margin-bottom:0;">New to the Worklist?</h3>
        <p><a href="signup.php">Sign up now!</a></p>
                        
        <h3 style="margin-bottom:0;">Forget your password?</h3>
        <p><a href="forgot.php">Recover it here</a></p>
    </div> 


    <div id="in-lt">
        <?php if(isset($error)){ ?>
            <?php foreach($error->getErrorMessage() as $msg){ ?>
              <p class="LV_invalid"><?php echo $msg; ?></p>
            <?php } ?>
        <?php } ?>

	<div class="login_left">

	    <div id="login-form" class="worklist">
		<div id="loginFormHolder">
		    <form id="login" action="" method="post">
			<input type="hidden" name="redir" value="<?php echo $redir ?>" />
			<div class="LVspace">
			    <label>E-mail<br />
				<input type="text" id="username" name="username" class="text-field" size="40" />
			    </label>
			    <script type="text/javascript">
				var username = new LiveValidation('username',{ validMessage: "Valid email address.", onlyOnBlur: false });
				    username.add(SLEmail);
				    username.add(Validate.Length, { minimum: 10, maximum: 50 } );
			    </script>
			</div>
			<div class="LVspace"><label>Password<br />
			    <input type="password" id="password" name="password" class="text-field" size="40"  />
			</label></div>
			<p><input type="submit" id="Login" value="Login" name="Login" alt="Login"></p>
		    </form>
		</div>
	    </div>
	</div>
    </div>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
