<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.                                                                                                                          
//  All Rights Reserved.                                                                                                                                          
//  http://www.lovemachineinc.com
include("config.php");
include_once("send_email.php");
// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
// Database Selection String
mysql_select_db(DB_NAME);
extract($_REQUEST);

if(!empty($_POST['username'])) { 
	$res=mysql_query("SELECT username, id FROM ".USERS." WHERE username ='".mysql_real_escape_string($_POST['username'])."'");
	// Edits by RussellReal below:
	if(mysql_num_rows($res) > 0 ) {
		$row = mysql_fetch_array($res);	
		// generate random token
		$token = md5((rand(1,100) + ord(substr($row['username'],rand(0,strlen($row['username']) - 2),1)))."salt is the prev part.. this too :)".$row['id']);
		// insert token into db table if successful, send email
		if (mysql_query("UPDATE ".USERS." SET forgot_hash = '{$token}', forgot_expire = ADDDATE(NOW(), INTERVAL 1 HOUR) WHERE username = '".mysql_real_escape_string($_POST['username'])."'")) {
			$subject = "Password Recovery";
			$body = "<p>Hi,</p>";
			$body .= "<p>Please click on the link below or copy and paste the url in browser to reset your password. <br/>";
			$body .= "&nbsp;&nbsp;&nbsp;&nbsp;".SECURE_SERVER_URL."resetpass.php?cs=".base64_encode($row['id'])."&str={$token}</p>";
			$body .= "<p>Love,<br/>Philip and Ryan</p>";			
			sl_send_email($row['username'], $subject, $body);
			$msg= "<p class='LV_valid'>Login information will be sent if the email address ".$row['username']." is registered.</p>";
		}
		else $msg = "<p class='LV_invalid'>Failed to send password reset information! Try again or contact an administrator! ".mysql_error()."</p>";
	}
	else $msg = "<p class='LV_invalid'>Failed to send password reset information! Try again or contact an administrator!</p>";
	// END Edits
}

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->

<!-- jquery file is for LiveValidation -->
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>


<title>SendLove | Recover Password</title>

</head>

<body>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->


       	
            <h1>Recover Your Password</h1>
                       
            <h3>Forget your password? It happens to the best of us.</h3><br />

                       
        <form action="#" method="post">
        
                 <? if(!empty($msg)) {?>
              <?=$msg?>
                <? } ?>
                
        		<div class="LVspace">
                <label>Email<br />
			  	<input type="text" id="username" name="username" class="text-field" size="30" />
                </label>
          		<script type="text/javascript">
						var username = new LiveValidation('username',{ validMessage: "Valid email address.", onlyOnBlur: false} );
						//username.add( Validate.Presence );
						username.add( Validate.Email );
						username.add(Validate.Length, { minimum: 10, maximum: 50 } );
					</script>
                 </div>
                 <br />
                <p><input type="submit" value="Send Mail" alt="Send Mail" name="Send Mail"></p>
        </form>

<?php include("footer.php"); ?>
