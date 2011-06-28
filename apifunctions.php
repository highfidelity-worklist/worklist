<?php


include_once("config.php");
include_once("class.session_handler.php");
include_once("functions.php");


function addRewarderBalance($userId, $points, $worklist_id = 0, $fee_id = 0) {
    //Wire off rewarder interface for the time being - gj 5/21/10
    if(true) return 1;

    defineSendLoveAPI();

    $reason = "LoveMachine paid you $" . $points;
    $params = array (
            'action' => 'change_balance',
            'api_key' => REWARDER_API_KEY,
            'user_id' => $userId,
            'points' => $points,
            'reason' => $reason,
            'worklist_id' => $worklist_id,
            'fee_id' => $fee_id,
                    );

    $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (REWARDER_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    if ($retval['status'] == "ok") {
        return 1;
    } else {
        return -1;
    }
}

/*
* getRewardedPoints  - api call to rewarder to get how many
* points $giverId has given to $receiverId
*
*/
function getRewardedPoints($giverId, $receiverId) {
    defineSendLoveAPI();


    $params = array (
            'action' => 'get_points',
            'api_key' => REWARDER_API_KEY,
            'giver_id' => $giverId,
            'receiver_id' => $receiverId,
                    );

    $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (REWARDER_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    if ($retval['status'] == "ok") {
        return $retval['data'];
    } else {
        return -1;
    }
}

/*
* rewardUser - api call to rewarder to grant
* rewarder points from $giverId to $receiverId
*
*/
function rewardUser($giverId, $receiverId, $points) {
    defineSendLoveAPI();

    $params = array (
            'action' => 'reward_user',
            'api_key' => REWARDER_API_KEY,
            'giver_id' => $giverId,
            'receiver_id' => $receiverId,
            'points' => $points,
                    );

    $referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
    $retval = json_decode(postRequest (REWARDER_API_URL, $params, array(CURLOPT_REFERER => $referer)), true);

    if ($retval['status'] == "ok") {
        return $retval['data'];
    } else {
        return -1;
    }
}  
