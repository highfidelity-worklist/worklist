<?php
$app_root_path = dirname(__FILE__);
$app_root_path = str_replace('helper','',$app_root_path);
require_once($app_root_path."config.php");
require_once($app_root_path."class.session_handler.php");

if(isset($_SESSION["userid"])){
     echo json_encode(array("reload" => "1"));
     die();
} else {
     echo json_encode(array("reload" => "0"));
     die();
}
