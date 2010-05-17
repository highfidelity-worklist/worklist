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

echo $filter->getUserSelectBox($active);

?>