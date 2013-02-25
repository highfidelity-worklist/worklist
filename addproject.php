<?php
//  vim:ts=4:et

//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
require_once ("config.php");
require_once ("class.session_handler.php");
include_once ("check_new_user.php"); 
require_once ("functions.php");

$journal_message = '';
$nick = '';

$userId = getSessionUserId();
if ($userId) {
    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();

    $project = new Project();
    $cr_3_favorites = $_REQUEST["cr_3_favorites"];
    $args = array('name', 'description', 'logo', 'website');
    foreach ($args as $arg) {
        $$arg = !empty($_POST[$arg]) ? $_POST[$arg] : '';
    }
    
    if (!ctype_alnum($name)) {
        die(json_encode(array('error' => "The name of the project can only contain letters (A-Z) and numbers (0-9). Please review and try again.")));
    }
    $repository = $name;

    if ($project->getIdFromName($name)) {
        die(json_encode(array('error' => "Project with the same name already exists!")));
    }

    $project->setName($name);
    $project->setDescription($description);
    $project->setRepository($name);
    $project->setWebsite($website);
    $project->setContactInfo($user->getUsername());
    $project->setOwnerId($userId);
    $project->setActive(true);
    $project->setLogo($logo);
    $project->save();
    
    $journal_message = $nick . ' added project ##' . $name;
    if (!empty($journal_message)) {
        //sending journal notification
        sendJournalNotification($journal_message);
    }

    echo json_encode(array( 'return' => "Done!"));
} else {
    echo json_encode(array( 'error' => "You must be logged in to add a new project!"));
}
