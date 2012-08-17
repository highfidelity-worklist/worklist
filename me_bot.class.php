<?php 
// Bot class for the 'mem' journal bot.
// 
//  vim:ts=4:et

class AlertBot extends Bot
{
    public function __construct() {
    	ini_set('memory_limit', '64M');
        parent::__construct();

        self::registerBot($this);
        self::watchFor($this, 'entry', 'botwatch_entry');
    }


    public function respondsTo() {
        return 'alert';
    }

    // allow access to alerts used by himbot for watching for alerts
    public function getAlerts($username = null) {
        return $this->recallInfo($username, 'alert');
    }

    public function botcmd_add($me, $botmsg) {
        if ($me == 'Guest') {
            $message = "I'm sorry. You need to be logged in to set an alert.";
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>$message);
        }

        $alertList = $this->recallInfo($me, 'alert');

        if (!empty($botmsg)) {
            /* Reconstruct the alertList as an associative array with the keys being lowercase keywords */
            if (!empty($alertList)) {
                $tList = explode(',', $alertList);
                $alertList = array();
                foreach ($tList as $user) {
                    $alertList[strtolower($user)] = $user;
                }
            } else {
                $alertList = array();
            }

            $keyword = trim($botmsg); // actually "phrases"
            $kwLower = strtolower($keyword);
            if (!isset($alertList[$kwLower])) {
                $alertList[] = $keyword;
            }

            $alertList = implode(',', $alertList);
            $this->rememberInfo($me, 'alert', $alertList);
        }

        if (empty($alertList)) {
            $message = "$me, I'm not watching for any keywords now.";
        } else {
            $message = "$me, I'm currently watching for: ".implode(', ', explode(',', $alertList));
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$message);
    }

    public function botcmd_hello($me, $botmsg) {
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>"Hi $me, you can use the 'alert' command to trigger alerts whenever a keywords is used and or someone pings you.");
    }

    public function botcmd_help($author, $botmsg) {
        switch ($botmsg) {
        case 'add':
            $message = "Add a new alert with the 'add' command. Type '@".$this->respondsTo()." add {keyword}'.";
            break;
        case 'list':
            $message = "Get a list of all the alerts you have set with the 'list' command. Type '@".$this->respondsTo()." list'.";
            break;
        case 'remove':
            $message = "Delete an alert with the 'remove' command. Type '@".$this->respondsTo()." remove {keyword}'.";
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

    public function botcmd_list($me, $botmsg) {
        if ($me == 'Guest') {
            $message = "I'm sorry. You need to be logged in to manage alerts.";
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>$message);
        }

        $alertList = $this->recallInfo($me, 'alert');

        if (!empty($botmsg)) {
            /* Reconstruct the alertList as an associative array with the keys being lowercase keywords */
            if (!empty($alertList)) {
                $tList = explode(',', $alertList);
                $alertList = array();
                foreach ($tList as $user) {
                    $alertList[strtolower($user)] = $user;
                }
            } else {
                $alertList = array();
            }
        }

        if (empty($alertList)) {
            $message = "$me, I'm not watching for any keywords now.";
        } else {
            $message = "$me, I'm currently watching for: ".implode(', ', explode(',', $alertList));
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$message);
    }

    public function botcmd_remove($me, $botmsg) {
        if ($me == 'Guest') {
            $message = "I'm sorry. You need to be logged in to manage an alerts.";
            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#private', 'message'=>$message);
        }

        $alertList = $this->recallInfo($me, 'alert');

        if (!empty($botmsg)) {
            /* Reconstruct the alertList as an associative array with the keys being lowercase keywords */
            if (!empty($alertList)) {
                $tList = explode(',', $alertList);
                $alertList = array();
                foreach ($tList as $user) {
                    $alertList[strtolower($user)] = $user;
                }
            } else {
                $alertList = array();
            }

            $keyword = trim($botmsg); // actually "phrases"
            $kwLower = strtolower($keyword);
            if (isset($alertList[$kwLower])) {
                unset($alertList[$kwLower]);
            }

            $alertList = implode(',', $alertList);
            $this->rememberInfo($me, 'alert', $alertList);
        }

        if (empty($alertList)) {
            $message = "$me, I'm not watching for any keywords now.";
        } else {
            $message = "$me, I'm currently watching for: ".implode(', ', explode(',', $alertList));
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$message);
    }

    /* botwatch_entry
     *
     * Watch for PING messages.
     */
    public function botwatch_entry($args) {
        $me = !empty($args[0]) ? $args[0] : 'Guest';
        $entry = $args[1];
        $author = $entry['author'];
        $message = strtolower($entry['entry']);

        if ($author == 'Eliza') {
            $nickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '';

            if (preg_match('/^ping! ([a-zA-Z0-9 ]+),/', $message, $matches) > 0) {
                if (strtolower($matches[1]) == strtolower($nickname)) {
                    return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'botdata'=>array('ping'=>1));
                } else  {
                    return array('bot'=>$this->respondsTo(), 'status'=>'skip');
                }
            }
            
        }

        if ($author == 'Eliza' && 
            preg_match('/^ping! everyone!/', $message, $matches) > 0) {

            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'botdata'=>array('ping'=>1));
        }

        static $alertList = null;
        if ($alertList === null) $alertList = $this->recallInfo($me, 'alert');
        $alertList .= (empty($alertList) && isset($_SESSION['nickname'])) ? '' : ',';
        $alertList .= (isset($_SESSION['nickname'])) ? "@{$_SESSION['nickname']}" : '';
        if (!empty($alertList) && !is_array($alertList)) {
            $alertList = explode(',', strtolower($alertList));
            foreach ($alertList as $alert) {
                if(preg_match('/(?:^|[\b\s])'.$alert.'(?:$|[\b\s\W])/Ui',$message)) {
                    // moved himbot recording out into himbot
                    $message = "$me, someone said '$alert'!";
                    $entry = array('id'=>0, 'entry'=>$message, 'author'=>$this->respondsTo(), 'ip'=>'', 'date'=>'now');
                    return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'botdata'=>array('ping'=>1));
                }
            }
        }

        return array('bot'=>$this->respondsTo(), 'status'=>'ignore');
    }

}

