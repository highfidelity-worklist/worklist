<?php
require_once ('config.php');
require_once('class/Session.class.php');
require_once('class/Utils.class.php');

if(! isset($_REQUEST["api_key"])){
    die("No api key defined.");
} else if(strcmp($_REQUEST["api_key"],API_KEY) != 0)
{
    die("Wrong api key provided.");
} else if(!isset($_SERVER['HTTPS'])){
    die("Only HTTPS connection is accepted.");
} else if($_SERVER["REQUEST_METHOD"] != "POST"){
    die("Only POST method is allowed.");
} else if(!empty($_REQUEST['action'])){
    mysql_connect (DB_SERVER, DB_USER, DB_PASSWORD);
    mysql_select_db (DB_NAME);
    switch($_REQUEST['action']){
        case 'updateuser':
            updateuser();
            break;
        case 'pushVerifyUser':
            pushVerifyUser();
            break;
        case 'login':
            loginUserIntoSession();
            break;
        default:
            die("Invalid action.");
    }
}

/*
* Setting session variables for the user so he is logged in
*/
function loginUserIntoSession(){
    $user_id = intval($_REQUEST['user_id']);
    $username = $_REQUEST['username'];
    $nickname = $_REQUEST['nickname'];
    $admin = $_REQUEST['admin'];

    $session_id = $_REQUEST['session_id'];
    session_id($session_id);
    session::init();
    Utils::setUserSession($user_id, $username, $nickname, $admin);
}

function updateuser(){
    $sql = "UPDATE ".USERS." ".
           "SET ";
    $id = (int)$_REQUEST["user_id"];
    foreach($_REQUEST["user_data"] as $key => $value){
        $sql .= $key." = '".mysql_real_escape_string($value)."', ";
    }
    $sql = substr($sql,0,(strlen($sql) - 1));
    $sql .= " ".
            "WHERE id = ".$id;
    mysql_query($sql); 
}

function pushVerifyUser(){
    $user_id = intval($_REQUEST['id']);
    $sql = "UPDATE " . USERS . " SET `confirm` = '1', is_active = '1' WHERE `id` = $user_id";
    mysql_unbuffered_query($sql);
    
    respond(array('success' => false, 'message' => 'User has been confirmed!'));
}

function respond($val){
    exit(json_encode($val));
}
