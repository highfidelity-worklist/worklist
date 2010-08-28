<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("class/Report.class.php");
$report = new Report();

$phistory = $report->getLastPaymentInfo(intval($_GET['cid']));
#var_dump($phistory);
?>
<table border="0" cellspacing="5" cellpadding="5">
    <tr>
        <th>id</th>
        <th>Method</th>
        <th>Status</th>
        <th>Amount</th>
        <th>Date</th>
        <th>Token</th>
    </tr>
    <?php foreach($phistory as $item): ?>
    <tr>
        <td><?php echo $item->id ?></td>
        <td><?php echo $item->payment_method ?></td>
        <td><?php echo $item->payment_status ?></td>
        <td><?php echo $item->payment_amount ?>$</td>
        <td><?php echo $item->payment_date ?></td>
        <td><?php echo $item->paypal_token ?></td>
    </tr>
    <?php endforeach;?>
</table>