class MeBot extends Bot
{
    public $backlink = '<a class="awayback">come back from away</a>';
    public function __construct() {
    	ini_set('memory_limit', '64M');
        parent::__construct();

        self::registerBot($this);
        self::watchFor($this, 'message', 'botwatch_away');
    }


    public function getAwayList($all = null) {
        $info = $this->recallInfo(null, 'away');
        return is_null($all) ? array_keys($info) : $info;
    }

    public function queryAway($nickname) {
        return($this->recallInfo($nickname, 'away'));
    }

    public function getAwayText($user='') {
        $text = $this->recallInfo($user, 'away');
        return ($text != NOMESSAGE) ? $text : '';
    }

    public function respondsTo() {
        return 'me';
    }


    public function botcmd_away($me, $botmsg) {
        if (empty($botmsg)) $botmsg = NOMESSAGE;
        $this->rememberInfo($me, 'away', $botmsg);
        // set this as the start of idletime for the wywa
        global $him_bot;
        $him_bot->recordIdleTime($me);
        /*($botmsg == NOMESSAGE ? '' : '')
           This can be commented out in next line if we want to add some text in system notification.
        */
        sendJournalNotification($_SESSION['nickname'] . ' is ' /*. ($botmsg == NOMESSAGE ? '' : '') */. $botmsg);
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'custom'=>'away',
            'message'=>"$me, I've got your back while you're away. {$this->backlink}");
    }

    public function botcmd_status($me, $botmsg) {
        if (empty($botmsg)) $botmsg = ' trying to remember how use @me status correctly.';
        User::update_status($botmsg);
        return array(
            'bot' => $this->respondsTo(),
            'status' => 'ok',
            'scope' => '#private',
            'message' => "Your status has been changed to: {$botmsg}");
    }


    public function botcmd_back($me, $botmsg) {
        $this->forgetInfo($me, 'away');
        $pung = $this->recallInfo($me, 'pung');
        if(!$pung) {
             $message = "Just let me know when you step away again.";
        } else {
            $message = "While you were away the following people tried to ping you: ". str_replace(',', ', ', $pung . '.');
            $this->forgetInfo($me, 'pung');
        }
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'custom'=>'back',
            'message'=>"Welcome back, $me. $message");
    }

    public function botcmd_hello($me, $botmsg) {
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>"Hi $me, when you use the 'me' command I can become your alter ego in the WorkRoom. Type '@".$this->respondsTo()." help' to find out more.");
    }

    public function botcmd_help($author, $botmsg) {
        switch ($botmsg) {
        case 'away':
            $message = "Type '@".$this->respondsTo()." away {message}' and I can keep people posted about your status if they ask about you.";
            break;
        case 'back':
            $message = "You can tell me you're 'back' if you don't have anything to say in the WorkRoom but want to cancel your away message.";
            break;
        case 'whosaway':
            $message = "With 'whosaway' you can find out who has left an away message.";
            break;
        case 'status':
            $message = "You can tell other users what you're currently up to, eg, \"$author is checking out @me help\".";
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

    public function botcmd_whosaway($me, $botmsg) {
        $info = $this->recallInfo(null, 'away');

        $away = "$me, the following people were kind enough to let us know they are away:\n" . implode(', ', array_keys($info));
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$away);
    }

    public function botwatch_away($args) {
        $me = $args[0];
        $message = $args[1];

        $info = $this->recallInfo($me, 'away');
        if (!empty($info)) {
            $this->forgetInfo($me, 'away');
        }
        
        $regex = '/^@([a-zA-Z]+[a-zA-Z0-9]*)[,]*[ \s]+/';
        if (preg_match($regex, $message, $matches) < 1) {
            return array('bot'=>$this->respondsTo(), 'status'=>'ignore');
        }

        $recipient = $matches[1];

        $info = $this->recallInfo($recipient, 'away');
        if (!empty($info)) {
            if ($info == 'NOMESSAGE') {
                $away = "$me, $recipient is away at the moment.";
            } else {
                $away = "$me, $recipient is away. I'm supposed to pass this message on:\n". $info;
            }

            return array('bot'=>$this->respondsTo(), 'status'=>'ok', 'scope'=>'#public', 'message'=>$away);
        } else {
            return array('bot'=>$this->respondsTo(), 'status'=>'ignore');
        }
    }
}

