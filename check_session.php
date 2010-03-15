<?php 
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
if (empty($_SESSION['username']) || empty($_SESSION['userid']) || empty($_SESSION['confirm_string'])) {
 	unset($_SESSION);
	session_destroy();
	header("location:login.php?expired=1&redir=".urlencode($_SERVER['REQUEST_URI']));
	exit;
}
?>
