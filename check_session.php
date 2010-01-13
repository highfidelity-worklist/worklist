<?php 
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
if (empty($_SESSION['username']) || empty($_SESSION['userid']) || empty($_SESSION['confirm_string'])) {
 	unset($_SESSION['username']);
	unset($_SESSION['userid']);
	unset($_SESSION['confirm_string']);
	session_destroy();
	header("location:index.php?expired=1&redir=".urlencode($_SERVER['REQUEST_URI']));
	exit;
}
?>
