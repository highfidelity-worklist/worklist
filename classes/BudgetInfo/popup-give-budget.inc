<div id="give-budget" title="Give Budget">
    <form method="post" action="">
        <div class="currentBudgetArea" >
            <label for="budget-current">Total Available Funds (all budgets): </label>
            $<?php echo $user->getBudget(); ?>
        </div>
        <label for="budget-amount" class="info-label">New Budget to Give (USD): </label>
        <input type="text" id="budget-amount" name="amount" class="form-control" /> 
        <?php 
        if (strpos(BUDGET_AUTHORIZED_USERS, "," . $reqUserId . ",") !== false) { ?>
            <span id="budget_seed_area">
                <label for="budget-seed" class="info-label">Seed: </label>
                <input type="checkbox" id="budget-seed" name="budget-seed" class="form-control" />
            </span>
            <br/>
        <?php } ?>
        <br/>
        <label for="budget-source" class="info-label">Source: </label>
            <input type="text" id="budget-source" name="budget-source" class="form-control" />
            <span id="budget-source-combo-area">
                <select id="budget-source-combo" name="budget-source-combo">
                <option value="0" selected="selected">Select a budget</option>
                <?php echo $user->getBudgetCombo(); ?>
                </select>
            </span>
        <label for="budget-reason">For: 
        <input type="text" id="budget-reason" name="budget-reason" class="form-control" />
        <input type="hidden" id="budget-receiver" name="receiver_id" value="<?php echo $user->getId();?>" />
        <label for="budget-note" class="info-label">Note: </label>
        <textarea id="budget-note" name="budget-note" class="form-control" ></textarea>
        <input type="submit" value="Give Budget" />
    </form>
</div><!-- end of give budget div-->

