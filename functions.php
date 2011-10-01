<?php
//  vim:ts=4:et

//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include_once('./timezones.php');

// Autoloader
function __autoload($class)
{
    $file = realpath(dirname(__FILE__) . '/classes') . "/$class.class.php";
    if (file_exists($file)) {
        require_once($file);
    }
}


/* Fee Categories
 *
 * The cannonical list of recognized fees.
 * For popup-addfee.inc (and other places)
 */
$feeCategories = array(
       'Bid', 'Code Review', 'Design Spec', 'Misc Expense', 'Management Fee');

function checkReferer() {
    $len = strlen(SERVER_NAME);
    if (   empty($_SERVER['HTTP_REFERER'])
    || (  substr($_SERVER['HTTP_REFERER'], 0, $len + 8) != 'https://'.SERVER_NAME )) {
        return false;
    } else {
        return true;
    }
}

// Get the userId from the session, or set it to 0 for Guests.
function getSessionUserId() {
	return isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
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
 *
 * NOTE: keep this function in sync with the same function in journal!!!
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

function initUserById($userid) {
    $res = mysql_query("select * from ".USERS." where id='".mysql_real_escape_string($userid)."'");
    $user_row = (($res) ? mysql_fetch_assoc($res) : null);
    if (empty($user_row)) return;

    $_SESSION['username']           = $user_row['username'];
    $_SESSION['userid']             = $user_row['id'];
    $_SESSION['confirm_string']     = $user_row['confirm_string'];
    $_SESSION['nickname']           = $user_row['nickname'];
    $_SESSION['timezone']           = $user_row['timezone'];
    $_SESSION['is_runner']          = intval($user_row['is_runner']);
    $_SESSION['is_payer']           = intval($user_row['is_payer']);
    
    // set the session variable for the inline message for new users before last seen is updated
    if ($user_row['last_seen'] === null) {
        $_SESSION['inlineHide'] = 0;
    } else {
        $_SESSION['inlineHide'] = 1;
    }

    $last_seen_db = substr($user_row['last_seen'], 0, 10);
    $today = date('Y-m-d');

    if ($last_seen_db != $today) {
        $res = mysql_query("UPDATE ".USERS." SET last_seen = NOW() WHERE id={$userid}");
    }
    $_SESSION['last_seen'] = $today;
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



/*  Function: countLoveToUser
 *
 *  Purpose: Gets the count of love sent to a user.
 *
 *  Parameters: username - The username of the desired user.
 *              fromUser - If set will get the love sent by this user.
 */
function countLove($username, $fromUsername="") {
    defineSendLoveAPI();
    //Wires off countLove to 0, ignores API (api working 5/24)
    //return array('status'=>SL_OK,'error'=>SL_NO_ERROR,array('count'=>0));
    
    if($fromUsername != "") {
        $params = array (
                'action' => 'getcount',
                'api_key' => SENDLOVE_API_KEY,
                'username' => $username,
                'fromUsername' => $fromUsername);
    } else {
        $params = array (
                'action' => 'getcount',
                'api_key' => SENDLOVE_API_KEY,
                'username' => $username);
    }
    $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (SENDLOVE_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    if ($retval['status'] == "ok") {
        return $retval['data']['count'];
    } else {
        return -1;
    }
}

/*  Function: getUserLove
 *
 *  Purpose: Get Love sent to the user
 *
 *  Parameters: username - The username of the user to get love from.
 *              fromUsername - If set it will filter to the love sent by this username.
 */
function getUserLove($username, $fromUsername="") {
    defineSendLoveAPI();
    //Wires off getUserLove to 0, ignores API (api working 5/24)
    //return array('status'=>SL_OK,'error'=>SL_NO_ERROR,array('count'=>0));
	
    if($fromUsername != "") {
		$params = array (
		        'action' => 'getlove',
		        'api_key' => SENDLOVE_API_KEY,
		        'username' => $username,
		        'fromUsername' => $fromUsername,
		        'pagination' => 0);
    } else {
        $params = array (
                'action' => 'getlove',
                'api_key' => SENDLOVE_API_KEY,
                'username' => $username,
                'pagination' => 0);
    }
	$referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (SENDLOVE_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);
    
    if ($retval['status'] == "ok") {
        return $retval['data'];
    } else {
        return -1;
    }
}

function defineSendLoveAPI() {
    // Sendlove API status and error codes. Keep in sync with .../sendlove/api.php
    // only define constants once
    if (!defined('SL_OK')){
        define ('SL_OK', 'ok');
        define ('SL_ERROR', 'error');
        define ('SL_WARNING', 'warning');
        define ('SL_NO_ERROR', '');
        define ('SL_NO_RESPONSE', 'no response');
        define ('SL_BAD_CALL', 'bad call');
        define ('SL_DB_FAILURE', 'db failure');
        define ('SL_UNKNOWN_USER', 'unknown user');
        define ('SL_NOT_COWORKER', 'receiver not co-worker');
        define ('SL_RATE_LIMIT', 'rate limit');
        define ('SL_SEND_FAILED', 'send failed');
        define ('SL_JOURNAL_FAILED', 'journal failed');
        define ('SL_NO_SSL', 'no ssl call');
        define ('SL_WRONG_KEY', 'wrong api key');
        define ('SL_LOVE_DISABLED', 'sendlove disabled');
    }
}

// This will be handled by Rewarder API
/*
* Populate the rewarder team automatically. It's based on who added a fee to a task you worked on in the last 30 days.
*
*
*/
 function PopulateRewarderTeam($user_id, $worklist_id = '') {
    //Wire off rewarder interface for the time being - gj 5/21/10
    // returns results of mysql update operation (success=true)
    return true;

   $where = !empty($worklist_id) ?  " f.worklist_id = $worklist_id  " : "  f.worklist_id IN (SELECT DISTINCT  f1.worklist_id FROM " . FEES . " f1 WHERE f1.user_id = $user_id and f1.rewarder = 0) ";
   $rewarder_limit_day = GetPopulateRewarderLimit($user_id);
   $rewarder_limit_day = $rewarder_limit_day == 0 ? 30 : $rewarder_limit_day;
   $where .= " AND f.paid_date BETWEEN  (NOW() - INTERVAL $rewarder_limit_day day) AND NOW() " ;
// This will be replaced with an API call
//   $sql = "INSERT INTO " . REWARDER . " (giver_id,receiver_id,rewarder_points) SELECT DISTINCT $user_id, u.id, 0 FROM " . USERS . " u INNER JOIN " . FEES . " f ON (f.user_id = u.id) WHERE  $where AND NOT EXISTS (SELECT 1 FROM " . REWARDER . " rd WHERE rd.giver_id = $user_id) AND u.id <> $user_id ";
//   mysql_query($sql);
 }

 function GetPopulateRewarderLimit($user_id) {
    //Wire off rewarder, will use API - gj 5/24/10
    //Rewarder limit/day just return 0
    return 0;

    $sql = "SELECT rewarder_limit_day FROM ". USERS . " WHERE id= $user_id ";
    $rt = mysql_query($sql);
    if($row = mysql_fetch_assoc($rt)) {
      return $row['rewarder_limit_day'];
    }
    return 0;
 }



/*  Function: GetUserList
 *
 *  Purpose: This function return a list of confirmed users.
 *
 *  Parameters: userid - The userid of the user signed in.
 *              nickname - The nickname of the user signed in.
 *              skipUser - If true, don't include the row for the user passed in.
 *              attrs - list of additional attributes to return
 */
function GetUserList($userid, $nickname, $skipUser=false, $attrs=array()) {
    if (!empty($attrs)) {
        $extra = ", `" . implode("`,`", $attrs) . "`";
    } else {
        $extra = "";
    }

    $rt = mysql_query("SELECT `id`, `nickname` $extra  FROM `".USERS."` WHERE `id`!='{$userid}' AND `confirm`='1' AND `is_active` = 1 ORDER BY `nickname`");

    $userList = array();
    if (! $skipUser && ! empty($userid) && ! empty($nickname)) {
        $skipUser = true;
        $userList[$userid] = $nickname;
    }

    while ($rt && $row = mysql_fetch_assoc($rt)) {
        if (!$skipUser || $userid != $row['id']) {
            if (empty($attrs)) {
                $userList[$row['id']] = $row['nickname'];
            } else {
                $userList[$row['id']] = array('nickname'=>$row['nickname']);
                foreach ($attrs as $attr) {
                    $userList[$row['id']][$attr] = $row[$attr];
                }
            }
        }
    }

    return $userList;
}

/* postRequest
 *
 * Function for performing a CURL request given an url and post data.
 * Returns the results.
 */
function postRequest($url, $post_data, $options = array(), $curlopt_timeout = 30) {
    if (!function_exists('curl_init')) {
        error_log('Curl is not enabled.');
        return 'error: curl is not enabled.';
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $curlopt_timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if (count($options)) {
        curl_setopt_array($ch, $options);
    }
    
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

//converts unix timestamp to user's time according to his timezone settings
function getUserTime($timestamp){
    //need a default to not spew errors when browser is not logged in
    //We should probably change logic to always has a SESSION defined (from default)
    //Determine login status by SESSION['userid'] etc
    if (!empty($_SESSION['timezone'])) {
        $tz_correction = $_SESSION['timezone'];
        if(strpos($_SESSION['timezone'], "+") === 0){
            $tz_correction = "-".substr($_SESSION['timezone'],1);
        }elseif(strpos($_SESSION['timezone'], "-") === 0){
            $tz_correction = "+".substr($_SESSION['timezone'],1);
        }
    } else { $tz_correction=0; }

    $server_tz = date_default_timezone_get();
    date_default_timezone_set  ("Europe/London");
    $userTime = date("m/d/Y h:i a", strtotime(date("Y-m-d H:i", $timestamp)." ".$tz_correction));
    date_default_timezone_set  ($server_tz);
    return $userTime;
}

// converts server time to users timzone time
function convertTimezone($timestamp){
    if (isset($_SESSION['timezone']) && !empty($_SESSION['timezone'])) {
        $time_zone_date_time = getTimeZoneDateTime($_SESSION['timezone']);
        if ($time_zone_date_time) {
            $oTz = date_default_timezone_get();
            date_default_timezone_set($time_zone_date_time);
            $new_time = date('m/d/Y h:i a', $timestamp);
            date_default_timezone_set($oTz);
            return $new_time;
        }
    }
    return date('m/d/Y h:i a', $timestamp);
}

/*    Function: AddFee
 *
 *     Purpose: This function inserts
 *
 *  Parameters:     itemid - id of the worklist entry
 *              fee_amount - amount of the fee
 *            fee_category - accounting category for the fee (Refer to $feeCategory for canonical list)
 *                fee_desc - description of the fee entry
 *             mechanic_id - userid of the mechanic
 *
 */
function AddFee($itemid, $fee_amount, $fee_category, $fee_desc, $mechanic_id, $is_expense, $is_rewarder=0)
{
	if ($is_rewarder) $is_expense = 0;
    // Get work item summary
    $query = "select summary from ".WORKLIST." where id='$itemid'";
    $rt = mysql_query($query);
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $summary = $row['summary'];
    }

    $query = "INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `category`, `user_id`, `desc`, `date`, `paid`, `expense`) ".
        "VALUES (NULL, '".(int)$itemid."', '".(float)$fee_amount."', '".(int)$fee_category."', '".(int)$mechanic_id."', '".mysql_real_escape_string($fee_desc)."', NOW(), '0', '".mysql_real_escape_string($is_expense)."' )";
    $result = mysql_unbuffered_query($query);

    // Journal notification
    if($mechanic_id == $_SESSION['userid'])
    {
        $journal_message = $_SESSION['nickname'] . " added a fee of \$$fee_amount to item #$itemid: $summary. ";
    }
    else
    {
        // Get the mechanic's nickname
        $rt = mysql_query("select nickname from ".USERS." where id='".(int)$mechanic_id."'");
        if ($rt) {
            $row = mysql_fetch_assoc($rt);
            $nickname = $row['nickname'];
        }
        else
        {
            $nickname = "unknown-{$mechanic_id}";
        }

        $journal_message = $_SESSION['nickname'] . " on behalf of {$nickname} added a fee of \$$fee_amount to item #$itemid:  $summary. ";
    }

    return $journal_message;
}

function AddTip($itemid, $tip_amount, $tip_desc, $mechanic_id) {
    // Get work item summary
    $query = "SELECT `summary` FROM " . WORKLIST. " WHERE `id` = '{$itemid}'";
    $rt = mysql_query($query);
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $summary = $row['summary'];
    }

    // get the tippee's nickname
    $rt = mysql_query("SELECT nickname FROM " . USERS . " WHERE id = '". (int) $mechanic_id . "'");
    if ($rt) {
        $row = mysql_fetch_assoc($rt);
        $nickname = $row['nickname'];
    }

    // validate
    $query = "
        SELECT * FROM " . FEES . " 
        WHERE 
            `worklist_id` = $itemid 
            AND `user_id` = " . getSessionUserId() . "
            AND `desc` = 'Accepted Bid'
            AND `withdrawn` = 0";

    $rt = mysql_query($query);
    if ($rt) {
        if (mysql_num_rows($rt) > 0) {
            $row = mysql_fetch_assoc($rt);

            // deduct the tip from the mechanic's accepted bid fee
            if ($tip_amount > 0 && $tip_amount <= $row['amount']) {
                $adjusted_amount = $row['amount'] - $tip_amount;
                // reduce the mechanic's accepted bid
                $query = "UPDATE " . FEES . " SET amount = {$adjusted_amount} WHERE id = {$row['id']}";
                mysql_query($query);
            }
            // add the tip as a fee on the job
            $tip_desc = 'Tip: ' . $tip_desc;
            $query = "INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `user_id`, `desc`, `date`, `paid`) ".
                     "VALUES (NULL, '".(int)$itemid."', '".(float)$tip_amount."', '".(int)$mechanic_id."', '".mysql_real_escape_string($tip_desc)."', NOW(), '0')";

            $result = mysql_unbuffered_query($query);
            return $_SESSION['nickname'] . " tipped $nickname on job #$itemid: $summary. ";
        }
    }
    return '';
}

function payBonusToUser($user_id, $amount, $notes) {

    $query = "INSERT INTO `".FEES."` (`id`,`worklist_id`,`payer_id`, `user_id`, `amount`, `notes`, `desc`, `date`, `bonus`,`paid`,`category`)".
             "VALUES ".
             "(NULL,0,'" . (int)$_SESSION['userid'] . "', '" . (int)$user_id . "', '" . (float)$amount . "', 'BONUS','" . mysql_real_escape_string($notes) . "', NOW(), 1, 0,0)";
    $result = mysql_unbuffered_query($query);

    if (mysql_insert_id()) {
        return true;
    } else {
        return false;
    }
}


function formatableRelativeTime($timestamp, $detailLevel = 1) {
	$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	$lengths = array("60", "60", "24", "7", "4.357", "12", "10");
	$now = time();
	if(empty($timestamp)) {
		return "Unknown time";
	}
	if($now > $timestamp) {
		$difference = $now - $timestamp;
		$tense = "";
	} else {
		$difference = $timestamp - $now;
		$tense = "from now";
	}
	if ($difference == 0) {
		return "1 second ago";
	}
	$remainders = array();
	for($j = 0; $j < count($lengths); $j++) {
		$remainders[$j] = floor(fmod($difference, $lengths[$j]));
		$difference = floor($difference / $lengths[$j]);
	}
	$difference = round($difference);
	$remainders[] = $difference;
	$string = "";
	for ($i = count($remainders) - 1; $i >= 0; $i--) {
		if ($remainders[$i]) {
			$string .= $remainders[$i] . " " . $periods[$i];
			if($remainders[$i] != 1) {
				$string .= "s";
			}
			$string .= " ";
			$detailLevel--;
			if ($detailLevel <= 0) {
				break;
			}
		}
	}
	return $string . $tense;
}

function relativeTime($time, $withIn = true, $justNow = true, $withAgo = true) {
    $secs = abs($time);
    $mins = 60;
    $hour = $mins * 60;
    $day = $hour * 24;
    $week = $day * 7;
    $month = $day * 30;
    $year = $day * 365;

    // years
    $segments = array();
    $segments['yr']   = intval($secs / $year);
    $secs %= $year;
    // month
    $segments['mnth'] = intval($secs / $month);
    $secs %= $month;
    if (!$segments['yr']) {
        $segments['day']  = intval($secs / $day);
        $secs %= $day;
        if (!$segments['mnth']) {
            $segments['hr']   = intval($secs / $hour);
            $secs %= $hour;
            if (!$segments['day']) {
                $segments['min']  = intval($secs / $mins);
                $secs %= $mins;
                if (!$segments['hr'] && !$segments['min']) {
                    $segments['sec']  = $secs;
                }
            }
        }
    }

    $relTime = '';
    foreach ($segments as $unit=>$cnt) {
        if ($segments[$unit]) {
            $relTime .= "$cnt $unit";
            if ($cnt > 1) {
                $relTime .= 's';
            }
            $relTime .= ', ';
        }
    }
    $relTime = substr($relTime, 0, -2);
    if (!empty($relTime)) {
        return ($time < 0) ? ($withAgo ? '' : '-') . ("$relTime " . ($withAgo ? 'ago' : '')) : ($withIn ? "in $relTime" : $relTime);
    } else {
        return $justNow ? 'just now' : '';
    }
}

function is_runner() {
    return !empty($_SESSION['is_runner']) ? true : false;
}

function sendJournalNotification($message) {
    
    $data = array(
        'user'    => JOURNAL_API_USER,
        'pwd'     => sha1(JOURNAL_API_PWD),
        'message' => stripslashes(strip_tags($message))
    );

    return postRequest(JOURNAL_API_URL, $data);
}

function withdrawBid($bid_id, $withdraw_reason) {
    $res = mysql_query('SELECT * FROM `' . BIDS . '` WHERE `id`='.$bid_id);
    $bid = mysql_fetch_object($res);
    
    // checking if is bidder or runner
    if (is_runner() || ($bid->bidder_id == $_SESSION['userid'])) {
        // getting the job
        $res = mysql_query('SELECT * FROM `' . WORKLIST . '` WHERE `id` = ' . $bid->worklist_id);
        $job = mysql_fetch_assoc($res);

        // additional changes if status is WORKING
        if ($job['status'] == 'WORKING'  && ($bid->accepted == 1) && (is_runner() || ($bid->bidder_id == $_SESSION['userid']))) {
            // change status of worklist item
            mysql_unbuffered_query("UPDATE `" . WORKLIST . "`
	            						SET `mechanic_id` = '0',
										`status` = 'BIDDING'
										WHERE `id` = $bid->worklist_id
										LIMIT 1 ;");
        }
        
        // set back to suggested if swb and is only bid
        $res = mysql_query('SELECT count(*) AS count_bids FROM `' . BIDS . '` WHERE `worklist_id` = ' . $job['id'] . ' AND `withdrawn` = 0');
        $bidCount = mysql_fetch_assoc($res);
        
        if ($bidCount['count_bids'] == 1 && $job['status'] == 'SUGGESTEDwithBID') {
        mysql_unbuffered_query("UPDATE `" . WORKLIST . "` SET `status` = 'SUGGESTED' WHERE `id` = $bid->worklist_id LIMIT 1 ;");
        }

        // change bid to withdrawn and set bids.accepted to 0
        mysql_unbuffered_query('UPDATE `' . BIDS . '`
	        						SET `withdrawn` = 1 , `accepted` = 0
	        						WHERE `id` = ' . $bid->id);

        // delete the fee entry for this bid
        mysql_unbuffered_query('UPDATE `' . FEES . '`
                                    SET `withdrawn` = 1
                                    WHERE `worklist_id` = ' . $bid->worklist_id . '
                                    AND `user_id` = ' . $bid->bidder_id . '
                                    AND `bid_id` = ' . $bid->id);

        // Get user
        $user = getUserById($bid->bidder_id);

        // Journal message
        $message  = 'A bid was deleted from item #' . $job['id'] . ': ';
        $message .= $job['summary'] . '.';

        // Journal notification
        sendJournalNotification($message);

        // Sending email to the bidder or runner
        $subject = "Bid: " . $job['id'] . " (" . $job['summary']. ")";
        
        if(is_runner()){
        	// Send to bidder
        	$recipient=$user;
        	$body = "<p>Your bid has been deleted from item #" . $job['id'] . " by: ".$_SESSION['nickname']."</p>";
        } else {
        	// Send to runner
        	$recipient=getUserById($job['runner_id']);
        	$body = "<p>A bid has been deleted from item #" . $job['id'] . " by: ".$_SESSION['nickname']."</p>";
        }
        	
        if(strlen($withdraw_reason)>0) {
		    // nl2br is added for proper formatting in email alert 12-MAR-2011 <webdev>
        	$body .= "<p>Reason: " .nl2br($withdraw_reason)."</p>";
        }
        
        // Copy text for sms notification
        $txtbody = $body;
        
        // Continue adding text to email body
        $item_link = SERVER_URL."workitem.php?job_id={$bid->worklist_id}&action=view";
        $body .= "<p><a href='${item_link}'>View Item</a></p>";
        $body .= "<p>If you think this has been done in error, please contact the job Runner.</p>";
        if (!send_email($recipient->username, $subject, $body)) { error_log("functions.php:withdrawlFee: send_email failed"); }
        notify_sms_by_object($recipient, $subject, "${txtbody}\n${item_link}");
        
        // Check if there are any active bids remaining
        $res = mysql_query("SELECT count(*) AS active_bids FROM `" . BIDS . "` WHERE `worklist_id` = " . $job['id'] . " AND `withdrawn` = 0 AND (NOW() < `bid_expires` OR `bid_expires`='0000-00-00 00:00:00')");
        $bids = mysql_fetch_assoc($res);

        
        if ($bids['active_bids'] < 1) {
        	// There are no active bids, so resend notifications
        	$workitem = new WorkItem();
        	$workitem->loadById($job['id']);
        	
        	Notification::statusNotify($workitem);
        }
        
    }
}

function deleteFee($fee_id) {
    $res = mysql_query('SELECT * FROM `' . FEES . '` WHERE `id`='.$fee_id);
    $fee = mysql_fetch_object($res);

    // checking if is bidder or runner
    if (is_runner() || ($fee->user_id == $_SESSION['userid'])) {
        mysql_unbuffered_query('UPDATE `' . FEES . '`
	    							SET `withdrawn` = 1
			            			WHERE `id` = ' . $fee_id);

        // Get worklist item summary
        $summary = getWorkItemSummary($fee->worklist_id);
        

        // Get user
        $user = getUserById($fee->user_id);

        // Journal message
        $message  = $_SESSION['nickname'] . ' deleted a fee from ';
        $message .= $user->nickname . ' on item #';
        $message .= $fee->worklist_id . ': ';
        $message .= $summary . '. ';

        // Journal notification
        sendJournalNotification($message);

        //sending email to the bidder
        $subject = "Fee: " . $summary;
        $body = "Your fee has been deleted by: ".$_SESSION['nickname']."</p>";
        $body .= "<p><a href=".SERVER_URL."workitem.php?job_id={$fee->worklist_id}&action=view>View Item</a></p>";
        $body .= "<p>If you think this has been done in error, please contact the job Runner.</p>";
        if (!send_email($user->username, $subject, $body)) { error_log("send_email failed"); }
        notify_sms_by_object($user, $subject, $body);
    }
}

function getUserById($id) {
    $res = mysql_query('SELECT * FROM `' . USERS . '` WHERE id = ' . $id);
    if ($res && (mysql_num_rows($res) == 1)) {
        return mysql_fetch_object($res);
    }
    return false;
}

function getUserByNickname($nickname) {
    $res = mysql_query('SELECT * FROM `' . USERS . '` WHERE `nickname` = "' . $nickname . '";');
    if ($res && (mysql_num_rows($res) == 1)) {
        return mysql_fetch_object($res);
    }
    return false;
}

function getWorklistById($id) {
    $query = "select * from ".WORKLIST." where id='$id'";
    $rt = mysql_query($query);
    if ($rt && (mysql_num_rows($rt) == 1)) {
        return mysql_fetch_assoc($rt);
    }
    return false;
}

/* invite one peorson By nickname or by email*/
function invitePerson( $invite, $item, $summary = null, $description = null) {
		// trim the whitespaces
        $invite = trim($invite);
        if (!empty($invite)) {
            // get the user by Nickname
            $user = getUserByNickname($invite);
            if ($user !== false) {
                //sending email to the invited developer
                $subject = "Invitation:  #" . $item . "," . $summary;
                $body = "<p>Hello you!</p>";
                $body .= "<p>You have been invited by " . $_SESSION['nickname'] . " at the Worklist to bid on <a href=\"" . SERVER_URL . "workitem.php?job_id=$item\">" . $summary . "</a>.</p>";
				$body .= "<p>Description:</p>";
                $body .= "<p>------------------------------</p>";
                $body .= "<p>" . $description . "</p>";
                $body .= "<p>------------------------------</p>";
                $body .= "<p>To bid on that job Just follow <a href=\"" . SERVER_URL . "workitem.php?job_id=$item\">this link</a>.</p>";
                $body .= "<p>Hope to see you soon.</p>";
                if(!send_email($user->username, $subject, $body)) { error_log("functions.php:invite: send_email failed"); }
				return true;
            } else if (validEmail($invite)) {
                //sending email to the NEW invited developer
                $subject = "Invitation:" . $summary;
                $body = "<p>Well, hello there!</p>";
                $body .= "<p>" . $_SESSION['nickname'] . " from the Worklist thought you might be interested in bidding on this job:</p>";
                $body .= "<p>Summary of the job: " . $summary . "</p>";
                $body .= "<p>Description:</p>";
                $body .= "<p>------------------------------</p>";
                $body .= "<p>" . $description . "</p>";
                $body .= "<p>------------------------------</p>";
                $body .= "<p>To bid on that job, follow the link, create an account (less than a minute) and set the price you want to be paid for completing it!</p>";
                $body .= "<p>This item is part of a larger body of work being done at Worklist. You can join our Live Workroom to ask more questions by going <a href=\"" . SERVER_BASE . "\">here</a>. You will be our 'Guest' while there but can also create an account if you like so we can refer to you by name.</p>";
                $body .= "<p>If you are the type that likes to look before jumping in, here are some helpful links to get you started.</p>";
                $body .= "<p>[<a href=\"http://www.lovemachineinc.com/\">www.lovemachineinc.com</a> | Learn more about LoveMachine the company]<br />";
                $body .= "[<a href=\"http://svn.worklist.net/\">svn.worklist.net</a> | Browse our SVN repositories]<br />";
                $body .= "[<a href=\"http://www.lovemachineinc.com/development-process/\">dev.sendllove.us</a> | Read about our Development Process]<br />";
                $body .= "[<a href=\"https://dev.sendllove.us/\">dev.sendllove.us</a> | Play around with SendLove]<br />";
                $body .= "[<a href=\"https://dev.worklist.net/worklist/\">dev.worklist.net/worklist</a> | Look over all our open work items]<br />";
                $body .= "[<a href=\"https://dev.worklist.net/journal/\">dev.worklist.net/journal</a> | Talk with us in our Journal]<br />";
                $body .= "<p>Hope to see you soon.</p>";
                if(!send_email($invite, $subject, $body)) { error_log("functions.php:inviteNew: send_email failed"); }
				return true;
            }
        }
	return false;
}
/* invite people By nickname or by email*/
function invitePeople(array $people, $item, $summary = null, $description = null) {
    foreach ($people as $invite) {
        // Call the invite person function
		invitePerson($invite, $item, $summary, $description);
    }
}
/**
 Validate an email address.
 Provide email address (raw input)
 Returns true if the email address has the email
 address format and the domain exists.
 */
function validEmail($email) {
    $isValid = true;
    $atIndex = strrpos($email, "@");
    if (is_bool($atIndex) && !$atIndex) {
        $isValid = false;
    } else {
        $domain = substr($email, $atIndex+1);
        $local = substr($email, 0, $atIndex);
        $localLen = strlen($local);
        $domainLen = strlen($domain);
        if ($localLen < 1 || $localLen > 64) {
            // local part length exceeded
            $isValid = false;
        } else if ($domainLen < 1 || $domainLen > 255) {
            // domain part length exceeded
            $isValid = false;
        } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
            // local part starts or ends with '.'
            $isValid = false;
        } else if (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $isValid = false;
        } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $isValid = false;
        } else if (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $isValid = false;
        } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
            // character not valid in local part unless
            // local part is quoted
            if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
                $isValid = false;
            }
        }
        if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
            // domain not found in DNS
            $isValid = false;
        }
    }
    return $isValid;
}

function  GetTimeStamp($MySqlDate, $i='')
{
    if (empty($MySqlDate)) $MySqlDate = date('Y/m/d');
    $date_array = explode("/",$MySqlDate); // split the array

    $var_year = $date_array[0];
    $var_month = $date_array[1];
    $var_day = $date_array[2];
    $var_timestamp=$date_array[2]."-".$date_array[0]."-".$date_array[1];
    //$var_timestamp=$var_month ."/".$var_day ."-".$var_year;
    return($var_timestamp); // return it to the user
}

// is user posting data without being logged in
function handleUnloggedPost() {
    // get the IP address
    $request_ip = $_SERVER['REMOTE_ADDR'];
    $request_uri = $_SERVER['REQUEST_URI'];
    error_log('Possible hack attempt from ' . $request_ip . ' on: ' . $request_uri);
    error_log(json_encode($_REQUEST));
    die('You are not authorized to post to this URL. Click ' .
        '<a href="' . SERVER_URL . '">here</a> to go to the main page. ' . "\n");
}

function checkLogin() {
    if (! getSessionUserId()) {
        $_SESSION = array();
        session_destroy();
        if (!empty($_POST)) {
            handleUnloggedPost();
        }
        header("location:login.php?expired=1&redir=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

    /* linkify function
     *
     * this takes some input and makes links where it thinks they should go
     * it is duplicated in journal and worklist so if you update one
     * remember to update the other, thanks very much
     *
     */
    function linkify($url, $author = null)
    {
        $original = $url;

        $class = '';
        if(strpos($url, "get_attachment.php") > 0)
        {
            $class=' class="attachment noicon"';
        } else {
            $class='';
        }

        $decodedUrl = html_entity_decode($url);
        if (preg_match("/\<a href=\"([^\"]*)\"/i", $decodedUrl) == 0) {
            // modified this so that it will exclude certain characters from the end of the url
            // add to this as you see fit as I assume the list is not exhaustive
            $regexp="/((?:(?:ht|f)tps?\:\/\/|www\.)\S+[^\s\.\)\"\'])/i";
            $url=  preg_replace($regexp,'<a href="$0"' . $class . '>$0</a>',$url);

            $regexp="/href=\"(www\.\S+?)\"/i";
            $url = preg_replace($regexp,'href="https://$1"',$url);
        }

        $regexp="/(href=)(.)?((www\.)\S+(\.)\S+)/i";
        $url = preg_replace($regexp,'href="https://$3"',$url);

        // Replace '#<number>' with a link to the worklist item with the same number
        $regexp = "/\#([1-9][0-9]*)/";
        $url = preg_replace($regexp,'<a href="'.WORKLIST_URL.'/workitem.php?job_id=$1&action=view" class="worklist-item" id="worklist-$1" >#$1</a>',$url);

        // Replace '#<nick>/<url>' with a link to the author sandbox
        $regexp="/\#([A-Za-z]+)\/(\S*)/i";
        if (strpos(SERVER_BASE, '~') === false) {
            $url = preg_replace(
                $regexp,
                '<a href="'.SERVER_BASE.'~$1/$2" class="sandbox-item" id="sandbox-$1">$1 : $2</a>',
                $url
            );
        } else { // link on a sand box :
            $url = preg_replace(
                $regexp,
                '<a href="'.SERVER_BASE.'/../~$1/$2" class="sandbox-item" id="sandbox-$1">$1 : $2</a>',
                $url
            );
        }

        // Replace '#/<url>' with a link to the author sandbox
        $regexp="/\#\/(\S*)/i";
        if (strpos(SERVER_BASE, '~') === false) {
            $url = preg_replace(
                $regexp,
                '<a href="'.SERVER_BASE.'~'.$author.'/$1" class="sandbox-item" id="sandbox-$1">'.$author.' : $1</a>',
                $url
            );
        } else { // link on a sand box :
            $url = preg_replace(
                $regexp,
                '<a href="'.SERVER_BASE.'/../~'.$author.'/$1" class="sandbox-item" id="sandbox-$1" >'.$author.' : $1</a>',
                $url
            );
        }

        $regexp="/\b(?<=mailto:)([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})/i";
        if(preg_match($regexp,$url)){
            $regexp="/\b(mailto:)(?=([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4}))/i";
            $url=preg_replace($regexp,"",$url);
        }

        $regexp="/\b([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})/i";
        $url=preg_replace($regexp,'<a href="mailto:$0">$0</a>',$url);

        // find anything that looks like a link and add target=_blank so it will open in a new window
        $url = preg_replace("/<a\s+href=\"/", "<a target=\"_blank\" href=\"", $url);

        return $url;
    }

    /**
     * Return a trimmed version of the nickname
     */
    function getSubNickname($nickname) {
        if (strlen($nickname) > 18) {
            return substr($nickname, 0, 18) . '...';
        } else {
            return $nickname;
        }
    }

    function getProjectList() {
        $query = "SELECT * FROM `".PROJECTS."` WHERE active=1";
        $query = mysql_query($query);
        $projects = array();
        $i = 0;
        while ($project = mysql_fetch_array($query)) {
            $projects[$i]['name'] = $project['name'];
            $projects[$i]['id']   = $project['project_id'];
            $projects[$i]['repo'] = $project['repository'];
            $i++;
        }
        return $projects;
    }

