<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com


include("config.php");
include("class.session_handler.php");
require_once('classes/User.class.php');
include("functions.php");

$limit = 30;
$page = isset($_REQUEST["page"])?intval($_REQUEST["page"]) : 1;
$letter = isset($_REQUEST["letter"]) ? mysql_real_escape_string(trim($_REQUEST["letter"])) : "";
$order = !empty($_REQUEST["order"]) ? mysql_real_escape_string(trim($_REQUEST["order"])) : "earnings30";
$order_dir =  isset($_REQUEST["order_dir"]) ? mysql_real_escape_string(trim($_REQUEST["order_dir"])) : "DESC";
$active = isset( $_REQUEST['active'] ) && $_REQUEST['active'] == 'TRUE' ? 'TRUE' : 'FALSE';

$sfilter = $_REQUEST['sfilter'];

if($letter == "all"){
  $letter = ".*";
}
if($letter == "_"){ //numbers
  $letter = "[0-9]";
}

if( $active == 'FALSE' )	{
	$rt = mysql_query("SELECT COUNT(*) FROM `".USERS."` WHERE `nickname` REGEXP '^$letter' AND `is_active` = 1");
	
	$row = mysql_fetch_row($rt);
	$users = intval($row[0]);
    
}	else if( $active == 'TRUE' )	{
	$rt = mysql_query("
	SELECT COUNT(*) FROM `".USERS."` 
    LEFT JOIN (SELECT `user_id`,MAX(`paid_date`) AS `date` FROM `".FEES."` WHERE `paid_date` IS NOT NULL AND `paid` = 1 AND `withdrawn` != 1 GROUP BY `user_id`) AS `dates` ON `".USERS."`.id = `dates`.user_id
    WHERE `date` > DATE_SUB(NOW(), INTERVAL $sfilter DAY) AND `is_active` = 1 AND `nickname` REGEXP '^$letter'");
	
	$row = mysql_fetch_row($rt);
	$users = intval($row[0]);
}
//SELECT `id`, `nickname`,DATE_FORMAT(`added`, '%m/%d/%Y') AS `joined`, `budget`,
$cPages = ceil($users/$limit); 

if( $active == 'FALSE' ) {
    $query = "
    SELECT `id`, `nickname`,TIMESTAMPDIFF(SECOND,`added`,NOW()) AS `joined`, `budget`,
    IFNULL(`creators`.`count`,0) + IFNULL(`mechanics`.`count`,0) AS `jobs_count`, 
    IFNULL(`earnings`.`sum`,0) AS `earnings`,
    IFNULL(`earnings30`.`sum`,0) AS `earnings30`,
    IFNULL(`rewarder`.`sum`,0)AS `rewarder`
    FROM `".USERS."` 
    LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `".WORKLIST."` WHERE (`status`!='SUGGESTED' AND `status`!='PASS' AND `status`!='BUG' AND `status`!='BIDDING' AND `status`!='') GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id` 
    LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `".WORKLIST."` WHERE (`status`!='SUGGESTED' AND `status`!='PASS' AND `status`!='BUG' AND `status`!='BIDDING' AND `status`!='') AND (`creator_id` != `mechanic_id`) GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id` 
    LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE ($sfilter AND `paid` = 1 AND `withdrawn`=0) AND `rewarder`=1 OR `bonus`=1 GROUP BY `user_id`) AS `rewarder` ON `".USERS."`.`id` = `rewarder`.`user_id`
    LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 AND `expense`=0 AND `bonus`=0 AND `rewarder`=0 AND `paid` = 1 AND `paid_date` IS NOT NULL GROUP BY `user_id`) AS `earnings` ON `".USERS."`.`id` = `earnings`.`user_id`  
    LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE `withdrawn`=0 AND `paid` = 1 AND `paid_date` IS NOT NULL AND `paid_date` > DATE_SUB(NOW(), INTERVAL 30 DAY) AND `expense`=0 AND `rewarder`=0 AND `bonus`=0 GROUP BY `user_id`) AS `earnings30` ON `".USERS."`.`id` = `earnings30`.`user_id`
    LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE ($sfilter AND `withdrawn`=0 AND `paid` = 1) AND `expense`=1 GROUP BY `user_id`) AS `expenses_billed` ON `".USERS."`.`id` = `expenses_billed`.`user_id`  
    WHERE `nickname` REGEXP '^$letter' AND `is_active` = 1  ORDER BY `$order` $order_dir LIMIT " . ($page-1)*$limit . ",$limit";
}    else if( $active == 'TRUE' )    {
    $query = "
    SELECT `id`, `nickname`,TIMESTAMPDIFF(SECOND,`added`,NOW()) AS `joined`, `budget`,
    IFNULL(`creators`.`count`,0) + IFNULL(`mechanics`.`count`,0) AS `jobs_count`,
    IFNULL(`earnings`.`sum`,0) AS `earnings`,
    IFNULL(`earnings30`.`sum`,0) AS `earnings30`,
    IFNULL(`rewarder`.`sum`,0)AS `rewarder`
    FROM `".USERS."` 
    LEFT JOIN (SELECT `user_id`,MAX(`date`) AS `date` FROM `".FEES."` WHERE `paid` = 1 AND `amount` != 0 AND `withdrawn` = 0 AND `expense` = 0 GROUP BY `user_id`) AS `dates` ON `".USERS."`.id = `dates`.user_id
    LEFT JOIN (SELECT `mechanic_id`, COUNT(`mechanic_id`) AS `count` FROM `".WORKLIST."` WHERE (`status`!='SUGGESTED' AND `status`!='PASS' AND `status`!='BUG' AND `status`!='BIDDING'  AND `status`!='') GROUP BY `mechanic_id`) AS `mechanics` ON `".USERS."`.`id` = `mechanics`.`mechanic_id` 
    LEFT JOIN (SELECT `creator_id`, COUNT(`creator_id`) AS `count` FROM `".WORKLIST."` WHERE (`status`!='SUGGESTED' AND `status`!='PASS' AND `status`!='BUG' AND `status`!='BIDDING' AND `status`!='') AND `creator_id` != `mechanic_id` GROUP BY `creator_id`) AS `creators` ON `".USERS."`.`id` = `creators`.`creator_id` 
    LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE ($sfilter AND `paid` = 1 AND `withdrawn`=0) AND `rewarder`=1 OR `bonus`= 1 GROUP BY `user_id`) AS `rewarder` ON `".USERS."`.`id` = `rewarder`.`user_id`
    LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE $sfilter AND `withdrawn`=0 AND `expense`=0 AND `bonus`=0 AND `rewarder`=0 AND `paid` = 1 AND `paid_date` IS NOT NULL GROUP BY `user_id`) AS `earnings` ON `".USERS."`.`id` = `earnings`.`user_id`
    LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE `withdrawn`=0 AND `paid` = 1 AND `paid_date` IS NOT NULL AND `paid_date` > DATE_SUB(NOW(), INTERVAL 30 DAY) AND `expense`=0 AND `rewarder`=0 AND `bonus`=0 GROUP BY `user_id`) AS `earnings30` ON `".USERS."`.`id` = `earnings30`.`user_id` 
    LEFT JOIN (SELECT `user_id`, SUM(amount) AS `sum` FROM `".FEES."` WHERE ($sfilter AND `withdrawn`=0 AND `paid` = 1) AND `expense`=1 GROUP BY `user_id`) AS `expenses_billed` ON `".USERS."`.`id` = `expenses_billed`.`user_id`  
    WHERE `date` > DATE_SUB(NOW(), INTERVAL $sfilter DAY) AND `nickname` REGEXP '^$letter' AND `is_active` = 1  ORDER BY `$order` $order_dir LIMIT " . ($page-1)*$limit . ",$limit";
}
$rt = mysql_query($query);

// Construct json for pagination
$userlist = array(array($users, $page, $cPages));

while($row = mysql_fetch_assoc($rt)){
    $userStats = new UserStats($row['id']);
    
	$user = new User();
	$user->findUserById($row['id']);
    if ($row['budget'] < 1){
	    $row['budget'] = 'NONE';
	    } else {
	    $row['budget'] = '$'.number_format($user->getRemainingFunds(), 0);	
	    }
	    $row['earnings'] = $userStats->getTotalEarnings();
    $userlist[] = $row;
}
                      
$json = json_encode($userlist);
echo $json;     
