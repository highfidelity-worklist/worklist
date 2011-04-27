<?php
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//
include("config.php");
require_once("class/CURLHandler.php");
include_once("send_email.php");
include_once("functions.php");

$msg="";
if (!empty($_POST['submit'])) {
  if (!empty($_POST['password'])) {
    $vars = array(
      'username' => $_POST['username'],
      'token' => $_POST['token'], 
      'password' => $_POST['password']
    );
      ob_start();
      // send the request
      echo CURLHandler::Post(LOGIN_APP_URL . 'changepassword', $vars);
      $result = json_decode(ob_get_contents());
      ob_end_clean();
      
      if ($result->success == true) {
        sendTemplateEmail($_POST['username'], 'changed_pass', array('app_name' => APP_NAME));
      header('Location: login.php');
      } else {
        $msg = 'The link to reset your password has expired or is invalid. <a href="forgot.php">Please try again.</a>';
      }
  } else {
    $msg = "Please enter a password!";
  }
}

if (empty($_REQUEST['token'])) {
  // no required information specified, redirect user
  header('Location: login.php');
}
// END EDITS By RussellReal
/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<script language="javascript">
	function validate() {
	 
		if (document.frmlogin.username.value=="") {
			alert("Please enter your email");
			document.frmlogin.username.focus();
			return false;
		}
		else if (!(/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(document.frmlogin.username.value))){
			alert("Invalid email address! please re-enter");
			document.frmlogin.username.focus();
			return false;
		}
		else if (document.frmlogin.password.value=="") {
			alert("Please enter your password");
			document.frmlogin.password.focus();
			return false;
		}
		else if (document.frmlogin.password.value!=document.frmlogin.confirmpassword.value) {
			alert("Your passwords don't match");
			document.frmlogin.confirmpassword.focus();
			return false;
		}
		else
			return true;
	}
</script>

<!-- jquery file is for LiveValidation -->
<script type="text/javascript" src="js/jquery.js"></script>
<link href="css/worklist.css" rel="stylesheet" type="text/css" />
<title>SendLove | Recover Password</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->
<h1>Reset Password</h1>

<form action="resetpass.php" method="post" name="frmlogin" onSubmit="return validate();">
<p class="error"><?=$msg?></p>

  <? if($msg =="") { ?>
<p><label>Email<br />
<input type="text" name="username" size="30" value="<?php echo(base64_decode($_REQUEST['un'])); ?>" readonly=""></label></p>
<input type="hidden" name="token" value="<?php echo($_REQUEST['token']); ?>" />
<div class="LVspace">
  <p><label>New Password<br />
<input type="password" name="password" id="password" size="30">
</label></p>
<script type="text/javascript">
        var password = new LiveValidation('password',{ validMessage: "You have an OK password.", onlyOnBlur: true });
        password.add(Validate.Length, { minimum: 5, maximum: 12 } );
</script>
  </div>
 
  <div class="LVspace">              
  <p><label>Confirm Password<br />
 <input type="password" name="confirmpassword" id="confirmpassword" size="30"></label></p>
                <script type="text/javascript">
                     var confirmpassword = new LiveValidation('confirmpassword', {validMessage: "Passwords Match."});
                      //confirmpassword.add(Validate.Length, { minimum: 5, maximum: 12 } );
                     confirmpassword.add(Validate.Confirmation, { match: 'password'} );
                </script>
 </div>
<p><input type="submit" class="lgbutton" value="Reset Password" alt="Reset Password" name="submit" /></p>
</form>
<? } ?>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
 