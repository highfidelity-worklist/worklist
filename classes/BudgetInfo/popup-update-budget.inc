<div id="update-budget">
    <form method="post" action="">
        <div class="row">
            <div class="col-md-4">
                <label for="budget-amount">Budget: </label>
            </div>
            <div class="col-md-6">
                $<span id="budget-amount"><?php echo $budget->amount; ?></span>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="budget-reason">For: </label>
            </div>
            <div class="col-md-6">
                <?php 
                    $dis = '';
                    if ($budgetClosed == 1) {
                        $dis = ' disabled="disabled" '; 
                    }
                ?>
                <input type="text" id="budget-reason" name="budget-reason" value="<?php echo $budget->reason; ?>" <?php echo $dis; ?>/>
            </div>
        </div>
        <?php if ((strpos(BUDGET_AUTHORIZED_USERS, "," . $reqUserId . ",") !== false 
              && $budget->seed == 1) || $budget->seed == 0): ?>
            <label class="info-label">Source(s): </label>
            <div class="budget-source">
                <table class="table table-striped" cellspacing="0" >
                    <thead>
                        <tr>
                            <th class="date">Date</th>
                            <th class="giver">Grantor</th>
                            <th class="amount">Amount</th>
                            <th class="for">From</th>
                        </tr>
                    </thead>
                <?php if ($sources != null): ?>
                    <?php foreach ($sources as $source): ?>
                        <tr onclick="Budget.displayHistoryFromParent('<?php echo $source["giver_id"]; ?>');">
                            <td><?php echo $source["transfer_date"]  ?></td>
                            <td><?php echo $source["nickname"] ?></td>
                            <td><?php echo $source["amount_granted"] ?></td>
                            <td>
                                <?php echo 
                                    $budget->seed == 0 
                                        ? "Budget ID: " . $source["budget_id"] . " - " . $source["reason"]
                                        : "Seed Budget: " . $source["source_data"] 
                                ?>
                            </td>
                    <?php endforeach; ?>
                <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>
        <input type="hidden" id="budget-receiver" name="receiver_id" value="<?php echo $user->getId(); ?>" />
        <label for="budget-note" class="info-label">Note: </label>
        <textarea id="budget-note" name="budget-note" class="form-control"
          <?php echo $dis; ?>><?php echo $budget->notes; ?></textarea>
        
        <input type="button" value="<?php echo ($budgetClosed == 1) ? "OK" : "Save"; ?>" class="<?php echo ($budgetClosed == 1) ? "" : "iToolTip budgetSave" ?>" id="<?php echo ($budgetClosed == 1) ? "closeButton" : "updateButton"; ?>" />
        <?php if ($budget->active == 1){ ?>
            <input class="iToolTip budgetClose" type="button" value="Close Out" id="closeOutButton" />
            <?php 
                if ($budget->seed == 0) { 
                    $title = "Add Funds to this budget";
                } else {
                    $title = "Add Funds to this seed budget";
                }?>            
            <input class="iToolTip budgetAddFunds" type="button" value="Add Funds" id="addFundsButton" title="<?php echo $title; ?>"/>
        <?php   
            } ?>
    </form>
    <br/><br/>
<?php
if (isset($_SESSION['userid']) && isset($_SESSION['is_runner']) && $_SESSION['is_runner']) {
    if (!isset($user)) {
        $user = new User();
        $user->findUserById($_SESSION['userid']);
    }
?>
        <div>
            <table id="be-table" class="table table-striped">
                <tr>
                    <td class="iToolTip budgetRemaining2"><strong>Remaining Funds:</strong></td>
                    <td class="iToolTip budgetRemaining2"><strong>
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