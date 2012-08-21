<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
require_once('class/Session.class.php');

if(!defined('CHECK_SESSION')) {
session::check();
define('CHECK_SESSION',true);
}
