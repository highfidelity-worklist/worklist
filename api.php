<?php
require_once ('config.php');
require_once('class/Session.class.php');
require_once('class/Utils.class.php');
require_once('class/Database.class.php');
if (!defined("ALL_ASSETS"))      define("ALL_ASSETS", "all_assets");

if(! isset($_REQUEST["api_key"])){
    die("No api key defined.");
} else if(strcmp($_REQUEST["api_key"],API_KEY) != 0)
{
    die("Wrong api key provided.");
} else if(!isset($_SERVER['HTTPS']) && ($_REQUEST['action'] != 'uploadProfilePicture')){
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
        case 'uploadProfilePicture':
            uploadProfilePicture();
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

function uploadProfilePicture() {
	// check if we have a file
	if (empty($_FILES)) {
		respond(array(
			'success' => false,
			'message' => 'No file uploaded!'
		));
	}
	
	if (empty($_REQUEST['userid'])) {
		respond(array(
			'success' => false,
			'message' => 'No user ID set!'
		));
	}

     $ext = end(explode(".", $_FILES['profile']['name']));
     $tempFile = $_FILES['profile']['tmp_name'];
     $path = UPLOAD_PATH. '/' . $_REQUEST['userid'] . '.' . $ext;

     if (move_uploaded_file($tempFile, $path)) {
        $imgName = strtolower($_REQUEST['userid'] . '.' . $ext);
     	$query = 'UPDATE `'.USERS.'` SET `picture` = "' . mysql_real_escape_string($imgName) . '" WHERE `id` = ' . (int)$_REQUEST['userid'] . ' LIMIT 1;';
     	if (!mysql_query($query)) {
     		respond(array(
     			'success' => false,
     			'message' => SL_DB_FAILURE
     		));
     	} else {
     	       $file = $path;
     	       $rc = null;
               $type = null;
               if ($ext == "JPG" || $ext == "jpg" || $ext == "JPEG" || $ext == "jpeg") {
                    $rc = imagecreatefromjpeg($file);
                    $type = "image/jpeg";
               } else if ($ext == "GIF" || $ext == "gif") {
                    $rc = imagecreatefromgif($file);
                    $type = "image/gif";
               } else if ($ext == "PNG" || $ext == "png") {
                    $rc = imagecreatefrompng($file);
                    $type = "image/png";
               }
               
               // Get original width and height
               $width = imagesx($rc);
               $height = imagesy($rc);
               $cont = addslashes(fread(fopen($file,"r"),filesize($file)));
               $size = filesize($file);
               $sql = "INSERT INTO " . ALL_ASSETS . " (`app`, `content_type`, `content`, `size`, `filename`,`created`, `width`, `height`) " . "VALUES('".WORKLIST."','" . $type . "','" . $cont . "','" . $size . "','" . $imgName . "',NOW()," . $width . "," . $height . ") ".
                      "ON DUPLICATE KEY UPDATE content_type = '".$type."', content = '".$cont."', size = '".$size."', updated = NOW(), width = ".$width.", height = ".$height;
               
               $db = new Database();
               if (! $db->query($sql)) {
                    unlink($file);
                    respond(array('success' => false, 'message' => "Error with: " . $file . " Error message: " . $db->getError()));
               } else {
                    unlink($file);
                    respond(array(
                    	'success' => true, 
                    	'picture' => $imgName
                    ));
               }
     	}
     } else {
     	respond(array(
     		'success' => false,
     		'message' => 'An error occured while uploading the file, please try again!'
     	));
     }
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
