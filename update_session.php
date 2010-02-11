<?php
  //  Copyright (c) 2009, LoveMachine Inc.
  //  All Rights Reserved.
  //  http://www.lovemachineinc.com

  // AJAX request from ourselves to update our session data

include("config.php");
include("class.session_handler.php");

if(isset($_REQUEST['sfilter'])) {
  $_SESSION['sfilter'] = $_REQUEST['sfilter'];
}

if(isset($_REQUEST['ufilter'])) {
  $_SESSION['ufilter'] = $_REQUEST['ufilter'];
}

$_SESSION['update_filter'] = 1;

exit;
?>