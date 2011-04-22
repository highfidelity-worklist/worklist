<?php
require_once ("class/CURLHandler.php");

class Utils{

    public static $keys = array(
        "about",
        "contactway",
        "payway",
        "skills",
        "timezone",
        "is_uscitizen",
        "has_w9approval",
        "phone",
        "smsaddr",
        "country",
		"city",
        "provider",
        "paypal_email",
        "sms_flags",
        "findus", 
        "int_code",
        "notifications"
    );
    public static function registerKey($key){
        return in_array($key, self::$keys);
    }

    public static function checkForNewUser($user_id){

        // check if user is in out database
        $query = "SELECT `id` FROM " . USERS . " WHERE id='" . intval($_SESSION['userid']) . "'";
        $res = mysql_query($query);

        // empty result
        if (!mysql_num_rows($res) > 0){
            return true;
        }
        return false;
    }

    public static function setUserSession($id, $username, $nickname, $admin){
        $_SESSION["userid"]   = $id;
        $_SESSION["username"] = $username;
        $_SESSION["nickname"] = $nickname;
        $_SESSION["admin"]    = $admin;
        $_SESSION["new_user"] = self::checkForNewUser($id);
        // user just logged  in, let's update the last seen date in session
        // date will be checked against db in initUserById
        $_SESSION['last_seen'] = date('Y-m-d');
    }

    public static function updateLoginData($data, $update_nickname = true, $update_password = true){

        $params = array("action" => "update", "user_data" => array("userid" => $_SESSION['userid']));
        if($update_nickname){
            $params["user_data"]["nickname"] = $data["nickname"];
        }
        if($update_password){
            $params["user_data"]["newpassword"] = $data["newpassword"];
            $params["user_data"]["oldpassword"] = $data["oldpassword"];
        }
        $params["sid"] = session_id();
        
        ob_start();
        // send the request
        CURLHandler::Post(SERVER_URL . 'loginApi.php', $params, false, true);
        $result = ob_get_contents();
        ob_end_clean();
        return json_decode($result);
    }
}
