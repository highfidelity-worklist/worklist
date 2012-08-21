<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

ob_start();
include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("functions.php");
include_once("send_email.php");
require_once ("class/Utils.class.php");

$msg = array();
if (!empty($_POST['oldpassword'])) {
    if((!empty($_POST['newpassword'])) && ($_POST['newpassword'] == $_POST['confirmpassword'])){

        $password = '{crypt}' . Utils::encryptPassword($_POST['newpassword']);

        $sql = "
            UPDATE " . USERS . " 
            SET password = '" . mysql_real_escape_string($password) . "'
            WHERE id ='" . $_SESSION['userid'] . "'";

        if (mysql_query($sql)) {

            $msg[] = "Password updated successfully!";
            $to = $_SESSION['username'];
            $subject = "Password Change";
            $body  = "<p>Congratulations!</p>";
            $body .= "<p>You have successfully updated your password with ".SERVER_NAME.".";
            $body .= "</p><p>Love,<br/>Philip and Ryan</p>";
            if (!send_email($to, $subject, $body)) { 
                error_log("password.php: send_email failed");
            }
        } else {
            $msg[] = "Failed to update your password";
        }
    } else {
        $msg[] = "New passwords don't match!";
    }
}
/*********************************** HTML layout begins here  *************************************/

include("head.html");
?>
<link href="css/worklist.css" rel="stylesheet" type="text/css">
<title>Worklist | Change Password</title>
</head>
<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<h1>Change Password</h1>

<?php if (!empty($msg)) { ?>
    <p class="error">
    <?php foreach($msg as $line){
        echo $line . "<br />";
    } ?>
    </p>
<?php } ?>

<form method="post" action="password.php" name="form_password" onSubmit="return validate();">

    <p><label>Current Password<br />
    <input type="password" name="oldpassword" id="oldpassword" size="35" />
    </label></p>
    <div class="LVspace">
    <p><label>New Password<br />
    <input type="password" name="newpassword" id="newpassword" size="35" />
    </label></p>
    <script type="text/javascript">
        var newpassword = new LiveValidation('newpassword',{ validMessage: "You have an OK password.", onlyOnBlur: true });
            newpassword.add(Validate.Length, { minimum: 5, maximum: 255 } );
    </script>
    </div>

    <div class="LVspace">
    <p><label>Re-enter New Password<br />
    <input type="password" name="confirmpassword" id="confirmpassword" size="35" />
    </label></p>
    <script type="text/javascript">
        var confirmpassword = new LiveValidation('confirmpassword', {validMessage: "Passwords Match."});
            confirmpassword.add(Validate.Confirmation, { match: 'newpassword'} );
    </script>
    </div>

    <input type="submit" value="Change Password" alt="Change Password" name="change-password" />

</form>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->

<script type="text/javascript" src="js/jquery.js"></script>
<?php include("footer.php"); ?>
