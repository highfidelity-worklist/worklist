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
?>
