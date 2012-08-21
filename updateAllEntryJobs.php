<?php
//  vim:ts=4:et
include('config.php');
require_once("class.session_handler.php");
require_once('helper/checkJournal_session.php');
require_once('chat.class.php');
require_once('functions.php');
require_once('classes/AjaxResponse.class.php');

$response = new AjaxResponse($chat);
try
{
	$data = $response->updateAllEntryJobs();
}
catch(Exception $e)
{
	$data['error'] = $e->getMessage();
}

echo $data;
?>
