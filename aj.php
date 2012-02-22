<?php
//  vim:ts=4:et
include('config.php');
require_once("class.session_handler.php");
require_once('helper/checkJournal_session.php');
require_once('chat.class.php');
require_once('functions.php');
require_once('class/AjaxResponse.class.php');

// check for referer
if(!checkReferer()){
    die(json_encode(array('error' => 'Wrong referer')));
}

// check if we access this page from the script
if(isset($_SESSION['csrf_token']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] != $_SESSION['csrf_token']){
    die(json_encode(array('error' => 'Invalid token')));
}


$toTime = 0;
$prevNext = '';
$query = isset($_REQUEST['query']) ? $_REQUEST['query'] : '';

$response = new AjaxResponse($chat);
$action = isset($_POST['what']) ? $_POST['what'] : 'noaction';
try
{
	$data = $response->$action();
}
catch(Exception $e)
{
	$data['error'] = $e->getMessage();
}

$json = json_encode($data);
echo $json;


?>