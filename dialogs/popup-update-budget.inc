<script type="text/javascript">
MapToolTips();
</script>
<div id="update-budget">
    <form method="post" action = "">
        <label for="budget-amount" class="info-label">Budget: </label>&nbsp;
            $<span id="budget-amount"><?php echo $budget->amount; ?></span>
            <br/>
        <label for="budget-reason" class="info-label">For: </label><br/>
            <?php 
                $dis = '';
                if ($budgetClosed == 1) {
                    $dis = ' disabled="disabled" '; 
                }
            ?>
            <input type="text" id="budget-reason" name="budget-reason" value="<?php echo $budget->reason; ?>" <?php echo $dis; ?>/>
            <br/>
            <?php if (strpos(BUDGET_AUTHORIZED_USERS, "," . $reqUserId . ",") !== false &&
                    $budget->seed == 1) { ?>
            <label for="budget-source" class="info-label">Source: </label>
            <div class="budget-source">
                <?php echo $budget->source_data; ?>
            </div>
            <?php 
                } 
                if ($budget->seed == 0) { ?>
        <label for="budget-source" class="info-label">Source(s): </label>
        <div class="budget-source">
            <table class="budgetSourceTable" cellspacing="0" >
                <thead>
                    <tr>
                        <th class="date">Date</th>
                        <th class="giver">Grantor</th>
                        <th class="amount">Amount</th>
                        <th class="for">For</th>
                    </tr>
                </thead>
            <?php 
            if ($sources != null) {
                $i = 1;
                foreach ($sources as $source) { ?>
                    <tr class="<?php echo ($i % 2 ? 'rowodd' : 'roweven'); ?>"  
                        onclick="Budget.displayHistoryFromParent('<?php echo $source["giver_id"]; ?>');"                        
                    >
                    <?php
                    echo "<td>" . $source["transfer_date"] . "</td><td>" . 
                        $source["nickname"] . "</td><td>" . 
                        $source["amount_granted"] . "</td><td>" . 
                        $source["source_data"] . " (" . $source["reason"] . ")" .
                        "</td></tr>"; 
                    $i++;    
                }
            }
            ?> 
            </table>
        </div>
            <?php } ?>
            <input type="hidden" id="budget-receiver" name="receiver_id" value="<?php echo $user->getId(); ?>" />
        <br/>
        <label for="budget-note" class="info-label">Note: </label><br/>
            <textarea id="budget-note" name="budget-note" <?php echo $dis; ?>><?php echo $budget->notes; ?></textarea>
        <br/>
        <input type="button" value="<?php echo ($budgetClosed == 1) ? "OK" : "Save"; ?>" class="<?php echo ($budgetClosed == 1) ? "" : "iToolTip budgetSave" ?>" id="<?php echo ($budgetClosed == 1) ? "closeButton" : "updateButton"; ?>" />
        <?php if ($budget->active == 1){ ?>
            <input class="iToolTip budgetClose" type="button" value="Close Out" id="closeOutButton" />
            <?php 
                if ($budget->seed == 0) { ?>            
            <input class="iToolTip budgetAddFunds" type="button" value="Add Funds" id="addFundsButton" />
        <?php   }
            } ?>
    </form>
    <?php
    if (isset($_SESSION['userid']) && isset($_SESSION['is_runner']) && $_SESSION['is_runner']) {
        if (!isset($user)) {
            $user = new User();
            $user->findUserById($_SESSION['userid']);
        }
    ?>
        <div>
            <table id="be-table">
                <tr>
                    <td class="be-table_cell1 iToolTip budgetRemaining2"><strong>Remaining Funds:</strong></td>
                    <td class="be-table_cell2 iToolTip budgetRemaining2"><strong>
                    <?php echo(money_format('$ %i', $remaining)); ?></strong></td>
                </tr>
                <tr>
                    <td onclick="Budget.budgetExpand(0,<?php echo $budget->id; ?>)" class="be-table_cell1 iToolTip budgetAllocated2">Allocated:</td>
                    <td onclick="Budget.budgetExpand(0,<?php echo $budget->id; ?>)" class="be-table_cell2 iToolTip budgetAllocated2">
                    <?php echo(money_format('$ %i', $allocated)); ?></td>
                </tr>
                <tr>
                    <td onclick="Budget.budgetExpand(1,<?php echo $budget->id; ?>)" class="be-table_cell1 iToolTip budgetSubmitted2">Submitted:</td>
                    <td onclick="Budget.budgetExpand(1,<?php echo $budget->id; ?>)" class="be-table_cell2 iToolTip budgetSubmitted2">
                    <?php echo(money_format('$ %i', $submitted)); ?></td>
                </tr>
                <tr>
                    <td onclick="Budget.budgetExpand(2,<?php echo $budget->id; ?>)" class="be-table_cell1 iToolTip budgetPaid2">Paid:</td>
                    <td onclick="Budget.budgetExpand(2,<?php echo $budget->id; ?>)" class="be-table_cell2 iToolTip budgetPaid2">
                    <?php echo(money_format('$ %i', $paid)); ?></td>
                </tr>
                <tr>
                    <td onclick="Budget.budgetExpand(3,<?php echo $budget->id; ?>)" class="be-table_cell1 iToolTip budgetTransfered2">Transferred:</td>
                    <td onclick="Budget.budgetExpand(3,<?php echo $budget->id; ?>)" class="be-table_cell2 iToolTip budgetTransfered2">
                    <?php echo(money_format('$ %i', $transfered)); ?></td>
                </tr>
            </table>
        </div>
    <?php } ?>
    
</div><!-- end of give budget div-->

