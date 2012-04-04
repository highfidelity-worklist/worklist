<?php 
// Bot class for the 'him' journal bot.
// 
//  vim:ts=4:et

class HimBot extends Bot
{
    
    public function __construct() {
        parent::__construct();

        $this->userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : 0;
        self::registerBot($this);
        self::watchFor($this, 'message', 'botwatch_message');
        // self::watchFor($this, 'entry', 'botwatch_entry');
    }


    public function respondsTo() {
        return 'him';
    }

    // set idle time for given user
    public function recordIdleTime($nickname) {
        $time = $this->recallInfo($nickname, 'from', time());
        if(!$time) {
            $this->rememberInfo($nickname, 'from', time());
        }
    }

	/* getWhereClause
	 *
	 * Shared function for generating a where clause based on a author or topic value,
	 * either of which may, if empty, equate to all.
	 */
	protected function getWhereClause($author, $date) {
	    // getWherePlus
	    // pass it an array of nickname and userid to perform
	    // the advanced getwhere needed for the backlist
		$bot = get_class($this);
		$author = is_array($author) ? $author : mysql_escape_string($author);
		$date  = mysql_escape_string($date);

		$where = "`bot`='$bot'";
		if (!empty($author)) {
            if(is_array($author)) {
                $nickname = mysql_escape_string($author['nickname']);
                $userid = mysql_escape_string($author['userid']);
                $where .= " AND ((`author`=LOWER('$nickname')) OR (`author`=LOWER('survey'))) ";
            } else {
                $where .= " AND `author`=LOWER('$author')";
            }
		}
		if (!empty($date)) {
            if(is_array($author)) {
    			$where .= " AND FROM_UNIXTIME(`topic`)>FROM_UNIXTIME($date)";
			} else {
                $where .= " AND `topic` = '$date'";
			}
		}

		return $where;
	}

	public function botcmd_hello($author, $botmsg) {
		return array(
			'bot'=>$this->respondsTo(),
			'status'=>'ok',
			'scope'=>'#private',
			'message'=>"Hello $author, use the 'him' (History Manager) get command to find out what's happened while you were away.");
	}

