<?php 
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
include("config.php");
include("class.session_handler.php");
include_once("functions.php");
include_once("send_email.php");

try {
	$server = new JsonServer();
	$server->run();
	echo($server->getOutput());
} catch(Exception $e) {
	echo(json_encode(array(
		'success' => false,
		'message' => $e
	)));
}

?>
