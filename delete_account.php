<?php
//  vim:ts=4:et

//
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

if(!empty($_POST['delete-account']))
{
    $password = sha1(mysql_real_escape_string($_POST['password']));
    $qry="SELECT id FROM ".USERS." WHERE username='".mysql_real_escape_string($_SESSION['username'])."' AND password='$password'";
    $rs=mysql_query($qry);
    if(mysql_num_rows($rs) > 0)
    {
        $sql = "DELETE FROM `".USERS."` WHERE `id` ='".$_SESSION['userid']."' AND password='$password'";
        mysql_unbuffered_query($sql);

        $to = $_SESSION['username'];
        $subject = "Account Deleted";
        $body .= "<p>As you requested, we have deleted your '".$_SESSION['nickname']."' account with ".SERVER_NAME.".";
        $body .= "</p><p>Love,<br/>Philip and Ryan</p>";
        if (! send_email($to, $subject, $body)) { error_log("delete: send_email failed"); }

        header("Location: logout.php");
        exit;
    } else {
        $msg ="The password you entered was incorrect.";
    }
}

/*********************************** HTML layout begins here  *************************************/

include("head.html");
?>

<title>Worklist | Delete Account</title>

</head>

<body>
<?php
    require_once('header.php');
    require_once('format.php');
?>
<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

<h1>Delete Account</h1>

<?php if (!empty($msg)) { ?><p class="error"><?php echo $msg ?></p><?php } ?>

<form method="post" action="delete_account.php" name="form_delete_account">

    <p><label>Please enter your password to confirm that you want to delete your account:<br />
    <input type="password" name="password" id="password" size="35" />
    </label></p>

    <input type="submit" value="Delete Account" alt="Delete Account" name="delete-account" />

</form>

<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->

<?php include("footer.php"); ?>
