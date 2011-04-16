<?php 
//  Copyright (c) 2011, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

ob_start();

include("config.php");
include("class.session_handler.php");
include("functions.php");
include("check_session.php");

$con=mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME,$con);

$query = "SELECT `skill` FROM ".SKILLS." ORDER BY skill";
$result = mysql_query($query);

$data = array();
while ($result && $row=mysql_fetch_assoc($result)) {
    $data[] = $row['skill'];
}

echo json_encode($data);