<?php 
// Bot class for the 'mem' journal bot.
// 
//  vim:ts=4:et

class FAQBot extends Bot
{
    public function __construct() {
        parent::__construct();

        self::registerBot($this);
        self::watchFor($this, 'message', 'botwatch_state');
    }

    public function __call($request, $args)
    {
        $author = $args[0];
        $topic = substr($request, 7);

        $faqInfo = $this->recallInfo(null, $topic);
        if (!empty($faqInfo)) {
            $faq = '';
            foreach ($faqInfo as $authorT=>$data) {
                foreach ($data as $topicT=>$info) {
                    if (strcasecmp($topicT, $topic) === 0) {
                        $faq .= "$authorT says: $info\n";
                    }
                }
            }
            return array(
                'bot'=>$this->respondsTo(),
                'status'=>'ok',
                'scope'=>'#private',
                'message'=>"$author, here's everything I know about '$topic':\n".$faq);
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>"$author, I don't know anything about '$topic'.");
    }


    public function respondsTo() {
        return 'faq';
    }

    public function understands($cmd=null) {
        if (!$cmd) {
            return parent::understands($cmd);
        }
        return 'true';
    }

    public function botcmd_add($author, $botmsg) {
        $state = $this->recallInfo($author, 'faq-state');
        $botmsg = preg_replace('/\W/', '', $botmsg);
        if (empty($botmsg)) {
            return array(
                'bot'=>$this->respondsTo(),
                'status'=>'error',
                'scope'=>'#private',
                'message'=>"$author, I'm sorry but I need a name for the FAQ you want to add.");
        }

        $this->rememberInfo($author, 'faq-add', $botmsg);
        $this->rememberInfo($author, 'faq-state', 'add');

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>"You want to add a new faq about '$botmsg', $author? If so, type in what you know and I'll remember it.\nIf you've changed your mind just enter 'cancel'.");
    }

    public function botcmd_forget($author, $botmsg) {
        if (empty($botmsg)) {
            return array(
                'bot'=>$this->respondsTo(),
                'status'=>'error',
                'scope'=>'#private',
                'message'=>"$author, please don't ask me to forget everything you've told me!");
        }

        $faq = $this->recallInfo($author, $botmsg);
        if (!empty($faq)) {
            $this->forgetInfo($author, $botmsg);
            $message = "$author, I've forgotten everything you've told me about '$botmsg'.";
        } else {
            $message = "$author, you haven't told me anything about '$botmsg'.";
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$message);
    }

    public function botcmd_hello($author, $botmsg) {
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>"Hi $author, I can answer questions about LoveMachine with the 'faq' command.  Type '@faq help' to get started.");
    }

    public function botcmd_list($author, $botmsg) {
        $faqList = $this->recallInfo();
        if (!empty($faqList)) {
            $faqs = array();
            foreach ($faqList as $writer=>$topics) {
                foreach ($topics as $topic=>$info) {
                    $faqs[$topic] = 1;
                }
            }
            $message = "$author, I can tell you about these things:\n".implode(', ', array_keys($faqs));
        } else {
            $message = "$author, I can't tell you about anything, because no one has told me anything yet.";
        }

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>$message);
    }

    public function botcmd_help($author, $botmsg) {
        switch ($botmsg) {
        case 'add':
            $message = "'add' is how I learn new things.  Type '@".$this->respondsTo()." add {topic-name}' to teach me something new.";
            break;
        case 'forget':
            $message = "'forget' makes me forget things.  Type '@".$this->respondsTo()." forget {topic-name}' to make me forget something.";
            break;
        case 'list':
            $message = "I can tell you the things I know about if you tell me to 'list'.";
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

    public function botwatch_state($args) {
        $author = $args[0];
        $botmsg = trim($args[1]);

        switch ($this->recallInfo($author, 'faq-state')) {
        case 'add':
            $title = str_replace(',', '', trim($this->recallInfo($author, 'faq-add')));

            $this->forgetInfo($author, 'faq-add');
            $this->forgetInfo($author, 'faq-state');

            if (empty($botmsg) || $botmsg == 'cancel') {
                return array(
                    'bot'=>$this->respondsTo(),
                    'status'=>'ok',
                    'scope'=>'#private',
                    'message'=>"Okay, $author, you can tell me about '$title' later.");
            }

            $this->rememberInfo($author, $title, $botmsg);
            return array(
                'bot'=>$this->respondsTo(),
                'status'=>'ok-quiet',
                'scope'=>'#private',
                'message'=>"$author, thanks for telling me about '$title'.");
        }

        return array('bot'=>$this->respondsTo(), 'status'=>'ignore');
    }

    /*
     * Protected Methods
     */
}

$faq_bot = new FAQBot();
