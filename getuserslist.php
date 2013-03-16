<?php 

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

//ob_start();

include("config.php");
include("functions.php");

$con=mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME,$con);

$query = "select id, nickname from ".USERS." WHERE 1=1";
if(isset($_REQUEST['startsWith']) && !empty($_REQUEST['startsWith'])) {
    $startsWith = $_REQUEST['startsWith'];
    $query .= " AND nickname like '".mysql_real_escape_string($startsWith)."%'";
}
$query .= " order by nickname limit 0,10";

$result = mysql_query($query);


$data = array();
while ($result && $row=mysql_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);

?>
