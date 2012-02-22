<?php 
// Bot class for the 'mem' journal bot.
// 
//  vim:ts=4:et

// Sendlove API status and error codes. Keep in sync with .../sendlove/add.php
define ('SL_OK', 'ok');
define ('SL_ERROR', 'error');
define ('SL_WARNING', 'warning');
define ('SL_NO_ERROR', '');
define ('SL_NO_RESPONSE', 'no response');
define ('SL_BAD_CALL', 'bad call');
define ('SL_DB_FAILURE', 'db failure');
define ('SL_UNKNOWN_USER', 'unknown user');
define ('SL_NOT_COWORKER', 'receiver not co-worker');
define ('SL_RATE_LIMIT', 'rate limit');
define ('SL_SEND_FAILED', 'send failed');
define ('SL_JOURNAL_FAILED', 'journal failed');
define ('SL_NO_SSL', 'no ssl call');
define ('SL_WRONG_KEY', 'wrong api key');
define ('SL_LOVE_DISABLED', 'sendlove disabled');

class LoveBot extends Bot
{
	public function __construct() {
		parent::__construct();

		self::registerBot($this);
	}

	public function __call($request, $args) {
	
		$author = $args[0];
		$why = $args[1];
		$receiver = substr($request, 7);
		
		$params = array (
            'action' => 'sendlove',
            'api_key' => SENDLOVE_API_KEY,
            'from' => $_SESSION['nickname'],
			'to' => $receiver,
			'why' => $why,
			'caller' => APP_JOURNAL);
		$referer = (empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
		$sendlove_rsp = postRequest (SENDLOVE_API_URL, $params, array(CURLOPT_REFERER => $referer));
		$rsp = json_decode ($sendlove_rsp, true);

        global $him_bot;
        $him_bot->rememberInfo($params['to'], time(), "[love]{$params['from']}:{$params['why']}");

		if (!is_array ($rsp) || !isset($rsp['status']) || !isset($rsp['error'])) {
			$rsp = array ('status' => SL_ERROR, 'error' => SL_NO_RESPONSE);
		}

		switch ($rsp['error']) {
		case SL_NO_ERROR:
			return false;
		case SL_NO_RESPONSE:
			$msg = "I'm sorry $author, but I couldn't communicate with Sendlove properly. Please try again!";
			break;
		case SL_UNKNOWN_USER:
			$msg = "$author, I don't recognize the name '$receiver'. Are you sure $receiver is registered on Sendlove, or did you perhaps misspell their name?";
			break;
		case SL_RATE_LIMIT:
			$msg = "Sorry $author, you have reached the send rate limit!";
			break;
		case SL_SEND_FAILED:
			$msg = "$author, Sendlove failed to send your love! Please try again.";
			break;
		case SL_JOURNAL_FAILED:
			$msg = "$author, you sent love to $receiver, but I failed to announce it in Journal (info: {$rsp['info']}).";
			break;
		case SL_LOVE_DISABLED:
			$msg = "$author, you sent love to $receiver, however the Love system is currently undergoing maintenance. (info: {$rsp['info']}).";
			break;
        case SL_WRONG_KEY;
		case SL_DB_FAILURE:
		case SL_NO_SSL:
		default:
			$msg = "$author, an unknown fault occured. Please report this! (code [info]: {$rsp['error']} [{$rsp['info']}])";
			break;
		}

		return array('bot' => $this->respondsTo(),
			 'status' => $rsp['status'],
			 'scope' => '#private',
			 'message' => $msg);
	}
	
	public function respondsTo() {
		return 'love';
	}

	public function understands($cmd=null) {
		if (!$cmd) {
			return parent::understands($cmd);
		}
		return 'true';
	}

	public function botcmd_hello($author, $botmsg) {
		return array(
			'bot'=>$this->respondsTo(),
			'status'=>'ok',
			'scope'=>'#private',
			'message'=>"Hi $author, with the 'love' command I can send your love to co-workers at LoveMachine.  Type '@".$this->respondsTo()." help' to get started.");
	}

	public function botcmd_help($author, $botmsg) {
		$message =
	  "To send love to a person, write '@".$this->respondsTo()." [to] [why]', " .
	  "where you replace '[to]' with the nickname of the receiver, and '[why]' " .
	  "with an explanation of why the receiver deserves your love.\n" .
	  "Example: @love ryan because he has great hair.";

		return array(
			'bot'=>$this->respondsTo(),
			'status'=>'ok',
			'scope'=>'#private',
			'message'=>$message);
	}

	/*
	 * Protected Methods
	 */
}

$love_bot = new LoveBot();
