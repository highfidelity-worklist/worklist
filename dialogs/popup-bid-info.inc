    <div id="popup-bid-info" class="popup-body" title = "Bid Info">
        <form name = "popup-form" id = "popup-form" action="" method="post">
        <input type="hidden" name="bid_id" value="" />
        <table border="0" style="border-width:0px">
            <tr>
                <td style="width:110px">Bidder</td>
                <td><span id="info-email"></span></td>
            </tr>
            <tr>
                <td>Amount</td>
                <td>$<span id="info-bid-amount"></span></td>
            </tr>
            <tr>
                <td>Bid Entered</td>
                <td><span id="info-bid-created"></span></td>
            </tr>
            <tr>
                <td>Bid Expires</td>
                <?php if ($worklist['status'] == 'SUGGESTEDwithBID') { ?>
                <td>None</td>
                <?php } else { ?>
                <td><span id="info-bid-expires"></span></td>
            </tr>
            <?php } ?>
            <tr>
                <td>Done In</td>
                <td><span id="info-bid-done-in"></span> </td>
            </tr>
            <tr>
                <td>Bid Accepted</td>
                <td><span id="info-bid-accepted"></span> </td>
            </tr>
            <tr>
                <td>Notes</td>
                <td style="text-align:justify;"><span id="info-notes"></span></td>
            </tr>
        </table>
        <div class="clear"></div>
        <br/>
        </form>
<?php if ($is_runner){ ?>
        <div class="clear"></div><br />
            <p class = "info-label block">
                Bidder statistics:
                    <img src="images/loader.gif" alt = "" class = "loader" /><span class = "loader">Please wait..</span>
            </p>

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
    </div>
