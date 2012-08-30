<?php 
// Base 'bot' class for journal bots.
// 
//  vim:ts=4:et

require_once ("config.php");

class Bot
{
	private $state = '';

	static private $botList = array();
	static private $watchList = array();


	public function __construct() {
	}

	/* __call
	 *
	 * Some bots may respond to any request or to requests matching data from the DB.  The commands don't
	 * map to a specific botcmd_ method, this this method is here as a catch-all.
	 */
	public function __call($request, $args)
	{
		error_log(get_class($this).": called as $request");
	}

	/* getBotByName
	 *
	 * Given the name of a registered bot, returns a pointer to the bot object or null if no such
	 * bot is registred.
	 */
	static public function getBotByName($botname) {
		if (isset(self::$botList[$botname])) {
			return self::$botList[$botname];
		} else {
			return null;
		}
	}

	/* getBotList
	 *
	 * Returns the list of all registered bot objects.
	 */
	static public function getBotList() {
		return self::$botList;
	}

	/* notifyOf
	 *
	 * notifyOf is used to trigger events bots may be watching for. args is an array of values
	 * passed with the event notification.  The specific contents of args are dependant on the
	 * event.
	 */
	static public function notifyOf($event, $args=array()) {
		foreach (self::$watchList as $botname=>$watchList) {
			if (isset($watchList[$event])) {
				$method = $watchList[$event];
				$rsp = self::$botList[$botname]->$method($args);
				if ($rsp['status'] != 'ignore') {
					return $rsp;
				}
			}
		}

		return array('bot'=>'', 'status'=>'ignore');
	}

	/* respondTo
	 *
	 * This is the main "loop" for bots, so to speak. It is called by the chat class every time
	 * a new message is sent.  It's called before the message is recorded in the database to give
	 * bots a chance to intercept messages.
	 */
	static public function respondTo($author, &$message) {
		/* Handle any watches that might be on messages.
		 *
		 * Note that all messages sent are intercepted, not just those preceeded by '@'.  This
		 * allows bots to intercept any message and to implement multi-message/state action.
		 */
		$rsp = self::notifyOf('message', array($author, $message));
		if ($rsp['status'] != 'ignore') {
			return $rsp;
		}
		$message = trim($message);
				
		if(!preg_match('/^@([a-zA-Z0-9]+)\s*(.*)$/', $message, $matches)) {
			return false;
		}
		$botname = $matches[1];
		$botQuery = $matches[2];
		$bot = self::getBotByName($botname);
		
		/* Ignore anything not directed at a registered bot */
		if (!$bot) {
			return false;
		}
		
		/* Preceed all bot directed messages with 'To <botname>: ' to indicate to users that the
		 * message is not being relayed to the general chat and is private.
		 */
		$message = "To $botname: " . $message;
		
		/* If botQuery is like: @botname some_command some message  */
		if(preg_match('/^(\w+)\s*(.*)$/', $botQuery, $matches)) {
			$botcmd = $matches[1];
			$botmsg = $matches[2];
		}
		/* If botQuery is like: @botname "some command" some message  */
		elseif(preg_match('/^(\"|\\\')(.*?)\\1\s*(.*)$/', $botQuery, $matches)) {
			$botcmd = $matches[2];
			$botmsg = $matches[3];
		}
		/* If botQuery is like: @botname  */
		else {
			$botmsg = trim($message);
			return $bot->botcmd_hello($author, $botmsg);
		}

		/* If the bot doesn't understand the command, then respond with a standard error.
		 * This can be overriden by having understands return true and catching misc. commands
		 * with the magic __call function in the bot.
		 */
		if (!$bot->understands($botcmd)) {
			return array(
				'bot'=>$bot->respondsTo(),
				'status'=>'error',
				'scope'=>'#private',
				'message'=>"I don't understand that.  ".
					"Type '@".$bot->respondsTo()." help for a list of things I understand.");
		}

		/* Call the appropriate bot/cmd and return the results to the chat class */
		$fn = 'botcmd_'.$botcmd;
		error_log('bot fn: ' . $fn);
		return $bot->$fn($author, $botmsg);
	}

