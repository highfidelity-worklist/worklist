<div id="budgetPopup" style="display:none;">
  <div id="be-block" >
        <label class="info-label" >Your Fees</label>   
        <table>
            <tr>
                <td class="be-table_cell1">this week:</td>
                <td class="be-table_cell2"><a href="#feesToolTip" class="feesum" id="fees-week">$<?php echo $sum['week']; ?></a></td>
            </tr>
            <tr>
                <td class="be-table_cell1">this month:</td>
                <td class="be-table_cell2"><a href="#feesToolTip" class="feesum" id="fees-month">$<?php echo $sum['month']; ?></a></td>
            </tr>
        </table>
<?php
if (isset($_SESSION['userid']) && isset($_SESSION['is_runner']) && $_SESSION['is_runner']) {
    if (!isset($user)) {
        $user = new User();
        $user->findUserById($_SESSION['userid']);
    }
?>      
        <br />
        <label class="info-label" >Budget</label>   
        <table>
            <tr onclick="Budget.displayHistory(<? echo $_SESSION['userid'] ?>);">
                <td class="be-table_cell1 iToolTip budgetManaged1"><strong>Total Funds Managed (lifetime):</strong></td>
                <td class="be-table_cell2 iToolTip budgetManaged1"><strong>
                <?php echo(money_format('$ %i', $user->getTotalManaged())); ?></strong></td>
            </tr>
            <tr onclick="Budget.displayHistory(<? echo $_SESSION['userid'] ?>);">
                <td class="be-table_cell1 iToolTip budgetRemaining1"><strong>Remaining Funds (from budgets):</strong></td>
                <td class="be-table_cell2 iToolTip budgetRemaining1"><strong>
                <?php echo(money_format('$ %i', $user->setRemainingFunds())); ?></strong></td>
            </tr>
            <tr>
                <td onclick="Budget.budgetExpand(0)" class="be-table_cell1 iToolTip budgetAllocated1">Allocated:</td>
                <td onclick="Budget.budgetExpand(0)" class="be-table_cell2 iToolTip budgetAllocated1">
                <?php echo(money_format('$ %i', $user->getAllocated())); ?></td>
            </tr>
            <tr>
                <td onclick="Budget.budgetExpand(1)" class="be-table_cell1 iToolTip budgetSubmitted1">Submitted:</td>
                <td onclick="Budget.budgetExpand(1)" class="be-table_cell2 iToolTip budgetSubmitted1">
                <?php echo(money_format('$ %i', $user->getSubmitted())); ?></td>
            </tr>
            <tr>
                <td onclick="Budget.budgetExpand(2)" class="be-table_cell1 iToolTip budgetPaid1">Paid:</td>
                <td onclick="Budget.budgetExpand(2)" class="be-table_cell2 iToolTip budgetPaid1">
                <?php echo(money_format('$ %i', $user->getPaid())); ?></td>
            </tr>
            <tr>
                <td onclick="Budget.showBudgetTransferDialog()" class="be-table_cell1 iToolTip budgetTransfered1">Transferred:</td>
                <td onclick="Budget.showBudgetTransferDialog()" class="be-table_cell2 iToolTip budgetTransfered1">
                <?php echo(money_format('$ %i', $user->getTransfered())); ?></td>
            </tr>
<?php } ?>
        </table>
    </div>
</div>
