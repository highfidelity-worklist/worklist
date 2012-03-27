    <div id="popup-bid-info" class="popup-body" title = "Bid Info">
        <form name = "popup-form" id = "popup-form" action="" method="post">
        <input type="hidden" name="bid_id" value="" />
        <input type="hidden" id="budget_id" name="budget_id" value="" />
        <table border="0" style="border-width:0px">
            <tr>
                <td class="bid-info-label" style="width:110px">Bidder</td>
                <td><span id="info-email"></span></td>
            </tr>
            <tr>
                <td class="bid-info-label">Amount</td>
                <td>$<span id="info-bid-amount"></span></td>
            </tr>
            <tr>
                <td class="bid-info-label">Bid Entered</td>
                <td><span id="info-bid-created"></span></td>
            </tr>
            <tr>
                <td class="bid-info-label">Bid Expires</td>
                <td><span id="info-bid-expires"></span></td>
            </tr>
            <tr>
                <td class="bid-info-label">Done In</td>
                <td><span id="info-bid-done-in"></span> </td>
            </tr>
            <tr id="bidAcceptedRow">
                <td class="bid-info-label">Bid Accepted</td>
                <td><span id="info-bid-accepted"></span> </td>
            </tr>
            <tr>
                <td class="bid-info-label notes">Notes</td>
                <td style="text-align:justify;"><div id="info-notes"></div></td>
            </tr>
        </table>
        <div class="clear"></div>
<?php if ($is_runner){ ?>
        <div class="clear"></div><br />
            <div class = "info-label block bidderStats">
                Bidder statistics
                    <img src="images/loader.gif" alt = "" class = "loader" /><span class = "loader">Please wait..</span>
            </div>

            <table id = "user-stats">
                <thead>
                    <tr class = "table-hdng">
                        <th>Total / Active Jobs</th><th>Total / 30 day $</th><th>Bonus $ / %</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><a href = "#" id = "total-jobs"></a> / <a href = "#" id = "active-jobs"></a></td>
                        <td ><span id = "total-earnings"></span> / <span id = "latest_earnings"></span></td>
                        <td><span id = "total-bonus"></span> / <span id = "percent_bonus"></span></td>
                    </tr>
                </tbody>
            </table>
<?php } ?>
        <div style="clear:both"></div>
        <div id="popup-bid-info-buttons"></div>
        </form>
    </div>
<?php if ($is_runner){ ?>
        <div id="popupSelectBudget" style="display:none" title="Select a budget">
            <label for="budget-source" class="info-label">Source: </label>
            <div class="budget-source">
                <span id="budget-source-combo-area">
                    <select id="budget-source-combo" name="budget-source-combo" class="divComboBox">
                        <option value="0" selected="selected">Select a budget</option>
                    <?php 
                        echo $user->getBudgetCombo(); 
                    ?>
                    </select>
                </span>
            </div>
            <div>
                <input type="button" id="confirm_budget"  value="OK">
                <input type="button" id="cancel_budget" value="Cancel">
            </div>
        </div>
<?php } ?>