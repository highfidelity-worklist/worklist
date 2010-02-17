<?php
if (isset($_REQUEST['q']) && !empty($_REQUEST['q'])) {
	include("config.php");
	include("class.session_handler.php");
	include_once("functions.php");
	include_once("send_email.php");
	
	$return = array(
		'success' => false,
		'data' => array()
	);
	
    $q = mysql_real_escape_string($_REQUEST['q']);
    $result = mysql_query('SELECT `id`, `nickname` FROM `' . USERS . '`' . 
    					  'WHERE `nickname` LIKE "%' . $q . '%" ' . 
    					  'ORDER BY `nickname` ASC;');
    
    if ($result && (mysql_num_rows($result) > 0)) {
    	$return['success'] = true;
    	
	    while ($row = mysql_fetch_assoc($result)) {
	    	echo $row['nickname'] . "\n";
	    }
    }
    
    die();
}
?>
