<?php

/**
 * @author:     Leonardo Murillo
 * @copyright:  (c)2012 Below92 Inc.
 * 
 * GitHub Callback Handler, use this for processing any responses
 * from the GitHub API.
 * 
 * To add a new handler, simply add the event type to $eventHandlers (as returned
 * by GitHub) and create a function with that name in the GitHubProject class.
 * The full payload object will be passed to the function.
 * 
 */

require_once 'config.php';
require_once 'class.session_handler.php';
require_once 'class/Utils.class.php';
require_once 'functions.php';
require_once 'classes/User.class.php';
require_once 'chat.class.php';
require_once 'models/DataObject.php';
require_once 'models/Budget.php';
require_once 'workitem.class.php';

// This is an array of events that are allowed, if not here we just ignore for now
$eventHandlers = array(
    'pull_request'
);

$eventsInRequest = array();

if (array_key_exists('payload', $_POST)) {
    // Webhook callbacks contain a POSTed JSON payload, if we have it, process it
    
    // Create object with JSON payload
    $payload = json_decode($_REQUEST['payload']);
    
    foreach ($payload as $key => $value) {
        if (in_array($key, $eventHandlers)) {
            $eventsInRequest[] = $key;
        }
    }
    
    // I dont think a payload may include multiple events, however, just in case
    // we list the events that we have a handler for, and run each in sequence
    foreach ($eventsInRequest as $key => $value) {
        $GitHubProject = new GitHubProject();
        $GitHubProject->$value($payload);
    }
    
} else {
    // We don't have a payload, this is a response to a federeation
    connectUser();
}

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