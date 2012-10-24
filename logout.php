<?php
//
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
//

ob_start();
include("config.php");
include("class.session_handler.php");


require_once('chat.class.php');
$chat = new Chat();
$chat->offlineSpeaker($_SESSION['userid']);

unset($_SESSION['username']);
unset($_SESSION['userid']);
unset($_SESSION['confirm_string']);
unset($_SESSION['nickname']);
unset($_SESSION['running']);
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

session_destroy();

if (array_key_exists('HTTP_REFERER', $_SERVER)) {
    $url = $_SERVER['HTTP_REFERER'];
} else {
    $url = 'login.php';
}

header("Location: " . $url);
exit;
?>
