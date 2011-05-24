<?php 
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

if (empty($_SESSION['userid'])) {
    session_destroy();
    // was there an attempt to POST data?
    if (!empty($_POST)) {
        require_once('functions.php');
        handleUnloggedPost();
    }
    header("location:login.php?redir=".urlencode($_SERVER['REQUEST_URI']));
    exit;
}