	/* respondsTo
	 *
	 * Bots override this method to indicate what "name" they respond to.
	 */
	public function respondsTo() {
		/* The core bot doesn't respond to any commands, although it's methods may respond
		 * in the context of a child class.
		 */
		return '';
	}

	/* understands
	 *
	 * The function can either return the list of "known" commands a bot understands, or
	 * true/false as to whether it will respond to a specific command.  Bots can override
	 * this method and return true for the second case in order to have all commands directed
	 * to them either via known methods or the __call magic method.
	 */
	public function understands($cmd=null) {
		$allMethods = get_class_methods(get_class($this));
		if (!$cmd) {
			$methods = array();
			foreach ($allMethods as $method) {
				if (strpos($method, 'botcmd_') === 0) $methods[] = substr($method, 7);
			}
			sort($methods);
			return $methods;
		} else if (in_array('botcmd_'.$cmd, $allMethods)) {
			return true;
		}

		return false;
	}

	/*
	 * Bot commands
	 *
	 * These are the standard commands that all bots understand.  Any of the commands may be
	 * overridden by a bot.
	 *
	 * The return value from bot commands is an array with the following required indices:
	 *   'bot' - the name of the bot that is responding
	 *   'status' - both and error code and a return value indicating how to process the response
	 *	  typical status values are 'ok', 'error', and 'ignore'.  Ignore means the bot doesn't
	 *	  have a response and the response should be treated as if there was no response.
	 *   'scope' - defines the audience of the bot response, typically either #public or #private.
	 *	  private responses are not saved in the DB and are only displayed to the current user.
	 *   'message' - the human readable response from the bot
	 */

	public function botcmd_echo($author, $botmsg) {
		return array(
			'bot'=>$this->respondsTo(),
			'status'=>'ok',
			'scope'=>'#private',
			'message'=>$botmsg);
	}

	public function botcmd_hello($author, $botmsg) {
		return array(
			'bot'=>$this->respondsTo(),
			'status'=>'ok',
			'scope'=>'#private',
			'message'=>"Hello $author, I am the core Bot.");
	}

