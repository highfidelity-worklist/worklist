<?php

/**
 * @author:     Leonardo Murillo
 * @copyright:  (c)2012 Below92 Inc.
 * 
 * GitHub Callback Handler, use this for processing any responses
 * from the GitHub API.
 * 
 */

require_once 'config.php';
require_once 'class.session_handler.php';
require_once 'class/Utils.class.php';
require_once 'functions.php';
require_once 'classes/User.class.php';
require_once 'classes/GitHub.class.php';

// This is an array of actions that are allowed
$authorizedActions = array(
    'connectUser',
    'disconnectUser',
    'forkRepo',
    'pullRequest',
    'codeMerged'
);

// We look for a function named as the action in the request
if (isset($_REQUEST['action']) && in_array($_REQUEST['action'], $authorizedActions)) {
    $action = $_REQUEST['action'];
    $action();
}

connectUser();

/**
 *  
 */
function connectUser() {
    $GitHub = new GitHubUser(getSessionUserId());
    $connectResponse = $GitHub->processConnectResponse();
    print_r($connectResponse);
    if (!$connectResponse['error']) {
        if ($GitHub->storeCredentials($connectResponse['data']['access_token'])) {
            // Everything went well, we should now close this window and allow the user
            // to continue his bidding process.
            $message = 'You have succesfully authorized our application in GitHub. Go ahead and Add your Bid!';
        } else {
            // Something went wrong updating the users details, close this window and
            // display a proper error message to the user
            $message = 'Something went wrong and we could not complete the authorization process with GitHub. Please try again.';
        };
    } else {
        // We have an error on the response, close this window and display an error message
        // to the user
        $message = 'We received an error when trying to complete the authorization process with GitHub. Please notify a member of the O-Team for assistance.';
    };
}

;
?>
<html>
    <head>
        <title>Worklist - GitHub Integration handler</title>
        <script type="text/javascript">
            window.close();
        </script>
    </head>
</html>