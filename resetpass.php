<?php
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//
include("config.php");
//include("class.session_handler.php");
include_once("send_email.php");
include_once("functions.php");

$msg="";
// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
// Database Selection String
mysql_select_db(DB_NAME);
///Connect Via facebook and already Registered

//////////new user//////////////
// Edits By RussellReal:
// 'CS' = Base64 Encoded USER ID 'STR' = Token
if (isset($_REQUEST['str']) && isset($_REQUEST['cs'])) {
	$str = stripslashes($_REQUEST['str']);
	$cs = stripslashes($_REQUEST['cs']);
	// if they're both set, go into the query.
	$q = mysql_query($query = "SELECT id, username
		FROM ".USERS."
		WHERE forgot_hash = '".mysql_real_escape_string($str)."'
		AND id = '".mysql_real_escape_string(base64_decode($cs))."'
		AND forgot_expire > NOW();");
	// Check if row returned info, if so set $row to it, then process the else.
	if (!($row = mysql_fetch_assoc($q))) {
		die('No reason to be here Señor!');
	} else {
		$id = $row['id'];
		if (isset($_POST['submit'])) {
			//using the same query as was already on here
			if (strlen($_POST['password'])) {
				mysql_query("UPDATE ".USERS." SET password = '".sha1(mysql_real_escape_string($_POST['password']))."', forgot_hash = 'NULL', forgot_expire = '00/00/00 00:00:00' WHERE id = '$id'");
				// SEND EMAIL TO CHANGEE
				$to = $row['username'];
				$subject = "Password Changed";
				$body = "<p>Congratulations!</p>";
				$body .= "<p>You have successfully changed your password with ".APP_NAME."<br/>";
				$body .= "<p>Love,<br/>Philip and Ryan</p>";
				sl_send_email($to, $subject, $body);
				$msg = "Password has been changed!";
				header('Location: index.php');
			} else {
				$msg = "Please give a password!";
			}
		}
	}
} else {
	// no required information specified, redirect user
	header('Location: index.php');
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
<input type="text" name="username" size="30" value="<?=$row['username']?>" readonly=""></label></p>
<input type="hidden" name="cs" value="<?=$cs;?>" />
<input type="hidden" name="str" value="<?=$str;?>" />
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
 