<?php
require_once ("config.php");
require_once ("class/Login.class.php");

$response = new Response();

if(isset($_REQUEST["action"])){
    $login = new Login();
    $login->setResponse($response);
    $action = $_REQUEST["action"];
    switch($action){
        case "login":
            $login->loginrequest();
            break;
        case "signup":
            $login->signup();
            break;
        case "getuserdata":
            $login->getUserData();
            break;
        case "setuserdata":
            $login->setUserData();
            break;
        case "resetuserpassword":
            $login->resetUserPassword();
            break;
        case "update":
            $login->update();
            break;
        default:
            $response->getError()->setError("Invalid action called.");
            break;
    }
    $response->sendResponse();
}else{
    $response->getError()->setError("There is an error in your request.");
    $response->sendResponse();
}
