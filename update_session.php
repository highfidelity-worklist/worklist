<?php
  //  Copyright (c) 2009, LoveMachine Inc.
  //  All Rights Reserved.
  //  http://www.lovemachineinc.com

  // AJAX request from ourselves to update our session data

include("config.php");
include("class.session_handler.php");

if(isset($_POST['sfilter']))
{
  $_SESSION['sfilter'] = $_POST['sfilter'];
}

if(isset($_POST['ufilter']))
{
  $_SESSION['ufilter'] = $_POST['ufilter'];
}

exit;

?>