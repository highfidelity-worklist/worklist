<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");
require 'workitem.class.php';
$job_id = isset($_REQUEST['job_id']) ? (int)$_REQUEST['job_id'] : 0;
if ($job_id == 0) {
	echo $job_id;
    return;
}
$workItem = new WorkItem();
$bids = $workItem->getBids($job_id);
if(sizeof($bids) > 0 ){
 $data = '<form name = "popup-multiple-bid-form" id = "popup-multiple-bid-form" action="" method="post">
 <table width="100%" class="table-bids">
							<caption class="table-caption" >
								<b>Bids</b>
							</caption>
							<thead>
								<tr class="table-hdng">
									<td>Email</td>
									<td>Bid Amount</td>
									<td>Done by</td>
									<td>Notes</td>
									<td>Select</td>
								</tr>
							</thead>
							<tbody> ';
				foreach($bids as $bid){
			$data .= '<tr>
				<td>'.$bid['email'].'</td>
				<td>'.$bid['bid_amount'].'</td>
				<td>'.$bid['bid_done'].'</td>
				<td>'.$bid['notes'].'</td>
				<td><input type="checkbox" name="chkMultipleBid[]" value="'.$bid['id'].'" /></td>
				</tr>';
				}
			$data .='<tr ><td colspan="5" align="right"><input type="submit" name="accept_multiple_bid" value="Accept Selected"></td></tr>
			</tbody></table></form>';
	}
	else{
		$data = "No Bid Present";
	}
echo $data;