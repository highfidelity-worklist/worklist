<?php
//  vim:ts=4:et
//
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//
include("config.php");
include("class.session_handler.php");
include("check_session.php");
require_once('lib/Agency/Worklist/Filter.php');

// If no action is passed exit
if (!isset($_REQUEST['name']) || !isset($_REQUEST['active'])) {
    return;
}

$name = $_REQUEST['name'];
$active = intval($_REQUEST['active']);

$filter = new Agency_Worklist_Filter();
$filter->setName($name)
       ->initFilter();
       
$users = User::getUserlist(getSessionUserId(), $active);
$json = array(
	array(
		'value' => 0,
		'text' => 'All Users',
		'selected' => (($filter->getUser() == 0) ? true : false)
	)
);
foreach ($users as $user) {
	$json[] = array(
		'value' => $user->getId(),
		'text' => $user->getNickname(),
		'selected' => (($filter->getUser() == $user->getId()) ? true : false)
	);
}
echo(json_encode($json));
?>