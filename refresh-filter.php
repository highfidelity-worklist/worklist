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
require_once('classes/Project.class.php');
require_once('lib/Agency/Worklist/Filter.php');

// If no action is passed exit
if (!isset($_REQUEST['name']) || !isset($_REQUEST['active'])) {
    return;
}

$name = $_REQUEST['name'];
$active = intval($_REQUEST['active']);
$type = $_REQUEST['filter'];

$filter = new Agency_Worklist_Filter();
$filter->setName($name)
       ->initFilter();

$json = array();

switch ($type) {
    case 'projects':
        $projects = Project::getProjects($active);
        
       $json[] = array(
            'value' => 0,
            'text' => '-- Select --',
            'selected' => false
        );
        
        foreach ($projects as $project) {
            $json[] = array(
                'value' => $project['project_id'],
                'text' => $project['name'],
                'selected' => false
            );
        }
        
        break;
        
    case 'users':
        $users = User::getUserlist(getSessionUserId(), $active);
        $json[] = array(
            'value' => 0,
            'text' => 'All Users',
            'selected' => (($filter->getUser() == 0) ? true : false)
        );
        foreach ($users as $user) {
        	$json[] = array(
        		'value' => $user->getId(),
        		'text' => $user->getNickname(),
        		'selected' => (($filter->getUser() == $user->getId()) ? true : false)
        	);
        }
    
        break;
}

echo(json_encode($json));
?>