class NotifyBot extends Bot
{
    public function __construct() {
    	ini_set('memory_limit', '64M');
        parent::__construct();

        self::registerBot($this);
        self::watchFor($this, 'speaker', 'botwatch_speaker');
    }


    public function respondsTo() {
        return 'notify';
    }


    public function botcmd_add($me, $botmsg) {
        global $chat;
        $speakers = $chat->listSpeakers();

        /* Ignore speakers who say they are away. */
        $away = array_keys($this->recallInfo(null, 'away'));
        foreach ($away as $awayUser) {
            foreach ($speakers as $idx=>$speaker) {
                if ($speaker[1] == strtolower($awayUser)) {
                    unset($speakers[$idx]);
                }
            }
        }

        $notifyNow = array();
        $notifyList = $this->recallInfo($me, 'notify');
        if (!empty($botmsg)) {
            /* Reconstruct the notifyList as an associative array with the keys being lowercase nicknames */
            if (!empty($notifyList)) {
                $tList = explode(',', $notifyList);
                $notifyList = array();
                foreach ($tList as $user) {
                    $notifyList[strtolower($user)] = $user;
                }
            } else {
                $notifyList = array();
            }

            $users = explode(',', $botmsg);
            foreach ($users as $user) {
                $user = trim($user);
                $userLower = strtolower($user);
                if (!isset($notifyList[$userLower])) {
                    /* Don't set notifies for people online and not marked away.
                     */
                    foreach ($speakers as $speaker) {
                        if ($userLower == strtolower($speaker[1])) {
                            $notifyNow[] = $user;
                            continue 2;
                        }
                    }
                
                    $notifyList[] = $user;
                }
            }

            $notifyList = implode(',', $notifyList);
            $this->rememberInfo($me, 'notify', $notifyList);
        }
        
        if (empty($notifyList)) {
            $message = "$me, I'm not watching for anyone now.";
        } else {
            $message = "$me, I'm currently watching for: ".implode(', ', explode(',', $notifyList));
        }
        if (!empty($notifyNow)) {
            $users = $notifyNow[0] . ' ';
            if (count($notifyNow) == 1) {
                $is = 'is';
            } else {
                for ($i = 1; $i < count($notifyNow) - 1; $i++) {
                    $users .= ', ' . $notifyNow[$i];
                }
                $users .= 'and ' . $notifyNow[$i];
                $is = 'are';
            }
            $message .= "<br/>$users $is online now.";
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$message);
    }

