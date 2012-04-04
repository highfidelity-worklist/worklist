<?php

  require_once dirname(__FILE__) . '/config.php';

  mysql_connect(DB_SERVER, DB_USER,DB_PASSWORD) or die(mysql_error()) ;
  mysql_select_db(DB_NAME) or die(mysql_error());

  class Survey{
    
	private $surveyData = null;
	public static $wheres = array('active' => " AND UNIX_TIMESTAMP(`starts`) + `duration` - UNIX_TIMESTAMP(NOW()) > 0",
				      'ended'  => " AND UNIX_TIMESTAMP(`starts`) + `duration` - UNIX_TIMESTAMP(NOW()) <= 0");

	// parse data from string get from journal survey bot into array
	public function parseFromString($surveyString, $userId){

		// get question
		if(preg_match('/(.*),(| *)\[/', $surveyString, $matches)){
		    $question = mysql_real_escape_string(trim($matches[1]));
		}else{
		    return false;
		}

		if(preg_match('/\d*$/', $surveyString, $matches)){
		    $duration = (int)trim($matches[0])*60;
		}else{
		    return false;
		}

		// get choices inside '[' and ']' delimited by ','
		if(preg_match('/,(| *)\[(.*)\](| *),/', $surveyString, $matches)){
		    $choices = explode(',', $matches[2]);
		}else{
		    return false;
		}

		// save survey into database
		$sql = "INSERT INTO `" . SURVEYS . "` (`id`, `user_id`, `question`, `starts`, `duration`)" 
		      . " VALUES (NULL, '$userId', '$question', CURRENT_TIMESTAMP, '$duration')";
		mysql_unbuffered_query($sql);
		$surveyId = mysql_insert_id();

		foreach($choices as $choice){
		    $choiceName = mysql_real_escape_string(trim($choice));		  
		    $sql = "INSERT INTO `" . SURVEY_CHOICES . "` (`id`, `survey_id`, `name`, `votes`)"
			  . " VALUES (NULL, '$surveyId', '$choiceName', '0')";
		    mysql_unbuffered_query($sql);
		}
	  
		// update data in current instance
		$this -> getFromDatabase($surveyId);
		return true;
	}

	// load already saved survey
	public function getFromDatabase($surveyId){

		$this->surveyData = null;
		
		if($this->surveyData = self::getSurveyById($surveyId)){
			return true;
		}else{
			return false;
		}
	}

	public static function getSurveyList($filter = '', $userId = null){

		$surveyList = array();
		$where = isset(self::$wheres[$filter]) ? self::$wheres[$filter] : '';
		if($userId){
			$where .= " AND `user_id` = $userId";
		}
		$sql = "SELECT `id` FROM `" . SURVEYS . "` WHERE 1 ".$where;
		$res = mysql_query($sql);
		if($res){
			while($row = mysql_fetch_assoc($res)){
			      $surveyList[] = self::getSurveyById($row['id']);
			}
			return $surveyList;
		}else{
			return null;
		}

	}

	public static function getSurveyListForUser($userId, $filter = ''){  

		return self::getSurveyList($filter, $userId);
	}

	public static function getSurveyById($surveyId){

		$choices = array();
		$sql = "SELECT `" . SURVEYS . "`.*, IF(UNIX_TIMESTAMP(`starts`) + `duration` - UNIX_TIMESTAMP(NOW()) > 0,"
			. " UNIX_TIMESTAMP(`starts`) + `duration` - UNIX_TIMESTAMP(NOW()), 0) AS `to_end`, `nickname`, SUM(`votes`) AS `votes` "
			. "FROM `" . SURVEYS . "` LEFT JOIN " . USERS . " ON `user_id` = " . USERS . ".`id` "
			. "LEFT JOIN `" . SURVEY_CHOICES . "` ON `survey_id` = `" . SURVEYS . "`.`id` "
			. " WHERE `" . SURVEYS . "`.`id` = '$surveyId' GROUP BY `" . SURVEYS . "`.`id`";
		$res = mysql_query($sql);
		
		if($surveyRow = mysql_fetch_assoc($res)){
			$sql = "SELECT * FROM `" . SURVEY_CHOICES . "` WHERE `survey_id` = '$surveyId'";
			$choiceRes = mysql_query($sql);

			if($choiceRes){
			while($choiceRow = mysql_fetch_assoc($choiceRes)){
				$choices[] = array('name' => $choiceRow['name'],
						   'id' => $choiceRow['id'],
						   'votes' => $choiceRow['votes']);
			}
			}

			$surveyData = array('question' => $surveyRow['question'],
					    'choices'  => $choices,
					    'starts' => $surveyRow['starts'],
					    'duration' => $surveyRow['duration'],
					    'to_end' => $surveyRow['to_end'],
					    'nickname' => $surveyRow['nickname'],
					    'votes' => $surveyRow['votes'],
					    'id' => $surveyRow['id']);

			return $surveyData;
		}else{
			return null;
		}		
	}

	public function vote($choiceId, $userId){

		if(!self::checkVoted($this->surveyData['id'], $userId)){

			$sql = "UPDATE `" . SURVEY_CHOICES . "` SET `votes` = `votes` + 1 WHERE `id` = '$choiceId'";
			if(mysql_unbuffered_query($sql)){

				// saving user data so he can't vote second time
				$sql = "INSERT INTO `" . SURVEY_VOTERS . "` (`id`, `survey_id`, `user_id`) "
					. "VALUES (NULL, '{$this->surveyData['id']}', '$userId')";
				mysql_unbuffered_query($sql);
			
				// update data in current instance
				$this -> getFromDatabase($this->surveyData['id']);
				return true;
			}
		}else{
			return false;
		}

	}

	// returns true if user already voted for given survey
	public static function checkVoted($surveyId, $userId){

		$sql = "SELECT * FROM `" . SURVEY_VOTERS . "` WHERE `survey_id` = '$surveyId' AND `user_id` = $userId";
		$res = mysql_query($sql);

		if(mysql_fetch_row($res)){
			return true;
		}else{
			return false;
		}		
	}

	public function getSurveyData(){
	  if($this->surveyData){
	      return $this->surveyData;
	  }else{
	      return false;
	  }
	}

  }

?>