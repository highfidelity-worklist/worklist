<?php
/* The purpose of this script is to run commands remotely through
 * an authenticated system.
 *
 * This requires server.local.php to have at least two entries:
 *      define('COMMAND_API_KEY', 'XXXX');
 *      define('COMMAND_YYYY',    'ZZZZ');
 * Where
 *      XXXX = the secret key to run commands
 *      YYYY = the name of the command
 *      ZZZZ = the command to run
 */

//Check for configuration and load
if (file_exists(dirname(__FILE__) . '/server.local.php')) {
    require_once(dirname(__FILE__) . '/server.local.php');

    // Require HTTPS
    if (!isset($_SERVER['HTTPS']) || empty($_SERVER['HTTPS'])) {
        die("I require HTTPS.");
    }

    // Validate request
    if (!isset($_POST['key']) || !isset($_POST['command'])) {
        die("Incorrect parameters.");
    }

    $key = $_POST['key'];
    $cmd = "COMMAND_".strtoupper($_POST['command']);
    
    // Check for constants defined & not empty
    if (!defined('COMMAND_API_KEY') || !defined($cmd) || 
        COMMAND_API_KEY=="" || empty($cmd)) {
        die("Instance not configured.");
    }

    // Authenticate request
    if ($key != COMMAND_API_KEY) {
        die("Authentication failed.");
    }
    
    $command = constant($cmd);

    // For deploy commands, append the revision to the command
    if (isset($_POST['rev']) && $cmd == "COMMAND_UPDATE") {
        $command .= " -n ".(int)$_POST['rev'];
    }
   
    // Run the command
    $result = 0;
    system($command, $result);
    
    if ($result != 0) {
        die("Error running command.");
    }
}

?>
