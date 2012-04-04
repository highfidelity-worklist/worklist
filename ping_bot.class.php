<?php 
// Bot class for the 'mem' journal bot.
// 
//  vim:ts=4:et

class PingBot extends Bot
{
    public function __construct() {
        parent::__construct();

        self::registerBot($this);
    }

    public function __call($request, $args)
    {
        $nickname = substr($request, 7);
        if (empty($nickname)) {
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>'Who do you want me to ping?');
        }

        $me = $args[0];
        $message = $args[1];
        if (!empty($message)) $message = ': '.$message;
        return $this->ping($me, $nickname, $message);
    }


    public function respondsTo() {
        return 'ping';
    }

    public function understands($cmd=null) {
        if (!$cmd) {
            return parent::understands($cmd);
        }
        return 'true';
    }

    public function botcmd_all($author, $botmsg) {
        $botmsg = mysql_real_escape_string(htmlentities($botmsg, ENT_COMPAT, 'UTF-8'));
        return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#privpub',
            'private'=>"$author, I pinged everyone.", 'message'=>"PING! Everyone! $author says: $botmsg");
    }

    public function botcmd_hello($author, $botmsg) {
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>"Hi $author, I am the Ping Bot - I can let people know you want to talk with them.  Type '@ping help' to get started.");
    }

    public function botcmd_help($author, $botmsg) {
        switch ($botmsg) {
        case 'all':
            $message = "Use my 'all' command to have me ping everyone who's not away.  Please don't overuse this command.";
            break;
        case 'user':
            $message = "Get someone's attention by typing '@".$this->respondsTo()." [user] {nickname} {message}'.";
            break;
        default:
            return parent::botcmd_help($author, $botmsg);
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$message);
    }

    public function botcmd_user($me, $botmsg) {
        $regex = '/^([\S]+)\s*(.*)/';
        if (preg_match($regex, $botmsg, $matches) < 1) {
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>'Who do you want me to ping?');
        }

        $nickname = $matches[1];
        if (empty($nickname)) {
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>'Who do you want me to ping?');
        }

        $message = trim($matches[2]);
        if (!empty($message)) $message = ': '.$message;
        return $this->ping($me, $nickname, $message);
    }

    /*
     * Protected Methods
     */

    protected function ping($me, $nickname, $message) {
        $res = mysql_query("select u.id, UNIX_TIMESTAMP(rs.`last_entry`), u.phone from ".USERS." u, ".RECENT_SPEAKERS." rs where LOWER(u.nickname)='".strtolower($nickname)."' AND (rs.user_id = u.id)");
        if (!$res || mysql_num_rows($res) == 0) {
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>"I'm sorry, I don't know a user named $nickname.");
        }
        $user = mysql_fetch_row($res);
        
        global $him_bot;
        $him_bot->rememberInfo($nickname, time(), "[ping]$me$message");

        global $chat;
        if (!$chat->isSpeakerOnline($user[0])) {
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>"$nickname isn't online right now.");
        }

	    $bot_return = "$me, I let $nickname know you wanted to talk to them.";        
        //Check if the user's last activity was more than 30 minutes ago
        if($user[1] <= time()-1800) {
            //If they have a cell number on file
            if(isset($user[2])) {
                //They have a cell number on file, txt them the PING! & Tell the requesting user the PING! went to the other user's cell
                try {
                    require_once('lib/Sms.php');
                	$pinguser = new User();
                	$pinguser->findUserByNickname($nickname);
                	$config = Zend_Registry::get('config')->get('sms', array());
                	if ($config instanceof Zend_Config) {
                		$config = $config->toArray();
                	}
                	$smsMessage = new Sms_Message($pinguser, 'New Ping', "$me: $message");
                	Sms::send($smsMessage, $config);
                } catch (Sms_Backend_Exception $e) { }

                $bot_return = "$me, I let $nickname know you wanted to talk to them. They have not spoken in over 30 minutes, your PING was sent to their cell phone.";
            } else {
            	//They do not have a cell number on file, send the ping but tell the user a text was not sent.
            	$bot_return = "$me, I let $nickname know you wanted to talk to them, but could not text them because they don't have a cell number on file.";
            }
        }

        $message = mysql_real_escape_string(htmlentities($message, ENT_COMPAT, 'UTF-8'));
        return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#privpub',
            'private'=>$bot_return, 'message'=>"PING! $nickname, you have been pinged by $me$message");
    }

}

$ping_bot = new PingBot();
