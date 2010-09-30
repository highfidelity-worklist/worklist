    <div id="popup-bid-info" class="popup-body" title = "Bid Info">
         <form name = "popup-form" id = "popup-form" action="" method="post">
		 <input type="hidden" name="bid_id" value="" />
		<table border="0" style="border-width:0px">
			 <tr>
				 <td style="width:110px">Email</td>
				 <td><span id="info-email"></span></td>
			 </tr>
			 <tr>
				 <td>Bid Amount</td>
				 <td>$<span id="info-bid-amount"></span></td>
			 </tr>
			 <tr>
				 <td>Bid Entered</td>
				 <td><span id="info-bid-created"></span></td>
			 </tr>
			 <tr>
				 <td>Done By</td>
				 <td><span id="info-bid-done-by"></span> </td>
			 </tr>
			 <tr>
				 <td>Bid Accepted</td>
				 <td><span id="info-bid-accepted"></span> </td>
			 </tr>
			 <tr>
				 <td>Time to Complete</td>
				 <td><span id="info-bid-timetocomplete"></span></td>
			 </tr>
			 <tr>
				 <td>Notes</td>
				 <td style="text-align:justify;"><span id="info-notes"></span></td>
			 </tr>
		</table>		 
        <div style = "clear: both;"></div>
		<br/>
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
