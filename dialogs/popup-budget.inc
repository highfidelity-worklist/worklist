<?php
if (isset($_SESSION['userid']) && isset($_SESSION['is_runner']) && $_SESSION['is_runner']) {
    if (!isset($user)) {
        $user = new User();
        $user->findUserById($_SESSION['userid']);
    }
?>
<div id="budgetPopup" style="display:none;">
    <div id="be-block" >
        <table id="be-table">
            <tr onclick="Budget.displayHistory();">
                <td class="be-table_cell1 iToolTip budgetRemaining1"><strong>Remaining Funds (legacy):</strong></td>
                <td class="be-table_cell2 iToolTip budgetRemaining1"><strong>
                <?php echo(money_format('$ %i', $user->getRemainingFunds())); ?></strong></td>
            </tr>
            <tr onclick="Budget.displayHistory();">
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
                <td onclick="Budget.budgetExpand(3)" class="be-table_cell1 iToolTip budgetTransfered1">Transferred:</td>
                <td onclick="Budget.budgetExpand(3)" class="be-table_cell2 iToolTip budgetTransfered1">
                <?php echo(money_format('$ %i', $user->getTransfered())); ?></td>
            </tr>
        </table>
    </div>
</div>
<?php } ?>
