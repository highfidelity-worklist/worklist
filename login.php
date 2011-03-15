<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//
ob_start();
error_log("trace 1");
include("config.php");
error_log("trace 2");
include("class.session_handler.php");
error_log("trace 3");

if (!empty($_SESSION['userid'])) {
     header("Location:worklist.php");
     exit;
}
error_log("trace 4");
require_once("functions.php");
error_log("trace 5");
require_once('class/Utils.class.php');
error_log("trace 6");

// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
error_log("trace 7");
// Database Selection String
mysql_select_db(DB_NAME);
error_log("trace 8");

$username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';
if (!empty($_REQUEST['reauth'])) { $msg = 'This transaction is not authorized!'; }
$expired = !empty($_REQUEST['expired']) ? 1 : 0;
error_log("trace 9");

if($_POST) {
error_log("trace 10");
        require_once ("class/Error.class.php");
error_log("trace 11");
        require_once ("class/Login.class.php");
error_log("trace 12");
        require_once ("class/Response.class.php");
error_log("trace 13");
        $error = new Error();
        $username = isset($_REQUEST["username"]) ? trim($_REQUEST["username"]) : "";
        $password = isset($_REQUEST["password"]) ? $_REQUEST["password"] : "";
        if(empty($username)){
	    $error->setError("E-mail cannot be empty.");
        }else if(empty($password)){
            $error->setError("Password cannot be empty.");
        }else{
error_log("trace 14");
            $params = array("username" => $username, "password" => $password, "action" => "login");
            ob_start();
error_log("trace 15".SERVER_URL);
            // send the request
            CURLHandler::Post(SERVER_URL . 'loginApi.php', $params, false, true);
            $result = ob_get_contents();
error_log("trace 16");
            ob_end_clean();
            $ret = json_decode($result);
error_log("trace 17");
            if($ret->error == 1){
                $error->setError($ret->message);
            }else{
                $id = $ret->userid;
                $username = $ret->username;
                $nickname = $ret->nickname;
                $admin = $ret->admin; 
error_log("trace 18");
                Utils::setUserSession($id, $username, $nickname, $admin);
error_log("trace 19");
                // notifying other applications
                $response = new Response();
error_log("trace 20");
                $login = new Login();
error_log("trace 21");
                $login->setResponse($response);
error_log("trace 22");
                $login->notify($id, session_id());
error_log("trace 23");
                if ($_POST['redir']) {
error_log("trace 24");
                    header("Location:".urldecode($_POST['redir']));
                } else { 
error_log("trace 25");
                      if (!empty($_POST['reauth'])) {
                          header("Location:".urldecode($_POST['reauth']));
                      } else {
                          header("Location:worklist.php");
                      }
                }
                exit;
            }
        }
}
include('openid.php');

error_log("trace 26");
if(isset($_SESSION['username']) and isset($_SESSION['confirm_string']) and $_SESSION['username']!="") {
error_log("trace 27");
    $res = mysql_query("select id from ".USERS.
                       " where username = '".mysql_real_escape_string($_SESSION['username'])."' and confirm_string = '".mysql_real_escape_string($_SESSION['confirm_string'])."'");
error_log("trace 28");
    if($res && mysql_num_rows($res) > 0) {
error_log("trace 29");
        $row=mysql_fetch_array($res);
error_log("trace 30");
        if (!empty($_POST['redir'])) {
             header("Location:".urldecode($_POST['redir']));
        } else {
error_log("trace 31");
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

error_log("trace 32");
include("head.html");
error_log("trace 33"); ?>


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
<?php include("footer.php");
error_log("trace 34"); ?>
