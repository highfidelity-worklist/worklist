<?php
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

class session {
    // session-lifetime
    var $lifeTime;
    // mysql-handle
    var $dbHandle;

    static $objSession = null;

    static function init() {
        if (self::$objSession != null) {
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time()-42000, '/');
            }
            session_destroy();
        }

        self::$objSession = new session();
        session_set_save_handler(array(&self::$objSession,"open"),
                                 array(&self::$objSession,"close"),
                                 array(&self::$objSession,"read"),
                                 array(&self::$objSession,"write"),
                                 array(&self::$objSession,"destroy"),
                                 array(&self::$objSession,"gc")); 
        session_set_cookie_params(SESSION_EXPIRE);
        session_start();
    }

    function open($savePath, $sessName) {
       // get session-lifetime
       $this->lifeTime = SESSION_EXPIRE;
       // open database-connection
       $dbHandle = @mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
       $dbSel = @mysql_select_db(DB_NAME, $dbHandle);
       // return success
       if(!$dbHandle || !$dbSel)
           return false;
       $this->dbHandle = $dbHandle;
       return true;
    }
    function close() {
        $this->gc(SESSION_EXPIRE);
        // close database-connection
        return @mysql_close($this->dbHandle);
    }
    function read($sessID) {
        // fetch session-data
        $res = mysql_query("SELECT session_data AS d FROM ws_sessions
                            WHERE session_id = '$sessID'
                            AND session_expires > ".time(),$this->dbHandle);
        // return data or an empty string at failure
        if($res && $row = mysql_fetch_assoc($res))
            return $row['d'];
        return "";
    }
    function write($sessID,$sessData) {
        // new session-expire-time
        $newExp = time() + $this->lifeTime;
        // is a session with this id in the database?
        $res = mysql_query("SELECT * FROM ws_sessions
                            WHERE session_id = '$sessID'",$this->dbHandle);
        // if yes,
        if($res && mysql_num_rows($res)) {
            // ...update session-data
            mysql_query("UPDATE ws_sessions
                         SET session_expires = '$newExp',
                         session_data = '$sessData'
                         WHERE session_id = '$sessID'",$this->dbHandle);
            // if something happened, return true
            if(mysql_affected_rows($this->dbHandle))
                return true;
        }
        // if no session-data was found,
        else {
            // create a new row
            mysql_query("INSERT INTO ws_sessions (
                         session_id,
                         session_expires,
                         session_data)
                         VALUES(
                         '$sessID',
                         '$newExp',
                         '$sessData')",$this->dbHandle);
            // if row was created, return true
            if(mysql_affected_rows($this->dbHandle))
                return true;
        }
        // an unknown error occured
        return false;
    }
    function destroy($sessID) {
        // delete session-data
        mysql_query("DELETE FROM ws_sessions WHERE session_id = '$sessID'",$this->dbHandle);
        // if session was deleted, return true,
        if(mysql_affected_rows($this->dbHandle))
            return true;
        // ...else return false
        return false;
    }
    function gc($sessMaxLifeTime) {
        // delete old sessions
        mysql_query("DELETE FROM ws_sessions WHERE session_expires < ".time(),$this->dbHandle);
        // return affected rows
        return mysql_affected_rows($this->dbHandle);
    }
}

session::init();
?>
