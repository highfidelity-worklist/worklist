<div id="give-budget" class="hidden" title="Give Budget" style="text-align: left;">
    <form method="post" action = "">
        <div class="currentBudgetArea" >
            <label for="budget-current" class="info-label">Total Available Funds: $</label>
            <?php echo $user-> getBudget(); ?>
        </div>
        <label for="budget-amount" class="info-label">New Budget to Give: </label><br/>
            $<input type="text" id="budget-amount" name="amount" /> 
            <?php if (strpos(BUDGET_AUTHORIZED_USERS, "," . $reqUserId . ",") !== false) { ?>
            <span id="budget_seed_area">
                <label for="budget-seed" class="info-label">Seed: </label>
                <input type="checkbox" id="budget-seed" name="budget-seed"  />
            </span>
            <?php } ?>
            <br/>
        <label for="budget-reason" class="info-label">For: </label><br/>
            <input type="text" id="budget-reason" name="budget-reason"/><br/>
        <label for="budget-source" class="info-label">Source: </label>
        
            <input type="text" id="budget-source" name="budget-source"/>
            <span id="budget-source-combo-area">
                <select id="budget-source-combo" name="budget-source-combo" class="project-dropdown hidden">
                <option value="0" selected="selected">Select a budget</option>
                <?php echo $user->getBudgetCombo(); ?>
                </select>
            </span>
        
            <input type="hidden" id="budget-receiver" name="receiver_id" value="<?php echo $user-> getId();?>" />
        <br/>
        <label for="budget-note" class="info-label">Note: </label><br/>
            <textarea id="budget-note" name="budget-note" ></textarea>
        <br/>
        <input type="submit" value="Give Budget" />
    </form>
</div><!-- end of give budget div-->

