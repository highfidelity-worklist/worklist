<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.                                                                                                                          
//  All Rights Reserved.                                                                                                                                          
//  http://www.lovemachineinc.com
include("config.php");
include_once("send_email.php");
require_once("class/CURLHandler.php");
// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
// Database Selection String
mysql_select_db(DB_NAME);
extract($_REQUEST);

if(!empty($_POST['username'])) { 
    ob_start();
    // send the request
    CURLHandler::Post(LOGIN_APP_URL . 'resettoken', array('username' => $_POST['username']));
    $result = ob_get_contents();
    ob_end_clean();
    $result = json_decode($result);
    if ($result->success == true) {
      $resetUrl = SECURE_SERVER_URL . 'resetpass.php?un=' . base64_encode($_POST['username']) . '&amp;token=' . $result->token;
      $resetUrl = '<a href="' . $resetUrl . '" title="Password Recovery">' . $resetUrl . '</a>';
      sendTemplateEmail($_POST['username'], 'recovery', array('url' => $resetUrl));
      $msg= '<p class="LV_valid">Login information will be sent if the email address ' . $_POST['username'] . ' is registered.</p>';
    } else {
      $msg = '<p class="LV_invalid">Sorry, unable to send password reset information. Try again or contact an administrator.</p>';
    }
}

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->

<!-- jquery file is for LiveValidation -->
<script type="text/javascript" src="js/jquery.livevalidation.js"></script>


<title>SendLove | Recover Password</title>

</head>

<body>

<?php include("format.php"); ?>
<br/>
<br/>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->


       	
            <h1>Recover Your Password</h1>
                       
            <h3>So many passwords, so little time... :)</h3><br />

                       
        <form action="#" method="post">
        
                 <?php if(!empty($msg)) {?>
              <?php echo $msg; ?>
                <?php } ?>
                
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
