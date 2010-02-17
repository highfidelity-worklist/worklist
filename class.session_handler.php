<?php
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

define('PS_DELIMITER', '|');
define('PS_UNDEF_MARKER', '!');

function session_real_decode($str)
{
  $str = (string)$str;

  $endptr = strlen($str);
  $p = 0;

  $serialized = '';
  $items = 0;
  $level = 0;

  while ($p < $endptr) {
    $q = $p;
    while ($str[$q] != PS_DELIMITER)
      if (++$q >= $endptr) break 2;

    if ($str[$p] == PS_UNDEF_MARKER) {
      $p++;
      $has_value = false;
    } else {
      $has_value = true;
    }

    $name = substr($str, $p, $q - $p);
    $q++;

    $serialized .= 's:' . strlen($name) . ':"' . $name . '";';

    if ($has_value) {
      for (;;) {
	$p = $q;
	switch ($str[$q]) {
	case 'N': /* null */
	case 'b': /* boolean */
	case 'i': /* integer */
	case 'd': /* decimal */
	  do $q++;
	  while ( ($q < $endptr) && ($str[$q] != ';') );
	  $q++;
	  $serialized .= substr($str, $p, $q - $p);
	  if ($level == 0) break 2;
	  break;
	case 'R': /* reference  */
	  $q+= 2;
	  for ($id = ''; ($q < $endptr) && ($str[$q] != ';'); $q++) $id .= $str[$q];
	  $q++;
	  $serialized .= 'R:' . ($id + 1) . ';'; /* increment pointer because of outer array */
	  if ($level == 0) break 2;
	  break;
	case 's': /* string */
	  $q+=2;
	  for ($length=''; ($q < $endptr) && ($str[$q] != ':'); $q++) $length .= $str[$q];
	  $q+=2;
	  $q+= (int)$length + 2;
	  $serialized .= substr($str, $p, $q - $p);
	  if ($level == 0) break 2;
	  break;
	case 'a': /* array */
	case 'O': /* object */
	  do $q++;
	  while ( ($q < $endptr) && ($str[$q] != '{') );
	  $q++;
	  $level++;
	  $serialized .= substr($str, $p, $q - $p);
	  break;
	case '}': /* end of array|object */
	  $q++;
	  $serialized .= substr($str, $p, $q - $p);
	  if (--$level == 0) break 2;
	  break;
	default:
	  return false;
	}
      }
    } else {
      $serialized .= 'N;';
      $q+= 2;
    }
    $items++;
    $p = $q;
  }
  return @unserialize( 'a:' . $items . ':{' . $serialized . '}' );
}

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
