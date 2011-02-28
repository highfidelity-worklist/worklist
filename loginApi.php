<?php
error_log("loginApi.php 1");
require_once ("config.php");
error_log("loginApi.php 2");
require_once ("class/Login.class.php");

error_log("loginApi.php 3");
$response = new Response();
error_log("loginApi.php 4");
if(isset($_REQUEST["action"])){
error_log("loginApi.php 5");
    $login = new Login();
error_log("loginApi.php 6");
    $login->setResponse($response);
error_log("loginApi.php 7");
    $action = $_REQUEST["action"];
    switch($action){
        case "login":
error_log("loginApi.php 8");
            $login->loginrequest();
            break;
        case "signup":
error_log("loginApi.php 9");
            $login->signup();
            break;
        case "getuserdata":
error_log("loginApi.php 10");
            $login->getUserData();
            break;
        case "setuserdata":
error_log("loginApi.php 11");
            $login->setUserData();
            break;
        case "resetuserpassword":
error_log("loginApi.php 12");
            $login->resetUserPassword();
            break;
        case "update":
error_log("loginApi.php 13");
            $login->update();
            break;
        default:
error_log("loginApi.php 14");
            $response->getError()->setError("Invalid action called.");
            break;
    }
error_log("loginApi.php 15");
    $response->sendResponse();
}else{
error_log("loginApi.php 16");
    $response->getError()->setError("There is an error in your request.");
    $response->sendResponse();
}
