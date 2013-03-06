<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

include("config.php");
include_once("send_email.php");
// Database Connection Establishment String
mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
// Database Selection String
mysql_select_db(DB_NAME);
extract($_REQUEST);

if(!empty($_POST['username']))
{ 
    $res=mysql_query("select id, confirm, confirm_string from ".USERS." where username ='".mysql_real_escape_string($_POST['username'])."'");
    if(mysql_num_rows($res) > 0 ) {
        $row = mysql_fetch_array($res);
        $to = $_POST['username'];
// Email user
        $subject = "Worklist Registration Confirmation";
        $body = "<p>You are only one click away from completing your registration with Worklist!</p><p>Click the link below or copy into your browser's window to verify your email address and activate your account. <br/>";
        $body .= "&nbsp;&nbsp;&nbsp;&nbsp;".SECURE_SERVER_URL."confirmation.php?cs=".$row['confirm_string']."&str=".base64_encode($_POST['username'])."</p>";
        $body .= "<p>Looking forward to seeing you in the Workroom! :)</p>";
        if(!send_email($to, $subject, $body)) { error_log("resend.php: send_email failed"); }
        $msg= "An email containing a link to confirm your email address is being sent to ".$to;
    }
    else $msg= "Sorry, your email address doesn't match";
}


/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<title>Worklist | Recover Password</title>

</head>

<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->           
            <h1>Re-Send Email Confirmation</h1>
                       
            <h3>Check your spam folder for the confirmation email or enter your email below to have us send you a new one</h3><br />

                       
        <form action="#" method="post">
        
                 <? if(!empty($msg)) 
              {
              ?>
              <p class="LV_valid"><?=$msg?></p>
                <? } ?>
                
                <div class="LVspace">
                <label>Email<br />
                  <input type="text" id="username" name="username" class="text-field" size="30" />
                </label>
                  <script type="text/javascript">
                        var username = new LiveValidation('username', {validMessage: "ok"});
                        //username.add( Validate.Presence );
                        username.add( Validate.Email );
                        username.add(Validate.Length, { minimum: 10, maximum: 50 } );
                    </script>
          </div>
                 <br />
                <p><input type="submit" value="Send Mail" alt="Send Mail"></p>
        </form>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php require_once('footer.php'); ?>
