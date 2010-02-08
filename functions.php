<?php
//  vim:ts=4:et

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

    function getWorkItemSummary($itemid) {
        $query = "select summary from ".WORKLIST." where id='$itemid'";
        $rt = mysql_query($query);
        if ($rt) {
            $row = mysql_fetch_assoc($rt);
            $summary = $row['summary'];    
        }
        return $summary;
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
        $_SESSION['timezone']           = $user_row['timezone'];
        $_SESSION['is_runner']          = intval($user_row['is_runner']);
        $_SESSION['is_payer']           = intval($user_row['is_payer']);
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


    /*    Function: GetUserList
     *
     *     Purpose: This function return a list of confirmed users.
     *
     *  Parameters: userid - The userid of the user signed in.
     *              nickname - The nickname of the user signed in.
     *
     */
    function GetUserList($userid, $nickname)
    {
      $rt = mysql_query("SELECT `id`, `nickname` FROM `users` WHERE `id`!='{$userid}' and `confirm`='1' ORDER BY `nickname`");

      $user_array = array();
      if($userid != '')
      {
	$user_array[] = array('userid' => $userid,
			      'nickname' => $nickname);
      }

      while ($row = mysql_fetch_assoc($rt))
      {
	$user_array[] = array('userid' => $row['id'],
			      'nickname' => $row['nickname']);
      }

      return $user_array;
    }


    /* DisplayFilter
     *
     *      Purpose:  This function outputs the desired filter with the currently
     *                active filter (session variable) selected.
     *
     *   Parameters:  $filter_name [sfilter,ufilter]
     */
    function DisplayFilter($filter_name)
    {
      $status_array = array('ALL', 'WORKING','BIDDING', 'SKIP', 'UNPAID', 'DONE');

      if($filter_name == 'sfilter')
      {
	echo "<select name='{$filter_name}' id='search-filter'>\n";
	foreach($status_array as $key => $status)
	{
	  echo "  <option value='{$status}'";
	  if($_SESSION[$filter_name] == $status)
	  {
	    echo " selected='selected'>";
	  }
	  else
	  {
	    echo ">";
	  }
	  echo "{$status}</option>\n";
	}
	echo "</select>";
      }
      
      if($filter_name == 'ufilter')
      {
	echo "<select name='{$filter_name}' id='user-filter'>\n";
	if($_SESSION[$filter_name] == 'ALL')
	{
	  echo "  <option value='ALL' selected='selected'>ALL USERS</option>\n";
	}
	else
	{
	  echo "  <option value='ALL'>ALL USERS</option>\n";
	}
	
	$user_array = GetUserList($_SESSION['userid'], $_SESSION['nickname']);

	foreach($user_array as $user_record)
	{
	  if($_SESSION[$filter_name] == $user_record['userid'])
	  {
	    echo "<option value='{$user_record['userid']}' selected='selected'>{$user_record['nickname']}</option>";
	  }
	  else
	  {
	    echo "<option value='{$user_record['userid']}'>{$user_record['nickname']}</option>";
	  }
	}

	echo "</select>";
      }
    }

    /* postRequest
     *
     * Function for performing a CURL request given an url and post data.
     * Returns the results.
     */
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

   //converts unix timestamp to user's time according to his timezone settings
  function getUserTime($timestamp){
    $tz_correction = $_SESSION['timezone'];  
    if(strpos($_SESSION['timezone'], "+") === 0){
      $tz_correction = "-".substr($_SESSION['timezone'],1);
    }elseif(strpos($_SESSION['timezone'], "-") === 0){
      $tz_correction = "+".substr($_SESSION['timezone'],1);
    }

    $server_tz = date_default_timezone_get();
    date_default_timezone_set  ("Europe/London");
    $userTime = date("m/d/Y h:i a", strtotime(date("Y-m-d H:i", $timestamp)." ".$tz_correction));
    date_default_timezone_set  ($server_tz);
    return $userTime;
    }

    /*    Function: AddFee
     *
     *     Purpose: This function inserts 
     *
     *  Parameters:     itemid - id of the worklist entry
     *              fee_amount - amount of the fee
     *                fee_desc - description of the fee entry
     *             mechanic_id - userid of the mechanic
     *
     */
    function AddFee($itemid, $fee_amount, $fee_desc, $mechanic_id)
    {
      // Get work item summary
      $query = "select summary from ".WORKLIST." where id='$itemid'";
      $rt = mysql_query($query);
      if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $summary = $row['summary'];    
      }
 
      $query = "INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `user_id`, `desc`, `date`, `paid`) VALUES (NULL, '$itemid', '$fee_amount', '$mechanic_id', '$fee_desc', NOW(), '0')";
      $result = mysql_unbuffered_query($query);
      
      // Journal notification
      if($mechanic_id == $_SESSION['userid'])
      {
	$journal_message = $_SESSION['nickname'] . " added a fee of $fee_amount to $summary. ";
      }
      else
      {
	// Get the mechanic's nickname
	$rt = mysql_query("select nickname from ".USERS." where id='{$mechanic_id}'");
	if ($rt) {
	  $row = mysql_fetch_assoc($rt);
	  $nickname = $row['nickname'];    
	}
	else
	{
	  $nickname = "unknown-{$mechanic_id}";
	}
	
	$journal_message = $_SESSION['nickname'] . " on behalf of {$nickname} added a fee of $fee_amount to $summary. ";
      }

      return $journal_message;
    }
?>
