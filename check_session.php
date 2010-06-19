<?php 
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

if (empty($_SESSION['userid'])) {
  session_destroy();
  header("location:login.php?redir=".urlencode($_SERVER['REQUEST_URI']));
  exit;
}