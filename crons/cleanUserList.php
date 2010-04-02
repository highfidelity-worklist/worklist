<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

ini_set('display_errors', 1);
error_reporting(-1);

ob_start();

require_once(dirname(dirname(__FILE__)).'/config.php');

$con = mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
if (!$con) {
	die('Could not connect: ' . mysql_error());
}
mysql_select_db(DB_NAME, $con);

$sql_delete = "SELECT `id`, `username`, `nickname` FROM `users` WHERE `added` < DATE_SUB(NOW(), INTERVAL 45 DAY) AND (SELECT COUNT(*) FROM `bids` WHERE `bidder_id` = `users`.`id`) = 0 AND (SELECT COUNT(*) FROM `fees` WHERE `user_id` = `users`.`id`) = 0 AND `is_runner` = 0 AND `is_active` != 2;";

$sql_deactivate = "SELECT `id` FROM `users` WHERE (SELECT COUNT(*) FROM `fees` WHERE `user_id` = `users`.`id` AND `paid` = 1 AND (`paid_date` < DATE_SUB(NOW(), INTERVAL 45 DAY) OR `date` < DATE_SUB(NOW(), INTERVAL 45 DAY))) > 0 AND (SELECT COUNT(*) FROM `fees` WHERE `user_id` = `users`.`id` AND `paid` = 1 AND (`paid_date` > DATE_SUB(NOW(), INTERVAL 45 DAY) OR `date` > DATE_SUB(NOW(), INTERVAL 45 DAY))) = 0;";

// Delete accounts which exists for at least 45 days and never have been used.
$result = mysql_query($sql_delete);
while ($row = mysql_fetch_assoc($result)) {
	$sql = "DELETE * FROM `users` WHERE `id` = " . $row['id'] . ";";
	#mysql_unbuffered_query($sql);
}
mysql_free_result($result);

// Deactivate accounts which have been used but not for at least 45 days (paid date) or 55 days (fee date)
$result = mysql_query($sql_deactivate);
while ($row = mysql_fetch_assoc($result)) {
	$sql = "UPDATE `users` SET `is_active` = 0 WHERE `id` = " . $row['id'] . ";";
	#mysql_unbuffered_query($sql);
}
mysql_free_result($result);

mysql_close($con);
