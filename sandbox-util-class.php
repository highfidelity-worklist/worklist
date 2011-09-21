<?php 
// Class to work with sandbox
// 
//  vim:ts=4:et

require_once dirname(__FILE__) . '/config.php';
include_once "send_email.php";
require_once('classes/Project.class.php');
require_once('functions.php');

class SandBoxUtil {
    //This needs to be synced with the matching file in journal - garth 12/15/2010
    private $projectList = array();
    private $chat ;

    public function __construct() {
        // get a list of repositories to match with requested projects
        $this->projectList = Project::getRepositoryList();
        if (!mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD)) {
            throw new Exception('Error: ' . mysql_error());
        }
        if (!mysql_select_db(DB_NAME)) {
            throw new Exception('Error: ' . mysql_error());
        }
    }

    public function createSandbox($username, $nickname, $unixusername, $projects,
                    $new_user=true) {

         # Ensure $projects is an array.. it could just be 1 project
        if (!is_array($projects)) {
            $projects = array($projects);
        }

        // If it's an existing user, just check out the requested projects for them
        if (!$new_user) {
            $result = true;
            foreach ($projects as $project) {
                try {
                    $this->checkoutProject($username, $unixusername, $project);
                } catch (Exception $e) {
                    $result = false;
                }
            }
            return $result;
        }
        
        // Validate inputs
        $this->validateUsername($unixusername);
        $this->validateProjects($projects);

        // Ensure that sandbox doesn't already exist. if so, throw an exception
        $this->ensureNonExistentSandbox($unixusername);

        // Generate a random password.
        $password = $this->generatePassword(8);
        // Create the user and checkout all the folders. if something went wrong, throw an exception
        $userCreationStatus = $this->createUserAndCheckoutProjects($unixusername, $password, $projects);
        error_log("creating Developer from worklist");
        // Send a mail to the developer
        $this->notifyDeveloper($username, $nickname, $unixusername, $password, $projects);

        // Notify Journal
        $this->notifyJournal($nickname, $projects);
    }

    /**
     * Returns true if the supplied $name is in /etc/passwd
     *
    */
    public function inPasswdFile($name) {
        //Don't continue if we are not configured to manage sandboxes (ie: from a sandbox/dev machine)
        if (! defined("SANDBOX_SERVER_API")) { throw new Exception('Unable to communicate to sandbox server, not defined');  }
        if (! defined("SANDBOX_SERVER_API_KEY")) { throw new Exception('Unable to communicate to sandbox server, not authorized');  }


        $command ='command=userexists&';
        $command.='key='.SANDBOX_SERVER_API_KEY.'&';
        if ($result = postRequest(SANDBOX_SERVER_API,$command.'username='.$name)) {
            //Only get a result if there was a failure (user doesn't exist or command failed)
            if (strpos($result,'Authentication failed')!==false) {throw new Exception('Unable to communicate to sandbox server, not authorized'); }
            if (strpos($result,'Error')==0) { return false; }
            return true;
        }
    }

    /**
     * Dumps a diff of a user's sandbox to the paste bin
     */
    public function pasteSandboxDiff($username, $workitem_num, $sandbox_dir) {
        if (! defined("SANDBOX_SERVER_API")) { throw new Exception('Unable to communicate to sandbox server, not defined'); }
        if (! defined("SANDBOX_SERVER_API_KEY")) { throw new Exception('Unable to communicate to sandbox server, not authorized'); }
        
        $command  = "command=paste_diff&";
        $command .= "output=1&";
        $command .= "username={$username}&";
        $command .= "job_num={$workitem_num}&";
        $command .= "sandbox_dir={$sandbox_dir}&";
        $command .= "key=".SANDBOX_SERVER_API_KEY;

        $result = postRequest(SANDBOX_SERVER_API, $command);

        if (strpos($result, "http") === false) {
            throw new Exception('Unable to paste sandbox diff to Worklist Pastebin: '.$result." -- ".$command);
        }

        return $result;
    }

    /**
    * Check if a user's sandbox already exists.Throws an exception if Sandbox already exists
    *
    */
    private function ensureNonExistentSandbox($username) {
        //Don't continue if we are not configured to manage sandboxes (ie: from a sandbox/dev machine)
        if (! defined("SANDBOX_SERVER_API")) { throw new Exception('Unable to communicate to sandbox server, not defined'); }
        if (! defined("SANDBOX_SERVER_API_KEY")) { throw new Exception('Unable to communicate to sandbox server, not authorized'); }

        $command ='command=sandboxexists&';
        $command.='key='.SANDBOX_SERVER_API_KEY.'&';
        if ($result = postRequest(SANDBOX_SERVER_API,$command.'username='.$username)) {
            //Only get a result if there was a failure (user doesn't exist or command failed)
            if (strpos($result,'Authentication failed')!==false) {throw new Exception('Unable to communicate to sandbox server, not authorized'); }
            if (strpos($result,'Error')!==false) { return true; }
            //If we don't fail, sandbox already exists
            throw new Exception('Sandbox already exists'); 
        }
    }

    /**
     * Check out a project for existing user
     *
     */
    private function checkoutProject($username, $unixusername, $project) {
        //Don't continue if we are not configured to manage sandboxes (ie: from a sandbox/dev machine)
        if (! defined("SANDBOX_SERVER_API")) { throw new Exception('Unable to communicate to sandbox server, not defined'); }
        if (! defined("SANDBOX_SERVER_API_KEY")) { throw new Exception('Unable to communicate to sandbox server, not authorized'); }

        $command ='command=checkoutrepo&';
        $command.='key='.SANDBOX_SERVER_API_KEY.'&';
        if ($result = postRequest(SANDBOX_SERVER_API,$command.'username='.$unixusername.'&repo='.$project)) {
            //Only get a result if there was a failure (user doesn't exist or command failed)
            if (strpos($result,'Authentication failed')!==false) {throw new Exception('Unable to communicate to sandbox server, not authorized'); }
            if (strpos($result,'Error')===true) { throw new Exception('Project checkout failed:'.$result); }
            $this->notifyCheckout($username, $unixusername, $project);
        }
    }

    /**
    * Check if Username is valid
    *
    */
    private function validateUsername($username) {
      if(!preg_match('/^[\w\d\_\.]{2,}$/',$username)) {
            throw new Exception('Invalid username');
      }
    }

    /**
    * Check if projects are valid
    *
    */
    private function validateProjects($projects) {
        if (!is_array($projects)) {
            $projects = array($projects);
        }

        foreach ($projects as $project) {
            if (!in_array($project, $this->projectList, true)) {
                throw new Exception('The project ' . $project . ' is invalid');
            }
        }
    }

    /**
    * Create the user and checkout the project(s).Throws an exception if something went wrong
    *
    */
    private function createUserAndCheckoutProjects($nickname, $password, $projects) {
        //Don't continue if we are not configured to manage sandboxes (ie: from a sandbox/dev machine)
        if (! defined("SANDBOX_SERVER_API")) { throw new Exception('Unable to communicate to sandbox server, not defined'); }
        if (! defined("SANDBOX_SERVER_API_KEY")) { throw new Exception('Unable to communicate to sandbox server, not authorized'); }

        $projectList = implode(' -r ',$projects);
        $encryptedPassword = crypt($password,"password");
        $transmitTestPassword = escapeshellcmd($encryptedPassword);
        if($transmitTestPassword!==$encryptedPassword) { throw new Exception('Encrypted password contains unsafe characters'); }

        $command ='command=adduser&';
        $command.='key='.SANDBOX_SERVER_API_KEY.'&';
        if ($result = postRequest(SANDBOX_SERVER_API,$command.'username='.$nickname.'&repo='.$projectList.'&password='.$encryptedPassword)) {
            if (strpos($result,'Authentication failed')!==false) {throw new Exception('Unable to communicate to sandbox server, not authorized'); }
            //Only get a result if there was a failure (user doesn't exist or command failed)
            if (strpos($result,'Error')===true) { throw new Exception('Sandbox create script failed: '.$result); }
        }
    }

    /**
    * Generates a random password of given length
    *
    */
    private function generatePassword ($length = 8)
    {

      // start with a blank password
      $password = "";

      // define possible characters
      $possible = "0123456789bcdfghjkmnpqrstvwxyz"; 
	
      // set up a counter
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

      // done!
      return $password;

    }

    /**
    * Create the user and checkout the project(s).Throws an exception if something went wrong
    *
    */
    private function notifyDeveloper($username, $nickname, $unixusername, $password, $projects)
    {
        $subject = "Your Sandbox Account";
        $body =  file_get_contents(SANDBOX_CREATION_EMAIL_TEMPATE);

        // Make sure we have proper line breaks in HTML
        $body =  nl2br($body);

        $body = str_replace("{USERNAME}",$unixusername,$body);
        $body = str_replace("{PASSWORD}",$password,$body);
        //$username = "vijay@bambeeq.com";
        if (!send_email($username , $subject, $body)) {
            error_log("sandbox-util-class.php: send_email failed");
        }
    }
    
    /**
     * Sends a notification email that a project was checked out for a user
     *
     */
    private function notifyCheckout($username, $unixusername, $project)
    {
        $subject = "Project Checkout";
        $sandbox = "https://dev.worklist.net/~".$unixusername."/".$project;

        $body = file_get_contents(PROJECT_CHECKOUT_EMAIL_TEMPATE);

        // Make sure we have proper line breaks in HTML
        $body = nl2br($body);

        $body = str_replace("{PROJECT}",$project,$body);
        $body = str_replace("{SANDBOX}",$sandbox,$body);
        
        if (!send_email($username , $subject, $body)) {
            error_log("sandbox-util-class.php: send_email failed");
        }
    }
    /**
    * Create the user and checkout the project(s).Throws an exception if something went wrong
    *
    */
    private function notifyJournal($nickname, $projects)
    {
        $journal_message = "Sandbox created for " . $nickname . " , checked out " . implode(", ",$projects);

        //sending journal notification
        $data = array();
        $data['user'] = JOURNAL_API_USER;
        $data['pwd'] = sha1(JOURNAL_API_PWD);
        $data['message'] = stripslashes($journal_message);
        $prc = postRequest(JOURNAL_API_URL, $data);
    }
}
