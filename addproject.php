<?php
//  vim:ts=4:et

//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
require_once("config.php");
require_once("class.session_handler.php");
include_once("check_new_user.php"); 
require_once("functions.php");
require_once('classes/Repository.class.php');
require_once('classes/Project.class.php');

$journal_message = '';
$nick = '';

$userId = getSessionUserId();
if ($userId > 0 && ($_SESSION['is_runner'] || $_SESSION['is_payer'])) {
    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();

    $project = new Project();
    $cr_3_favorites = $_REQUEST["cr_3_favorites"];
    $args = array( 'name', 'description', 'repository', 'logo', 'cr_anyone', $cr_3_favorites, 'cr_project_admin', 'cr_job_runner'  );
    foreach ($args as $arg) {
        $$arg = !empty($_POST[$arg]) ? $_POST[$arg] : '';
    }
    // check if repository exists, ignore empty repository
    if (!empty($repository) && $project->getIdFromRepo($repository)) {
        die(json_encode(array('error' => "Project repository already exists!")));
    }

    $project->setName($name);
    $project->setDescription($description);
    $project->setRepository($repository);
    $project->setContactInfo($user->getUsername());
    $project->setOwnerId($userId);
    $project->setActive(true);
    $project->setLogo($logo);
    $project->setCrAnyone($cr_anyone);
    $project->setCrFav($cr_3_favorites);
    $project->setCrAdmin($cr_project_admin);
    $project->setCrRunner($cr_job_runner);
    $project->save();
    $journal_message = $nick . ' added project ' . $name;
    
    if (!empty($journal_message)) {
        //sending journal notification
        sendJournalNotification(stripslashes($journal_message));
    }

    echo json_encode(array( 'return' => "Done!"));
} else {
    echo json_encode(array( 'error' => "You must be logged in to add a new project!"));
}
