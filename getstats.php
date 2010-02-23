<?php
//  Copyright (c) 2010, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");

$req =  isset($_REQUEST['req'])? $_REQUEST['req'] : 'table';

	if( $req == 'currentlink' )	{
		$query_b = mysql_query( "SELECT status FROM ".WORKLIST." WHERE status = 'BIDDING'" );
		$query_w = mysql_query( "SELECT status FROM ".WORKLIST." WHERE status = 'WORKING'" );
		$count_b = mysql_num_rows( $query_b );
		$count_w = mysql_num_rows( $query_w );
		echo "<a href='javascript:ShowStats()' id='stats'>". $count_b. " jobs bidding, ". $count_w. " jobs underway</a>";
		
	}	else if( $req == 'current' )	{
		$query_b = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'bidding'");
		$query_w = mysql_query("SELECT status FROM ".WORKLIST." WHERE status = 'working'");
		$count_b = mysql_num_rows( $query_b );
		$count_w = mysql_num_rows( $query_w );
		$res = array( $count_b, $count_w );
		echo json_encode( $res );
	
	}	else	if( $req == 'fees' )	{
		// Get Average Fees in last 7 days
		$query = mysql_query( "SELECT AVG(amount) FROM ".FEES." INNER JOIN ".WORKLIST." ON
					".FEES.".worklist_id = ".WORKLIST.".id WHERE date > DATE_SUB(NOW(),
					INTERVAL 7 DAY) AND status = 'DONE'" );

		$rt = mysql_fetch_assoc( $query );
		echo json_encode( $rt );
	
	} else if( $req == 'table' )	{
		// Get jobs done in last 7 days
		$fees_q = mysql_query( "SELECT ".WORKLIST.".id,summary,nickname,amount,date,user_paid FROM ".FEES."
					INNER JOIN ".USERS." ON ".FEES.".user_id = ".USERS.".id INNER JOIN ".WORKLIST."
					ON ".FEES.".worklist_id = ".WORKLIST.".id WHERE status='DONE' AND
					date > DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY date DESC;" );
		$fees = array();
		// Prepare json
		while( $row = mysql_fetch_assoc( $fees_q ) )	{
			$fees[] = array( $row['id'], $row['summary'], $row['nickname'], $row['amount'], $row['date'], $row['user_paid'] );
		}

		echo json_encode( $fees );
		
	} else if( $req == 'runners' )	{
		// Get Top 10 runners
		$info_q = mysql_query( "SELECT nickname AS nick, (SELECT COUNT(*) FROM ".FEES." INNER JOIN ".USERS." ON
					".USERS.".id = ".FEES.".user_id WHERE ".USERS.".nickname=nick AND
					".USERS.".is_runner=1) AS fee_no, (SELECT COUNT(*) FROM ".FEES." INNER JOIN
					".USERS." ON ".USERS.".id=".FEES.".user_id INNER JOIN ".WORKLIST." ON
					".WORKLIST.".id=".FEES.".worklist_id WHERE ".WORKLIST.".status='WORKING'
					AND ".USERS.".nickname=nick) AS working_no FROM ".USERS." ORDER BY fee_no DESC" );

		$info = array();
		// Get user nicknames
		while( $row = mysql_fetch_assoc( $info_q ) )	{
			if( count( $info ) < 10 )	{
				if( !empty( $row['nick'] ) )	{
					$info[] = array( $row['nick'],$row['fee_no'],$row['working_no'] );
				}
			}
		}
		echo json_encode( $info );
	
	}	else if( $req == 'mechanics' )	{
		// Get Top 10 mechanics
		$info_q = mysql_query( "SELECT nickname AS nick, (SELECT COUNT(*) FROM ".BIDS." INNER JOIN ".USERS." ON
					".USERS.".id = ".BIDS.".bidder_id WHERE ".USERS.".nickname=nick) AS bid_no,
					(SELECT COUNT(*) FROM ".WORKLIST." INNER JOIN ".USERS." ON 
					".WORKLIST.".mechanic_id=".USERS.".id WHERE ".USERS.".nickname=nick AND
					".WORKLIST.".status='WORKING') AS work_no FROM ".USERS." ORDER BY work_no DESC" );

		$info = array();
		// Get user nicknames
		while( $row = mysql_fetch_assoc( $info_q ) )	{
			if( count( $info ) < 10 )	{
				if( !empty( $row['nick'] ) )	{
					$info[] = array( $row['nick'],$row['bid_no'],$row['work_no'] );
				}
			}
		}
		echo json_encode( $info );
	
	}	else if( $req == 'feeadders' )	{
		// Get the top 10 fee adders
		$info_q = mysql_query( "SELECT nickname AS nick,(SELECT COUNT(*) FROM ".FEES." INNER JOIN ".USERS." ON
					".USERS.".id = ".FEES.".user_id WHERE ".USERS.".nickname=nick) AS fee_no,
					(SELECT AVG(amount) FROM ".FEES." INNER JOIN ".USERS." ON
					".USERS.".id=".FEES.".user_id WHERE ".USERS.".nickname=nick) AS amount
					FROM ".USERS." ORDER BY fee_no DESC" );

		$info = array();
		while( $row = mysql_fetch_assoc( $info_q ) )	{
			if( count( $info ) < 10 )	{
				if( !empty( $row['nick'] ) )	{
					$info[] = array( $row['nick'],$row['fee_no'],$row['amount'] );
				}
			}
		}
		echo json_encode( $info );

	}	else if( $req == 'pastdue' )	{
		// Get the top 10 mechanics with "Past due" fees
		$info_q = mysql_query( "SELECT nickname AS nick,(SELECT COUNT(*) FROM ".BIDS." INNER JOIN ".USERS." ON
					".USERS.".id=".BIDS.".bidder_id INNER JOIN ".WORKLIST." ON
					".WORKLIST.".id=".BIDS.".worklist_id WHERE ".USERS.".nickname=nick
					AND ".WORKLIST.".status='WORKING' AND bid_done < NOW()) AS past_due
					FROM ".USERS." ORDER BY past_due DESC" );

		$info = array();
		while( $row = mysql_fetch_assoc( $info_q ) )	{
			if( count( $info ) < 10 )	{
				if( !empty( $row['nick'] ) )	{
					$info[] = array( $row['nick'],$row['past_due'] );
				}
			}
		}
		echo json_encode( $info );
	}

?>
