<?php
//  vim:ts=4:et

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

if (!empty($_SESSION['userid'])) {
    $r = mysql_query ("SELECT SUM(`amount`) AS `sum_amount` FROM `".FEES."` WHERE `user_id` = {$_SESSION['userid']} AND `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'DONE') AND YEAR(DATE) = YEAR(NOW()) AND MONTH(`date`) = MONTH(NOW()) AND withdrawn != 1;") or exit (mysql_error());
    $sum['month'] = mysql_fetch_object($r)->sum_amount;
    if (is_numeric($sum['month'])) {
        $sum['month'] = number_format($sum['month']);
    } else {
        $sum['month'] = '0';
    }

    $r = mysql_query ("SELECT SUM(`amount`) AS `sum_amount` FROM `".FEES."` WHERE `user_id` = {$_SESSION['userid']} AND `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'DONE') AND YEAR(DATE) = YEAR(NOW()) AND WEEK(`date`) = WEEK(NOW()) AND withdrawn != 1;") or exit (mysql_error());
    $sum['week'] = mysql_fetch_object($r)->sum_amount;
    if (is_numeric($sum['week'])) {
        $sum['week'] = number_format($sum['week']);
    } else {
        $sum['week'] = '0';
    }
} else {
    $sum['month'] = '0';
    $sum['week'] = '0';
}

if (isset($return_from_getfeesums) && ($return_from_getfeesums === true))
  return;

exit (json_encode ($sum));

?>
