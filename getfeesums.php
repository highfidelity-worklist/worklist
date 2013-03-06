<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

  /*
   *  Should userid be provided in $_REQUEST instead of $_SESSION?
   *  Should the date be provided in $_REQUEST instead of assuming now()?
   */

include_once("config.php");
include_once("class.session_handler.php");

// adding ajax handling posibilities
// to this file
// 10-MAY-2010 <Yani>

$sum = array();
$sum["error"] = 0;

if(isset($_GET["weekly"])){
    if (!empty($_SESSION['userid'])) {
        $result = mysql_query("SELECT amount, worklist_id AS task 
                               FROM `".FEES."` 
                               WHERE `user_id` = {$_SESSION['userid']} AND 
                               `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'Done') AND 
                               YEAR(DATE) = YEAR(NOW()) AND 
                               WEEK(`date`) = WEEK(NOW()) AND 
                               withdrawn != 1") or exit (mysql_error());
    $output = '
    <table class="table-bids">
      <thead>
        <tr class="table-hdng">
          <td>Task ID</td>
          <td>Fee</td>
        </tr>
      </thead>
      <tbody>
    ';
    $c = 0;
    while($row = mysql_fetch_object($result)) {
        $c++;
        $output .= '
        <tr>
          <td><a href="workitem.php?job_id='.$row->task.'">'.$row->task.'</a></td>
          <td style="color: #6F6F6F; font-weight:bold;">$'.$row->amount.'</td>
        </tr>
        ';
    }
    if($c == 0){
        $output .= '
        <tr>
          <td colspan="2">There is nothing to show.</td>
        </tr>
        ';
    }
    $output .= '
      </tbody>
    </table>
    ';
    $sum['output'] = $output;
    } else {
        $sum["error"] = 1;
    }
    echo json_encode($sum);
} else if(isset($_GET["monthly"])){
    if (!empty($_SESSION['userid'])) {
    $result = mysql_query("SELECT amount, worklist_id as task 
                           FROM `".FEES."` 
                           WHERE `user_id` = {$_SESSION['userid']} AND 
                           `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'Done') AND 
                           YEAR(DATE) = YEAR(NOW()) AND 
                           MONTH(`date`) = MONTH(NOW()) AND 
                           withdrawn != 1") or exit (mysql_error());
    $output = '
    <table class="table-bids">
      <thead>
        <tr class="table-hdng">
          <td>Task ID</td>
          <td>Fee</td>
        </tr>
      </thead>
      <tbody>
    ';
    $c = 0;
    while($row = mysql_fetch_object($result)) {
        $c++;
        $output .= '
        <tr>
          <td><a href="workitem.php?job_id='.$row->task.'">'.$row->task.'</a></td>
          <td style="color: #6F6F6F; font-weight:bold;">$'.$row->amount.'</td>
        </tr>
        ';
    }
    if($c == 0){
        $output .= '
        <tr>
          <td colspan="2">There is nothing to show.</td>
        </tr>
        ';
    }
    $output .= '
      </tbody>
    </table>
    ';
    $sum['output'] = $output;
    } else {
        $sum["error"] = 1;
    }
    echo json_encode($sum);
} else {
    if (!empty($_SESSION['userid'])) {
        $r = mysql_query ("SELECT SUM(`amount`) AS `sum_amount` FROM `".FEES."` WHERE `user_id` = {$_SESSION['userid']} AND
                          `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'Done') AND YEAR(DATE) = YEAR(NOW()) AND
                           MONTH(`date`) = MONTH(NOW()) AND withdrawn != 1;") or exit (mysql_error());
        $sum['month'] = mysql_fetch_object($r)->sum_amount;
        if (is_numeric($sum['month'])) {
            $sum['month'] = number_format($sum['month']);
        } else {
            $sum['month'] = '0';
        }
    
        $r = mysql_query ("SELECT SUM(`amount`) AS `sum_amount` FROM `".FEES."` WHERE `user_id` = {$_SESSION['userid']} AND
                          `worklist_id` IN (SELECT `id` FROM `".WORKLIST."` WHERE `status` = 'Done') AND YEAR(DATE) = YEAR(NOW()) AND
                           WEEK(`date`) = WEEK(NOW()) AND withdrawn != 1;") or exit (mysql_error());
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
}
?>
