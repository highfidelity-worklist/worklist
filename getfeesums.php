<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

  /*
   *  Should userid be provided in $_REQUEST instead of $_SESSION?
   *  Should the date be provided in $_REQUEST instead of assuming now()?
   */

include_once("config.php");
include_once("class.session_handler.php");

$sum = array();

$r = mysql_query ("select sum(amount) as sum_amount from fees where user_id={$_SESSION['userid']} and worklist_id in (select id from worklist where status='DONE') and year(date)=year(now()) and month(date)=month(now());") or exit (mysql_error());
$sum['month'] = number_format (mysql_fetch_object($r)->sum_amount);
if (!is_numeric($sum['month'])) $sum['month'] = '0';

$r = mysql_query ("select sum(amount) as sum_amount from fees where user_id={$_SESSION['userid']} and worklist_id in (select id from worklist where status='DONE') and year(date)=year(now()) and week(date)=week(now());") or exit (mysql_error());
$sum['week'] = number_format (mysql_fetch_object($r)->sum_amount);
if (!is_numeric($sum['week'])) $sum['week'] = '0';

if (isset($return_from_getfeesums) && ($return_from_getfeesums === true))
  return;

exit (json_encode ($sum));

?>
