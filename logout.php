<?php ob_start();
//
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//
include("config.php");
include("class.session_handler.php");
?>

<?php
unset($_SESSION['username']);
unset($_SESSION['userid']);
unset($_SESSION['confirm_string']);
unset($_SESSION['nickname']);
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();
header("location:login.php");
exit;
?>
