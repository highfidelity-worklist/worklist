<?php 
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
if (empty($_SESSION['username']) || empty($_SESSION['userid']) ) {
 	unset($_SESSION['username']);
	unset($_SESSION['userid']);
	unset($_SESSION['confirm_string']);
	session_regenerate_id();
}
?>