    public function botcmd_hello($me, $botmsg) {
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>"Hi $me, the 'notify' command let's you watch for people you want to talk to in the Workroom.  I can notify you when they return.");
    }

    public function botcmd_help($author, $botmsg) {
        switch ($botmsg) {
        case 'add':
            $message = "Add a new person to watch for with the 'add' command. Type '@".$this->respondsTo()." add {user}'.";
            break;
        case 'list':
            $message = "Get a list of all the people you are watching for with the 'list' command. Type '@".$this->respondsTo()." list'.";
            break;
        case 'remove':
            $message = "Remove a person from the watch list the 'remove' command. Type '@".$this->respondsTo()." remove {user}'.";
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

    public function botcmd_list($me, $botmsg) {
        $notifyList = $this->recallInfo($me, 'notify');
        if (!empty($botmsg)) {
            /* Reconstruct the notifyList as an associative array with the keys being lowercase nicknames */
            if (!empty($notifyList)) {
                $tList = explode(',', $notifyList);
                $notifyList = array();
                foreach ($tList as $user) {
                    $notifyList[strtolower($user)] = $user;
                }
            } else {
                $notifyList = array();
            }
        }
        
        if (empty($notifyList)) {
            $message = "$me, I'm not watching for anyone now.";
        } else {
            $message = "$me, I'm currently watching for: ".implode(', ', explode(',', $notifyList));
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$message);
    }

    public function botcmd_remove($me, $botmsg) {
        $notifyList = $this->recallInfo($me, 'notify');
        if (!empty($botmsg)) {
            /* Reconstruct the notifyList as an associative array with the keys being lowercase nicknames */
            if (!empty($notifyList)) {
                $tList = explode(',', $notifyList);
                $notifyList = array();
                foreach ($tList as $user) {
                    $notifyList[strtolower($user)] = $user;
                }
            } else {
                $notifyList = array();
            }

            $users = explode(',', $botmsg);
            foreach ($users as $user) {
                $user = trim($user);
                $userLower = strtolower($user);
                if (isset($notifyList[$userLower])) {
                    unset($notifyList[$userLower]);
                }
            }

            $notifyList = implode(',', $notifyList);
            $this->rememberInfo($me, 'notify', $notifyList);
        }
        
        if (empty($notifyList)) {
            $message = "$me, I'm not watching for anyone now.";
        } else {
            $message = "$me, I'm currently watching for: ".implode(', ', explode(',', $notifyList));
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$message);
    }

    /* botwatch_speaker
     *
     * Handle notifications for speaker watches.
     */
    public function botwatch_speaker($args) {
        $me = !empty($args[0]) ? $args[0] : 'Guest';
        $speakers = $args[1];

        $info = $this->recallInfo($me, 'notify');
        if (!empty($info)) {
            $info = explode(',', strtolower($info));
        } else {
            $info = array();
        }

        $online = array();
        foreach ($speakers as $speaker) {
            $idx = strtolower($speaker[1]);
            if (in_array($idx, $info)) {
                $online[$idx] = $speaker[1];
            }
        }

        /* If there are no newly online people we're watching for, then we have nothing to do.
         */
        if (empty($online)) {
            return array('bot'=>$this->respondsTo(), 'status'=>'ignore');
        }

        foreach ($online as $idx=>$speaker) {
            unset($info[array_search($idx, $info)]);
        }
        $this->rememberInfo($me, 'notify', implode(',', $info));

        $count = count($online);
        $online = implode(', ', $online);
        if (count($online) > 1) {
            $message = "The following people have come online: $online";
        } else {
            $message = "$online has come online.";
        }
        $entry = array('id'=>0, 'entry'=>$message, 'author'=>$this->respondsTo(), 'ip'=>'', 'date'=>'now');
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'entry'=>$entry);
    }

}

$alert_bot = new AlertBot();
$me_bot = new MeBot();
$notify_bot = new NotifyBot();
