<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

require_once('functions.php');
require_once('class/Utils.class.php');
if (!isset($is_runner)) {
    $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
}
if (!isset($is_payer)) {
    $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
}

$userId = getSessionUserId();
$lovemachineLink = SENDLOVE_URL . '/';
$linkTarget = '';
?>
    <div id="outside"> 
<!-- Welcome, login/out -->
        <div id="welcome">
<?php
    if (isset($_SESSION['username'])) {
        $return_from_getfeesums = true;
        include 'getfeesums.php';
        $earnings = ' | <a href="javascript:;" class="earnings">Earnings</a> ';
        if (!empty($_SESSION['is_runner'])) {
            $budget = ' | <a href="javascript:;" class="budget">Budget</a> ';
        } else {
            $budget = '<span class="budget"></span>';
        }
        if (empty($_SESSION['nickname'])) { 
            $name = getSubNickname($_SESSION['username']);
        } else {
            $name = getSubNickname($_SESSION['nickname']);
        }
        $following = " | <a href='javascript:;' class='following'>My Followed Jobs</a>";
        echo "Welcome, <span id='user'> $name </span> | <a href='settings.php' " . $linkTarget . ">Settings</a> $earnings $following $budget | <a href='logout.php'>Logout</a>";
    }
?>
            <div id="tagline">A community of independent software developers.</div>
            <div class="clear"></div>
        </div>
