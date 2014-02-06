<?php
/**
 * vim:ts=4:et
 * 
 * Copyright (c) 2013, CoffeeandPower Inc.
 * All Rights Reserved. 
 *
 * http://www.worklist.net
 */

include('config.php');
require_once('helper/checkJournal_session.php');

Session::check();

if(!empty($_REQUEST['unblockIp'])) {
	$sql="DELETE FROM " . BLOCKED_IP . " WHERE ipv4='" . $_REQUEST['unblockIp'] . "';";
	$result = mysql_query($sql);	
}
$is_runner = !empty($_SESSION['is_runner']) ? $_SESSION['is_runner'] : 0;

if(!$is_runner) { die("You're not allowed to access this page");}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Worklist - Manage Blocked IPs</title>
</head>

<body>
<h1>Manage Blocked IPs</h1>
<?php

$sql="SELECT * FROM ".BLOCKED_IP;
$result = mysql_query($sql);
?>
<table border="1" cellpadding="10">
<thead>
<tr><th>#</th><th>IP</th><th>Action</th></tr>
</thead>
<tbody>
<?php
$i=1;
while ($row = mysql_fetch_assoc($result)) {
?>
	<tr>
    	<td>
        	<?php echo $i++; ?>
        </td>
    	<td>
        	<?php echo $row['ipv4']; ?>
        </td>
        <td>
        	<a href="<?php echo SERVER_URL;?>blocked.php?unblockIp=<?php echo urlencode($row['ipv4']); ?>">Unblock</a>
        </td>
    </tr>
<?php
}
?>
</tbody>
</table>
</body>
</html>
