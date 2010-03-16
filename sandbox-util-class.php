<?php 
// Class to work with sandbox
// 
//  vim:ts=4:et

require_once dirname(__FILE__) . '/config.php';
include_once "send_email.php";


define('SANDBOX_BASE_DIR','/mnt/ebsvol/dev-www');
define('SANDBOX_CREATE_SCRIPT','/usr/local/bin/addnewdev-util.sh');
define('CMD_SEPARATOR',' ');
define('SANDBOX_CREATION_EMAIL_TEMPATE','./sb-developer-mail.inc');
class SandBoxUtil
{
    private $projectList = array('journal','sendlove','worklist');
    private $chat ;

    public function __construct()
    {
        if (!mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD)) {
            throw new Exception('Error: ' . mysql_error());
        }
        if (!mysql_select_db(DB_NAME)) {
            throw new Exception('Error: ' . mysql_error());
        }
    }

    public function createSandbox($username, $nickname, $unixusername,  $projects)
    {
	// Validate inputs
	$this->validateUsername($unixusername);
	$this->validateProjects($projects);

	// Ensure that sandbox doesn't already exist. if so, throw an exception
	$this->ensureNonExistentSandbox($unixusername);

	// Generate a random password.
	$password = $this->generatePassword(8);
	// Create the user and checkout all the folders. if something went wrong, throw an exception
	$userCreationStatus = $this->createUserAndCheckoutProjects($unixusername, $password, $projects);

	// Send a mail to the developer
	$this->notifyDeveloper($username, $nickname, $unixusername, $password, $projects);

	// Notify Journal
	$this->notifyJournal($nickname, $projects);

    	//	$this->sendMail($username,$password);                                                // todo.......

    }

    /**
    * Check if a user's sandbox already exists.Throws an exception if Sandbox already exists
    *
    */
    private function ensureNonExistentSandbox($username)
    {
      $userSandboxHome = SANDBOX_BASE_DIR ."/" .$username;
      if(file_exists($userSandboxHome)) {
            throw new Exception('Sandbox already exists');
      }
    }

    /**
    * Check if Username is valid
    *
    */
    private function validateUsername($username)
    {
      if(!preg_match('/^[\w\d\_\.]{3,}$/',$username)) {
            throw new Exception('Invalid username');
      }
    }

    /**
    * Check if Username is valid
    *
    */
    private function validateProjects($projects)
    {
      foreach($projects as $project) {
	if(!in_array($project, $this->projectList, true)) {
	      throw new Exception('The project ' . $project . ' is invalid');
	}
      }
    }

    /**
    * Create the user and checkout the project(s).Throws an exception if something went wrong
    *
    */
    private function createUserAndCheckoutProjects($nickname, $password, $projects)
    {
      $projectList = implode(' -r ',$projects);
      $encryptedPassword = crypt($password,"password");
      #$command = '/usr/bin/sudo '.SANDBOX_CREATE_SCRIPT .CMD_SEPARATOR . "-u " .$nickname . CMD_SEPARATOR . "-p " . $encryptedPassword. CMD_SEPARATOR . "-r " . $projectList;
      $command = SANDBOX_CREATE_SCRIPT .CMD_SEPARATOR . "-u " .$nickname . CMD_SEPARATOR . "-p " . $encryptedPassword. CMD_SEPARATOR . "-r " . $projectList;
	error_log($command);
      $scriptStatus  = exec($command . "; echo $?");
      if($scriptStatus != "0") {
            throw new Exception('Sandbox create script failed:'.$scriptStatus);
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

	$body = str_replace("{USERNAME}",$username,$body);
	$body = str_replace("{PASSWORD}",$password,$body);
	//$username = "vijay@bambeeq.com";
        sl_send_email($username , $subject, $body);
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

static $sandboxUtil = null;
if (empty($sandboxUtil)) {
    $sandboxUtil = new SandBoxUtil;
}

?>
