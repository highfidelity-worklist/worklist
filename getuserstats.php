<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");

if (isset($_REQUEST['id'])) {
    $userId = (int)$_REQUEST['id'];
} else {
    die("No id provided");
}

    $userStats = new UserStats($userId);
    $userStats->setItemsPerPage(9);

    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;

    switch($_REQUEST['statstype']){

    case 'love':
        echo json_encode($userStats->getTotalLove($page));
        break;

    case 'donejobs':
        echo json_encode($userStats->getDoneJobs($page));
        break;

    case 'activejobs':
        echo json_encode($userStats->getActiveJobs($page));
        break;
    }
