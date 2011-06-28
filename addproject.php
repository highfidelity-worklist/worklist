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
if ($userId > 0) {
    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
    $nick = $user->getNickname();

    $project = new Project();
    $args = array( 'name', 'description', 'repository' );
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
    $project->save();
    $journal_message = $nick . ' added project ' . $name;
    
    if (!empty($journal_message)) {
        //sending journal notification
        $data = array();
        $data['user'] = JOURNAL_API_USER;
        $data['pwd'] = sha1(JOURNAL_API_PWD);
        $data['message'] = stripslashes($journal_message);
        $prc = postRequest(JOURNAL_API_URL, $data,array(),10); //increase timeout to 10 seconds
    }

    echo json_encode(array( 'return' => "Done!"));
} else {
    echo json_encode(array( 'error' => "Invalid parameters !"));
}
