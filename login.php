<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */


require_once('config.php');

Session::check();

// is the user already logged in? go to worklist.php
if (getSessionUserId() > 0) {
    Utils::redirect('worklist.php');
}

// remember the last submitted username
$username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';

// requesting the user to reauthenticate
if (! empty($_REQUEST['reauth'])) { 
    $msg = 'This transaction is not authorized!';
}

$error = new Error();

if (! empty($_REQUEST['expired'])) {
    $error->setError('Your session has expired. Please log in again.');
}

// handle login request
if($_POST) {

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
            $error->setError('Oops. That email address or password doesn\'t seem to be working.
                Need to <a href="forgot.php">recover your password</a>?');
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
    <link href="css/worklist.css" rel="stylesheet" type="text/css" />
    <link href="css/login.css" rel="stylesheet" type="text/css" />
    <title>Welcome to the Worklist</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<?php require_once('header.php'); ?>
    <section id="login">
        <h1>Login to Worklist</h1>
        <?php if($error->getErrorFlag()): ?>
            <div class="errors">
            <?php foreach($error->getErrorMessage() as $msg): ?>
                <p class="error"><?php echo $msg; ?></p>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form id="form-login" action="" method="post">

            <input type="hidden" name="redir" value="<?php echo $redir; ?>" />
            <label for="username">Email</label>
            <input type="text" id="form[username]" name="username" class="text-field" size="40" />

            <div class="clear"></div>

            <label for="password">Password</label>
            <input id="form[password]" type="password" name="password" class="text-field" />
            <div class="clear"></div>

            <p class="actions">
                <input type="submit" id="Login" name="Login" value="Login" />
                or <a href="forgot.php" title="Reset your password">recover password</a>
            </p>
        </form>
        <div class="clear"></div>
    </section>

    <section id="signup">
        <p>New to the Worklist? <a href="signup.php" title="Sign up to worklist">Sign up now.</a></p>
    </section>



<?php include("footer.php"); ?>
