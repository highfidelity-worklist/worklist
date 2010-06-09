<?php 
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com
session::check();

if (empty($_SESSION['username']) || empty($_SESSION['userid']) || empty($_SESSION['confirm_string'])) {
  unset($_SESSION['username']);
  unset($_SESSION['userid']);
  session_destroy();
  header("location:login.php?redir=".urlencode($_SERVER['REQUEST_URI']));
  exit;
}