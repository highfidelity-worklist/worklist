<?php
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

    function checkReferer() {
        $len = strlen(SERVER_NAME);
        if (   empty($_SERVER['HTTP_REFERER'])
            || (   substr($_SERVER['HTTP_REFERER'], 0, $len + 7) != 'http://'.SERVER_NAME
                && substr($_SERVER['HTTP_REFERER'], 0, $len + 8) != 'https://'.SERVER_NAME)) {
            return false;
        } else {
            return true;
        }
    }

    function getNickName($username) {
        static $map = array();
        if (!isset($map[$username])) {
            $strSQL = "select nickname from ".USERS." where username='".$username."'";
            $result = mysql_query($strSQL);
            $row    = mysql_fetch_array($result);
            $map[$username] = $row['nickname'];
        }
        return $map[$username];
    }

    /* initSessionData
     *
     * Initializes the session data for a user.  Takes as input either a username or a an array containing
     * data from a row in the users table.
     */
    function initSessionData($user) {
        if (!is_array($user)) {
            $res = mysql_query("select * from ".USERS." where username='".mysql_real_escape_string($user)."'");
            $user_row = (($res) ? mysql_fetch_assoc($res) : null);
            if (empty($user_row)) return;
        } else {
            $user_row = $user;
        }

        $_SESSION['username']           = $user_row['username'];
        $_SESSION['userid']             = $user_row['id'];
        $_SESSION['confirm_string']     = $user_row['confirm_string'];
        $_SESSION['nickname']           = $user_row['nickname'];
        $_SESSION['features']           = intval($user_row['features']) & FEATURE_USER_MASK;
    }

    function isEnabled($features) {
        if (empty($_SESSION['features']) || ($_SESSION['features'] & $features) != $features) {
            return false;
        } else {
            return true;
        }
    }

    function isSuperAdmin() {
        if (empty($_SESSION['features']) || ($_SESSION['features'] & FEATURE_SUPER_ADMIN) != FEATURE_SUPER_ADMIN) {
            return false;
        } else {
            return true;
        }
    }

    //creates new activated user from email(username) and nickname
    function simpleCreateUser($username, $nickname){
        $confirm_string = rand();
        $password = generatePassword();
        $res = mysql_query("INSERT INTO `".USERS."` ( `username`, `password`, `added`, `nickname`, `confirm`, `confirm_string` ) ".
	    "VALUES ('".mysql_real_escape_string($username)."', '".sha1(mysql_real_escape_string($password))."', NOW(), '".
	    mysql_real_escape_string($nickname)."',
	    1, '$confirm_string' )");
        return array("user_id" => mysql_insert_id(), "password" => $password);
    }

    function generatePassword ($length = 8)
    {
        $password = "";
        $possible = "0123456789bcdfghjkmnpqrstvwxyz"; 
        $i = 0; 
        // add random characters to $password until $length is reached
        while ($i < $length) { 
            // pick a random character from the possible ones
            $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
            // we don't want this character if it's already in the password
            if (!strstr($password, $char)) { 
	        $password .= $char;
	        $i++;
            }
        }
        return $password;
    }

    function postRequest($url, $post_data) {
        if (!function_exists('curl_init')) {
            error_log('Curl is not enabled.');
            return 'error: curl is not enabled.';
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
?>
