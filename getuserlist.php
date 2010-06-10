<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com


include("config.php");
include("class.session_handler.php");

$limit = 30;
$page = isset($_REQUEST["page"])?intval($_REQUEST["page"]) : 1;
$letter = isset($_REQUEST["letter"]) ? mysql_real_escape_string(trim($_REQUEST["letter"])) : "";
$order = !empty($_REQUEST["order"]) ? mysql_real_escape_string(trim($_REQUEST["order"])) : "nickname";
$order_dir =  isset($_REQUEST["order_dir"]) ? mysql_real_escape_string(trim($_REQUEST["order_dir"])) : "DESC";
$active = isset( $_REQUEST['active'] ) && $_REQUEST['active'] == 'TRUE' ? 'TRUE' : 'FALSE';

$sfilter = !empty($_REQUEST['sfilter']) ? $_REQUEST['sfilter'] : 'UNPAID';
switch ($sfilter) {
case 'TOTAL':
  $sfilter = '1';
  break;
case 'UNPAID':
  $sfilter = '`paid`!=1';
  break;
default:
  $sfilter = '`paid`=1';
  break;
}

if($letter == "all"){
  $letter = ".*";
}
if($letter == "_"){ //numbers
  $letter = "[0-9]";
}

if( $active == 'FALSE' )	{
	$rt = mysql_query("SELECT COUNT(*) FROM `users` WHERE `nickname` REGEXP '^$letter' AND `is_active` = 1");
	$row = mysql_fetch_row($rt);
	$users = intval($row[0]);
}	else if( $active == 'TRUE' )	{
	$rt = mysql_query("
	SELECT COUNT(*) FROM `users` 
	LEFT JOIN (SELECT `user_id`,MAX(`date`) AS `date` FROM `".FEES."` GROUP BY `user_id`) AS `dates` ON `".USERS."`.id = `dates`.user_id
	WHERE (`date` > DATE_SUB(NOW(), INTERVAL 45 DAY) OR `added` > DATE_SUB(NOW(), INTERVAL 30 DAY)) AND `is_active` = 1 AND `nickname` REGEXP '^$letter'");
	
	$row = mysql_fetch_row($rt);
	$users = intval($row[0]);
}

$cPages = ceil($users/$limit); 

if( $active == 'FALSE' ) {
	$query = "
	SELECT `id`, `nickname`,DATE_FORMAT(`added`, '%m/%d/%Y') AS `joined`, `is_runner`, `budget`,
	IFNULL(`creators`.`count`,0) AS `created_count`, 
	IFNULL(`mechanics`.`count`,0) AS `mechanic_count`, 
	IFNULL(`bids_placed`.`count`,0) AS `bids_placed`, 
	IFNULL(`bids_accepted`.`count`,0) AS `bids_accepted`,
	IFNULL(`earnings`.`sum`,0) AS `earnings`,
	IFNULL(`expenses_billed`.`sum`,0) AS `expenses_billed`,
	IFNULL(`rewarder`.`sum`,0) AS `rewarder`
	FROM `".USERS."` 
	LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `".WORKLIST."` GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id` 
	LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `".WORKLIST."` GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id` 
	LEFT JOIN (SELECT `bidder_id`, COUNT(`bidder_id`) AS `count` FROM `".BIDS."` GROUP BY `bidder_id`) AS `bids_placed` ON `".USERS."`.`id` = `bids_placed`.`bidder_id` 
	LEFT JOIN (SELECT `bidder_id`, COUNT(`bidder_id`) AS `count` FROM `".BIDS."` WHERE `accepted` = 1 GROUP BY `bidder_id`) AS `bids_accepted` ON `".USERS."`.`id` = `bids_accepted`.`bidder_id`  
	LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 AND `rewarder`=1 GROUP BY `user_id`) AS `rewarder` ON `".USERS."`.`id` = `rewarder`.`user_id`  
	LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 AND `expense`=0 and `rewarder`=0 GROUP BY `user_id`) AS `earnings` ON `".USERS."`.`id` = `earnings`.`user_id`  
	LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 and `expense`=1 GROUP BY `user_id`) AS `expenses_billed` ON `".USERS."`.`id` = `expenses_billed`.`user_id`  
	WHERE `nickname` REGEXP '^$letter' AND `is_active` = 1  ORDER BY `$order` $order_dir LIMIT " . ($page-1)*$limit . ",$limit";
}	else if( $active == 'TRUE' )	{
	$query = "
	SELECT `id`, `nickname`,DATE_FORMAT(`added`, '%m/%d/%Y') AS `joined`, `is_runner`, `budget`,
	IFNULL(`creators`.`count`,0) AS `created_count`, 
	IFNULL(`mechanics`.`count`,0) AS `mechanic_count`, 
	IFNULL(`bids_placed`.`count`,0) AS `bids_placed`, 
	IFNULL(`bids_accepted`.`count`,0) AS `bids_accepted`,
	IFNULL(`earnings`.`sum`,0) AS `earnings`,
	IFNULL(`expenses_billed`.`sum`,0) AS `expenses_billed`,
	IFNULL(`rewarder`.`sum`,0) AS `rewarder`
	FROM `".USERS."` 
	LEFT JOIN (SELECT `user_id`,MAX(`date`) AS `date` FROM `".FEES."` GROUP BY `user_id`) AS `dates` ON `".USERS."`.id = `dates`.user_id
	LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `".WORKLIST."` GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id` 
	LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `".WORKLIST."` GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id` 
	LEFT JOIN (SELECT `bidder_id`, COUNT(`bidder_id`) AS `count` FROM `".BIDS."` GROUP BY `bidder_id`) AS `bids_placed` ON `".USERS."`.`id` = `bids_placed`.`bidder_id` 
	LEFT JOIN (SELECT `bidder_id`, COUNT(`bidder_id`) AS `count` FROM `".BIDS."` WHERE `accepted` = 1 GROUP BY `bidder_id`) AS `bids_accepted` ON `".USERS."`.`id` = `bids_accepted`.`bidder_id`  
	LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 AND `rewarder`=1 GROUP BY `user_id`) AS `rewarder` ON `".USERS."`.`id` = `rewarder`.`user_id`  
	LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 AND `expense`=0 AND `rewarder`=0 GROUP BY `user_id`) AS `earnings` ON `".USERS."`.`id` = `earnings`.`user_id`  
	LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 and `expense`=1 GROUP BY `user_id`) AS `expenses_billed` ON `".USERS."`.`id` = `expenses_billed`.`user_id`  
	WHERE (`date` > DATE_SUB(NOW(), INTERVAL 45 DAY) OR `added` > DATE_SUB(NOW(), INTERVAL 30 DAY)) AND `nickname` REGEXP '^$letter' AND `is_active` = 1  ORDER BY `$order` $order_dir LIMIT " . ($page-1)*$limit . ",$limit";
}

$rt = mysql_query($query);

// Construct json for pagination
$userlist = array(array($users, $page, $cPages));

while($row = mysql_fetch_assoc($rt)){
/*
  $json_row = array(); 
  foreach($row as $user){
    $json_row[] = $user;
  }
*/
  $userlist[] = $row;
}
                      
$json = json_encode($userlist);
echo $json;     
