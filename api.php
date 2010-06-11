<?php
require_once ('config.php');
require('class/Session.class.php');

if(! isset($_REQUEST["api_key"])){
    die("No api key defined.");
} else if(strcmp($_REQUEST["api_key"],API_KEY) != 0)
{
    die("Wrong api key provided.");
} else if($_SERVER["https"] != "on"){
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
        case 'pushCreateUser':
            pushCreateUser();
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
    $session_id = $_REQUEST['session_id'];

    // getting user data from database
    $sql = "SELECT `username`, `nickname` FROM " . USERS . " WHERE `id`=" . $user_id;
    $result = mysql_query($sql);
    if($result && $user = mysql_fetch_object($result)){
        $username = $user->username;
        $nickname = $user->nickname;
    }

    session_id($session_id);
    session::init();
    $_SESSION["userid"] = $user_id;

    $_SESSION["username"] = $username;
    $_SESSION["nickname"] = $nickname;
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

function pushCreateUser(){
    if($_REQUEST['calling_app'] != SERVICE_NAME){
        $user_id = intval($_REQUEST['id']);
        $username = mysql_real_escape_string($_REQUEST['username']);
        $nickname = mysql_real_escape_string($_REQUEST['nickname']);
        
        $sql = "INSERT INTO " . USERS . " " . "(`id`, `username`, `nickname`) " . "VALUES ('" . $user_id . "', '" . $username . "', '" . $nickname . "')";
        mysql_unbuffered_query($sql);
    }
    
    respond(array('success' => true, 'message' => 'User has been created!'));
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
