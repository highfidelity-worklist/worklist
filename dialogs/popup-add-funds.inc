<div id="addFundsDialog" class="hidden" title="Add Funds" style="text-align: left;">
    <form method="post" action="">
        <label for="budget-source" class="info-label">Source: </label>
        
        <?php if ($budget->seed == 1) { ?>
            <input type="hidden" id="budget-seed" />
        <?php } ?>
        <input type="text" id="budget-source" name="budget-source"/>
        <span id="budget-source-combo-area">
            <select id="budget-source-combo" name="budget-source-combo" class="project-dropdown hidden">
            <option value="0" selected="selected">Select a budget</option>
            <?php echo $user->getBudgetCombo(); ?>
            </select>
        </span>
        
        <br/>
        <div class="currentBudgetArea" >
            <label for="budget-current" class="info-label">Current Funds Granted: $</label>
            <?php echo $user-> getBudget(); ?>
        </div>
        <br/>
        <label for="amountToAdd" class="info-label">Amount to Add: </label>
        $<input type="text" id="amountToAdd" name="amountToAdd" /> 
        <br/>
        <br/>
        <div class="currentBudgetArea" >
            <label for="newBudgetTotal" class="info-label">New Budget Total: $</label>
             <span id="newBudgetTotal"></span>
        </div>
        <input type="hidden" id="budget-receiver" name="receiver_id" value="<?php echo $user-> getId(); ?>" />
        <input type="hidden" id="add_funds_to" name="add_funds_to" value="<?php echo $budget_id; ?>" />
        <br/>
        <br/>
        <input type="submit" value="OK" />
    </form>
</div><!-- end of give budget div-->

