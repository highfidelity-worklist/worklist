<?php
    $mysql_host = DB_SERVER;
    $mysql_user = DB_USER;
    $mysql_pass = DB_PASSWORD;
    $db_name = DB_NAME;
    mysql_connect($mysql_host, $mysql_user,$mysql_pass) or die(mysql_error()) ;
    mysql_select_db($db_name) or die(mysql_error());

class WorkItem
{

    function getWorkItem($worklist_id){
		$query = "SELECT w.id, w.summary,w.owner_id, u.nickname, w.status, w.notes, w.funded
			  FROM  ".WORKLIST. " as w  
			  LEFT JOIN ".USERS." as u ON w.owner_id = u.id 
			  WHERE w.id = '$worklist_id'";
		$result_query = mysql_query($query);
		$row =  $result_query ? mysql_fetch_assoc($result_query) : null;
		return !empty($row) ? $row : null;
    }

    function getBids($worklist_id) {
		$query = "SELECT bids.`id`, bids.`bidder_id`, `email`, u.`nickname`, bids.`bid_amount`, 
				TIMESTAMPDIFF(SECOND, bids.`bid_created`, NOW()) AS `delta`, 
				TIMESTAMPDIFF(SECOND, NOW(), bids.`bid_done`) AS `future_delta`, 
				DATE_FORMAT(bids.`bid_done`, '%m/%d/%Y') AS `bid_done` 
				FROM `".BIDS. "` as bids
				INNER JOIN `".USERS."` as u on (u.id = bids.bidder_id)
				WHERE bids.worklist_id=".$worklist_id.
				" and bids.withdrawn = 0 ORDER BY bids.`id` DESC";
		$result_query = mysql_query($query);
		if($result_query){
			$temp_array = array();
			while($row = mysql_fetch_assoc($result_query)) {
			$temp_array[] = $row;
			}
			return $temp_array;
		}
		else {
			return null;
		}
    }

    function getFees($worklist_id){
		$query = "SELECT fees.`id`, fees.`amount`, u.`nickname`, fees.`desc`, DATE_FORMAT(fees.`date`, '%m/%d/%Y') as date, fees.`paid` 
			FROM `".FEES. "` as fees, `".USERS."` as u
			WHERE worklist_id = ".$worklist_id." 
			AND u.`id` = fees.`user_id` and fees.withdrawn = 0 ";

		$result_query = mysql_query($query);
		if($result_query){
			$temp_array = array();
			while($row = mysql_fetch_assoc($result_query)) {
			$temp_array[] = $row;
			}
			return $temp_array;
		}
		else {
			return null;
		}
    }
    
    function placeBid($mechanic_id, $username, $itemid, $bid_amount, $done_by, $timezone,$notes) {
		$query =  "INSERT INTO `".BIDS."` 
				(`id`, `bidder_id`, `email`,`worklist_id`,`bid_amount`,`bid_created`,`bid_done`, `notes`) 
			  VALUES
				(NULL, '$mechanic_id', '$username', '$itemid', '$bid_amount', NOW(), FROM_UNIXTIME('".strtotime($done_by." ".$timezone)."'), '$notes')";

		return mysql_query($query) ? mysql_insert_id() : null;
    }

    function getUserDetails($mechanic_id) {
		$query = "SELECT nickname, username FROM ".USERS." WHERE id='{$mechanic_id}'";
		$result_query = mysql_query($query);
        return  $result_query ?  mysql_fetch_assoc($result_query) : null;  
    }

    function getOwnerSummary($worklist_id) {
	    $query = "SELECT `username`,`is_runner`, `summary` FROM `users`, `worklist` WHERE `worklist`.`creator_id` = `users`.`id` AND `worklist`.`id` = ".$worklist_id;
	    $result_query = mysql_query($query);
	    return $result_query ? mysql_fetch_assoc($result_query) : null ;
    }


    function updateWorkItem($worklist_id, $summary, $notes, $status, $funded) {
        $query = 'UPDATE '.WORKLIST.' SET summary= "'.$summary.'", notes="'.$notes.'", status="' .$status.'" ';
	if($funded != null) {
	  $query .= ' ,funded='. $funded ;
	}
	$query .= ' WHERE id='.$worklist_id; 
        return mysql_query($query) ? 1 : 0;
    }

    function getSumOfFee($worklist_id) {
	$query = "SELECT SUM(`amount`) FROM `".FEES."` WHERE worklist_id = ".$worklist_id . " and withdrawn = 0 ";
	$result_query = mysql_query($query);
	$row = $result_query ? mysql_fetch_row($result_query) : null;
	return !empty($row) ? $row[0] : 0;
    }

    // Accept a bid given it's Bid id
    function acceptBid($bid_id) {
	    $res = mysql_query('SELECT * FROM `'.BIDS.'` WHERE `id`='.$bid_id);
	    $bid_info = mysql_fetch_assoc($res);

	    // Get bidder nickname
	    $res = mysql_query("select nickname from ".USERS." where id='{$bid_info['bidder_id']}'");
	    if ($res && ($row = mysql_fetch_assoc($res))) {
		$bidder_nickname = $row['nickname'];
	    }

	    //changing owner of the job
	    mysql_unbuffered_query("UPDATE `worklist` SET `mechanic_id` =  '".$bid_info['bidder_id']."', `status` = 'WORKING' WHERE `worklist`.`id` = ".$bid_info['worklist_id']);
	    //marking bid as "accepted"
	    mysql_unbuffered_query("UPDATE `bids` SET `accepted` =  1 WHERE `id` = ".$bid_id);
	    //adding bid amount to list of fees
	    mysql_unbuffered_query("INSERT INTO `".FEES."` (`id`, `worklist_id`, `amount`, `user_id`, `desc`, `date`, `bid_id`) VALUES (NULL, ".$bid_info['worklist_id'].", '".$bid_info['bid_amount']."', '".$bid_info['bidder_id']."', 'Accepted Bid', NOW(), '$bid_id')");
	    $bid_info['summary'] = getWorkItemSummary($bid_info['worklist_id']);
	    return $bid_info;
    }

 }// end of the class
