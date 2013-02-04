<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include ("config.php");
include ("class.session_handler.php");
include ("functions.php");

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
                        <th><span>User</span></th>
                        <th><span>Amount</span></th>
                        <th><span>Done in</span></th>
                        <th><span>Expires</span></th>
                        <th><span>Notes</span></th>
                        <th><span>Accept</span></th>
                        <th><span>Mechanic</span></th>
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
                        <td>
                            <a href="userinfo.php?id=' . $bid['bidder_id'] . '" target="_blank">
                               ' . $bid['nickname'] . '
                            </a>
                        </td>
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
                </tbody>
            </table>
            <input type="submit" style="display:none;" name="accept_multiple_bid" id="accept_multiple_bid" value="Confirm Accept Selected">
            <input type="hidden" id="budget_id_multiple_bid" name="budget_id" value="" />
            <input type="button" id="accept_bid_select_budget"
                   name="accept_bid_select_budget" value="Accept Selected">
        </form>
<?php
} else {
    echo 'No Bid Present';
}
