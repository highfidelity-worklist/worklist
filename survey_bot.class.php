<?php 
// Bot class for the 'mem' journal bot.
// 
//  vim:ts=4:et
include("survey.class.php");
include_once("send_email.php");

class SurveyBot extends Bot
{
    public function __construct() {
        parent::__construct();

        self::registerBot($this);
        self::watchFor($this, 'message', 'botwatch_state');

    $this->survey = new Survey();
    }

    public function __call($request, $args)
    {
    // if no command is speified - try to create a survey from data provided
       /* $author = $args[0];
        $botmsg = substr($request, 7);
    $this->botcmd_add($author, $botmsg); */
    }


    public function respondsTo() {
        return 'survey';
    }

    public function understands($cmd=null) {
        if (!$cmd) {
            return parent::understands($cmd);
        }
        return 'true';
    }

    public function botcmd_add($author, $botmsg) {

    $userId = isset($_SESSION['userid']) ? $_SESSION['userid'] : 0;
    $this -> survey -> parseFromString($botmsg, $userId);
    $surveyData = $this -> survey -> getSurveyData();
    
    if($surveyData){

        global $him_bot;
        $him_bot->rememberInfo($this->respondsTo(), time(), "[survey]{$surveyData['question']}:posted by $author");
        
        $message = $author . ' created a survey:<br /><a href="' . SERVER_URL . 'survey.php?id=' . $surveyData['id'] . '" target = "_blank">' 
                    . $surveyData['question'] .'</a><br /><br />Choices are: <br /><br />';
        foreach($surveyData['choices'] as $choice){
            $message .= '<a href="' . SERVER_URL . 'survey.php?id=' . $surveyData['id'] . '&choiceid=' . $choice['id'] . '" target = "_blank">'
            . $choice['name'] . '</a><br />';
        }
        $message .= '<br />You are welcome to vote!';

        emailSurvey($surveyData['question'], $message); 
          
        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#public',
            'message'=>$message);
    }else{

        return array(
            'bot'=>$this->respondsTo(),
            'status'=>'ok',
            'scope'=>'#private',
            'message'=>"Could not parse the survey string. Please type '@".$this->respondsTo()." help add' to see string format.");
    }
    }

    public function botcmd_list($author, $botmsg) {

    $filter = '';
    switch ($botmsg){
        case 'active':
        $filter = 'active';
        $message = 'List of all active surveys: <br />';
        break;
        case 'finished':
        $filter = 'ended';
        $message = 'List of all finished surveys: <br />';
        break;
        default:
        $message = 'List of all surveys: <br />';
        break;
    }
    $surveyList = Survey::getSurveyList($filter);
    
    foreach($surveyList as $survey){
        $message .= '<a href="survey.php?id=' . $survey['id'] . '" target = "_blank">'
                . $survey['question'] .'</a> By ' . $survey['nickname'] . '<br />';
    }
    
    if(count($surveyList) == 0){
        $message = 'No results';
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
            'message'=>"Hi $author, use the 'survey' command to create short anonymous surveys.  Type '@survey help' to get started.");
    }


    public function botcmd_help($author, $botmsg) {
        switch ($botmsg) {
        case 'add':            $message = "'add' is how I create surveys.  Type '@".$this->respondsTo()." add" 
                      . " Survey Topic, [Yes, No], 45' where '45' is a number of munutes for survey to last.";
            break;
        case 'list':           $message = "Type '@".$this->respondsTo()." list' to get all surveys<br />"
                      . "'@".$this->respondsTo()." list active' to get all currently active surveys<br />"
                      . "'@".$this->respondsTo()." list finished' to get all finished surveys<br />";
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

        return array('bot'=>$this->respondsTo(), 'status'=>'ignore');
    }


}

function emailSurvey($question, $message) {
    $subject = "Journal Survey: " . $question;
    $body = $message;
    sl_send_email(SURVEYS_EMAIL, $subject, $body);
}

$survey_bot = new SurveyBot();
