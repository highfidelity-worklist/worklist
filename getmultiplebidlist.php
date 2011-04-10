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
$data = '';

if (sizeof($bids) > 0 ) {
?>
        <form name="popup-multiple-bid-form" id="popup-multiple-bid-form" action="" method="post">
             <table width="100%" class="table-bids">
                <caption class="table-caption" >
                    <b>Bids</b>
                </caption>
                <thead>
                    <tr class="table-hdng">
                        <td>User</td>
                        <td>Amount</td>
                        <td>Done in</td>
                        <td>Expires</td>
                        <td>Notes</td>
                        <td>Accept</td>
                        <td>Mechanic</td>
                    </tr>
                </thead>
                <tbody>
<?php
    foreach($bids as $bid) {
        $expire_class = '';
        if ($bid['expires'] <= BID_EXPIRE_WARNING) {
            $expire_class = 'class="warn"';
        }
        $data .= '
                    <tr>
                        <td><a href="#" onclick="javascript:showUserInfo(' . $bid['bidder_id'] . ');">'.$bid['nickname'].'</a></td>
                        <td>'.$bid['bid_amount'].'</td>
                        <td>'.$bid['done_in'].'</td>
                        <td ' . $expire_class . '>'. relativeTime($bid['expires']) .'</td>
                        <td>'.$bid['notes'].'</td>
                        <td><input type="checkbox" class="acceptMechanic" name="chkMultipleBid[]" value="'.$bid['id'].'" /></td>
                        <td><input type="checkbox" name="mechanic" class="chkMechanic" value="'.$bid['bidder_id'].'" /></td>
                    </tr>';
    }
    echo $data;
?>
                    <tr>
                        <td colspan="7" align="right"><input type="submit" name="accept_multiple_bid" value="Accept Selected"></td>
                    </tr>
                </tbody>
            </table>
        </form>
<?php
} else {
    echo 'No Bid Present';
}