	public function botcmd_help($author, $botmsg) {
		switch ($botmsg) {
		case 'echo':
			$message = "'echo' is just my way of being silly.  Tell me to echo something and I will repeat it to you.";
			break;
		case 'hello':
			$message = "Say 'hello' and I'll tell you about myself.";
			break;
		case 'help':
			$message = "Don't be silly! You already know how to use help.";
			break;
		default:
			$message = "$author, I recognize these commands:\n" . implode(', ', $this->understands()).
				 "\nType '@" . $this->respondsTo() . " help {command}' to find out more about any of these commands.";
		}

		return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>$message);
	}

	/*
	 * Protected Methods
	 */

	/* forgetInfo
	 *
	 * Very generic database delete method.  Can delete information matching a given author and/or
	 * a given topic, or all of the information associated with the bot.
	 */
	protected function forgetInfo($author=null, $topic=null) {
		$bot = get_class($this);

		$where = $this->getWhereClause($author, $topic);
		$sql = "DELETE FROM `".BOTDATA."` WHERE $where";
		mysql_unbuffered_query($sql);
	}

    /* getWhereClause
     *
     * Shared function for generating a where clause based on a author or topic value,
     * either of which may, if empty, equate to all.
     */
    protected function getWhereClause($author, $topic) {
        $bot = get_class($this);
        $author = mysql_escape_string($author);
        $topic = mysql_escape_string($topic);

        $where = "`bot`='$bot'";
        if (!empty($author)) {
            $where .= " AND `author`='$author'";
        }
        if (!empty($topic)) {
            $where .= " AND `topic`='$topic'";
        }

        return $where;
    }

	/* recallInfo
	 *
	 * Very generic database read method.  Can return information matching a given author and/or
	 * a given topic, or all of the information associated with the bot.
	 */
	protected function recallInfo($author=null, $topic=null, $expectMore = false) {
		$bot = get_class($this);

		$where = $this->getWhereClause($author, $topic);
		$sql = "SELECT `author`, `topic`, `info` FROM `".BOTDATA."` WHERE $where";
		$res = mysql_query($sql);

		/* Info for one author, return string, unless we expect more, then we can return an array of strings */
		if (!empty($author) && !empty($topic) && !$expectMore) {
            if ($res && ($row = mysql_fetch_assoc($res))) {
                return $row['info'];
            } else {
                return '';
            }
		}

		/* Info for all authors, return 2-dimension array[author][topic] */
		$info = array();
		while ($res && ($row = mysql_fetch_assoc($res))) {
			if (!isset($info[$row['author']])) {
				$info[$row['author']] = array();
			}
			$info[$row['author']][$row['topic']] = $row['info'];
		}
		return $info;
	}

	/* registerBot
	 *
	 * Adds a bot the list of all registered bots.
	 */
	static protected function registerBot($bot) {
		self::$botList[$bot->respondsTo()] = $bot;
	}

    /* rememberInfo
     *
     * Method for saving bot information.  While bots can return/delete information matching a
     * subset of criteria, they can only store specific information individually (for now anyway).
     */
    protected function rememberInfo($author, $topic, $info, $listItem = false) {
        $bot = get_class($this);
        $author = mysql_escape_string($author);
        $topic = mysql_escape_string($topic);
        $info = mysql_escape_string($info);

        /* Bot data essentially has a composite key, it's only unique for a given bot, author, and topic.
         * By attempting to read an existing item matching the current criteria bots can simulateously
         * maintain uniqueness (with db help) and determine whether an insert or update is required.
         */
        $sql = "SELECT `bot`, `info` FROM `".BOTDATA."` WHERE `bot`='$bot' AND `author`='$author' AND `topic`='$topic'";
        $res = mysql_query($sql);
        if (!$res || mysql_num_rows($res) == 0) { 
            $sql = "INSERT INTO `".BOTDATA."` SET `bot`='$bot', `author`='$author', `topic`='$topic', `info`='$info', `created`=CURRENT_TIMESTAMP, `updated`=CURRENT_TIMESTAMP";
        } else {
            if($listItem === true) {
                $row = mysql_fetch_row($res);
                $list = explode(',', $row[1]);
                if(!in_array($info, $list)) {
                    $list[] = $info;
                }
                $info = implode(',', $list);
            }
            $sql = "UPDATE `".BOTDATA."` SET `info`='$info', `updated`=CURRENT_TIMESTAMP WHERE `bot`='$bot' AND `author`='$author' AND `topic`='$topic'";
        } 
        $res = mysql_query($sql);

        return mysql_affected_rows() > 0;
    }

	/* watchFor
	 *
	 * watchFor is by bots register their interest in different types of events (e.g. incoming
	 * journal messages).  method is the function that should be called whenever the event
	 * occurs.  Bots may register one callback function for each event type.
	 */
	static protected function watchFor($bot, $event, $method) {
		$botname = $bot->respondsTo();
		if (!isset(self::$watchList[$botname])) {
			 self::$watchList[$botname] = array();
		}
		self::$watchList[$botname][$event] = $method;
	}
	
}


/* Bots
 *
 * Load the bots that will be active.
 */
 // load him bot first as it needs to be first to intercept any messages
require_once dirname(__FILE__) . '/him_bot.class.php';
require_once dirname(__FILE__) . '/faq_bot.class.php';
require_once dirname(__FILE__) . '/me_bot.class.php';
require_once dirname(__FILE__) . '/love_bot.class.php';
require_once dirname(__FILE__) . '/ping_bot.class.php';
require_once dirname(__FILE__) . '/survey_bot.class.php';
