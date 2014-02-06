<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

// Force HTTPS 
if(!array_key_exists('HTTPS', $_SERVER)) {
   header("HTTP/1.1 301 Moved Permanently");
   header('Location: https://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
   exit();
}

require_once("config.php");

Session::check();

ob_start();

if (isset($_SESSION['userid'])) {
    header("Location:worklist.php");
} else {
    header("Location:welcome.php");
}

?>
