<?php
//  vim:ts=4:et

//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com


require_once("config.php");
require_once("class.session_handler.php");
include_once("check_new_user.php"); 
require_once("functions.php");
require_once("send_email.php");
require_once('lib/Agency/Worklist/Filter.php');
require_once('classes/UserStats.class.php');
require_once('classes/Repository.class.php');

  
$page=isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 1; //Get the page number to show, set default to 1

$workitem = new WorkItem();

$userId = getSessionUserId();

if( $userId > 0 )	{
    initUserById($userId);
    $user = new User();
    $user->findUserById( $userId );
}

if ($user->getId() > 0 ) {
    $args = array( 'itemid');
    foreach ($args as $arg) {
        if(!empty($_POST[$arg])) {
            $$arg=$_POST[$arg];
        } else {
            $$arg='';
        }
    }
    if (!empty($itemid)) {
        try {
            $workitem->loadById($itemid);
            $summary= "#". $workitem->getId()." - ". $workitem->getSummary();    		
        } catch(Exception $e) {
            //Item id doesnt exist
            $summary="";
        }
    } else {
        $summary='';
    }
    
    $returnString=$summary;
    
} else {
    echo json_encode(array( 'error' => "Invalid parameters !"));
    return;
}

echo json_encode(array( 'returnString' => $returnString));
