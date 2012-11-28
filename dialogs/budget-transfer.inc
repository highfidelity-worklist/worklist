<?php 
$user = new User();
$user->findUserById($_SESSION['userid']);	
$details = $user->getBudgetTransfersDetails();
?>

<div id="budget-transfer" class="popup-body" title = "More info">
    <!-- Report Table -->
	<table width="100%" id="table-transfer-expanded">
	    <thead>
	        <tr style="cursor:arrow;" class="table-hdng">
	            <td id="bt-id" width="40px">ID</td>
	            <td id="bt-budget" width="140px">Budget</td>
	            <td id="bt-date" width="30px">Date</td>
	            <td id="bt-recipient" width="40px">Recipient</td>
	            <td id="bt-amount" width="40px">Amount</td>
	        </tr>
	    </thead>
	    <tbody>
        <?php foreach ($details as $transfer): ?>
            <tr><td><?php echo $transfer['id'];?></td>
            <td><?php echo $transfer['reason'];?></td>
            <td><?php echo $transfer['transfer_date'];?></td>
            <?php $receiver_id=$transfer['receiver_id'];?>
            <?php echo '<td onclick="showUserInfo(' . $receiver_id . ')">'; ?>
            <?php echo $transfer['nickname'];?></td>
            <td><?php echo $transfer['amount_granted'];?></td></tr>
        <?php endforeach; ?>
	    </tbody>
	</table><br/>
	
</div>