<?php
    include ("config.php");

    Session::check();
    checkLogin();

    mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD) or die(mysql_error());;
    mysql_select_db(DB_NAME) or die(mysql_error());

    $survey = new Survey();
    $message = '';

    if(isset($_GET['id'])){
	$survey -> getFromDatabase(intval($_GET['id']));
    }

    if($surveyData = $survey -> getSurveyData()){
	$message = '';
	if(isset($_REQUEST['choiceid'])){
	      if(isset($_SESSION['userid'])){
		      if($survey -> vote(intval($_REQUEST['choiceid']), $_SESSION['userid'])){
			      $message = 'Thank you for your vote!';
		      }else{
			      $message = 'You already voted in this survey!';
		      }
	      }else{
		      $message = 'Guests cannot vote!';
	      }
	}
    }

    include("head.html");
    if($surveyData = $survey -> getSurveyData()){
	echo '<title>Surveys - ' . $surveyData['question'] . '</title>';
    }else{
	echo '<title>Surveys - Survey not found</title>';	
    }
?> 
	<link rel="stylesheet" type="text/css" href="css/survey.css" />
    </head>
    <body>
	<div id = "wrapper">
	    <div id = header>
		<h2>LoveMachine Live Workroom - Surveys</h2>
	    </div>
<?php
    if($surveyData = $survey -> getSurveyData()){
	if($message){
		echo '
    <div class = "survey-message">' . $message . '</div>';	      
	}
	
	$userId = isset($_SESSION['userid']) ? $_SESSION['userid'] : 0;
	if($surveyData['to_end'] == 0 || Survey::checkVoted(intval($_GET['id']), $userId)){
	      echo formatSurveyForResults($survey -> getSurveyData());
	}else{
	      if($userId != 0){ 
		      echo formatSurveyForVote($surveyData);
	      }else{
		      echo formatSurveyForGuests($surveyData);
	      }
	}
    }else{
	echo "Survey not found!";
    }
?>
	</div>
    </body>

</html>

<?php

function formatSurveyHead(&$surveydata){
    
    $surveyHead = '';
    $surveyHead .= '
  <div class = "survey-title"><h2>' . $surveydata['question'] . '</h2>
    <h3>By ' . $surveydata['nickname'] . '</h3>
    <div class = "survey-created">Created: ' . date('m/d/Y h:i a', strtotime($surveydata['starts'])) . '</div>
    <div class = "survey-status">Status: ';

    if($surveydata['to_end'] == 0){
	$surveyHead .= 'Finished';
    }else{
	$surveyHead .= 'Active (time to completion: ' . getRelativeTime($surveydata['to_end']) . ')';
    }
    $surveyHead .= '</div>
    <div class = "survey-voters">Number of voters: ' . $surveydata['votes'] . ' 
    </div>
    </div>';

    return $surveyHead;
}

function formatSurveyForVote($surveydata){
    
    $surveyHtml = '<div class = "survey-wrapper">';
    $surveyHtml .= formatSurveyHead($surveydata) . '
  <div class = "survey-body-vote"><table><form action = "survey.php' . implode_get() . '" method = "POST">';

    foreach($surveydata['choices'] as $choice){
	$surveyHtml .= '
  <tr class = "survey-choice">
    <td class = "survey-choice-radio">
      <input type = "radio" name = "choiceid" value = "' . $choice['id'] . '">
    </td>
    <td class = "survey-choice-name">'
  . $choice['name'] . '</td></tr>';
    }

    $surveyHtml .= '
  </table>
  <input type = "submit" value = "Vote"></form>
  </div></div>';

    return $surveyHtml;
}

function formatSurveyForResults($surveydata){

    $surveyHtml = '<div class = "survey-wrapper">';
    $surveyHtml .= formatSurveyHead($surveydata) . '
  <div class = "survey-body-results"><table>';
    
    $total = (int)$surveydata['votes'];

    foreach($surveydata['choices'] as $choice){

	$percentage = ($total != 0) ? round($choice['votes']/$total*100) : 0;
	$surveyHtml .= '
  <tr class = "survey-choice">
    <td class = "survey-choice-name">'
  . $choice['name'] . '</td>
    <td class = "survey-choice-votes">'
  . $choice['votes'] . ' vote' . plural($choice['votes']) . '
   </td>
   <td>' . $percentage . '% </td>
   <td><div class = "survey-percentage" style = "width: ' . $percentage . 'px;">&nbsp</div></td>
  </tr>';

    }

    $surveyHtml .= '
  </table>
  </div></div>';

    return $surveyHtml;
}

function formatSurveyForGuests($surveydata){

    $surveyHtml = '<div class = "survey-wrapper">';
    $surveyHtml .= formatSurveyHead($surveydata) . '
  <div class = "survey-body-results">
    <div class = "survey-noresults" >
      Results are not available yet.
    </div>
  </div></div>';

    return $surveyHtml;
}


function implode_get() {
    $first = true;
    $output = '';
    foreach($_GET as $key => $value) {
        if ($first) {
            $output = '?'.$key.'='.$value;
            $first = false;
        } else {
            $output .= '&'.$key.'='.$value;   
        }
    }
    return $output;
}

function plural($num){
    if ($num != 1)
    return "s";
}

function getRelativeTime($time_in_sec){

    $diff = $time_in_sec;

    if ($diff<60)
	return $diff . " second" . plural($diff);

    $diff = round($diff/60);
    if ($diff<60)
	return $diff . " minute" . plural($diff);

    $diff = round($diff/60);
    if ($diff<24)
	return $diff . " hour" . plural($diff);

    $diff = round($diff/24);
	return $diff . " day" . plural($diff);
}

?>