	public function botcmd_forget($author, $botmsg) {
        if($author == GUEST_NAME) {
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>"I'm sorry $author, You'll need to sign up before I can keep track of things for you.");
        }
        if (strpos($botmsg, 'all') !== false) {
            $this->forgetInfo($author);
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>"Sorry, $author, who are you again ;)");
        } else {
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>"To make me forget everything about you, $author, it's '@him forget all'.");
        }
    }
    
    public function botcmd_get($author, $botmsg) {
        // this function collates all the history for the user for the given time
        
        if($author == GUEST_NAME) {
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>"I'm sorry $author, You'll need to sign up before I can keep track of things for you.");
        }

        // if we're told to get a different time, get that
        // otherwise forget the remembered time
        if (strpos($botmsg, 'today') !== false) {
            $time = time() - (24 * 60 * 60);
        } elseif (strpos($botmsg, 'all') !== false) {
            $time = 1;
        } else {
            // get the remembered from time
            $time = $this->recallInfo($author, 'from');
            $this->forgetInfo($author, 'from');
        }
        // if we don't have a time get the last hour
        if(empty($time)) {
            $time = time() - (1 * 60 * 60);
        }

        $info = $this->recallInfo(array('userid' => $this->userid, 'nickname' => $author), $time, true);
        
        // get a relative time to display in the message
        if($time > 1) {
            $time = relativeTime(time() - $time);
            $time = str_replace(' ago', '', $time);
        } else {
            $time = 'forever';
        }
        
        // what to do if there's no info
        if(count($info) == 0) {
    		return array(
    			'bot'=>$this->respondsTo(),
    			'status'=>'ok',
    			'scope'=>'#private',
    			'custom'=>'history',
    			'message'=> 'I\'ve got nothing to tell you right now, you could try "@him get today" to check for the last 24 hours worth of messages');
        }
        
        // for author comparison purposes we need to ensure a lowercase author
        $author = strtolower($author);
        // lets go through the history and pull the different message types into seperate arrays
        $collate = array();
        foreach($info as $enauthor => $entries) {
            foreach($entries as $key => $entry) {
                preg_match('/\[([^\]]+)\](.*)/', $entry, $matches);
                if (isset($collate[$matches[1]])) {
                  if (! is_array($collate[$matches[1]]) ) {
                    $collate[$matches[1]] = array();
                  }
                } else {
                  $collate[$matches[1]] = array();
                }
                $collate[$matches[1]][$key] = ($author == $enauthor) ? $matches[2] : "$enauthor|{$matches[2]}";
            }
        }
        // and then construct an return the message
        $message = "Here's what you should know from the last $time: <br />";
        foreach($collate as $type => $items) {
            if(count($items)) {
                $message .= "{$type}s: ";
                $array = array();
                foreach($items as $timestamp => $item) {
                    $item = explode(':', $item);
                    // check for blank title
                    if (! isset($item[1])) {
                        $item[1] = $item[0];
                    }
                    
                    $array[] = "<a class='gotoLink' data='$timestamp' title=\"" . 
                        date("d M H:i:s", $timestamp) . " - {$item[1]}\">" . 
                        relativeTime(time() - $timestamp, true) . " - {$item[0]}</a>";
                }
                $message .= implode(' ', $array) . "<br />";
            }
        }
		return array(
			'bot'=>$this->respondsTo(),
			'status'=>'ok',
			'scope'=>'#private',
			'custom'=>'history',
			'message'=> $message);
	}
    /* botwatch_entry
     *
     * Watch for messages that we might want to record.
     */
    public function botwatch_message($args) {
        $author = !empty($args[0]) ? $args[0] : 'Guest';
        $message = $args[1];

        // set the time here so all recordings have identical timestamp
        $time = time();

        // catch mentions and dump to history
        if(preg_match('/(?:^|[\b\s]+)@([^\b\s\W]+)(?:$|[\b\s\W]+)/Ui',$message, $matches)) {
            if(strpos(BOTLIST, $matches[1]) === false) {
                $this->rememberInfo($matches[1], $time, "[mention]{$author}:$message");
            }
        }

        // catch jobs and dump to history
        if(preg_match('/\#([1-9][0-9]+)/i',$message, $matches)) {
            if(strpos(BOTLIST, $matches[1]) === false) {
                $this->rememberInfo($matches[1], $time, "[job]{$author}:$message");
            }
        }

        // catch alerts and dump to history
        global $alert_bot;
        // get the alerts from the alertbot and loop through each users list
        $globalAlertList = $alert_bot->getAlerts();
        foreach ($globalAlertList as $me => $alertList) {
            $alertList = explode(',', strtolower($alertList['alert']));
            foreach ($alertList as $alert) {
                if(preg_match('/(?:^|[\b\s])'.$alert.'(?:$|[\b\s\W])/Ui',$message)) {
                    $this->rememberInfo($me, $time, "[alert]$alert:$author~ $message");
                }
            }
        }
        
        // we don't want to do anything other than record this information so
        // return an ignore
        return array('bot'=>$this->respondsTo(), 'status'=>'ignore');
    }

    // commented out in case it's needed in the future
    // currently only makes sense to just watch for items that are
    // being posted, as otherwise things will only be recorded
    // if there's someone to record them
    
    // public function botwatch_entry($args) {
    //     $currentuser = !empty($args[0]) ? $args[0] : 'Guest';
    //     $entry = $args[1];
    //     $message = $entry['entry'];
    // 
    //     return array('bot'=>$this->respondsTo(), 'status'=>'ignore');
    // }


    /*
     * Protected Methods
     */
}

$him_bot = new HimBot();
