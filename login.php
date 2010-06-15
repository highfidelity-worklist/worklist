<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//
ob_start();
include("config.php");
include("class.session_handler.php");
if (!empty($_SESSION['username'])) {
     header("Location:worklist.php");
     exit;
}
if (!empty($_SESSION['userid'])) {
     header("Location:worklist.php");
     exit;
}
require_once("functions.php");

// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
// Database Selection String
mysql_select_db(DB_NAME);

$username = isset($_REQUEST['username']) ? trim($_REQUEST['username']) : '';
if (!empty($_REQUEST['reauth'])) { $msg = 'This transaction is not authorized!'; }
$expired = !empty($_REQUEST['expired']) ? 1 : 0;

if($_POST) {
        require_once ("class/Error.class.php");
        require_once ("class/Login.class.php");
        require_once ("class/Response.class.php");
        $error = new Error();
        $username = isset($_REQUEST["username"]) ? trim($_REQUEST["username"]) : "";
        $password = isset($_REQUEST["password"]) ? $_REQUEST["password"] : "";
        if(empty($username)){
            $error->setError("Username cannot be empty.");
        }else if(empty($password)){
            $error->setError("Password cannot be empty.");
        }else{
            $params = array("username" => $username, "password" => $password, "action" => "login");
            ob_start();
            // send the request
            CURLHandler::Post(SERVER_URL . 'loginApi.php', $params, false, true);
            $result = ob_get_contents();
            ob_end_clean();
            $ret = json_decode($result);
            if($ret->error == 1){
                $error->setError($ret->message);
            }else{
                $id = $ret->userid;
                $username = $ret->username;
                $nickname = $ret->nickname;
                initUserById($id);
                $_SESSION["userid"] = $id;
                $_SESSION["username"] = $username;
                $_SESSION["nickname"] = $nickname;
                $_SESSION["confirm_string"] = $ret->confirm_string;
                // notifying other applications
                $response = new Response();
                $login = new Login();
                $login->setResponse($response);
                $login->notify($id, session_id());
                if ($_POST['redir']) {
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
}
include('openid.php');

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

include("head.html"); ?>


<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css">

<!-- jquery file is for LiveValidation -->
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>

<title>Welcome to the Worklist</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->
           
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
        <? if(isset($error)): ?>
            <?php foreach($error->getErrorMessage() as $msg):?>
              <p class="LV_invalid"><?php echo $msg; ?></p>
            <?php endforeach;?>
        <? endif; ?>
	<div class="login_left">
		<form id="login" action="" method="post">
			<input type="hidden" name="redir" value="<?php echo $redir ?>" />
			<div class="LVspace">
				<label>Username<br />
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
	<div class="login_right">
		<form action="" method="post">
			<div class="LVspace">
				<label>Google Login<br />
					<input type="text" name="google_identifier" class="text-field google" id="google_identifier" value="" size="40" />
				</label>
				<script type="text/javascript">
					var openid = new LiveValidation('openid_identifier', {validMessage: 'Valid url.', onlyOnBlur: false});
						openid.add(Validate.Format, { pattern: /((http|https)(:\/\/))?([a-zA-Z0-9]+[.]{1}){2}[a-zA-z0-9]+(\/{1}[a-zA-Z0-9]+)*\/?/i, failureMessage: "Must be a valid url!" });
				</script>
			</div>
			<p>
				<input type="submit" name="openid_action" value="Google Login" />
			</p>
		</form>
	</div>
</div>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
