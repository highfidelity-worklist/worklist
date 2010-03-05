<?php 

//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

ob_start();

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("functions.php");

if (!checkReferer()) die;

$q = mysql_real_escape_string(strtolower($_GET["q"]));
$byuser = empty($_GET["nnonly"]) ? "or lower(username) like '$q%'" : "";

if (!$q) return;
$isemail = (strpos($q, '@') !== false);
$limit = intval($_GET["limit"]);
if (empty($limit)) $limit = 8;

$con=mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME,$con);

$query = "select distinct(nickname) from ".USERS." where lower(nickname) like '$q%' $byuser".
         "order by nickname asc limit ".$limit;
$result = mysql_query($query);

$data = array();
while ($result && $row=mysql_fetch_assoc($result)) {
    $data[] = $row['nickname'];
}

for ($i = 0; $i < $limit && $i < count($data); $i++) {
    echo $data[$i]."\n";
}

?>
