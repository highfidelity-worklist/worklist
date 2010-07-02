    <div id="popup-bid-info" class="popup-body" title = "Bid Info">
         <form name = "popup-form" id = "popup-form" action="" method="post">
            <div id = "left-column">
                <input type="hidden" name="bid_id" value="" />
                <p class = "info-label block">Email<br />
                <span id="info-email"></span>
                </p>

                <p class = "info-label block">Bid Amount<br />
                <span id="info-bid-amount"></span>
                </p>
            </div>

            <div id = "right-column">
                <p class = "info-label block">Done By<br />
                <span id="info-bid-done-by"></span>
                </p>

                <p class = "info-label block">Notes<br />
                <span id="info-notes"></span>
                </p>
            </div>

            <div style = "clear: both;"></div>

        </form>
<?php if ($is_runner){ ?>
            <div style = "clear: both;" ></div><br />
            <p class = "info-label block">
                Bidder statistics:
                    <img src="images/loader.gif" alt = "" class = "loader" /><span class = "loader">Please wait..</span>
            </p>

            <table id = "user-stats">
                <thead>
                    <tr class = "table-hdng">
                        <th>Total Jobs</th><th>Active Jobs</th><th>Total $</th><th>Fees (30 days)</th><th>Love</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><a href = "#" id = "total-jobs"></a></td>
                        <td><a href = "#" id = "active-jobs"></a></td>
                        <td id = "total-earnings"></td>
                        <td><a href = "#" id = "latest-earnings"></a></td>
                        <td><a href = "#" id = "love"></a></td>
                    </tr>
                </tbody>
            </table>
<?php } ?>
    </div>
