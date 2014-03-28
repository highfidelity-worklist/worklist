<div id="addFundsDialog" title="Add Funds">
    <form method="post" action="">
        <div class="currentBudgetArea row" >
            <label for="budget-current" class="col-md-4">Total Funds Available (all budgets):</label>
            <span class="col-md-8">$<?php echo $user->getBudget(); ?></span>
        </div>
        <div class="row">
            <label for="budget-source" class="col-md-4">Source: </label>
            <?php if ($budget->seed == 1) { ?>
                <input type="hidden" id="budget-seed" />
            <?php } ?>
            <div class="col-md-8">
                <input type="text" id="budget-source" name="budget-source"/>
                <span id="budget-source-combo-area">
                    <select id="budget-source-combo" name="budget-source-combo">
                    <option value="0" selected="selected">Select a budget</option>
                    <?php echo $user->getBudgetCombo(); ?>
                    </select>
                </span>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <label for="amountToAdd">Amount to Add (USD): </label>
            </div>
            <div class="col-md-6">
                <input type="text" id="amountToAdd" name="amountToAdd" class="form-control" /> 
            </div>
        </div>
        <div class="currentBudgetArea" >
            <label for="newBudgetTotal">New Budget Total: </label>
             $<span id="newBudgetTotal"></span>
        </div>
        <input type="hidden" id="budget-receiver" name="receiver_id" value="<?php echo $user->getId(); ?>" />
        <input type="hidden" id="add_funds_to" name="add_funds_to" value="<?php echo $budget_id; ?>" />
        <input type="submit" value="OK" />
    </form>
</div><!-- end of give budget div-->

