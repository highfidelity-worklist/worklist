<div id="give-budget" title="Give Budget">
    <form method="post" action = "">
        <div class="currentBudgetArea" >
            <label for="budget-current" class="info-label">Total Available Funds (all budgets): </label>
            $<?php echo $user->getBudget(); ?>
        </div>
        <br/>
        <label for="budget-amount" class="info-label">New Budget to Give (USD): </label>
        <input type="text" id="budget-amount" name="amount" /> 
        <?php 
        if (strpos(BUDGET_AUTHORIZED_USERS, "," . $reqUserId . ",") !== false) { ?>
            <br/>
            <br/>
            <span id="budget_seed_area">
                <label for="budget-seed" class="info-label">Seed: </label>
                <input type="checkbox" id="budget-seed" name="budget-seed" />
            </span>
            <br/>
        <?php } ?>
        <br/>
        <label for="budget-source" class="info-label">Source: </label>
            <input type="text" id="budget-source" name="budget-source"/>
            <span id="budget-source-combo-area">
                <select id="budget-source-combo" name="budget-source-combo" class="project-dropdown">
                <option value="0" selected="selected">Select a budget</option>
                <?php echo $user->getBudgetCombo(); ?>
                </select>
            </span><br/><br/>
        <label for="budget-reason" class="info-label">For: </label>
            <input type="text" id="budget-reason" name="budget-reason"/><br/>       
            <input type="hidden" id="budget-receiver" name="receiver_id" value="<?php echo $user->getId();?>" />
        <br/>
        <label for="budget-note" class="info-label">Note: </label>
            <textarea id="budget-note" name="budget-note" ></textarea>
        <br/>
        <input type="submit" value="Give Budget" />
    </form>
</div><!-- end of give budget div-->

