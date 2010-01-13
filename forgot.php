<?php
//  Copyright (c) 2009, LoveMachine Inc.                                                                                                                          
//  All Rights Reserved.                                                                                                                                          
//  http://www.lovemachineinc.com
include("config.php");
include_once("send_email.php");
// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
// Database Selection String
mysql_select_db(DB_NAME);
extract($_REQUEST);

if(!empty($_POST['username']))
{ 
	$to = strip_tags($_POST['username']);
	$res=mysql_query("select id, confirm, confirm_string from ".USERS." where username ='".mysql_real_escape_string($_POST['username'])."'");
	if(mysql_num_rows($res) > 0 ) {
		$row = mysql_fetch_array($res);
		$subject = "Password Recovery";
		$body = "<p>Hi,</p>";
		$body .= "<p>Please click on the link below or copy and paste the url in browser to reset your password. <br/>";
		$body .= "&nbsp;&nbsp;&nbsp;&nbsp;".SECURE_SERVER_URL."resetpass.php?cs=".$row['confirm_string']."&str=".base64_encode($_POST['username'])."</p>";
		$body .= "<p>Love,<br/>Philip and Ryan</p>";
		
		sl_send_email($to, $subject, $body);
	}
	$msg= "<p class='LV_valid'>Login information will be sent if the email address ".strip_tags($to)." is registered.</p>";
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
