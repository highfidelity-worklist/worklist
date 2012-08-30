<?php

require_once ("config.php");

class Penalty{

	const CAN_PENALIZE = 1; 
	const ALREADY_PENALIZED = 2; // if a user already penalized someone he can't do it again before penalized user goes to PB
	const CANT_PENALIZE = 3; // mechanic himself has been more than 2 times in PB - only good boys can penalize others :D
	const ALREADY_SUSPENDED = 4;

	const NOT_PENALIZED = 5;
	const IN_BOX = 6;
	const SUSPENDED = 7;


	static public function getUserPenalties($userId, $ip = null){

		$penaltiesData = null;
		$userId = intval($userId);
		$ip = $ip ? mysql_real_escape_string($ip) : null;
		$and = $ip ? " AND `ip` = '" . mysql_real_escape_string($ip) . "'" : '';


		$sql = "SELECT *, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`date`) AS `timediff` "
			. "FROM `" . PENALTIES . "` WHERE `user_id` = $userId" . $and . " ORDER BY `id` ASC";
		
		if($res = mysql_query($sql)){
			
			$penaltiesData = array('sums' => array( 'timeleft' => 0 ), 'data' => array());
			$timediff = 0;
			while($row = mysql_fetch_assoc($res)){

				$penaltiesData['data'][] = array('reason' => $row['reason'],
								  'from' => $row['from'],
								  'time' => date('d/m/Y h:i a', strtotime($row['date'])));

				$timediff = $row['timediff'];
			}

			// checking how much time (if any) user has to be in P box 
			$penaltiesData['sums']['timeleft'] = getTimeLeft(count($penaltiesData['data']), $timediff);
		}
  
		if(count($penaltiesData['data']) >= 12){

			$penaltiesData['sums']['status'] = 'suspended';
		}else{
			$penaltiesData['sums']['status'] = 'notsuspended';
		}

		return $penaltiesData;
	}

	static public function getOnlinePenalties($userType = 'registered'){

		$onlinePenalties = array();

		switch($userType){

		    case 'registered':

			$sql = "SELECT  `user_id`, COUNT(*) AS `penalties_count`, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MAX(`date`)) AS `timediff`"
				. " FROM `" . PENALTIES . "` WHERE `user_id` IN"
					. " (SELECT `user_id` FROM `" . RECENT_SPEAKERS . "` WHERE `online`!=0 AND `ip`='0')"
				. " GROUP BY `user_id`";

			if($res = mysql_query($sql)){
				
				while($row = mysql_fetch_assoc($res)){
				    
					$status = $row['penalties_count'] >= 12 ? 'suspended' : 'notsuspended'; 
					$onlinePenalties[$row['user_id']] = array('count' => $row['penalties_count'],
										  'timeleft' => getTimeLeft($row['penalties_count'], $row['timediff']),
										  'status' => $status);
				}
			}

		    break;

		    case 'guest':

			$sql = "SELECT  `ip`, COUNT(*) AS `penalties_count`, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MAX(`date`)) AS `timediff`"
				. " FROM `" . PENALTIES . "` WHERE `ip` IN"
					. " (SELECT `ip` FROM `" . RECENT_SPEAKERS . "` WHERE `online`!=0 AND `ip`!='0')"
				. " GROUP BY `ip`";

			if($res = mysql_query($sql)){
				
				while($row = mysql_fetch_assoc($res)){

					$status = $row['penalties_count'] >= 12 ? 'suspended' : 'notsuspended';
					$onlinePenalties[$row['ip']] =array('count' => $row['penalties_count'],
										  'timeleft' => getTimeLeft($row['penalties_count'], $row['timediff']),
										  'status' => $status);
				}
			}

		    break;

		}

		return $onlinePenalties;
	}

	static public function penalizeUser($data){
	
		if(self::checkPenalize($data['from'], $data['id'], $data['ip']) == self::CAN_PENALIZE){

			foreach($data as $key => $value){

				$data[$key] = mysql_real_escape_string($data[$key]);
			}

			$sql = "INSERT INTO `" . PENALTIES . "` (`id`, `user_id`, `ip`, `reason`, `from`, `date`)"
				. " VALUES (NULL, '{$data['id']}', '{$data['ip']}', '{$data['reason']}', '{$data['from']}', CURRENT_TIMESTAMP)";
			return mysql_unbuffered_query($sql);
		}else{
			return false;
		}
	}

	// used to check penalty status before penalizing
	static public function checkPenalize($penalizer_id, $penalated_id, $penalated_ip){
		
		// check if user has been in penalty box more that twice (3 penalties send user to box, so 9 makes it 3 times in a box)
		$penalizerData = self::getUserPenalties($penalizer_id);
		if(count($penalizerData['data']) < 9){

			// if it's a 'Guest' user - add checking by ip
			$penalatedData = $penalated_id != 0 ? self::getUserPenalties($penalated_id) : self::getUserPenalties(0, $penalated_ip);
			$can = true;
			$i = 1;

			// we can't penalize already suspended user
			if(count($penalatedData['data']) >= 12){
				return self::ALREADY_SUSPENDED;
			}

			foreach($penalatedData['data'] as $penalty){

				// if user already penalized another user than he cant do it again
				if($penalizer_id == $penalty['from']){
					$can = false;
				}

				// unless new round is started
				if($i == 3 || $i == 6 || $i == 9){

					$can = true;
				}

				$i++;
			}

			if($can){
				
				if($penalatedData['sums']['timeleft'] > 0){

					return self::IN_BOX;
				}else{

					return self::CAN_PENALIZE;
				}
			}else{
				// user is already penalized by given user - will have to wait till next round
				return self::ALREADY_PENALIZED;	
			}

 
		}else{
			return self::CANT_PENALIZE;
		}
	}

	// wrapper to get simple status about given user (or Guest with ip)
	static public function getSimpleStatus($userId, $ip = null){

		$penalizeData = $userId != 0 ? self::getUserPenalties($userId) : self::getUserPenalties(0, $ip);

		if(count($penalizeData['data']) >= 12){

			return self::SUSPENDED;
		}

		if($penalizeData['sums']['timeleft'] > 0){

			return self::IN_BOX;
		}

		return self::NOT_PENALIZED;
		
	}

}

	// returns time user has to be in Penalty Box according to how many penalties he has received
	function getTimeLeft($count, $timediff){

		switch($count){
	
		    case 3:

			 return (PB_TIMEOUT_1 - $timediff) > 0 ? (PB_TIMEOUT_1 - $timediff) : 0;
		    break;

		    case 6:

			 return (PB_TIMEOUT_2 - $timediff) > 0 ? (PB_TIMEOUT_2 - $timediff) : 0;
		    break;

		    case 9:

			 return (PB_TIMEOUT_3 - $timediff) > 0 ? (PB_TIMEOUT_3 - $timediff) : 0;
		    break;

		    default:
			
			return 0;
		}
	}

?> 
